<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();
$msg = '';
if(isset($_POST['submit']))
{
	$fname=$_POST['fname'];
	$address=$_POST['address'];
	$city=$_POST['city'];
	$gender=$_POST['gender'];
	$sql=hms_query($con,"Update users set fullName='$fname',address='$address',city='$city',gender='$gender' where id='".$_SESSION['id']."'");
	if($sql)
	{
		$msg="Your Profile updated Successfully";
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Edit Profile</title>

	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
	<?php
	$page_title = 'User | Edit Profile';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h5 style="color: green; font-size:18px; ">
				<?php if(!empty($msg)) { echo htmlentities($msg);}?> </h5>
				<div class="row margin-top-30">
					<div class="col-lg-8 col-md-12">
						<div class="panel panel-white">
							<div class="panel-heading">
								<h5 class="panel-title">Edit Profile</h5>
							</div>
							<div class="panel-body">
								<?php
								$sql=hms_query($con,"select * from users where id='".$_SESSION['id']."'");
								while($data=hms_fetch_array($sql))
								{
									?>
									<h4><?php echo htmlentities($data['fullName']);?>'s Profile</h4>
									<p><b>Profile Reg. Date: </b><?php echo htmlentities($data['regDate']);?></p>
									<?php if($data['updationDate']){?>
										<p><b>Profile Last Updation Date: </b><?php echo htmlentities($data['updationDate']);?></p>
									<?php } ?>
									<hr />													<form role="form" name="edit" method="post">
										<div class="form-group">
											<label for="fname">
												User Name
											</label>
											<input type="text" name="fname" class="form-control" value="<?php echo htmlentities($data['fullName']);?>" >
										</div>
										<div class="form-group">
											<label for="address">
												Address
											</label>
											<textarea name="address" class="form-control"><?php echo htmlentities($data['address']);?></textarea>
										</div>
										<div class="form-group">
											<label for="city">
												City
											</label>
											<input type="text" name="city" class="form-control" required="required"  value="<?php echo htmlentities($data['city']);?>" >
										</div>
										<div class="form-group">
											<label for="gender">
												Gender
											</label>
											<select name="gender" class="form-control" required="required" >
												<option value="<?php echo htmlentities($data['gender']);?>"><?php echo htmlentities($data['gender']);?></option>
												<option value="male">Male</option>
												<option value="female">Female</option>
												<option value="other">Other</option>
											</select>
										</div>
										<div class="form-group">
											<label for="fess">
												User Email
											</label>
											<input type="email" name="uemail" class="form-control"  readonly="readonly"  value="<?php echo htmlentities($data['email']);?>">
											<a href="change-emaild.php">Update your email id</a>
										</div>
										<button type="submit" name="submit" class="btn btn-o btn-primary">
											Update
										</button>
									</form>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-12 col-md-12">
				<div class="panel panel-white">
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