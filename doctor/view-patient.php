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
  <title>Doctor | Manage Patients</title>


  <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">

  <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">

  <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

  <link href="../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">

  <link href="../vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>

  <link href="../vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

  <link href="../assets/css/custom.css" rel="stylesheet">
  <body class="nav-md">
    <?php
    $page_title = 'Doctor | Manage Patients';
    $x_content = true;
    ?>
    <?php include('include/header.php');?>

    <div class="row">
      <div class="col-md-12">
      <h5 class="over-title margin-bottom-15">Patient <span class="text-bold">Details</span></h5>
        <?php
        $vid=$_GET['viewid'];
        $ret=hms_query($con,"select * from tblpatient where ID='$vid'");
        $cnt=1;
        while ($row=hms_fetch_array($ret)) {
         ?>
         <table border="1" class="table table-bordered">
           <tr align="center">
            <td colspan="4" style="font-size:20px;color:blue">
            Patient Details</td></tr>

            <tr>
              <th scope>Patient Name</th>
              <td><?php  echo $row['PatientName'];?></td>
              <th scope>Patient Email</th>
              <td><?php  echo $row['PatientEmail'];?></td>
            </tr>
            <tr>
              <th scope>Patient Mobile Number</th>
              <td><?php  echo $row['PatientContno'];?></td>
              <th>Patient Address</th>
              <td><?php  echo $row['PatientAdd'];?></td>
            </tr>
            <tr>
              <th>Patient Gender</th>
              <td><?php  echo $row['PatientGender'];?></td>
              <th>Patient Age</th>
              <td><?php  echo $row['PatientAge'];?></td>
            </tr>
            <tr>
              <th>Patient Reg Date</th>
              <td><?php  echo $row['CreationDate'];?></td>
              <th>Last Updated</th>
              <td><?php  echo $row['UpdationDate'];?></td>
            </tr>

            <?php }?>
          </table>
          <p>
            <a href="manage-patient.php" class="btn btn-default">Back</a>
          </p>
          </div>
        </div>
        </div>
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