<?php
require_once __DIR__ . '/init.php';
session_start();

// If not logged in, redirect to lab login page
if (empty($_SESSION['id']) || empty($_SESSION['login'])) {
    header('Location: index.php');
    exit;
}
$userId = intval($_SESSION['id']);
$email = htmlspecialchars($_SESSION['login']);
$fullName = htmlspecialchars($_SESSION['fullName'] ?? '');

// Fetch any auth_tokens rows for debugging (non-production helper)
$tokens = [];
$stmt = $con->prepare('SELECT selector, expires FROM auth_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 10');
if($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $tokens[] = $r;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Lab Dashboard</title>
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{padding:24px;background:#f7f9fb;} .card{margin-bottom:12px;}</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Lab Dashboard</h4>
            <p>Welcome, <strong><?php echo $fullName ?: $email; ?></strong></p>
            <p><strong>User ID:</strong> <?php echo $userId; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
            <p>
                <a class="btn btn-sm btn-outline-secondary" href="logout.php">Logout</a>
                <a class="btn btn-sm btn-outline-primary" href="../index.php">Project Home</a>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Remember-me cookie (client)</h5>
            <p>Cookie value (document.cookie):</p>
            <pre id="cookieVal">(click show)</pre>
            <button id="showCookie" class="btn btn-sm btn-info">Show remember cookie (JS)</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Server-side auth_tokens (last 10)</h5>
            <?php if(empty($tokens)): ?>
                <p>No tokens found for this user.</p>
            <?php else: ?>
                <table class="table table-sm"><thead><tr><th>selector</th><th>expires</th></tr></thead><tbody>
                <?php foreach($tokens as $t): ?>
                    <tr><td><?php echo htmlspecialchars($t['selector']); ?></td><td><?php echo htmlspecialchars($t['expires']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5>Notes for your writeup</h5>
            <ul>
                <li>Use DevTools &gt; Application &gt; Cookies to inspect the <code>remember</code> cookie.</li>
                <li>Take screenshots: login page, registration success message, dashboard showing session info, cookie in Application tab.</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('showCookie').addEventListener('click', function(){
    document.getElementById('cookieVal').textContent = document.cookie || '(no cookies)';
});
</script>
</body>
</html>