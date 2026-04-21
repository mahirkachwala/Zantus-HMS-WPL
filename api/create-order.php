<?php
require_once __DIR__ . '/../include/session.php';
hms_session_start();
require_once __DIR__ . '/../include/razorpay.php';

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
	hms_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$userId = hms_api_user_id();
if ($userId <= 0) {
	hms_json_response(401, ['success' => false, 'message' => 'Authentication required.']);
}

if (!hms_razorpay_is_configured()) {
	hms_json_response(500, ['success' => false, 'message' => 'Razorpay is not configured.']);
}

$payload = hms_json_input();
$appointmentId = (int)($payload['appointment_id'] ?? 0);
$currency = strtoupper(trim((string)($payload['currency'] ?? 'INR')));
if ($currency === '') {
	$currency = 'INR';
}

$context = hms_get_payable_appointment_context($con, $appointmentId, $userId);
if (!empty($context['error'])) {
	hms_json_response(400, ['success' => false, 'message' => $context['error']]);
}

$appointment = $context['appointment'];
$amountRupees = (float)($appointment['consultancyFees'] ?? 0);
$amountPaise = (int)round($amountRupees * 100);
if ($amountPaise < 100) {
	hms_json_response(400, ['success' => false, 'message' => 'Amount must be at least 100 paise.']);
}

$receipt = 'appt_' . $appointmentId . '_u_' . $userId . '_' . time();

try {
	$order = hms_create_razorpay_order(
		$amountPaise,
		$currency,
		$receipt,
		[
			'appointment_id' => (string)$appointmentId,
			'user_id' => (string)$userId,
		]
	);

	hms_json_response(200, [
		'success' => true,
		'order_id' => (string)($order['id'] ?? ''),
		'amount' => (int)($order['amount'] ?? $amountPaise),
		'currency' => (string)($order['currency'] ?? $currency),
		'receipt' => (string)($order['receipt'] ?? $receipt),
	]);
} catch (\Razorpay\Api\Errors\Error $e) {
	$code = (int)$e->getCode();
	if ($code === 401) {
		hms_json_response(401, ['success' => false, 'message' => 'Razorpay authentication failed.']);
	}
	hms_json_response(500, ['success' => false, 'message' => 'Unable to create Razorpay order.']);
} catch (\Throwable $e) {
	$message = trim((string)$e->getMessage());
	if ($message === '') {
		$message = 'Unable to create Razorpay order.';
	}

	if (stripos($message, 'authentication') !== false) {
		hms_json_response(401, ['success' => false, 'message' => $message]);
	}

	hms_json_response(500, ['success' => false, 'message' => $message]);
}
?>
