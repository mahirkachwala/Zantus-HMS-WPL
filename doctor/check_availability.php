<?php 
require_once("include/config.php");
if(!empty($_POST["email"])) {
	$email = hms_escape($con, trim($_POST["email"]));
	$count = 0;

	if (hms_table_exists($con, 'patients')) {
		$result = hms_query($con, "SELECT id FROM patients WHERE patientEmail='$email' LIMIT 1");
		$count = hms_num_rows($result);
	} elseif (hms_table_exists($con, 'tblpatient')) {
		$result = hms_query($con, "SELECT ID FROM tblpatient WHERE PatientEmail='$email' LIMIT 1");
		$count = hms_num_rows($result);
	}

	if($count>0)
	{
		echo "<span style='color:red'> Email already exists .</span>";
		echo "<script>$('#submit').prop('disabled',true);</script>";
	} else{

		echo "<span style='color:green'> Email available for Registration .</span>";
		echo "<script>$('#submit').prop('disabled',false);</script>";
	}
}
?>
