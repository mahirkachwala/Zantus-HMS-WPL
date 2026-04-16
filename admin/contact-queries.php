<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();

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

if (isset($_POST['action']) && isset($_POST['id'])) {
	$id = (int)$_POST['id'];
	$action = trim($_POST['action']);
	if ($action === 'progress') {
		hms_query($con, "UPDATE contact_queries SET status='In Progress', updated_at=NOW() WHERE id='$id'");
		$msg = 'Query moved to In Progress.';
	} elseif ($action === 'close') {
		$remark = trim($_POST['admin_note'] ?? '');
		if ($remark === '') {
			$err = 'To dispose/close a complaint, admin remark is mandatory.';
		} else {
			$remarkEsc = hms_escape($con, $remark);
			$disposedBy = hms_escape($con, (string)($_SESSION['alogin'] ?? 'admin'));
			$currentQuery = hms_query($con, "SELECT * FROM contact_queries WHERE id='$id' LIMIT 1");
			$currentRow = $currentQuery ? hms_fetch_assoc($currentQuery) : null;
			if (!$currentRow) {
				$err = 'Complaint not found or already disposed.';
			} else {
				$portalTypeEsc = hms_escape($con, (string)($currentRow['portal_type'] ?? 'user'));
				$historyCheck = hms_query($con, "SELECT id FROM contact_query_history WHERE original_query_id='$id' AND portal_type='$portalTypeEsc' LIMIT 1");
				if ($historyCheck && hms_num_rows($historyCheck) > 0) {
					$historyRow = hms_fetch_assoc($historyCheck);
					$historyId = (int)($historyRow['id'] ?? 0);
					$archived = hms_query($con, "UPDATE contact_query_history SET final_status='Closed', admin_note='$remarkEsc', disposed_at=NOW(), disposed_by='$disposedBy' WHERE id='$historyId' LIMIT 1");
				} else {
					$archiveSql = "INSERT INTO contact_query_history(
						original_query_id, portal_type, user_id, doctor_id, name, email, phone, subject, message,
						final_status, admin_note, created_at, disposed_at, disposed_by
					)
					SELECT id, portal_type, user_id, doctor_id, name, email, phone, subject, message,
					'Closed', '$remarkEsc', created_at, NOW(), '$disposedBy'
					FROM contact_queries WHERE id='$id' LIMIT 1";
					$archived = hms_query($con, $archiveSql);
				}

				if ($archived) {
					hms_query($con, "DELETE FROM contact_queries WHERE id='$id' LIMIT 1");
					$msg = 'Complaint disposed successfully. It is removed from admin active queue.';
				} else {
					$err = 'Unable to dispose complaint right now. Please retry.';
				}
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin | Contact Queries</title>
	<link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
	<link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
	<link href="../assets/css/custom.min.css" rel="stylesheet">
</head>
<body class="nav-md">
<?php $page_title='Admin | Contact Queries'; $x_content=true; include('include/header.php');?>
<div class="row">
	<div class="col-md-12">
		<div class="x_panel">
			<div class="x_title"><h2>Active Contact Queries</h2><div class="clearfix"></div></div>
			<div class="x_content">
				<?php if($msg!==''): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
				<?php if($err!==''): ?><div class="alert alert-danger"><?php echo htmlentities($err); ?></div><?php endif; ?>
				<table class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>#</th><th>Portal</th><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Message</th><th>Status</th><th>Admin Remark</th><th>Created</th><th>Action</th>
						</tr>
					</thead>
					<tbody>
					<?php
					$cnt=1;
					$q = hms_query($con, "SELECT cq.* FROM contact_queries cq WHERE NOT EXISTS (
						SELECT 1 FROM contact_query_history cqh
						WHERE cqh.original_query_id = cq.id AND cqh.portal_type = cq.portal_type
					) ORDER BY cq.id DESC");
					if($q) while($r=hms_fetch_assoc($q)) {
					?>
					<tr>
						<td><?php echo $cnt; ?>.</td>
						<td><?php echo htmlentities(ucfirst($r['portal_type'])); ?></td>
						<td><?php echo htmlentities($r['name']); ?></td>
						<td><?php echo htmlentities($r['email']); ?></td>
						<td><?php echo htmlentities(($r['phone'] ?: '-')); ?></td>
						<td><?php echo htmlentities($r['subject']); ?></td>
						<td style="max-width:320px; white-space:pre-wrap;"><?php echo htmlentities($r['message']); ?></td>
						<td><?php echo htmlentities($r['status']); ?></td>
						<td style="max-width:240px; white-space:pre-wrap;"><?php echo htmlentities(($r['admin_note'] ?? '') !== '' ? $r['admin_note'] : '-'); ?></td>
						<td><?php echo htmlentities($r['created_at']); ?></td>
						<td>
							<?php if(($r['status'] ?? 'New') === 'New'): ?>
								<form method="post" style="margin-bottom:6px;">
									<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
									<input type="hidden" name="action" value="progress">
									<button type="submit" class="btn btn-primary btn-xs">In Progress</button>
								</form>
								<form method="post">
									<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
									<input type="hidden" name="action" value="close">
									<textarea name="admin_note" class="form-control" rows="2" placeholder="Required remark to close complaint" required></textarea>
									<button type="submit" class="btn btn-cancel btn-xs" style="margin-top:5px;">Dispose / Close</button>
								</form>
							<?php elseif(($r['status'] ?? '') === 'In Progress'): ?>
								<form method="post">
									<input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
									<input type="hidden" name="action" value="close">
									<textarea name="admin_note" class="form-control" rows="2" placeholder="Required remark to close complaint" required></textarea>
									<button type="submit" class="btn btn-cancel btn-xs" style="margin-top:5px;">Dispose / Close</button>
								</form>
							<?php else: ?>
								<span class="text-muted">No Action</span>
							<?php endif; ?>
						</td>
					</tr>
					<?php $cnt++; } ?>
					<?php if($cnt===1): ?><tr><td colspan="11" class="text-center text-muted">No active queries. Disposed queries are hidden from admin queue.</td></tr><?php endif; ?>
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
