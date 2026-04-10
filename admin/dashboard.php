<?php
session_start();
error_reporting(0);
include('include/config.php');
include('include/checklogin.php');
check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin  | Dashboard</title>

  <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

  <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

  <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

  <link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

  <link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

  <link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

  <link href="../assets/css/custom.min.css" rel="stylesheet">
  <style>
    .admin-dashboard-wrap {
      background: linear-gradient(180deg, #f4f7fb 0%, #eef3ff 100%);
      border: 1px solid #e4e9f5;
      border-radius: 14px;
      padding: 25px 20px 10px;
      margin-bottom: 20px;
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .admin-dashboard-title {
      margin: 0 0 20px;
      color: #1e3a8a;
      font-weight: 700;
      font-size: 22px;
    }

    .stats-grid {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -10px;
    }

    .stat-col {
      width: 20%;
      padding: 0 10px;
      margin-bottom: 20px;
    }

    .stat-card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #e6ebf5;
      padding: 18px 16px;
      min-height: 145px;
      box-shadow: 0 5px 14px rgba(30, 58, 138, 0.08);
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 22px rgba(30, 58, 138, 0.14);
    }

    .stat-top {
      color: #334155;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .stat-count {
      color: #1e3a8a;
      font-weight: 700;
      font-size: 34px;
      line-height: 1;
      margin-bottom: 14px;
    }

    .stat-link a {
      color: #0f172a;
      font-size: 13px;
      text-decoration: none;
      font-weight: 600;
    }

    .stat-link a:hover {
      color: #1e40af;
    }

    @media (max-width: 1200px) {
      .stat-col { width: 33.3333%; }
    }
    @media (max-width: 900px) {
      .stat-col { width: 50%; }
    }
    @media (max-width: 600px) {
      .stat-col { width: 100%; }
    }
  </style>
</head>
<body class="nav-md">
  <?php include('include/header.php');?>
  <div class="admin-dashboard-wrap">
    <h2 class="admin-dashboard-title">Admin Overview</h2>
    <div class="stats-grid">
      <?php

      $tableCheck = hms_query($con,"SHOW TABLES LIKE 'current_appointments'");
      $appointmentTable = ($tableCheck && hms_num_rows($tableCheck) > 0) ? 'current_appointments' : 'appointment';

      $result = hms_query($con,"SELECT * FROM users ");
      $num_rows = hms_num_rows($result);
      $total_users = htmlentities($num_rows);

      $result1 = hms_query($con,"SELECT * FROM doctors ");
      $num_rows1 = hms_num_rows($result1);
      $total_doctors = htmlentities($num_rows1);

      $sql= hms_query($con,"SELECT * FROM $appointmentTable");
      $num_rows2 = hms_num_rows($sql);
      $total_appointments = htmlentities($num_rows2);

      $result = hms_query($con,"SELECT * FROM tblpatient ");
      $num_rows = hms_num_rows($result);
      $total_patients = htmlentities($num_rows);

      $sql= hms_query($con,"SELECT * FROM $appointmentTable where visitStatus='Completed'");
      $total_completed = htmlentities(hms_num_rows($sql));

      $sql= hms_query($con,"SELECT * FROM $appointmentTable where paymentStatus IN ('Paid','Paid at Hospital')");
      $total_paid = htmlentities(hms_num_rows($sql));

      $total_contact_queries = 0;
      $total_feedbacks = 0;
      $cqCheck = hms_query($con, "SHOW TABLES LIKE 'contact_queries'");
      if ($cqCheck && hms_num_rows($cqCheck) > 0) {
        $cqRes = hms_query($con, "SELECT COUNT(*) as total FROM contact_queries");
        $cqRow = ($cqRes) ? hms_fetch_assoc($cqRes) : null;
        $total_contact_queries = (int)($cqRow['total'] ?? 0);
      }
      $fbCheck = hms_query($con, "SHOW TABLES LIKE 'feedback_entries'");
      if ($fbCheck && hms_num_rows($fbCheck) > 0) {
        $fbRes = hms_query($con, "SELECT COUNT(*) as total FROM feedback_entries");
        $fbRow = ($fbRes) ? hms_fetch_assoc($fbRes) : null;
        $total_feedbacks = (int)($fbRow['total'] ?? 0);
      }

      ?>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-users"></i> Registered Users</div>
          <div class="stat-count"><?php echo $total_users; ?></div>
          <div class="stat-link"><a href="manage-users.php">View all users</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-user-md"></i> Active Doctors</div>
          <div class="stat-count"><?php echo $total_doctors; ?></div>
          <div class="stat-link"><a href="manage-doctors.php">View all doctors</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-list-alt"></i> Total Appointments</div>
          <div class="stat-count"><?php echo $total_appointments; ?></div>
          <div class="stat-link"><a href="appointment-history.php">View all appointments</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-user"></i> Total Patients</div>
          <div class="stat-count"><?php echo $total_patients; ?></div>
          <div class="stat-link"><a href="manage-patient.php">View all patients</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-check-circle"></i> Completed Visits</div>
          <div class="stat-count"><?php echo $total_completed; ?></div>
          <div class="stat-link"><a href="appointment-history.php">Review completed visits</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-credit-card"></i> Payments Received</div>
          <div class="stat-count"><?php echo $total_paid; ?></div>
          <div class="stat-link"><a href="payments.php">Check payment status</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-envelope"></i> Contact Queries</div>
          <div class="stat-count"><?php echo (int)$total_contact_queries; ?></div>
          <div class="stat-link"><a href="contact-queries.php">View all contact queries</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-commenting"></i> Feedback Entries</div>
          <div class="stat-count"><?php echo (int)$total_feedbacks; ?></div>
          <div class="stat-link"><a href="feedbacks.php">View all feedbacks</a></div>
        </div>
      </div>
    </div>
  </div>


  <?php include('include/footer.php');?>

  <script src="../vendors/jquery/dist/jquery.min.js"></script>

  <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

  <script src="../vendors/fastclick/lib/fastclick.js"></script>

  <script src="../vendors/nprogress/nprogress.js"></script>

  <script src="../vendors/Chart.js/dist/Chart.min.js"></script>

  <script src="../vendors/gauge.js/dist/gauge.min.js"></script>

  <script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>

  <script src="../vendors/iCheck/icheck.min.js"></script>

  <script src="../vendors/skycons/skycons.js"></script>

  <script src="../vendors/Flot/jquery.flot.js"></script>
  <script src="../vendors/Flot/jquery.flot.pie.js"></script>
  <script src="../vendors/Flot/jquery.flot.time.js"></script>
  <script src="../vendors/Flot/jquery.flot.stack.js"></script>
  <script src="../vendors/Flot/jquery.flot.resize.js"></script>

  <script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
  <script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
  <script src="../vendors/flot.curvedlines/curvedLines.js"></script>

  <script src="../vendors/DateJS/build/date.js"></script>

  <script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
  <script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
  <script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>

  <script src="../vendors/moment/min/moment.min.js"></script>
  <script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

  <script src="../assets/js/custom.min.js"></script>
</body>
</html>