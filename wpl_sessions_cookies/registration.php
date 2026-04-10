<?php
// copy of project's registration UI adapted for lab: posts to register_action.php
session_start();
include_once __DIR__ . '/../include/config.php';
$msg = $_SESSION['reg_msg'] ?? ''; $_SESSION['reg_msg']='';
$err = $_SESSION['reg_err'] ?? ''; $_SESSION['reg_err']='';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Registration (Lab)</title>
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/custom.min.css" rel="stylesheet">
    <style>body.login{background:#f4f7fb;} .login-brand{ text-align:center; margin-bottom:12px; }</style>
    <script type="text/javascript">
        function strongPassword(pwd) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(pwd || '')
        }
        function valid() {
            if(document.registration.password.value != document.registration.password_again.value) { alert('Password and Confirm Password Field do not match !!'); return false; }
            if(!strongPassword(document.registration.password.value)) { alert('Password must be minimum 8 characters with uppercase, lowercase, number and special character.'); return false; }
            return true;
        }
        function userAvailability() {
            jQuery.ajax({ url: "../check_availability.php", data:'email='+jQuery("#email").val(), type: "POST", success:function(data){ jQuery("#user-availability-status1").html(data); }, error:function (){} });
        }
    </script>
</head>
<body class="login">
    <div>
        <div class="login_wrapper">
            <div class="animate form login_form">
                <section class="login_content">
                    <div class="box-login">
                        <form class="form-login" name="registration" id="registration" method="post" action="register_action.php" onSubmit="return valid();">
                            <div class="login-brand"><img src="../assets/images/zantus-logo.jpg" alt="Logo"></div>
                            <fieldset>
                                <legend>Zantus HMS | Patient Create Account (Lab)</legend>
                                <?php if($msg !== ''): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
                                <?php if($err !== ''): ?><div class="alert alert-danger"><?php echo htmlentities($err); ?></div><?php endif; ?>
                                <div class="form-group"><input type="text" class="form-control" name="full_name" placeholder="Full Name" required></div>
                                <div class="form-group"><input type="text" class="form-control" name="address" placeholder="Address" required></div>
                                <div class="form-group"><input type="text" class="form-control" name="city" placeholder="City" required></div>
                                <div class="form-group"><select name="gender" class="form-control" required><option value="">Select Gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
                                <div class="form-group"><input type="email" class="form-control" name="email" id="email" onBlur="userAvailability()" placeholder="Email" required><span id="user-availability-status1" style="font-size:12px;"></span></div>
                                <div class="form-group"><input type="password" class="form-control password" name="password" id="password" placeholder="Password" minlength="8" required><small class="text-muted">Min 8 chars with uppercase, lowercase, number, and special character.</small></div>
                                <div class="form-group form-actions"><input type="password" class="form-control password" name="password_again" id="password_again" placeholder="Confirm Password" required></div>
                                <div class="form-actions"><button type="submit" class="btn btn-primary pull-right" name="submit">Create Account <i class="fa fa-arrow-circle-right"></i></button></div>
                                <div class="new-account">Already have an account? <a href="index.php">Login</a></div>
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