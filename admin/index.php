<?php
session_start();
error_reporting(0);
include("include/config.php");
if(isset($_POST['submit']))
{
	// PHP session-based admin authentication with database lookup.
	$username = hms_escape($con, trim($_POST['username'] ?? ''));
	$password = $_POST['password'] ?? '';
	$ret=hms_query($con,"SELECT * FROM admin WHERE username='".$username."' LIMIT 1");
	$num=hms_fetch_array($ret);
	$storedPassword = $num['password'] ?? '';
	$isPasswordValid = ($storedPassword === $password);
	if($num && $isPasswordValid)
	{
		session_regenerate_id(true);
$extra="dashboard.php";
$_SESSION['alogin']=$_POST['username'];
$_SESSION['admin_id']=$num['id'];
$_SESSION['login']=$_POST['username'];
$_SESSION['id']=$num['id'];
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
	$_SESSION['errmsg']="Invalid username or password";
	$extra="index.php";
	$host  = $_SERVER['HTTP_HOST'];
	$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
	header("location:http://$host$uri/$extra");
	exit();
}
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin-Login</title>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta content="" name="description" />
	<meta content="" name="author" />
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
	<style>
		.login-brand {
			text-align: center;
			margin-bottom: 12px;
		}
		.login-brand img {
			width: 70px;
			height: 70px;
			border-radius: 12px;
			padding: 6px;
			background: #fff;
			box-shadow: 0 2px 8px rgba(0,0,0,.12);
		}
		.login_content legend {
			color: #1e3a8a;
			font-weight: 600;
		}
		body.login {
			background: #f4f7fb;
		}
	</style>
</head>
<body class="login">
	<div>
		<a class="hiddenanchor" id="signup"></a>
		<a class="hiddenanchor" id="signin"></a>

		<div class="login_wrapper">
			<div class="animate form login_form">
				<section class="login_content">
					<form class="form-login" method="post">
						<div class="login-brand">
							<img src="../assets/images/zantus-logo.jpg" alt="Zantus Life Science Hospital">
						</div>
						<fieldset>
							<legend>
								Zantus HMS | Admin Login
							</legend>
							<p>
								Please enter your name and password to log in.<br />
								<span style="color:red;"><?php echo htmlentities($_SESSION['errmsg']); ?><?php echo htmlentities($_SESSION['errmsg']="");?></span>
							</p>
							<div class="form-group">
								<span class="input-icon">
									<input type="text" class="form-control" name="username" placeholder="Username">
								</div>
								<div class="form-group form-actions">
									<span class="input-icon">
										<input type="password" class="form-control password" name="password" placeholder="Password">
									</span>
								</div>
								<div class="form-actions">

									<button type="submit" class="btn btn-primary pull-right" name="submit">
										Login <i class="fa fa-arrow-circle-right"></i>
									</button>
								</div>

							</fieldset>
						</form>
						<div class="copyright">
							&copy; <span class="current-year"></span><span class="text-bold text-uppercase"> Zantus Life Science Hospital</span>. <span>All rights reserved</span>
						</div>
					</section>
				</div>


			</div>
		</div>

		<script src="vendor/jquery/jquery.min.js"></script>
		<script src="vendor/bootstrap/js/bootstrap.min.js"></script>
		<script src="vendor/modernizr/modernizr.js"></script>
		<script src="vendor/jquery-cookie/jquery.cookie.js"></script>
		<script src="vendor/perfect-scrollbar/perfect-scrollbar.min.js"></script>
		<script src="vendor/switchery/switchery.min.js"></script>
		<script src="vendor/jquery-validation/jquery.validate.min.js"></script>

		<script src="assets/js/main.js"></script>

		<script src="assets/js/login.js"></script>
		<script>
			jQuery(document).ready(function() {
				Main.init();
				Login.init();
			});
		</script>

	</body>
	</html>