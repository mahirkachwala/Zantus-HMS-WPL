<?php
session_start();
include('include/config.php');
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
date_default_timezone_set('Asia/Kolkata');
$ldate=date( 'd-m-Y h:i:s A', time () );
if($userId > 0) {
	mysqli_query($con,"UPDATE userlog  SET logout = '$ldate' WHERE uid = '$userId' ORDER BY id DESC LIMIT 1");
}
unset($_SESSION['login'], $_SESSION['id'], $_SESSION['user_id'], $_SESSION['fullName']);
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
	document.location="index.php";
</script>
