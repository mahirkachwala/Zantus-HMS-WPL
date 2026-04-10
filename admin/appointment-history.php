<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function appointmentColumnExists($con, $tableName, $columnName) {
	$check = hms_query($con, "SHOW COLUMNS FROM `$tableName` LIKE '" . hms_escape($con, $columnName) . "'");
	return ($check && hms_num_rows($check) > 0);
}

function tableExists($con, $tableName) {
	$check = hms_query($con, "SHOW TABLES LIKE '" . hms_escape($con, $tableName) . "'");
	return ($check && hms_num_rows($check) > 0);
}

$hasCurrentAppointments = tableExists($con, 'current_appointments');
$hasLegacyAppointments = tableExists($con, 'appointment');
$hasPastAppointments = tableExists($con, 'past_appointments');
$appointmentTable = $hasCurrentAppointments ? 'current_appointments' : 'appointment';
$hasVisitStatus = appointmentColumnExists($con, $appointmentTable, 'visitStatus');
$hasPrescriptionsTable = tableExists($con, 'prescriptions');
$hasTransferTable = tableExists($con, 'appointment_transfers');

if(isset($_GET['cancelid']))
{
	$aid = (int)$_GET['cancelid'];
	hms_query($con,"update $appointmentTable set userStatus='0', doctorStatus='0', visitStatus='Cancelled' where id='$aid'");
	hms_archive_appointment($con, $appointmentTable, $aid);
	$_SESSION['msg'] = 'Appointment cancelled by admin.';
	header('location:appointment-history.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Appointment Management</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
		.history-table {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 10px;
			overflow: hidden;
		}
		.history-table td, .history-table th {
			font-size: 14px;
		}
		.status-text {
			font-weight: 600;
		}
	</style>
</head>
<body class="nav-md">
	<?php
	$page_title = 'Admin | Appointment Management';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">All Appointments (Active / Checked-In / Completed / Cancelled / Transferred)</h3>
			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']); ?></div>
				<?php $_SESSION['msg']=''; ?>
			<?php endif; ?>
			<table class="table table-hover history-table" id="sample-table-1">
				<thead>
					<tr>
						<th class="center">#</th>
						<th class="hidden-xs">Doctor Name</th>
						<th>Patient Name</th>
						<th>Specialization</th>
						<th>Consultancy Fee</th>
						<th>Appointment Date / Time </th>
						<th>Appointment Creation Date  </th>
						<th>Current Status</th>
						<th>Visit Status</th>
						<th>Prescription</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Load all appointment sources so admin can view complete records.
					$rows = [];
					$tablesToRead = [];
					if($hasCurrentAppointments) {
						$tablesToRead[] = 'current_appointments';
					} elseif($hasLegacyAppointments) {
						$tablesToRead[] = 'appointment';
					}
					if($hasPastAppointments) {
						$tablesToRead[] = 'past_appointments';
					}

					foreach($tablesToRead as $tbl) {
						$q = hms_query($con, "SELECT doctors.doctorName as docname, users.fullName as pname, $tbl.*, '$tbl' as sourceTableName FROM $tbl JOIN doctors ON doctors.id=$tbl.doctorId JOIN users ON users.id=$tbl.userId");
						if($q) {
							while($r = hms_fetch_array($q)) {
								$rows[] = $r;
							}
						}
					}

					usort($rows, function($a, $b) {
						$da = strtotime((string)($a['postingDate'] ?? $a['archivedAt'] ?? '1970-01-01 00:00:00'));
						$db = strtotime((string)($b['postingDate'] ?? $b['archivedAt'] ?? '1970-01-01 00:00:00'));
						if($da === $db) {
							return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
						}
						return $db <=> $da;
					});

					$cnt=1;
					foreach($rows as $row)
					{
						$isTransferred = false;
						$effectiveAppointmentId = (int)($row['originalAppointmentId'] ?? 0) > 0 ? (int)$row['originalAppointmentId'] : (int)$row['id'];
						if($hasTransferTable) {
							$tr = hms_query($con, "SELECT id FROM appointment_transfers WHERE originalAppointmentId='".$effectiveAppointmentId."' ORDER BY id DESC LIMIT 1");
							$isTransferred = ($tr && hms_num_rows($tr) > 0);
						}
						if(!$isTransferred && stripos((string)($row['prescription'] ?? ''), 'Transferred to Admitted') !== false) {
							$isTransferred = true;
						}
						?>
						<tr>
							<td class="center"><?php echo $cnt;?>.</td>
							<td class="hidden-xs"><?php echo $row['docname'];?></td>
							<td class="hidden-xs"><?php echo $row['pname'];?></td>
							<td><?php echo $row['doctorSpecialization'];?></td>
							<td><?php echo $row['consultancyFees'];?></td>
							<td><?php echo $row['appointmentDate'];?> / <?php echo
							$row['appointmentTime'];?>
						</td>
						<td><?php echo $row['postingDate'];?></td>
						<td class="status-text">
							<?php if(($row['userStatus']==1) && ($row['doctorStatus']==1))
							{
								echo '<span class="status-active">Active</span>';
							}
							if(($row['userStatus']==0) && ($row['doctorStatus']==1))
							{
								echo '<span class="status-cancelled">Cancelled by Patient</span>';
							}
							if(($row['userStatus']==1) && ($row['doctorStatus']==0))
							{
								echo '<span class="status-cancelled">Cancelled by Doctor</span>';
							}
							if(($row['userStatus']==0) && ($row['doctorStatus']==0))
							{
								echo '<span class="status-cancelled">Cancelled</span>';
							}
							?></td>
							<td>
								<?php
								$visitStatus = $row['visitStatus'] ?? 'Scheduled';
								if($isTransferred) {
									echo '<span class="status-transferred">Transferred to Admitted</span>';
								} elseif($visitStatus === 'Completed') {
									echo '<span class="status-active">Completed</span>';
								} elseif($visitStatus === 'Checked In') {
									echo '<span class="status-info">Checked In</span>';
								} elseif($visitStatus === 'Cancelled') {
									echo '<span class="status-cancelled">Cancelled</span>';
								} else {
									echo '<span class="status-warning">Scheduled</span>';
								}
								?>
							</td>
							<td><?php echo nl2br(htmlentities(($row['prescription'] ?? '') ?: 'Not added yet')); ?></td>
							<td>
								<?php
								$hasStructured = false;
								$prescriptionId = 0;
								if($hasPrescriptionsTable) {
									$ps = hms_query($con, "SELECT id FROM prescriptions WHERE appointment_id='".$effectiveAppointmentId."' ORDER BY id DESC LIMIT 1");
									if($ps && hms_num_rows($ps) > 0) {
										$psRow = hms_fetch_assoc($ps);
										$prescriptionId = (int)($psRow['id'] ?? 0);
										$hasStructured = ($prescriptionId > 0);
									} elseif(isset($row['userId'], $row['doctorId'])) {
										$ps2 = hms_query($con, "SELECT id FROM prescriptions WHERE doctor_id='".(int)$row['doctorId']."' AND patient_id='".(int)$row['userId']."' ORDER BY id DESC LIMIT 1");
										if($ps2 && hms_num_rows($ps2) > 0) {
											$psRow2 = hms_fetch_assoc($ps2);
											$prescriptionId = (int)($psRow2['id'] ?? 0);
											$hasStructured = ($prescriptionId > 0);
										}
									}
								}
								if($hasStructured) {
									echo '<a href="view-prescription.php?prescription_id='.(int)$prescriptionId.'" class="btn btn-primary btn-sm">View</a>';
								} else {
									echo '<span class="text-muted">History Record</span>';
								}
								?>
							</td>
						</tr>
						<?php
						$cnt=$cnt+1;
					}?>
				</tbody>
			</table>
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