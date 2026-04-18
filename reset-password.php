<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
include("include/config.php");

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

if(isset($_POST['change']))
{
$name=$_SESSION['name'];
$email=$_SESSION['email'];
$newpassword=$_POST['password'];
	if(($_POST['password'] ?? '') !== ($_POST['password_again'] ?? '')) {
		echo "<script>alert('Password and Confirm Password Field do not match.');</script>";
	} elseif(!isStrongPassword($newpassword)) {
		echo "<script>alert('Password must be minimum 8 characters with uppercase, lowercase, number and special character.');</script>";
	} else {
        // Hash new password before storing
        $newHash = password_hash($newpassword, PASSWORD_DEFAULT);
        $query=hms_query($con,"update users set password='".hms_escape($con, $newHash)."' where fullName='".hms_escape($con, $name)."' and email='".hms_escape($con, $email)."'");
		if ($query) {
		echo "<script>alert('Password successfully updated.');</script>";
		echo "<script>window.location.href ='index.php'</script>";
		}
	}

}


?>


<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Zantus HMS | Patient Reset Password</title>

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

				<script type="text/javascript">
function valid()
{
 if(!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(document.passwordreset.password.value || ''))
{
alert("Password must be minimum 8 characters with uppercase, lowercase, number and special character.");
document.passwordreset.password.focus();
return false;
}
 if(document.passwordreset.password.value!= document.passwordreset.password_again.value)
{
alert("Password and Confirm Password Field do not match  !!");
document.passwordreset.password_again.focus();
return false;
}
return true;
}
</script>
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
					<form class="form-login" name="passwordreset" method="post" onSubmit="return valid();">
						<div class="login-brand">
							<img src="assets/images/zantus-logo.jpg" alt="Zantus Life Science Hospital">
						</div>
						<fieldset>
							<legend>
								Zantus HMS | Patient Reset Password
							</legend>
							<p>
								Please set your new password.<br />
								<span style="color:red;"><?php echo $_SESSION['errmsg']; ?><?php echo $_SESSION['errmsg']="";?></span>
							</p>

<div class="form-group">
<span class="input-icon">
<input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="8" required>
<i class="fa fa-lock"></i> </span>
</div>


<div class="form-group">
<span class="input-icon">
<input type="password" class="form-control"  id="password_again" name="password_again" placeholder="Password Again" required>
<i class="fa fa-lock"></i> </span>
</div>


							<div class="form-actions">

								<button type="submit" class="btn btn-primary pull-right" name="change">
									Change <i class="fa fa-arrow-circle-right"></i>
								</button>
							</div>
							<div class="new-account">
								Already have an account?
								<a href="index.php">
									Log-in
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
			</div>
		</div>
		<script src="vendors/jquery/dist/jquery.min.js"></script>
		<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/custom.min.js"></script>

	</body>

</html>
