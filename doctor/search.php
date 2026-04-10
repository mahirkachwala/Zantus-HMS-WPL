<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);
$keyword = trim($_GET['q'] ?? $_POST['q'] ?? '');
$hasQuery = ($keyword !== '');
$kwEsc = hms_escape($con, $keyword);

$results = [
	'patients' => [],
	'appointments' => [],
	'prescriptions' => [],
	'queries' => [],
	'feedback' => []
];

if ($hasQuery && $doctorId > 0) {
	if (hms_table_exists($con, 'patients')) {
		$q = hms_query($con, "SELECT id, patientName, patientPhone, patientGender, patientType, createdAt
			FROM patients
			WHERE doctorId='$doctorId' AND (
				patientName LIKE '%$kwEsc%' OR patientPhone LIKE '%$kwEsc%' OR patientEmail LIKE '%$kwEsc%'
			)
			ORDER BY id DESC LIMIT 50");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['patients'][] = $r; }
	} elseif (hms_table_exists($con, 'tblpatient')) {
		$q = hms_query($con, "SELECT ID as id, PatientName as patientName, PatientContno as patientPhone, PatientGender as patientGender, 'consultancy' as patientType, CreationDate as createdAt
			FROM tblpatient
			WHERE Docid='$doctorId' AND (PatientName LIKE '%$kwEsc%' OR PatientContno LIKE '%$kwEsc%' OR PatientEmail LIKE '%$kwEsc%')
			ORDER BY ID DESC LIMIT 50");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['patients'][] = $r; }
	}

	$apptTable = hms_table_exists($con, 'current_appointments') ? 'current_appointments' : 'appointment';
	if (hms_table_exists($con, $apptTable)) {
		$q = hms_query($con, "SELECT a.id, a.appointmentDate, a.appointmentTime, a.visitStatus, a.paymentStatus, u.fullName
			FROM $apptTable a
			LEFT JOIN users u ON u.id=a.userId
			WHERE a.doctorId='$doctorId' AND (
				a.id LIKE '%$kwEsc%' OR a.appointmentDate LIKE '%$kwEsc%' OR a.appointmentTime LIKE '%$kwEsc%' OR
				COALESCE(a.visitStatus,'') LIKE '%$kwEsc%' OR COALESCE(a.paymentStatus,'') LIKE '%$kwEsc%' OR
				COALESCE(u.fullName,'') LIKE '%$kwEsc%'
			)
			ORDER BY a.id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['appointments'][] = $r; }
	}

	if (hms_table_exists($con, 'prescriptions')) {
		$q = hms_query($con, "SELECT p.id, p.appointment_id, p.diagnosis, p.created_at, u.fullName as patientName
			FROM prescriptions p
			LEFT JOIN users u ON u.id=p.patient_id
			WHERE p.doctor_id='$doctorId' AND (
				p.id LIKE '%$kwEsc%' OR p.appointment_id LIKE '%$kwEsc%' OR
				COALESCE(p.diagnosis,'') LIKE '%$kwEsc%' OR COALESCE(p.notes,'') LIKE '%$kwEsc%' OR
				COALESCE(u.fullName,'') LIKE '%$kwEsc%'
			)
			ORDER BY p.id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['prescriptions'][] = $r; }
	}

	if (hms_table_exists($con, 'contact_queries')) {
		$q = hms_query($con, "SELECT id, subject, status, created_at
			FROM contact_queries
			WHERE portal_type='doctor' AND doctor_id='$doctorId' AND (
				subject LIKE '%$kwEsc%' OR message LIKE '%$kwEsc%' OR status LIKE '%$kwEsc%'
			)
			ORDER BY id DESC LIMIT 50");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['queries'][] = $r; }
	}

	if (hms_table_exists($con, 'feedback_entries')) {
		$q = hms_query($con, "SELECT id, rating, status, created_at
			FROM feedback_entries
			WHERE portal_type='doctor' AND doctor_id='$doctorId' AND (
				COALESCE(feedback_text,'') LIKE '%$kwEsc%' OR COALESCE(status,'') LIKE '%$kwEsc%' OR id LIKE '%$kwEsc%'
			)
			ORDER BY id DESC LIMIT 50");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['feedback'][] = $r; }
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Search</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title = 'Doctor | Search'; $x_content = true; include('include/header.php');?>
<div class="row">
	<div class="col-md-12">
		<form method="get" class="form-inline" style="margin-bottom:14px;">
			<div class="form-group" style="width:78%;">
				<input type="text" name="q" class="form-control" style="width:100%;" placeholder="Search patient, appointment ID/date, prescription, status..." value="<?php echo htmlentities($keyword); ?>" required>
			</div>
			<button type="submit" class="btn btn-primary" style="margin-left:8px;">Search</button>
		</form>

		<?php if($hasQuery): ?>
			<div class="alert alert-info">Search result for <strong><?php echo htmlentities($keyword); ?></strong></div>

			<div class="x_panel"><div class="x_title"><h2>Patients (<?php echo count($results['patients']); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(!empty($results['patients'])): ?>
			<table class="table table-bordered table-hover"><thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Gender</th><th>Type</th><th>Created</th></tr></thead><tbody>
			<?php $i=1; foreach($results['patients'] as $r): ?><tr><td><?php echo $i++; ?></td><td><?php echo htmlentities($r['patientName'] ?? '-'); ?></td><td><?php echo htmlentities($r['patientPhone'] ?? '-'); ?></td><td><?php echo htmlentities($r['patientGender'] ?? '-'); ?></td><td><?php echo htmlentities($r['patientType'] ?? '-'); ?></td><td><?php echo htmlentities($r['createdAt'] ?? '-'); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php else: ?><div class="text-muted">No patients matched.</div><?php endif; ?>
			</div></div>

			<div class="x_panel"><div class="x_title"><h2>Appointments (<?php echo count($results['appointments']); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(!empty($results['appointments'])): ?>
			<table class="table table-bordered table-hover"><thead><tr><th>#</th><th>Appointment ID</th><th>Patient</th><th>Date/Time</th><th>Visit</th><th>Payment</th></tr></thead><tbody>
			<?php $i=1; foreach($results['appointments'] as $r): ?><tr><td><?php echo $i++; ?></td><td><?php echo (int)$r['id']; ?></td><td><?php echo htmlentities($r['fullName'] ?? '-'); ?></td><td><?php echo htmlentities(($r['appointmentDate'] ?? '').' '.($r['appointmentTime'] ?? '')); ?></td><td><?php echo htmlentities($r['visitStatus'] ?? 'Scheduled'); ?></td><td><?php echo htmlentities($r['paymentStatus'] ?? 'Pending'); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php else: ?><div class="text-muted">No appointments matched.</div><?php endif; ?>
			</div></div>

			<div class="x_panel"><div class="x_title"><h2>Prescriptions (<?php echo count($results['prescriptions']); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(!empty($results['prescriptions'])): ?>
			<table class="table table-bordered table-hover"><thead><tr><th>#</th><th>Prescription ID</th><th>Appointment</th><th>Patient</th><th>Diagnosis</th><th>Created</th></tr></thead><tbody>
			<?php $i=1; foreach($results['prescriptions'] as $r): ?><tr><td><?php echo $i++; ?></td><td><?php echo (int)$r['id']; ?></td><td><?php echo (int)$r['appointment_id']; ?></td><td><?php echo htmlentities($r['patientName'] ?? '-'); ?></td><td><?php echo htmlentities($r['diagnosis'] ?? '-'); ?></td><td><?php echo htmlentities($r['created_at'] ?? '-'); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php else: ?><div class="text-muted">No prescriptions matched.</div><?php endif; ?>
			</div></div>

			<div class="x_panel"><div class="x_title"><h2>Contact Queries (<?php echo count($results['queries']); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(!empty($results['queries'])): ?>
			<table class="table table-bordered table-hover"><thead><tr><th>#</th><th>ID</th><th>Subject</th><th>Status</th><th>Created</th></tr></thead><tbody>
			<?php $i=1; foreach($results['queries'] as $r): ?><tr><td><?php echo $i++; ?></td><td><?php echo (int)$r['id']; ?></td><td><?php echo htmlentities($r['subject'] ?? '-'); ?></td><td><?php echo htmlentities($r['status'] ?? 'New'); ?></td><td><?php echo htmlentities($r['created_at'] ?? '-'); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php else: ?><div class="text-muted">No contact queries matched.</div><?php endif; ?>
			</div></div>

			<div class="x_panel"><div class="x_title"><h2>Feedback (<?php echo count($results['feedback']); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(!empty($results['feedback'])): ?>
			<table class="table table-bordered table-hover"><thead><tr><th>#</th><th>ID</th><th>Rating</th><th>Status</th><th>Created</th></tr></thead><tbody>
			<?php $i=1; foreach($results['feedback'] as $r): ?><tr><td><?php echo $i++; ?></td><td><?php echo (int)$r['id']; ?></td><td><?php echo (int)($r['rating'] ?? 0) > 0 ? (int)$r['rating'].'/5' : '-'; ?></td><td><?php echo htmlentities($r['status'] ?? 'New'); ?></td><td><?php echo htmlentities($r['created_at'] ?? '-'); ?></td></tr><?php endforeach; ?>
			</tbody></table><?php else: ?><div class="text-muted">No feedback matched.</div><?php endif; ?>
			</div></div>
		<?php endif; ?>
	</div>
</div>
<?php include('include/footer.php');?>
<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../vendors/nprogress/nprogress.js"></script>
<script src="../assets/js/custom.min.js"></script>
</body>
</html>