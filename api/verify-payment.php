<?php
require_once __DIR__ . '/../include/session.php';
hms_session_start();
require_once __DIR__ . '/../include/razorpay.php';

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
	hms_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$userId = hms_api_user_id();
if ($userId <= 0) {
	hms_json_response(401, ['success' => false, 'message' => 'Authentication required.']);
}

if (!hms_razorpay_is_configured()) {
	hms_json_response(500, ['success' => false, 'message' => 'Razorpay is not configured.']);
}

$payload = hms_json_input();
$appointmentId = (int)($payload['appointment_id'] ?? 0);
$orderId = trim((string)($payload['razorpay_order_id'] ?? ''));
$paymentId = trim((string)($payload['razorpay_payment_id'] ?? ''));
$signature = trim((string)($payload['razorpay_signature'] ?? ''));

if ($appointmentId <= 0 || $orderId === '' || $paymentId === '' || $signature === '') {
	hms_json_response(400, ['success' => false, 'message' => 'Missing required payment fields.']);
}

$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
if (!empty($context['error']) && empty($context['is_paid'])) {
	hms_json_response(400, ['success' => false, 'message' => $context['error']]);
}

if (!hms_verify_razorpay_signature($orderId, $paymentId, $signature)) {
	hms_json_response(400, ['success' => false, 'message' => 'Payment signature mismatch.']);
}

$table = $context['table'];
$appointment = $context['appointment'];
$amount = (float)($appointment['consultancyFees'] ?? 0);

$existing = hms_query_params($con, "SELECT id FROM payment_transactions WHERE transaction_ref=$1 LIMIT 1", [$paymentId]);
	if ($existing && hms_num_rows($existing) > 0) {
		$_SESSION['msg'] = 'Payment already recorded for appointment #' . $appointmentId . '.';
		hms_json_response(200, [
			'success' => true,
			'message' => $_SESSION['msg'],
			'redirect_url' => 'appointments.php',
		]);
	}

$paidAt = date('Y-m-d H:i:s');
$transactionStarted = function_exists('mysqli_begin_transaction') ? @mysqli_begin_transaction($con) : false;
$updateResult = hms_query_params(
	$con,
	"UPDATE $table SET paymentStatus='Paid', paymentRef=$1, paidAt=$2 WHERE id=$3 AND userId=$4",
	[$paymentId, $paidAt, $appointmentId, $userId]
);
$updateOk = (bool)$updateResult;
$logOk = $updateOk && hms_record_payment_transaction(
	$con,
	$appointmentId,
	$userId,
	$amount,
	'Razorpay',
	$paymentId,
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
	hms_json_response(500, ['success' => false, 'message' => 'Payment verified but the appointment could not be updated. Please contact support.']);
}

$_SESSION['msg'] = 'Payment successful for appointment #' . $appointmentId . '.';
hms_json_response(200, [
	'success' => true,
	'message' => $_SESSION['msg'],
	'redirect_url' => 'appointments.php',
]);
?>

