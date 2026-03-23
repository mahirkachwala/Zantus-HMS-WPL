-- HMS update: admin logs/patient normalization and legacy cleanup
-- Date: 2026-03-23

USE `hms`;

-- 1) Ensure userlog table exists and has basic indexes for admin logs page
CREATE TABLE IF NOT EXISTS `userlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `userip` varchar(45) DEFAULT NULL,
  `loginTime` timestamp NULL DEFAULT current_timestamp(),
  `logout` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `userlog`
  ADD INDEX `idx_userlog_uid` (`uid`),
  ADD INDEX `idx_userlog_logintime` (`loginTime`),
  ADD INDEX `idx_userlog_status` (`status`);

-- 2) Remove legacy medical history schema (frontend moved away from it)
ALTER TABLE `tblpatient`
  DROP COLUMN IF EXISTS `PatientMedhis`;

DROP TABLE IF EXISTS `tblmedicalhistory`;

-- 3) Optional helpful indexes for new patient management tables
ALTER TABLE `patients`
  ADD INDEX `idx_patients_doctor_type` (`doctorId`, `patientType`),
  ADD INDEX `idx_patients_status` (`status`),
  ADD INDEX `idx_patients_createdAt` (`createdAt`);

-- 4) Optional helpful indexes for appointment visibility pages
ALTER TABLE `current_appointments`
  ADD INDEX `idx_current_appt_status_mix` (`doctorId`, `userStatus`, `doctorStatus`, `visitStatus`),
  ADD INDEX `idx_current_appt_payment_mix` (`paymentStatus`, `paymentOption`);

-- Note:
-- If your DB does not have `current_appointments`, run corresponding ALTER statements on `appointment`.
