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
	$statusCard = hms_pdf_status_card_html('Payment Status', $paymentStatus, 'green');
	$doctorLine = hms_pdf_clean_doctor_name($appointment['doctorName'] ?? '');
	$doctorDisplay = 'Dr. ' . $doctorLine;
	$signaturePath = hms_pdf_invoice_signature_path();
	$isOnlinePayment = strcasecmp($paymentStatus, 'Paid at Hospital') !== 0
		&& ($transactionRef !== '' || stripos($method, 'payu') !== false || stripos($method, 'online') !== false);
	$payuLogoPath = $isOnlinePayment ? hms_pdf_payu_logo_path() : '';

	$summaryHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="63%" style="padding-right:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Medical Payment Invoice</div>'
		. '<div style="font-size:17px;color:#0f172a;font-weight:bold;padding-top:3px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:9px;color:#475569;padding-top:4px;">Paid by <strong>' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</strong> for consultation with <strong>' . hms_pdf_html_escape($doctorDisplay, '') . '</strong>.</div>'
		. '</td>'
		. '<td width="37%" style="padding-left:8px;">'
		. '<div style="font-size:8px;color:#047857;letter-spacing:0.8px;text-transform:uppercase;">Total Paid</div>'
		. '<div style="font-size:23px;color:#064e3b;font-weight:bold;padding-top:3px;">' . hms_pdf_html_escape($amountPaid) . '</div>'
		. '<div style="font-size:8px;color:#065f46;padding-top:7px;">Method: ' . hms_pdf_html_escape($method !== '' ? $method : $appointment['paymentStatusResolved']) . '</div>'
		. '</td>'
		. '</tr>'
		. '</table>'
		. '<div style="border-bottom:1px solid #bfdbfe;padding-top:6px;"></div>';

	$pdf->writeHTML($summaryHtml, true, false, true, false, '');
	$pdf->Ln(1);
	$pdf->writeHTML($statusCard, true, false, true, false, '');
	$pdf->Ln(1);
	$paymentMetaHtml = '<div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:3px;">Payment Information</div>';
	$paymentMetaHtml .= '<table cellpadding="2" cellspacing="0" border="0" width="100%">';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Appointment ID</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . (int)$appointment['id'] . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Paid At</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($paidAt !== '' ? $paidAt : ($appointment['paidAt'] ?? '')) . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Patient</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Physician</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($doctorDisplay, '') . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Specialization</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Transaction Ref</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($transactionRef !== '' ? $transactionRef : ($appointment['paymentRef'] ?? '')) . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '<tr>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Visit Date</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentDate'] ?? '') . '</td>'
		. '<td width="17%" style="font-size:8px;color:#64748b;text-transform:uppercase;">Visit Time</td>'
		. '<td width="33%" style="border-bottom:1px solid #dbeafe;font-size:9px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentTime'] ?? '') . '</td>'
		. '</tr>';
	$paymentMetaHtml .= '</table>';
	$pdf->writeHTML($paymentMetaHtml, true, false, true, false, '');

	$pdf->Ln(2);
	$lineItemHtml = '<div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:3px;">Invoice Line Item</div>';
	$lineItemHtml .= '<table cellpadding="4" cellspacing="0" border="0" width="100%">';
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
		. 'This is a computer-generated Zantus HMS payment receipt. Please keep the transaction reference available for billing support, reconciliation, or refund review.'
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
		'<div style="border-top:1px solid #94a3b8;padding-top:4px;font-size:7px;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;text-align:center;">Verified by</div>'
		. '<div style="font-size:9px;color:#0f172a;font-weight:bold;padding-top:1px;text-align:center;">Zantus HMS</div>',
		0,
		1,
		false,
		true,
		'C',
		true
	);

	$pdf->SetY(max($noteTopY + 16, $signatureStartY + 18));

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
