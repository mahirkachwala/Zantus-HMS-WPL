<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
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

$hasPrescriptionsTable = tableExists($con, 'prescriptions');
$usePastAppointments = tableExists($con, 'past_appointments');
$appointmentTable = tableExists($con, 'current_appointments') ? 'current_appointments' : ($usePastAppointments ? 'past_appointments' : 'appointment');
$hasVisitStatus = appointmentColumnExists($con, $appointmentTable, 'visitStatus');
$hasPaymentStatus = appointmentColumnExists($con, $appointmentTable, 'paymentStatus');
$hasPaymentOption = appointmentColumnExists($con, $appointmentTable, 'paymentOption');
$hasPaymentRef = appointmentColumnExists($con, $appointmentTable, 'paymentRef');
$hasPaidAt = appointmentColumnExists($con, $appointmentTable, 'paidAt');
$hasTransferTable = tableExists($con, 'appointment_transfers');
if(isset($_GET['cancel']))
{
	$aid = (int)($_GET['id'] ?? 0);
	$hasPaymentStatus = appointmentColumnExists($con, $appointmentTable, 'paymentStatus');
	$hasPaymentRef = appointmentColumnExists($con, $appointmentTable, 'paymentRef');
	$hasPaidAt = appointmentColumnExists($con, $appointmentTable, 'paidAt');
	$hasCheckOutTime = appointmentColumnExists($con, $appointmentTable, 'checkOutTime');
	$apptRow = hms_query($con, "SELECT * FROM $appointmentTable WHERE id='$aid' AND userId='".(int)$_SESSION['id']."' LIMIT 1");
	$apptData = ($apptRow) ? hms_fetch_array($apptRow) : null;
	$isPaid = ($hasPaymentStatus && in_array(strtolower((string)($apptData['paymentStatus'] ?? '')), ['paid','paid at hospital'], true))
		|| ($hasPaymentRef && !empty($apptData['paymentRef']))
		|| ($hasPaidAt && !empty($apptData['paidAt']));
	$updates = ["userStatus='0'"];
	if($hasVisitStatus) {
		$updates[] = "visitStatus='Cancelled'";
	}
	if($hasPaymentStatus && !$isPaid) {
		$updates[] = "paymentStatus='Cancelled'";
	}
	if($hasCheckOutTime) {
		$updates[] = "checkOutTime=NOW()";
	}
	hms_query($con,"update $appointmentTable set ".implode(', ', $updates)." where id = '$aid' and userId='".(int)$_SESSION['id']."'");
	hms_archive_appointment($con, $appointmentTable, $aid);
	$_SESSION['msg']="Your appointment canceled !!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Appointment History</title>


	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="assets/css/custom.min.css" rel="stylesheet">
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
	$page_title = 'User  | Appointment History';
	$x_content = true;
	?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Appointment History (Checked-In / Completed / Cancelled)</h3>

			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']);?></div>
				<?php $_SESSION['msg']=""; ?>
			<?php endif; ?>
			<table class="table table-hover history-table" id="sample-table-1">
				<thead>
					<tr>
						<th class="center">#</th>
						<th class="hidden-xs">Doctor Name</th>
						<th>Specialization</th>
						<th>Consultancy Fee</th>
						<th>Payment</th>
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
									$historyWhere = "($appointmentTable.userStatus=0 OR $appointmentTable.doctorStatus=0)";
									if($hasVisitStatus) {
										$historyWhere .= " OR $appointmentTable.visitStatus IN ('Checked In','Completed','Cancelled')";
									}
									$sql=hms_query($con,"select doctors.doctorName as docname,$appointmentTable.*  from $appointmentTable join doctors on doctors.id=$appointmentTable.doctorId where $appointmentTable.userId='".$_SESSION['id']."' and (".$historyWhere.") order by $appointmentTable.id desc");

									// Fallback: if using current_appointments and no rows found, try past_appointments.
									if($appointmentTable === 'current_appointments' && $sql && hms_num_rows($sql) === 0 && $usePastAppointments) {
										$pastHasVisitStatus = appointmentColumnExists($con, 'past_appointments', 'visitStatus');
										$historyWherePast = "(past_appointments.userStatus=0 OR past_appointments.doctorStatus=0)";
										if($pastHasVisitStatus) {
											$historyWherePast .= " OR past_appointments.visitStatus IN ('Checked In','Completed','Cancelled')";
										}
										$sql=hms_query($con,"select doctors.doctorName as docname,past_appointments.* from past_appointments join doctors on doctors.id=past_appointments.doctorId where past_appointments.userId='".$_SESSION['id']."' and (".$historyWherePast.") order by past_appointments.id desc");
									}
					$cnt=1;
					while($row=hms_fetch_array($sql))
					{
						$paymentTransaction = hms_get_latest_payment_transaction($con, (int)($row['id'] ?? 0), (int)($_SESSION['id'] ?? 0));
						if (hms_payment_transaction_implies_paid($paymentTransaction)) {
							hms_sync_appointment_payment_from_transaction($con, $appointmentTable, (int)($row['id'] ?? 0), (int)($_SESSION['id'] ?? 0), $paymentTransaction);
							$row['paymentStatus'] = strtolower(trim((string)($paymentTransaction['payment_method'] ?? ''))) === 'pay at hospital' ? 'Paid at Hospital' : 'Paid';
							$row['paymentRef'] = (string)($paymentTransaction['transaction_ref'] ?? ($row['paymentRef'] ?? ''));
							$row['paidAt'] = (string)($paymentTransaction['paid_at'] ?? ($row['paidAt'] ?? ''));
						}
						$isTransferred = false;
						if($hasTransferTable) {
							$tr = hms_query($con, "SELECT id FROM appointment_transfers WHERE originalAppointmentId='".(int)$row['id']."' ORDER BY id DESC LIMIT 1");
							$isTransferred = ($tr && hms_num_rows($tr) > 0);
						}
						if(!$isTransferred && stripos((string)($row['prescription'] ?? ''), 'Transferred to Admitted') !== false) {
							$isTransferred = true;
						}
						?>

						<tr>
							<td class="center"><?php echo $cnt;?>.</td>
							<td class="hidden-xs"><?php echo $row['docname'];?></td>
							<td><?php echo $row['doctorSpecialization'];?></td>
							<td><?php echo $row['consultancyFees'];?></td>
							<td>
								<?php
								$paymentStatus = (string)($row['paymentStatus'] ?? 'Pending');
								$isPaid = ($hasPaymentStatus && in_array(strtolower($paymentStatus), ['paid','paid at hospital'], true))
									|| ($hasPaymentRef && !empty($row['paymentRef']))
									|| ($hasPaidAt && !empty($row['paidAt']));
								$isHospitalPayment = $isPaid && (
									($hasPaymentOption && (string)($row['paymentOption'] ?? '') === 'PayLater')
									|| in_array(strtolower($paymentStatus), ['pay at hospital', 'paid at hospital'], true)
								);
								if($isHospitalPayment) {
									echo '<span class="status-active">Paid at Hospital</span>';
								} elseif($isPaid) {
									echo '<span class="status-active">Paid</span>';
								} elseif($paymentStatus === 'Pay at Hospital') {
									echo '<span class="status-info">Pay at Hospital</span>';
								} elseif($paymentStatus === 'Cancelled') {
									echo '<span class="status-cancelled">Cancelled</span>';
								} elseif($paymentStatus === 'Transferred to Admitted') {
									echo '<span class="status-warning">Transferred to Admitted</span>';
								} else {
									echo '<span class="status-warning">'.htmlentities($paymentStatus).'</span>';
								}
								?>
							</td>
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
								echo '<span class="status-cancelled">Cancelled by You</span>';
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
									echo '<span class="status-warning">Transferred to Admitted</span>';
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
							<td><?php echo nl2br(htmlentities(($row['prescription'] ?? '') ?: 'Not available yet')); ?></td>
							<td>
								<?php
								$hasStructured = false;
								$prescriptionId = 0;
								if($hasPrescriptionsTable) {
									$ps = hms_query($con, "SELECT id FROM prescriptions WHERE appointment_id='".(int)$row['id']."' ORDER BY id DESC LIMIT 1");
									if($ps && hms_num_rows($ps) > 0) {
										$psRow = hms_fetch_assoc($ps);
										$prescriptionId = (int)($psRow['id'] ?? 0);
										$hasStructured = ($prescriptionId > 0);
									} elseif(isset($row['doctorId'])) {
										// Fallback for migrated/mismatched appointment IDs.
										$ps2 = hms_query($con, "SELECT id FROM prescriptions WHERE patient_id='".(int)$_SESSION['id']."' AND doctor_id='".(int)$row['doctorId']."' ORDER BY id DESC LIMIT 1");
										if($ps2 && hms_num_rows($ps2) > 0) {
											$psRow2 = hms_fetch_assoc($ps2);
											$prescriptionId = (int)($psRow2['id'] ?? 0);
											$hasStructured = ($prescriptionId > 0);
										}
									}
								}
								echo '<div style="display:flex; gap:5px; flex-wrap:wrap;">';
								echo '<a href="appointment-receipt.php?appointment_id='.(int)$row['id'].'" target="_blank" class="btn btn-default btn-sm">Appointment Receipt</a>';
								if($isPaid) {
									echo '<a href="payment-receipt.php?appointment_id='.(int)$row['id'].'" target="_blank" class="btn btn-info btn-sm">Payment Receipt</a>';
								}
								if($hasStructured) {
									echo '<a href="view-prescription.php?prescription_id='.(int)$prescriptionId.'" class="btn btn-primary btn-sm">View</a>';
									echo '<a href="prescription-receipt.php?prescription_id='.(int)$prescriptionId.'" target="_blank" class="btn btn-success btn-sm">Prescription PDF</a>';
								} else {
									echo '<span class="text-muted">History Record</span>';
								}
								echo '</div>';
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
