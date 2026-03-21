<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function tableExists($con, $tableName) {
	$check = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tableName) . "'");
	return ($check && mysqli_num_rows($check) > 0);
}

if (!tableExists($con, 'prescriptions') || !tableExists($con, 'prescription_medicines')) {
	$_SESSION['msg'] = 'Structured prescription tables are not available.';
	header('location:appointment-history.php');
	exit();
}

$appointmentId = (int)($_GET['appointment_id'] ?? 0);

$q = mysqli_query($con, "SELECT p.*, a.userId, a.doctorId, a.appointmentDate, a.appointmentTime, u.fullName AS patientName, d.doctorName
	FROM prescriptions p
	JOIN appointment a ON a.id=p.appointment_id
	JOIN users u ON u.id=a.userId
	JOIN doctors d ON d.id=a.doctorId
	WHERE p.appointment_id='$appointmentId'
	ORDER BY p.id DESC LIMIT 1");
$prescription = ($q) ? mysqli_fetch_array($q) : null;

if (!$prescription) {
	$_SESSION['msg'] = 'Prescription not found for this appointment.';
	header('location:appointment-history.php');
	exit();
}

$meds = mysqli_query($con, "SELECT * FROM prescription_medicines WHERE prescription_id='".(int)$prescription['id']."' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | View Prescription</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
	<style>.block{background:#fff;border:1px solid #e6ebf5;border-radius:10px;padding:14px;margin-bottom:12px}.tt{font-weight:700;color:#1e3a8a;margin-bottom:10px}</style>
</head>
<body class="nav-md">
<?php $page_title='Admin | View Prescription'; $x_content=true; include('include/header.php');?>
<div class="row"><div class="col-md-12">
	<div class="block"><div class="tt">Patient & Visit Info</div><div class="row"><div class="col-md-3"><strong>Patient:</strong> <?php echo htmlentities($prescription['patientName']); ?></div><div class="col-md-3"><strong>Doctor:</strong> <?php echo htmlentities($prescription['doctorName']); ?></div><div class="col-md-3"><strong>Appointment:</strong> <?php echo htmlentities($prescription['appointmentDate'].' '.$prescription['appointmentTime']); ?></div><div class="col-md-3"><strong>Created:</strong> <?php echo htmlentities($prescription['created_at']); ?></div></div></div>
	<div class="block"><div class="tt">Vitals</div><div class="row"><div class="col-md-3"><strong>Temperature:</strong> <?php echo htmlentities($prescription['temperature'] ?: '-'); ?></div><div class="col-md-3"><strong>Blood Pressure:</strong> <?php echo htmlentities($prescription['blood_pressure'] ?: '-'); ?></div><div class="col-md-3"><strong>Pulse:</strong> <?php echo htmlentities($prescription['pulse'] ?: '-'); ?></div><div class="col-md-3"><strong>Weight:</strong> <?php echo htmlentities($prescription['weight'] ?: '-'); ?></div></div></div>
	<div class="block"><div class="tt">Symptoms</div><?php echo nl2br(htmlentities($prescription['symptoms'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Diagnosis</div><?php echo nl2br(htmlentities($prescription['diagnosis'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Medicines</div><table class="table table-bordered table-hover"><thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead><tbody><?php if($meds && mysqli_num_rows($meds)>0): while($m=mysqli_fetch_array($meds)): ?><tr><td><?php echo htmlentities($m['medicine_name']); ?></td><td><?php echo htmlentities($m['dosage'] ?: '-'); ?></td><td><?php echo htmlentities($m['frequency'] ?: '-'); ?></td><td><?php echo htmlentities($m['duration'] ?: '-'); ?></td><td><?php echo htmlentities($m['instructions'] ?: '-'); ?></td></tr><?php endwhile; else: ?><tr><td colspan="5" class="text-center text-muted">No medicines added.</td></tr><?php endif; ?></tbody></table></div>
	<div class="block"><div class="tt">Tests</div><?php echo nl2br(htmlentities($prescription['tests'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Doctor Notes</div><?php echo nl2br(htmlentities($prescription['notes'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Follow-up Date</div><?php echo htmlentities($prescription['next_visit_date'] ?: '-'); ?></div>
	<a href="appointment-history.php" class="btn btn-primary">Back to History</a>
</div></div>
<?php include('include/footer.php');?>
<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../vendors/fastclick/lib/fastclick.js"></script>
<script src="../vendors/nprogress/nprogress.js"></script>
<script src="../vendors/Chart.js/dist/Chart.min.js"></script>
<script src="../vendors/gauge.js/dist/gauge.min.js"></script>
<script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
<script src="../vendors/iCheck/icheck.min.js"></script>
<script src="../vendors/skycons/skycons.js"></script>
<script src="../vendors/Flot/jquery.flot.js"></script>
<script src="../vendors/Flot/jquery.flot.pie.js"></script>
<script src="../vendors/Flot/jquery.flot.time.js"></script>
<script src="../vendors/Flot/jquery.flot.stack.js"></script>
<script src="../vendors/Flot/jquery.flot.resize.js"></script>
<script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
<script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
<script src="../vendors/flot.curvedlines/curvedLines.js"></script>
<script src="../vendors/DateJS/build/date.js"></script>
<script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
<script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
<script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
<script src="../vendors/moment/min/moment.min.js"></script>
<script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
<script src="../assets/js/custom.min.js"></script>
</body>
</html>
