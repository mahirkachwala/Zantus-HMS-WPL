<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

hms_query($con, "CREATE TABLE IF NOT EXISTS feedback_entries (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	portal_type VARCHAR(20) NOT NULL,
	user_id INT DEFAULT NULL,
	doctor_id INT DEFAULT NULL,
	name VARCHAR(150) NOT NULL,
	email VARCHAR(150) NOT NULL,
	rating TINYINT DEFAULT NULL,
	feedback_text TEXT NOT NULL,
	status VARCHAR(20) NOT NULL DEFAULT 'New',
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (isset($_GET['mark']) && isset($_GET['id'])) {
	$id = (int)$_GET['id'];
	$status = ($_GET['mark'] === 'reviewed') ? 'Reviewed' : 'New';
	hms_query($con, "UPDATE feedback_entries SET status='".hms_escape($con, $status)."' WHERE id='$id'");
	header('location:feedbacks.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Feedbacks</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title='Admin | Feedbacks'; $x_content=true; include('include/header.php');?>
<div class="row">
	<div class="col-md-12">
		<div class="x_panel">
			<div class="x_title"><h2>Portal Feedbacks</h2><div class="clearfix"></div></div>
			<div class="x_content">
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>#</th><th>Portal</th><th>Name</th><th>Email</th><th>Rating</th><th>Feedback</th><th>Status</th><th>Created</th><th>Action</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cnt=1;
					$q = hms_query($con, "SELECT * FROM feedback_entries ORDER BY id DESC");
					if($q) while($r=hms_fetch_assoc($q)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo htmlentities(ucfirst($r['portal_type'])); ?></td>
						<td><?php echo htmlentities($r['name']); ?></td>
						<td><?php echo htmlentities($r['email']); ?></td>
						<td><?php echo (int)($r['rating'] ?? 0) > 0 ? (int)$r['rating'].'/5' : '-'; ?></td>
						<td style="max-width:420px; white-space:pre-wrap;"><?php echo htmlentities($r['feedback_text']); ?></td>
						<td><?php echo htmlentities($r['status']); ?></td>
						<td><?php echo htmlentities($r['created_at']); ?></td>
						<td>
							<a class="btn btn-primary btn-xs" href="feedbacks.php?id=<?php echo (int)$r['id']; ?>&mark=reviewed">Mark Reviewed</a>
						</td>
					</tr>
					<?php $cnt++; } ?>
					<?php if($cnt===1): ?><tr><td colspan="9" class="text-center text-muted">No feedback entries found.</td></tr><?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php include('include/footer.php');?>
<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../vendors/nprogress/nprogress.js"></script>
<script src="../assets/js/custom.min.js"></script>
</body>
</html>
