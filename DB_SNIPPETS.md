Overview of SQL connection and query usage in this project

This file lists the code that opens DB connections and executes SQL across the project. Files are grouped with their relevant snippets and a short context note. After conversion to MySQL, the primary DB code now lives in `include/config.php` (MySQLi wrappers) and `admin/include/dbcontroller.php` (PDO MySQL). Many other scripts call the helper `hms_query()` (defined in `include/config.php`).

---

File: include/config.php
Purpose: Central MySQLi connection and wrapper helpers (mysqli_connect, hms_query, hms_query_params, helpers for fetch/escape/num_rows, schema ensure functions).

Full relevant content (connection + helpers):

```php
<?php
// MySQL i connection: default values can be changed in the file
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'hms');
define('DB_USER', 'root');
define('DB_PASS', '');

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
if (!$con) { die('Failed to connect to MySQL: ' . mysqli_connect_error()); }
mysqli_set_charset($con, 'utf8');

/* ... helper functions: hms_escape (mysqli_real_escape_string), hms_last_error (mysqli_error), hms_fetch_assoc (mysqli_fetch_assoc), hms_fetch_array, hms_num_rows, hms_last_insert_id (mysqli_insert_id), hms_query_params (simple $1 -> quoted param replacer), hms_normalize_sql, hms_query (mysqli_query wrapper), hms_table_exists, hms_column_exists, hms_get_table_columns, hms_ensure_schema, hms_ensure_past_appointments, hms_archive_appointment ... */

hms_ensure_schema($con);
?>
```

Context: This file provides the `$con` MySQLi connection that most pages use. It also implements compatibility helpers so existing calls to `hms_query()` continue to work with MySQL.

---

File: admin/include/dbcontroller.php
Purpose: PDO-based connection used by some admin scripts (PDO MySQL).

Snippet:

```php
<?php
$DB_host = "localhost";
$DB_port = "3306";
$DB_user = "root";
$DB_pass = "";
$DB_name = "hms";
try {
    $dsn = "mysql:host={$DB_host};port={$DB_port};dbname={$DB_name};charset=utf8";
    $DB_con = new PDO($dsn, $DB_user, $DB_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
```

Context: Some admin pages use PDO via `$DB_con`; others use the global `$con` from `include/config.php`.

---

Other files: these mostly call `hms_query($con, "<sql>")`, `hms_query_params(...)`, or check table/column existence using `SHOW TABLES` / `SHOW COLUMNS`. Below are the files and the exact lines where SQL is issued (extracted from a repo scan).

Notes: The snippets below are the actual SQL-calling lines from each file. Many pages include `include/config.php` at top to get `$con` and helpers.

File: contact-us.php
- $uq = hms_query($con, "SELECT fullName,email FROM users WHERE id='$userId' LIMIT 1");
- hms_query($con, "CREATE TABLE IF NOT EXISTS contact_queries (");
- hms_query($con, "CREATE TABLE IF NOT EXISTS contact_query_history (");
- $ins = hms_query($con, "INSERT INTO contact_queries(portal_type,user_id,doctor_id,name,email,phone,subject,message,status,created_at) VALUES('user','".(int)$userId."',NULL,'$nameIn','$emailIn','$phoneIn','$subjectIn','$messageIn','New',NOW())");
- $q = hms_query($con, "... (block query for listing) ");

File: view-prescription.php
- $check = hms_query($con, "SHOW TABLES LIKE '" . hms_escape($con, $tableName) . "'");
- $q = hms_query($con, "SELECT p.*, u.fullName AS patientName, d.doctorName ...");
- $aq = hms_query($con, "SELECT doctorId FROM $tableName WHERE id='$appointmentId' AND userId='$userId' LIMIT 1");
- $q2 = hms_query($con, "SELECT p.*, u.fullName AS patientName, d.doctorName ...");
- $aq = hms_query($con, "SELECT appointmentDate, appointmentTime FROM $tableName WHERE id='$apptId' AND userId='$userId' LIMIT 1");

File: status-updates.php
- hms_query($con, "CREATE TABLE IF NOT EXISTS contact_query_history (");
- $q = hms_query($con, "... (status updates listing block)");
- $fq = hms_query($con, "SELECT * FROM feedback_entries WHERE portal_type='user' AND user_id='$userId' ORDER BY id DESC");

File: reset-password.php
- $query=hms_query($con,"update users set password='$newpassword' where fullName='$name' and email='$email'");

File: registration.php
- $check = hms_query($con, "SELECT id FROM users WHERE email='$emailEsc' LIMIT 1");
- $query = hms_query($con, "INSERT INTO ... (user creation) ...");

File: pay-fees.php
- $check = hms_query($con, "SHOW COLUMNS FROM $table LIKE '" . $columnName . "'");
- hms_query($con, $ddl);
- $tableCheck = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");

File: logout.php
- hms_query($con,"UPDATE userlog SET logout = '$ldate' WHERE id = (SELECT id FROM userlog WHERE uid = $userId ORDER BY id DESC LIMIT 1)");

File: index.php
- $ret=hms_query($con,"SELECT * FROM users WHERE email='".$_POST['username']."' and password='".$_POST['password']."'");
- $log=hms_query($con,"insert into userlog(uid,username,userip,status) values('".$_SESSION['id']."','".$_SESSION['login']."','$uip','$status')");
- hms_query($con,"insert into userlog(username,userip,status) values('".$_SESSION['login']."','$uip','$status')");

File: get_doctor.php
- $doctorSpecColumnCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specialization'");
- $doctorSpecLegacyCheck = hms_query($con, "SHOW COLUMNS FROM doctors LIKE 'specilization'");
- if (hms_num_rows(hms_query($con, "SHOW TABLES LIKE 'doctorspecialization'")) > 0) { ... }
- $sp = hms_query($con, "SELECT id FROM $specTable WHERE $specColumn='".hms_escape($con, $selectedSpecialization)."' LIMIT 1");
- $sql=hms_query($con,"SELECT doctorName,id FROM doctors WHERE $doctorSpecColumn='".hms_escape($con, $doctorFilterValue)."'");
- $sql=hms_query($con,"select docFees from doctors where id='".$_POST['docid']."'");

File: include/checklogin.php
- $rebuild = hms_query($con, "SELECT id, fullName, email FROM users WHERE email='$emailEsc' LIMIT 1");
- $verify = hms_query($con, "SELECT id, fullName, email FROM users WHERE id='$userId' AND email='$userEmailEsc' LIMIT 1");
- $verify = hms_query($con, "SELECT id, fullName, email FROM users WHERE id='$userId' LIMIT 1");

File: forgot-password.php
- $query=hms_query($con,"select id from  users where fullName='$name' and email='$email'");

File: doctor/add-patient.php
- $patientsTableCheck = hms_query($con, "SHOW TABLES LIKE 'patients'");
- $userCheck = hms_query($con, "SELECT id FROM users WHERE email='$patemail' LIMIT 1");
- hms_query($con, "INSERT INTO users(fullName, email, password, regDate) VALUES('$patname', '$patemail', '$tempPassword', NOW())");
- $sql = hms_query($con, "INSERT INTO patients(...)");
- $sql = hms_query($con, "INSERT INTO tblpatient(...)");
- ... (many more hms_query usages in doctor pages)

File: doctor/add-prescription.php
- $check = hms_query($con, "SHOW COLUMNS FROM $table LIKE '" . $columnName . "'");
- hms_query($con, $ddl);
- $check = hms_query($con, "SHOW TABLES LIKE 'current_appointments'");
- hms_query($con, "CREATE TABLE IF NOT EXISTS prescriptions (");
- $medicinesColumnCheck = hms_query($con, "SHOW COLUMNS FROM prescriptions LIKE 'medicines'");
- hms_query($con, "ALTER TABLE prescriptions ADD COLUMN medicines LONGTEXT DEFAULT NULL AFTER notes");
- $appointmentSql = hms_query($con, "SELECT $appointmentTable.*, users.fullName FROM $appointmentTable JOIN users ON users.id=$appointmentTable.userId WHERE $appointmentTable.id='$appointmentId' AND $appointmentTable.doctorId='$doctorId' LIMIT 1");
- $insertPrescription = hms_query($con, "INSERT INTO prescriptions(...)");
- hms_query($con, "UPDATE $appointmentTable SET prescription='$summaryEscaped', visitStatus='Completed', checkOutTime=NOW() WHERE id='$appointmentId' AND doctorId='$doctorId'");

(Additional files with hms_query usage include many pages under root, admin/, doctor/, and assets that rely on `include/config.php`. Full per-file listing is available via a codebase search for 'hms_query(' or 'pg_query('.)

---

How this extraction was produced
- Scanned repository for usages of Postgres native functions (pg_*), wrapper `hms_query`, and PDO usage.
- Captured connection code (pg_connect and new PDO line) and all places where queries are run.
- The `hms_query()` wrapper maps MySQL-style "SHOW" statements to information_schema queries so many pages can use legacy MySQL-like queries.

If you want: I can create a single consolidated PHP script which prints runtime examples of each helper (connect, run sample SELECT, show table list) to demonstrate live queries against your Postgres DB.

---

Next steps I can take on request:
- Add a runnable demo script (PHP CLI or web page) that connects and runs a small set of queries to show data.
- Generate a shorter CSV of (file,path,query) for easier presentation.
- Create automated test(s) that connect to Postgres and assert main tables exist.
