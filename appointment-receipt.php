<?php
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

$pdf = hms_create_pdf_document('Appointment Receipt');
$pdf->writeHTML(
	hms_pdf_block_title('Receipt Summary') .
	hms_pdf_label_value_table([
		'Receipt Number' => 'APPT-' . (int)$appointment['id'],
		'Appointment ID' => (int)$appointment['id'],
		'Patient Name' => $appointment['patientName'] ?? '',
		'Patient Email' => $appointment['patientEmail'] ?? '',
		'Doctor Name' => $appointment['doctorName'] ?? '',
		'Doctor Email' => $appointment['docEmail'] ?? '',
		'Specialization' => $appointment['doctorSpecialization'] ?? '',
		'Consultation Fee' => hms_pdf_money($appointment['consultancyFees'] ?? 0),
		'Payment Status' => $appointment['paymentStatusResolved'] ?? '',
		'Visit Status' => $appointment['visitStatusResolved'] ?? '',
		'Appointment Date' => $appointment['appointmentDate'] ?? '',
		'Appointment Time' => $appointment['appointmentTime'] ?? '',
		'Booked On' => $appointment['postingDate'] ?? '',
	])
);

$pdf->Ln(4);
$pdf->writeHTML(
	hms_pdf_block_title('Hospital Note') .
	'<div style="line-height:1.6;color:#334155;">Please keep this appointment receipt with you during check-in. Any schedule, payment, or visit-status changes will be reflected in your Zantus HMS account.</div>'
);

hms_pdf_output_inline($pdf, 'appointment-receipt-' . (int)$appointment['id'] . '.pdf');
?>
