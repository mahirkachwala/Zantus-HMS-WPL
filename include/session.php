<?php
if (!defined('HMS_TIMEZONE')) {
	define('HMS_TIMEZONE', 'Asia/Kolkata');
}

if (!defined('HMS_SESSION_IDLE_SECONDS')) {
	define('HMS_SESSION_IDLE_SECONDS', 8 * 60 * 60);
}

if (!function_exists('hms_configure_runtime')) {
	function hms_configure_runtime() {
		date_default_timezone_set(HMS_TIMEZONE);
		@ini_set('date.timezone', HMS_TIMEZONE);
		@ini_set('session.gc_maxlifetime', (string)HMS_SESSION_IDLE_SECONDS);
		@ini_set('session.cookie_lifetime', (string)HMS_SESSION_IDLE_SECONDS);
		@ini_set('session.cookie_httponly', '1');
		if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
			@ini_set('session.cookie_samesite', 'Lax');
		}
	}
}

if (!function_exists('hms_session_start')) {
	function hms_session_start() {
		hms_configure_runtime();

		if (session_status() === PHP_SESSION_ACTIVE) {
			return;
		}

		$params = session_get_cookie_params();
		$lifetime = HMS_SESSION_IDLE_SECONDS;
		$path = !empty($params['path']) ? $params['path'] : '/';
		$domain = $params['domain'] ?? '';
		$secure = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';

		if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
			session_set_cookie_params([
				'lifetime' => $lifetime,
				'path' => $path,
				'domain' => $domain,
				'secure' => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			]);
		} else {
			session_set_cookie_params($lifetime, $path, $domain, $secure, true);
		}

		session_start();

		if (PHP_SAPI !== 'cli' && !headers_sent() && session_id() !== '') {
			$expires = time() + HMS_SESSION_IDLE_SECONDS;
			if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
				setcookie(session_name(), session_id(), [
					'expires' => $expires,
					'path' => $path,
					'domain' => $domain,
					'secure' => $secure,
					'httponly' => true,
					'samesite' => 'Lax',
				]);
			} else {
				setcookie(session_name(), session_id(), $expires, $path, $domain, $secure, true);
			}
		}
	}
}

hms_configure_runtime();
?>
