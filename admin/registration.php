<?php
include_once('include/config.php');

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

$message = '';

if (isset($_POST['submit'])) {
	$fname = trim($_POST['full_name'] ?? '');
	$address = trim($_POST['address'] ?? '');
	$city = trim($_POST['city'] ?? '');
	$gender = trim($_POST['gender'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$confirmPassword = $_POST['password_again'] ?? '';

	if ($password !== $confirmPassword) {
		$message = 'Password and Confirm Password fields do not match.';
	} elseif (!isStrongPassword($password)) {
		$message = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
	} else {
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);
		$query = hms_query(
			$con,
			"INSERT INTO users(fullName,address,city,gender,email,password) VALUES('" .
			hms_escape($con, $fname) . "','" .
			hms_escape($con, $address) . "','" .
			hms_escape($con, $city) . "','" .
			hms_escape($con, $gender) . "','" .
			hms_escape($con, $email) . "','" .
			hms_escape($con, $passwordHash) . "')"
		);
		if ($query) {
			$message = 'Successfully registered. You can login now.';
		} else {
			$message = 'Unable to register right now.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User Registration</title>
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
		.logo {
			text-align: center;
			margin-bottom: 12px;
		}
		.logo img {
			max-width: 72px;
			border-radius: 10px;
			background: #fff;
			padding: 5px;
			box-shadow: 0 2px 8px rgba(0,0,0,.12);
		}
		.box-register {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 14px;
			box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
			padding: 14px 18px;
		}
		.box-register legend {
			color: #1e3a8a;
			font-size: 24px;
			font-weight: 700;
		}
	</style>
	<script type="text/javascript">
		function strongPassword(pwd) {
			return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd || '');
		}

		function valid() {
			if (document.registration.password.value !== document.registration.password_again.value) {
				alert("Password and Confirm Password Field do not match !!");
				document.registration.password_again.focus();
				return false;
			}
			if (!strongPassword(document.registration.password.value)) {
				alert("Password must be minimum 8 characters with uppercase, lowercase, number and special character.");
				document.registration.password.focus();
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
				<img src="../assets/images/zantus-logo.jpg" alt="Zantus"/>
			</div>

			<div class="box-register">
				<form name="registration" id="registration" method="post" onsubmit="return valid();">
					<fieldset>
						<legend>Zantus HMS | Sign Up</legend>
						<p>Enter your personal details below:</p>
						<?php if ($message !== ''): ?>
							<div class="alert alert-info"><?php echo htmlentities($message); ?></div>
						<?php endif; ?>
						<div class="form-group">
							<input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
						</div>
						<div class="form-group">
							<input type="text" class="form-control" name="address" placeholder="Address" required>
						</div>
						<div class="form-group">
							<input type="text" class="form-control" name="city" placeholder="City" required>
						</div>
						<div class="form-group">
							<label class="block">Gender</label>
							<div class="clip-radio radio-primary">
								<input type="radio" id="rg-female" name="gender" value="female">
								<label for="rg-female">Female</label>
								<input type="radio" id="rg-male" name="gender" value="male">
								<label for="rg-male">Male</label>
							</div>
						</div>
						<p>Enter your account details below:</p>
						<div class="form-group">
							<span class="input-icon">
								<input type="email" class="form-control" name="email" id="email" onblur="userAvailability()" placeholder="Email" required>
								<i class="fa fa-envelope"></i>
							</span>
							<span id="user-availability-status1" style="font-size:12px;"></span>
						</div>
						<div class="form-group">
							<span class="input-icon">
								<input type="password" class="form-control" id="password" name="password" placeholder="Password" minlength="8" required>
								<i class="fa fa-lock"></i>
							</span>
						</div>
						<div class="form-group">
							<span class="input-icon">
								<input type="password" class="form-control" name="password_again" placeholder="Password Again" required>
								<i class="fa fa-lock"></i>
							</span>
						</div>
						<div class="form-actions">
							<p>
								Already have an account?
								<a href="index.php">Log-in</a>
							</p>
							<button type="submit" class="btn btn-primary pull-right" id="submit" name="submit">
								Submit <i class="fa fa-arrow-circle-right"></i>
							</button>
						</div>
					</fieldset>
				</form>

				<div class="copyright">
					&copy; <span class="current-year"></span><span class="text-bold text-uppercase"> Zantus Life Science Hospital</span>. <span>All rights reserved</span>
				</div>
			</div>
		</div>
	</div>

	<script src="../vendors/jquery/dist/jquery.min.js"></script>
	<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
	<script src="../assets/js/custom.min.js"></script>
	<script>
		function userAvailability() {
			jQuery.ajax({
				url: "check_availability.php",
				data: 'email=' + jQuery("#email").val(),
				type: "POST",
				success: function(data) {
					jQuery("#user-availability-status1").html(data);
				},
				error: function() {}
			});
		}
	</script>
</body>
</html>
