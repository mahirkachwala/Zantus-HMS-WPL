<?php
function check_login()
{
	global $con;

	if (!isset($_SESSION['doctor_id']) && isset($_SESSION['id'])) {
		$_SESSION['doctor_id'] = (int)$_SESSION['id'];
	}

	if(strlen($_SESSION['dlogin'] ?? '')==0 || empty($_SESSION['doctor_id']))
	{	
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";		
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$doctorId = (int)$_SESSION['doctor_id'];
	$doctorEmail = hms_escape($con, $_SESSION['dlogin']);
	$verify = hms_query($con, "SELECT id, doctorName, docEmail FROM doctors WHERE id='$doctorId' AND docEmail='$doctorEmail' LIMIT 1");
	if(!$verify || hms_num_rows($verify) === 0) {
		unset($_SESSION['dlogin'], $_SESSION['id'], $_SESSION['doctor_id'], $_SESSION['doctorName']);
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$currentDoctor = hms_fetch_array($verify);
	$_SESSION['id'] = (int)$currentDoctor['id'];
	$_SESSION['doctor_id'] = (int)$currentDoctor['id'];
	$_SESSION['doctorName'] = $currentDoctor['doctorName'];
	$_SESSION['dlogin'] = $currentDoctor['docEmail'];
}
?>