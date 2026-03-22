<?php
session_start();
include('include/config.php');
$doctorId = (int)($_SESSION['doctor_id'] ?? $_SESSION['id'] ?? 0);
date_default_timezone_set('Asia/Kolkata');
$ldate=date( 'd-m-Y h:i:s A', time () );
if($doctorId > 0) {
	mysqli_query($con,"UPDATE doctorslog  SET logout = '$ldate' WHERE uid = '$doctorId' ORDER BY id DESC LIMIT 1");
}
unset($_SESSION['dlogin'], $_SESSION['id'], $_SESSION['doctor_id'], $_SESSION['doctorName']);
$_SESSION['errmsg']="You have successfully logout";
?>
<script language="javascript">
	document.location="index.php";
</script>
