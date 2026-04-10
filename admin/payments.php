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

$tableCheck = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
$appointmentTable = ($tableCheck && hms_num_rows($tableCheck) > 0) ? 'current_appointments' : 'appointment';

$hasPaymentStatus = appointmentColumnExists($con, $appointmentTable, 'paymentStatus');
$hasPaymentRef = appointmentColumnExists($con, $appointmentTable, 'paymentRef');
$hasPaidAt = appointmentColumnExists($con, $appointmentTable, 'paidAt');

$statusFilter = trim($_GET['status'] ?? 'all');
$where = "1=1";
if ($statusFilter === 'paid') {
	if ($hasPaymentStatus || $hasPaymentRef || $hasPaidAt) {
		$where .= " AND ((COALESCE($appointmentTable.paymentStatus,'') IN ('Paid','Paid at Hospital'))";
		if ($hasPaymentRef) { $where .= " OR COALESCE($appointmentTable.paymentRef,'')<>''"; }
		if ($hasPaidAt) { $where .= " OR $appointmentTable.paidAt IS NOT NULL"; }
		$where .= ")";
	}
} elseif ($statusFilter === 'pending') {
	if ($hasPaymentStatus || $hasPaymentRef || $hasPaidAt) {
		$where .= " AND NOT ((COALESCE($appointmentTable.paymentStatus,'') IN ('Paid','Paid at Hospital'))";
		if ($hasPaymentRef) { $where .= " OR COALESCE($appointmentTable.paymentRef,'')<>''"; }
		if ($hasPaidAt) { $where .= " OR $appointmentTable.paidAt IS NOT NULL"; }
		$where .= ")";
	}
}

$paidCount = 0;
$pendingCount = 0;
$totQ = hms_query($con, "SELECT $appointmentTable.* FROM $appointmentTable");
if ($totQ) {
	while($r = hms_fetch_array($totQ)) {
		$isPaid = in_array(strtolower((string)($r['paymentStatus'] ?? '')), ['paid', 'paid at hospital'], true) || (!empty($r['paymentRef'])) || (!empty($r['paidAt']));
		if ($isPaid) { $paidCount++; } else { $pendingCount++; }
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Payments</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
	<style>
		.page-heading { font-size:22px; font-weight:700; color:#1e3a8a; margin-bottom:14px; }
		.card-mini { background:#fff; border:1px solid #e6ebf5; border-radius:10px; padding:12px 14px; margin-bottom:12px; }
		.table-wrap { background:#fff; border:1px solid #e6ebf5; border-radius:10px; overflow:hidden; }
	</style>
</head>
<body class="nav-md">
<?php $page_title='Admin | Payments'; $x_content=true; include('include/header.php');?>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-heading">Payments</h3>
		<div class="row">
			<div class="col-md-3"><div class="card-mini"><strong>Paid:</strong> <span class="status-active"><?php echo (int)$paidCount; ?></span></div></div>
			<div class="col-md-3"><div class="card-mini"><strong>Pending:</strong> <span class="status-cancelled"><?php echo (int)$pendingCount; ?></span></div></div>
			<div class="col-md-6 text-right" style="padding-top:4px;">
				<a class="btn btn-primary btn-sm" href="payments.php?status=all">All</a>
				<a class="btn btn-primary btn-sm" href="payments.php?status=paid">Paid</a>
				<a class="btn btn-cancel btn-sm" href="payments.php?status=pending">Pending</a>
			</div>
		</div>

		<table class="table table-hover table-wrap">
			<thead>
				<tr>
					<th>#</th>
					<th>Appointment ID</th>
					<th>Patient</th>
					<th>Doctor</th>
					<th>Consultancy Fee</th>
					<th>Payment Status</th>
					<th>Payment Ref</th>
					<th>Paid At</th>
					<th>Appointment Date/Time</th>
				</tr>
			</thead>
			<tbody>
			<?php
			$cnt=1;
			$sql = hms_query($con, "SELECT $appointmentTable.*, users.fullName AS pname, doctors.doctorName AS dname FROM $appointmentTable JOIN users ON users.id=$appointmentTable.userId JOIN doctors ON doctors.id=$appointmentTable.doctorId WHERE $where ORDER BY $appointmentTable.id DESC");
			if($sql) while($row=hms_fetch_array($sql)) {
				$isPaid = in_array(strtolower((string)($row['paymentStatus'] ?? '')), ['paid', 'paid at hospital'], true) || (!empty($row['paymentRef'])) || (!empty($row['paidAt']));
				$paymentStatusText = (string)($row['paymentStatus'] ?? 'Pending');
				$isHospitalPaid = $isPaid && (strcasecmp($paymentStatusText, 'Paid at Hospital') === 0 || strcasecmp((string)($row['paymentOption'] ?? ''), 'PayLater') === 0);
			?>
			<tr>
				<td><?php echo $cnt; ?>.</td>
				<td><?php echo (int)$row['id']; ?></td>
				<td><?php echo htmlentities($row['pname']); ?></td>
				<td><?php echo htmlentities($row['dname']); ?></td>
				<td><?php echo htmlentities($row['consultancyFees']); ?></td>
				<td>
					<?php
					if ($isHospitalPaid) {
						echo '<span class="status-active">Paid at Hospital</span>';
					} elseif ($isPaid) {
						echo '<span class="status-active">Paid</span>';
					} elseif (strcasecmp($paymentStatusText, 'Cancelled') === 0) {
						echo '<span class="status-cancelled">Cancelled</span>';
					} elseif (stripos($paymentStatusText, 'Transferred') !== false) {
						echo '<span class="status-warning">'.htmlentities($paymentStatusText).'</span>';
					} elseif (strcasecmp($paymentStatusText, 'Pay at Hospital') === 0) {
						echo '<span class="status-info">Pay at Hospital</span>';
					} else {
						echo '<span class="status-cancelled">'.htmlentities($paymentStatusText).'</span>';
					}
					?>
				</td>
				<td><?php echo htmlentities(($row['paymentRef'] ?? '') ?: '-'); ?></td>
				<td><?php echo htmlentities(($row['paidAt'] ?? '') ?: '-'); ?></td>
				<td><?php echo htmlentities($row['appointmentDate'].' '.$row['appointmentTime']); ?></td>
			</tr>
			<?php $cnt++; } ?>
			<?php if($cnt===1): ?>
			<tr><td colspan="9" class="text-center text-muted">No payment records found.</td></tr>
			<?php endif; ?>
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
</html>
