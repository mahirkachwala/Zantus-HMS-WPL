<?php
function check_login()
{
	global $con;
	$sessionMaxIdleSeconds = 8 * 60 * 60; // 8 hours

	if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
		$_SESSION['user_id'] = (int)$_SESSION['id'];
	}
	if (!isset($_SESSION['id']) && isset($_SESSION['user_id'])) {
		$_SESSION['id'] = (int)$_SESSION['user_id'];
	}

	$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
	if($userId <= 0 && !empty($_SESSION['login'])) {
		$emailEsc = hms_escape($con, trim((string)$_SESSION['login']));
		$rebuild = hms_query($con, "SELECT id, fullName, email FROM users WHERE email='$emailEsc' LIMIT 1");
		if($rebuild && hms_num_rows($rebuild) > 0) {
			$r = hms_fetch_assoc($rebuild);
			$_SESSION['id'] = (int)$r['id'];
			$_SESSION['user_id'] = (int)$r['id'];
			$_SESSION['fullName'] = $r['fullName'];
			$_SESSION['login'] = $r['email'];
			$userId = (int)$r['id'];
		}
	}
	if($userId <= 0)
	{
		header("Location: index.php");
		exit();
	}

	$now = time();
	if (!isset($_SESSION['last_activity'])) {
		$_SESSION['last_activity'] = $now;
	} elseif (($now - (int)$_SESSION['last_activity']) > $sessionMaxIdleSeconds) {
		unset($_SESSION['login'], $_SESSION['id'], $_SESSION['user_id'], $_SESSION['fullName'], $_SESSION['last_activity']);
		header("Location: index.php");
		exit();
	}
	$_SESSION['last_activity'] = $now;

	$userEmail = trim((string)($_SESSION['login'] ?? ''));
	if ($userEmail !== '') {
		$userEmailEsc = hms_escape($con, $userEmail);
		$verify = hms_query($con, "SELECT id, fullName, email FROM users WHERE id='$userId' AND email='$userEmailEsc' LIMIT 1");
	} else {
		$verify = hms_query($con, "SELECT id, fullName, email FROM users WHERE id='$userId' LIMIT 1");
	}
	if($verify === false) {
		// Avoid hard logout on transient DB issues.
		return;
	}
	if(hms_num_rows($verify) === 0) {
		unset($_SESSION['login'], $_SESSION['id'], $_SESSION['user_id'], $_SESSION['fullName']);
		header("Location: index.php");
		exit();
	}

	$currentUser = hms_fetch_array($verify);
	$_SESSION['id'] = (int)$currentUser['id'];
	$_SESSION['user_id'] = (int)$currentUser['id'];
	$_SESSION['fullName'] = $currentUser['fullName'];
	$_SESSION['login'] = $currentUser['email'];
}
?>