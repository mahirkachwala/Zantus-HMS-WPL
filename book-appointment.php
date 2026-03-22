<?php
session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();

if (!isset($_SESSION['id'])) {
	header('location:index.php');
	exit();
}

// Determine which appointment table to use
$tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'current_appointments'");
$useCurrentAppointments = mysqli_num_rows($tableCheck) > 0;
$appointmentTable = $useCurrentAppointments ? 'current_appointments' : 'appointment';

if(isset($_POST['submit'])) {
	$specilization = $_POST['Doctorspecialization'];
	$doctorid = (int)$_POST['doctor'];
	$userid = $_SESSION['id'];
	$fees = (int)$_POST['fees'];
	$appdate = $_POST['appdate'];
	$time = $_POST['apptime'];
	$paymentOption = $_POST['paymentOption'] ?? 'BookOnly';
	$userstatus = 1;
	$docstatus = 1;
	
	// Check if using new schema
	$columnsCheck = mysqli_query($con, "SHOW COLUMNS FROM $appointmentTable");
	$columns = [];
	while ($col = mysqli_fetch_assoc($columnsCheck)) {
		$columns[] = $col['Field'];
	}
	
	$hasPaymentOption = in_array('paymentOption', $columns);
	$hasAppointmentType = in_array('appointmentType', $columns);
	$hasPaymentStatus = in_array('paymentStatus', $columns);
	
	$insertCols = "doctorSpecialization,doctorId,userId,consultancyFees,appointmentDate,appointmentTime,userStatus,doctorStatus";
	$insertVals = "'$specilization','$doctorid','$userid','$fees','$appdate','$time','$userstatus','$docstatus'";
	
	if ($hasAppointmentType) {
		$insertCols .= ",appointmentType";
		$insertVals .= ",'Online'";
	}
	
	if ($hasPaymentOption) {
		$insertCols .= ",paymentOption";
		$insertVals .= ",'$paymentOption'";
	}
	
	// Set payment status based on option
	if ($hasPaymentStatus) {
		$insertCols .= ",paymentStatus";
		if ($paymentOption === 'PayNow') {
			$insertVals .= ",'Pending'";
		} elseif ($paymentOption === 'PayLater') {
			$insertVals .= ",'Pay at Hospital'";
		} else {
			$insertVals .= ",'Pending'";
		}
	}
	
	$query = mysqli_query($con, "INSERT INTO $appointmentTable($insertCols) VALUES($insertVals)");
	
	if($query) {
		$appointmentId = mysqli_insert_id($con);
		
		if ($paymentOption === 'PayNow') {
			$_SESSION['msg1'] = "Appointment created. Proceed to payment.";
			header("Location: pay-fees.php?appointment_id=" . $appointmentId);
		} else {
			$_SESSION['msg1'] = "Your appointment was booked successfully.";
			header("Location: appointments.php?msg=booked");
		}
		exit();
	} else {
		$_SESSION['error'] = "Error booking appointment: " . mysqli_error($con);
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Book Appointment</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/iCheck/skins/flat/green.css" rel="stylesheet">
	<link href="vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
	<link href="vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
	<style>
		.payment-option-card {
			border: 2px solid #e6ebf5;
			border-radius: 8px;
			padding: 15px;
			margin-bottom: 10px;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		.payment-option-card:hover {
			border-color: #1e40af;
			background: #f0f9ff;
		}
		.payment-option-card input[type="radio"] {
			cursor: pointer;
		}
		.payment-option-card.selected {
			border-color: #1e40af;
			background: #f0f9ff;
		}
	</style>
	<script>
		function getdoctor(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data:'specilizationid='+val,
				success: function(data){
					$("#doctor").html(data);
				}
			});
		}
		function getfee(val) {
			$.ajax({
				type: "POST",
				url: "get_doctor.php",
				data:'docid='+val,
				success: function(data){
					$("#fees").html(data);
					$("#feesDisplay").text('₹ ' + data.replace(/[^0-9]/g, ''));
				}
			});
		}
		function selectPaymentOption(option) {
			document.querySelectorAll('.payment-option-card').forEach(el => {
				el.classList.remove('selected');
			});
			document.getElementById('paymentOption-' + option).parentElement.classList.add('selected');
			document.getElementById('paymentOption-' + option).checked = true;
		}
	</script>
</head>
<body class="nav-md">
	<?php
	$page_title = 'User | Book Appointment';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<div class="row margin-top-30">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-heading">
							<h5 class="panel-title">Book Your Appointment</h5>
						</div>
						<div class="panel-body">
							<?php if(!empty($_SESSION['msg1'])): ?>
								<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg1']);?></div>
								<?php $_SESSION['msg1']=""; ?>
							<?php endif; ?>
							<?php if(!empty($_SESSION['error'])): ?>
								<div class="alert alert-danger"><?php echo htmlentities($_SESSION['error']);?></div>
								<?php $_SESSION['error']=""; ?>
							<?php endif; ?>
							<form role="form" name="book" method="post">
								<div class="form-group">
									<label for="DoctorSpecialization">Doctor Specialization</label>
									<select name="Doctorspecialization" class="form-control" onChange="getdoctor(this.value);" required="required">
										<option value="">Select Specialization</option>
										<?php 
										$ret=mysqli_query($con,"select * from doctorspecilization");
										while($row=mysqli_fetch_array($ret)) {
											echo "<option value='".htmlentities($row['specilization'])."'>".htmlentities($row['specilization'])."</option>";
										}
										?>
									</select>
								</div>
								
								<div class="form-group">
									<label for="doctor">Doctor</label>
									<select name="doctor" class="form-control" id="doctor" onChange="getfee(this.value);" required="required">
										<option value="">Select Doctor</option>
									</select>
								</div>
								
								<div class="form-group">
									<label for="consultancyfees">Consultancy Fees</label>
									<div style="display:flex; align-items:center; gap:10px;">
										<select name="fees" class="form-control" id="fees" readonly style="flex:1;">
											<option>Select Doctor First</option>
										</select>
										<div style="font-size:18px; font-weight:700; color:#1e40af; min-width:80px; text-align:right;">
											<span id="feesDisplay">-</span>
										</div>
									</div>
								</div>
								
								<div class="form-group">
									<label for="AppointmentDate">Date</label>
									<input type="date" class="form-control" name="appdate" min="<?php echo date('Y-m-d'); ?>" required="required">
								</div>
								
								<div class="form-group">
									<label for="Appointmenttime">Time</label>
									<input type="time" class="form-control" name="apptime" id="time" required="required" placeholder="eg: 10:00 AM">
								</div>

								<!-- Payment Options Section -->
								<hr style="margin:20px 0;">
								<h5 style="color:#1e3a8a; font-weight:600; margin-bottom:15px;">Payment Method</h5>
								
								<div class="payment-option-card selected" onclick="selectPaymentOption('BookOnly')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-BookOnly" name="paymentOption" value="BookOnly" checked>
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-BookOnly" style="cursor:pointer; margin:0; font-weight:600;">Book Appointment</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Book now, pay from your appointments page later</p>
										</div>
									</div>
								</div>
								
								<div class="payment-option-card" onclick="selectPaymentOption('PayNow')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-PayNow" name="paymentOption" value="PayNow">
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-PayNow" style="cursor:pointer; margin:0; font-weight:600;">Pay Now</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Complete payment immediately to confirm your appointment</p>
										</div>
									</div>
								</div>
								
								<div class="payment-option-card" onclick="selectPaymentOption('PayLater')">
									<div style="display:flex; align-items:center;">
										<input type="radio" id="paymentOption-PayLater" name="paymentOption" value="PayLater">
										<div style="margin-left:12px; flex:1;">
											<label for="paymentOption-PayLater" style="cursor:pointer; margin:0; font-weight:600;">Pay at Hospital</label>
											<p style="margin:5px 0 0; font-size:13px; color:#666;">Pay directly at the hospital during your visit</p>
										</div>
									</div>
								</div>

								<hr style="margin:20px 0;">
								<button type="submit" name="submit" class="btn btn-primary btn-lg" style="min-width:150px;">
									<i class="fa fa-check"></i> Continue
								</button>
								<a href="appointments.php" class="btn btn-default btn-lg">Cancel</a>
							</form>
						</div>
					</div>
				</div>
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