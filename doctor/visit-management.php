<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$doctorId = (int)($_SESSION['id'] ?? 0);

if (isset($_GET['checkin'])) {
	$aid = (int)$_GET['checkin'];
	mysqli_query($con, "UPDATE appointment SET visitStatus='Checked In', checkInTime=NOW() WHERE id='$aid' AND doctorId='$doctorId' AND userStatus='1' AND doctorStatus='1'");
	$_SESSION['msg'] = 'Patient checked in successfully.';
	header('location:visit-management.php');
	exit();
}

if (isset($_POST['checkout'])) {
	$aid = (int)$_POST['appointment_id'];
	$prescription = mysqli_real_escape_string($con, trim($_POST['prescription'] ?? ''));
	if ($prescription === '') {
		$_SESSION['msg'] = 'Prescription is required for check-out.';
	} else {
		mysqli_query($con, "UPDATE appointment SET visitStatus='Completed', checkOutTime=NOW(), prescription='$prescription' WHERE id='$aid' AND doctorId='$doctorId' AND userStatus='1' AND doctorStatus='1'");
		$_SESSION['msg'] = 'Patient checked out and prescription saved.';
	}
	header('location:visit-management.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Visit Management</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php
$page_title = 'Doctor | Visit Management';
$x_content = true;
include('include/header.php');
?>
<div class="row">
	<div class="col-md-12">
		<?php if(!empty($_SESSION['msg'])): ?>
			<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']); ?></div>
			<?php $_SESSION['msg']=''; ?>
		<?php endif; ?>

		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>Patient</th>
					<th>Payment Status</th>
					<th>Appointment Date/Time</th>
					<th>Visit Status</th>
					<th>Check In</th>
					<th>Check Out + Prescription</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$cnt=1;
			$sql = mysqli_query($con, "SELECT appointment.*, users.fullName FROM appointment JOIN users ON users.id=appointment.userId WHERE appointment.doctorId='$doctorId' AND appointment.userStatus='1' AND appointment.doctorStatus='1' AND appointment.paymentStatus='Paid' ORDER BY appointment.id DESC");
			while($row = mysqli_fetch_array($sql)) {
				$status = $row['visitStatus'] ?: 'Scheduled';
			?>
				<tr>
					<td><?php echo $cnt; ?>.</td>
					<td><?php echo htmlentities($row['fullName']); ?></td>
					<td><span class="status-active">Paid</span></td>
					<td><?php echo htmlentities($row['appointmentDate'].' '.$row['appointmentTime']); ?></td>
					<td>
						<?php if($status === 'Completed'): ?>
							<span class="status-active">Completed</span>
						<?php elseif($status === 'Checked In'): ?>
							<span style="color:#1d4ed8;font-weight:700;">Checked In</span>
						<?php else: ?>
							<span>Scheduled</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if($status === 'Scheduled'): ?>
							<a class="btn btn-primary btn-sm" href="visit-management.php?checkin=<?php echo (int)$row['id']; ?>">Check In</a>
						<?php else: ?>
							--
						<?php endif; ?>
					</td>
					<td>
						<?php if($status !== 'Completed'): ?>
							<form method="post" class="form-inline" style="display:flex; gap:8px;">
								<input type="hidden" name="appointment_id" value="<?php echo (int)$row['id']; ?>">
								<input type="text" class="form-control" name="prescription" placeholder="Write prescription" required>
								<button type="submit" name="checkout" class="btn btn-cancel btn-sm">Check Out</button>
							</form>
						<?php else: ?>
							<?php echo nl2br(htmlentities($row['prescription'] ?: 'Prescription added.')); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php $cnt++; } ?>
			<?php if($cnt === 1): ?>
				<tr>
					<td colspan="7" class="text-center text-muted">No paid appointments are ready for visit management yet.</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
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
