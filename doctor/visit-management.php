<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['id'])) {
	header('location:index.php');
	exit();
}

// Check which appointment table to use
$currentApptCheck = mysqli_query($con, "SHOW TABLES LIKE 'current_appointments'");
$useCurrentAppointments = mysqli_num_rows($currentApptCheck) > 0;
$appointmentTable = $useCurrentAppointments ? 'current_appointments' : 'appointment';

function ensureAppointmentColumns($con, $table) {
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
		$check = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '" . $columnName . "'");
		if ($check && mysqli_num_rows($check) === 0) {
			mysqli_query($con, $ddl);
		}
	}
}

function appointmentColumnExists($con, $table, $columnName) {
	$check = mysqli_query($con, "SHOW COLUMNS FROM $table LIKE '" . mysqli_real_escape_string($con, $columnName) . "'");
	return ($check && mysqli_num_rows($check) > 0);
}

ensureAppointmentColumns($con, $appointmentTable);

$hasVisitStatus = appointmentColumnExists($con, $appointmentTable, 'visitStatus');
$hasCheckInTime = appointmentColumnExists($con, $appointmentTable, 'checkInTime');
$hasCheckOutTime = appointmentColumnExists($con, $appointmentTable, 'checkOutTime');
$hasPrescription = appointmentColumnExists($con, $appointmentTable, 'prescription');
$hasPaymentStatus = appointmentColumnExists($con, $appointmentTable, 'paymentStatus');
$hasPaymentRef = appointmentColumnExists($con, $appointmentTable, 'paymentRef');
$hasPaidAt = appointmentColumnExists($con, $appointmentTable, 'paidAt');

$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);

if (isset($_GET['markpaid'])) {
	$aid = (int)$_GET['markpaid'];
	$txnRef = 'HOSPITAL-' . date('YmdHis') . '-' . $aid;
	$updates = [];
	$apptRow = mysqli_query($con, "SELECT * FROM $appointmentTable WHERE id='$aid' AND doctorId='$doctorId' LIMIT 1");
	$apptData = ($apptRow) ? mysqli_fetch_array($apptRow) : null;
	$hasPrescriptionData = !empty(trim((string)($apptData['prescription'] ?? '')));
	if ($hasPaymentStatus) {
		$updates[] = "paymentStatus='Paid'";
	}
	if ($hasPaymentRef) {
		$updates[] = "paymentRef='" . mysqli_real_escape_string($con, $txnRef) . "'";
	}
	if ($hasPaidAt) {
		$updates[] = "paidAt=NOW()";
	}
	if ($hasPrescriptionData && $hasVisitStatus) {
		$updates[] = "visitStatus='Completed'";
	}
	if ($hasPrescriptionData && $hasCheckOutTime) {
		$updates[] = "checkOutTime=NOW()";
	}
	if (!empty($updates)) {
		mysqli_query($con, "UPDATE $appointmentTable SET " . implode(', ', $updates) . " WHERE id='$aid' AND doctorId='$doctorId'");
		$_SESSION['msg'] = $hasPrescriptionData
			? 'Payment marked as received and appointment moved to history.'
			: 'Payment marked as received.';
	}
	header('location:visit-management.php');
	exit();
}

// Check-in functionality
if (isset($_GET['checkin'])) {
	$aid = (int)$_GET['checkin'];
	$updates = [];
	if ($hasVisitStatus) {
		$updates[] = "visitStatus='Checked In'";
	}
	if ($hasCheckInTime) {
		$updates[] = "checkInTime=NOW()";
	}
	if (!empty($updates)) {
		mysqli_query($con, "UPDATE $appointmentTable SET " . implode(', ', $updates) . " WHERE id='$aid' AND doctorId='$doctorId'");
		$_SESSION['msg'] = 'Patient checked in successfully.';
	}
	header('location:visit-management.php');
	exit();
}

// Check-out functionality
if (isset($_GET['checkout'])) {
	$aid = (int)$_GET['checkout'];
	$updates = [];
	if ($hasVisitStatus) {
		$updates[] = "visitStatus='Completed'";
	}
	if ($hasCheckOutTime) {
		$updates[] = "checkOutTime=NOW()";
	}
	if (!empty($updates)) {
		mysqli_query($con, "UPDATE $appointmentTable SET " . implode(', ', $updates) . " WHERE id='$aid' AND doctorId='$doctorId'");
		$_SESSION['msg'] = 'Patient checked out successfully.';
	}
	header('location:visit-management.php');
	exit();
}

// Transfer to admitted functionality
if (isset($_POST['transferToAdmitted'])) {
	$appointmentId = (int)$_POST['appointmentId'];
	$transferReason = trim($_POST['transferReason'] ?? '');
	$transferReasonEscaped = mysqli_real_escape_string($con, $transferReason);
	
	// Check if appointment_transfers table exists
	$transferTableCheck = mysqli_query($con, "SHOW TABLES LIKE 'appointment_transfers'");
	if (mysqli_num_rows($transferTableCheck) > 0) {
		// Get appointment details
		$apptDetails = mysqli_query($con, "SELECT * FROM $appointmentTable WHERE id='$appointmentId' AND doctorId='$doctorId'");
		if ($apptDetails && $row = mysqli_fetch_array($apptDetails)) {
			// Insert transfer record
			$transferQuery = "INSERT INTO appointment_transfers(
				originalAppointmentId, 
				patientId, 
				doctorId, 
				fromType, 
				toType, 
				transferReason, 
				transferDate
			) VALUES(
				'$appointmentId', 
				'" . ($row['patientId'] ?? 0) . "', 
				'$doctorId', 
				'consultancy', 
				'admitted', 
				'$transferReasonEscaped', 
				NOW()
			)";
			if (mysqli_query($con, $transferQuery)) {
				$disposeUpdates = [];
				if ($hasVisitStatus) {
					$disposeUpdates[] = "visitStatus='Completed'";
				}
				if ($hasCheckOutTime) {
					$disposeUpdates[] = "checkOutTime=NOW()";
				}
				if ($hasPrescription) {
					$transferNote = 'Transferred to Admitted: ' . ($transferReason !== '' ? $transferReason : 'Reason not specified');
					$disposeUpdates[] = "prescription='" . mysqli_real_escape_string($con, $transferNote) . "'";
				}

				if (!empty($disposeUpdates)) {
					mysqli_query($con, "UPDATE $appointmentTable SET " . implode(', ', $disposeUpdates) . " WHERE id='$appointmentId' AND doctorId='$doctorId'");
				}

				$_SESSION['msg'] = 'Patient transferred to Admitted and appointment moved to history.';
			}
		}
	}
	header('location:visit-management.php');
	exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Visit Management</title>
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
$page_title = 'Doctor | Visit Management';
$x_content = true;
include('include/header.php');
?>
<div class="row">
	<div class="col-md-12">
		<div class="alert alert-warning" style="margin-bottom:12px;">
			Showing appointments for <strong><?php echo htmlentities($_SESSION['doctorName'] ?? 'Doctor'); ?></strong> (Doctor ID: <strong><?php echo (int)$doctorId; ?></strong>) only.
		</div>
		<?php if(!empty($_SESSION['msg'])): ?>
			<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']); ?></div>
			<?php $_SESSION['msg']=''; ?>
		<?php endif; ?>

		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>Patient Name</th>
					<th>Payment Status</th>
					<th>Appointment Date/Time</th>
					<th>Visit Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$cnt=1;
			$whereVisit = " AND $appointmentTable.userStatus='1' AND $appointmentTable.doctorStatus='1'";
			if ($hasVisitStatus) {
				$whereVisit .= " AND COALESCE($appointmentTable.visitStatus,'Scheduled') IN ('Scheduled','Checked In')";
			}
			
			$sql = mysqli_query($con, "SELECT $appointmentTable.*, users.fullName FROM $appointmentTable JOIN users ON users.id=$appointmentTable.userId WHERE $appointmentTable.doctorId='$doctorId'" . $whereVisit . " ORDER BY $appointmentTable.id DESC");
			
			if ($sql) while($row = mysqli_fetch_array($sql)) {
				if ($hasVisitStatus) {
					$status = $row['visitStatus'] ?: 'Scheduled';
				} else {
					$status = ((int)($row['doctorStatus'] ?? 1) === 0 || (int)($row['userStatus'] ?? 1) === 0) ? 'Cancelled' : 'Scheduled';
				}
				$hasPrescriptionData = !empty(trim((string)($row['prescription'] ?? '')));
				$isPaid = ($hasPaymentStatus && strtoupper((string)($row['paymentStatus'] ?? '')) === 'PAID')
					|| ($hasPaymentRef && !empty($row['paymentRef']))
					|| ($hasPaidAt && !empty($row['paidAt']));
			?>
				<tr>
					<td><?php echo $cnt; ?>.</td>
					<td><?php echo htmlentities($row['fullName']); ?></td>
					<td>
						<?php if($isPaid): ?>
							<span class="status-active">Paid</span>
						<?php else: ?>
							<span class="status-cancelled"><?php echo htmlentities($row['paymentStatus'] ?? 'Pending'); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo htmlentities($row['appointmentDate'].' '.$row['appointmentTime']); ?></td>
					<td>
						<?php if($status === 'Completed'): ?>
							<span class="status-active">Completed</span>
						<?php elseif($status === 'Checked In'): ?>
							<span style="color:#1d4ed8;font-weight:700;">Checked In</span>
						<?php else: ?>
							<span>Scheduled</span>
						<?php endif; ?>
					</td>
					<td style="font-size:12px;">
						<?php if($status === 'Scheduled'): ?>
							<a class="btn btn-primary btn-xs" href="visit-management.php?checkin=<?php echo (int)$row['id']; ?>">Check In</a>
						<?php elseif($status === 'Checked In'): ?>
							<?php if(!$hasPrescriptionData): ?>
								<div style="margin-bottom:5px;">
									<a class="btn btn-primary btn-xs" href="add-prescription.php?appointment_id=<?php echo (int)$row['id']; ?>">Add Prescription</a>
								</div>
							<?php endif; ?>
							<?php if($hasPrescriptionData && !$isPaid): ?>
								<div style="margin-bottom:5px;">
									<a class="btn btn-primary btn-xs" href="visit-management.php?markpaid=<?php echo (int)$row['id']; ?>">Payment Received</a>
								</div>
							<?php endif; ?>
							<?php if($hasPrescriptionData && $isPaid): ?>
								<span class="status-active">Completed - In History</span>
							<?php endif; ?>
							<button class="btn btn-cancel btn-xs" onclick="showTransferModal(<?php echo (int)$row['id']; ?>)">Transfer to Admitted</button>
						<?php else: ?>
							<span class="text-muted">--</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php $cnt++; } ?>
			<?php if(!$sql): ?>
				<tr>
					<td colspan="6" class="text-center text-danger">Unable to load visit data. Please check database updates.</td>
				</tr>
			<?php endif; ?>
			<?php if($cnt === 1): ?>
				<tr>
					<td colspan="6" class="text-center text-muted">No appointments found for this doctor.</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<!-- Transfer to Admitted Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Transfer Patient to Admitted Status</h5>
				<button type="button" class="close" data-dismiss="modal">
					<span>&times;</span>
				</button>
			</div>
			<form method="POST">
				<div class="modal-body">
					<div class="form-group">
						<label>Transfer Reason</label>
						<textarea name="transferReason" class="form-control" placeholder="Enter reason for transfer (e.g., Surgery required, Extended hospitalization needed)" required></textarea>
					</div>
					<input type="hidden" name="appointmentId" id="appointmentId">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
					<button type="submit" name="transferToAdmitted" class="btn btn-primary">Transfer to Admitted</button>
				</div>
			</form>
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
<script>
	function showTransferModal(appointmentId) {
		document.getElementById('appointmentId').value = appointmentId;
		$('#transferModal').modal('show');
	}
</script>
</body>
</html>
