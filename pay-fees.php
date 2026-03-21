<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

function ensureAppointmentColumns($con) {
	$requiredColumns = [
		"visitStatus" => "ALTER TABLE appointment ADD COLUMN visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled' AFTER doctorStatus",
		"checkInTime" => "ALTER TABLE appointment ADD COLUMN checkInTime datetime DEFAULT NULL AFTER visitStatus",
		"checkOutTime" => "ALTER TABLE appointment ADD COLUMN checkOutTime datetime DEFAULT NULL AFTER checkInTime",
		"prescription" => "ALTER TABLE appointment ADD COLUMN prescription mediumtext DEFAULT NULL AFTER checkOutTime",
		"paymentStatus" => "ALTER TABLE appointment ADD COLUMN paymentStatus varchar(20) NOT NULL DEFAULT 'Pending' AFTER prescription",
		"paymentRef" => "ALTER TABLE appointment ADD COLUMN paymentRef varchar(64) DEFAULT NULL AFTER paymentStatus",
		"paidAt" => "ALTER TABLE appointment ADD COLUMN paidAt datetime DEFAULT NULL AFTER paymentRef"
	];

	foreach ($requiredColumns as $columnName => $ddl) {
		$check = mysqli_query($con, "SHOW COLUMNS FROM appointment LIKE '" . $columnName . "'");
		if ($check && mysqli_num_rows($check) === 0) {
			mysqli_query($con, $ddl);
		}
	}
}

ensureAppointmentColumns($con);

function luhnCheck($number) {
	$number = preg_replace('/\D/', '', $number);
	$sum = 0;
	$alt = false;
	for ($i = strlen($number) - 1; $i >= 0; $i--) {
		$n = (int)$number[$i];
		if ($alt) {
			$n *= 2;
			if ($n > 9) {
				$n -= 9;
			}
		}
		$sum += $n;
		$alt = !$alt;
	}
	return strlen($number) >= 13 && ($sum % 10 === 0);
}

$userId = (int)($_SESSION['id'] ?? 0);
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$appointment = null;
$amount = '';
$errors = [];
$successMsg = '';

if ($appointmentId > 0) {
	$stmt = mysqli_prepare($con, "SELECT id, consultancyFees, appointmentDate, appointmentTime, paymentStatus FROM appointment WHERE id=? AND userId=?");
	mysqli_stmt_bind_param($stmt, 'ii', $appointmentId, $userId);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	$appointment = mysqli_fetch_assoc($result);
	mysqli_stmt_close($stmt);

	if ($appointment) {
		$amount = $appointment['consultancyFees'];
	} else {
		$appointmentId = 0;
	}
}

if (isset($_POST['submit_payment'])) {
	$appointmentId = (int)($_POST['appointment_id'] ?? 0);
	$cardName = trim($_POST['card_name'] ?? '');
	$cardNumberRaw = trim($_POST['card_number'] ?? '');
	$cardNumber = preg_replace('/\D/', '', $cardNumberRaw);
	$expMonth = (int)($_POST['exp_month'] ?? 0);
	$expYear = (int)($_POST['exp_year'] ?? 0);
	$cvv = trim($_POST['cvv'] ?? '');
	$amountInput = trim($_POST['amount'] ?? '');

	if ($cardName === '' || strlen($cardName) < 3) {
		$errors[] = 'Please enter a valid card holder name.';
	}
	if (!luhnCheck($cardNumber)) {
		$errors[] = 'Please enter a valid card number.';
	}
	if ($expMonth < 1 || $expMonth > 12) {
		$errors[] = 'Please select a valid expiry month.';
	}
	$currentYear = (int)date('Y');
	$currentMonth = (int)date('n');
	if ($expYear < $currentYear || $expYear > ($currentYear + 20)) {
		$errors[] = 'Please select a valid expiry year.';
	} elseif ($expYear === $currentYear && $expMonth < $currentMonth) {
		$errors[] = 'Card expiry date cannot be in the past.';
	}
	if (!preg_match('/^\d{3,4}$/', $cvv)) {
		$errors[] = 'CVV must be 3 or 4 digits.';
	}
	if (!is_numeric($amountInput) || (float)$amountInput <= 0) {
		$errors[] = 'Please enter a valid payment amount.';
	}

	if ($appointmentId > 0) {
		$stmt = mysqli_prepare($con, "SELECT consultancyFees FROM appointment WHERE id=? AND userId=?");
		mysqli_stmt_bind_param($stmt, 'ii', $appointmentId, $userId);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);
		$row = mysqli_fetch_assoc($result);
		mysqli_stmt_close($stmt);

		if (!$row) {
			$errors[] = 'Selected appointment is invalid.';
		} else {
			$amountInput = (string)$row['consultancyFees'];
		}
	}

	if (empty($errors)) {
		$txnRef = 'ZTXN' . time() . rand(100, 999);
		$_SESSION['lastPayment'] = [
			'txnRef' => $txnRef,
			'amount' => $amountInput,
			'appointmentId' => $appointmentId,
			'paidAt' => date('Y-m-d H:i:s'),
			'cardLast4' => substr($cardNumber, -4)
		];
		if ($appointmentId > 0) {
			$stmt = mysqli_prepare($con, "UPDATE appointment SET paymentStatus='Paid', paymentRef=?, paidAt=NOW() WHERE id=? AND userId=?");
			mysqli_stmt_bind_param($stmt, 'sii', $txnRef, $appointmentId, $userId);
			$ok = mysqli_stmt_execute($stmt);
			mysqli_stmt_close($stmt);

			if ($ok) {
				$_SESSION['msg'] = 'Payment successful for appointment #'.$appointmentId.'.';
				header('Location: appointment-history.php');
				exit();
			}

			$errors[] = 'Payment recorded but appointment update failed. Please refresh and try again.';
		}

		$successMsg = 'Payment successful. Transaction Ref: ' . $txnRef;
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
	</style>
	<script>
	function validCardLuhn(number) {
		number = (number || '').replace(/\D/g, '');
		if (number.length < 13) return false;
		let sum = 0;
		let alt = false;
		for (let i = number.length - 1; i >= 0; i--) {
			let n = parseInt(number.charAt(i), 10);
			if (alt) {
				n *= 2;
				if (n > 9) n -= 9;
			}
			sum += n;
			alt = !alt;
		}
		return sum % 10 === 0;
	}

	function validatePaymentForm() {
		const card = document.getElementById('card_number').value;
		const cvv = document.getElementById('cvv').value;
		if (!validCardLuhn(card)) {
			alert('Invalid card number.');
			return false;
		}
		if (!/^\d{3,4}$/.test(cvv)) {
			alert('CVV must be 3 or 4 digits.');
			return false;
		}
		return true;
	}
	</script>
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
				<h3 style="margin-top:0;color:#1e3a8a;font-weight:700;">Card Payment</h3>
				<p class="help-note">Enter your card details to complete appointment fee payment. Card data is validated with the Luhn algorithm.</p>

				<?php if (!empty($successMsg)): ?>
					<div class="alert alert-success"><?php echo htmlentities($successMsg); ?></div>
				<?php endif; ?>

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
					<div class="alert alert-info">
						Appointment #<?php echo (int)$appointment['id']; ?> | Date: <?php echo htmlentities($appointment['appointmentDate']); ?> <?php echo htmlentities($appointment['appointmentTime']); ?> | Amount: ₹<?php echo htmlentities($appointment['consultancyFees']); ?> | Current Payment: <?php echo htmlentities($appointment['paymentStatus'] ?? 'Pending'); ?>
					</div>
				<?php endif; ?>

				<form method="post" onsubmit="return validatePaymentForm();">
					<input type="hidden" name="appointment_id" value="<?php echo (int)$appointmentId; ?>">
					<div class="row">
						<div class="col-md-6 form-group">
							<label>Card Holder Name</label>
							<input type="text" name="card_name" class="form-control" required>
						</div>
						<div class="col-md-6 form-group">
							<label>Card Number</label>
							<input type="text" id="card_number" name="card_number" class="form-control" maxlength="19" placeholder="XXXX XXXX XXXX XXXX" required>
						</div>
					</div>
					<div class="row">
						<div class="col-md-3 form-group">
							<label>Expiry Month</label>
							<select name="exp_month" class="form-control" required>
								<option value="">Month</option>
								<?php for($m=1;$m<=12;$m++): ?>
									<option value="<?php echo $m; ?>"><?php echo str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col-md-3 form-group">
							<label>Expiry Year</label>
							<select name="exp_year" class="form-control" required>
								<option value="">Year</option>
								<?php $year=(int)date('Y'); for($y=$year;$y<=$year+20;$y++): ?>
									<option value="<?php echo $y; ?>"><?php echo $y; ?></option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col-md-3 form-group">
							<label>CVV</label>
							<input type="password" id="cvv" name="cvv" class="form-control" maxlength="4" required>
						</div>
						<div class="col-md-3 form-group">
							<label>Amount (₹)</label>
							<input type="number" step="0.01" min="1" name="amount" class="form-control" value="<?php echo htmlentities((string)$amount); ?>" <?php echo $appointment ? 'readonly' : 'required'; ?>>
						</div>
					</div>
					<button type="submit" name="submit_payment" class="btn btn-primary">Pay Now</button>
				</form>
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
</html>
