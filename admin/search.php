<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$keyword = trim($_GET['q'] ?? $_POST['q'] ?? '');
$hasQuery = ($keyword !== '');
$kwEsc = hms_escape($con, $keyword);

$results = [
	'users' => [],
	'doctors' => [],
	'patients' => [],
	'appointments' => [],
	'prescriptions' => [],
	'queries' => [],
	'feedback' => []
];

if ($hasQuery) {
	if (hms_table_exists($con, 'users')) {
		$q = hms_query($con, "SELECT id, fullName, email, regDate FROM users WHERE id LIKE '%$kwEsc%' OR fullName LIKE '%$kwEsc%' OR email LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['users'][] = $r; }
	}

	if (hms_table_exists($con, 'doctors')) {
		$q = hms_query($con, "SELECT id, doctorName, docEmail, contactno, docFees FROM doctors WHERE id LIKE '%$kwEsc%' OR doctorName LIKE '%$kwEsc%' OR docEmail LIKE '%$kwEsc%' OR contactno LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['doctors'][] = $r; }
	}

	if (hms_table_exists($con, 'patients')) {
		$q = hms_query($con, "SELECT id, patientName, patientEmail, patientPhone, patientType, doctorId, createdAt FROM patients WHERE id LIKE '%$kwEsc%' OR patientName LIKE '%$kwEsc%' OR patientEmail LIKE '%$kwEsc%' OR patientPhone LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['patients'][] = $r; }
	} elseif (hms_table_exists($con, 'tblpatient')) {
		$q = hms_query($con, "SELECT ID as id, PatientName as patientName, PatientEmail as patientEmail, PatientContno as patientPhone, 'consultancy' as patientType, Docid as doctorId, CreationDate as createdAt FROM tblpatient WHERE ID LIKE '%$kwEsc%' OR PatientName LIKE '%$kwEsc%' OR PatientEmail LIKE '%$kwEsc%' OR PatientContno LIKE '%$kwEsc%' ORDER BY ID DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['patients'][] = $r; }
	}

	$apptTable = hms_table_exists($con, 'current_appointments') ? 'current_appointments' : 'appointment';
	if (hms_table_exists($con, $apptTable)) {
		$q = hms_query($con, "SELECT a.id, a.appointmentDate, a.appointmentTime, a.visitStatus, a.paymentStatus, u.fullName as patientName, d.doctorName
			FROM $apptTable a
			LEFT JOIN users u ON u.id=a.userId
			LEFT JOIN doctors d ON d.id=a.doctorId
			WHERE a.id LIKE '%$kwEsc%' OR a.appointmentDate LIKE '%$kwEsc%' OR a.appointmentTime LIKE '%$kwEsc%' OR COALESCE(a.visitStatus,'') LIKE '%$kwEsc%' OR COALESCE(a.paymentStatus,'') LIKE '%$kwEsc%' OR COALESCE(u.fullName,'') LIKE '%$kwEsc%' OR COALESCE(d.doctorName,'') LIKE '%$kwEsc%'
			ORDER BY a.id DESC LIMIT 120");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['appointments'][] = $r; }
	}

	if (hms_table_exists($con, 'prescriptions')) {
		$q = hms_query($con, "SELECT p.id, p.appointment_id, p.diagnosis, p.created_at, u.fullName as patientName, d.doctorName
			FROM prescriptions p
			LEFT JOIN users u ON u.id=p.patient_id
			LEFT JOIN doctors d ON d.id=p.doctor_id
			WHERE p.id LIKE '%$kwEsc%' OR p.appointment_id LIKE '%$kwEsc%' OR COALESCE(p.diagnosis,'') LIKE '%$kwEsc%' OR COALESCE(p.notes,'') LIKE '%$kwEsc%' OR COALESCE(u.fullName,'') LIKE '%$kwEsc%' OR COALESCE(d.doctorName,'') LIKE '%$kwEsc%'
			ORDER BY p.id DESC LIMIT 120");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['prescriptions'][] = $r; }
	}

	if (hms_table_exists($con, 'contact_queries')) {
		$q = hms_query($con, "SELECT id, portal_type, name, subject, status, created_at FROM contact_queries WHERE id LIKE '%$kwEsc%' OR portal_type LIKE '%$kwEsc%' OR name LIKE '%$kwEsc%' OR subject LIKE '%$kwEsc%' OR message LIKE '%$kwEsc%' OR status LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['queries'][] = $r; }
	}
	if (hms_table_exists($con, 'contact_query_history')) {
		$q = hms_query($con, "SELECT original_query_id as id, portal_type, name, subject, final_status as status, disposed_at as created_at FROM contact_query_history WHERE original_query_id LIKE '%$kwEsc%' OR portal_type LIKE '%$kwEsc%' OR name LIKE '%$kwEsc%' OR subject LIKE '%$kwEsc%' OR message LIKE '%$kwEsc%' OR final_status LIKE '%$kwEsc%' OR admin_note LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['queries'][] = $r; }
	}

	if (hms_table_exists($con, 'feedback_entries')) {
		$q = hms_query($con, "SELECT id, portal_type, name, rating, status, created_at FROM feedback_entries WHERE id LIKE '%$kwEsc%' OR portal_type LIKE '%$kwEsc%' OR name LIKE '%$kwEsc%' OR COALESCE(feedback_text,'') LIKE '%$kwEsc%' OR COALESCE(status,'') LIKE '%$kwEsc%' ORDER BY id DESC LIMIT 80");
		if ($q) while($r = hms_fetch_assoc($q)) { $results['feedback'][] = $r; }
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Search</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title='Admin | Search'; $x_content=true; include('include/header.php');?>
<div class="row"><div class="col-md-12">
	<form method="get" class="form-inline" style="margin-bottom:14px;">
		<div class="form-group" style="width:82%;"><input type="text" name="q" class="form-control" style="width:100%;" placeholder="Search users, doctors, patients, appointments, prescriptions, queries, feedback..." value="<?php echo htmlentities($keyword); ?>" required></div>
		<button type="submit" class="btn btn-primary" style="margin-left:8px;">Search</button>
	</form>

	<?php if($hasQuery): ?>
		<div class="alert alert-info">Search result for <strong><?php echo htmlentities($keyword); ?></strong></div>

		<?php
		$sections = [
			'users' => 'Users',
			'doctors' => 'Doctors',
			'patients' => 'Patients',
			'appointments' => 'Appointments',
			'prescriptions' => 'Prescriptions',
			'queries' => 'Contact Queries',
			'feedback' => 'Feedback'
		];
		foreach($sections as $key => $label):
		?>
		<div class="x_panel"><div class="x_title"><h2><?php echo $label; ?> (<?php echo count($results[$key]); ?>)</h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(empty($results[$key])): ?>
				<div class="text-muted">No <?php echo strtolower($label); ?> matched.</div>
			<?php else: ?>
				<table class="table table-bordered table-hover">
					<thead><tr>
					<?php foreach(array_keys($results[$key][0]) as $col): ?><th><?php echo htmlentities(ucwords(str_replace('_',' ',$col))); ?></th><?php endforeach; ?>
					</tr></thead>
					<tbody>
					<?php foreach($results[$key] as $row): ?><tr><?php foreach($row as $val): ?><td><?php echo htmlentities((string)$val); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div></div>
		<?php endforeach; ?>
	<?php endif; ?>
</div></div>
<?php include('include/footer.php');?>
<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../vendors/nprogress/nprogress.js"></script>
<script src="../assets/js/custom.min.js"></script>
</body>
</html>
