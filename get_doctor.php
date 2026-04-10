<?php
include('include/config.php');
$doctorSpecColumn = 'specilization';
$doctorSpecType = '';
$doctorSpecColumnCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specialization'");
if ($doctorSpecColumnCheck && hms_num_rows($doctorSpecColumnCheck) > 0) {
  $doctorSpecColumn = 'specialization';
  $doctorSpecMeta = hms_fetch_assoc($doctorSpecColumnCheck);
  $doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
} else {
  $doctorSpecLegacyCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specilization'");
  if ($doctorSpecLegacyCheck && hms_num_rows($doctorSpecLegacyCheck) > 0) {
    $doctorSpecMeta = hms_fetch_assoc($doctorSpecLegacyCheck);
    $doctorSpecType = strtolower($doctorSpecMeta['Type'] ?? '');
  }
}
$isDoctorSpecNumeric = preg_match('/int|decimal|float|double/', $doctorSpecType) === 1;

$specTable = '';
$specColumn = 'specialization';
if (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecialization'")) > 0) {
  $specTable = 'doctorspecialization';
  $specColumn = 'specialization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecilization'")) > 0) {
  $specTable = 'doctorspecilization';
  $specColumn = 'specilization';
} elseif (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctor_specialization'")) > 0) {
  $specTable = 'doctor_specialization';
  $specColumn = 'specialization';
}

$selectedSpecialization = $_POST["specializationid"] ?? $_POST["specilizationid"] ?? '';

if(!empty($selectedSpecialization))
{
  $doctorFilterValue = $selectedSpecialization;

  if ($isDoctorSpecNumeric) {
    if (!ctype_digit((string)$selectedSpecialization) && !empty($specTable)) {
      $sp = hms_query($con, "SELECT id FROM $specTable WHERE $specColumn='".hms_escape($con, $selectedSpecialization)."' LIMIT 1");
      if ($sp && ($spRow = hms_fetch_assoc($sp))) {
        $doctorFilterValue = (int)$spRow['id'];
      }
    }
    $sql=hms_query($con,"SELECT doctorName,id FROM doctors WHERE $doctorSpecColumn='".(int)$doctorFilterValue."'");
  } else {
    if (ctype_digit((string)$selectedSpecialization) && !empty($specTable)) {
      $sp = hms_query($con, "SELECT $specColumn AS specialization_name FROM $specTable WHERE id='".(int)$selectedSpecialization."' LIMIT 1");
      if ($sp && ($spRow = hms_fetch_assoc($sp))) {
        $doctorFilterValue = $spRow['specialization_name'];
      }
    }
    $sql=hms_query($con,"SELECT doctorName,id FROM doctors WHERE $doctorSpecColumn='".hms_escape($con, $doctorFilterValue)."'");
  }
  ?>
 <option selected="selected">Select Doctor </option>
 <?php
 while($row=hms_fetch_array($sql))
  {?>
 <option value="<?php echo htmlentities($row['id']); ?>"><?php echo htmlentities($row['doctorName']); ?> (ID: <?php echo (int)$row['id']; ?>)</option>
  <?php
}
}


if(!empty($_POST["docid"])) 
{

 $sql=hms_query($con,"select docFees from doctors where id='".$_POST['docid']."'");
 while($row=hms_fetch_array($sql))
 	{?>
 <option value="<?php echo htmlentities($row['docFees']); ?>"><?php echo htmlentities($row['docFees']); ?></option>
  <?php
}
}

?>

