-- Zantus HMS
-- Clean MySQL schema for the current PHP codebase
-- Legacy tables removed from this schema: appointment, tblpatient, tblmedicalhistory, tblcontactus
-- Import target: MySQL / MariaDB
-- Time zone: IST

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+05:30';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `contact_query_history`;
DROP TABLE IF EXISTS `contact_queries`;
DROP TABLE IF EXISTS `feedback_entries`;
DROP TABLE IF EXISTS `payment_transactions`;
DROP TABLE IF EXISTS `appointment_transfers`;
DROP TABLE IF EXISTS `prescriptions`;
DROP TABLE IF EXISTS `past_appointments`;
DROP TABLE IF EXISTS `current_appointments`;
DROP TABLE IF EXISTS `appointment`;
DROP TABLE IF EXISTS `tblmedicalhistory`;
DROP TABLE IF EXISTS `patients`;
DROP TABLE IF EXISTS `tblpatient`;
DROP TABLE IF EXISTS `userlog`;
DROP TABLE IF EXISTS `doctorslog`;
DROP TABLE IF EXISTS `doctors`;
DROP TABLE IF EXISTS `doctorspecialization`;
DROP TABLE IF EXISTS `tblcontactus`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admin`;

CREATE TABLE `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `doctorspecialization` (
  `id` int NOT NULL AUTO_INCREMENT,
  `specialization` varchar(255) NOT NULL,
  `creationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctorspecialization_specialization` (`specialization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `doctors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `specialization` int DEFAULT NULL,
  `doctorName` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `docFees` varchar(255) DEFAULT NULL,
  `contactno` varchar(20) DEFAULT NULL,
  `docEmail` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `creationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctors_docEmail` (`docEmail`),
  KEY `idx_doctors_specialization` (`specialization`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullName` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `regDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `current_appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctorSpecialization` varchar(255) DEFAULT NULL,
  `doctorId` int DEFAULT NULL,
  `userId` int DEFAULT NULL,
  `patientId` int DEFAULT NULL,
  `consultancyFees` int DEFAULT NULL,
  `appointmentDate` varchar(255) DEFAULT NULL,
  `appointmentTime` varchar(255) DEFAULT NULL,
  `postingDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `userStatus` int DEFAULT 1,
  `doctorStatus` int DEFAULT 1,
  `visitStatus` varchar(30) NOT NULL DEFAULT 'Scheduled',
  `checkInTime` datetime DEFAULT NULL,
  `checkOutTime` datetime DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `paymentStatus` varchar(20) NOT NULL DEFAULT 'Pending',
  `paymentRef` varchar(64) DEFAULT NULL,
  `paidAt` datetime DEFAULT NULL,
  `appointmentType` varchar(50) DEFAULT 'Online',
  `paymentOption` varchar(30) DEFAULT 'BookOnly',
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_current_appointments_userId` (`userId`),
  KEY `idx_current_appointments_doctorId` (`doctorId`),
  KEY `idx_current_appointments_patientId` (`patientId`),
  KEY `idx_current_appointments_visitStatus` (`visitStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `past_appointments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `doctorSpecialization` varchar(255) DEFAULT NULL,
  `doctorId` int DEFAULT NULL,
  `userId` int DEFAULT NULL,
  `patientId` int DEFAULT NULL,
  `consultancyFees` int DEFAULT NULL,
  `appointmentDate` varchar(255) DEFAULT NULL,
  `appointmentTime` varchar(255) DEFAULT NULL,
  `postingDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `userStatus` int DEFAULT 1,
  `doctorStatus` int DEFAULT 1,
  `visitStatus` varchar(30) NOT NULL DEFAULT 'Scheduled',
  `checkInTime` datetime DEFAULT NULL,
  `checkOutTime` datetime DEFAULT NULL,
  `prescription` text DEFAULT NULL,
  `paymentStatus` varchar(20) NOT NULL DEFAULT 'Pending',
  `paymentRef` varchar(64) DEFAULT NULL,
  `paidAt` datetime DEFAULT NULL,
  `appointmentType` varchar(50) DEFAULT 'Online',
  `paymentOption` varchar(30) DEFAULT 'BookOnly',
  `originalappointmentid` int NOT NULL DEFAULT 0,
  `sourcetable` varchar(64) NOT NULL DEFAULT '',
  `archivedat` datetime DEFAULT NULL,
  `updationDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_past_appointments_userId` (`userId`),
  KEY `idx_past_appointments_doctorId` (`doctorId`),
  KEY `idx_past_appointments_patientId` (`patientId`),
  KEY `idx_past_appointments_originalappointmentid` (`originalappointmentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `patients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userId` int DEFAULT NULL,
  `doctorId` int DEFAULT NULL,
  `patientName` varchar(255) NOT NULL,
  `patientEmail` varchar(255) DEFAULT NULL,
  `patientPhone` varchar(30) DEFAULT NULL,
  `patientGender` varchar(20) DEFAULT NULL,
  `patientAge` int DEFAULT NULL,
  `patientAddress` text DEFAULT NULL,
  `patientType` varchar(50) NOT NULL DEFAULT 'consultancy',
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `isEmergency` tinyint(1) NOT NULL DEFAULT 0,
  `admissionDate` datetime DEFAULT NULL,
  `dischargeDate` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_patients_userId` (`userId`),
  KEY `idx_patients_doctorId` (`doctorId`),
  KEY `idx_patients_type_status` (`patientType`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `prescriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `patient_id` int NOT NULL,
  `doctor_id` int NOT NULL,
  `appointment_id` int NOT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(30) DEFAULT NULL,
  `pulse` varchar(20) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `tests` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `medicines` text DEFAULT NULL,
  `patient_name` varchar(255) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `next_visit_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prescriptions_patient_id` (`patient_id`),
  KEY `idx_prescriptions_doctor_id` (`doctor_id`),
  KEY `idx_prescriptions_appointment_id` (`appointment_id`),
  KEY `idx_prescriptions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `appointment_transfers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `originalAppointmentId` int NOT NULL,
  `transferredAppointmentId` int DEFAULT NULL,
  `patientId` int DEFAULT NULL,
  `doctorId` int DEFAULT NULL,
  `fromType` varchar(30) NOT NULL DEFAULT 'Consultancy',
  `toType` varchar(30) NOT NULL DEFAULT 'Admitted',
  `transferReason` text DEFAULT NULL,
  `transferDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `transferredAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_transfers_originalAppointmentId` (`originalAppointmentId`),
  KEY `idx_appointment_transfers_patientId` (`patientId`),
  KEY `idx_appointment_transfers_doctorId` (`doctorId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payment_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `appointment_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Card',
  `transaction_ref` varchar(64) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Paid',
  `paid_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_transactions_transaction_ref` (`transaction_ref`),
  KEY `idx_payment_transactions_appointment_id` (`appointment_id`),
  KEY `idx_payment_transactions_user_id` (`user_id`),
  KEY `idx_payment_transactions_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `contact_queries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `portal_type` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_queries_portal_status` (`portal_type`, `status`),
  KEY `idx_contact_queries_user_id` (`user_id`),
  KEY `idx_contact_queries_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `contact_query_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_query_id` int NOT NULL,
  `portal_type` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `final_status` varchar(20) NOT NULL DEFAULT 'Closed',
  `admin_note` text NOT NULL,
  `created_at` datetime NOT NULL,
  `disposed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `disposed_by` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contact_query_history_original_portal` (`original_query_id`, `portal_type`),
  KEY `idx_contact_query_history_user_id` (`user_id`),
  KEY `idx_contact_query_history_doctor_id` (`doctor_id`),
  KEY `idx_contact_query_history_disposed_at` (`disposed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `feedback_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `portal_type` varchar(20) NOT NULL,
  `user_id` int DEFAULT NULL,
  `doctor_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `rating` tinyint DEFAULT NULL,
  `feedback_text` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'New',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_entries_portal_status` (`portal_type`, `status`),
  KEY `idx_feedback_entries_user_id` (`user_id`),
  KEY `idx_feedback_entries_doctor_id` (`doctor_id`),
  KEY `idx_feedback_entries_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `userlog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` varchar(45) DEFAULT NULL,
  `loginTime` datetime DEFAULT CURRENT_TIMESTAMP,
  `logout` varchar(255) DEFAULT NULL,
  `status` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_userlog_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `doctorslog` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` int DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` varchar(45) DEFAULT NULL,
  `loginTime` datetime DEFAULT CURRENT_TIMESTAMP,
  `logout` varchar(255) DEFAULT NULL,
  `status` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_doctorslog_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin` (`id`, `username`, `password`, `updationDate`) VALUES
(1, 'admin', '$2y$10$NdLebFjq.zXI2Hsg.XtDIuqJnpGrOyFnMTlDRIlc.g8H7KYr33Ykq', NOW());

INSERT INTO `doctorspecialization` (`id`, `specialization`) VALUES
(1, 'General Physician'),
(2, 'Cardiologist'),
(3, 'Dermatologist'),
(4, 'Neurologist'),
(5, 'Orthopedic'),
(6, 'Pediatrician'),
(7, 'Psychiatrist'),
(8, 'Gynecologist'),
(9, 'ENT Specialist'),
(10, 'Ophthalmologist');

INSERT INTO `doctors` (`id`, `specialization`, `doctorName`, `address`, `docFees`, `contactno`, `docEmail`, `password`) VALUES
(1, 1, 'Dr. Rajesh Sharma', 'Mumbai', '800', '9876543201', 'doctor-general@gmail.com', '$2y$10$jdtQwmwHWOXhsRDyssUN/eQRqNN9QqtlOSm3vSaVcQ0J1KENhy4Wy'),
(2, 2, 'Dr. Neha Mehta', 'Delhi', '900', '9876543202', 'doctor-cardio@gmail.com', '$2y$10$jdtQwmwHWOXhsRDyssUN/eQRqNN9QqtlOSm3vSaVcQ0J1KENhy4Wy'),
(3, 3, 'Dr. Amit Verma', 'Pune', '700', '9876543203', 'doctor-derma@gmail.com', '$2y$10$jdtQwmwHWOXhsRDyssUN/eQRqNN9QqtlOSm3vSaVcQ0J1KENhy4Wy'),
(4, 4, 'Dr. Priya Singh', 'Bangalore', '1000', '9876543204', 'doctor-neuro@gmail.com', '$2y$10$jdtQwmwHWOXhsRDyssUN/eQRqNN9QqtlOSm3vSaVcQ0J1KENhy4Wy'),
(5, 5, 'Dr. Karan Patel', 'Ahmedabad', '850', '9876543205', 'doctor-ortho@gmail.com', '$2y$10$jdtQwmwHWOXhsRDyssUN/eQRqNN9QqtlOSm3vSaVcQ0J1KENhy4Wy');

INSERT INTO `users` (`id`, `fullName`, `address`, `city`, `gender`, `email`, `password`) VALUES
(1, 'Amit Shah', 'Andheri East', 'Mumbai', 'Male', 'amit@gmail.com', 'amit123'),
(2, 'Neha Mehta', 'Connaught Place', 'Delhi', 'Female', 'neha@gmail.com', 'neha123'),
(3, 'Rahul Verma', 'MG Road', 'Bangalore', 'Male', 'rahul@gmail.com', 'rahul123'),
(4, 'Priya Singh', 'Aliganj', 'Lucknow', 'Female', 'priya@gmail.com', 'priya123'),
(5, 'Karan Patel', 'Navrangpura', 'Ahmedabad', 'Male', 'karan@gmail.com', 'karan123'),
(6, 'Sneha Joshi', 'Shivaji Nagar', 'Pune', 'Female', 'sneha@gmail.com', 'sneha123'),
(7, 'Arjun Nair', 'Kakkanad', 'Kochi', 'Male', 'arjun@gmail.com', 'arjun123'),
(8, 'Pooja Reddy', 'Banjara Hills', 'Hyderabad', 'Female', 'pooja@gmail.com', 'pooja123'),
(9, 'Vikas Gupta', 'Salt Lake', 'Kolkata', 'Male', 'vikas@gmail.com', 'vikas123'),
(10, 'Anjali Desai', 'Borivali West', 'Mumbai', 'Female', 'anjali@gmail.com', 'anjali123'),
(11, 'Rohit Agarwal', 'Sector 62', 'Noida', 'Male', 'rohit@gmail.com', '12345'),
(12, 'Sana Sheikh', 'Bandra', 'Mumbai', 'Female', 'sana@gmail.com', '12345'),
(13, 'Varun Saxena', 'Indirapuram', 'Ghaziabad', 'Male', 'varun@gmail.com', '12345'),
(14, 'Ishita Roy', 'Salt Lake', 'Kolkata', 'Female', 'ishita@gmail.com', '12345'),
(15, 'Aditya Mishra', 'Hazratganj', 'Lucknow', 'Male', 'aditya@gmail.com', '12345'),
(16, 'Mehak Jain', 'Malviya Nagar', 'Delhi', 'Female', 'mehak@gmail.com', '12345'),
(17, 'Siddharth Rao', 'Whitefield', 'Bangalore', 'Male', 'sid@gmail.com', '12345'),
(18, 'Naina Kapoor', 'Janakpuri', 'Delhi', 'Female', 'naina@gmail.com', '12345'),
(19, 'Harsh Vardhan', 'Patna City', 'Patna', 'Male', 'harsh@gmail.com', '12345'),
(20, 'Riya Sen', 'Park Street', 'Kolkata', 'Female', 'riya@gmail.com', '12345'),
(21, 'Mahir', NULL, NULL, NULL, 'kachwalamahir17@gmail.com', 'hospital2026');

SET FOREIGN_KEY_CHECKS = 1;

-- Default credentials
-- Admin:  admin / admin123
-- Doctor: doctor-general@gmail.com / doctor123
-- User:   user email / plain-text password from the users seed above
