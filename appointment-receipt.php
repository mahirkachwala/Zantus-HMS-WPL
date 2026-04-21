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

	$headerHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%"><tr>'
		. '<td width="62%" style="border:1px solid #dbeafe;background-color:#ffffff;padding:12px;">'
		. '<div style="font-size:8px;color:#64748b;letter-spacing:0.6px;text-transform:uppercase;">Appointment Receipt</div>'
		. '<div style="font-size:18px;color:#0f172a;font-weight:bold;padding-top:4px;">' . hms_pdf_html_escape($appointment['patientName'] ?? '') . '</div>'
		. '<div style="font-size:10px;color:#475569;padding-top:2px;">Consultation booked with <strong>' . hms_pdf_html_escape($appointment['doctorName'] ?? '') . '</strong></div>'
		. '<div style="font-size:9px;color:#2563eb;padding-top:4px;">' . hms_pdf_html_escape($appointment['doctorSpecialization'] ?? '') . '</div>'
		. '</td>'
		. '<td width="4%"></td>'
		. '<td width="34%" style="border:1px solid #bfdbfe;background-color:#eff6ff;padding:12px;">'
		. '<div style="font-size:8px;color:#64748b;text-transform:uppercase;">Receipt No.</div>'
		. '<div style="font-size:12px;color:#1d4ed8;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape($receiptNumber) . '</div>'
		. '<div style="font-size:8px;color:#64748b;text-transform:uppercase;padding-top:10px;">Consultation Fee</div>'
		. '<div style="font-size:20px;color:#0f172a;font-weight:bold;padding-top:2px;">' . hms_pdf_html_escape(hms_pdf_money($appointment['consultancyFees'] ?? 0)) . '</div>'
		. '<div style="padding-top:10px;">' . $paymentBadge . '&nbsp;&nbsp;' . $visitBadge . '</div>'
		. '</td>'
		. '</tr></table>';

	$pdf->writeHTML($headerHtml, true, false, true, false, '');
	$pdf->Ln(4);
	$pdf->writeHTML(hms_pdf_kv_grid_html([
		'Appointment ID' => (int)$appointment['id'],
		'Appointment Date' => $appointment['appointmentDate'] ?? '',
		'Appointment Time' => $appointment['appointmentTime'] ?? '',
		'Booked On' => $appointment['postingDate'] ?? '',
		'Patient Email' => $appointment['patientEmail'] ?? '',
		'Doctor Email' => $appointment['docEmail'] ?? '',
		'Specialization' => $appointment['doctorSpecialization'] ?? '',
		'Source Table' => $appointment['sourceTable'] ?? '',
	], 2), true, false, true, false, '');

	$pdf->Ln(3);
	$pdf->writeHTML(
		hms_pdf_note_box_html(
			'Check-in Guidance',
			"Please keep this receipt available during check-in. Arrive 10 minutes before the slot and quote Appointment ID " . (int)$appointment['id'] . " for faster desk verification.",
			'blue'
		),
		true,
		false,
		true,
		false,
		''
	);

	hms_pdf_output_inline($pdf, 'appointment-receipt-' . (int)$appointment['id'] . '.pdf');
} catch (\Throwable $e) {
	$_SESSION['msg'] = 'Appointment receipt is temporarily unavailable. Please verify the TCPDF upload on the server.';
	header('location:appointments.php');
	exit();
}
?>
