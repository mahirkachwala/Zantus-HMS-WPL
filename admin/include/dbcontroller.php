<?php
$DB_host = "localhost";
$DB_port = "3306";
$DB_user = "root";
$DB_pass = "";
$DB_name = "hms";
try
{
	$dsn = "mysql:host={$DB_host};port={$DB_port};dbname={$DB_name};charset=utf8";
	$DB_con = new PDO($dsn, $DB_user, $DB_pass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]);
}
catch(PDOException $e)
{
	// For debugging, you can echo or log $e->getMessage();
	die('Database connection failed: ' . $e->getMessage());
}
?>
