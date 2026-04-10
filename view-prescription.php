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

if (!tableExists($con, 'prescriptions')) {
	$_SESSION['msg'] = 'Structured prescription tables are not available.';
	header('location:appointment-history.php');
	exit();
}

$appointmentId = (int)($_GET['appointment_id'] ?? 0);
$prescriptionId = (int)($_GET['prescription_id'] ?? 0);
$userId = (int)($_SESSION['id'] ?? 0);

$q = null;
if($prescriptionId > 0) {
	$q = hms_query($con, "SELECT p.*, u.fullName AS patientName, d.doctorName
		FROM prescriptions p
		JOIN users u ON u.id=p.patient_id
		JOIN doctors d ON d.id=p.doctor_id
		WHERE p.id='$prescriptionId' AND p.patient_id='$userId'
		LIMIT 1");
} elseif($appointmentId > 0) {
	$q = hms_query($con, "SELECT p.*, u.fullName AS patientName, d.doctorName
		FROM prescriptions p
		JOIN users u ON u.id=p.patient_id
		JOIN doctors d ON d.id=p.doctor_id
		WHERE p.appointment_id='$appointmentId' AND p.patient_id='$userId'
		ORDER BY p.id DESC LIMIT 1");
}
$prescription = ($q) ? hms_fetch_array($q) : null;

if(!$prescription && $appointmentId > 0) {
	// Fallback for migrated/mismatched appointment IDs across legacy and new tables.
	$apptTables = ['current_appointments', 'past_appointments', 'appointment'];
	$doctorIdFromAppointment = 0;
	foreach($apptTables as $tableName) {
		if(!tableExists($con, $tableName)) {
			continue;
		}
		$aq = hms_query($con, "SELECT doctorId FROM $tableName WHERE id='$appointmentId' AND userId='$userId' LIMIT 1");
		if($aq && hms_num_rows($aq) > 0) {
			$ar = hms_fetch_assoc($aq);
			$doctorIdFromAppointment = (int)($ar['doctorId'] ?? 0);
			break;
		}
	}
	if($doctorIdFromAppointment > 0) {
		$q2 = hms_query($con, "SELECT p.*, u.fullName AS patientName, d.doctorName
			FROM prescriptions p
			JOIN users u ON u.id=p.patient_id
			JOIN doctors d ON d.id=p.doctor_id
			WHERE p.patient_id='$userId' AND p.doctor_id='$doctorIdFromAppointment'
			ORDER BY p.id DESC LIMIT 1");
		if($q2 && hms_num_rows($q2) > 0) {
			$prescription = hms_fetch_array($q2);
		}
	}
}

if (!$prescription) {
	$_SESSION['msg'] = 'Prescription not found for this appointment.';
	header('location:appointment-history.php');
	exit();
}

$appointmentDateTime = '-';
$apptId = (int)($prescription['appointment_id'] ?? 0);
if($apptId > 0) {
	$appointmentTables = ['current_appointments', 'past_appointments', 'appointment'];
	foreach($appointmentTables as $tableName) {
		if(!tableExists($con, $tableName)) {
			continue;
		}
		$aq = hms_query($con, "SELECT appointmentDate, appointmentTime FROM $tableName WHERE id='$apptId' AND userId='$userId' LIMIT 1");
		if($aq && hms_num_rows($aq) > 0) {
			$ar = hms_fetch_assoc($aq);
			$appointmentDateTime = trim((string)($ar['appointmentDate'] ?? '') . ' ' . (string)($ar['appointmentTime'] ?? ''));
			if($appointmentDateTime === '') {
				$appointmentDateTime = '-';
			}
			break;
		}
	}
}

$medRows = [];
$medicinesText = trim((string)($prescription['medicines'] ?? ''));
if ($medicinesText !== '') {
	$lines = preg_split('/\r\n|\r|\n/', $medicinesText);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '') {
			continue;
		}
		$item = ['medicine_name' => '-', 'dosage' => '-', 'frequency' => '-', 'duration' => '-', 'instructions' => '-'];
		$parts = array_map('trim', explode('|', $line));
		foreach ($parts as $part) {
			if (stripos($part, 'Medicine:') === 0) {
				$item['medicine_name'] = trim(substr($part, strlen('Medicine:')));
			} elseif (stripos($part, 'Dosage:') === 0) {
				$item['dosage'] = trim(substr($part, strlen('Dosage:')));
			} elseif (stripos($part, 'Frequency:') === 0) {
				$item['frequency'] = trim(substr($part, strlen('Frequency:')));
			} elseif (stripos($part, 'Duration:') === 0) {
				$item['duration'] = trim(substr($part, strlen('Duration:')));
			} elseif (stripos($part, 'Instructions:') === 0) {
				$item['instructions'] = trim(substr($part, strlen('Instructions:')));
			}
		}
		$medRows[] = $item;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | View Prescription</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.block { background:#fff; border:1px solid #e6ebf5; border-radius:10px; padding:14px; margin-bottom:12px; }
		.tt { font-weight:700; color:#1e3a8a; margin-bottom:10px; }
	</style>
</head>
<body class="nav-md">
<?php $page_title='User | View Prescription'; $x_content=true; include('include/header.php');?>
<div class="row"><div class="col-md-12">
	<div class="block">
		<div class="tt">Patient & Visit Info</div>
		<div class="row">
			<div class="col-md-3"><strong>Patient:</strong> <?php echo htmlentities($prescription['patientName']); ?></div>
			<div class="col-md-3"><strong>Doctor:</strong> <?php echo htmlentities($prescription['doctorName']); ?></div>
			<div class="col-md-3"><strong>Appointment:</strong> <?php echo htmlentities($appointmentDateTime); ?></div>
			<div class="col-md-3"><strong>Created:</strong> <?php echo htmlentities($prescription['created_at']); ?></div>
		</div>
	</div>

	<div class="block">
		<div class="tt">Vitals</div>
		<div class="row">
			<div class="col-md-3"><strong>Temperature:</strong> <?php echo htmlentities($prescription['temperature'] ?: '-'); ?></div>
			<div class="col-md-3"><strong>Blood Pressure:</strong> <?php echo htmlentities($prescription['blood_pressure'] ?: '-'); ?></div>
			<div class="col-md-3"><strong>Pulse:</strong> <?php echo htmlentities($prescription['pulse'] ?: '-'); ?></div>
			<div class="col-md-3"><strong>Weight:</strong> <?php echo htmlentities($prescription['weight'] ?: '-'); ?></div>
		</div>
	</div>

	<div class="block"><div class="tt">Symptoms</div><?php echo nl2br(htmlentities($prescription['symptoms'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Diagnosis</div><?php echo nl2br(htmlentities($prescription['diagnosis'] ?: '-')); ?></div>

	<div class="block">
		<div class="tt">Medicines</div>
		<table class="table table-bordered table-hover">
			<thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead>
			<tbody>
			<?php if(!empty($medRows)): foreach($medRows as $m): ?>
			<tr>
				<td><?php echo htmlentities($m['medicine_name'] ?: '-'); ?></td>
				<td><?php echo htmlentities($m['dosage'] ?: '-'); ?></td>
				<td><?php echo htmlentities($m['frequency'] ?: '-'); ?></td>
				<td><?php echo htmlentities($m['duration'] ?: '-'); ?></td>
				<td><?php echo htmlentities($m['instructions'] ?: '-'); ?></td>
			</tr>
			<?php endforeach; else: ?>
			<tr><td colspan="5" class="text-center text-muted">No medicines added.</td></tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="block"><div class="tt">Tests</div><?php echo nl2br(htmlentities($prescription['tests'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Doctor Notes</div><?php echo nl2br(htmlentities($prescription['notes'] ?: '-')); ?></div>
	<div class="block"><div class="tt">Follow-up Date</div><?php echo htmlentities($prescription['next_visit_date'] ?: '-'); ?></div>

	<a href="appointment-history.php" class="btn btn-primary">Back to History</a>
</div></div>
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
</html>
