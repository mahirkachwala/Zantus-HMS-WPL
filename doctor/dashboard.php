<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor  | Dashboard</title>


	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="../assets/css/custom.css" rel="stylesheet">
	<style>
		.dashboard-grid .x_panel {
			border-radius: 12px;
			border: 1px solid #e6ebf5;
			box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
		}
		.dashboard-grid .x_title h2 {
			color: #1e3a8a;
			font-weight: 700;
		}
		.dashboard-grid .panel-body {
			padding: 24px 10px;
		}
		.dashboard-grid .fa-stack-2x.text-primary {
			color: #1e3a8a !important;
		}
		.dashboard-grid a {
			font-weight: 600;
			color: #0f172a;
		}
		.dashboard-grid a:hover {
			color: #1e40af;
		}
	</style>
</head>
<body class="nav-md">
	<?php include('include/header.php');?>
	<?php
	$doctorId = (int)($_SESSION['id'] ?? 0);
	$completedCount = 0;
	$activeCount = 0;
	$tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'current_appointments'");
	$appointmentTable = ($tableCheck && mysqli_num_rows($tableCheck) > 0) ? 'current_appointments' : 'appointment';
	if($doctorId > 0) {
		$res1 = mysqli_query($con, "SELECT COUNT(*) as total FROM $appointmentTable WHERE doctorId='$doctorId' AND visitStatus='Completed'");
		$row1 = mysqli_fetch_assoc($res1);
		$completedCount = (int)($row1['total'] ?? 0);
		$res2 = mysqli_query($con, "SELECT COUNT(*) as total FROM $appointmentTable WHERE doctorId='$doctorId' AND userStatus='1' AND doctorStatus='1' AND visitStatus!='Completed'");
		$row2 = mysqli_fetch_assoc($res2);
		$activeCount = (int)($row2['total'] ?? 0);
	}
	?>
	<div class="row dashboard-grid">
		<div class="col-md-4 col-sm-4 ">
			<div class="x_panel tile fixed_height_320">
				<div class="x_title">
					<h2>My Profile</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
						</li>
						<li><a class="close-link"><i class="fa fa-close"></i></a>
						</li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-smile-o fa-stack-1x fa-inverse"></i> </span>
							<p class="links cl-effect-1">
								<a href="edit-profile.php">
									Update Profile
								</a>
							</p>
						</div>
					</div>

				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4 ">
			<div class="x_panel tile fixed_height_320">
				<div class="x_title">
					<h2>Visit Management</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
						</li>
						<li><a class="close-link"><i class="fa fa-close"></i></a>
						</li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-stethoscope fa-stack-1x fa-inverse"></i> </span>
							<p class="text-muted">Pending Clinical Visits: <strong><?php echo $activeCount; ?></strong></p>
							<p class="cl-effect-1">
								<a href="visit-management.php">
									Check In / Check Out Patients
								</a>
							</p>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="col-md-4 col-sm-4 ">
			<div class="x_panel tile fixed_height_320">
				<div class="x_title">
					<h2>Completed Appointments</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
						</li>
						<li><a class="close-link"><i class="fa fa-close"></i></a>
						</li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-check fa-stack-1x fa-inverse"></i> </span>
							<p class="text-muted">Completed Visits: <strong><?php echo $completedCount; ?></strong></p>
							<p class="cl-effect-1">
								<a href="appointment-history.php">
									View Completed with Prescription
								</a>
							</p>
						</div>
					</div>

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