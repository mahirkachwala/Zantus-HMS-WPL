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
$patientsTableCheck = hms_query($con, "SHOW TABLES LIKE 'patients'");
$usePatientsTable = hms_num_rows($patientsTableCheck) > 0;

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
	$newPatientId = 0;
	$userId = 0;
	
	// If using new patients table
	if ($usePatientsTable) {
		// First check if user exists, if not create a temporary user
		$userCheck = hms_query($con, "SELECT id FROM users WHERE email='$patemail' LIMIT 1");
		if (hms_num_rows($userCheck) == 0) {
			// Create a temporary user for this patient (store hashed temporary password)
			$tempPassword = 'hospital2026';
			$passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);
			hms_query($con, "INSERT INTO users(fullName, email, password, regDate) VALUES('".hms_escape($con, $patname)."', '".hms_escape($con, $patemail)."', '".hms_escape($con, $passwordHash)."', NOW())");
			$userId = hms_last_insert_id($con);
		} else {
			$userRow = hms_fetch_array($userCheck);
			$userId = $userRow['id'];
		}
		
		// Insert into patients table
		$admissionDate = $isEmergency ? 'NOW()' : 'NULL';
		$sql = hms_query($con, "INSERT INTO patients(
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
		if ($sql) {
			$newPatientId = (int)hms_last_insert_id($con);
		}
	} else {
		// Fallback to old tblpatient table
		$sql = hms_query($con, "INSERT INTO tblpatient(
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
		// For consultancy patients added by doctor, create a same-day appointment
		// so doctor-side workflow buttons (check-in, prescription, payment, transfer) are available.
		if ($usePatientsTable && strtolower($patientType) === 'consultancy' && $userId > 0) {
			$tableCheck = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
			$appointmentTable = ($tableCheck && hms_num_rows($tableCheck) > 0) ? 'current_appointments' : 'appointment';

			$columnsCheck = hms_query($con, "SHOW COLUMNS FROM $appointmentTable");
			$columns = [];
			while ($col = hms_fetch_assoc($columnsCheck)) {
				$columns[] = $col['Field'];
			}

			$doctorSpecialization = '';
			$doctorSpecColumn = 'specilization';
			if (hms_num_rows(hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specialization'")) > 0) {
				$doctorSpecColumn = 'specialization';
			}
			$docRowQ = hms_query($con, "SELECT $doctorSpecColumn AS dspec FROM doctors WHERE id='".(int)$docid."' LIMIT 1");
			if ($docRowQ && ($docRow = hms_fetch_assoc($docRowQ))) {
				$doctorSpecialization = (string)($docRow['dspec'] ?? 'General');
				if (is_numeric($doctorSpecialization)) {
					$sp = hms_query($con, "SELECT specialization FROM doctorspecialization WHERE id='".(int)$doctorSpecialization."' LIMIT 1");
					if ($sp && ($spRow = hms_fetch_assoc($sp))) {
						$doctorSpecialization = (string)$spRow['specialization'];
					}
				}
			}

			$today = date('Y-m-d');
			$nowTime = date('H:i');
			$feesValue = 0;
			$feesQ = hms_query($con, "SELECT docFees FROM doctors WHERE id='".(int)$docid."' LIMIT 1");
			if ($feesQ && ($fr = hms_fetch_assoc($feesQ))) {
				$feesValue = (int)($fr['docFees'] ?? 0);
			}

			$insertCols = ['doctorSpecialization','doctorId','userId','consultancyFees','appointmentDate','appointmentTime','userStatus','doctorStatus'];
			$insertVals = [
				"'" . hms_escape($con, $doctorSpecialization) . "'",
				"'" . (int)$docid . "'",
				"'" . (int)$userId . "'",
				"'" . (int)$feesValue . "'",
				"'" . hms_escape($con, $today) . "'",
				"'" . hms_escape($con, $nowTime) . "'",
				"'1'",
				"'1'"
			];

			if (in_array('patientId', $columns)) {
				$insertCols[] = 'patientId';
				$insertVals[] = "'" . (int)$newPatientId . "'";
			}
			if (in_array('appointmentType', $columns)) {
				$insertCols[] = 'appointmentType';
				$insertVals[] = "'Consultancy'";
			}
			if (in_array('paymentOption', $columns)) {
				$insertCols[] = 'paymentOption';
				$insertVals[] = "'PayLater'";
			}
			if (in_array('paymentStatus', $columns)) {
				$insertCols[] = 'paymentStatus';
				$insertVals[] = "'Pay at Hospital'";
			}
			if (in_array('visitStatus', $columns)) {
				$insertCols[] = 'visitStatus';
				$insertVals[] = "'Scheduled'";
			}

			hms_query($con, "INSERT INTO $appointmentTable(" . implode(',', $insertCols) . ") VALUES(" . implode(',', $insertVals) . ")");
		}

		if (strtolower($patientType) === 'consultancy') {
			$_SESSION['msg'] = 'Consultancy patient added successfully and moved to Visit Management.';
			header('location:visit-management.php');
		} else {
			$_SESSION['msg'] = 'Patient added successfully.';
			header('location:add-patient.php');
		}
		exit();
	} else {
		$_SESSION['error'] = "Error adding patient: " . hms_last_error($con);
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