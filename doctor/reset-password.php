<?php
session_start();
include("include/config.php");

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

if(isset($_POST['change']))
{
$cno=$_SESSION['cnumber'];
$email=$_SESSION['email'];
	$newpassword=$_POST['password'];
	if(($_POST['password'] ?? '') !== ($_POST['password_again'] ?? '')) {
		echo "<script>alert('Password and Confirm Password Field do not match.');</script>";
	} elseif(!isStrongPassword($newpassword)) {
		echo "<script>alert('Password must be minimum 8 characters with uppercase, lowercase, number and special character.');</script>";
	} else {
	// Hash the new password before storing
	$newHash = password_hash($newpassword, PASSWORD_DEFAULT);
	$query=hms_query($con,"update doctors set password='".hms_escape($con, $newHash)."' where contactno='".hms_escape($con, $cno)."' and docEmail='".hms_escape($con, $email)."'");
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
		<title>Password Reset</title>

		<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
		<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
		<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
		<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
		<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
		<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		<link href="../assets/css/custom.min.css" rel="stylesheet">
		<style>
			body.login {
				background: #f4f7fb;
			}
			.main-login {
				margin-top: 40px;
			}
			.logo h2 {
				color: #1e3a8a;
				font-weight: 700;
				text-align: center;
			}
			.box-login {
				background: #fff;
				border: 1px solid #e6ebf5;
				border-radius: 14px;
				box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
				padding: 14px 18px;
			}
			.box-login legend {
				color: #1e3a8a;
				font-weight: 700;
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
		<div class="row">
			<div class="main-login col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
				<div class="logo margin-top-30">
				<a href="../index.html"><h2> HMS | Patient Reset Password</h2></a>
				</div>

				<div class="box-login">
					<form class="form-login" name="passwordreset" method="post" onSubmit="return valid();">
						<fieldset>
							<legend>
								Patient Reset Password
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
						&copy; <span class="current-year"></span><span class="text-bold text-uppercase"> HMS</span>. <span>All rights reserved</span>
					</div>

				</div>

			</div>
		</div>
		<script src="../vendors/jquery/dist/jquery.min.js"></script>
		<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
		<script src="../assets/js/custom.min.js"></script>

	</body>

</html>
