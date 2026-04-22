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

	$paymentStatus = trim((string)($appointment['paymentStatusResolved'] ?? 'Pending'));
	$visitStatus = trim((string)($appointment['visitStatusResolved'] ?? 'Scheduled'));
	$paymentCard = hms_pdf_status_card_html('Payment Status', $paymentStatus);
	$visitCard = hms_pdf_status_card_html('Visit Status', $visitStatus, 'blue');
	$receiptNumber = 'APPT-' . (int)$appointment['id'];
	$doctorLine = hms_pdf_clean_doctor_name($appointment['doctorName'] ?? '');
	$doctorDisplay = 'Dr. ' . $doctorLine;
	$signaturePath = hms_pdf_invoice_signature_path();
	$isOnlinePayment = !empty($appointment['paymentRef']) && strcasecmp($paymentStatus, 'Paid at Hospital') !== 0;
	$payuLogoPath = $isOnlinePayment ? hms_pdf_payu_logo_path() : '';

	$headerHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="64%" style="padding-right:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Appointment Booking Invoice</div>'
		. '<div style="font-size:18px;color:#0f172a;font-weight:bold;padding-top:3px;">' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</div>'
		. '<div style="font-size:9px;color:#475569;padding-top:3px;">Consultation scheduled with <strong>' . hms_pdf_html_escape($doctorDisplay, '') . '</strong></div>'
		. '<div style="font-size:8px;color:#2563eb;padding-top:4px;letter-spacing:0.5px;text-transform:uppercase;">' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? '') . '</div>'
		. '</td>'
		. '<td width="36%" style="padding-left:10px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Invoice No.</div>'
		. '<div style="font-size:13px;color:#1d4ed8;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;padding-top:8px;">Consultation Fee</div>'
		. '<div style="font-size:22px;color:#0f172a;font-weight:bold;padding-top:1px;">' . hms_pdf_html_escape(hms_pdf_money($appointment['consultancyFees'] ?? 0)) . '</div>'
		. '</td>'
		. '</tr>'
		. '</table>'
		. '<div style="border-bottom:1px solid #bfdbfe;padding-top:6px;"></div>';

	$pdf->writeHTML($headerHtml, true, false, true, false, '');
	$pdf->Ln(1);
	$pdf->writeHTML(
		'<table cellpadding="0" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="48%">' . $paymentCard . '</td>'
		. '<td width="4%"></td>'
		. '<td width="48%">' . $visitCard . '</td>'
		. '</tr>'
		. '</table>',
		true,
		false,
		true,
		false,
		''
	);
	$pdf->Ln(1);
	$detailsHtml = '<div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:3px;">Booking Details</div>';
	$detailsHtml .= '<table cellpadding="2" cellspacing="0" border="0" width="100%">';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment ID</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . (int)$appointment['id'] . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Booked On</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['postingDate'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment Date</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentDate'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment Time</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentTime'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Patient Email</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['patientEmail'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Doctor Email</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['docEmail'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Physician</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($doctorDisplay, '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Record Source</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['sourceTable'] ?? '') . '</td>'
		. '</tr>';
	$detailsHtml .= '</table>';
	$pdf->writeHTML($detailsHtml, true, false, true, false, '');

	$pdf->Ln(2);
	$chargeHtml = '<div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:3px;">Charge Summary</div>';
	$chargeHtml .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">';
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

	$pageWidth = $pdf->getPageWidth();
	$leftX = 14;
	$rightWidth = 58;
	$gapWidth = 10;
	$rightX = $pageWidth - 14 - $rightWidth;
	$leftWidth = $rightX - $leftX - $gapWidth;
	$bottomTopY = $pdf->GetY() + 2;
	$noteTopY = $bottomTopY;

	if ($isOnlinePayment && $payuLogoPath !== '') {
		$pdf->writeHTMLCell(
			$leftWidth,
			0,
			$leftX,
			$bottomTopY,
			'<div style="font-size:8px;color:#64748b;letter-spacing:0.7px;text-transform:uppercase;">Paid via</div>',
			0,
			1,
			false,
			true,
			'L',
			true
		);
		if (hms_pdf_try_render_image($pdf, $payuLogoPath, $leftX, $bottomTopY + 4, 28, 0)) {
			$noteTopY = $bottomTopY + 16;
		} else {
			$pdf->writeHTMLCell(
				$leftWidth,
				0,
				$leftX,
				$bottomTopY + 4,
				'<div style="font-size:10px;color:#0f172a;font-weight:bold;">PayU</div>',
				0,
				1,
				false,
				true,
				'L',
				true
			);
			$noteTopY = $bottomTopY + 10;
		}
	}

	$pdf->writeHTMLCell(
		$leftWidth,
		0,
		$leftX,
		$noteTopY,
		'<div style="font-size:9px;color:#334155;line-height:1.5;border-left:3px solid #2dd4bf;padding-left:8px;">'
		. 'Please keep this invoice available during check-in. Arrive 10 minutes before the slot and quote Appointment ID <strong>' . (int)$appointment['id'] . '</strong> for faster desk verification.'
		. '</div>',
		0,
		1,
		false,
		true,
		'L',
		true
	);

	$signatureStartY = max($bottomTopY + 8, $noteTopY + 2);
	$signatureWidth = 36;
	$signatureHeight = 11;
	$signatureX = $rightX + (($rightWidth - $signatureWidth) / 2);
	if ($signaturePath !== '' && file_exists($signaturePath)) {
		$pdf->Image($signaturePath, $signatureX, $signatureStartY, $signatureWidth, $signatureHeight, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
	}
	$pdf->writeHTMLCell(
		$rightWidth,
		0,
		$rightX,
		$signatureStartY + 9,
		'<div style="border-top:1px solid #94a3b8;padding-top:4px;font-size:7px;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;text-align:center;">Confirmed by</div>'
		. '<div style="font-size:9px;color:#0f172a;font-weight:bold;padding-top:1px;text-align:center;">Zantus HMS</div>',
		0,
		1,
		false,
		true,
		'C',
		true
	);

	$pdf->SetY(max($noteTopY + 16, $signatureStartY + 18));

	hms_pdf_output_inline($pdf, 'appointment-receipt-' . (int)$appointment['id'] . '.pdf');
} catch (\Throwable $e) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'APPOINTMENT RECEIPT DEBUG ERROR: ' . $e->getMessage() . "\n";
	echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
	exit();
}
?>
