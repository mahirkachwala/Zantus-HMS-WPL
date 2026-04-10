<?php
require_once __DIR__ . '/../include/config.php';
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

function bad($msg) {
    $_SESSION['reg_err'] = $msg;
    header('Location: registration.php');
    exit;
}

$full = trim($_POST['full_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$email = trim($_POST['email'] ?? '');
$pwd = $_POST['password'] ?? '';
$pwd2 = $_POST['password_again'] ?? '';

if($full === '' || $address === '' || $city === '' || $gender === '' || $email === '' || $pwd === '') {
    bad('Please fill all required fields.');
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    bad('Invalid email address.');
}
if($pwd !== $pwd2) {
    bad('Password and Confirm Password do not match.');
}
if(!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/', $pwd)) {
    bad('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.');
}

// use prepared statements for safety
$checkStmt = $con->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkStmt->store_result();
if($checkStmt->num_rows > 0) {
    $checkStmt->close();
    bad('Email already registered. Please login.');
}
$checkStmt->close();

$pwdHash = password_hash($pwd, PASSWORD_DEFAULT);
$insert = $con->prepare('INSERT INTO users (fullName,address,city,gender,email,password) VALUES (?,?,?,?,?,?)');
$insert->bind_param('ssssss', $full, $address, $city, $gender, $email, $pwdHash);
if($insert->execute()) {
    $_SESSION['reg_msg'] = 'Successfully Registered. You can login now.';
    $insert->close();
    header('Location: registration.php');
    exit;
} else {
    $insert->close();
    bad('Unable to create account. Try again later.');
}

?>