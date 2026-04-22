<?php
require_once __DIR__ . '/razorpay.php';

if (!function_exists('hms_payu_merchant_key')) {
	function hms_payu_merchant_key() {
		return trim((string)hms_env('PAYU_MERCHANT_KEY', ''));
	}
}

if (!function_exists('hms_payu_merchant_salt')) {
	function hms_payu_merchant_salt() {
		return trim((string)hms_env('PAYU_MERCHANT_SALT', ''));
	}
}

if (!function_exists('hms_payu_base_url')) {
	function hms_payu_base_url() {
		$baseUrl = trim((string)hms_env('PAYU_BASE_URL', 'https://test.payu.in'));
		if ($baseUrl === '') {
			$baseUrl = 'https://test.payu.in';
		}

		return rtrim($baseUrl, '/');
	}
}

if (!function_exists('hms_payu_payment_url')) {
	function hms_payu_payment_url() {
		return hms_payu_base_url() . '/_payment';
	}
}

if (!function_exists('hms_payu_verify_url')) {
	function hms_payu_verify_url() {
		return hms_payu_base_url() . '/merchant/postservice?form=2';
	}
}

if (!function_exists('hms_payu_is_configured')) {
	function hms_payu_is_configured() {
		return hms_payu_merchant_key() !== '' && hms_payu_merchant_salt() !== '';
	}
}

if (!function_exists('hms_current_origin')) {
	function hms_current_origin() {
		$isHttps = false;
		$httpsValue = strtolower((string)($_SERVER['HTTPS'] ?? ''));
		if ($httpsValue !== '' && $httpsValue !== 'off') {
			$isHttps = true;
		}
		if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
			$isHttps = true;
		}

		$scheme = $isHttps ? 'https' : 'http';
		$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
		if ($host === '') {
			$host = trim((string)($_SERVER['SERVER_NAME'] ?? 'localhost'));
		}

		return $scheme . '://' . $host;
	}
}

if (!function_exists('hms_absolute_url')) {
	function hms_absolute_url($path) {
		$path = '/' . ltrim((string)$path, '/');
		return hms_current_origin() . $path;
	}
}

if (!function_exists('hms_payu_response_url')) {
	function hms_payu_response_url() {
		return hms_absolute_url('payu-response.php');
	}
}

if (!function_exists('hms_payu_clean_phone')) {
	function hms_payu_clean_phone($phone) {
		$digits = preg_replace('/\D+/', '', (string)$phone);
		if ($digits === '') {
			return '9999999999';
		}
		if (strlen($digits) > 10) {
			$digits = substr($digits, -10);
		}
		if (strlen($digits) < 10) {
			$digits = str_pad($digits, 10, '9');
		}

		return $digits;
	}
}

if (!function_exists('hms_payu_lookup_phone')) {
	function hms_payu_lookup_phone($con, $userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return '9999999999';
		}

		if (hms_table_exists($con, 'patients') && hms_column_exists($con, 'patients', 'patientPhone')) {
			$res = hms_query_params($con, "SELECT patientPhone FROM patients WHERE userId=$1 AND patientPhone IS NOT NULL AND patientPhone<>'' ORDER BY id DESC LIMIT 1", [$userId]);
			$row = $res ? hms_fetch_assoc($res) : null;
			if ($row && !empty($row['patientPhone'])) {
				return hms_payu_clean_phone($row['patientPhone']);
			}
		}

		if (hms_table_exists($con, 'tblpatient') && hms_column_exists($con, 'tblpatient', 'PatientContno')) {
			$res = hms_query_params($con, "SELECT PatientContno FROM tblpatient WHERE PatientContno IS NOT NULL AND PatientContno<>'' ORDER BY ID DESC LIMIT 1", []);
			$row = $res ? hms_fetch_assoc($res) : null;
			if ($row && !empty($row['PatientContno'])) {
				return hms_payu_clean_phone($row['PatientContno']);
			}
		}

		return '9999999999';
	}
}

if (!function_exists('hms_payu_format_amount')) {
	function hms_payu_format_amount($amount) {
		return number_format((float)$amount, 2, '.', '');
	}
}

if (!function_exists('hms_restore_user_session')) {
	function hms_restore_user_session($con, $userId) {
		$userId = (int)$userId;
		if ($userId <= 0) {
			return false;
		}

		$result = hms_query_params($con, "SELECT id, fullName, email FROM users WHERE id=$1 LIMIT 1", [$userId]);
		$user = $result ? hms_fetch_assoc($result) : null;
		if (!$user) {
			return false;
		}

		$_SESSION['id'] = (int)$user['id'];
		$_SESSION['user_id'] = (int)$user['id'];
		$_SESSION['fullName'] = (string)($user['fullName'] ?? '');
		$_SESSION['login'] = (string)($user['email'] ?? '');
		$_SESSION['last_activity'] = time();
		return true;
	}
}

if (!function_exists('hms_payu_generate_txnid')) {
	function hms_payu_generate_txnid($appointmentId, $userId) {
		$appointmentId = (int)$appointmentId;
		$userId = (int)$userId;
		$seed = strtoupper(bin2hex(random_bytes(4)));
		return 'HMS' . $appointmentId . 'U' . $userId . $seed;
	}
}

if (!function_exists('hms_payu_request_hash')) {
	function hms_payu_request_hash(array $fields) {
		$key = trim((string)($fields['key'] ?? ''));
		$txnid = trim((string)($fields['txnid'] ?? ''));
		$amount = hms_payu_format_amount($fields['amount'] ?? 0);
		$productinfo = trim((string)($fields['productinfo'] ?? ''));
		$firstname = trim((string)($fields['firstname'] ?? ''));
		$email = trim((string)($fields['email'] ?? ''));
		$udf1 = trim((string)($fields['udf1'] ?? ''));
		$udf2 = trim((string)($fields['udf2'] ?? ''));
		$udf3 = trim((string)($fields['udf3'] ?? ''));
		$udf4 = trim((string)($fields['udf4'] ?? ''));
		$udf5 = trim((string)($fields['udf5'] ?? ''));
		$salt = hms_payu_merchant_salt();

		if ($key === '' || $txnid === '' || $salt === '') {
			return '';
		}

		$hashString = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;
		return strtolower(hash('sha512', $hashString));
	}
}

if (!function_exists('hms_payu_verify_hash')) {
	function hms_payu_verify_hash($txnid) {
		$key = hms_payu_merchant_key();
		$salt = hms_payu_merchant_salt();
		$command = 'verify_payment';
		$txnid = trim((string)$txnid);

		if ($key === '' || $salt === '' || $txnid === '') {
			return '';
		}

		return strtolower(hash('sha512', $key . '|' . $command . '|' . $txnid . '|' . $salt));
	}
}

if (!function_exists('hms_verify_payu_response_hash')) {
	function hms_verify_payu_response_hash(array $payload) {
		$responseHash = strtolower(trim((string)($payload['hash'] ?? '')));
		$key = trim((string)($payload['key'] ?? ''));
		$status = trim((string)($payload['status'] ?? ''));
		$txnid = trim((string)($payload['txnid'] ?? ''));
		$amount = hms_payu_format_amount($payload['amount'] ?? 0);
		$productinfo = trim((string)($payload['productinfo'] ?? ''));
		$firstname = trim((string)($payload['firstname'] ?? ''));
		$email = trim((string)($payload['email'] ?? ''));
		$udf1 = trim((string)($payload['udf1'] ?? ''));
		$udf2 = trim((string)($payload['udf2'] ?? ''));
		$udf3 = trim((string)($payload['udf3'] ?? ''));
		$udf4 = trim((string)($payload['udf4'] ?? ''));
		$udf5 = trim((string)($payload['udf5'] ?? ''));
		$additionalCharges = trim((string)($payload['additional_charges'] ?? ''));
		$salt = hms_payu_merchant_salt();

		if ($responseHash === '' || $key === '' || $status === '' || $txnid === '' || $salt === '') {
			return false;
		}

		$reverse = $salt . '|' . $status . '||||||' . $udf5 . '|' . $udf4 . '|' . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
		if ($additionalCharges !== '') {
			$reverse = $additionalCharges . '|' . $reverse;
		}

		$generated = strtolower(hash('sha512', $reverse));
		return hash_equals($generated, $responseHash);
	}
}

if (!function_exists('hms_save_payu_pending_reference')) {
	function hms_save_payu_pending_reference($con, $appointmentId, $userId, $txnid) {
		$appointmentId = (int)$appointmentId;
		$userId = (int)$userId;
		$txnid = trim((string)$txnid);
		$table = hms_get_payment_appointment_table($con);

		if ($appointmentId <= 0 || $userId <= 0 || $txnid === '') {
			return false;
		}

		$_SESSION['payu_pending_txn_' . $appointmentId] = $txnid;
		return (bool)hms_query_params(
			$con,
			"UPDATE $table SET paymentRef=$1 WHERE id=$2 AND userId=$3 AND (paymentRef IS NULL OR paymentRef='' OR paymentStatus='Pending')",
			[$txnid, $appointmentId, $userId]
		);
	}
}

if (!function_exists('hms_payu_verify_transaction')) {
	function hms_payu_verify_transaction($txnid) {
		$txnid = trim((string)$txnid);
		if ($txnid === '' || !hms_payu_is_configured() || !function_exists('curl_init')) {
			return null;
		}

		$postFields = [
			'key' => hms_payu_merchant_key(),
			'command' => 'verify_payment',
			'var1' => $txnid,
			'hash' => hms_payu_verify_hash($txnid),
		];

		$ch = curl_init(hms_payu_verify_url());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: application/json',
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

		$responseBody = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($responseBody === false || $httpCode < 200 || $httpCode >= 300) {
			return null;
		}

		$decoded = json_decode((string)$responseBody, true);
		if (!is_array($decoded)) {
			return null;
		}

		if (isset($decoded['transaction_details'][$txnid]) && is_array($decoded['transaction_details'][$txnid])) {
			return $decoded['transaction_details'][$txnid];
		}
		if (isset($decoded['result'][0]) && is_array($decoded['result'][0])) {
			return $decoded['result'][0];
		}

		return null;
	}
}

if (!function_exists('hms_reconcile_payu_transaction')) {
	function hms_reconcile_payu_transaction($con, $appointmentId, $userId, $txnid) {
		$appointmentId = (int)$appointmentId;
		$userId = (int)$userId;
		$txnid = trim((string)$txnid);
		if ($appointmentId <= 0 || $userId <= 0 || $txnid === '') {
			return false;
		}

		$details = hms_payu_verify_transaction($txnid);
		if (!is_array($details)) {
			return false;
		}

		$status = strtolower(trim((string)($details['status'] ?? '')));
		if ($status !== 'success') {
			return false;
		}

		$table = hms_get_payment_appointment_table($con);
		$payuId = trim((string)($details['mihpayid'] ?? ''));
		$transactionRef = $payuId !== '' ? $payuId : $txnid;
		$paidAt = trim((string)($details['addedon'] ?? ''));
		if ($paidAt === '') {
			$paidAt = date('Y-m-d H:i:s');
		}

		$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
		$appointment = $context['appointment'] ?? null;
		if (!$appointment) {
			return false;
		}

		$amount = (float)($appointment['consultancyFees'] ?? 0);
		$updateOk = (bool)hms_query_params(
			$con,
			"UPDATE $table SET paymentStatus='Paid', paymentRef=$1, paidAt=$2 WHERE id=$3 AND userId=$4",
			[$transactionRef, $paidAt, $appointmentId, $userId]
		);
		$logOk = $updateOk && hms_record_payment_transaction(
			$con,
			$appointmentId,
			$userId,
			$amount,
			'PayU',
			$transactionRef,
			'Paid',
			$paidAt
		);

		if ($updateOk && $logOk) {
			unset($_SESSION['payu_pending_txn_' . $appointmentId]);
			return [
				'transaction_ref' => $transactionRef,
				'paid_at' => $paidAt,
				'status' => 'Paid',
			];
		}

		return false;
	}
}

if (!function_exists('hms_build_payu_checkout_request')) {
	function hms_build_payu_checkout_request($con, $appointmentId, $userId) {
		$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
		if (!empty($context['error'])) {
			throw new \RuntimeException((string)$context['error']);
		}

		$appointment = $context['appointment'] ?? null;
		if (!$appointment) {
			throw new \RuntimeException('Selected appointment is invalid.');
		}

		if (!hms_payu_is_configured()) {
			throw new \RuntimeException('PayU configuration is missing.');
		}

		$userId = (int)$userId;
		$appointmentId = (int)$appointmentId;
		$fullName = trim((string)($_SESSION['fullName'] ?? 'Patient'));
		$email = trim((string)($_SESSION['login'] ?? ''));
		$phone = hms_payu_lookup_phone($con, $userId);
		$amount = hms_payu_format_amount($appointment['consultancyFees'] ?? 0);
		$txnid = hms_payu_generate_txnid($appointmentId, $userId);
		$productinfo = 'Appointment Fee #' . $appointmentId;
		$responseUrl = hms_payu_response_url();

		$fields = [
			'key' => hms_payu_merchant_key(),
			'txnid' => $txnid,
			'amount' => $amount,
			'productinfo' => $productinfo,
			'firstname' => $fullName !== '' ? $fullName : 'Patient',
			'email' => $email !== '' ? $email : 'patient@example.com',
			'phone' => $phone,
			'surl' => $responseUrl,
			'furl' => $responseUrl,
			'udf1' => (string)$appointmentId,
			'udf2' => (string)$userId,
			'udf3' => '',
			'udf4' => '',
			'udf5' => '',
		];
		$fields['hash'] = hms_payu_request_hash($fields);

		if ($fields['hash'] === '') {
			throw new \RuntimeException('Unable to generate PayU request hash.');
		}

		hms_save_payu_pending_reference($con, $appointmentId, $userId, $txnid);

		return [
			'action' => hms_payu_payment_url(),
			'fields' => $fields,
			'appointment' => $appointment,
		];
	}
}
?>
