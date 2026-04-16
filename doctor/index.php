<?php
session_start();
include("include/config.php");
error_reporting(0);
if(isset($_POST['submit']))
{
	// Keep shared-host login logic minimal to avoid redirect/logging failures.
	$username = hms_escape($con, trim($_POST['username'] ?? ''));
	$password = trim($_POST['password'] ?? '');
	$ret = hms_query($con, "SELECT id, doctorName, docEmail, password FROM doctors WHERE docEmail='".$username."' LIMIT 1");
	$num = hms_fetch_array($ret);
	$stored = (string)($num['password'] ?? '');
	$isPasswordValid = false;

	if ($num) {
		if ($stored === $password) {
			$isPasswordValid = true;
		} elseif ($stored !== '' && password_verify($password, $stored)) {
			$isPasswordValid = true;
		}
	}

	if($num && $isPasswordValid)
	{
		session_regenerate_id(true);
		$_SESSION['dlogin'] = $num['docEmail'];
		$_SESSION['id'] = (int)$num['id'];
		$_SESSION['doctor_id'] = (int)$num['id'];
		$_SESSION['doctorName'] = $num['doctorName'];
		header("Location: dashboard.php");
		exit();
	}

	$_SESSION['errmsg'] = "Invalid username or password";
	header("Location: index.php");
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor Login</title>
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
					<div class="box-login">
						<form class="form-login" method="post">
							<div class="login-brand">
								<img src="../assets/images/zantus-logo.jpg" alt="Zantus Life Science Hospital">
							</div>
							<fieldset>
								<legend>
									Zantus HMS | Doctor Login
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
										</span>
										<a href="forgot-password.php">
											Forgot Password ?
										</a>
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
						</div>
					</div>
				</body>
				</html>
