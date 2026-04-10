<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$doctorId = (int)($_SESSION['id'] ?? 0);
$name = '';
$email = '';
if ($doctorId > 0) {
	$dq = hms_query($con, "SELECT doctorName,docEmail FROM doctors WHERE id='$doctorId' LIMIT 1");
	if ($dq && hms_num_rows($dq) > 0) {
		$dr = hms_fetch_assoc($dq);
		$name = (string)($dr['doctorName'] ?? '');
		$email = (string)($dr['docEmail'] ?? '');
	}
}

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

$msg = '';
$err = '';
if (isset($_POST['submit'])) {
	$nameIn = hms_escape($con, trim($_POST['name'] ?? ''));
	$emailIn = hms_escape($con, trim($_POST['email'] ?? ''));
	$ratingIn = (int)($_POST['rating'] ?? 0);
	$textIn = hms_escape($con, trim($_POST['feedback_text'] ?? ''));

	if ($nameIn === '' || $emailIn === '' || $textIn === '') {
		$err = 'Please fill all required fields.';
	} else {
		if ($ratingIn < 1 || $ratingIn > 5) { $ratingIn = 0; }
		$ratingSql = ($ratingIn > 0) ? "'".$ratingIn."'" : "NULL";
		$ins = hms_query($con, "INSERT INTO feedback_entries(portal_type,user_id,doctor_id,name,email,rating,feedback_text,status,created_at) VALUES('doctor',NULL,'".(int)$doctorId."','$nameIn','$emailIn',$ratingSql,'$textIn','New',NOW())");
		if ($ins) {
			$msg = 'Thank you for your feedback.';
		} else {
			$err = 'Unable to submit feedback. Please try again.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Feedback</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title = 'Doctor | Feedback'; $x_content = true; include('include/header.php');?>
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="x_panel">
			<div class="x_title"><h2>Feedback</h2><div class="clearfix"></div></div>
			<div class="x_content">
				<?php if($msg!==''): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
				<?php if($err!==''): ?><div class="alert alert-danger"><?php echo htmlentities($err); ?></div><?php endif; ?>
				<form method="post">
					<div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="<?php echo htmlentities($_POST['name'] ?? $name); ?>" required></div>
					<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="<?php echo htmlentities($_POST['email'] ?? $email); ?>" required></div>
					<div class="form-group"><label>Rating (1-5)</label><select name="rating" class="form-control"><option value="">Select</option><?php for($i=1;$i<=5;$i++): ?><option value="<?php echo $i; ?>" <?php echo ((int)($_POST['rating'] ?? 0)===$i)?'selected':''; ?>><?php echo $i; ?></option><?php endfor; ?></select></div>
					<div class="form-group"><label>Feedback *</label><textarea name="feedback_text" class="form-control" rows="5" required><?php echo htmlentities($_POST['feedback_text'] ?? ''); ?></textarea></div>
					<button type="submit" name="submit" class="btn btn-primary">Submit Feedback</button>
				</form>
			</div>
		</div>
		<div class="x_panel">
			<div class="x_title"><h2>My Feedback Status</h2><div class="clearfix"></div></div>
			<div class="x_content table-responsive">
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>#</th><th>Rating</th><th>Feedback</th><th>Status</th><th>Submitted</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cnt=1;
					$fq = hms_query($con, "SELECT * FROM feedback_entries WHERE portal_type='doctor' AND doctor_id='$doctorId' ORDER BY id DESC");
					if($fq) while($r=hms_fetch_assoc($fq)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo ((int)($r['rating'] ?? 0) > 0) ? (int)$r['rating'].'/5' : '-'; ?></td>
						<td style="max-width:500px; white-space:pre-wrap;"><?php echo htmlentities($r['feedback_text']); ?></td>
						<td><?php echo htmlentities($r['status']); ?></td>
						<td><?php echo htmlentities($r['created_at']); ?></td>
					</tr>
					<?php $cnt++; } ?>
					<?php if($cnt===1): ?><tr><td colspan="5" class="text-center text-muted">No feedback submitted yet.</td></tr><?php endif; ?>
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
