<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function isStrongPassword($password) {
	return (bool)preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', (string)$password);
}

$doctorSpecColumn = 'specilization';
$doctorSpecType = '';
$doctorSpecColumnCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specialization'");
if ($doctorSpecColumnCheck && hms_num_rows($doctorSpecColumnCheck) > 0) {
	$doctorSpecColumn = 'specialization';
	$doctorSpecMeta = hms_fetch_assoc($doctorSpecColumnCheck);
	$doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
} else {
	$doctorSpecLegacyCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specilization'");
	if ($doctorSpecLegacyCheck && hms_num_rows($doctorSpecLegacyCheck) > 0) {
		$doctorSpecMeta = hms_fetch_assoc($doctorSpecLegacyCheck);
		$doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
	}
}
$isDoctorSpecNumeric = preg_match('/int|decimal|float|double/', $doctorSpecType) === 1;

$specTable = '';
$specColumn = 'specialization';
if (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecialization'")) > 0) {
	$specTable = 'doctorspecialization';
	$specColumn = 'specialization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecilization'")) > 0) {
	$specTable = 'doctorspecilization';
	$specColumn = 'specilization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctor_specialization'")) > 0) {
	$specTable = 'doctor_specialization';
	$specColumn = 'specialization';
}

if(isset($_POST['submit']))
{
	$docspecialization=$_POST['Doctorspecialization'];
	if ($isDoctorSpecNumeric) {
		$docspecialization = (int)$docspecialization;
	}
	$docname=$_POST['docname'];
	$docaddress=$_POST['clinicaddress'];
	$docfees=$_POST['docfees'];
	$doccontactno=$_POST['doccontact'];
	$docemail=$_POST['docemail'];
	$password=$_POST['npass'] ?? '';
	$confirmPassword=$_POST['cfpass'] ?? '';
	if($password !== $confirmPassword) {
		echo "<script>alert('Password and Confirm Password Field do not match.');</script>";
	} elseif(!isStrongPassword($password)) {
		echo "<script>alert('Password must be minimum 8 characters with uppercase, lowercase, number and special character.');</script>";
	} else {
		// Hash the doctor's password before storing.
		$passwordHash = password_hash($password, PASSWORD_DEFAULT);
		$sql=hms_query($con,"INSERT INTO doctors($doctorSpecColumn,doctorName,address,docFees,contactno,docEmail,password) VALUES('".$docspecialization."','".hms_escape($con, $docname)."','".hms_escape($con, $docaddress)."','".hms_escape($con, $docfees)."','".hms_escape($con, $doccontactno)."','".hms_escape($con, $docemail)."','".hms_escape($con, $passwordHash)."')");
		if($sql)
		{
			echo "<script>alert('Doctor info added Successfully');</script>";
			echo "<script>window.location.href ='manage-doctors.php'</script>";

		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Add Doctor</title>


	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

	<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

	<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

	<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

	<link href="../assets/css/custom.min.css" rel="stylesheet">
	<script type="text/javascript">
		function strongPassword(pwd) {
			return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd || '');
		}

		function valid()
		{
			if(document.adddoc.npass.value!= document.adddoc.cfpass.value)
			{
				alert("Password and Confirm Password Field do not match  !!");
				document.adddoc.cfpass.focus();
				return false;
			}
			if(!strongPassword(document.adddoc.npass.value)) {
				alert("Password must be minimum 8 characters with uppercase, lowercase, number and special character.");
				document.adddoc.npass.focus();
				return false;
			}
			return true;
		}
	</script>


	<script>
		function checkemailAvailability() {
			$("#loaderIcon").show();
			jQuery.ajax({
				url: "check_availability.php",
				data:'emailid='+$("#docemail").val(),
				type: "POST",
				success:function(data){
					$("#email-availability-status").html(data);
					$("#loaderIcon").hide();
				},
				error:function (){}
			});
		}
	</script>
</head>
<body class="nav-md">
	<?php
	$page_title = 'Add Doctor';
	$x_content = true;
	?>
	<?php include('include/header.php');?>

	<div class="row">
		<div class="col-md-12">

			<div class="row margin-top-30">
				<div class="col-lg-8 col-md-12">
					<div class="panel panel-white">
						<div class="panel-body">

							<form role="form" name="adddoc" method="post" onSubmit="return valid();">
								<div class="form-group">
									<label for="DoctorSpecialization">
										Doctor Specialization
									</label>
									<select name="Doctorspecialization" class="form-control" required="true">
										<option value="">Select Specialization</option>
										<?php $ret=hms_query($con,"SELECT MIN(id) AS id, $specColumn AS specialization_name FROM $specTable GROUP BY $specColumn ORDER BY $specColumn ASC");
										while($row=hms_fetch_array($ret))
										{
											$optionValue = $isDoctorSpecNumeric ? $row['id'] : $row['specialization_name'];
											?>
											<option value="<?php echo htmlentities($optionValue);?>">
												<?php echo htmlentities($row['specialization_name']);?>
											</option>
										<?php } ?>

									</select>
								</div>

								<div class="form-group">
									<label for="doctorname">
										Doctor Name
									</label>
									<input type="text" name="docname" class="form-control"  placeholder="Enter Doctor Name" required="true">
								</div>


								<div class="form-group">
									<label for="address">
										Doctor Clinic Address
									</label>
									<textarea name="clinicaddress" class="form-control"  placeholder="Enter Doctor Clinic Address" required="true"></textarea>
								</div>
								<div class="form-group">
									<label for="fess">
										Doctor Consultancy Fees
									</label>
									<input type="text" name="docfees" class="form-control"  placeholder="Enter Doctor Consultancy Fees" required="true">
								</div>

								<div class="form-group">
									<label for="fess">
										Doctor Contact no
									</label>
									<input type="text" name="doccontact" class="form-control"  placeholder="Enter Doctor Contact no" required="true">
								</div>

								<div class="form-group">
									<label for="fess">
										Doctor Email
									</label>
									<input type="email" id="docemail" name="docemail" class="form-control"  placeholder="Enter Doctor Email id" required="true" onBlur="checkemailAvailability()">
									<span id="email-availability-status"></span>
								</div>




								<div class="form-group">
									<label for="exampleInputPassword1">
										Password
									</label>
									<input type="password" name="npass" class="form-control"  placeholder="New Password" required="required">
									<small class="text-muted">Min 8 chars with uppercase, lowercase, number, and special character.</small>
								</div>

								<div class="form-group">
									<label for="exampleInputPassword2">
										Confirm Password
									</label>
									<input type="password" name="cfpass" class="form-control"  placeholder="Confirm Password" required="required">
								</div>



								<button type="submit" name="submit" id="submit" class="btn btn-o btn-primary">
									Submit
								</button>
							</form>
						</div>
					</div>
				</div>

			</div>
		</div>
		<div class="col-lg-12 col-md-12">
			<div class="panel panel-white">


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
</body>
</html>
