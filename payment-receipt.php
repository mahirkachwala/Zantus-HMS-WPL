<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/checklogin.php';
check_login();
require_once __DIR__ . '/include/hms-pdf.php';

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$userId = (int)($_SESSION['id'] ?? 0);
list($appointment, $payment) = hms_find_user_payment_record($con, $appointmentId, $userId);

if (!$appointment || !$payment) {
	$_SESSION['msg'] = 'Payment receipt is not available for this appointment.';
	header('location:appointment-history.php');
	exit();
}

$paymentStatus = trim((string)($payment['status'] ?? $appointment['paymentStatusResolved'] ?? 'Pending'));
if (!in_array(strtolower($paymentStatus), ['paid', 'paid at hospital'], true)) {
	$_SESSION['msg'] = 'Payment receipt is only available for completed payments.';
	header('location:appointment-history.php');
	exit();
}

$transactionRef = (string)($payment['transaction_ref'] ?? '');
$paidAt = (string)($payment['paid_at'] ?? '');
$method = (string)($payment['payment_method'] ?? '');
$receiptNumber = 'PAY-' . (int)$appointment['id'] . '-' . ($transactionRef !== '' ? $transactionRef : date('YmdHis'));

try {
	$pdf = hms_create_pdf_document('Payment Receipt');
	$pdf->writeHTML(
		hms_pdf_block_title('Payment Details') .
		hms_pdf_label_value_table([
			'Receipt Number' => $receiptNumber,
			'Appointment ID' => (int)$appointment['id'],
			'Patient Name' => $appointment['patientName'] ?? '',
			'Doctor Name' => $appointment['doctorName'] ?? '',
			'Specialization' => $appointment['doctorSpecialization'] ?? '',
			'Amount Paid' => hms_pdf_money($payment['amount'] ?? $appointment['consultancyFees'] ?? 0),
			'Payment Status' => $paymentStatus,
			'Payment Method' => $method !== '' ? $method : $appointment['paymentStatusResolved'],
			'Transaction Reference' => $transactionRef !== '' ? $transactionRef : ($appointment['paymentRef'] ?? ''),
			'Paid At' => $paidAt !== '' ? $paidAt : ($appointment['paidAt'] ?? ''),
			'Appointment Date' => $appointment['appointmentDate'] ?? '',
			'Appointment Time' => $appointment['appointmentTime'] ?? '',
		])
	);

	$pdf->Ln(4);
	$pdf->writeHTML(
		hms_pdf_block_title('Important') .
		'<div style="line-height:1.6;color:#334155;">This is a computer-generated payment receipt from Zantus HMS. Please quote the transaction reference shown above for any billing or support query.</div>'
	);

	hms_pdf_output_inline($pdf, 'payment-receipt-' . (int)$appointment['id'] . '.pdf');
} catch (\Throwable $e) {
	$_SESSION['msg'] = 'Payment receipt is temporarily unavailable. Please verify the TCPDF upload on the server.';
	header('location:appointment-history.php');
	exit();
}
?>
