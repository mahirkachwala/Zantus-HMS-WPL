<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
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

	$doctorLine = trim((string)($prescription['doctorName'] ?? ''));
	$specialization = trim((string)($appointment['doctorSpecialization'] ?? 'Consultation'));
	$metaHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="72%">'
		. '<div style="font-size:20px;color:#2563eb;font-weight:bold;">Dr. ' . hms_pdf_html_escape($doctorLine, '') . '</div>'
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
	$medicineHtml .= '<tr style="background-color:#2563eb;color:#ffffff;">';
	$medicineHtml .= '<th width="22%"><strong>Medicine</strong></th>';
	$medicineHtml .= '<th width="15%"><strong>Dosage</strong></th>';
	$medicineHtml .= '<th width="18%"><strong>Frequency</strong></th>';
	$medicineHtml .= '<th width="15%"><strong>Duration</strong></th>';
	$medicineHtml .= '<th width="30%"><strong>Instructions</strong></th>';
	$medicineHtml .= '</tr>';
	if (!empty($medicineRows)) {
		foreach ($medicineRows as $index => $row) {
			$background = $index % 2 === 0 ? '#ffffff' : '#f8fafc';
			$medicineHtml .= '<tr style="background-color:' . $background . ';">';
			$medicineHtml .= '<td width="22%">' . htmlspecialchars(hms_pdf_text($row['medicine_name'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="15%">' . htmlspecialchars(hms_pdf_text($row['dosage'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="18%">' . htmlspecialchars(hms_pdf_text($row['frequency'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="15%">' . htmlspecialchars(hms_pdf_text($row['duration'] ?? '')) . '</td>';
			$medicineHtml .= '<td width="30%">' . htmlspecialchars(hms_pdf_text($row['instructions'] ?? '')) . '</td>';
			$medicineHtml .= '</tr>';
		}
	} else {
		$medicineHtml .= '<tr><td width="100%" align="center">No medicines added.</td></tr>';
	}
	$medicineHtml .= '</table>';
	$pdf->writeHTML($medicineHtml, true, false, true, false, '');

	$pdf->Ln(4);
	$pdf->writeHTML(hms_pdf_kv_grid_html([
		'Temperature' => $prescription['temperature'] ?? '',
		'Blood Pressure' => $prescription['blood_pressure'] ?? '',
		'Pulse' => $prescription['pulse'] ?? '',
		'Weight' => $prescription['weight'] ?? '',
	], 2), true, false, true, false, '');

	$pdf->Ln(3);
	$pdf->writeHTML(
		hms_pdf_note_box_html('Symptoms', $prescription['symptoms'] ?? '', 'blue'),
		true,
		false,
		true,
		false,
		''
	);
	$pdf->Ln(3);
	$pdf->writeHTML(
		hms_pdf_note_box_html('Tests', $prescription['tests'] ?? '', 'blue'),
		true,
		false,
		true,
		false,
		''
	);
	$pdf->Ln(3);
	$pdf->writeHTML(
		hms_pdf_note_box_html('Doctor Notes', $prescription['notes'] ?? '', 'teal'),
		true,
		false,
		true,
		false,
		''
	);

	$pdf->Ln(10);
	$pdf->writeHTML(
		'<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="58%"></td>'
		. '<td width="42%" align="center">'
		. '<div style="border-top:1px solid #94a3b8;padding-top:6px;font-size:9px;color:#475569;">Authorized by Dr. ' . hms_pdf_html_escape($doctorLine, '') . '</div>'
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
	$_SESSION['msg'] = 'Prescription PDF is temporarily unavailable. Please verify the TCPDF upload on the server.';
	header('location:appointment-history.php');
	exit();
}
?>
