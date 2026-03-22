<?php
function check_login()
{
	global $con;

	if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
		$_SESSION['user_id'] = (int)$_SESSION['id'];
	}

	if(strlen($_SESSION['login'] ?? '')==0 || empty($_SESSION['user_id']))
	{
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$userId = (int)$_SESSION['user_id'];
	$userEmail = mysqli_real_escape_string($con, $_SESSION['login']);
	$verify = mysqli_query($con, "SELECT id, fullName, email FROM users WHERE id='$userId' AND email='$userEmail' LIMIT 1");
	if(!$verify || mysqli_num_rows($verify) === 0) {
		unset($_SESSION['login'], $_SESSION['id'], $_SESSION['user_id'], $_SESSION['fullName']);
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$currentUser = mysqli_fetch_array($verify);
	$_SESSION['id'] = (int)$currentUser['id'];
	$_SESSION['user_id'] = (int)$currentUser['id'];
	$_SESSION['fullName'] = $currentUser['fullName'];
	$_SESSION['login'] = $currentUser['email'];
}
?>