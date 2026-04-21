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
	$pdf = hms_create_pdf_document('Prescription Receipt');
	$pdf->writeHTML(
		hms_pdf_block_title('Prescription Summary') .
		hms_pdf_label_value_table([
			'Prescription ID' => (int)($prescription['id'] ?? 0),
			'Appointment ID' => (int)($prescription['appointment_id'] ?? 0),
			'Patient Name' => $prescription['patientName'] ?? '',
			'Doctor Name' => $prescription['doctorName'] ?? '',
			'Appointment Date' => $appointment['appointmentDate'] ?? '',
			'Appointment Time' => $appointment['appointmentTime'] ?? '',
			'Created At' => $prescription['created_at'] ?? '',
			'Follow-up Date' => $prescription['next_visit_date'] ?? '',
		])
	);

	$pdf->Ln(4);
	$pdf->writeHTML(
		hms_pdf_block_title('Vitals') .
		hms_pdf_label_value_table([
			'Temperature' => $prescription['temperature'] ?? '',
			'Blood Pressure' => $prescription['blood_pressure'] ?? '',
			'Pulse' => $prescription['pulse'] ?? '',
			'Weight' => $prescription['weight'] ?? '',
		])
	);

	$pdf->Ln(4);
	$pdf->writeHTML(hms_pdf_block_title('Symptoms') . '<div style="line-height:1.6;">' . nl2br(htmlspecialchars(hms_pdf_text($prescription['symptoms'] ?? ''))) . '</div>');
	$pdf->Ln(2);
	$pdf->writeHTML(hms_pdf_block_title('Diagnosis') . '<div style="line-height:1.6;">' . nl2br(htmlspecialchars(hms_pdf_text($prescription['diagnosis'] ?? ''))) . '</div>');
	$pdf->Ln(2);

	$medicineRows = (array)($prescription['medicineRows'] ?? []);
	$medicineHtml = hms_pdf_block_title('Medicines');
	$medicineHtml .= '<table cellpadding="5" cellspacing="0" border="1" style="border-color:#dbe4f0;">';
	$medicineHtml .= '<tr style="background-color:#eff6ff;color:#1e3a8a;">';
	$medicineHtml .= '<th width="22%"><strong>Medicine</strong></th>';
	$medicineHtml .= '<th width="15%"><strong>Dosage</strong></th>';
	$medicineHtml .= '<th width="18%"><strong>Frequency</strong></th>';
	$medicineHtml .= '<th width="15%"><strong>Duration</strong></th>';
	$medicineHtml .= '<th width="30%"><strong>Instructions</strong></th>';
	$medicineHtml .= '</tr>';
	if (!empty($medicineRows)) {
		foreach ($medicineRows as $row) {
			$medicineHtml .= '<tr>';
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
	$pdf->writeHTML($medicineHtml);

	$pdf->Ln(4);
	$pdf->writeHTML(hms_pdf_block_title('Tests') . '<div style="line-height:1.6;">' . nl2br(htmlspecialchars(hms_pdf_text($prescription['tests'] ?? ''))) . '</div>');
	$pdf->Ln(2);
	$pdf->writeHTML(hms_pdf_block_title('Doctor Notes') . '<div style="line-height:1.6;">' . nl2br(htmlspecialchars(hms_pdf_text($prescription['notes'] ?? ''))) . '</div>');

	hms_pdf_output_inline($pdf, 'prescription-' . (int)($prescription['id'] ?? 0) . '.pdf');
} catch (\Throwable $e) {
	$_SESSION['msg'] = 'Prescription PDF is temporarily unavailable. Please verify the TCPDF upload on the server.';
	header('location:appointment-history.php');
	exit();
}
?>
