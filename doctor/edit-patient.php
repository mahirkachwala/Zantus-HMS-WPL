<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function tableExists($con, $tableName) {
	$check = hms_query($con, "SHOW TABLES LIKE '" . hms_escape($con, $tableName) . "'");
	return ($check && hms_num_rows($check) > 0);
}

$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);
$patientId = (int)($_GET['editid'] ?? 0);
$usePatientsTable = tableExists($con, 'patients');

if(isset($_POST['submit']) && $patientId > 0)
{
	$patname = hms_escape($con, trim($_POST['patname'] ?? ''));
	$patcontact = hms_escape($con, trim($_POST['patcontact'] ?? ''));
	$patemail = hms_escape($con, trim($_POST['patemail'] ?? ''));
	$gender = hms_escape($con, trim($_POST['gender'] ?? ''));
	$pataddress = hms_escape($con, trim($_POST['pataddress'] ?? ''));
	$patage = (int)($_POST['patage'] ?? 0);

	if ($usePatientsTable) {
		$sql = hms_query($con, "UPDATE patients SET patientName='$patname', patientPhone='$patcontact', patientEmail='$patemail', patientGender='$gender', patientAddress='$pataddress', patientAge='$patage', updatedAt=NOW() WHERE id='$patientId' AND doctorId='$doctorId'");
	} else {
		$sql = hms_query($con, "UPDATE tblpatient SET PatientName='$patname', PatientContno='$patcontact', PatientEmail='$patemail', PatientGender='$gender', PatientAdd='$pataddress', PatientAge='$patage', UpdationDate=NOW() WHERE ID='$patientId' AND Docid='$doctorId'");
	}

	if($sql)
	{
		$_SESSION['msg'] = 'Patient info updated successfully.';
		header('location:manage-patient.php');
		exit();
	}
}

$patient = null;
if ($patientId > 0) {
	if ($usePatientsTable) {
		$ret = hms_query($con, "SELECT * FROM patients WHERE id='$patientId' AND doctorId='$doctorId' LIMIT 1");
	} else {
		$ret = hms_query($con, "SELECT * FROM tblpatient WHERE ID='$patientId' AND Docid='$doctorId' LIMIT 1");
	}
	if ($ret) {
		$patient = hms_fetch_array($ret);
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Add Patient</title>


	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="nav-md">
	<?php
	$page_title = 'Patient | Edit Patient';
	$x_content = true;
	?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">
			<div class="row margin-top-30">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-heading">
							<h5 class="panel-title">Edit Patient</h5>
						</div>
						<div class="panel-body">
							<?php if(!$patient): ?>
								<div class="alert alert-warning">Patient record not found.</div>
								<a href="manage-patient.php" class="btn btn-default">Back</a>
							<?php else: ?>
							<form role="form" name="" method="post">
									<div class="form-group">
										<label for="doctorname">
											Patient Name
										</label>
										<input type="text" name="patname" class="form-control"  value="<?php  echo htmlentities($patient['patientName'] ?? $patient['PatientName'] ?? '');?>" required="true">
									</div>
									<div class="form-group">
										<label for="fess">
											Patient Contact no
										</label>
										<input type="text" name="patcontact" class="form-control"  value="<?php  echo htmlentities($patient['patientPhone'] ?? $patient['PatientContno'] ?? '');?>" required="true" maxlength="10" pattern="[0-9]+">
									</div>
									<div class="form-group">
										<label for="fess">
											Patient Email
										</label>
										<input type="email" id="patemail" name="patemail" class="form-control"  value="<?php  echo htmlentities($patient['patientEmail'] ?? $patient['PatientEmail'] ?? '');?>" readonly='true'>
										<span id="email-availability-status"></span>
									</div>
									<div class="form-group">
										<label class="control-label">Gender: </label>
										<?php $genderValue = strtolower((string)($patient['patientGender'] ?? $patient['PatientGender'] ?? '')); ?>
										<?php  if($genderValue === "female"){ ?>
											<input type="radio" name="gender" id="gender-female" value="female" checked="true">Female
											<input type="radio" name="gender" id="gender-male" value="male">Male
										<?php } else { ?>
											<label>
												<input type="radio" name="gender" id="gender-male" value="male" <?php echo $genderValue === 'male' ? 'checked="true"' : ''; ?>>Male
												<input type="radio" name="gender" id="gender-female" value="female" <?php echo $genderValue === 'female' ? 'checked="true"' : ''; ?>>Female
											</label>
										<?php } ?>
									</div>
									<div class="form-group">
										<label for="address">
											Patient Address
										</label>
										<textarea name="pataddress" class="form-control" required="true"><?php  echo htmlentities($patient['patientAddress'] ?? $patient['PatientAdd'] ?? '');?></textarea>
									</div>
									<div class="form-group">
										<label for="fess">
											Patient Age
										</label>
										<input type="text" name="patage" class="form-control"  value="<?php  echo htmlentities($patient['patientAge'] ?? $patient['PatientAge'] ?? '');?>" required="true">
									</div>

									<div class="form-group">
										<label for="fess">
											Creation Date
										</label>
										<input type="text" class="form-control"  value="<?php  echo htmlentities($patient['createdAt'] ?? $patient['CreationDate'] ?? '');?>" readonly='true'>
									</div>
								<button type="submit" name="submit" id="submit" class="btn btn-o btn-primary">
									Update
								</button>
								<a href="manage-patient.php" class="btn btn-default">Back</a>
							</form>
							<?php endif; ?>
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
</html>
