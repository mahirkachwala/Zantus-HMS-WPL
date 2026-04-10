<?php
// include this at the top of protected pages in this lab to ensure sessions are initialized
require_once __DIR__ . '/../include/config.php';
session_start();

// if user already has session, optionally check last_activity elsewhere (existing checklogin.php does that)
if(!empty($_SESSION['id']) && !empty($_SESSION['login'])) {
    return; // already logged in
}

// otherwise try remember cookie
if(!empty($_COOKIE['remember'])) {
    $parts = explode(':', $_COOKIE['remember']);
    if(count($parts) === 2) {
        list($selector, $token) = $parts;
        $selStmt = $con->prepare('SELECT id, user_id, token_hash, expires FROM auth_tokens WHERE selector = ? LIMIT 1');
        $selStmt->bind_param('s', $selector);
        $selStmt->execute();
        $res = $selStmt->get_result();
        $row = $res->fetch_assoc();
        $selStmt->close();
        if($row) {
            if(strtotime($row['expires']) >= time()) {
                $tokenHash = hash('sha256', $token);
                if(hash_equals($row['token_hash'], $tokenHash)) {
                    // token valid: load user and establish session
                    $uid = intval($row['user_id']);
                    $uStmt = $con->prepare('SELECT id, fullName, email FROM users WHERE id = ? LIMIT 1');
                    $uStmt->bind_param('i', $uid);
                    $uStmt->execute();
                    $ures = $uStmt->get_result();
                    $user = $ures->fetch_assoc();
                    $uStmt->close();
                    if($user) {
                        session_regenerate_id(true);
                        $_SESSION['id'] = $user['id'];
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['login'] = $user['email'];
                        $_SESSION['fullName'] = $user['fullName'];
                        // optionally rotate token: create new token and delete old
                        $newToken = bin2hex(random_bytes(33));
                        $newHash = hash('sha256', $newToken);
                        $newExpires = date('Y-m-d H:i:s', time() + 60*60*24*30);
                        $upd = $con->prepare('UPDATE auth_tokens SET token_hash = ?, expires = ? WHERE id = ?');
                        $upd->bind_param('ssi', $newHash, $newExpires, $row['id']);
                        $upd->execute();
                        $upd->close();
                        // set cookie again
                        $cookieVal = $selector . ':' . $newToken;
                        setcookie('remember', $cookieVal, time() + 60*60*24*30, '/', '', false, true);
                        return;
                    }
                }
            }
        }
    }
    // if we reach here, cookie invalid -> clear it
    setcookie('remember', '', time() - 3600, '/', '', false, true);
}

// not logged in after attempts; do nothing — protected pages should redirect if they require login
return;

?>