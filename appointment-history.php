<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();
if(isset($_GET['cancel']))
{
	mysqli_query($con,"update appointment set userStatus='0' where id = '".$_GET['id']."'");
	$_SESSION['msg']="Your appointment canceled !!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Appointment History</title>

	<!-- Bootstrap -->
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Font Awesome -->
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<!-- NProgress -->
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<!-- iCheck -->
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<!-- bootstrap-progressbar -->
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<!-- JQVMap -->
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<!-- bootstrap-daterangepicker -->
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<!-- Custom Theme Style -->
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
		.history-table {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 10px;
			overflow: hidden;
		}
		.history-table td, .history-table th {
			font-size: 14px;
		}
		.status-text {
			font-weight: 600;
		}
	</style>
</head>
<body class="nav-md">
	<?php
	$page_title = 'User  | Appointment History';
	$x_content = true;
	?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Appointment History</h3>

			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']);?></div>
				<?php $_SESSION['msg']=""; ?>
			<?php endif; ?>
			<table class="table table-hover history-table" id="sample-table-1">
				<thead>
					<tr>
						<th class="center">#</th>
						<th class="hidden-xs">Doctor Name</th>
						<th>Specialization</th>
						<th>Consultancy Fee</th>
						<th>Appointment Date / Time </th>
						<th>Appointment Creation Date  </th>
						<th>Current Status</th>
						<th>Action</th>

					</tr>
				</thead>
				<tbody>
					<?php
					$sql=mysqli_query($con,"select doctors.doctorName as docname,appointment.*  from appointment join doctors on doctors.id=appointment.doctorId where appointment.userId='".$_SESSION['id']."'");
					$cnt=1;
					while($row=mysqli_fetch_array($sql))
					{
						?>

						<tr>
							<td class="center"><?php echo $cnt;?>.</td>
							<td class="hidden-xs"><?php echo $row['docname'];?></td>
							<td><?php echo $row['doctorSpecialization'];?></td>
							<td><?php echo $row['consultancyFees'];?></td>
							<td><?php echo $row['appointmentDate'];?> / <?php echo
							$row['appointmentTime'];?>
						</td>
						<td><?php echo $row['postingDate'];?></td>
						<td class="status-text">
							<?php if(($row['userStatus']==1) && ($row['doctorStatus']==1))
							{
								echo '<span class="status-active">Active</span>';
							}
							if(($row['userStatus']==0) && ($row['doctorStatus']==1))
							{
								echo '<span class="status-cancelled">Cancelled by You</span>';
							}

							if(($row['userStatus']==1) && ($row['doctorStatus']==0))
							{
								echo '<span class="status-cancelled">Cancelled by Doctor</span>';
							}



							?></td>
							<td >
								<div class="visible-md visible-lg hidden-sm hidden-xs">
									<?php if(($row['userStatus']==1) && ($row['doctorStatus']==1))
									{ ?>


										<a href="appointment-history.php?id=<?php echo $row['id']?>&cancel=update" onClick="return confirm('Are you sure you want to cancel this appointment ?')" class="btn btn-cancel btn-sm">Cancel</a>
										<a href="pay-fees.php?appointment_id=<?php echo (int)$row['id']; ?>" class="btn btn-primary btn-sm">Pay</a>
									<?php } else {

										echo '<span class="status-cancelled">Cancelled</span>';
									} ?>
								</div>
							</td>
						</tr>

						<?php
						$cnt=$cnt+1;
					}?>


				</tbody>
			</table>
		</div>
	</div>

	<?php include('include/footer.php');?>
	<!-- jQuery -->
	<script src="vendors/jquery/dist/jquery.min.js"></script>
	<!-- Bootstrap -->
	<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<!-- FastClick -->
	<script src="vendors/fastclick/lib/fastclick.js"></script>
	<!-- NProgress -->
	<script src="vendors/nprogress/nprogress.js"></script>
	<!-- Chart.js -->
	<script src="vendors/Chart.js/dist/Chart.min.js"></script>
	<!-- gauge.js -->
	<script src="vendors/gauge.js/dist/gauge.min.js"></script>
	<!-- bootstrap-progressbar -->
	<script src="vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
	<!-- iCheck -->
	<script src="vendors/iCheck/icheck.min.js"></script>
	<!-- Skycons -->
	<script src="vendors/skycons/skycons.js"></script>
	<!-- Flot -->
	<script src="vendors/Flot/jquery.flot.js"></script>
	<script src="vendors/Flot/jquery.flot.pie.js"></script>
	<script src="vendors/Flot/jquery.flot.time.js"></script>
	<script src="vendors/Flot/jquery.flot.stack.js"></script>
	<script src="vendors/Flot/jquery.flot.resize.js"></script>
	<!-- Flot plugins -->
	<script src="vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
	<script src="vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
	<script src="vendors/flot.curvedlines/curvedLines.js"></script>
	<!-- DateJS -->
	<script src="vendors/DateJS/build/date.js"></script>
	<!-- JQVMap -->
	<script src="vendors/jqvmap/dist/jquery.vmap.js"></script>
	<script src="vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
	<script src="vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
	<!-- bootstrap-daterangepicker -->
	<script src="vendors/moment/min/moment.min.js"></script>
	<script src="vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
	<!-- Custom Theme Scripts -->
	<script src="assets/js/custom.min.js"></script>
</body>
