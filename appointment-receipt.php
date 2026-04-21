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
$appointment = hms_find_user_appointment($con, $appointmentId, $userId);

if (!$appointment) {
	$_SESSION['msg'] = 'Appointment receipt could not be generated.';
	header('location:appointments.php');
	exit();
}

try {
	$pdf = hms_create_pdf_document('Appointment Receipt', 'receipt');

	$paymentBadge = hms_pdf_status_badge_html($appointment['paymentStatusResolved'] ?? 'Pending');
	$visitBadge = hms_pdf_status_badge_html($appointment['visitStatusResolved'] ?? 'Scheduled');
	$receiptNumber = 'APPT-' . (int)$appointment['id'];
	$doctorLine = hms_pdf_clean_doctor_name($appointment['doctorName'] ?? '');
	$doctorDisplay = 'Dr. ' . $doctorLine;
	$signaturePath = hms_pdf_invoice_signature_path();
	$paymentStatus = trim((string)($appointment['paymentStatusResolved'] ?? 'Pending'));
	$isOnlinePayment = !empty($appointment['paymentRef']) && strcasecmp($paymentStatus, 'Paid at Hospital') !== 0;
	$razorpayLogoPath = $isOnlinePayment ? hms_pdf_razorpay_logo_path() : '';

	$headerHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="64%" style="padding-right:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Appointment Booking Invoice</div>'
		. '<div style="font-size:20px;color:#0f172a;font-weight:bold;padding-top:4px;">' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</div>'
		. '<div style="font-size:10px;color:#475569;padding-top:4px;">Consultation scheduled with <strong>' . hms_pdf_html_escape($doctorDisplay, '') . '</strong></div>'
		. '<div style="font-size:9px;color:#2563eb;padding-top:5px;letter-spacing:0.5px;text-transform:uppercase;">' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? '') . '</div>'
		. '</td>'
		. '<td width="36%" style="padding-left:10px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Invoice No.</div>'
		. '<div style="font-size:13px;color:#1d4ed8;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;padding-top:10px;">Consultation Fee</div>'
		. '<div style="font-size:24px;color:#0f172a;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape(hms_pdf_money($appointment['consultancyFees'] ?? 0)) . '</div>'
		. '<div style="padding-top:10px;">' . $paymentBadge . '&nbsp;&nbsp;' . $visitBadge . '</div>'
		. '</td>'
		. '</tr>'
		. '</table>'
		. '<div style="border-bottom:1px solid #bfdbfe;padding-top:10px;"></div>';

	$pdf->writeHTML($headerHtml, true, false, true, false, '');
	$pdf->Ln(3);
	$detailsHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Booking Details</div>';
	$detailsHtml .= '<table cellpadding="3" cellspacing="0" border="0" width="100%">';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment ID</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . (int)$appointment['id'] . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Booked On</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['postingDate'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment Date</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentDate'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment Time</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentTime'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Patient Email</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['patientEmail'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Doctor Email</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['docEmail'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Physician</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($doctorDisplay, '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Record Source</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['sourceTable'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '</table>';
	$pdf->writeHTML($detailsHtml, true, false, true, false, '');

	$pdf->Ln(3);
	$chargeHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Charge Summary</div>';
	$chargeHtml .= '<table cellpadding="5" cellspacing="0" border="0" width="100%">';
	$chargeHtml .= '<tr>'
		. '<td width="58%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;">Description</td>'
		. '<td width="17%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;">Qty</td>'
		. '<td width="25%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;" align="right">Amount</td>'
		. '</tr>';
	$chargeHtml .= '<tr>'
		. '<td width="58%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">Consultation booking for ' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? 'General Consultation', '') . '</td>'
		. '<td width="17%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">1</td>'
		. '<td width="25%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:bold;" align="right">' . hms_pdf_html_escape(hms_pdf_money($appointment['consultancyFees'] ?? 0)) . '</td>'
		. '</tr>';
	$chargeHtml .= '</table>';
	$pdf->writeHTML($chargeHtml, true, false, true, false, '');

	if ($isOnlinePayment && $razorpayLogoPath !== '') {
		$pdf->Ln(2);
		$pdf->writeHTML('<div style="font-size:8px;color:#64748b;letter-spacing:0.7px;text-transform:uppercase;">Paid via</div>', true, false, true, false, '');
		$pdf->Image($razorpayLogoPath, 14, $pdf->GetY(), 42, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
		$pdf->Ln(16);
	}

	$pdf->Ln(1);
	$pdf->writeHTML(
		'<div style="font-size:10px;color:#334155;line-height:1.7;border-left:3px solid #2dd4bf;padding-left:10px;">'
		. 'Please keep this invoice available during check-in. Arrive 10 minutes before the slot and quote Appointment ID <strong>' . (int)$appointment['id'] . '</strong> for faster desk verification.'
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
		. '<div style="border-top:1px solid #94a3b8;padding-top:6px;font-size:8px;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;">Confirmed by</div>'
		. '<div style="font-size:10px;color:#0f172a;font-weight:bold;padding-top:2px;">Zantus HMS</div>'
		. '</td>'
		. '</tr></table>',
		true,
		false,
		true,
		false,
		''
	);

	hms_pdf_output_inline($pdf, 'appointment-receipt-' . (int)$appointment['id'] . '.pdf');
} catch (\Throwable $e) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'APPOINTMENT RECEIPT DEBUG ERROR: ' . $e->getMessage() . "\n";
	echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
	exit();
}
?>
