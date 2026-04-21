<?php
if (!ob_get_level()) {
	ob_start();
}
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
	$pdf = hms_create_pdf_document('Payment Receipt', 'receipt');

	$amountPaid = hms_pdf_money($payment['amount'] ?? $appointment['consultancyFees'] ?? 0);
	$statusBadge = hms_pdf_status_badge_html($paymentStatus);

	$summaryHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="60%" style="border:1px solid #dbeafe;background-color:#ffffff;padding:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.6px;text-transform:uppercase;">Payment Receipt</div>'
		. '<div style="font-size:16px;color:#0f172a;font-weight:bold;padding-top:4px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:9px;color:#475569;padding-top:8px;">Paid by <strong>' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</strong> for consultation with <strong>' . hms_pdf_html_escape($appointment['doctorName'] ?? '') . '</strong>.</div>'
		. '<div style="padding-top:8px;">' . $statusBadge . '</div>'
		. '</td>'
		. '<td width="4%"></td>'
		. '<td width="36%" style="border:1px solid #a7f3d0;background-color:#ecfdf5;padding:12px;">'
		. '<div style="font-size:8px;color:#047857;text-transform:uppercase;">Total Paid</div>'
		. '<div style="font-size:22px;color:#064e3b;font-weight:bold;padding-top:6px;">' . hms_pdf_html_escape($amountPaid) . '</div>'
		. '<div style="font-size:9px;color:#065f46;padding-top:10px;">Method: ' . hms_pdf_html_escape($method !== '' ? $method : $appointment['paymentStatusResolved']) . '</div>'
		. '</td>'
		. '</tr></table>';

	$pdf->writeHTML($summaryHtml, true, false, true, false, '');
	$pdf->Ln(4);
	$pdf->writeHTML(hms_pdf_kv_grid_html([
		'Appointment ID' => (int)$appointment['id'],
		'Patient Name' => $appointment['patientName'] ?? '',
		'Doctor Name' => $appointment['doctorName'] ?? '',
		'Specialization' => $appointment['doctorSpecialization'] ?? '',
		'Transaction Reference' => $transactionRef !== '' ? $transactionRef : ($appointment['paymentRef'] ?? ''),
		'Paid At' => $paidAt !== '' ? $paidAt : ($appointment['paidAt'] ?? ''),
		'Appointment Date' => $appointment['appointmentDate'] ?? '',
		'Appointment Time' => $appointment['appointmentTime'] ?? '',
	], 2), true, false, true, false, '');

	$pdf->Ln(3);
	$pdf->writeHTML(
		hms_pdf_simple_table_html(
			['Description', 'Price', 'Qty', 'Total'],
			[[
				hms_pdf_html_escape('Consultation fee - ' . ($appointment['doctorSpecialization'] ?? 'General Visit'), ''),
				hms_pdf_html_escape($amountPaid, ''),
				'1',
				hms_pdf_html_escape($amountPaid, ''),
			]],
			[52, 16, 12, 20],
			'#2563eb',
			'#ffffff'
		),
		true,
		false,
		true,
		false,
		''
	);

	$pdf->Ln(4);
	$pdf->writeHTML(
		hms_pdf_note_box_html(
			'Billing Note',
			'This is a computer-generated Zantus HMS payment receipt. Please keep the transaction reference for billing support, reconciliation, or refund review.',
			'teal'
		),
		true,
		false,
		true,
		false,
		''
	);

	hms_pdf_output_inline($pdf, 'payment-receipt-' . (int)$appointment['id'] . '.pdf');
} catch (\Throwable $e) {
	$_SESSION['msg'] = 'Payment receipt is temporarily unavailable. Please verify the TCPDF upload on the server.';
	header('location:appointment-history.php');
	exit();
}
?>
