<?php
// copy of project's login UI adapted for lab: posts to login_action.php
session_start();
// clear any previous message shown in session (copied behavior)
include_once __DIR__ . '/../include/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User-Login (Lab)</title>
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
    <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <link href="../assets/css/custom.min.css" rel="stylesheet">
    <style>
        .login-brand { text-align:center; margin-bottom:12px; }
        .login-brand img { width:70px; height:70px; border-radius:12px; }
        body.login { background:#f4f7fb; }
    </style>
</head>
<body class="login">
    <div>
        <div class="login_wrapper">
            <div class="animate form login_form">
                <section class="login_content">
                    <div class="box-login">
                        <form class="form-login" method="post" action="login_action.php">
                            <div class="login-brand">
                                <img src="../assets/images/zantus-logo.jpg" alt="Logo">
                            </div>
                            <fieldset>
                                <legend>Zantus HMS | Patient Login (Lab)</legend>
                                <p>
                                    <span style="color:red;"><?php echo $_SESSION['errmsg'] ?? ''; $_SESSION['errmsg']=''; ?></span>
                                </p>
                                <div class="form-group">
                                    <input type="email" class="form-control" name="username" placeholder="Email" required>
                                </div>
                                <div class="form-group form-actions">
                                    <input type="password" class="form-control password" name="password" placeholder="Password" required>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="remember"> Remember me</label>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary pull-right" name="submit">Login</button>
                                </div>
                                <div class="new-account">Don't have an account yet? <a href="registration.php">Create an account</a></div>
                            </fieldset>
                        </form>
                        <div class="copyright">&copy; Zantus Life Science Hospital (Lab)</div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>