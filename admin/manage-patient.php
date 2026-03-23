<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

function tableExists($con, $tableName) {
	$check = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tableName) . "'");
	return ($check && mysqli_num_rows($check) > 0);
}

$usePatientsTable = tableExists($con, 'patients');

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
	<style>
		.page-heading {
			font-size: 22px;
			font-weight: 700;
			color: #1e3a8a;
			margin-bottom: 14px;
		}
		.data-table-wrap {
			background: #fff;
			border: 1px solid #e6ebf5;
			border-radius: 10px;
			overflow: hidden;
		}
		.data-table-wrap td, .data-table-wrap th {
			font-size: 14px;
		}
	</style>
</head>
<body class="nav-md">
	<?php
	$page_title = 'Admin | View Patients';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Manage Patients</h3>

			<table class="table table-hover data-table-wrap" id="sample-table-1">
				<thead>
					<tr>
						<th class="center">#</th>
						<th>Patient Name</th>
						<th>Email</th>
						<th>Patient Contact Number</th>
						<th>Patient Gender </th>
						<th>Age</th>
						<th>Type</th>
						<th>Status</th>
						<th>Doctor</th>
						<th>Creation Date </th>
						<th>Updation Date / Discharge</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$cnt=1;
					if($usePatientsTable) {
						$sql=mysqli_query($con,"SELECT p.*, d.doctorName, u.fullName as userName FROM patients p LEFT JOIN doctors d ON d.id=p.doctorId LEFT JOIN users u ON u.id=p.userId ORDER BY p.id DESC");
						while($row=mysqli_fetch_array($sql)) {
							?>
							<tr>
								<td class="center"><?php echo $cnt;?>.</td>
								<td class="hidden-xs"><?php echo htmlentities($row['patientName'] ?? $row['userName'] ?? ''); ?></td>
								<td><?php echo htmlentities($row['patientEmail'] ?? ''); ?></td>
								<td><?php echo htmlentities($row['patientPhone'] ?? ''); ?></td>
								<td><?php echo htmlentities($row['patientGender'] ?? ''); ?></td>
								<td><?php echo htmlentities($row['patientAge'] ?? ''); ?></td>
								<td><?php echo ucfirst(htmlentities($row['patientType'] ?? 'consultancy')); ?></td>
								<td><?php echo htmlentities($row['status'] ?? 'Active'); ?></td>
								<td><?php echo htmlentities($row['doctorName'] ?? '--'); ?></td>
								<td><?php echo htmlentities($row['createdAt'] ?? $row['admissionDate'] ?? '--'); ?></td>
								<td><?php echo htmlentities($row['updatedAt'] ?? $row['dischargeDate'] ?? '--'); ?></td>
								<td><a href="view-patient.php?viewid=<?php echo (int)$row['id'];?>" class="btn btn-primary btn-sm">View</a></td>
							</tr>
							<?php
							$cnt=$cnt+1;
						}
					} else {
						$sql=mysqli_query($con,"select * from tblpatient");
						while($row=mysqli_fetch_array($sql)) {
							?>
							<tr>
								<td class="center"><?php echo $cnt;?>.</td>
								<td class="hidden-xs"><?php echo $row['PatientName'];?></td>
								<td><?php echo $row['PatientEmail'];?></td>
								<td><?php echo $row['PatientContno'];?></td>
								<td><?php echo $row['PatientGender'];?></td>
								<td><?php echo $row['PatientAge'];?></td>
								<td>Consultancy</td>
								<td>Active</td>
								<td>--</td>
								<td><?php echo $row['CreationDate'];?></td>
								<td><?php echo $row['UpdationDate'];?></td>
								<td><a href="view-patient.php?viewid=<?php echo $row['ID'];?>" class="btn btn-primary btn-sm">View</a></td>
							</tr>
							<?php
							$cnt=$cnt+1;
						}
					}
					if($cnt===1) {
						echo '<tr><td colspan="12" class="text-center text-muted">No patients found.</td></tr>';
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php include('include/footer.php');?>

	<script src="../vendors/jquery/dist/jquery.min.js"></script>

	<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

	<script src="../vendors/fastclick/lib/fastclick.js"></script>

	<script src="../vendors/nprogress/nprogress.js"></script>

	<script src="../vendors/Chart.js/dist/Chart.min.js"></script>

	<script src="../vendors/gauge.js/dist/gauge.min.js"></script>

	<script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>

	<script src="../vendors/iCheck/icheck.min.js"></script>

	<script src="../vendors/skycons/skycons.js"></script>

	<script src="../vendors/Flot/jquery.flot.js"></script>
	<script src="../vendors/Flot/jquery.flot.pie.js"></script>
	<script src="../vendors/Flot/jquery.flot.time.js"></script>
	<script src="../vendors/Flot/jquery.flot.stack.js"></script>
	<script src="../vendors/Flot/jquery.flot.resize.js"></script>

	<script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
	<script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
	<script src="../vendors/flot.curvedlines/curvedLines.js"></script>

	<script src="../vendors/DateJS/build/date.js"></script>

	<script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
	<script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
	<script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>

	<script src="../vendors/moment/min/moment.min.js"></script>
	<script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

	<script src="../assets/js/custom.min.js"></script>
</body>