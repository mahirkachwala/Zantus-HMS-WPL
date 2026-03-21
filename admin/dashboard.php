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
  <!-- Bootstrap -->
  <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
  <!-- NProgress -->
  <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
  <!-- iCheck -->
  <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
  <!-- bootstrap-progressbar -->
  <link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
  <!-- JQVMap -->
  <link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
  <!-- bootstrap-daterangepicker -->
  <link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
  <!-- Custom Theme Style -->
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

      $result = mysqli_query($con,"SELECT * FROM users ");
      $num_rows = mysqli_num_rows($result);
      $total_users = htmlentities($num_rows);

      $result1 = mysqli_query($con,"SELECT * FROM doctors ");
      $num_rows1 = mysqli_num_rows($result1);
      $total_doctors = htmlentities($num_rows1);

      $sql= mysqli_query($con,"SELECT * FROM appointment");
      $num_rows2 = mysqli_num_rows($sql);
      $total_appointments = htmlentities($num_rows2);

      $result = mysqli_query($con,"SELECT * FROM tblpatient ");
      $num_rows = mysqli_num_rows($result);
      $total_patients = htmlentities($num_rows);

      $sql= mysqli_query($con,"SELECT * FROM tblcontactus where  IsRead is null");
      $num_rows22 = mysqli_num_rows($sql);
      $total_queries = htmlentities($num_rows22);

      $sql= mysqli_query($con,"SELECT * FROM appointment where visitStatus='Completed'");
      $total_completed = htmlentities(mysqli_num_rows($sql));

      $sql= mysqli_query($con,"SELECT * FROM appointment where paymentStatus='Paid'");
      $total_paid = htmlentities(mysqli_num_rows($sql));

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
          <div class="stat-link"><a href="appointment-history.php">Check payment status</a></div>
        </div>
      </div>
      <div class="stat-col">
        <div class="stat-card">
          <div class="stat-top"><i class="fa fa-copy"></i> Open Queries</div>
          <div class="stat-count"><?php echo $total_queries; ?></div>
          <div class="stat-link"><a href="read-query.php">View all queries</a></div>
        </div>
      </div>
    </div>
  </div>


  <?php include('include/footer.php');?>
  <!-- jQuery -->
  <script src="../vendors/jquery/dist/jquery.min.js"></script>
  <!-- Bootstrap -->
  <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <!-- FastClick -->
  <script src="../vendors/fastclick/lib/fastclick.js"></script>
  <!-- NProgress -->
  <script src="../vendors/nprogress/nprogress.js"></script>
  <!-- Chart.js -->
  <script src="../vendors/Chart.js/dist/Chart.min.js"></script>
  <!-- gauge.js -->
  <script src="../vendors/gauge.js/dist/gauge.min.js"></script>
  <!-- bootstrap-progressbar -->
  <script src="../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
  <!-- iCheck -->
  <script src="../vendors/iCheck/icheck.min.js"></script>
  <!-- Skycons -->
  <script src="../vendors/skycons/skycons.js"></script>
  <!-- Flot -->
  <script src="../vendors/Flot/jquery.flot.js"></script>
  <script src="../vendors/Flot/jquery.flot.pie.js"></script>
  <script src="../vendors/Flot/jquery.flot.time.js"></script>
  <script src="../vendors/Flot/jquery.flot.stack.js"></script>
  <script src="../vendors/Flot/jquery.flot.resize.js"></script>
  <!-- Flot plugins -->
  <script src="../vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
  <script src="../vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
  <script src="../vendors/flot.curvedlines/curvedLines.js"></script>
  <!-- DateJS -->
  <script src="../vendors/DateJS/build/date.js"></script>
  <!-- JQVMap -->
  <script src="../vendors/jqvmap/dist/jquery.vmap.js"></script>
  <script src="../vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
  <script src="../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
  <!-- bootstrap-daterangepicker -->
  <script src="../vendors/moment/min/moment.min.js"></script>
  <script src="../vendors/bootstrap-daterangepicker/daterangepicker.js"></script>
  <!-- Custom Theme Scripts -->
  <script src="../assets/js/custom.min.js"></script>
</body>
</html>