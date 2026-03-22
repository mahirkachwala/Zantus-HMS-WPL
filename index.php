<?php
session_start();
error_reporting(0);
include("include/config.php");
if(isset($_POST['submit']))
{
	// PHP session login flow using MySQL query result.
	$ret=mysqli_query($con,"SELECT * FROM users WHERE email='".$_POST['username']."' and password='".md5($_POST['password'])."'");
	$num=mysqli_fetch_array($ret);
	if($num>0)
	{
		// Regenerate session id on login to avoid session fixation/collision.
		session_regenerate_id(true);
$extra="dashboard.php";
$_SESSION['login']=$_POST['username'];
$_SESSION['fullName']=$num['fullName'];
$_SESSION['id']=$num['id'];
$_SESSION['user_id']=$num['id'];
$host=$_SERVER['HTTP_HOST'];
$uip=$_SERVER['REMOTE_ADDR'];
$status=1;
$log=mysqli_query($con,"insert into userlog(uid,username,userip,status) values('".$_SESSION['id']."','".$_SESSION['login']."','$uip','$status')");
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
	$_SESSION['login']=$_POST['username'];
	$uip=$_SERVER['REMOTE_ADDR'];
	$status=0;
	mysqli_query($con,"insert into userlog(username,userip,status) values('".$_SESSION['login']."','$uip','$status')");
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
	<title>User-Login</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
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
					<div class="box-login">
						<div class="box-login">
							<form class="form-login" method="post">
								<div class="login-brand">
									<img src="assets/images/zantus-logo.jpg" alt="Zantus Life Science Hospital">
								</div>

								<fieldset>
									<legend>
										Zantus HMS | Patient Login
									</legend>
									<p>
										Please enter your name and password to log in.<br />
										<span style="color:red;"><?php echo $_SESSION['errmsg']; ?><?php echo $_SESSION['errmsg']="";?></span>
									</p>
									<div class="form-group">
										<span class="input-icon">
											<input type="text" class="form-control" name="username" placeholder="Username">
										</div>
										<div class="form-group form-actions">
											<span class="input-icon">
												<input type="password" class="form-control password" name="password" placeholder="Password">
											</span><a href="forgot-password.php">
												Forgot Password ?
											</a>
										</div>
										<div class="form-actions">

											<button type="submit" class="btn btn-primary pull-right" name="submit">
												Login <i class="fa fa-arrow-circle-right"></i>
											</button>
										</div>
										<div class="new-account">
											Don't have an account yet?
											<a href="registration.php">
												Create an account
											</a>
										</div>
									</fieldset>
								</form>

								<div class="copyright">
									&copy; <span class="current-year"></span><span class="text-bold text-uppercase"> Zantus Life Science Hospital</span>. <span>All rights reserved</span>
								</div>

							</div>

						</div>
					</section>
				</div>
			</body>
			</html>