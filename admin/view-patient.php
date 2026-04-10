<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();
function tableExists($con, $tableName) {
	$check = hms_query($con, "SHOW TABLES LIKE '" . hms_escape($con, $tableName) . "'");
	return ($check && hms_num_rows($check) > 0);
}

$viewId = (int)($_GET['viewid'] ?? 0);
$usePatientsTable = tableExists($con, 'patients');
$patient = null;

if ($viewId > 0 && $usePatientsTable) {
	$q = hms_query($con, "SELECT p.*, d.doctorName, u.fullName AS userName FROM patients p LEFT JOIN doctors d ON d.id=p.doctorId LEFT JOIN users u ON u.id=p.userId WHERE p.id='$viewId' LIMIT 1");
	if ($q) {
		$patient = hms_fetch_array($q);
	}
} elseif ($viewId > 0) {
	$q = hms_query($con, "SELECT * FROM tblpatient WHERE ID='$viewId' LIMIT 1");
	if ($q) {
		$patient = hms_fetch_array($q);
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | View Patient</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
	<?php
	$page_title = 'Admin | View Patient';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h5 class="over-title margin-bottom-15">Patient <span class="text-bold">Details</span></h5>
			<?php if(!$patient): ?>
				<div class="alert alert-warning">Patient record not found.</div>
			<?php else: ?>
				<table class="table table-bordered">
					<tr><th width="25%">Patient Name</th><td><?php echo htmlentities($patient['patientName'] ?? $patient['PatientName'] ?? $patient['userName'] ?? ''); ?></td></tr>
					<tr><th>Email</th><td><?php echo htmlentities($patient['patientEmail'] ?? $patient['PatientEmail'] ?? ''); ?></td></tr>
					<tr><th>Contact Number</th><td><?php echo htmlentities($patient['patientPhone'] ?? $patient['PatientContno'] ?? ''); ?></td></tr>
					<tr><th>Gender</th><td><?php echo htmlentities($patient['patientGender'] ?? $patient['PatientGender'] ?? ''); ?></td></tr>
					<tr><th>Age</th><td><?php echo htmlentities($patient['patientAge'] ?? $patient['PatientAge'] ?? ''); ?></td></tr>
					<tr><th>Address</th><td><?php echo htmlentities($patient['patientAddress'] ?? $patient['PatientAdd'] ?? ''); ?></td></tr>
					<tr><th>Patient Type</th><td><?php echo ucfirst(htmlentities($patient['patientType'] ?? 'consultancy')); ?></td></tr>
					<tr><th>Status</th><td><?php echo htmlentities($patient['status'] ?? 'Active'); ?></td></tr>
					<tr><th>Doctor</th><td><?php echo htmlentities($patient['doctorName'] ?? '--'); ?></td></tr>
					<tr><th>Created At</th><td><?php echo htmlentities($patient['createdAt'] ?? $patient['CreationDate'] ?? '--'); ?></td></tr>
					<tr><th>Updated At</th><td><?php echo htmlentities($patient['updatedAt'] ?? $patient['UpdationDate'] ?? '--'); ?></td></tr>
				</table>
			<?php endif; ?>
			<a href="manage-patient.php" class="btn btn-default">Back</a>
		</div>
	</div>
	<?php include('include/footer.php');?>
	<script src="../vendors/jquery/dist/jquery.min.js"></script>
	<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<script src="../vendors/fastclick/lib/fastclick.js"></script>
	<script src="../vendors/nprogress/nprogress.js"></script>
	<script src="../assets/js/custom.min.js"></script>
</body>
</html>