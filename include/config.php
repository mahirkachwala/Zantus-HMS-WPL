<?php
require_once __DIR__ . '/session.php';
hms_configure_runtime();

// MySQL (mysqli) connection and compatibility helpers
define('DB_HOST', 'sql204.byethost10.com');
define('DB_PORT', '3306');
define('DB_NAME', 'b10_41663109_HMS');
define('DB_USER', 'b10_41663109');
define('DB_PASS', '@Esa9Yfi#_3W5Ud');
define('DB_TIMEZONE_OFFSET', '+05:30');

if (!function_exists('mysqli_connect')) {
    die('MySQLi extension is not enabled in PHP. Enable mysqli in XAMPP.');
}

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);

if (!$con) {
    die('Failed to connect to MySQL: ' . mysqli_connect_error());
}

mysqli_set_charset($con, 'utf8');
@mysqli_query($con, "SET time_zone = '" . DB_TIMEZONE_OFFSET . "'");

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

if (!function_exists('hms_index_exists')) {
    function hms_index_exists($con, $table, $indexName) {
        $t = mysqli_real_escape_string($con, $table);
        $i = mysqli_real_escape_string($con, $indexName);
        $res = mysqli_query($con, "SHOW INDEX FROM {$t} WHERE Key_name = '{$i}'");
        return ($res && mysqli_num_rows($res) > 0);
    }
}

if (!function_exists('hms_add_column_if_missing')) {
    function hms_add_column_if_missing($con, $table, $column, $definitionSql) {
        if (hms_column_exists($con, $table, $column)) {
            return true;
        }

        $t = mysqli_real_escape_string($con, $table);
        return (bool)@mysqli_query($con, "ALTER TABLE {$t} ADD COLUMN {$definitionSql}");
    }
}

if (!function_exists('hms_create_index_if_missing')) {
    function hms_create_index_if_missing($con, $table, $indexName, $columnsSql) {
        if (hms_index_exists($con, $table, $indexName)) {
            return true;
        }

        $t = mysqli_real_escape_string($con, $table);
        $i = mysqli_real_escape_string($con, $indexName);
        return (bool)@mysqli_query($con, "CREATE INDEX {$i} ON {$t} ({$columnsSql})");
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
                'visitStatus' => "visitStatus varchar(30) NOT NULL DEFAULT 'Scheduled'",
                'checkInTime' => "checkInTime datetime NULL",
                'checkOutTime' => "checkOutTime datetime NULL",
                'prescription' => "prescription text NULL",
                'paymentStatus' => "paymentStatus varchar(20) NOT NULL DEFAULT 'Pending'",
                'paymentRef' => "paymentRef varchar(64) NULL",
                'paidAt' => "paidAt datetime NULL",
                'appointmentType' => "appointmentType varchar(50) DEFAULT 'Online'",
            ];

            foreach ($appointmentColumns as $column => $definitionSql) {
                hms_add_column_if_missing($con, $tableName, $column, $definitionSql);
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
            hms_create_index_if_missing($con, 'prescriptions', 'idx_prescriptions_appointment_id', 'appointment_id');
            hms_create_index_if_missing($con, 'prescriptions', 'idx_prescriptions_patient_id', 'patient_id');
            hms_create_index_if_missing($con, 'prescriptions', 'idx_prescriptions_doctor_id', 'doctor_id');
        } elseif (!hms_column_exists($con, 'prescriptions', 'medicines')) {
            hms_add_column_if_missing($con, 'prescriptions', 'medicines', 'medicines text NULL');
        }
    }
}

if (!function_exists('hms_ensure_support_schema')) {
    function hms_ensure_support_schema($con) {
        if (!$con) {
            return;
        }

        if (!hms_table_exists($con, 'contact_queries')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_queries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                portal_type VARCHAR(20) NOT NULL,
                user_id INT DEFAULT NULL,
                doctor_id INT DEFAULT NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'New',
                admin_note TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $contactQueryColumns = [
            'portal_type' => "portal_type VARCHAR(20) NOT NULL DEFAULT 'user'",
            'user_id' => "user_id INT DEFAULT NULL",
            'doctor_id' => "doctor_id INT DEFAULT NULL",
            'name' => "name VARCHAR(150) NOT NULL DEFAULT ''",
            'email' => "email VARCHAR(150) NOT NULL DEFAULT ''",
            'phone' => "phone VARCHAR(30) DEFAULT NULL",
            'subject' => "subject VARCHAR(200) NOT NULL DEFAULT ''",
            'message' => "message TEXT NOT NULL",
            'status' => "status VARCHAR(20) NOT NULL DEFAULT 'New'",
            'admin_note' => "admin_note TEXT DEFAULT NULL",
            'created_at' => "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updated_at' => "updated_at DATETIME DEFAULT NULL",
        ];
        foreach ($contactQueryColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'contact_queries', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'contact_queries', 'idx_contact_queries_portal_status', 'portal_type, status');
        hms_create_index_if_missing($con, 'contact_queries', 'idx_contact_queries_user_id', 'user_id');
        hms_create_index_if_missing($con, 'contact_queries', 'idx_contact_queries_doctor_id', 'doctor_id');

        if (!hms_table_exists($con, 'contact_query_history')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_query_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                original_query_id INT NOT NULL,
                portal_type VARCHAR(20) NOT NULL,
                user_id INT DEFAULT NULL,
                doctor_id INT DEFAULT NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(30) DEFAULT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                final_status VARCHAR(20) NOT NULL DEFAULT 'Closed',
                admin_note TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                disposed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                disposed_by VARCHAR(120) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $contactHistoryColumns = [
            'original_query_id' => "original_query_id INT NOT NULL DEFAULT 0",
            'portal_type' => "portal_type VARCHAR(20) NOT NULL DEFAULT 'user'",
            'user_id' => "user_id INT DEFAULT NULL",
            'doctor_id' => "doctor_id INT DEFAULT NULL",
            'name' => "name VARCHAR(150) NOT NULL DEFAULT ''",
            'email' => "email VARCHAR(150) NOT NULL DEFAULT ''",
            'phone' => "phone VARCHAR(30) DEFAULT NULL",
            'subject' => "subject VARCHAR(200) NOT NULL DEFAULT ''",
            'message' => "message TEXT NOT NULL",
            'final_status' => "final_status VARCHAR(20) NOT NULL DEFAULT 'Closed'",
            'admin_note' => "admin_note TEXT NOT NULL",
            'created_at' => "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'disposed_at' => "disposed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'disposed_by' => "disposed_by VARCHAR(120) DEFAULT NULL",
        ];
        foreach ($contactHistoryColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'contact_query_history', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'contact_query_history', 'idx_contact_query_history_original_query_id', 'original_query_id');
        hms_create_index_if_missing($con, 'contact_query_history', 'idx_contact_query_history_portal', 'portal_type');
        hms_create_index_if_missing($con, 'contact_query_history', 'idx_contact_query_history_user_id', 'user_id');
        hms_create_index_if_missing($con, 'contact_query_history', 'idx_contact_query_history_doctor_id', 'doctor_id');
        hms_create_index_if_missing($con, 'contact_query_history', 'idx_contact_query_history_disposed_at', 'disposed_at');

        if (!hms_table_exists($con, 'feedback_entries')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS feedback_entries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                portal_type VARCHAR(20) NOT NULL,
                user_id INT DEFAULT NULL,
                doctor_id INT DEFAULT NULL,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL,
                rating TINYINT DEFAULT NULL,
                feedback_text TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'New',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        $feedbackColumns = [
            'portal_type' => "portal_type VARCHAR(20) NOT NULL DEFAULT 'user'",
            'user_id' => "user_id INT DEFAULT NULL",
            'doctor_id' => "doctor_id INT DEFAULT NULL",
            'name' => "name VARCHAR(150) NOT NULL DEFAULT ''",
            'email' => "email VARCHAR(150) NOT NULL DEFAULT ''",
            'rating' => "rating TINYINT DEFAULT NULL",
            'feedback_text' => "feedback_text TEXT NOT NULL",
            'status' => "status VARCHAR(20) NOT NULL DEFAULT 'New'",
            'created_at' => "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];
        foreach ($feedbackColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'feedback_entries', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'feedback_entries', 'idx_feedback_entries_portal_status', 'portal_type, status');
        hms_create_index_if_missing($con, 'feedback_entries', 'idx_feedback_entries_user_id', 'user_id');
        hms_create_index_if_missing($con, 'feedback_entries', 'idx_feedback_entries_doctor_id', 'doctor_id');
        hms_create_index_if_missing($con, 'feedback_entries', 'idx_feedback_entries_created_at', 'created_at');

        if (!hms_table_exists($con, 'appointment_transfers')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS appointment_transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                originalAppointmentId INT NOT NULL,
                transferredAppointmentId INT DEFAULT NULL,
                patientId INT DEFAULT NULL,
                doctorId INT DEFAULT NULL,
                fromType VARCHAR(30) NOT NULL DEFAULT 'Consultancy',
                toType VARCHAR(30) NOT NULL DEFAULT 'Admitted',
                transferReason TEXT DEFAULT NULL,
                transferDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                transferredAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $transferColumns = [
            'originalAppointmentId' => "originalAppointmentId INT NOT NULL DEFAULT 0",
            'transferredAppointmentId' => "transferredAppointmentId INT DEFAULT NULL",
            'patientId' => "patientId INT DEFAULT NULL",
            'doctorId' => "doctorId INT DEFAULT NULL",
            'fromType' => "fromType VARCHAR(30) NOT NULL DEFAULT 'Consultancy'",
            'toType' => "toType VARCHAR(30) NOT NULL DEFAULT 'Admitted'",
            'transferReason' => "transferReason TEXT DEFAULT NULL",
            'transferDate' => "transferDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'transferredAt' => "transferredAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];
        foreach ($transferColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'appointment_transfers', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'appointment_transfers', 'idx_appointment_transfers_original_appointment', 'originalAppointmentId');
        hms_create_index_if_missing($con, 'appointment_transfers', 'idx_appointment_transfers_patient_id', 'patientId');
        hms_create_index_if_missing($con, 'appointment_transfers', 'idx_appointment_transfers_doctor_id', 'doctorId');

        if (!hms_table_exists($con, 'patients')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS patients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                userId INT DEFAULT NULL,
                doctorId INT DEFAULT NULL,
                patientName VARCHAR(255) NOT NULL,
                patientEmail VARCHAR(255) DEFAULT NULL,
                patientPhone VARCHAR(30) DEFAULT NULL,
                patientGender VARCHAR(20) DEFAULT NULL,
                patientAge INT DEFAULT NULL,
                patientAddress TEXT DEFAULT NULL,
                patientType VARCHAR(50) NOT NULL DEFAULT 'consultancy',
                status VARCHAR(20) NOT NULL DEFAULT 'Active',
                isEmergency TINYINT(1) NOT NULL DEFAULT 0,
                admissionDate DATETIME DEFAULT NULL,
                dischargeDate DATETIME DEFAULT NULL,
                notes TEXT DEFAULT NULL,
                createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updatedAt DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $patientColumns = [
            'userId' => "userId INT DEFAULT NULL",
            'doctorId' => "doctorId INT DEFAULT NULL",
            'patientName' => "patientName VARCHAR(255) NOT NULL DEFAULT ''",
            'patientEmail' => "patientEmail VARCHAR(255) DEFAULT NULL",
            'patientPhone' => "patientPhone VARCHAR(30) DEFAULT NULL",
            'patientGender' => "patientGender VARCHAR(20) DEFAULT NULL",
            'patientAge' => "patientAge INT DEFAULT NULL",
            'patientAddress' => "patientAddress TEXT DEFAULT NULL",
            'patientType' => "patientType VARCHAR(50) NOT NULL DEFAULT 'consultancy'",
            'status' => "status VARCHAR(20) NOT NULL DEFAULT 'Active'",
            'isEmergency' => "isEmergency TINYINT(1) NOT NULL DEFAULT 0",
            'admissionDate' => "admissionDate DATETIME DEFAULT NULL",
            'dischargeDate' => "dischargeDate DATETIME DEFAULT NULL",
            'notes' => "notes TEXT DEFAULT NULL",
            'createdAt' => "createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'updatedAt' => "updatedAt DATETIME DEFAULT NULL",
        ];
        foreach ($patientColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'patients', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'patients', 'idx_patients_user_id', 'userId');
        hms_create_index_if_missing($con, 'patients', 'idx_patients_doctor_id', 'doctorId');
        hms_create_index_if_missing($con, 'patients', 'idx_patients_type_status', 'patientType, status');

        if (!hms_table_exists($con, 'payment_transactions')) {
            @mysqli_query($con, "CREATE TABLE IF NOT EXISTS payment_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                appointment_id INT NOT NULL,
                user_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'Card',
                transaction_ref VARCHAR(64) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Paid',
                paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $paymentColumns = [
            'appointment_id' => "appointment_id INT NOT NULL DEFAULT 0",
            'user_id' => "user_id INT NOT NULL DEFAULT 0",
            'amount' => "amount DECIMAL(10,2) NOT NULL DEFAULT 0.00",
            'payment_method' => "payment_method VARCHAR(50) NOT NULL DEFAULT 'Card'",
            'transaction_ref' => "transaction_ref VARCHAR(64) NOT NULL DEFAULT ''",
            'status' => "status VARCHAR(20) NOT NULL DEFAULT 'Paid'",
            'paid_at' => "paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            'created_at' => "created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];
        foreach ($paymentColumns as $column => $definitionSql) {
            hms_add_column_if_missing($con, 'payment_transactions', $column, $definitionSql);
        }
        hms_create_index_if_missing($con, 'payment_transactions', 'idx_payment_transactions_appointment_id', 'appointment_id');
        hms_create_index_if_missing($con, 'payment_transactions', 'idx_payment_transactions_user_id', 'user_id');
        hms_create_index_if_missing($con, 'payment_transactions', 'idx_payment_transactions_status', 'status');
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

        hms_add_column_if_missing($con, 'past_appointments', 'originalappointmentid', "originalappointmentid INT NOT NULL DEFAULT 0");
        hms_add_column_if_missing($con, 'past_appointments', 'sourcetable', "sourcetable varchar(64) NOT NULL DEFAULT ''");
        hms_add_column_if_missing($con, 'past_appointments', 'archivedat', "archivedat datetime NULL");
        return true;
    }
}

if (!function_exists('hms_record_payment_transaction')) {
    function hms_record_payment_transaction($con, $appointmentId, $userId, $amount, $paymentMethod, $transactionRef, $status = 'Paid', $paidAt = null) {
        $appointmentId = (int)$appointmentId;
        $userId = (int)$userId;
        $amount = (float)$amount;
        $paymentMethod = trim((string)$paymentMethod);
        $transactionRef = trim((string)$transactionRef);
        $status = trim((string)$status);
        $paidAt = trim((string)$paidAt);

        if ($appointmentId <= 0 || $userId <= 0 || $transactionRef === '') {
            return false;
        }

        if ($paymentMethod === '') {
            $paymentMethod = 'Card';
        }
        if ($status === '') {
            $status = 'Paid';
        }

        if (!hms_table_exists($con, 'payment_transactions')) {
            hms_ensure_support_schema($con);
        }
        if (!hms_table_exists($con, 'payment_transactions')) {
            return false;
        }

        $existing = hms_query_params($con, "SELECT id FROM payment_transactions WHERE transaction_ref=$1 LIMIT 1", [$transactionRef]);
        if ($existing && hms_num_rows($existing) > 0) {
            return true;
        }

        $amountText = number_format($amount, 2, '.', '');
        $paidAtSql = ($paidAt !== '') ? "'" . hms_escape($con, $paidAt) . "'" : "CURRENT_TIMESTAMP";

        return (bool)hms_query(
            $con,
            "INSERT INTO payment_transactions(appointment_id, user_id, amount, payment_method, transaction_ref, status, paid_at, created_at)
            VALUES(
                '$appointmentId',
                '$userId',
                '$amountText',
                '" . hms_escape($con, $paymentMethod) . "',
                '" . hms_escape($con, $transactionRef) . "',
                '" . hms_escape($con, $status) . "',
                $paidAtSql,
                CURRENT_TIMESTAMP
            )"
        );
    }
}

if (!function_exists('hms_get_latest_payment_transaction')) {
    function hms_get_latest_payment_transaction($con, $appointmentId, $userId) {
        $appointmentId = (int)$appointmentId;
        $userId = (int)$userId;

        if ($appointmentId <= 0 || $userId <= 0 || !hms_table_exists($con, 'payment_transactions')) {
            return null;
        }

        $result = hms_query_params(
            $con,
            "SELECT * FROM payment_transactions WHERE appointment_id=$1 AND user_id=$2 ORDER BY paid_at DESC, id DESC LIMIT 1",
            [$appointmentId, $userId]
        );
        return $result ? hms_fetch_assoc($result) : null;
    }
}

if (!function_exists('hms_payment_transaction_implies_paid')) {
    function hms_payment_transaction_implies_paid($paymentTransaction) {
        if (!is_array($paymentTransaction) || empty($paymentTransaction)) {
            return false;
        }

        $status = strtolower(trim((string)($paymentTransaction['status'] ?? '')));
        return in_array($status, ['paid', 'paid at hospital', 'success'], true);
    }
}

if (!function_exists('hms_sync_appointment_payment_from_transaction')) {
    function hms_sync_appointment_payment_from_transaction($con, $table, $appointmentId, $userId, $paymentTransaction) {
        $appointmentId = (int)$appointmentId;
        $userId = (int)$userId;
        $table = trim((string)$table);

        if ($appointmentId <= 0 || $userId <= 0 || $table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        if (!hms_payment_transaction_implies_paid($paymentTransaction)) {
            return false;
        }

        $paymentMethod = strtolower(trim((string)($paymentTransaction['payment_method'] ?? '')));
        $status = $paymentMethod === 'pay at hospital' ? 'Paid at Hospital' : 'Paid';
        $transactionRef = trim((string)($paymentTransaction['transaction_ref'] ?? ''));
        $paidAt = trim((string)($paymentTransaction['paid_at'] ?? ''));
        if ($paidAt === '') {
            $paidAt = date('Y-m-d H:i:s');
        }
        if ($transactionRef === '') {
            return false;
        }

        $result = hms_query_params(
            $con,
            "UPDATE $table SET paymentStatus=$1, paymentRef=$2, paidAt=$3 WHERE id=$4 AND userId=$5",
            [$status, $transactionRef, $paidAt, $appointmentId, $userId]
        );
        return (bool)$result;
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
hms_ensure_support_schema($con);
