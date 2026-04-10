<?php
// Quick DB check script — uses the project's include/config.php connection and helpers
require_once __DIR__ . '/include/config.php';

// Tables to check counts for
$tables = [
    'users',
    'doctors',
    'current_appointments',
    'past_appointments',
    'prescriptions',
    'payment_transactions'
];

echo "DB connection check:\n";
foreach ($tables as $t) {
    $safe = preg_match('/^[a-zA-Z0-9_]+$/', $t) ? $t : null;
    if (!$safe) {
        echo "$t: invalid table name\n";
        continue;
    }
    $res = hms_query($con, "SELECT COUNT(*) AS c FROM $safe");
    if ($res === false) {
        $err = hms_last_error($con);
        echo "$t: ERROR - " . ($err ?: 'query failed') . "\n";
        continue;
    }
    $row = mysqli_fetch_assoc($res);
    $count = $row ? $row['c'] : '0';
    echo str_pad($t, 22) . ": $count\n";
}

// show a quick sample from users
$s = hms_query($con, "SELECT id, fullName, email FROM users ORDER BY id ASC LIMIT 5");
if ($s && hms_num_rows($s) > 0) {
    echo "\nSample users:\n";
    while ($r = hms_fetch_assoc($s)) {
        echo isset($r['id']) ? ($r['id'] . ' - ') : '';
        echo (isset($r['fullname']) ? $r['fullname'] : (isset($r['fullName']) ? $r['fullName'] : ''));
        echo ' <' . (isset($r['email']) ? $r['email'] : '') . ">\n";
    }
} else {
    echo "\nNo users found or query returned empty.\n";
}

echo "\nDone.\n";

?>
