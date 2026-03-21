<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function ensureAppointmentColumns($con) {
	$requiredColumns = [
		"visitStatus" => "ALTER TABLE appointment ADD COLUMN visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled' AFTER doctorStatus",
		"checkOutTime" => "ALTER TABLE appointment ADD COLUMN checkOutTime datetime DEFAULT NULL AFTER checkInTime",
		"prescription" => "ALTER TABLE appointment ADD COLUMN prescription mediumtext DEFAULT NULL AFTER checkOutTime"
	];

	foreach ($requiredColumns as $columnName => $ddl) {
		$check = mysqli_query($con, "SHOW COLUMNS FROM appointment LIKE '" . $columnName . "'");
		if ($check && mysqli_num_rows($check) === 0) {
			mysqli_query($con, $ddl);
		}
	}
}

function ensurePrescriptionTables($con) {
	mysqli_query($con, "CREATE TABLE IF NOT EXISTS prescriptions (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		patient_id INT NOT NULL,
		doctor_id INT NOT NULL,
		appointment_id INT NOT NULL,
		temperature VARCHAR(20) DEFAULT NULL,
		blood_pressure VARCHAR(30) DEFAULT NULL,
		pulse VARCHAR(20) DEFAULT NULL,
		weight VARCHAR(20) DEFAULT NULL,
		symptoms TEXT DEFAULT NULL,
		diagnosis TEXT DEFAULT NULL,
		tests TEXT DEFAULT NULL,
		notes TEXT DEFAULT NULL,
		next_visit_date DATE DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_appointment_id (appointment_id),
		INDEX idx_patient_id (patient_id),
		INDEX idx_doctor_id (doctor_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	mysqli_query($con, "CREATE TABLE IF NOT EXISTS prescription_medicines (
		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
		prescription_id INT NOT NULL,
		medicine_name VARCHAR(255) NOT NULL,
		dosage VARCHAR(100) DEFAULT NULL,
		frequency VARCHAR(100) DEFAULT NULL,
		duration VARCHAR(100) DEFAULT NULL,
		instructions VARCHAR(255) DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_prescription_id (prescription_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureAppointmentColumns($con);
ensurePrescriptionTables($con);

$doctorId = (int)($_SESSION['id'] ?? 0);
$appointmentId = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
	$_SESSION['msg'] = 'Invalid appointment selected.';
	header('location:visit-management.php');
	exit();
}

$appointmentSql = mysqli_query($con, "SELECT appointment.*, users.fullName FROM appointment JOIN users ON users.id=appointment.userId WHERE appointment.id='$appointmentId' AND appointment.doctorId='$doctorId' LIMIT 1");
$appointment = ($appointmentSql) ? mysqli_fetch_array($appointmentSql) : null;

if (!$appointment) {
	$_SESSION['msg'] = 'Appointment not found for this doctor.';
	header('location:visit-management.php');
	exit();
}

if (($appointment['userStatus'] ?? 0) != 1 || ($appointment['doctorStatus'] ?? 0) != 1) {
	$_SESSION['msg'] = 'This appointment is not active.';
	header('location:visit-management.php');
	exit();
}

if (($appointment['visitStatus'] ?? 'Scheduled') !== 'Checked In') {
	$_SESSION['msg'] = 'Please check in the patient before adding prescription.';
	header('location:visit-management.php');
	exit();
}

if (isset($_POST['submit_prescription'])) {
	$temperature = mysqli_real_escape_string($con, trim($_POST['temperature'] ?? ''));
	$bloodPressure = mysqli_real_escape_string($con, trim($_POST['blood_pressure'] ?? ''));
	$pulse = mysqli_real_escape_string($con, trim($_POST['pulse'] ?? ''));
	$weight = mysqli_real_escape_string($con, trim($_POST['weight'] ?? ''));
	$symptoms = mysqli_real_escape_string($con, trim($_POST['symptoms'] ?? ''));
	$diagnosis = mysqli_real_escape_string($con, trim($_POST['diagnosis'] ?? ''));
	$notes = mysqli_real_escape_string($con, trim($_POST['notes'] ?? ''));
	$nextVisitDate = trim($_POST['next_visit_date'] ?? '');
	$nextVisitDateValue = ($nextVisitDate !== '') ? "'" . mysqli_real_escape_string($con, $nextVisitDate) . "'" : "NULL";

	$testList = $_POST['tests'] ?? [];
	if (!is_array($testList)) {
		$testList = [];
	}
	$otherTest = trim($_POST['tests_other'] ?? '');
	if ($otherTest !== '') {
		$testList[] = $otherTest;
	}
	$testList = array_filter(array_map('trim', $testList));
	$tests = mysqli_real_escape_string($con, implode(', ', $testList));

	if ($diagnosis === '') {
		$_SESSION['msg'] = 'Diagnosis is required.';
	} else {
		$insertPrescription = mysqli_query($con, "INSERT INTO prescriptions(patient_id, doctor_id, appointment_id, temperature, blood_pressure, pulse, weight, symptoms, diagnosis, tests, notes, next_visit_date) VALUES('" . (int)$appointment['userId'] . "', '$doctorId', '$appointmentId', '$temperature', '$bloodPressure', '$pulse', '$weight', '$symptoms', '$diagnosis', '$tests', '$notes', $nextVisitDateValue)");

		if ($insertPrescription) {
			$prescriptionId = (int)mysqli_insert_id($con);
			$medicines = $_POST['medicine_name'] ?? [];
			$dosages = $_POST['dosage'] ?? [];
			$frequencies = $_POST['frequency'] ?? [];
			$durations = $_POST['duration'] ?? [];
			$instructions = $_POST['instructions'] ?? [];
			$medicineCount = 0;

			for ($i = 0; $i < count($medicines); $i++) {
				$medicineName = trim($medicines[$i] ?? '');
				if ($medicineName === '') {
					continue;
				}
				$dosage = mysqli_real_escape_string($con, trim($dosages[$i] ?? ''));
				$frequency = mysqli_real_escape_string($con, trim($frequencies[$i] ?? ''));
				$duration = mysqli_real_escape_string($con, trim($durations[$i] ?? ''));
				$instruction = mysqli_real_escape_string($con, trim($instructions[$i] ?? ''));
				$medicineEscaped = mysqli_real_escape_string($con, $medicineName);
				mysqli_query($con, "INSERT INTO prescription_medicines(prescription_id, medicine_name, dosage, frequency, duration, instructions) VALUES('$prescriptionId', '$medicineEscaped', '$dosage', '$frequency', '$duration', '$instruction')");
				$medicineCount++;
			}

			$summary = 'Diagnosis: ' . trim($_POST['diagnosis'] ?? '');
			$summary .= ' | Medicines: ' . $medicineCount;
			if ($nextVisitDate !== '') {
				$summary .= ' | Follow-up: ' . $nextVisitDate;
			}
			$summaryEscaped = mysqli_real_escape_string($con, $summary);
			mysqli_query($con, "UPDATE appointment SET visitStatus='Completed', checkOutTime=NOW(), prescription='$summaryEscaped' WHERE id='$appointmentId' AND doctorId='$doctorId'");

			$_SESSION['msg'] = 'Prescription added and visit completed successfully.';
			header('location:visit-management.php');
			exit();
		}
		$_SESSION['msg'] = 'Unable to save prescription. Please try again.';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Add Prescription</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.css" rel="stylesheet">
	<style>
		.card-box {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 10px;
			padding: 16px;
			margin-bottom: 14px;
		}
		.section-title {
			font-size: 16px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 10px;
		}
	</style>
</head>
<body class="nav-md">
<?php
$page_title = 'Doctor | Add Prescription';
$x_content = true;
include('include/header.php');
?>
<div class="row">
	<div class="col-md-12">
		<?php if(!empty($_SESSION['msg'])): ?>
			<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']); ?></div>
			<?php $_SESSION['msg'] = ''; ?>
		<?php endif; ?>

		<form method="post">
			<input type="hidden" name="appointment_id" value="<?php echo (int)$appointmentId; ?>">

			<div class="card-box">
				<div class="section-title">Patient & Visit Info</div>
				<div class="row">
					<div class="col-md-4"><strong>Patient Name:</strong> <?php echo htmlentities($appointment['fullName']); ?></div>
					<div class="col-md-2"><strong>Patient ID:</strong> <?php echo (int)$appointment['userId']; ?></div>
					<div class="col-md-3"><strong>Doctor Name:</strong> <?php echo htmlentities($_SESSION['doctorName'] ?? 'Doctor'); ?></div>
					<div class="col-md-3"><strong>Date:</strong> <?php echo date('Y-m-d'); ?></div>
				</div>
			</div>

			<div class="card-box">
				<div class="section-title">Vitals</div>
				<div class="row">
					<div class="col-md-3 form-group">
						<label>Temperature</label>
						<input type="text" name="temperature" class="form-control" placeholder="e.g. 98.6°F">
					</div>
					<div class="col-md-3 form-group">
						<label>Blood Pressure</label>
						<input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80">
					</div>
					<div class="col-md-3 form-group">
						<label>Pulse</label>
						<input type="text" name="pulse" class="form-control" placeholder="e.g. 78 bpm">
					</div>
					<div class="col-md-3 form-group">
						<label>Weight</label>
						<input type="text" name="weight" class="form-control" placeholder="e.g. 70 kg">
					</div>
				</div>
			</div>

			<div class="card-box">
				<div class="section-title">Symptoms & Diagnosis</div>
				<div class="form-group">
					<label>Symptoms</label>
					<textarea name="symptoms" class="form-control" rows="3" placeholder="Patient symptoms"></textarea>
				</div>
				<div class="form-group">
					<label>Diagnosis *</label>
					<textarea name="diagnosis" class="form-control" rows="3" required placeholder="Clinical diagnosis"></textarea>
				</div>
			</div>

			<div class="card-box">
				<div class="section-title">Medicines</div>
				<table class="table table-bordered" id="medicineTable">
					<thead>
						<tr>
							<th>Medicine Name</th>
							<th>Dosage</th>
							<th>Frequency</th>
							<th>Duration</th>
							<th>Instructions</th>
							<th width="80">Action</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><input type="text" name="medicine_name[]" class="form-control" placeholder="Paracetamol" required></td>
							<td><input type="text" name="dosage[]" class="form-control" placeholder="500mg"></td>
							<td><input type="text" name="frequency[]" class="form-control" placeholder="1-1-1"></td>
							<td><input type="text" name="duration[]" class="form-control" placeholder="5 days"></td>
							<td><input type="text" name="instructions[]" class="form-control" placeholder="After food"></td>
							<td><button type="button" class="btn btn-cancel btn-sm" onclick="removeRow(this)">Remove</button></td>
						</tr>
					</tbody>
				</table>
				<button type="button" class="btn btn-primary btn-sm" onclick="addMedicineRow()">Add Medicine</button>
			</div>

			<div class="card-box">
				<div class="section-title">Tests, Notes & Follow-up</div>
				<div class="form-group">
					<label>Recommended Tests</label><br>
					<label style="margin-right:12px;"><input type="checkbox" name="tests[]" value="Blood Test"> Blood Test</label>
					<label style="margin-right:12px;"><input type="checkbox" name="tests[]" value="X-Ray"> X-Ray</label>
					<label style="margin-right:12px;"><input type="checkbox" name="tests[]" value="MRI"> MRI</label>
					<input type="text" name="tests_other" class="form-control" style="margin-top:8px;" placeholder="Other test (optional)">
				</div>
				<div class="form-group">
					<label>Doctor Notes / Advice</label>
					<textarea name="notes" class="form-control" rows="3" placeholder="General advice and lifestyle instructions"></textarea>
				</div>
				<div class="form-group" style="max-width:260px;">
					<label>Next Visit Date</label>
					<input type="date" name="next_visit_date" class="form-control">
				</div>
			</div>

			<button type="submit" name="submit_prescription" class="btn btn-primary">Save Prescription & Complete Visit</button>
			<a href="visit-management.php" class="btn btn-cancel">Cancel</a>
		</form>
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
<script>
function addMedicineRow() {
	var row = '<tr>' +
		'<td><input type="text" name="medicine_name[]" class="form-control" placeholder="Medicine name"></td>' +
		'<td><input type="text" name="dosage[]" class="form-control" placeholder="Dosage"></td>' +
		'<td><input type="text" name="frequency[]" class="form-control" placeholder="Frequency"></td>' +
		'<td><input type="text" name="duration[]" class="form-control" placeholder="Duration"></td>' +
		'<td><input type="text" name="instructions[]" class="form-control" placeholder="Instructions"></td>' +
		'<td><button type="button" class="btn btn-cancel btn-sm" onclick="removeRow(this)">Remove</button></td>' +
	'</tr>';
	document.querySelector('#medicineTable tbody').insertAdjacentHTML('beforeend', row);
}

function removeRow(btn) {
	var tbody = document.querySelector('#medicineTable tbody');
	if (tbody.rows.length > 1) {
		btn.closest('tr').remove();
	}
}
</script>
</body>
</html>
