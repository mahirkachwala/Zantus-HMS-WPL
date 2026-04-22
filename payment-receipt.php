<?php
if (!ob_get_level()) {
	ob_start();
}
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/config.php';
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
$forceDownload = isset($_GET['download']) && (string)$_GET['download'] === '1';

try {
	$pdf = hms_create_pdf_document('Payment Receipt', 'receipt');

	$amountPaid = hms_pdf_money($payment['amount'] ?? $appointment['consultancyFees'] ?? 0);
	$statusBadge = hms_pdf_status_badge_html($paymentStatus);
	$doctorLine = hms_pdf_clean_doctor_name($appointment['doctorName'] ?? '');
	$doctorDisplay = 'Dr. ' . $doctorLine;
	$signaturePath = hms_pdf_invoice_signature_path();
	$isRazorpay = stripos($method, 'razorpay') !== false;
	$razorpayLogoPath = $isRazorpay ? hms_pdf_razorpay_logo_path() : '';

	$summaryHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="63%" style="padding-right:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Medical Payment Invoice</div>'
		. '<div style="font-size:18px;color:#0f172a;font-weight:bold;padding-top:4px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:10px;color:#475569;padding-top:5px;">Paid by <strong>' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</strong> for consultation with <strong>' . hms_pdf_html_escape($doctorDisplay, '') . '</strong>.</div>'
		. '<div style="padding-top:9px;">' . $statusBadge . '</div>'
		. '</td>'
		. '<td width="37%" style="padding-left:8px;">'
		. '<div style="font-size:8px;color:#047857;letter-spacing:0.8px;text-transform:uppercase;">Total Paid</div>'
		. '<div style="font-size:26px;color:#064e3b;font-weight:bold;padding-top:4px;">' . hms_pdf_html_escape($amountPaid) . '</div>'
		. '<div style="font-size:9px;color:#065f46;padding-top:10px;">Method: ' . hms_pdf_html_escape($method !== '' ? $method : $appointment['paymentStatusResolved']) . '</div>'
		. '</td>'
		. '</tr>'
		. '</table>'
		. '<div style="border-bottom:1px solid #bfdbfe;padding-top:10px;"></div>';

	$pdf->writeHTML($summaryHtml, true, false, true, false, '');
	$pdf->Ln(3);
	$paymentMetaHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Payment Information</div>';
	$paymentMetaHtml .= '<table cellpadding="3" cellspacing="0" border="0" width="100%">';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment ID</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . (int)$appointment['id'] . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Paid At</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($paidAt !== '' ? $paidAt : ($appointment['paidAt'] ?? '')) . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Patient</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Physician</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($doctorDisplay, '') . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Specialization</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Transaction Ref</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($transactionRef !== '' ? $transactionRef : ($appointment['paymentRef'] ?? '')) . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Visit Date</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentDate'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Visit Time</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentTime'] ?? '') . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '</table>';
	$pdf->writeHTML($paymentMetaHtml, true, false, true, false, '');

	$pdf->Ln(3);
	$lineItemHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Invoice Line Item</div>';
	$lineItemHtml .= '<table cellpadding="5" cellspacing="0" border="0" width="100%">';
	$lineItemHtml .= '<tr>'
		. '<td width="55%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;">Description</td>'
		. '<td width="15%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;">Qty</td>'
		. '<td width="15%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;">Unit Price</td>'
		. '<td width="15%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;" align="right">Total</td>'
		. '</tr>';
	$lineItemHtml .= '<tr>'
		. '<td width="55%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">Consultation fee - ' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? 'General Visit', '') . '</td>'
		. '<td width="15%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">1</td>'
		. '<td width="15%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">' . hms_pdf_html_escape($amountPaid, '') . '</td>'
		. '<td width="15%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:bold;" align="right">' . hms_pdf_html_escape($amountPaid, '') . '</td>'
		. '</tr>';
	$lineItemHtml .= '</table>';
	$pdf->writeHTML($lineItemHtml, true, false, true, false, '');

	$pdf->Ln(4);
	if ($isRazorpay && $razorpayLogoPath !== '') {
		$pdf->writeHTML('<div style="font-size:8px;color:#64748b;letter-spacing:0.7px;text-transform:uppercase;">Paid via</div>', true, false, true, false, '');
		$pdf->Image($razorpayLogoPath, 14, $pdf->GetY(), 42, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
		$pdf->Ln(16);
	}

	$pdf->writeHTML(
		'<div style="font-size:10px;color:#334155;line-height:1.7;border-left:3px solid #2dd4bf;padding-left:10px;">'
		. 'This is a computer-generated Zantus HMS payment receipt. Please keep the transaction reference available for billing support, reconciliation, or refund review.'
		. '</div>',
		true,
		false,
		true,
		false,
		''
	);

	$pdf->Ln(8);
	$signatureStartY = $pdf->GetY();
	$signatureWidth = 46;
	$signatureHeight = 16;
	$signatureX = $pdf->getPageWidth() - 14 - $signatureWidth;
	if ($signaturePath !== '' && file_exists($signaturePath)) {
		$pdf->Image($signaturePath, $signatureX, $signatureStartY, $signatureWidth, $signatureHeight, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
	}
	$pdf->SetY($signatureStartY + 14);
	$pdf->writeHTML(
		'<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="58%"></td>'
		. '<td width="42%" align="center">'
		. '<div style="border-top:1px solid #94a3b8;padding-top:6px;font-size:8px;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;">Verified by</div>'
		. '<div style="font-size:10px;color:#0f172a;font-weight:bold;padding-top:2px;">Zantus HMS</div>'
		. '</td>'
		. '</tr></table>',
		true,
		false,
		true,
		false,
		''
	);

	$filename = 'payment-receipt-' . (int)$appointment['id'] . '.pdf';
	if ($forceDownload) {
		hms_pdf_output_download($pdf, $filename);
	}
	hms_pdf_output_inline($pdf, $filename);
} catch (\Throwable $e) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'PAYMENT RECEIPT DEBUG ERROR: ' . $e->getMessage() . "\n";
	echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
	exit();
}
?>
