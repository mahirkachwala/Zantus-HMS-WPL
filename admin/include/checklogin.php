<?php
function check_login()
{
	global $con;

	if (!isset($_SESSION['admin_id']) && isset($_SESSION['id'])) {
		$_SESSION['admin_id'] = (int)$_SESSION['id'];
	}
	if (!isset($_SESSION['alogin']) && isset($_SESSION['login'])) {
		$_SESSION['alogin'] = $_SESSION['login'];
	}

	if(strlen($_SESSION['alogin'] ?? '')==0 || empty($_SESSION['admin_id']))
	{	
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";		
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$adminId = (int)$_SESSION['admin_id'];
	$adminUser = mysqli_real_escape_string($con, $_SESSION['alogin']);
	$verify = mysqli_query($con, "SELECT id, username FROM admin WHERE id='$adminId' AND username='$adminUser' LIMIT 1");
	if(!$verify || mysqli_num_rows($verify) === 0) {
		unset($_SESSION['alogin'], $_SESSION['admin_id'], $_SESSION['login'], $_SESSION['id']);
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra="./index.php";
		header("Location: http://$host$uri/$extra");
		exit();
	}

	$currentAdmin = mysqli_fetch_array($verify);
	$_SESSION['admin_id'] = (int)$currentAdmin['id'];
	$_SESSION['alogin'] = $currentAdmin['username'];
	$_SESSION['id'] = (int)$currentAdmin['id'];
	$_SESSION['login'] = $currentAdmin['username'];
}
?>