<?php
// demo_flow.php
// Performs a quick HTTP demo: registration -> login -> book appointment
// Usage (CLI): php demo_flow.php

$base = "http://localhost/hospital_hms";
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hms_demo_cookie.txt';
if (file_exists($cookieFile)) { @unlink($cookieFile); }

function curl_get($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HMS-Demo/1.0');
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        throw new Exception('GET error: ' . $err);
    }
    return $res;
}

function curl_post($url, $data, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_USERAGENT, 'HMS-Demo/1.0');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        throw new Exception('POST error: ' . $err);
    }
    return $res;
}

echo "Starting demo flow...\n";

// 1) Register new user
$rand = time();
$email = "demo_user_{$rand}@example.test";
$password = 'Demo@1234';
echo "Registering user: $email\n";
$regUrl = $base . '/registration.php';
$regData = [
    'full_name' => 'Demo User',
    'address' => 'Demo Address',
    'city' => 'Demo City',
    'gender' => 'male',
    'email' => $email,
    'password' => $password,
    'password_again' => $password,
    'submit' => 'Create Account'
];
$regResp = curl_post($regUrl, $regData, $cookieFile);
if (stripos($regResp, 'Successfully Registered') !== false || stripos($regResp, 'Successfully Registered. You can login now') !== false) {
    echo "Registration succeeded.\n";
} else {
    // It may still succeed if page redirects; also check if email exists
    if (stripos($regResp, 'Email already registered') !== false) {
        echo "Registration: email already registered (continuing).\n";
    } else {
        echo "Registration warning: response didn't contain success marker. Continuing, response snippet:\n";
        echo substr($regResp, 0, 400) . "\n";
    }
}

// 2) Login
echo "Logging in...\n";
$loginUrl = $base . '/index.php';
$loginData = [
    'username' => $email,
    'password' => $password,
    'submit' => 'Login'
];
$loginResp = curl_post($loginUrl, $loginData, $cookieFile);
if (stripos($loginResp, 'dashboard.php') !== false || stripos($loginResp, 'Logout') !== false || stripos($loginResp, 'Your Dashboard') !== false) {
    echo "Login appears successful (redirect to dashboard).\n";
} elseif (stripos($loginResp, 'Invalid username or password') !== false) {
    throw new Exception('Login failed: invalid credentials');
} else {
    echo "Login response received (unable to strictly verify).\n";
}

// 3) Get book-appointment page to pick specialization
echo "Fetching book-appointment page to obtain specializations...\n";
$bookPage = curl_get($base . '/book-appointment.php', $cookieFile);
// Find first specialization option value
preg_match_all('/<select[^>]*name=["\']Doctorspecialization["\'][^>]*>(.*?)<\/select>/is', $bookPage, $selMatch);
$specValue = null;
if (!empty($selMatch[1][0])) {
    $optionsHtml = $selMatch[1][0];
    if (preg_match('/<option[^>]*value=["\']?([^"'>]+)["\']?[^>]*>\s*([^<]+)\s*<\/option>/is', $optionsHtml, $opt)) {
        $specValue = $opt[1];
        echo "Chosen specialization value: $specValue\n";
    }
}
if (!$specValue) {
    throw new Exception('No specialization option found on book-appointment page');
}

// 4) Call get_doctor.php with specializationid to get doctors list
echo "Requesting doctors list for specialization...\n";
$doctorsResp = curl_post($base . '/get_doctor.php', ['specializationid' => $specValue], $cookieFile);
// Parse first doctor option value
if (preg_match('/<option[^>]*value=["\']?([^"'>]+)["\']?[^>]*>\s*([^<]+)\s*<\/option>/is', $doctorsResp, $opt)) {
    $doctorId = $opt[1];
    echo "Chosen doctor id: $doctorId\n";
} else {
    throw new Exception('No doctor option returned for specialization');
}

// 5) Get fees for doctor
echo "Requesting fees for doctor id $doctorId...\n";
$feesResp = curl_post($base . '/get_doctor.php', ['docid' => $doctorId], $cookieFile);
if (preg_match('/<option[^>]*value=["\']?([^"'>]+)["\']?[^>]*>\s*([^<]+)\s*<\/option>/is', $feesResp, $fopt)) {
    $feeValue = $fopt[1];
    echo "Chosen fee value: $feeValue\n";
} else {
    // sometimes the endpoint returns plain numeric or HTML; fallback to regex digits
    if (preg_match('/(\d{2,6})/', $feesResp, $fnum)) {
        $feeValue = $fnum[1];
        echo "Fallback fee: $feeValue\n";
    } else {
        throw new Exception('Unable to detect fee value for doctor');
    }
}

// 6) Book appointment (set date 2 days from now and time 10:00)
$appDate = date('Y-m-d', strtotime('+2 days'));
$appTime = '10:00';
echo "Booking appointment on $appDate at $appTime...\n";
$bookData = [
    'Doctorspecialization' => $specValue,
    'doctor' => $doctorId,
    'fees' => $feeValue,
    'appdate' => $appDate,
    'apptime' => $appTime,
    'paymentOption' => 'BookOnly',
    'submit' => 'Continue'
];
$bookResp = curl_post($base . '/book-appointment.php', $bookData, $cookieFile);
if (stripos($bookResp, 'appointments.php') !== false || stripos($bookResp, 'booked') !== false || stripos($bookResp, 'Your appointment was booked successfully') !== false) {
    echo "Appointment booking appears successful.\n";
} else {
    echo "Booking response snippet:\n";
    echo substr($bookResp, 0, 800) . "\n";
    echo "Booking may have failed; check server logs or DB.\n";
}

echo "Demo flow complete. Cookie file: $cookieFile\n";

?>
