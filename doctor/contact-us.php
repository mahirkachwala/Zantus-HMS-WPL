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

hms_query($con, "CREATE TABLE IF NOT EXISTS contact_queries (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	portal_type VARCHAR(20) NOT NULL,
	user_id INT DEFAULT NULL,
	doctor_id INT DEFAULT NULL,
	name VARCHAR(150) NOT NULL,
	email VARCHAR(150) NOT NULL,
	phone VARCHAR(30) DEFAULT NULL,
	subject VARCHAR(200) NOT NULL,
	message TEXT NOT NULL,
	status VARCHAR(20) NOT NULL DEFAULT 'New',
	admin_note TEXT DEFAULT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

hms_query($con, "CREATE TABLE IF NOT EXISTS contact_query_history (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	original_query_id INT NOT NULL,
	portal_type VARCHAR(20) NOT NULL,
	user_id INT DEFAULT NULL,
	doctor_id INT DEFAULT NULL,
	name VARCHAR(150) NOT NULL,
	email VARCHAR(150) NOT NULL,
	phone VARCHAR(30) DEFAULT NULL,
	subject VARCHAR(200) NOT NULL,
	message TEXT NOT NULL,
	final_status VARCHAR(20) NOT NULL DEFAULT 'Closed',
	admin_note TEXT NOT NULL,
	created_at DATETIME NOT NULL,
	disposed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	disposed_by VARCHAR(120) DEFAULT NULL,
	KEY idx_history_portal (portal_type),
	KEY idx_history_user (user_id),
	KEY idx_history_doctor (doctor_id),
	KEY idx_history_disposed (disposed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = '';
$err = '';
if (isset($_POST['submit'])) {
	$nameIn = hms_escape($con, trim($_POST['name'] ?? ''));
	$emailIn = hms_escape($con, trim($_POST['email'] ?? ''));
	$phoneIn = hms_escape($con, trim($_POST['phone'] ?? ''));
	$subjectIn = hms_escape($con, trim($_POST['subject'] ?? ''));
	$messageIn = hms_escape($con, trim($_POST['message'] ?? ''));

	if ($nameIn === '' || $emailIn === '' || $subjectIn === '' || $messageIn === '') {
		$err = 'Please fill all required fields.';
	} else {
		$ins = hms_query($con, "INSERT INTO contact_queries(portal_type,user_id,doctor_id,name,email,phone,subject,message,status,created_at) VALUES('doctor',NULL,'".(int)$doctorId."','$nameIn','$emailIn','$phoneIn','$subjectIn','$messageIn','New',NOW())");
		if ($ins) {
			$msg = 'Your query has been submitted successfully.';
		} else {
			$err = 'Unable to submit query. Please try again.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Doctor | Contact Us</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title = 'Doctor | Contact Us'; $x_content = true; include('include/header.php');?>
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="x_panel">
			<div class="x_title"><h2>Contact Us</h2><div class="clearfix"></div></div>
			<div class="x_content">
				<?php if($msg!==''): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
				<?php if($err!==''): ?><div class="alert alert-danger"><?php echo htmlentities($err); ?></div><?php endif; ?>
				<form method="post">
					<div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="<?php echo htmlentities($_POST['name'] ?? $name); ?>" required></div>
					<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="<?php echo htmlentities($_POST['email'] ?? $email); ?>" required></div>
					<div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlentities($_POST['phone'] ?? ''); ?>"></div>
					<div class="form-group"><label>Subject *</label><input type="text" name="subject" class="form-control" value="<?php echo htmlentities($_POST['subject'] ?? ''); ?>" required></div>
					<div class="form-group"><label>Message *</label><textarea name="message" class="form-control" rows="5" required><?php echo htmlentities($_POST['message'] ?? ''); ?></textarea></div>
					<button type="submit" name="submit" class="btn btn-primary">Submit Query</button>
				</form>
			</div>
		</div>
		<div class="x_panel">
			<div class="x_title"><h2>My Contact Query Status</h2><div class="clearfix"></div></div>
			<div class="x_content table-responsive">
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>#</th><th>Subject</th><th>Message</th><th>Status</th><th>Admin Remark</th><th>Submitted</th><th>Updated</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cnt=1;
					$q = hms_query($con, "
						SELECT id, subject, message, status, admin_note, created_at, updated_at
						FROM contact_queries
						WHERE portal_type='doctor' AND doctor_id='$doctorId'
						UNION ALL
						SELECT original_query_id AS id, subject, message, final_status AS status, admin_note, created_at, disposed_at AS updated_at
						FROM contact_query_history
						WHERE portal_type='doctor' AND doctor_id='$doctorId'
						ORDER BY created_at DESC
					");
					if($q) while($r=hms_fetch_assoc($q)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo htmlentities($r['subject']); ?></td>
						<td style="max-width:320px; white-space:pre-wrap;"><?php echo htmlentities($r['message']); ?></td>
						<td>
							<?php if(($r['status'] ?? '') === 'In Progress'): ?>
								<span class="status-info">In Progress</span>
							<?php elseif(($r['status'] ?? '') === 'Closed'): ?>
								<span class="status-active">Disposed/Closed</span>
							<?php else: ?>
								<span class="status-warning"><?php echo htmlentities($r['status'] ?? 'New'); ?></span>
							<?php endif; ?>
						</td>
						<td style="max-width:320px; white-space:pre-wrap;"><?php echo htmlentities(($r['admin_note'] ?? '') !== '' ? $r['admin_note'] : '-'); ?></td>
						<td><?php echo htmlentities($r['created_at']); ?></td>
						<td><?php echo htmlentities(($r['updated_at'] ?? '') ?: '-'); ?></td>
					</tr>
					<?php $cnt++; } ?>
					<?php if($cnt===1): ?><tr><td colspan="7" class="text-center text-muted">No contact queries submitted yet.</td></tr><?php endif; ?>
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
