<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/payu.php';

function hms_payu_redirect($url) {
	header('Location: ' . $url);
	exit();
}

$payload = !empty($_POST) ? $_POST : $_REQUEST;
$appointmentId = (int)($payload['udf1'] ?? 0);
$userId = (int)($payload['udf2'] ?? 0);
$backUrl = 'appointments.php';
if ($appointmentId > 0) {
	$backUrl = 'pay-fees.php?appointment_id=' . $appointmentId;
}

if ($userId > 0) {
	hms_restore_user_session($con, $userId);
}

if (empty($payload)) {
	$pendingTxnId = '';
	if ($appointmentId > 0 && !empty($_SESSION['payu_pending_txn_' . $appointmentId])) {
		$pendingTxnId = trim((string)$_SESSION['payu_pending_txn_' . $appointmentId]);
	}
	if ($pendingTxnId === '' && $appointmentId > 0 && $userId > 0) {
		$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
		$appointment = $context['appointment'] ?? null;
		$pendingTxnId = trim((string)($appointment['paymentRef'] ?? ''));
	}
	if ($pendingTxnId !== '' && $appointmentId > 0 && $userId > 0) {
		$reconciled = hms_reconcile_payu_transaction($con, $appointmentId, $userId, $pendingTxnId);
		if ($reconciled !== false) {
			$_SESSION['msg'] = 'Payment successful for appointment #' . $appointmentId . '.';
			hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
		}
	}

	$_SESSION['payu_error'] = 'PayU did not return a payment response.';
	hms_payu_redirect($backUrl);
}

if (!hms_payu_is_configured()) {
	$_SESSION['payu_error'] = 'PayU configuration is missing on the server.';
	hms_payu_redirect($backUrl);
}

if ($appointmentId <= 0 || $userId <= 0) {
	$_SESSION['payu_error'] = 'The PayU response is missing appointment details.';
	hms_payu_redirect('appointments.php');
}

if (!hms_verify_payu_response_hash($payload)) {
	$pendingTxnId = trim((string)($payload['txnid'] ?? ''));
	if ($pendingTxnId !== '') {
		$reconciled = hms_reconcile_payu_transaction($con, $appointmentId, $userId, $pendingTxnId);
		if ($reconciled !== false) {
			$_SESSION['msg'] = 'Payment successful for appointment #' . $appointmentId . '.';
			hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
		}
	}
	$_SESSION['payu_error'] = 'PayU response validation failed.';
	hms_payu_redirect($backUrl);
}

$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
$appointment = $context['appointment'] ?? null;
$table = (string)($context['table'] ?? hms_get_payment_appointment_table($con));

if (!$appointment) {
	$_SESSION['payu_error'] = 'Unable to locate the appointment returned by PayU.';
	hms_payu_redirect('appointments.php');
}

$expectedKey = hms_payu_merchant_key();
$responseKey = trim((string)($payload['key'] ?? ''));
if ($expectedKey === '' || !hash_equals($expectedKey, $responseKey)) {
	$_SESSION['payu_error'] = 'PayU merchant key mismatch.';
	hms_payu_redirect($backUrl);
}

$expectedAmount = hms_payu_format_amount($appointment['consultancyFees'] ?? 0);
$responseAmount = hms_payu_format_amount($payload['amount'] ?? 0);
if ($expectedAmount !== $responseAmount) {
	$_SESSION['payu_error'] = 'PayU returned an amount that does not match the appointment fee.';
	hms_payu_redirect($backUrl);
}

$status = strtolower(trim((string)($payload['status'] ?? '')));
$transactionRef = trim((string)($payload['mihpayid'] ?? $payload['txnid'] ?? ''));
$fieldMessage = trim((string)($payload['error_Message'] ?? $payload['field9'] ?? ''));
$statusMessage = $fieldMessage !== '' ? $fieldMessage : 'Payment was not completed on PayU.';

if ($status !== 'success') {
	$pendingTxnId = trim((string)($payload['txnid'] ?? ''));
	if ($pendingTxnId !== '') {
		$reconciled = hms_reconcile_payu_transaction($con, $appointmentId, $userId, $pendingTxnId);
		if ($reconciled !== false) {
			$_SESSION['msg'] = 'Payment successful for appointment #' . $appointmentId . '.';
			hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
		}
	}
	$_SESSION['payu_error'] = $statusMessage;
	hms_payu_redirect($backUrl);
}

if (!empty($context['is_paid'])) {
	$_SESSION['msg'] = 'Payment already recorded for appointment #' . $appointmentId . '.';
	hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
}

if ($transactionRef === '') {
	$_SESSION['payu_error'] = 'PayU did not return a valid transaction reference.';
	hms_payu_redirect($backUrl);
}

$existing = hms_query_params($con, "SELECT id FROM payment_transactions WHERE transaction_ref=$1 LIMIT 1", [$transactionRef]);
if ($existing && hms_num_rows($existing) > 0) {
	$_SESSION['msg'] = 'Payment already recorded for appointment #' . $appointmentId . '.';
	hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
}

$amount = (float)($appointment['consultancyFees'] ?? 0);
$paidAt = date('Y-m-d H:i:s');
$transactionStarted = function_exists('mysqli_begin_transaction') ? @mysqli_begin_transaction($con) : false;
$updateResult = hms_query_params(
	$con,
	"UPDATE $table SET paymentStatus='Paid', paymentRef=$1, paidAt=$2 WHERE id=$3 AND userId=$4",
	[$transactionRef, $paidAt, $appointmentId, $userId]
);
$updateOk = (bool)$updateResult;
$logOk = $updateOk && hms_record_payment_transaction(
	$con,
	$appointmentId,
	$userId,
	$amount,
	'PayU',
	$transactionRef,
	'Paid',
	$paidAt
);

if ($transactionStarted) {
	if ($updateOk && $logOk) {
		@mysqli_commit($con);
	} else {
		@mysqli_rollback($con);
	}
}

if (!$updateOk || !$logOk) {
	$_SESSION['payu_error'] = 'Payment was approved on PayU, but the appointment could not be updated locally.';
	hms_payu_redirect($backUrl);
}

$_SESSION['msg'] = 'Payment successful for appointment #' . $appointmentId . '.';
hms_payu_redirect('payment-receipt.php?appointment_id=' . $appointmentId . '&download=1');
?>
