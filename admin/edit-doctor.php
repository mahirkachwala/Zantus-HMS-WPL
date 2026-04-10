<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$doctorSpecColumn = 'specilization';
$doctorSpecType = '';
$doctorSpecColumnCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specialization'");
if ($doctorSpecColumnCheck && hms_num_rows($doctorSpecColumnCheck) > 0) {
	$doctorSpecColumn = 'specialization';
	$doctorSpecMeta = hms_fetch_assoc($doctorSpecColumnCheck);
	$doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
} else {
	$doctorSpecLegacyCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specilization'");
	if ($doctorSpecLegacyCheck && hms_num_rows($doctorSpecLegacyCheck) > 0) {
		$doctorSpecMeta = hms_fetch_assoc($doctorSpecLegacyCheck);
		$doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
	}
}
$isDoctorSpecNumeric = preg_match('/int|decimal|float|double/', $doctorSpecType) === 1;

$specTable = '';
$specColumn = 'specialization';
if (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecialization'")) > 0) {
	$specTable = 'doctorspecialization';
	$specColumn = 'specialization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecilization'")) > 0) {
	$specTable = 'doctorspecilization';
	$specColumn = 'specilization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctor_specialization'")) > 0) {
	$specTable = 'doctor_specialization';
	$specColumn = 'specialization';
}

$did=intval($_GET['id']);
if(isset($_POST['submit']))
{
	$docspecialization=$_POST['Doctorspecialization'];
	if ($isDoctorSpecNumeric) {
		$docspecialization = (int)$docspecialization;
	}
	$docname=$_POST['docname'];
	$docaddress=$_POST['clinicaddress'];
	$docfees=$_POST['docfees'];
	$doccontactno=$_POST['doccontact'];
	$docemail=$_POST['docemail'];
	$sql=hms_query($con,"UPDATE doctors SET $doctorSpecColumn='$docspecialization',doctorName='$docname',address='$docaddress',docFees='$docfees',contactno='$doccontactno',docEmail='$docemail' WHERE id='$did'");
	if($sql)
	{
		$msg="Doctor Details updated Successfully";

	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Edit Doctor Details</title>


	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="../assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
	<?php
	$page_title = 'Admin | Edit Doctor Details';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h5 style="color: green; font-size:18px; ">
				<?php if($msg) { echo htmlentities($msg);}?> </h5>
				<div class="row margin-top-30">
					<div class="col-lg-8 col-md-12">
						<div class="panel panel-white">
							<div class="panel-heading">
								<h5 class="panel-title">Edit Doctor info</h5>
							</div>
							<div class="panel-body">
								<?php $sql=hms_query($con,"select * from doctors where id='$did'");
								while($data=hms_fetch_array($sql))
								{
									?>
									<h4><?php echo htmlentities($data['doctorName']);?>'s Profile</h4>
									<p><b>Profile Reg. Date: </b><?php echo htmlentities($data['creationDate']);?></p>
									<?php if($data['updationDate']){?>
										<p><b>Profile Last Updation Date: </b><?php echo htmlentities($data['updationDate']);?></p>
									<?php } ?>
									<hr />
									<form role="form" name="adddoc" method="post" onSubmit="return valid();">
										<div class="form-group">
											<label for="DoctorSpecialization">
												Doctor Specialization
											</label>
											<select name="Doctorspecialization" class="form-control" required="required">
												<?php
												$currentSpecValue = $data[$doctorSpecColumn];
												$currentSpecLabel = $currentSpecValue;
												if ($isDoctorSpecNumeric) {
													$currentSpecQuery = hms_query($con, "SELECT $specColumn AS specialization_name FROM $specTable WHERE id='".(int)$currentSpecValue."' LIMIT 1");
													if ($currentSpecQuery && ($currentSpecRow = hms_fetch_assoc($currentSpecQuery))) {
														$currentSpecLabel = $currentSpecRow['specialization_name'];
													}
												}
												?>
												<option value="<?php echo htmlentities($currentSpecValue);?>"><?php echo htmlentities($currentSpecLabel);?></option>
													<?php $ret=hms_query($con,"SELECT id, $specColumn AS specialization_name FROM $specTable ORDER BY $specColumn ASC");
													while($row=hms_fetch_array($ret))
													{
														$optionValue = $isDoctorSpecNumeric ? $row['id'] : $row['specialization_name'];
														?>
														<option value="<?php echo htmlentities($optionValue);?>">
															<?php echo htmlentities($row['specialization_name']);?>
														</option>
													<?php } ?>

												</select>
											</div>

											<div class="form-group">
												<label for="doctorname">
													Doctor Name
												</label>
												<input type="text" name="docname" class="form-control" value="<?php echo htmlentities($data['doctorName']);?>" >
											</div>


											<div class="form-group">
												<label for="address">
													Doctor Clinic Address
												</label>
												<textarea name="clinicaddress" class="form-control"><?php echo htmlentities($data['address']);?></textarea>
											</div>
											<div class="form-group">
												<label for="fess">
													Doctor Consultancy Fees
												</label>
												<input type="text" name="docfees" class="form-control" required="required"  value="<?php echo htmlentities($data['docFees']);?>" >
											</div>

											<div class="form-group">
												<label for="fess">
													Doctor Contact no
												</label>
												<input type="text" name="doccontact" class="form-control" required="required"  value="<?php echo htmlentities($data['contactno']);?>">
											</div>

											<div class="form-group">
												<label for="fess">
													Doctor Email
												</label>
												<input type="email" name="docemail" class="form-control"  readonly="readonly"  value="<?php echo htmlentities($data['docEmail']);?>">
											</div>




										<?php } ?>


										<button type="submit" name="submit" class="btn btn-o btn-primary">
											Update
										</button>
									</form>
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

			<script src="../vendors/jquery/dist/jquery.min.js"></script>

			<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

			<script src="../vendors/fastclick/lib/fastclick.js"></script>

			<script src="../vendors/nprogress/nprogress.js"></script>

			<script src="../vendors/Chart.js/dist/Chart.min.js"></script>

			<script src="../vendors/gauge.js/dist/gauge.min.js"></script>

			<script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>

			<script src="../vendors/iCheck/icheck.min.js"></script>

			<script src="../vendors/skycons/skycons.js"></script>

			<script src="../vendors/Flot/jquery.flot.js"></script>
			<script src="../vendors/Flot/jquery.flot.pie.js"></script>
			<script src="../vendors/Flot/jquery.flot.time.js"></script>
			<script src="../vendors/Flot/jquery.flot.stack.js"></script>
			<script src="../vendors/Flot/jquery.flot.resize.js"></script>

			<script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
			<script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
			<script src="../vendors/flot.curvedlines/curvedLines.js"></script>

			<script src="../vendors/DateJS/build/date.js"></script>

			<script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
			<script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
			<script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>

			<script src="../vendors/moment/min/moment.min.js"></script>
			<script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

			<script src="../assets/js/custom.min.js"></script>
		</body>