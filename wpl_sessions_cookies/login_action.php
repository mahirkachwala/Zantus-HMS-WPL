<?php
require_once __DIR__ . '/../include/config.php';
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$email = trim($_POST['username'] ?? '');
$pwd = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if($email === '' || $pwd === '') {
    $_SESSION['errmsg'] = 'Missing credentials';
    header('Location: index.php');
    exit;
}

// fetch user by email
$stmt = $con->prepare('SELECT id, fullName, email, password FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if(!$user) {
    // invalid
    $_SESSION['login'] = $email;
    $uip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    hms_query($con, "insert into userlog(username,userip,status) values('".hms_escape($con,$email)."','$uip',0)");
    $_SESSION['errmsg'] = 'Invalid username or password';
    header('Location: index.php');
    exit;
}

$stored = $user['password'];
$verified = false;
if(password_verify($pwd, $stored)) {
    $verified = true;
} else {
    // fallback: if plaintext stored (legacy), check and re-hash
    if(hash_equals($stored, $pwd)) {
        $verified = true;
        $newHash = password_hash($pwd, PASSWORD_DEFAULT);
        $uup = $con->prepare('UPDATE users SET password = ? WHERE id = ?');
        $uup->bind_param('si', $newHash, $user['id']);
        $uup->execute();
        $uup->close();
    }
}

if(!$verified) {
    $_SESSION['login'] = $email;
    $uip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    hms_query($con, "insert into userlog(username,userip,status) values('".hms_escape($con,$email)."','$uip',0)");
    $_SESSION['errmsg'] = 'Invalid username or password';
    header('Location: index.php');
    exit;
}

// success: regenerate session id and set session values
session_regenerate_id(true);
$_SESSION['login'] = $user['email'];
$_SESSION['fullName'] = $user['fullName'];
$_SESSION['id'] = $user['id'];
$_SESSION['user_id'] = $user['id'];

// log success
$host = $_SERVER['HTTP_HOST'] ?? '';
$uip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$status = 1;
hms_query($con, "insert into userlog(uid,username,userip,status) values('".$_SESSION['id']."','".hms_escape($con,$_SESSION['login'])."','$uip',$status)");

// handle remember-me: create token, store hash in auth_tokens
if($remember) {
    // ensure auth_tokens table exists (basic)
    $con->query("CREATE TABLE IF NOT EXISTS `auth_tokens` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `selector` VARCHAR(64) NOT NULL,
        `token_hash` VARCHAR(128) NOT NULL,
        `expires` DATETIME NOT NULL,
        INDEX (`selector`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $selector = bin2hex(random_bytes(9));
    $token = bin2hex(random_bytes(33));
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 60*60*24*30); // 30 days

    $insert = $con->prepare('INSERT INTO auth_tokens (user_id, selector, token_hash, expires) VALUES (?, ?, ?, ?)');
    $insert->bind_param('isss', $_SESSION['id'], $selector, $tokenHash, $expires);
    $insert->execute();
    $insert->close();

    // set cookie: selector:token
    $cookieVal = $selector . ':' . $token;
    setcookie('remember', $cookieVal, time() + 60*60*24*30, '/', '', false, true); // secure=false for local
}

// redirect to dashboard (same-site path)
$extra = 'dashboard.php';
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
header("Location: http://$host$uri/$extra");
exit;

?>