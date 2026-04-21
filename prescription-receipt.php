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

$userId = (int)($_SESSION['id'] ?? 0);
$prescriptionId = (int)($_GET['prescription_id'] ?? 0);
$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$prescription = hms_find_user_prescription($con, $userId, $prescriptionId, $appointmentId);

if (!$prescription) {
	$_SESSION['msg'] = 'Prescription PDF could not be generated.';
	header('location:appointment-history.php');
	exit();
}

$appointment = hms_find_user_appointment($con, (int)($prescription['appointment_id'] ?? $appointmentId), $userId);

try {
	$pdf = hms_create_pdf_document('Prescription Sheet', 'prescription');
	hms_pdf_add_logo_watermark($pdf, 63, 103, 82, 0.05);

	$doctorLine = hms_pdf_clean_doctor_name($prescription['doctorName'] ?? '');
	$doctorDisplay = 'Dr. ' . $doctorLine;
	$specialization = trim((string)($appointment['doctorSpecialization'] ?? 'Consultation'));
	$signaturePath = hms_pdf_signature_path(
		($prescription['id'] ?? 0) . '|'
		. ($prescription['doctor_id'] ?? 0) . '|'
		. ($prescription['appointment_id'] ?? 0)
	);
	$metaHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="72%">'
		. '<div style="font-size:20px;color:#2563eb;font-weight:bold;">' . hms_pdf_html_escape($doctorDisplay, '') . '</div>'
		. '<div style="font-size:10px;color:#64748b;letter-spacing:1px;padding-top:2px;">' . hms_pdf_html_escape(strtoupper($specialization)) . '</div>'
		. '<div style="font-size:9px;color:#475569;padding-top:4px;">Zantus Life Science Hospital</div>'
		. '</td>'
		. '<td width="28%" align="right">'
		. '<div style="font-size:8px;color:#64748b;text-transform:uppercase;">Prescription No.</div>'
		. '<div style="font-size:13px;color:#1d4ed8;font-weight:bold;padding-top:3px;">RX-' . (int)($prescription['id'] ?? 0) . '</div>'
		. '<div style="font-size:8px;color:#64748b;padding-top:8px;">Follow-up</div>'
		. '<div style="font-size:10px;color:#0f172a;font-weight:bold;">' . hms_pdf_html_escape($prescription['next_visit_date'] ?? '') . '</div>'
		. '</td>'
		. '</tr></table>';
	$pdf->writeHTML($metaHtml, true, false, true, false, '');

	$lineHtml = '<table cellpadding="4" cellspacing="0" border="0" width="100%">'
		. '<tr>'
		. '<td width="18%" style="font-size:9px;color:#475569;">Patient Name</td><td width="32%" style="border-bottom:1px solid #cbd5e1;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($prescription['patientName'] ?? '') . '</td>'
		. '<td width="12%" style="font-size:9px;color:#475569;">Date</td><td width="38%" style="border-bottom:1px solid #cbd5e1;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($prescription['created_at'] ?? '') . '</td>'
		. '</tr>'
		. '<tr>'
		. '<td width="18%" style="font-size:9px;color:#475569;">Appointment ID</td><td width="32%" style="border-bottom:1px solid #cbd5e1;font-size:10px;color:#0f172a;">' . (int)($prescription['appointment_id'] ?? 0) . '</td>'
		. '<td width="12%" style="font-size:9px;color:#475569;">Time</td><td width="38%" style="border-bottom:1px solid #cbd5e1;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($appointment['appointmentTime'] ?? '') . '</td>'
		. '</tr>'
		. '<tr>'
		. '<td width="18%" style="font-size:9px;color:#475569;">Diagnosis</td><td width="82%" colspan="3" style="border-bottom:1px solid #cbd5e1;font-size:10px;color:#0f172a;">' . hms_pdf_html_escape($prescription['diagnosis'] ?? '') . '</td>'
		. '</tr>'
		. '</table>';
	$pdf->writeHTML($lineHtml, true, false, true, false, '');

	$pdf->Ln(2);
	$pdf->writeHTML('<div style="font-size:34px;color:#2563eb;font-weight:bold;">Rx</div>', true, false, true, false, '');
	$pdf->Ln(1);

	$medicineRows = (array)($prescription['medicineRows'] ?? []);
	$medicineHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Medicines</div>';
	$medicineHtml .= '<table cellpadding="5" cellspacing="0" border="0" width="100%">';
	$medicineHtml .= '<tr>';
	$medicineHtml .= '<th width="22%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;"><strong>Medicine</strong></th>';
	$medicineHtml .= '<th width="15%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;"><strong>Dosage</strong></th>';
	$medicineHtml .= '<th width="18%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;"><strong>Frequency</strong></th>';
	$medicineHtml .= '<th width="15%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;"><strong>Duration</strong></th>';
	$medicineHtml .= '<th width="30%" style="border-bottom:1px solid #2563eb;color:#1d4ed8;font-size:9px;font-weight:bold;"><strong>Instructions</strong></th>';
	$medicineHtml .= '</tr>';
	if (!empty($medicineRows)) {
		foreach ($medicineRows as $index => $row) {
			$medicineHtml .= '<tr>';
			$medicineHtml .= '<td width="22%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">' . htmlspecialchars(hms_pdf_text($row['medicine_name'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="15%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">' . htmlspecialchars(hms_pdf_text($row['dosage'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="18%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">' . htmlspecialchars(hms_pdf_text($row['frequency'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="15%" style="border-bottom:1px solid #e2e8f0;color:#0f172a;">' . htmlspecialchars(hms_pdf_text($row['duration'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="30%" style="border-bottom:1px solid #e2e8f0;color:#334155;">' . htmlspecialchars(hms_pdf_text($row['instructions'] ?? '')) . '</td>';
			$medicineHtml .= '</tr>';
		}
	} else {
		$medicineHtml .= '<tr><td width="100%" align="center" style="border-bottom:1px solid #e2e8f0;color:#64748b;">No medicines added.</td></tr>';
	}
	$medicineHtml .= '</table>';
	$pdf->writeHTML($medicineHtml, true, false, true, false, '');

	$pdf->Ln(3);
	$vitalsHtml = '<div style="font-size:11px;font-weight:bold;color:#1d4ed8;margin-bottom:4px;">Clinical Snapshot</div>';
	$vitalsHtml .= '<table cellpadding="2" cellspacing="0" border="0" width="100%">';
	$vitalsHtml .= '<tr>'
		. '<td width="48%">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.4px;text-transform:uppercase;">Temperature</div>'
		. '<div style="border-bottom:1px solid #cbd5e1;font-size:11px;font-weight:bold;color:#0f172a;padding:4px 0 5px 0;">' . hms_pdf_html_escape($prescription['temperature'] ?? '') . '</div>'
		. '</td>'
		. '<td width="4%"></td>'
		. '<td width="48%">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.4px;text-transform:uppercase;">Blood Pressure</div>'
		. '<div style="border-bottom:1px solid #cbd5e1;font-size:11px;font-weight:bold;color:#0f172a;padding:4px 0 5px 0;">' . hms_pdf_html_escape($prescription['blood_pressure'] ?? '') . '</div>'
		. '</td>'
		. '</tr>';
	$vitalsHtml .= '<tr><td colspan="3" height="4"></td></tr>';
	$vitalsHtml .= '<tr>'
		. '<td width="48%">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.4px;text-transform:uppercase;">Pulse</div>'
		. '<div style="border-bottom:1px solid #cbd5e1;font-size:11px;font-weight:bold;color:#0f172a;padding:4px 0 5px 0;">' . hms_pdf_html_escape($prescription['pulse'] ?? '') . '</div>'
		. '</td>'
		. '<td width="4%"></td>'
		. '<td width="48%">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.4px;text-transform:uppercase;">Weight</div>'
		. '<div style="border-bottom:1px solid #cbd5e1;font-size:11px;font-weight:bold;color:#0f172a;padding:4px 0 5px 0;">' . hms_pdf_html_escape($prescription['weight'] ?? '') . '</div>'
		. '</td>'
		. '</tr>';
	$vitalsHtml .= '</table>';
	$pdf->writeHTML($vitalsHtml, true, false, true, false, '');

	$pdf->Ln(3);
	$linearSection = static function ($title, $body) {
		$content = trim((string)$body);
		if ($content === '') {
			$content = '-';
		}

		return '<div style="font-size:10px;font-weight:bold;color:#1d4ed8;margin-bottom:3px;text-transform:uppercase;letter-spacing:0.5px;">'
			. hms_pdf_html_escape($title)
			. '</div>'
			. '<div style="font-size:9px;line-height:1.7;color:#334155;padding-bottom:5px;">'
			. nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8'))
			. '</div>'
			. '<div style="border-bottom:1px solid #dbeafe;"></div>';
	};

	$pdf->writeHTML($linearSection('Symptoms', $prescription['symptoms'] ?? ''), true, false, true, false, '');
	$pdf->Ln(3);
	$pdf->writeHTML($linearSection('Tests', $prescription['tests'] ?? ''), true, false, true, false, '');
	$pdf->Ln(3);
	$pdf->writeHTML($linearSection('Doctor Notes', $prescription['notes'] ?? ''), true, false, true, false, '');

	$pdf->Ln(6);
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
		. '<div style="border-top:1px solid #94a3b8;padding-top:6px;font-size:8px;color:#64748b;text-transform:uppercase;letter-spacing:0.6px;">Authorized by</div>'
		. '<div style="font-size:10px;color:#0f172a;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape($doctorDisplay, '') . '</div>'
		. '</td>'
		. '</tr></table>',
		true,
		false,
		true,
		false,
		''
	);

	hms_pdf_output_inline($pdf, 'prescription-' . (int)($prescription['id'] ?? 0) . '.pdf');
} catch (\Throwable $e) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'PRESCRIPTION RECEIPT DEBUG ERROR: ' . $e->getMessage() . "\n";
	echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
	exit();
}
?>
