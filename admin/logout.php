<?php
session_start();
unset($_SESSION['alogin'], $_SESSION['admin_id'], $_SESSION['login'], $_SESSION['id']);
?>
<script language="javascript">
	document.location="index.php";
</script>
