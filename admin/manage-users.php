<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();
if(isset($_GET['del']))
{
	hms_query($con,"delete from users where id = '".$_GET['id']."'");
	$_SESSION['msg']="data deleted !!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Manage Users</title>

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
	$page_title = 'Admin | Manage Users';
	$x_content = true;
	?>
	<?php include('include/header.php');?>
	<div class="row">
		<div class="col-md-12">
			<h3 class="page-heading">Manage Users</h3>
			<?php if(!empty($_SESSION['msg'])): ?>
				<div class="alert alert-info"><?php echo htmlentities($_SESSION['msg']);?></div>
				<?php $_SESSION['msg']=""; ?>
			<?php endif; ?>
			<table class="table table-hover data-table-wrap" id="sample-table-1">
				<thead>
					<tr>
						<th class="center">#</th>
						<th>Full Name</th>
						<th class="hidden-xs">Adress</th>
						<th>City</th>
						<th>Gender </th>
						<th>Email </th>
						<th>Creation Date </th>
						<th>Updation Date </th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$sql=hms_query($con,"select * from users");
					$cnt=1;
					while($row=hms_fetch_array($sql))
					{
						?>
						<tr>
							<td class="center"><?php echo $cnt;?>.</td>
							<td class="hidden-xs"><?php echo $row['fullName'];?></td>
							<td><?php echo $row['address'];?></td>
							<td><?php echo $row['city'];?>
						</td>
						<td><?php echo $row['gender'];?></td>
						<td><?php echo $row['email'];?></td>
						<td><?php echo $row['regDate'];?></td>
						<td><?php echo $row['updationDate'];?>
					</td>
					<td >
						<div class="visible-md visible-lg hidden-sm hidden-xs">
							<a href="manage-users.php?id=<?php echo $row['id']?>&del=delete" onClick="return confirm('Are you sure you want to delete?')" class="btn btn-cancel btn-sm">Delete</a>
						</div>
					</td>
				</tr>
				<?php
				$cnt=$cnt+1;
			}?>
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