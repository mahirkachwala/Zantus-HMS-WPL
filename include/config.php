<?php
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');
define('DB_NAME', 'hms');
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);
// Check connection
if (mysqli_connect_errno())
{
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

if (!function_exists('hms_column_exists')) {
	function hms_column_exists($con, $table, $column) {
		$res = mysqli_query($con, "SHOW COLUMNS FROM `".$table."` LIKE '" . mysqli_real_escape_string($con, $column) . "'");
		return ($res && mysqli_num_rows($res) > 0);
	}
}

if (!function_exists('hms_table_exists')) {
	function hms_table_exists($con, $table) {
		$res = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $table) . "'");
		return ($res && mysqli_num_rows($res) > 0);
	}
}

if (!function_exists('hms_ensure_schema')) {
	function hms_ensure_schema($con) {
		if (!$con) {
			return;
		}

		$appointmentColumns = [
			"visitStatus" => "ALTER TABLE appointment ADD COLUMN visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled' AFTER doctorStatus",
			"checkInTime" => "ALTER TABLE appointment ADD COLUMN checkInTime datetime DEFAULT NULL AFTER visitStatus",
			"checkOutTime" => "ALTER TABLE appointment ADD COLUMN checkOutTime datetime DEFAULT NULL AFTER checkInTime",
			"prescription" => "ALTER TABLE appointment ADD COLUMN prescription mediumtext DEFAULT NULL AFTER checkOutTime",
			"paymentStatus" => "ALTER TABLE appointment ADD COLUMN paymentStatus varchar(20) NOT NULL DEFAULT 'Pending' AFTER prescription",
			"paymentRef" => "ALTER TABLE appointment ADD COLUMN paymentRef varchar(64) DEFAULT NULL AFTER paymentStatus",
			"paidAt" => "ALTER TABLE appointment ADD COLUMN paidAt datetime DEFAULT NULL AFTER paymentRef"
		];

		foreach ($appointmentColumns as $column => $ddl) {
			if (!hms_column_exists($con, 'appointment', $column)) {
				@mysqli_query($con, $ddl);
			}
		}

		if (!hms_table_exists($con, 'prescriptions')) {
			@mysqli_query($con, "CREATE TABLE IF NOT EXISTS prescriptions (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				patient_id INT NOT NULL,
				doctor_id INT NOT NULL,
				appointment_id INT NOT NULL,
				temperature VARCHAR(20) DEFAULT NULL,
				blood_pressure VARCHAR(30) DEFAULT NULL,
				pulse VARCHAR(20) DEFAULT NULL,
				weight VARCHAR(20) DEFAULT NULL,
				symptoms TEXT DEFAULT NULL,
				diagnosis TEXT DEFAULT NULL,
				tests TEXT DEFAULT NULL,
				notes TEXT DEFAULT NULL,
				next_visit_date DATE DEFAULT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_appointment_id (appointment_id),
				INDEX idx_patient_id (patient_id),
				INDEX idx_doctor_id (doctor_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		}

		if (!hms_table_exists($con, 'prescription_medicines')) {
			@mysqli_query($con, "CREATE TABLE IF NOT EXISTS prescription_medicines (
				id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				prescription_id INT NOT NULL,
				medicine_name VARCHAR(255) NOT NULL,
				dosage VARCHAR(100) DEFAULT NULL,
				frequency VARCHAR(100) DEFAULT NULL,
				duration VARCHAR(100) DEFAULT NULL,
				instructions VARCHAR(255) DEFAULT NULL,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX idx_prescription_id (prescription_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		}
	}
}

hms_ensure_schema($con);
?>