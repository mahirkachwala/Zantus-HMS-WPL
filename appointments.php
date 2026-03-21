<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function appointmentColumnExists($con, $columnName) {
	$check = mysqli_query($con, "SHOW COLUMNS FROM appointment LIKE '" . mysqli_real_escape_string($con, $columnName) . "'");
	return ($check && mysqli_num_rows($check) > 0);
}

$hasVisitStatus = appointmentColumnExists($con, 'visitStatus');

if(isset($_GET['cancel']))
{
	mysqli_query($con,"update appointment set userStatus='0' where id = '".$_GET['id']."'");
	$_SESSION['msg']="Your appointment canceled !!";
	header('location:appointments.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Appointments</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.page-heading { font-size: 22px; font-weight: 700; color: #1e3a8a; margin-bottom: 14px; }
		.history-table { background: #fff; border: 1px solid #e6ebf5; border-radius: 10px; overflow: hidden; }
		.history-table td, .history-table th { font-size: 14px; }
	</style>
</head>
<body class="nav-md">
	<?php $page_title = 'User | Appointments'; $x_content = true; ?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Current Appointments (Pending / Active)</h3>

			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']);?></div>
				<?php $_SESSION['msg']=""; ?>
			<?php endif; ?>
			<table class="table table-hover history-table" id="sample-table-1">
				<thead>
					<tr>
						<th>#</th>
						<th>Doctor Name</th>
						<th>Specialization</th>
						<th>Consultancy Fee</th>
						<th>Payment</th>
						<th>Appointment Date / Time</th>
						<th>Current Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$activeWhere = "appointment.userStatus=1 AND appointment.doctorStatus=1";
					if($hasVisitStatus) {
						$activeWhere .= " AND COALESCE(appointment.visitStatus,'Scheduled')='Scheduled'";
					}
					$sql=mysqli_query($con,"select doctors.doctorName as docname,appointment.* from appointment join doctors on doctors.id=appointment.doctorId where appointment.userId='".$_SESSION['id']."' and (".$activeWhere.") order by appointment.id desc");
					$cnt=1;
					while($row=mysqli_fetch_array($sql)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo htmlentities($row['docname']); ?></td>
						<td><?php echo htmlentities($row['doctorSpecialization']); ?></td>
						<td><?php echo htmlentities($row['consultancyFees']); ?></td>
						<td>
							<?php if(($row['paymentStatus'] ?? 'Pending') === 'Paid'): ?>
								<span class="status-active">Paid</span>
							<?php else: ?>
								<span class="status-cancelled"><?php echo htmlentities($row['paymentStatus'] ?? 'Pending'); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo htmlentities($row['appointmentDate'].' / '.$row['appointmentTime']); ?></td>
						<td><span class="status-active">Active</span></td>
						<td>
							<a href="appointments.php?id=<?php echo (int)$row['id']; ?>&cancel=update" onClick="return confirm('Are you sure you want to cancel this appointment ?')" class="btn btn-cancel btn-sm">Cancel</a>
							<?php if(($row['paymentStatus'] ?? 'Pending') !== 'Paid'): ?>
								<a href="pay-fees.php?appointment_id=<?php echo (int)$row['id']; ?>" class="btn btn-primary btn-sm">Pay</a>
							<?php endif; ?>
						</td>
					</tr>
					<?php $cnt++; } ?>
					<?php if($cnt===1): ?>
					<tr><td colspan="8" class="text-center text-muted">No current appointments found.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php include('include/footer.php');?>
	<script src="vendors/jquery/dist/jquery.min.js"></script>
	<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<script src="vendors/fastclick/lib/fastclick.js"></script>
	<script src="vendors/nprogress/nprogress.js"></script>
	<script src="vendors/Chart.js/dist/Chart.min.js"></script>
	<script src="vendors/gauge.js/dist/gauge.min.js"></script>
	<script src="vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
	<script src="vendors/iCheck/icheck.min.js"></script>
	<script src="vendors/skycons/skycons.js"></script>
	<script src="vendors/Flot/jquery.flot.js"></script>
	<script src="vendors/Flot/jquery.flot.pie.js"></script>
	<script src="vendors/Flot/jquery.flot.time.js"></script>
	<script src="vendors/Flot/jquery.flot.stack.js"></script>
	<script src="vendors/Flot/jquery.flot.resize.js"></script>
	<script src="vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
	<script src="vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
	<script src="vendors/flot.curvedlines/curvedLines.js"></script>
	<script src="vendors/DateJS/build/date.js"></script>
	<script src="vendors/jqvmap/dist/jquery.vmap.js"></script>
	<script src="vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
	<script src="vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
	<script src="vendors/moment/min/moment.min.js"></script>
	<script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	<script src="assets/js/custom.min.js"></script>
</body>
</html>
