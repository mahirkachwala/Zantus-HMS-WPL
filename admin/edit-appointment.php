<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$aid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($aid <= 0) {
	header('location:appointment-history.php');
	exit();
}

if (isset($_POST['submit'])) {
	$appDate = hms_escape($con, $_POST['appointmentDate']);
	$appTime = hms_escape($con, $_POST['appointmentTime']);
	$fees = (int)$_POST['consultancyFees'];
	$paymentStatus = hms_escape($con, $_POST['paymentStatus']);
	$visitStatus = hms_escape($con, $_POST['visitStatus']);

	hms_query($con, "UPDATE appointment SET appointmentDate='$appDate', appointmentTime='$appTime', consultancyFees='$fees', paymentStatus='$paymentStatus', visitStatus='$visitStatus' WHERE id='$aid'");
	if (in_array($visitStatus, ['Completed', 'Cancelled'], true)) {
		hms_archive_appointment($con, 'appointment', $aid);
	}
	$_SESSION['msg'] = 'Appointment updated successfully.';
	header('location:appointment-history.php');
	exit();
}

$q = hms_query($con, "SELECT appointment.*, users.fullName as patientName, doctors.doctorName as doctorName FROM appointment JOIN users ON users.id=appointment.userId JOIN doctors ON doctors.id=appointment.doctorId WHERE appointment.id='$aid'");
$row = hms_fetch_array($q);
if (!$row) {
	header('location:appointment-history.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Edit Appointment</title>
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
$page_title = 'Admin | Edit Appointment';
$x_content = true;
include('include/header.php');
?>
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="x_panel">
			<div class="x_title">
				<h2>Edit Appointment</h2>
				<div class="clearfix"></div>
			</div>
			<div class="x_content">
				<p><strong>Patient:</strong> <?php echo htmlentities($row['patientName']); ?> | <strong>Doctor:</strong> <?php echo htmlentities($row['doctorName']); ?></p>
				<form method="post">
					<div class="form-group">
						<label>Appointment Date</label>
						<input type="date" name="appointmentDate" value="<?php echo htmlentities($row['appointmentDate']); ?>" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Appointment Time</label>
						<input type="text" name="appointmentTime" value="<?php echo htmlentities($row['appointmentTime']); ?>" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Consultancy Fees</label>
						<input type="number" name="consultancyFees" value="<?php echo (int)$row['consultancyFees']; ?>" class="form-control" required>
					</div>
					<div class="form-group">
						<label>Payment Status</label>
						<select name="paymentStatus" class="form-control" required>
							<?php $payments=['Pending','Paid','Failed']; foreach($payments as $p): ?>
								<option value="<?php echo $p; ?>" <?php echo (($row['paymentStatus'] ?? 'Pending') === $p) ? 'selected' : ''; ?>><?php echo $p; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-group">
						<label>Visit Status</label>
						<select name="visitStatus" class="form-control" required>
							<?php $visits=['Scheduled','Checked In','Completed','Cancelled']; foreach($visits as $v): ?>
								<option value="<?php echo $v; ?>" <?php echo (($row['visitStatus'] ?? 'Scheduled') === $v) ? 'selected' : ''; ?>><?php echo $v; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<button type="submit" name="submit" class="btn btn-primary">Save Changes</button>
					<a href="appointment-history.php" class="btn btn-cancel">Back</a>
				</form>
			</div>
		</div>
	</div>
</div>
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
