<?php
require_once __DIR__ . '/../include/config.php';
// Creates a test user for the lab if it doesn't already exist.
// Password: Test@1234
$email = 'testuser@example.com';
$password_plain = 'Test@1234';
$fullName = 'Test User';
$address = '123 Test St';
$city = 'LabCity';
$gender = 'other';

try {
    $stmt = $con->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "User already exists: $email<br>";
        echo '<a href="index.php">Go to Lab Login</a>';
        $stmt->close();
        exit;
    }
    $stmt->close();

    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);
    $ins = $con->prepare('INSERT INTO users (fullName,address,city,gender,email,password) VALUES (?,?,?,?,?,?)');
    $ins->bind_param('ssssss', $fullName, $address, $city, $gender, $email, $password_hash);
    if ($ins->execute()) {
        echo "Test user created successfully.<br>";
        echo "Email: <strong>$email</strong><br>Password: <strong>$password_plain</strong><br>";
        echo '<p><a href="index.php">Open Lab Login</a></p>';
    } else {
        echo 'Failed to create test user. Error: ' . htmlspecialchars($con->error);
    }
    $ins->close();
} catch (Exception $e) {
    echo 'Exception: ' . htmlspecialchars($e->getMessage());
}

?>