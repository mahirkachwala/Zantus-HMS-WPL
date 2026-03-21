-- Run this in phpMyAdmin on the hms database
-- Adds visit + payment tracking fields used by doctor/admin/patient portals

ALTER TABLE `appointment`
  ADD COLUMN `visitStatus` varchar(30) NOT NULL DEFAULT 'Scheduled' AFTER `doctorStatus`,
  ADD COLUMN `checkInTime` datetime DEFAULT NULL AFTER `visitStatus`,
  ADD COLUMN `checkOutTime` datetime DEFAULT NULL AFTER `checkInTime`,
  ADD COLUMN `prescription` mediumtext DEFAULT NULL AFTER `checkOutTime`,
  ADD COLUMN `paymentStatus` varchar(20) NOT NULL DEFAULT 'Pending' AFTER `prescription`,
  ADD COLUMN `paymentRef` varchar(64) DEFAULT NULL AFTER `paymentStatus`,
  ADD COLUMN `paidAt` datetime DEFAULT NULL AFTER `paymentRef`;

-- Keep existing rows consistent
UPDATE `appointment`
SET `visitStatus` = CASE
    WHEN `userStatus` = 0 OR `doctorStatus` = 0 THEN 'Cancelled'
    ELSE 'Scheduled'
  END,
  `paymentStatus` = IFNULL(`paymentStatus`, 'Pending')
WHERE `visitStatus` IS NULL OR `visitStatus` = '';
