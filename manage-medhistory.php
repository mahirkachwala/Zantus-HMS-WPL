<?php
session_start();
include('include/checklogin.php');
check_login();
$_SESSION['msg'] = 'This legacy page is disabled.';
header('location:appointment-history.php');
exit();
