<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function ensureAppointmentColumns($con, $table) {
	// Server-side schema guard for appointment workflow fields.
	$requiredColumns = [
		"visitStatus" => "ALTER TABLE $table ADD COLUMN visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled' AFTER doctorStatus",
		"checkInTime" => "ALTER TABLE $table ADD COLUMN checkInTime datetime DEFAULT NULL AFTER visitStatus",
		"checkOutTime" => "ALTER TABLE $table ADD COLUMN checkOutTime datetime DEFAULT NULL AFTER checkInTime",
		"prescription" => "ALTER TABLE $table ADD COLUMN prescription mediumtext DEFAULT NULL AFTER checkOutTime",
		"paymentStatus" => "ALTER TABLE $table ADD COLUMN paymentStatus varchar(20) NOT NULL DEFAULT 'Pending' AFTER prescription",
		"paymentRef" => "ALTER TABLE $table ADD COLUMN paymentRef varchar(64) DEFAULT NULL AFTER paymentStatus",
		"paidAt" => "ALTER TABLE $table ADD COLUMN paidAt datetime DEFAULT NULL AFTER paymentRef"
	];

	foreach ($requiredColumns as $columnName => $ddl) {
		$check = hms_query($con, "SHOW COLUMNS FROM $table LIKE '" . $columnName . "'");
		if ($check && hms_num_rows($check) === 0) {
			hms_query($con, $ddl);
		}
	}
}

function appointmentTableName($con) {
	$check = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
	return ($check && hms_num_rows($check) > 0) ? 'current_appointments' : 'appointment';
}

function ensurePrescriptionTables($con) {
	hms_query($con, "CREATE TABLE IF NOT EXISTS prescriptions (
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
		medicines LONGTEXT DEFAULT NULL,
		next_visit_date DATE DEFAULT NULL,
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		INDEX idx_appointment_id (appointment_id),
		INDEX idx_patient_id (patient_id),
		INDEX idx_doctor_id (doctor_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

	$medicinesColumnCheck = hms_query($con, "SHOW COLUMNS FROM prescriptions LIKE 'medicines'");
	if (!$medicinesColumnCheck || hms_num_rows($medicinesColumnCheck) === 0) {
		hms_query($con, "ALTER TABLE prescriptions ADD COLUMN medicines LONGTEXT DEFAULT NULL AFTER notes");
	}
}

ensureAppointmentColumns($con, appointmentTableName($con));
ensurePrescriptionTables($con);

$appointmentTable = appointmentTableName($con);

$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);
$appointmentId = (int)($_GET['appointment_id'] ?? $_GET['id'] ?? $_GET['aid'] ?? $_POST['appointment_id'] ?? $_POST['id'] ?? $_POST['aid'] ?? 0);
$candidateAppointments = [];
$showAppointmentChooser = false;

if ($appointmentId <= 0) {
	$fallbackSql = hms_query(
		$con,
		"SELECT $appointmentTable.id, users.fullName, $appointmentTable.appointmentDate, $appointmentTable.appointmentTime
		FROM $appointmentTable
		JOIN users ON users.id=$appointmentTable.userId
		WHERE $appointmentTable.doctorId='$doctorId'
			AND $appointmentTable.userStatus='1'
			AND $appointmentTable.doctorStatus='1'
			AND COALESCE($appointmentTable.visitStatus, 'Scheduled')='Checked In'
		ORDER BY $appointmentTable.id DESC
		LIMIT 10"
	);
	if ($fallbackSql) {
		while ($fallbackRow = hms_fetch_assoc($fallbackSql)) {
			$candidateAppointments[] = $fallbackRow;
		}
	}

	if (count($candidateAppointments) === 1) {
		$appointmentId = (int)($candidateAppointments[0]['id'] ?? 0);
	} elseif (count($candidateAppointments) > 1) {
		$showAppointmentChooser = true;
	} else {
		$fallbackSql = hms_query(
			$con,
			"SELECT id FROM $appointmentTable
		WHERE doctorId='$doctorId'
			AND userStatus='1'
			AND doctorStatus='1'
			AND COALESCE(visitStatus, 'Scheduled')='Checked In'
		ORDER BY id DESC
		LIMIT 2"
		);
		if ($fallbackSql && hms_num_rows($fallbackSql) === 1) {
			$fallbackRow = hms_fetch_assoc($fallbackSql);
			$appointmentId = (int)($fallbackRow['id'] ?? 0);
		}
	}

	if ($appointmentId <= 0 && !$showAppointmentChooser) {
		$_SESSION['msg'] = 'Invalid appointment selected.';
		header('location:visit-management.php');
		exit();
	}
}

$appointment = null;
if (!$showAppointmentChooser) {
	$appointmentSql = hms_query($con, "SELECT $appointmentTable.*, users.fullName FROM $appointmentTable JOIN users ON users.id=$appointmentTable.userId WHERE $appointmentTable.id='$appointmentId' AND $appointmentTable.doctorId='$doctorId' LIMIT 1");
	$appointment = ($appointmentSql) ? hms_fetch_array($appointmentSql) : null;
}

if (!$showAppointmentChooser && !$appointment) {
	$_SESSION['msg'] = 'Appointment not found for this doctor.';
	header('location:visit-management.php');
	exit();
}

if (!$showAppointmentChooser && (($appointment['userStatus'] ?? 0) != 1 || ($appointment['doctorStatus'] ?? 0) != 1)) {
	$_SESSION['msg'] = 'This appointment is not active.';
	header('location:visit-management.php');
	exit();
}

if (!$showAppointmentChooser && (($appointment['visitStatus'] ?? 'Scheduled') !== 'Checked In')) {
	$_SESSION['msg'] = 'Please check in the patient before adding prescription.';
	header('location:visit-management.php');
	exit();
}

$isPaid = !$showAppointmentChooser && (
	in_array(strtolower((string)($appointment['paymentStatus'] ?? '')), ['paid', 'paid at hospital'], true)
	|| !empty($appointment['paymentRef'])
	|| !empty($appointment['paidAt'])
);
if (!$showAppointmentChooser && !$isPaid) {
	$_SESSION['msg'] = 'Payment must be received before adding prescription.';
	header('location:visit-management.php');
	exit();
}

if (isset($_POST['submit_prescription'])) {
	$temperature = hms_escape($con, trim($_POST['temperature'] ?? ''));
	$bloodPressure = hms_escape($con, trim($_POST['blood_pressure'] ?? ''));
	$pulse = hms_escape($con, trim($_POST['pulse'] ?? ''));
	$weight = hms_escape($con, trim($_POST['weight'] ?? ''));
	$symptoms = hms_escape($con, trim($_POST['symptoms'] ?? ''));
	$diagnosis = hms_escape($con, trim($_POST['diagnosis'] ?? ''));
	$notes = hms_escape($con, trim($_POST['notes'] ?? ''));
	$nextVisitDate = trim($_POST['next_visit_date'] ?? '');
	$nextVisitDateValue = ($nextVisitDate !== '') ? "'" . hms_escape($con, $nextVisitDate) . "'" : "NULL";

	$testList = $_POST['tests'] ?? [];
	if (!is_array($testList)) {
		$testList = [];
	}
	$otherTest = trim($_POST['tests_other'] ?? '');
	if ($otherTest !== '') {
		$testList[] = $otherTest;
	}
	$testList = array_filter(array_map('trim', $testList));
	$tests = hms_escape($con, implode(', ', $testList));

	if ($diagnosis === '') {
		$_SESSION['msg'] = 'Diagnosis is required.';
	} else {
		$medicines = $_POST['medicine_name'] ?? [];
		$dosages = $_POST['dosage'] ?? [];
		$frequencies = $_POST['frequency'] ?? [];
		$durations = $_POST['duration'] ?? [];
		$instructions = $_POST['instructions'] ?? [];
		$medicineCount = 0;
		$medicineLines = [];

		for ($i = 0; $i < count($medicines); $i++) {
			$medicineName = trim($medicines[$i] ?? '');
			if ($medicineName === '') {
				continue;
			}
			$dosageText = trim($dosages[$i] ?? '');
			$frequencyText = trim($frequencies[$i] ?? '');
			$durationText = trim($durations[$i] ?? '');
			$instructionText = trim($instructions[$i] ?? '');

			$line = 'Medicine: ' . $medicineName;
			$line .= ' | Dosage: ' . ($dosageText !== '' ? $dosageText : '-');
			$line .= ' | Frequency: ' . ($frequencyText !== '' ? $frequencyText : '-');
			$line .= ' | Duration: ' . ($durationText !== '' ? $durationText : '-');
			$line .= ' | Instructions: ' . ($instructionText !== '' ? $instructionText : '-');
			$medicineLines[] = $line;
			$medicineCount++;
		}

		$medicinesSummary = hms_escape($con, implode("\n", $medicineLines));

		$insertPrescription = hms_query($con, "INSERT INTO prescriptions(patient_id, doctor_id, appointment_id, temperature, blood_pressure, pulse, weight, symptoms, diagnosis, tests, notes, medicines, next_visit_date) VALUES('" . (int)$appointment['userId'] . "', '$doctorId', '$appointmentId', '$temperature', '$bloodPressure', '$pulse', '$weight', '$symptoms', '$diagnosis', '$tests', '$notes', '$medicinesSummary', $nextVisitDateValue)");

		if ($insertPrescription) {
			$summary = 'Diagnosis: ' . trim($_POST['diagnosis'] ?? '');
			$summary .= ' | Medicines: ' . $medicineCount;
			if ($nextVisitDate !== '') {
				$summary .= ' | Follow-up: ' . $nextVisitDate;
			}
			$summaryEscaped = hms_escape($con, $summary);
			hms_query($con, "UPDATE $appointmentTable SET prescription='$summaryEscaped', visitStatus='Completed', checkOutTime=NOW() WHERE id='$appointmentId' AND doctorId='$doctorId'");
			hms_archive_appointment($con, $appointmentTable, $appointmentId);

			$_SESSION['msg'] = 'Prescription added successfully and appointment moved to history.';
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

		<?php if($showAppointmentChooser): ?>
			<div class="card-box">
				<div class="section-title">Select Checked-In Appointment</div>
				<p class="text-muted">More than one checked-in appointment is available for this doctor. Please choose the patient for whom you want to add the prescription.</p>
				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>Appointment ID</th>
							<th>Patient Name</th>
							<th>Date / Time</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($candidateAppointments as $candidate): ?>
							<tr>
								<td><?php echo (int)($candidate['id'] ?? 0); ?></td>
								<td><?php echo htmlentities($candidate['fullName'] ?? 'Patient'); ?></td>
								<td><?php echo htmlentities(trim((string)($candidate['appointmentDate'] ?? '') . ' ' . (string)($candidate['appointmentTime'] ?? ''))); ?></td>
								<td>
									<a class="btn btn-primary btn-sm" href="add-prescription.php?appointment_id=<?php echo (int)($candidate['id'] ?? 0); ?>&id=<?php echo (int)($candidate['id'] ?? 0); ?>">Open Prescription Form</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<a href="visit-management.php" class="btn btn-cancel">Back to Visit Management</a>
			</div>
		<?php else: ?>
		<form method="post">
			<!-- Form groups collect clinical inputs using Bootstrap grid columns. -->
			<input type="hidden" name="appointment_id" value="<?php echo (int)$appointmentId; ?>">
			<input type="hidden" name="id" value="<?php echo (int)$appointmentId; ?>">

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
				<!-- HTML table captures multiple medicine rows for one prescription. -->
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
							<td><input type="text" name="medicine_name[]" class="form-control" placeholder="Paracetamol"></td>
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

			<button type="submit" name="submit_prescription" class="btn btn-primary">Save Prescription</button>
			<a href="visit-management.php" class="btn btn-cancel">Cancel</a>
		</form>
		<?php endif; ?>
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
// DOM helpers add and remove medicine rows dynamically.
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
