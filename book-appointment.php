<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

if (!isset($_SESSION['id'])) {
	header('location:index.php');
	exit();
}

// Determine which appointment table to use
$tableCheck = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
$useCurrentAppointments = hms_num_rows($tableCheck) > 0;
$appointmentTable = $useCurrentAppointments ? 'current_appointments' : 'appointment';

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

if(isset($_POST['submit'])) {
	$selectedSpecialization = $_POST['Doctorspecialization'];
	$specilization = $selectedSpecialization;
	if ($isDoctorSpecNumeric && !empty($specTable)) {
		$spQuery = hms_query($con, "SELECT $specColumn AS specialization_name FROM $specTable WHERE id='".(int)$selectedSpecialization."' LIMIT 1");
		if ($spQuery && ($spRow = hms_fetch_assoc($spQuery))) {
			$specilization = $spRow['specialization_name'];
		}
	}
	$doctorid = (int)$_POST['doctor'];
	$userid = $_SESSION['id'];
	$fees = (int)$_POST['fees'];
	$appdate = $_POST['appdate'];
	$time = $_POST['apptime'];
	$paymentOption = $_POST['paymentOption'] ?? 'BookOnly';
	$userstatus = 1;
	$docstatus = 1;
	$linkedPatientId = 0;

	// Create/find patient profile only when appointment is booked.
	$patientsTableCheck = hms_query($con, "SHOW TABLES LIKE 'patients'");
	$usePatientsTable = ($patientsTableCheck && hms_num_rows($patientsTableCheck) > 0);

	if ($usePatientsTable) {
		$patientsColumnsCheck = hms_query($con, "SHOW COLUMNS FROM patients");
		$patientsColumns = [];
		if ($patientsColumnsCheck) {
			while ($pc = hms_fetch_assoc($patientsColumnsCheck)) {
				$patientsColumns[] = $pc['Field'];
			}
		}

		$where = ["userId='" . (int)$userid . "'"];
		if (in_array('doctorId', $patientsColumns)) {
			$where[] = "doctorId='" . (int)$doctorid . "'";
		}
		if (in_array('patientType', $patientsColumns)) {
			$where[] = "LOWER(patientType)='consultancy'";
		}
		$existingPatientQ = hms_query(
			$con,
			"SELECT id FROM patients WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT 1"
		);
		if ($existingPatientQ && hms_num_rows($existingPatientQ) > 0) {
			$existingPatient = hms_fetch_assoc($existingPatientQ);
			$linkedPatientId = (int)($existingPatient['id'] ?? 0);
		} else {
			$userQ = hms_query($con, "SELECT fullName,email,gender,address FROM users WHERE id='" . (int)$userid . "' LIMIT 1");
			$userData = ($userQ && hms_num_rows($userQ) > 0) ? hms_fetch_assoc($userQ) : [];

			$insertPatientCols = [];
			$insertPatientVals = [];

			if (in_array('userId', $patientsColumns)) {
				$insertPatientCols[] = 'userId';
				$insertPatientVals[] = "'" . (int)$userid . "'";
			}
			if (in_array('doctorId', $patientsColumns)) {
				$insertPatientCols[] = 'doctorId';
				$insertPatientVals[] = "'" . (int)$doctorid . "'";
			}
			if (in_array('patientName', $patientsColumns)) {
				$insertPatientCols[] = 'patientName';
				$insertPatientVals[] = "'" . hms_escape($con, (string)($userData['fullName'] ?? '')) . "'";
			}
			if (in_array('patientEmail', $patientsColumns)) {
				$insertPatientCols[] = 'patientEmail';
				$insertPatientVals[] = "'" . hms_escape($con, (string)($userData['email'] ?? '')) . "'";
			}
			if (in_array('patientGender', $patientsColumns)) {
				$insertPatientCols[] = 'patientGender';
				$insertPatientVals[] = "'" . hms_escape($con, (string)($userData['gender'] ?? '')) . "'";
			}
			if (in_array('patientAddress', $patientsColumns)) {
				$insertPatientCols[] = 'patientAddress';
				$insertPatientVals[] = "'" . hms_escape($con, (string)($userData['address'] ?? '')) . "'";
			}
			if (in_array('patientType', $patientsColumns)) {
				$insertPatientCols[] = 'patientType';
				$insertPatientVals[] = "'consultancy'";
			}
			if (in_array('status', $patientsColumns)) {
				$insertPatientCols[] = 'status';
				$insertPatientVals[] = "'Active'";
			}
			if (in_array('isEmergency', $patientsColumns)) {
				$insertPatientCols[] = 'isEmergency';
				$insertPatientVals[] = "'0'";
			}

			if (!empty($insertPatientCols)) {
				$createPatientQ = hms_query(
					$con,
					"INSERT INTO patients(" . implode(',', $insertPatientCols) . ") VALUES(" . implode(',', $insertPatientVals) . ")"
				);
				if ($createPatientQ) {
					$linkedPatientId = (int)hms_last_insert_id($con);
				}
			}
		}
	} else {
		$tblPatientCheck = hms_query($con, "SHOW TABLES LIKE 'tblpatient'");
		$useTblPatient = ($tblPatientCheck && hms_num_rows($tblPatientCheck) > 0);
		if ($useTblPatient) {
			$userQ = hms_query($con, "SELECT fullName,email,gender,address FROM users WHERE id='" . (int)$userid . "' LIMIT 1");
			$userData = ($userQ && hms_num_rows($userQ) > 0) ? hms_fetch_assoc($userQ) : [];

			$legacyExistsQ = hms_query(
				$con,
				"SELECT ID FROM tblpatient WHERE Docid='" . (int)$doctorid . "' AND PatientEmail='" . hms_escape($con, (string)($userData['email'] ?? '')) . "' LIMIT 1"
			);
			if (!$legacyExistsQ || hms_num_rows($legacyExistsQ) === 0) {
				hms_query(
					$con,
					"INSERT INTO tblpatient(Docid,PatientName,PatientContno,PatientEmail,PatientGender,PatientAdd,PatientAge) VALUES('" .
					(int)$doctorid . "','" .
					hms_escape($con, (string)($userData['fullName'] ?? '')) . "',NULL,'" .
					hms_escape($con, (string)($userData['email'] ?? '')) . "','" .
					hms_escape($con, (string)($userData['gender'] ?? '')) . "','" .
					hms_escape($con, (string)($userData['address'] ?? '')) . "',NULL)"
				);
			}
		}
	}
	
	// Check if using new schema
	$columnsCheck = hms_query($con, "SHOW COLUMNS FROM $appointmentTable");
	$columns = [];
	while ($col = hms_fetch_assoc($columnsCheck)) {
		$columns[] = $col['Field'];
	}
	
	$hasPaymentOption = in_array('paymentOption', $columns);
	$hasAppointmentType = in_array('appointmentType', $columns);
	$hasPaymentStatus = in_array('paymentStatus', $columns);
	$hasPatientId = in_array('patientId', $columns);
	
	$insertCols = "doctorSpecialization,doctorId,userId,consultancyFees,appointmentDate,appointmentTime,userStatus,doctorStatus";
	$insertVals = "'$specilization','$doctorid','$userid','$fees','$appdate','$time','$userstatus','$docstatus'";

	if ($hasPatientId && $linkedPatientId > 0) {
		$insertCols .= ",patientId";
		$insertVals .= ",'$linkedPatientId'";
	}
	
	if ($hasAppointmentType) {
		$insertCols .= ",appointmentType";
		$insertVals .= ",'Online'";
	}
	
	if ($hasPaymentOption) {
		$insertCols .= ",paymentOption";
		$insertVals .= ",'$paymentOption'";
	}
	
	// Set payment status based on option
	if ($hasPaymentStatus) {
		$insertCols .= ",paymentStatus";
		if ($paymentOption === 'PayNow') {
			$insertVals .= ",'Pending'";
		} elseif ($paymentOption === 'PayLater') {
			$insertVals .= ",'Pay at Hospital'";
		} else {
			$insertVals .= ",'Pending'";
		}
	}
	
	$query = hms_query($con, "INSERT INTO $appointmentTable($insertCols) VALUES($insertVals)");
	
	if($query) {
		$appointmentId = hms_last_insert_id($con);
		
		if ($paymentOption === 'PayNow') {
			$_SESSION['msg1'] = "Appointment created. Proceed to payment.";
			header("Location: pay-fees.php?appointment_id=" . $appointmentId);
		} else {
			$_SESSION['msg1'] = "Your appointment was booked successfully.";
			header("Location: appointments.php?msg=booked");
		}
		exit();
	} else {
		$_SESSION['error'] = "Error booking appointment: " . hms_last_error($con);
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Book Appointment</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.payment-option-card {
			border: 2px solid #e6ebf5;
			border-radius: 8px;
			padding: 15px;
			margin-bottom: 10px;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		.payment-option-card:hover {
			border-color: #337ab7;
			background: #f7fbff;
		}
		.payment-option-card input[type="radio"] {
			cursor: pointer;
		}
		.payment-option-card.selected {
			border-color: #337ab7;
			background: #f7fbff;
		}
	</style>
	<script>
		function getdoctor(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data:'specializationid='+val,
				success: function(data){
					$("#doctor").html(data);
				}
			});
		}
		function getfee(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data:'docid='+val,
				success: function(data){
					$("#fees").html(data);
					var selectedFee = $("#fees option:selected").val() || '';
					$("#feesDisplay").text(selectedFee ? ('₹ ' + selectedFee) : '-');
				}
			});
		}
		function selectPaymentOption(option) {
			document.querySelectorAll('.payment-option-card').forEach(el => {
				el.classList.remove('selected');
			});
			document.getElementById('paymentOption-' + option).parentElement.classList.add('selected');
			document.getElementById('paymentOption-' + option).checked = true;
		}
	</script>
</head>
<body class="nav-md">
	<?php
	$page_title = 'User | Book Appointment';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<div class="row margin-top-30">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-heading">
							<h5 class="panel-title">Book Your Appointment</h5>
						</div>
						<div class="panel-body">
							<?php if(!empty($_SESSION['msg1'])): ?>
								<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg1']);?></div>
								<?php $_SESSION['msg1']=""; ?>
							<?php endif; ?>
							<?php if(!empty($_SESSION['error'])): ?>
								<div class="alert alert-danger"><?php echo htmlentities($_SESSION['error']);?></div>
								<?php $_SESSION['error']=""; ?>
							<?php endif; ?>
							<form role="form" name="book" method="post">
								<div class="form-group">
									<label for="DoctorSpecialization">Doctor Specialization</label>
									<select name="Doctorspecialization" class="form-control" onChange="getdoctor(this.value);" required="required">
										<option value="">Select Specialization</option>
										<?php 
										$ret=hms_query($con,"SELECT MIN(id) AS id, $specColumn AS specialization_name FROM $specTable GROUP BY $specColumn ORDER BY $specColumn ASC");
										while($row=hms_fetch_array($ret)) {
											$optionValue = $isDoctorSpecNumeric ? $row['id'] : $row['specialization_name'];
											echo "<option value='".htmlentities($optionValue)."'>".htmlentities($row['specialization_name'])."</option>";
										}
										?>
									</select>
								</div>
								
								<div class="form-group">
									<label for="doctor">Doctor</label>
									<select name="doctor" class="form-control" id="doctor" onChange="getfee(this.value);" required="required">
										<option value="">Select Doctor</option>
									</select>
								</div>
								
								<div class="form-group">
									<label for="consultancyfees">Consultancy Fees</label>
									<div style="display:flex; align-items:center; gap:10px;">
										<select name="fees" class="form-control" id="fees" readonly style="flex:1;">
											<option>Select Doctor First</option>
										</select>
										<div style="font-size:18px; font-weight:700; color:#1e40af; min-width:80px; text-align:right;">
											<span id="feesDisplay">-</span>
										</div>
									</div>
								</div>
								
								<div class="form-group">
									<label for="AppointmentDate">Date</label>
									<input type="date" class="form-control" name="appdate" min="<?php echo date('Y-m-d'); ?>" required="required">
								</div>
								
								<div class="form-group">
									<label for="Appointmenttime">Time</label>
									<input type="time" class="form-control" name="apptime" id="time" required="required" placeholder="eg: 10:00 AM">
								</div>

								<!-- Payment Options Section -->
								<hr style="margin:20px 0;">
								<h5 style="color:#1e3a8a; font-weight:600; margin-bottom:15px;">Payment Method</h5>
								
								<div class="payment-option-card selected" onclick="selectPaymentOption('BookOnly')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-BookOnly" name="paymentOption" value="BookOnly" checked>
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-BookOnly" style="cursor:pointer; margin:0; font-weight:600;">Book Appointment</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Book now, pay from your appointments page later</p>
										</div>
									</div>
								</div>
								
								<div class="payment-option-card" onclick="selectPaymentOption('PayNow')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-PayNow" name="paymentOption" value="PayNow">
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-PayNow" style="cursor:pointer; margin:0; font-weight:600;">Pay Now</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Complete payment immediately to confirm your appointment</p>
										</div>
									</div>
								</div>
								
								<div class="payment-option-card" onclick="selectPaymentOption('PayLater')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-PayLater" name="paymentOption" value="PayLater">
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-PayLater" style="cursor:pointer; margin:0; font-weight:600;">Pay at Hospital</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Pay directly at the hospital during your visit</p>
										</div>
									</div>
								</div>

								<hr style="margin:20px 0;">
								<button type="submit" name="submit" class="btn btn-o btn-primary" style="min-width:150px;">
									<i class="fa fa-check"></i> Continue
								</button>
								<a href="appointments.php" class="btn btn-o btn-default">Cancel</a>
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
