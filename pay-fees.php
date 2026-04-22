<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/checklogin.php';
require_once __DIR__ . '/include/payu.php';
check_login();

$userId = (int)($_SESSION['id'] ?? 0);
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$errors = [];
$appointment = null;
$amount = '';
$checkoutRequest = null;

$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
$appointment = $context['appointment'] ?? null;
if ($appointment) {
	$pendingTxnId = trim((string)($appointment['paymentRef'] ?? ($_SESSION['payu_pending_txn_' . $appointmentId] ?? '')));
	$isStillPending = empty($context['is_paid']) && $pendingTxnId !== '';
	if ($isStillPending) {
		$reconciled = hms_reconcile_payu_transaction($con, $appointmentId, $userId, $pendingTxnId);
		if ($reconciled !== false) {
			$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
			$appointment = $context['appointment'] ?? $appointment;
		}
	}
	$amount = (string)($appointment['consultancyFees'] ?? '');
}
if (!empty($context['error'])) {
	$errors[] = $context['error'];
}

$flashPayuError = trim((string)($_SESSION['payu_error'] ?? ''));
if ($flashPayuError !== '') {
	$errors[] = $flashPayuError;
	unset($_SESSION['payu_error']);
}

$payuConfigured = hms_payu_is_configured();
if (!$payuConfigured) {
	$errors[] = 'PayU configuration is missing. Add PAYU_MERCHANT_KEY and PAYU_MERCHANT_SALT to the local .env file.';
}

$canStartPayment = $appointment && empty($context['error']) && $payuConfigured;
if ($canStartPayment) {
	try {
		$checkoutRequest = hms_build_payu_checkout_request($con, $appointmentId, $userId);
	} catch (\Throwable $e) {
		$errors[] = trim((string)$e->getMessage()) !== '' ? trim((string)$e->getMessage()) : 'Unable to prepare PayU checkout.';
		$canStartPayment = false;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Pay Fees</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.payment-panel {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 12px;
			padding: 18px;
			box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
		}
		.help-note {
			font-size: 13px;
			color: #475569;
		}
		.payment-summary {
			background: #eff6ff;
			border: 1px solid #bfdbfe;
			border-radius: 10px;
			padding: 16px;
			margin-bottom: 18px;
		}
		.payment-summary strong {
			color: #1e3a8a;
		}
	</style>
</head>
<body class="nav-md">
	<?php
	$page_title = 'User | Pay Fees';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-10 col-md-offset-1">
			<div class="payment-panel">
				<h3 style="margin-top:0;color:#1e3a8a;font-weight:700;">PayU Secure Payment</h3>
				<p class="help-note">Use PayU Hosted Checkout to complete your appointment fee payment. Payment details are entered only on PayU&apos;s hosted payment page.</p>

				<?php if (!empty($errors)): ?>
					<div class="alert alert-danger">
						<ul style="margin-bottom:0;">
							<?php foreach ($errors as $error): ?>
								<li><?php echo htmlentities($error); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php if ($appointment): ?>
					<div class="payment-summary">
						<div class="row">
							<div class="col-md-4"><strong>Appointment ID:</strong> #<?php echo (int)$appointment['id']; ?></div>
							<div class="col-md-4"><strong>Date / Time:</strong> <?php echo htmlentities(($appointment['appointmentDate'] ?? '') . ' ' . ($appointment['appointmentTime'] ?? '')); ?></div>
							<div class="col-md-4"><strong>Amount:</strong> &#8377;<?php echo htmlentities((string)($appointment['consultancyFees'] ?? '0')); ?></div>
						</div>
						<div style="margin-top:10px;">
							<strong>Current Payment Status:</strong> <?php echo htmlentities((string)($appointment['paymentStatus'] ?? 'Pending')); ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="well" style="background:#f8fafc;border-color:#e2e8f0;">
					<p style="margin:0 0 12px 0;"><strong>What happens next?</strong></p>
					<ol style="margin-bottom:0;padding-left:18px;">
						<li>Click the payment button to redirect securely to PayU Hosted Checkout.</li>
						<li>Complete payment on the PayU payment page using a supported test method.</li>
						<li>PayU will redirect back and the response hash will be verified on the server before the appointment is marked as paid.</li>
					</ol>
				</div>

				<?php if ($checkoutRequest): ?>
					<form id="payu-payment-form" method="post" action="<?php echo htmlentities((string)$checkoutRequest['action']); ?>" target="_self" style="display:inline;">
						<?php foreach (($checkoutRequest['fields'] ?? []) as $fieldName => $fieldValue): ?>
							<input type="hidden" name="<?php echo htmlentities((string)$fieldName); ?>" value="<?php echo htmlentities((string)$fieldValue); ?>">
						<?php endforeach; ?>
						<button type="submit" id="payu-pay-button" class="btn btn-primary btn-lg">
							<i class="fa fa-credit-card"></i> Pay with PayU
						</button>
					</form>
				<?php else: ?>
					<button type="button" class="btn btn-primary btn-lg" disabled>
						<i class="fa fa-credit-card"></i> Pay with PayU
					</button>
				<?php endif; ?>
				<a href="appointments.php" class="btn btn-default btn-lg">Back to Appointments</a>
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
	<script>
	(function () {
		var form = document.getElementById('payu-payment-form');
		var button = document.getElementById('payu-pay-button');
		if (!form || !button) {
			return;
		}

		var defaultHtml = button.innerHTML;
		form.addEventListener('submit', function () {
			button.disabled = true;
			button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Redirecting to PayU...';
			window.setTimeout(function () {
				button.disabled = false;
				button.innerHTML = defaultHtml;
			}, 8000);
		});
	})();
	</script>
</body>
</html>

