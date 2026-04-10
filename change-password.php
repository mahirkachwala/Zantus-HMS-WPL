<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();
date_default_timezone_set('Asia/Kolkata');
$currentTime = date( 'Y-m-d h:i:s', time () );

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

if(isset($_POST['submit']))
{

	if($_POST['npass'] != $_POST['cfpass']) {
		$_SESSION['msg1']="confirm Password not match !!";
	} elseif(!isStrongPassword($_POST['npass'] ?? '')) {
		$_SESSION['msg1']="Password must be minimum 8 characters with uppercase, lowercase, number and special character.";
	} else {
		$sql=hms_query($con,"SELECT password FROM  users where password='".$_POST['cpass']."' && id='".$_SESSION['id']."'");
		$num=hms_fetch_array($sql);
		if($num)
		{
			$updateResult = hms_query($con,"update users set `password`='".$_POST['npass']."', `updationDate`='$currentTime' where id='".$_SESSION['id']."'");

			$_SESSION['msg1']="Password Changed Successfully !!";
		}
		else
		{
			$_SESSION['msg1']="Old Password not match !!";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User  | change Password</title>


	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
	</style>
	<script type="text/javascript">
		function strongPassword(pwd) {
			return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd || '');
		}

		function valid()
		{
			if(document.chngpwd.cpass.value=="")
			{
				alert("Current Password Filed is Empty !!");
				document.chngpwd.cpass.focus();
				return false;
			}
			else if(document.chngpwd.npass.value=="")
			{
				alert("New Password Filed is Empty !!");
				document.chngpwd.npass.focus();
				return false;
			}
			else if(document.chngpwd.cfpass.value=="")
			{
				alert("Confirm Password Filed is Empty !!");
				document.chngpwd.cfpass.focus();
				return false;
			}
			else if(document.chngpwd.npass.value!= document.chngpwd.cfpass.value)
			{
				alert("Password and Confirm Password Field do not match  !!");
				document.chngpwd.cfpass.focus();
				return false;
			}
			else if(!strongPassword(document.chngpwd.npass.value))
			{
				alert("Password must be minimum 8 characters with uppercase, lowercase, number and special character.");
				document.chngpwd.npass.focus();
				return false;
			}
			return true;
		}
	</script>

</head>
<body class="nav-md">
	<?php
	$page_title = 'User | Change Password';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Change Password</h3>

			<div class="row margin-top-30">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-body">
							<?php if(!empty($_SESSION['msg1'])): ?>
								<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg1']); ?></div>
								<?php $_SESSION['msg1']=''; ?>
							<?php endif; ?>
							<form role="form" name="chngpwd" method="post" onSubmit="return valid();">
								<div class="form-group">
									<label for="exampleInputEmail1">
										Current Password
									</label>
									<input type="password" name="cpass" class="form-control"  placeholder="Enter Current Password">
								</div>
								<div class="form-group">
									<label for="exampleInputPassword1">
										New Password
									</label>
									<input type="password" name="npass" class="form-control"  placeholder="New Password">
								</div>

								<div class="form-group">
									<label for="exampleInputPassword1">
										Confirm Password
									</label>
									<input type="password" name="cfpass" class="form-control"  placeholder="Confirm Password">
								</div>



								<button type="submit" name="submit" class="btn btn-primary">
									Submit
								</button>
							</form>
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