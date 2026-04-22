<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function tableExists($con, $tableName) {
	$check = hms_query($con, "SHOW TABLES LIKE '" . hms_escape($con, $tableName) . "'");
	return ($check && hms_num_rows($check) > 0);
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Admin | View Patients</title>

		<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
		<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
		<link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
		<link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
		<link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
		<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		<link href="../assets/css/custom.min.css" rel="stylesheet">
	</head>
	<body>
		<div id="app">		
<?php include('include/sidebar.php');?>
<div class="app-content">
<?php include('include/header.php');?>
<div class="main-content" >
<div class="wrap-content container" id="container">

<section id="page-title">
<div class="row">
<div class="col-sm-8">
<h1 class="mainTitle">Admin | View Patients</h1>
</div>
<ol class="breadcrumb">
<li>
<span>Admin</span>
</li>
<li class="active">
<span>View Patients</span>
</li>
</ol>
</div>
</section>
<div class="container-fluid container-fullw bg-white">
<div class="row">
<div class="col-md-12">
<h4 class="tittle-w3-agileits mb-4">Between dates reports</h4>
<?php
$fdate=$_POST['fromdate'];
$tdate=$_POST['todate'];

?>
<h5 align="center" style="color:blue">Report from <?php echo $fdate?> to <?php echo $tdate?></h5>

<table class="table table-hover" id="sample-table-1">
<thead>
<tr>
<th class="center">#</th>
<th>Patient Name</th>
<th>Patient Contact Number</th>
<th>Patient Gender </th>
<th>Patient Type</th>
<th>Creation Date </th>
<th>Updation Date / Doctor</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php

if (tableExists($con, 'patients')) {
	$sql=hms_query($con,"SELECT p.*, d.doctorName FROM patients p LEFT JOIN doctors d ON d.id=p.doctorId WHERE DATE(COALESCE(p.createdAt, p.admissionDate)) BETWEEN '$fdate' AND '$tdate' ORDER BY p.id DESC");
} else {
	$sql=hms_query($con,"SELECT * FROM tblpatient WHERE DATE(CreationDate) BETWEEN '$fdate' AND '$tdate' ORDER BY ID DESC");
}
$cnt=1;
while($row=hms_fetch_array($sql))
{
?>
<tr>
<td class="center"><?php echo $cnt;?>.</td>
<td class="hidden-xs"><?php echo htmlentities($row['patientName'] ?? $row['PatientName'] ?? '');?></td>
<td><?php echo htmlentities($row['patientPhone'] ?? $row['PatientContno'] ?? '');?></td>
<td><?php echo htmlentities($row['patientGender'] ?? $row['PatientGender'] ?? '');?></td>
<td><?php echo ucfirst(htmlentities($row['patientType'] ?? 'consultancy'));?></td>
<td><?php echo htmlentities($row['createdAt'] ?? $row['CreationDate'] ?? $row['admissionDate'] ?? '');?></td>
<td><?php echo htmlentities($row['updatedAt'] ?? $row['UpdationDate'] ?? $row['doctorName'] ?? '--');?>
</td>
<td>

<a href="view-patient.php?viewid=<?php echo (int)($row['id'] ?? $row['ID'] ?? 0);?>"><i class="fa fa-eye"></i></a>

</td>
</tr>
<?php 
$cnt=$cnt+1;
 }?></tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
</div>

	<?php include('include/footer.php');?>



	<?php include('include/setting.php');?>


		</div>

		<script src="../vendors/jquery/dist/jquery.min.js"></script>
		<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
		<script src="../assets/js/custom.min.js"></script>


	</body>
</html>
