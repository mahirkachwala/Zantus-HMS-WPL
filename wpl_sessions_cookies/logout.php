<?php
require_once __DIR__ . '/../include/config.php';
session_start();

// remove remember token for current user if cookie set
if(!empty($_COOKIE['remember'])) {
    $parts = explode(':', $_COOKIE['remember']);
    if(count($parts) === 2) {
        $selector = $parts[0];
        $del = $con->prepare('DELETE FROM auth_tokens WHERE selector = ?');
        $del->bind_param('s', $selector);
        $del->execute();
        $del->close();
    }
    setcookie('remember', '', time() - 3600, '/', '', false, true);
}

// log user logout in userlog table if available
if(!empty($_SESSION['id'])) {
    $uid = intval($_SESSION['id']);
    $logIdRes = hms_query($con, "SELECT id FROM userlog WHERE uid='$uid' ORDER BY id DESC LIMIT 1");
    $r = hms_fetch_assoc($logIdRes);
    if(!empty($r['id'])) {
        hms_query($con, "UPDATE userlog SET logout = NOW() WHERE id='".hms_escape($con,$r['id'])."'");
    }
}

// clear session
$_SESSION = array();
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// redirect to root login page in this lab folder
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
header("Location: http://$host$uri/index.php");
exit();

?>