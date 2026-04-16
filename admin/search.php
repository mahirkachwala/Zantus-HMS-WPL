<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function search_pick_value($row, array $keys, $default = '')
{
	foreach ($keys as $key) {
		if (is_array($row) && array_key_exists($key, $row)) {
			return $row[$key];
		}
	}
	return $default;
}

function search_prepare_rows(array $rows, array $columns)
{
	$prepared = [];
	foreach ($rows as $row) {
		$displayRow = [];
		foreach ($columns as $label => $keys) {
			$value = search_pick_value($row, (array)$keys, '');
			$displayRow[$label] = is_scalar($value) || $value === null ? (string)$value : '';
		}
		$prepared[] = $displayRow;
	}
	return $prepared;
}

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

$sectionColumns = [
	'users' => [
		'User ID' => ['id', 'ID'],
		'Full Name' => ['fullName', 'fullname'],
		'Email' => ['email'],
		'Registered On' => ['regDate', 'regdate'],
	],
	'doctors' => [
		'Doctor ID' => ['id', 'ID'],
		'Doctor Name' => ['doctorName', 'doctorname'],
		'Email' => ['docEmail', 'docemail'],
		'Contact No' => ['contactno'],
		'Fees' => ['docFees', 'docfees'],
	],
	'patients' => [
		'Patient ID' => ['id', 'ID'],
		'Patient Name' => ['patientName', 'patientname'],
		'Email' => ['patientEmail', 'patientemail'],
		'Phone' => ['patientPhone', 'patientphone', 'PatientContno'],
		'Type' => ['patientType', 'patienttype'],
		'Doctor ID' => ['doctorId', 'doctorid', 'Docid'],
		'Created On' => ['createdAt', 'createdat', 'CreationDate'],
	],
	'appointments' => [
		'Appointment ID' => ['id', 'ID'],
		'Patient Name' => ['patientName', 'patientname', 'fullName', 'fullname'],
		'Doctor Name' => ['doctorName', 'doctorname'],
		'Date' => ['appointmentDate', 'appointmentdate'],
		'Time' => ['appointmentTime', 'appointmenttime'],
		'Visit Status' => ['visitStatus', 'visitstatus'],
		'Payment Status' => ['paymentStatus', 'paymentstatus'],
	],
	'prescriptions' => [
		'Prescription ID' => ['id', 'ID'],
		'Appointment ID' => ['appointment_id', 'appointmentId', 'appointmentid'],
		'Patient Name' => ['patientName', 'patientname', 'fullName', 'fullname'],
		'Doctor Name' => ['doctorName', 'doctorname'],
		'Diagnosis' => ['diagnosis'],
		'Created At' => ['created_at', 'createdAt', 'createdat'],
	],
	'queries' => [
		'Query ID' => ['id', 'ID'],
		'Portal' => ['portal_type'],
		'Name' => ['name'],
		'Subject' => ['subject'],
		'Status' => ['status'],
		'Created At' => ['created_at', 'createdAt', 'createdat'],
	],
	'feedback' => [
		'Feedback ID' => ['id', 'ID'],
		'Portal' => ['portal_type'],
		'Name' => ['name'],
		'Rating' => ['rating'],
		'Status' => ['status'],
		'Created At' => ['created_at', 'createdAt', 'createdat'],
	],
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
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
		.search-card,
		.result-card {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 12px;
			box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
		}
		.search-card {
			padding: 18px;
			margin-bottom: 16px;
		}
		.search-form {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: center;
		}
		.search-form .form-group {
			flex: 1 1 520px;
			margin-bottom: 0;
		}
		.search-form .form-control {
			height: 42px;
			border-radius: 8px;
		}
		.search-form .btn {
			min-width: 120px;
			border-radius: 8px;
			height: 42px;
		}
		.search-note {
			margin: 0 0 14px;
			color: #52607a;
			font-size: 14px;
		}
		.result-banner {
			background: #eef4ff;
			border: 1px solid #d7e4ff;
			color: #1e3a8a;
			border-radius: 10px;
			padding: 12px 16px;
			margin-bottom: 16px;
		}
		.result-card {
			margin-bottom: 18px;
			overflow: hidden;
		}
		.result-card .x_title {
			padding: 16px 18px 10px;
			border-bottom: 1px solid #edf2f7;
			margin-bottom: 0;
		}
		.result-card .x_title h2 {
			font-size: 18px;
			font-weight: 700;
			color: #1e3a8a;
			margin: 0;
		}
		.result-card .x_content {
			padding: 16px 18px 18px;
		}
		.count-badge {
			display: inline-block;
			margin-left: 8px;
			padding: 2px 10px;
			border-radius: 999px;
			background: #e8efff;
			color: #1e3a8a;
			font-size: 12px;
			font-weight: 700;
			vertical-align: middle;
		}
		.search-table-wrap {
			width: 100%;
			overflow-x: auto;
			border: 1px solid #e6ebf5;
			border-radius: 10px;
		}
		.search-table {
			margin-bottom: 0;
			min-width: 860px;
			background: #fff;
		}
		.search-table thead th {
			background: #294498;
			color: #fff;
			border-color: #294498 !important;
			white-space: nowrap;
			font-weight: 600;
		}
		.search-table tbody tr:nth-child(even) {
			background: #f8fbff;
		}
		.search-table td {
			max-width: 240px;
			word-break: break-word;
			white-space: normal;
			color: #334155;
			vertical-align: top;
		}
		.empty-state {
			padding: 8px 0;
			color: #64748b;
		}
		@media (max-width: 767px) {
			.search-form .btn {
				width: 100%;
			}
			.search-table {
				min-width: 680px;
			}
		}
	</style>
</head>
<body class="nav-md">
<?php $page_title='Admin | Search'; $x_content=true; include('include/header.php');?>
<div class="row"><div class="col-md-12">
	<h3 class="page-heading">Global Search</h3>
	<div class="search-card">
		<p class="search-note">Search users, doctors, patients, appointments, prescriptions, contact queries, and feedback from one place.</p>
		<form method="get" class="search-form">
			<div class="form-group">
				<input type="text" name="q" class="form-control" placeholder="Search by ID, name, email, date, status, diagnosis..." value="<?php echo htmlentities($keyword); ?>" required>
			</div>
			<button type="submit" class="btn btn-primary">Search</button>
		</form>
	</div>

	<?php if($hasQuery): ?>
		<div class="result-banner">Search result for <strong><?php echo htmlentities($keyword); ?></strong></div>

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
			$displayRows = search_prepare_rows($results[$key], $sectionColumns[$key]);
		?>
		<div class="x_panel result-card"><div class="x_title"><h2><?php echo $label; ?><span class="count-badge"><?php echo count($results[$key]); ?></span></h2><div class="clearfix"></div></div><div class="x_content">
			<?php if(empty($results[$key])): ?>
				<div class="empty-state">No <?php echo strtolower($label); ?> matched.</div>
			<?php else: ?>
				<div class="search-table-wrap">
					<table class="table table-bordered table-hover search-table">
						<thead><tr>
						<?php foreach(array_keys($displayRows[0]) as $col): ?><th><?php echo htmlentities($col); ?></th><?php endforeach; ?>
						</tr></thead>
						<tbody>
						<?php foreach($displayRows as $row): ?><tr><?php foreach($row as $val): ?><td><?php echo nl2br(htmlentities((string)$val)); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
						</tbody>
					</table>
				</div>
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
