<?php
// MySQL (mysqli) connection and compatibility helpers
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'hms');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!function_exists('mysqli_connect')) {
    die('MySQLi extension is not enabled in PHP. Enable mysqli in XAMPP.');
}

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);

if (!$con) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

mysqli_set_charset($con, 'utf8');

if (!function_exists('hms_escape')) {
    function hms_escape($con, $value) {
        return mysqli_real_escape_string($con, (string)$value);
    }
}

if (!function_exists('hms_last_error')) {
    function hms_last_error($con) {
        return mysqli_error($con);
    }
}

if (!function_exists('hms_alias_key')) {
    function hms_alias_key($key) {
        static $map = [
            'id' => ['id', 'ID'],
            'userid' => ['userid', 'userId', 'UserId'],
            'doctorid' => ['doctorid', 'doctorId', 'DoctorId', 'Docid'],
            'patientid' => ['patientid', 'patientId', 'PatientId', 'PatientID'],
            'appointmentid' => ['appointmentid', 'appointmentId', 'AppointmentId'],
            'originalappointmentid' => ['originalappointmentid', 'originalAppointmentId'],
            'transferredappointmentid' => ['transferredappointmentid', 'transferredAppointmentId'],
            'doctorspecialization' => ['doctorspecialization', 'doctorSpecialization'],
            'consultancyfees' => ['consultancyfees', 'consultancyFees'],
            'appointmentdate' => ['appointmentdate', 'appointmentDate'],
            'appointmenttime' => ['appointmenttime', 'appointmentTime'],
            'postingdate' => ['postingdate', 'postingDate', 'PostingDate'],
            'userstatus' => ['userstatus', 'userStatus'],
            'doctorstatus' => ['doctorstatus', 'doctorStatus'],
            'visitstatus' => ['visitstatus', 'visitStatus'],
            'checkintime' => ['checkintime', 'checkInTime'],
            'checkouttime' => ['checkouttime', 'checkOutTime'],
            'paymentstatus' => ['paymentstatus', 'paymentStatus'],
            'paymentref' => ['paymentref', 'paymentRef'],
            'paidat' => ['paidat', 'paidAt'],
            'appointmenttype' => ['appointmenttype', 'appointmentType'],
            'paymentoption' => ['paymentoption', 'paymentOption'],
            'doctorname' => ['doctorname', 'doctorName'],
            'docfees' => ['docfees', 'docFees'],
            'docemail' => ['docemail', 'docEmail'],
            'fullname' => ['fullname', 'fullName'],
            'regdate' => ['regdate', 'regDate'],
            'updationdate' => ['updationdate', 'updationDate', 'UpdationDate'],
            'creationdate' => ['creationdate', 'creationDate', 'CreationDate'],
            'createdat' => ['createdat', 'createdAt'],
            'updatedat' => ['updatedat', 'updatedAt'],
            'patientname' => ['patientname', 'patientName', 'PatientName'],
            'patientemail' => ['patientemail', 'patientEmail', 'PatientEmail'],
            'patientphone' => ['patientphone', 'patientPhone'],
            'patientcontno' => ['patientcontno', 'PatientContno'],
            'patientgender' => ['patientgender', 'patientGender', 'PatientGender'],
            'patientage' => ['patientage', 'patientAge', 'PatientAge'],
            'patientaddress' => ['patientaddress', 'patientAddress'],
            'patienttype' => ['patienttype', 'patientType'],
            'patientadd' => ['patientadd', 'PatientAdd'],
            'isemergency' => ['isemergency', 'isEmergency'],
            'admissiondate' => ['admissiondate', 'admissionDate'],
            'dischargedate' => ['dischargedate', 'dischargeDate'],
            'bloodpressure' => ['bloodpressure', 'BloodPressure'],
            'bloodsugar' => ['bloodsugar', 'BloodSugar'],
            'temperature' => ['temperature', 'Temperature'],
            'medicalpres' => ['medicalpres', 'MedicalPres'],
            'fromtype' => ['fromtype', 'fromType'],
            'totype' => ['totype', 'toType'],
            'transferreason' => ['transferreason', 'transferReason'],
            'transferdate' => ['transferdate', 'transferDate'],
            'transferredat' => ['transferredat', 'transferredAt'],
            'logintime' => ['logintime', 'loginTime'],
            'docid' => ['docid', 'Docid'],
        ];

        $normalized = strtolower((string)$key);
        return $map[$normalized] ?? [$key];
    }
}

if (!function_exists('hms_expand_row_keys')) {
    function hms_expand_row_keys($row) {
        if (!is_array($row)) {
            return $row;
        }

        $expanded = [];
        $numericIndex = 0;
        foreach ($row as $key => $value) {
            if (is_int($key)) {
                $expanded[$key] = $value;
                continue;
            }

            foreach (hms_alias_key($key) as $alias) {
                $expanded[$alias] = $value;
            }
            $expanded[$numericIndex++] = $value;
        }

        return $expanded;
    }
}

if (!function_exists('hms_fetch_assoc')) {
    function hms_fetch_assoc($result) {
        if (!$result) {
            return false;
        }

        $row = mysqli_fetch_assoc($result);
        if ($row === null || $row === false) {
            return false;
        }

        return hms_expand_row_keys($row);
    }
}

if (!function_exists('hms_fetch_array')) {
    function hms_fetch_array($result) {
        if (!$result) {
            return false;
        }

        $row = mysqli_fetch_assoc($result);
        if ($row === null || $row === false) {
            return false;
        }

        return hms_expand_row_keys($row);
    }
}

if (!function_exists('hms_num_rows')) {
    function hms_num_rows($result) {
        return $result ? mysqli_num_rows($result) : 0;
    }
}

if (!function_exists('hms_last_insert_id')) {
    function hms_last_insert_id($con) {
        return (int)mysqli_insert_id($con);
    }
}

if (!function_exists('hms_query_params')) {
    function hms_query_params($con, $sql, array $params) {
        $replaced = $sql;
        for ($i = count($params); $i >= 1; $i--) {
            $val = isset($params[$i-1]) ? $params[$i-1] : null;
            if ($val === null) {
                $subst = "NULL";
            } else {
                $subst = "'" . mysqli_real_escape_string($con, (string)$val) . "'";
            }
            $replaced = str_replace('$' . $i, $subst, $replaced);
        }
        return mysqli_query($con, hms_normalize_sql($replaced));
    }
}

if (!function_exists('hms_normalize_sql')) {
    function hms_normalize_sql($sql) {
        // Keep backticks for MySQL; strip none but normalize MySQL-specific small differences
        $normalized = $sql;
        $normalized = str_ireplace('&&', 'AND', $normalized);
        // Remove Postgres-only AFTER column syntax if present
        $normalized = preg_replace('/\s+AFTER\s+[a-zA-Z0-9_]+/i', '', $normalized);
        return $normalized;
    }
}

if (!function_exists('hms_query')) {
    function hms_query($con, $sql) {
        $trimmed = trim((string)$sql);
        // For MySQL we can run most queries directly. Normalize SQL where needed.
        // Replace any Postgres-specific CREATE TABLE ... LIKE ... INCLUDING DEFAULTS -> MySQL: CREATE TABLE IF NOT EXISTS new LIKE source
        if (preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+past_appointments\s*\(LIKE\s+([a-zA-Z0-9_]+)\s+INCLUDING\s+DEFAULTS\)$/i', $trimmed, $m)) {
            $source = $m[1];
            $q = "CREATE TABLE IF NOT EXISTS past_appointments LIKE {$source}";
            return mysqli_query($con, $q);
        }

        return mysqli_query($con, hms_normalize_sql($sql));
    }
}

if (!function_exists('hms_table_exists')) {
    function hms_table_exists($con, $table) {
        $tbl = mysqli_real_escape_string($con, strtolower($table));
        $res = mysqli_query($con, "SHOW TABLES LIKE '{$tbl}'");
        return ($res && mysqli_num_rows($res) > 0);
    }
}

if (!function_exists('hms_column_exists')) {
    function hms_column_exists($con, $table, $column) {
        $t = mysqli_real_escape_string($con, $table);
        $c = mysqli_real_escape_string($con, $column);
        $res = mysqli_query($con, "SHOW COLUMNS FROM {$t} LIKE '{$c}'");
        return ($res && mysqli_num_rows($res) > 0);
    }
}

if (!function_exists('hms_get_table_columns')) {
    function hms_get_table_columns($con, $table) {
        $cols = [];
        $t = mysqli_real_escape_string($con, $table);
        $res = mysqli_query($con, "SHOW COLUMNS FROM {$t}");
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $cols[] = $r['Field'];
            }
        }
        return $cols;
    }
}

if (!function_exists('hms_ensure_schema')) {
    function hms_ensure_schema($con) {
        if (!$con) {
            return;
        }

        foreach (['current_appointments', 'appointment'] as $tableName) {
            if (!hms_table_exists($con, $tableName)) {
                continue;
            }

            $appointmentColumns = [
                'visitStatus' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS visitstatus varchar(30) NOT NULL DEFAULT 'Scheduled'",
                'checkInTime' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS checkintime datetime NULL",
                'checkOutTime' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS checkouttime datetime NULL",
                'prescription' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS prescription text NULL",
                'paymentStatus' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS paymentstatus varchar(20) NOT NULL DEFAULT 'Pending'",
                'paymentRef' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS paymentref varchar(64) NULL",
                'paidAt' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS paidat datetime NULL",
                'appointmentType' => "ALTER TABLE {$tableName} ADD COLUMN IF NOT EXISTS appointmenttype varchar(50) DEFAULT 'Online'",
            ];

            foreach ($appointmentColumns as $column => $ddl) {
                if (!hms_column_exists($con, $tableName, $column)) {
                    @mysqli_query($con, $ddl);
                }
            }
        }

        if (!hms_table_exists($con, 'prescriptions')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS prescriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                doctor_id INT NOT NULL,
                appointment_id INT NOT NULL,
                temperature varchar(20),
                blood_pressure varchar(30),
                pulse varchar(20),
                weight varchar(20),
                symptoms text,
                diagnosis text,
                tests text,
                notes text,
                medicines text,
                patient_name varchar(255),
                doctor_name varchar(255),
                next_visit_date date,
                created_at datetime DEFAULT CURRENT_TIMESTAMP
            )");
            @mysqli_query($con, "CREATE INDEX IF NOT EXISTS idx_prescriptions_appointment_id ON prescriptions (appointment_id)");
            @mysqli_query($con, "CREATE INDEX IF NOT EXISTS idx_prescriptions_patient_id ON prescriptions (patient_id)");
            @mysqli_query($con, "CREATE INDEX IF NOT EXISTS idx_prescriptions_doctor_id ON prescriptions (doctor_id)");
        } elseif (!hms_column_exists($con, 'prescriptions', 'medicines')) {
            @mysqli_query($con, "ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS medicines text NULL");
        }
    }
}

if (!function_exists('hms_ensure_past_appointments')) {
    function hms_ensure_past_appointments($con, $sourceTable) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sourceTable)) {
            return false;
        }
        $sourceTable = $sourceTable;

        if (!hms_table_exists($con, $sourceTable)) {
            return false;
        }

        if (!hms_table_exists($con, 'past_appointments')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS past_appointments LIKE {$sourceTable}");
        }

        if (!hms_table_exists($con, 'past_appointments')) {
            return false;
        }

        @mysqli_query($con, "ALTER TABLE past_appointments ADD COLUMN IF NOT EXISTS originalappointmentid INT NOT NULL DEFAULT 0");
        @mysqli_query($con, "ALTER TABLE past_appointments ADD COLUMN IF NOT EXISTS sourcetable varchar(64) NOT NULL DEFAULT '");
        @mysqli_query($con, "ALTER TABLE past_appointments ADD COLUMN IF NOT EXISTS archivedat datetime NULL");
        return true;
    }
}

if (!function_exists('hms_archive_appointment')) {
    function hms_archive_appointment($con, $sourceTable, $appointmentId) {
        $appointmentId = (int)$appointmentId;
        if ($appointmentId <= 0 || !preg_match('/^[a-zA-Z0-9_]+$/', $sourceTable)) {
            return false;
        }
        $sourceTable = $sourceTable;

        if (!hms_ensure_past_appointments($con, $sourceTable)) {
            return false;
        }

        $dupQ = mysqli_query($con, "SELECT id FROM past_appointments WHERE originalappointmentid = {$appointmentId} AND sourcetable = '" . mysqli_real_escape_string($con, $sourceTable) . "' LIMIT 1");
        if ($dupQ && mysqli_num_rows($dupQ) > 0) {
            return true;
        }

        $srcQ = mysqli_query($con, "SELECT * FROM {$sourceTable} WHERE id = {$appointmentId} LIMIT 1");
        if (!$srcQ || mysqli_num_rows($srcQ) === 0) {
            return false;
        }

        $srcRow = mysqli_fetch_assoc($srcQ);
        $pastCols = hms_get_table_columns($con, 'past_appointments');

        $insertCols = [];
        $insertVals = [];
        foreach ($srcRow as $col => $val) {
            if ($col === 'id' || !in_array($col, $pastCols, true)) {
                continue;
            }
            $insertCols[] = $col;
            $insertVals[] = is_null($val) ? 'NULL' : "'" . mysqli_real_escape_string($con, (string)$val) . "'";
        }

        if (in_array('originalappointmentid', $pastCols, true)) {
            $insertCols[] = 'originalappointmentid';
            $insertVals[] = (string)$appointmentId;
        }
        if (in_array('sourcetable', $pastCols, true)) {
            $insertCols[] = 'sourcetable';
            $insertVals[] = "'" . mysqli_real_escape_string($con, $sourceTable) . "'";
        }
        if (in_array('archivedat', $pastCols, true)) {
            $insertCols[] = 'archivedat';
            $insertVals[] = 'CURRENT_TIMESTAMP';
        }

        if (empty($insertCols)) {
            return false;
        }

        $insertSql = 'INSERT INTO past_appointments (' . implode(',', $insertCols) . ') VALUES (' . implode(',', $insertVals) . ')';
        return (bool)mysqli_query($con, $insertSql);
    }
}

hms_ensure_schema($con);
?>
