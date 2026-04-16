<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User  | Dashboard</title>


	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="assets/css/custom.min.css" rel="stylesheet">
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
	$userId = (int)($_SESSION['id'] ?? 0);
	$completedCount = 0;
	if($userId > 0) {
		$apptTable = 'appointment';
		$tableCheck = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
		if($tableCheck && hms_num_rows($tableCheck) > 0) {
			$apptTable = 'current_appointments';
		}
		$res = hms_query($con, "SELECT COUNT(*) as total FROM `$apptTable` WHERE userId='$userId' AND visitStatus='Completed'");
		if($res) {
			$rowCount = hms_fetch_assoc($res);
			$completedCount = (int)($rowCount['total'] ?? 0);
		}
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
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-user fa-stack-1x fa-inverse"></i> </span>
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
					<h2>Contact Us</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
						<li><a class="close-link"><i class="fa fa-close"></i></a></li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-envelope fa-stack-1x fa-inverse"></i> </span>
							<p class="cl-effect-1"><a href="contact-us.php">Submit Contact Query</a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4 ">
			<div class="x_panel tile fixed_height_320">
				<div class="x_title">
					<h2>Feedback</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
						<li><a class="close-link"><i class="fa fa-close"></i></a></li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-commenting fa-stack-1x fa-inverse"></i> </span>
							<p class="cl-effect-1"><a href="feedback.php">Share Your Feedback</a></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-sm-4 ">
			<div class="x_panel tile fixed_height_320">
				<div class="x_title">
					<h2>My Appointments</h2>
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
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-list-alt fa-stack-1x fa-inverse"></i> </span>
							<p class="cl-effect-1">
								<a href="appointment-history.php">
									View Appointment History
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
					<h2>My Prescriptions</h2>
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
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-medkit fa-stack-1x fa-inverse"></i> </span>
							<p class="text-muted">Completed Appointments: <strong><?php echo $completedCount; ?></strong></p>
							<p class="cl-effect-1">
								<a href="appointment-history.php">
									View Prescriptions
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
					<h2>Book My Appointment</h2>
					<ul class="nav navbar-right panel_toolbox">
						<li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
						<li><a class="close-link"><i class="fa fa-close"></i></a></li>
					</ul>
					<div class="clearfix"></div>
				</div>
				<div class="x_content">
					<div class="panel panel-white no-radius text-center">
						<div class="panel-body">
							<span class="fa-stack fa-2x"> <i class="fa fa-square fa-stack-2x text-primary"></i> <i class="fa fa-copy fa-stack-1x fa-inverse"></i> </span>
							<p class="cl-effect-1"><a href="book-appointment.php">Book Appointment</a></p>
						</div>
					</div>
				</div>
			</div>
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
