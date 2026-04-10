<?php
include_once('include/config.php');
$msg = '';
$err = '';

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

if(isset($_POST['submit']))
{
	$fname = trim($_POST['full_name'] ?? '');
	$address = trim($_POST['address'] ?? '');
	$city = trim($_POST['city'] ?? '');
	$gender = trim($_POST['gender'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$passwordAgain = $_POST['password_again'] ?? '';

	if($password !== $passwordAgain) {
		$err = 'Password and Confirm Password do not match.';
	} elseif(!isStrongPassword($password)) {
		$err = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
	} elseif($fname === '' || $address === '' || $city === '' || $gender === '' || $email === '' || $password === '') {
		$err = 'Please fill all required fields.';
	} else {
		$emailEsc = hms_escape($con, $email);
		$check = hms_query($con, "SELECT id FROM users WHERE email='$emailEsc' LIMIT 1");
		if($check && hms_num_rows($check) > 0) {
			$err = 'Email already registered. Please login.';
		} else {
			$query = hms_query(
				$con,
				"INSERT INTO users(fullName,address,city,gender,email,password) VALUES('".
				hms_escape($con, $fname)."','".
				hms_escape($con, $address)."','".
				hms_escape($con, $city)."','".
				hms_escape($con, $gender)."','".
				$emailEsc."','".
				hms_escape($con, $password)."')"
			);
			if($query) {
				$msg = 'Successfully Registered. You can login now.';
			} else {
				$err = 'Unable to create account. Try again.';
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User Registration</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		body.login {
			background: #f4f7fb;
		}
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
		.alert {
			text-align: left;
			margin-bottom: 12px;
		}
	</style>
	<script type="text/javascript">
		function strongPassword(pwd) {
			return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd || '');
		}

		function valid() {
			if(document.registration.password.value != document.registration.password_again.value) {
				alert("Password and Confirm Password Field do not match !!");
				document.registration.password_again.focus();
				return false;
			}
			if(!strongPassword(document.registration.password.value)) {
				alert("Password must be minimum 8 characters with uppercase, lowercase, number and special character.");
				document.registration.password.focus();
				return false;
			}
			return true;
		}

		function userAvailability() {
			jQuery.ajax({
				url: "check_availability.php",
				data:'email='+jQuery("#email").val(),
				type: "POST",
				success:function(data){
					jQuery("#user-availability-status1").html(data);
				},
				error:function (){}
			});
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
								<form class="form-login" name="registration" id="registration" method="post" onSubmit="return valid();">
									<div class="login-brand">
										<img src="assets/images/zantus-logo.jpg" alt="Zantus Life Science Hospital">
									</div>
									<fieldset>
										<legend>
											Zantus HMS | Patient Create Account
										</legend>
										<p>
											Please enter your details to create your account.
										</p>

										<?php if($msg !== ''): ?>
											<div class="alert alert-success"><?php echo htmlentities($msg); ?></div>
										<?php endif; ?>
										<?php if($err !== ''): ?>
											<div class="alert alert-danger"><?php echo htmlentities($err); ?></div>
										<?php endif; ?>

										<div class="form-group">
											<input type="text" class="form-control" name="full_name" placeholder="Full Name" value="<?php echo htmlentities($_POST['full_name'] ?? ''); ?>" required>
										</div>
										<div class="form-group">
											<input type="text" class="form-control" name="address" placeholder="Address" value="<?php echo htmlentities($_POST['address'] ?? ''); ?>" required>
										</div>
										<div class="form-group">
											<input type="text" class="form-control" name="city" placeholder="City" value="<?php echo htmlentities($_POST['city'] ?? ''); ?>" required>
										</div>
										<div class="form-group">
											<select name="gender" class="form-control" required>
												<option value="">Select Gender</option>
												<option value="male" <?php echo (($_POST['gender'] ?? '')==='male') ? 'selected' : ''; ?>>Male</option>
												<option value="female" <?php echo (($_POST['gender'] ?? '')==='female') ? 'selected' : ''; ?>>Female</option>
												<option value="other" <?php echo (($_POST['gender'] ?? '')==='other') ? 'selected' : ''; ?>>Other</option>
											</select>
										</div>
										<div class="form-group">
											<input type="email" class="form-control" name="email" id="email" onBlur="userAvailability()" placeholder="Email" value="<?php echo htmlentities($_POST['email'] ?? ''); ?>" required>
											<span id="user-availability-status1" style="font-size:12px;"></span>
										</div>
										<div class="form-group">
											<input type="password" class="form-control password" name="password" id="password" placeholder="Password" minlength="8" required>
											<small class="text-muted">Min 8 chars with uppercase, lowercase, number, and special character.</small>
										</div>
										<div class="form-group form-actions">
											<input type="password" class="form-control password" name="password_again" id="password_again" placeholder="Confirm Password" required>
										</div>

										<div class="form-actions">
											<button type="submit" class="btn btn-primary pull-right" name="submit">
												Create Account <i class="fa fa-arrow-circle-right"></i>
											</button>
										</div>
										<div class="new-account">
											Already have an account?
											<a href="index.php">Login</a>
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
	</body>
	</html>