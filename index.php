<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
error_reporting(0);
include("include/config.php");
if(isset($_POST['submit']))
{
	// Secure login: fetch user by email and verify password using password_verify().
	$username = hms_escape($con, trim($_POST['username'] ?? ''));
	$pwd = $_POST['password'] ?? '';

	// Try to get the user record (using mysqli prepared path when available).
	$num = null;
	if ($stmt = $con->prepare('SELECT * FROM users WHERE email = ? LIMIT 1')) {
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$res = $stmt->get_result();
		if ($res) {
			$num = $res->fetch_assoc();
		}
		$stmt->close();
	} else {
		$ret = hms_query($con, "SELECT * FROM users WHERE email='".$username."' LIMIT 1");
		$num = hms_fetch_array($ret);
	}

	if ($num) {
		$stored = $num['password'] ?? '';
		$isValid = false;

		// Prefer password_verify (bcrypt/argon2 etc.).
		if ($stored !== '' && password_verify($pwd, $stored)) {
			$isValid = true;
		} elseif ($stored === $pwd) {
			// Legacy plaintext password detected: accept login but migrate to hashed password.
			$isValid = true;
			$newHash = password_hash($pwd, PASSWORD_DEFAULT);
			hms_query($con, "UPDATE users SET password='".hms_escape($con, $newHash)."' WHERE id='".$num['id']."'");
		}

		if ($isValid) {
			// Regenerate session id on login to avoid session fixation/collision.
			session_regenerate_id(true);
			$extra="dashboard.php";
			$_SESSION['login']=$num['email'];
			$_SESSION['fullName']=$num['fullName'];
			$_SESSION['id']=$num['id'];
			$_SESSION['user_id']=$num['id'];
			$host=$_SERVER['HTTP_HOST'];
			$status=1;
			hms_query($con,"insert into userlog(uid,username,status) values('".$_SESSION['id']."','".$_SESSION['login']."','$status')");
			$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
			header("location:http://$host$uri/$extra");
			exit();
		}
	}

	// Failed login path
	$_SESSION['login']=$_POST['username'];
	$status=0;
	hms_query($con,"insert into userlog(username,status) values('".$_SESSION['login']."','$status')");
	$_SESSION['errmsg']="Invalid username or password";
	$extra="index.php";
	$host  = $_SERVER['HTTP_HOST'];
	$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
	header("location:http://$host$uri/$extra");
	exit();
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
