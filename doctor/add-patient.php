<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);
if ($doctorId <= 0) {
	header('location:index.php');
	exit();
}

// Check if new patients table exists
$patientsTableCheck = mysqli_query($con, "SHOW TABLES LIKE 'patients'");
$usePatientsTable = mysqli_num_rows($patientsTableCheck) > 0;

if(isset($_POST['submit'])) {
	$docid = $doctorId;
	$patname = $_POST['patname'];
	$patcontact = $_POST['patcontact'];
	$patemail = $_POST['patemail'];
	$gender = $_POST['gender'];
	$pataddress = $_POST['pataddress'];
	$patage = (int)$_POST['patage'];
	$patientType = $_POST['patientType'] ?? 'consultancy';
	$isEmergency = isset($_POST['isEmergency']) ? 1 : 0;
	
	// If using new patients table
	if ($usePatientsTable) {
		// First check if user exists, if not create a temporary user
		$userCheck = mysqli_query($con, "SELECT id FROM users WHERE email='$patemail' LIMIT 1");
		if (mysqli_num_rows($userCheck) == 0) {
			// Create a temporary user for this patient
			$tempPassword = md5('hospital2026');
			mysqli_query($con, "INSERT INTO users(fullName, email, password, regDate) VALUES('$patname', '$patemail', '$tempPassword', NOW())");
			$userId = mysqli_insert_id($con);
		} else {
			$userRow = mysqli_fetch_array($userCheck);
			$userId = $userRow['id'];
		}
		
		// Insert into patients table
		$admissionDate = $isEmergency ? 'NOW()' : 'NULL';
		$sql = mysqli_query($con, "INSERT INTO patients(
			userId, 
			doctorId, 
			patientName, 
			patientEmail, 
			patientPhone, 
			patientGender, 
			patientAge, 
			patientAddress, 
			patientType, 
			isEmergency,
			admissionDate,
			status
		) VALUES(
			'$userId', 
			'$docid', 
			'$patname', 
			'$patemail', 
			'$patcontact', 
			'$gender', 
			'$patage', 
			'$pataddress', 
			'$patientType', 
			'$isEmergency',
			".($isEmergency ? 'NOW()' : 'NULL').",
			'Active'
		)");
	} else {
		// Fallback to old tblpatient table
		$sql = mysqli_query($con, "INSERT INTO tblpatient(
			Docid,
			PatientName,
			PatientContno,
			PatientEmail,
			PatientGender,
			PatientAdd,
			PatientAge
		) VALUES(
			'$docid',
			'$patname',
			'$patcontact',
			'$patemail',
			'$gender',
			'$pataddress',
			'$patage'
		)");
	}
	
	if($sql) {
		$_SESSION['msg'] = 'Patient added successfully.';
		header('location:add-patient.php');
		exit();
	} else {
		$_SESSION['error'] = "Error adding patient: " . mysqli_error($con);
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
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
	</style>

	<script>
		function userAvailability() {
			$("#loaderIcon").show();
			jQuery.ajax({
				url: "check_availability.php",
				data:'email='+$("#patemail").val(),
				type: "POST",
				success:function(data){
					$("#user-availability-status1").html(data);
					$("#loaderIcon").hide();
				},
				error:function (){}
			});
		}
	</script>
</head>
<body class="nav-md">
	<?php
	$page_title = 'Patient | Add Patient';
	$x_content = true;
	?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Add Patient</h3>
			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-success"><?php echo htmlentities($_SESSION['msg']); ?></div>
				<?php $_SESSION['msg']=''; ?>
			<?php endif; ?>
			<?php if(!empty($_SESSION['error'])): ?>
				<div class="alert alert-danger"><?php echo htmlentities($_SESSION['error']); ?></div>
				<?php $_SESSION['error']=''; ?>
			<?php endif; ?>
			<div class="row margin-top-10">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-body">
							<form role="form" method="post">

								<div class="form-group">
									<label>Patient Name</label>
									<input type="text" name="patname" class="form-control" placeholder="Enter Patient Name" required="true">
								</div>

								<div class="form-group">
									<label>Patient Contact Number</label>
									<input type="text" name="patcontact" class="form-control" placeholder="Enter Patient Contact no" required="true" maxlength="10" pattern="[0-9]+">
								</div>

								<div class="form-group">
									<label>Patient Email</label>
									<input type="email" id="patemail" name="patemail" class="form-control" placeholder="Enter Patient Email id" required="true" onBlur="userAvailability()">
									<span id="user-availability-status1" style="font-size:12px;"></span>
								</div>

								<div class="form-group">
									<label>Gender</label>
									<div class="clip-radio radio-primary">
										<input type="radio" id="rg-female" name="gender" value="female" required>
										<label for="rg-female">Female</label>
										<input type="radio" id="rg-male" name="gender" value="male" required>
										<label for="rg-male">Male</label>
									</div>
								</div>

								<div class="form-group">
									<label>Patient Address</label>
									<textarea name="pataddress" class="form-control" placeholder="Enter Patient Address" required="true"></textarea>
								</div>

								<div class="form-group">
									<label>Patient Age</label>
									<input type="number" name="patage" class="form-control" placeholder="Enter Patient Age" required="true" min="1" max="150">
								</div>

								<!-- Patient Type Selection -->
								<hr style="margin:20px 0;">
								<h5 style="color:#1e3a8a; font-weight:600; margin-bottom:15px;">Patient Type</h5>

								<div class="form-group">
									<label>Select Patient Type</label>
									<select name="patientType" class="form-control" required>
										<option value="consultancy">Consultancy (Online/Scheduled Appointment)</option>
										<option value="admitted">Admitted (Hospital Stay)</option>
									</select>
									<small class="form-text text-muted" style="display:block; margin-top:5px;">
										• <strong>Consultancy:</strong> Patients with scheduled appointments<br>
										• <strong>Admitted:</strong> Patients admitted to hospital
									</small>
								</div>

								<div class="form-group">
									<label>
										<input type="checkbox" name="isEmergency" value="1">
										<span style="margin-left:8px;">Mark as Emergency Admission</span>
									</label>
									<small class="form-text text-muted" style="display:block; margin-top:5px;">Check if this is an emergency case requiring immediate admission</small>
								</div>

								<hr style="margin:20px 0;">
								<button type="submit" name="submit" id="submit" class="btn btn-primary">
									<i class="fa fa-plus"></i> Add Patient
								</button>
								<a href="manage-patient.php" class="btn btn-default">Cancel</a>
							</form>
						</div>
					</div>
				</div>
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