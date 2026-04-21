<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
require_once __DIR__ . '/include/config.php';
require_once __DIR__ . '/include/checklogin.php';
require_once __DIR__ . '/include/razorpay.php';
check_login();

$userId = (int)($_SESSION['id'] ?? 0);
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$errors = [];
$appointment = null;
$amount = '';

$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
$appointment = $context['appointment'] ?? null;
if ($appointment) {
	$amount = (string)($appointment['consultancyFees'] ?? '');
}
if (!empty($context['error'])) {
	$errors[] = $context['error'];
}

$razorpayConfigured = hms_razorpay_is_configured();
if (!$razorpayConfigured) {
	$errors[] = 'Razorpay configuration is missing. Add RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET to the local .env file.';
}

$canStartPayment = $appointment && empty($context['error']) && $razorpayConfigured;
$frontendConfig = [
	'ready' => (bool)$canStartPayment,
	'appointmentId' => (int)$appointmentId,
	'keyId' => hms_razorpay_key_id(),
	'fullName' => (string)($_SESSION['fullName'] ?? ''),
	'email' => (string)($_SESSION['login'] ?? ''),
	'amountRupees' => (float)($appointment['consultancyFees'] ?? 0),
];
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
	<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
				<h3 style="margin-top:0;color:#1e3a8a;font-weight:700;">Razorpay Secure Payment</h3>
				<p class="help-note">Use Razorpay Standard Checkout to complete your appointment fee payment. Card and UPI details are entered only inside Razorpay&apos;s hosted modal.</p>

				<div id="payment-message" class="alert" style="display:none;"></div>

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
						<li>Click the payment button to create a secure Razorpay order.</li>
						<li>Complete payment inside the Razorpay modal.</li>
						<li>The payment signature will be verified on the server before the appointment is marked as paid.</li>
					</ol>
				</div>

				<button type="button" id="razorpay-pay-button" class="btn btn-primary btn-lg" <?php echo $canStartPayment ? '' : 'disabled'; ?>>
					<i class="fa fa-credit-card"></i> Pay with Razorpay
				</button>
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
		const config = <?php echo json_encode($frontendConfig, JSON_UNESCAPED_SLASHES); ?>;
		const payButton = document.getElementById('razorpay-pay-button');
		const messageBox = document.getElementById('payment-message');
		const defaultButtonHtml = payButton ? payButton.innerHTML : '';

		function showMessage(type, message) {
			if (!messageBox) {
				return;
			}
			messageBox.className = 'alert alert-' + type;
			messageBox.textContent = message;
			messageBox.style.display = 'block';
		}

		function setButtonState(isBusy, busyLabel) {
			if (!payButton) {
				return;
			}
			payButton.disabled = isBusy;
			payButton.innerHTML = isBusy
				? '<i class="fa fa-spinner fa-spin"></i> ' + busyLabel
				: defaultButtonHtml;
		}

		async function postJson(url, payload) {
			const response = await fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				},
				body: JSON.stringify(payload || {})
			});

			let data = {};
			try {
				data = await response.json();
			} catch (error) {
				data = {};
			}

			if (!response.ok || !data.success) {
				throw new Error(data.message || 'The request could not be completed.');
			}

			return data;
		}

		if (!payButton) {
			return;
		}

		payButton.addEventListener('click', async function () {
			if (!config.ready) {
				showMessage('danger', 'Payment is not available for this appointment.');
				return;
			}

			if (typeof Razorpay === 'undefined') {
				showMessage('danger', 'Razorpay checkout failed to load. Please refresh and try again.');
				return;
			}

			setButtonState(true, 'Creating order...');

			try {
				const order = await postJson('api/create-order.php', {
					appointment_id: config.appointmentId,
					currency: 'INR'
				});

				setButtonState(false, 'Creating order...');

				const options = {
					key: config.keyId,
					order_id: order.order_id,
					amount: order.amount,
					currency: order.currency,
					name: 'Zantus HMS',
					description: 'Appointment fee payment',
					prefill: {
						name: config.fullName || '',
						email: config.email || ''
					},
					notes: {
						appointment_id: String(config.appointmentId || ''),
						receipt: order.receipt || ''
					},
					handler: async function (response) {
						setButtonState(true, 'Verifying payment...');
						showMessage('info', 'Payment received. Verifying signature...');

						try {
							const verification = await postJson('api/verify-payment.php', {
								appointment_id: config.appointmentId,
								razorpay_payment_id: response.razorpay_payment_id,
								razorpay_order_id: response.razorpay_order_id,
								razorpay_signature: response.razorpay_signature
							});

							showMessage('success', verification.message || 'Payment successful. Redirecting...');
							window.location.href = verification.redirect_url || 'appointments.php';
						} catch (error) {
							setButtonState(false, 'Verifying payment...');
							showMessage('danger', error.message || 'Payment verification failed.');
						}
					},
					modal: {
						ondismiss: function () {
							setButtonState(false, 'Creating order...');
							showMessage('warning', 'Payment popup was closed before completion.');
						}
					},
					theme: {
						color: '#1e3a8a'
					}
				};

				const rzp = new Razorpay(options);
				rzp.on('payment.failed', function (response) {
					const description = response && response.error && response.error.description
						? response.error.description
						: 'Payment failed. Please try again.';
					setButtonState(false, 'Creating order...');
					showMessage('danger', description);
				});
				rzp.open();
			} catch (error) {
				setButtonState(false, 'Creating order...');
				showMessage('danger', error.message || 'Unable to start Razorpay checkout.');
			}
		});
	})();
	</script>
</body>
</html>

