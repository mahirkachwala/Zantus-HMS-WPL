<?php
require_once __DIR__ . '/config.php';

$hmsRazorpayAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($hmsRazorpayAutoload)) {
	require_once $hmsRazorpayAutoload;
}

if (!function_exists('hms_load_env')) {
	function hms_load_env($path = null) {
		static $loaded = false;
		static $values = [];

		if ($loaded) {
			return $values;
		}

		$loaded = true;
		$path = $path ?: dirname(__DIR__) . '/.env';
		if (!is_file($path)) {
			return $values;
		}

		$lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!is_array($lines)) {
			return $values;
		}

		foreach ($lines as $line) {
			$line = trim((string)$line);
			if ($line === '' || strpos($line, '#') === 0) {
				continue;
			}

			$parts = explode('=', $line, 2);
			if (count($parts) !== 2) {
				continue;
			}

			$key = trim($parts[0]);
			$value = trim($parts[1]);
			if ($key === '') {
				continue;
			}

			$len = strlen($value);
			if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
				$value = substr($value, 1, -1);
			}

			$values[$key] = $value;
			$_ENV[$key] = $value;
			$_SERVER[$key] = $value;
			@putenv($key . '=' . $value);
		}

		return $values;
	}
}

if (!function_exists('hms_env')) {
	function hms_env($key, $default = null) {
		hms_load_env();

		$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
		if ($value === false || $value === null || $value === '') {
			return $default;
		}

		return $value;
	}
}

if (!function_exists('hms_json_input')) {
	function hms_json_input() {
		$raw = file_get_contents('php://input');
		if (!is_string($raw) || trim($raw) === '') {
			return [];
		}

		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}
}

if (!function_exists('hms_json_response')) {
	function hms_json_response($statusCode, array $payload) {
		if (!headers_sent()) {
			http_response_code((int)$statusCode);
			header('Content-Type: application/json; charset=utf-8');
			header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		}

		echo json_encode($payload);
		exit();
	}
}

if (!function_exists('hms_api_user_id')) {
	function hms_api_user_id() {
		hms_session_start();

		if (!isset($_SESSION['user_id']) && isset($_SESSION['id'])) {
			$_SESSION['user_id'] = (int)$_SESSION['id'];
		}
		if (!isset($_SESSION['id']) && isset($_SESSION['user_id'])) {
			$_SESSION['id'] = (int)$_SESSION['user_id'];
		}

		return (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
	}
}

if (!function_exists('hms_get_payment_appointment_table')) {
	function hms_get_payment_appointment_table($con) {
		$table = hms_table_exists($con, 'current_appointments') ? 'current_appointments' : 'appointment';
		hms_ensure_appointment_payment_columns($con, $table);
		return $table;
	}
}

if (!function_exists('hms_ensure_appointment_payment_columns')) {
	function hms_ensure_appointment_payment_columns($con, $table) {
		if (!preg_match('/^[a-zA-Z0-9_]+$/', (string)$table)) {
			return false;
		}

		$requiredColumns = [
			'visitStatus' => "visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled'",
			'checkInTime' => "checkInTime datetime DEFAULT NULL",
			'checkOutTime' => "checkOutTime datetime DEFAULT NULL",
			'prescription' => "prescription mediumtext DEFAULT NULL",
			'paymentStatus' => "paymentStatus varchar(20) NOT NULL DEFAULT 'Pending'",
			'paymentRef' => "paymentRef varchar(64) DEFAULT NULL",
			'paidAt' => "paidAt datetime DEFAULT NULL",
		];

		foreach ($requiredColumns as $columnName => $definitionSql) {
			hms_add_column_if_missing($con, $table, $columnName, $definitionSql);
		}

		return true;
	}
}

if (!function_exists('hms_get_payable_appointment_context')) {
	function hms_get_payable_appointment_context($con, $appointmentId, $userId) {
		$appointmentId = (int)$appointmentId;
		$userId = (int)$userId;
		$table = hms_get_payment_appointment_table($con);

		if ($appointmentId <= 0 || $userId <= 0) {
			return ['table' => $table, 'appointment' => null, 'is_paid' => false, 'error' => 'Selected appointment is invalid.'];
		}

		$sql = "SELECT id, userId, consultancyFees, appointmentDate, appointmentTime, paymentStatus, paymentRef, paidAt, userStatus, doctorStatus, visitStatus, paymentOption
			FROM $table WHERE id=$1 AND userId=$2 LIMIT 1";
		$result = hms_query_params($con, $sql, [$appointmentId, $userId]);
		$appointment = $result ? hms_fetch_assoc($result) : null;

		if (!$appointment) {
			return ['table' => $table, 'appointment' => null, 'is_paid' => false, 'error' => 'Selected appointment is invalid.'];
		}

		$visitStatusNow = (string)($appointment['visitStatus'] ?? 'Scheduled');
		$isCancelled = ((int)($appointment['userStatus'] ?? 1) === 0 || (int)($appointment['doctorStatus'] ?? 1) === 0 || strcasecmp($visitStatusNow, 'Cancelled') === 0);
		$isTransferred = (stripos((string)($appointment['paymentStatus'] ?? ''), 'Transferred') !== false);
		$isPaid = in_array(strtolower((string)($appointment['paymentStatus'] ?? '')), ['paid', 'paid at hospital'], true)
			|| !empty($appointment['paymentRef'])
			|| !empty($appointment['paidAt']);

		if ($isCancelled || $isTransferred) {
			return ['table' => $table, 'appointment' => $appointment, 'is_paid' => $isPaid, 'error' => 'This appointment is not payable because it is cancelled or transferred.'];
		}

		if ((float)($appointment['consultancyFees'] ?? 0) <= 0) {
			return ['table' => $table, 'appointment' => $appointment, 'is_paid' => $isPaid, 'error' => 'This appointment does not have a valid payable amount.'];
		}

		if ($isPaid) {
			return ['table' => $table, 'appointment' => $appointment, 'is_paid' => true, 'error' => 'This appointment is already paid.'];
		}

		return ['table' => $table, 'appointment' => $appointment, 'is_paid' => false, 'error' => null];
	}
}

if (!function_exists('hms_razorpay_key_id')) {
	function hms_razorpay_key_id() {
		return trim((string)hms_env('RAZORPAY_KEY_ID', ''));
	}
}

if (!function_exists('hms_razorpay_key_secret')) {
	function hms_razorpay_key_secret() {
		return trim((string)hms_env('RAZORPAY_KEY_SECRET', ''));
	}
}

if (!function_exists('hms_razorpay_is_configured')) {
	function hms_razorpay_is_configured() {
		return hms_razorpay_key_id() !== '' && hms_razorpay_key_secret() !== '';
	}
}

if (!function_exists('hms_get_razorpay_client')) {
	function hms_get_razorpay_client() {
		if (!hms_razorpay_is_configured()) {
			return null;
		}

		if (!class_exists('\Razorpay\Api\Api')) {
			return null;
		}

		return new \Razorpay\Api\Api(hms_razorpay_key_id(), hms_razorpay_key_secret());
	}
}

if (!function_exists('hms_create_razorpay_order')) {
	function hms_create_razorpay_order($amountPaise, $currency, $receipt, array $notes = []) {
		$amountPaise = (int)$amountPaise;
		$currency = strtoupper(trim((string)$currency));
		$receipt = trim((string)$receipt);

		if (!hms_razorpay_is_configured()) {
			throw new \RuntimeException('Razorpay is not configured.');
		}

		if ($amountPaise < 100) {
			throw new \InvalidArgumentException('Amount must be at least 100 paise.');
		}

		if ($currency === '') {
			$currency = 'INR';
		}

		$payload = [
			'receipt' => $receipt,
			'amount' => $amountPaise,
			'currency' => $currency,
			'notes' => $notes,
		];

		$client = hms_get_razorpay_client();
		if ($client) {
			$order = $client->order->create($payload);
			return [
				'id' => (string)($order['id'] ?? ''),
				'amount' => (int)($order['amount'] ?? $amountPaise),
				'currency' => (string)($order['currency'] ?? $currency),
				'receipt' => (string)($order['receipt'] ?? $receipt),
			];
		}

		if (!function_exists('curl_init')) {
			throw new \RuntimeException('Razorpay SDK is unavailable and cURL is not enabled on the server.');
		}

		$ch = curl_init('https://api.razorpay.com/v1/orders');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, hms_razorpay_key_id() . ':' . hms_razorpay_key_secret());
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

		$responseBody = curl_exec($ch);
		$curlError = curl_error($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($responseBody === false) {
			throw new \RuntimeException($curlError !== '' ? $curlError : 'Unable to connect to Razorpay.');
		}

		$decoded = json_decode((string)$responseBody, true);
		if ($httpCode === 401) {
			throw new \RuntimeException('Razorpay authentication failed.');
		}

		if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded) || empty($decoded['id'])) {
			$errorMessage = '';
			if (is_array($decoded)) {
				$errorMessage = (string)($decoded['error']['description'] ?? $decoded['error']['reason'] ?? $decoded['message'] ?? '');
			}
			if ($errorMessage === '') {
				$errorMessage = 'Unable to create Razorpay order.';
			}

			throw new \RuntimeException($errorMessage);
		}

		return [
			'id' => (string)$decoded['id'],
			'amount' => (int)($decoded['amount'] ?? $amountPaise),
			'currency' => (string)($decoded['currency'] ?? $currency),
			'receipt' => (string)($decoded['receipt'] ?? $receipt),
		];
	}
}

if (!function_exists('hms_verify_razorpay_signature')) {
	function hms_verify_razorpay_signature($orderId, $paymentId, $signature) {
		$orderId = trim((string)$orderId);
		$paymentId = trim((string)$paymentId);
		$signature = trim((string)$signature);
		$secret = hms_razorpay_key_secret();

		if ($orderId === '' || $paymentId === '' || $signature === '' || $secret === '') {
			return false;
		}

		$generated = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
		return hash_equals($generated, $signature);
	}
}
?>
