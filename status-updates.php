<?php
require_once __DIR__ . '/include/session.php';
hms_session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

$userId = (int)($_SESSION['id'] ?? 0);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>User | Status Updates</title>
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title = 'User | Status Updates'; $x_content = true; include('include/header.php');?>
<div class="row">
	<div class="col-md-12">
		<div class="x_panel">
			<div class="x_title"><h2>My Contact Query Status</h2><div class="clearfix"></div></div>
			<div class="x_content">
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
						SELECT cq.id, cq.subject, cq.message, cq.status, cq.admin_note, cq.created_at, cq.updated_at
						FROM contact_queries cq
						WHERE cq.portal_type='user' AND cq.user_id='$userId'
						AND NOT EXISTS (
							SELECT 1 FROM contact_query_history cqh
							WHERE cqh.original_query_id = cq.id AND cqh.portal_type = cq.portal_type
						)
						UNION ALL
						SELECT original_query_id AS id, subject, message, final_status AS status, admin_note, created_at, disposed_at AS updated_at
						FROM contact_query_history
						WHERE portal_type='user' AND user_id='$userId'
						ORDER BY created_at DESC
					");
					if($q) while($r=hms_fetch_assoc($q)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo htmlentities($r['subject']); ?></td>
						<td style="max-width:350px; white-space:pre-wrap;"><?php echo htmlentities($r['message']); ?></td>
						<td>
							<?php if(($r['status'] ?? '') === 'In Progress'): ?>
								<span class="status-info">In Progress</span>
							<?php elseif(($r['status'] ?? '') === 'Closed'): ?>
								<span class="status-active">Disposed/Closed</span>
							<?php else: ?>
								<span class="status-warning"><?php echo htmlentities($r['status'] ?? 'New'); ?></span>
							<?php endif; ?>
						</td>
						<td style="max-width:350px; white-space:pre-wrap;"><?php echo htmlentities(($r['admin_note'] ?? '') !== '' ? $r['admin_note'] : '-'); ?></td>
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

<div class="row">
	<div class="col-md-12">
		<div class="x_panel">
			<div class="x_title"><h2>My Feedback Status</h2><div class="clearfix"></div></div>
			<div class="x_content">
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>#</th><th>Rating</th><th>Feedback</th><th>Status</th><th>Submitted</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cnt=1;
					$fq = hms_query($con, "SELECT * FROM feedback_entries WHERE portal_type='user' AND user_id='$userId' ORDER BY id DESC");
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
<script src="vendors/jquery/dist/jquery.min.js"></script>
<script src="vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="vendors/nprogress/nprogress.js"></script>
<script src="assets/js/custom.min.js"></script>
</body>
</html>
