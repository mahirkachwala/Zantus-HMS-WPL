<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
include('include/config.php');
include('include/checklogin.php');
check_login();
$_SESSION['msg'] = 'This legacy page is disabled.';
header('location:appointment-history.php');
exit();
