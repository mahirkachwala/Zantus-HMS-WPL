-- Drop only the unused legacy tables from an existing HMS database.
-- Verified against the current PHP codebase on 2026-04-22.
-- The export file `b10_41663109_HMS (1).sql` contained no INSERT statements
-- for these tables, so they were empty in that dump.
-- This version avoids `information_schema` queries because some shared hosts block them.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `appointment`;
DROP TABLE IF EXISTS `tblpatient`;
DROP TABLE IF EXISTS `tblmedicalhistory`;
DROP TABLE IF EXISTS `tblcontactus`;

SET FOREIGN_KEY_CHECKS = 1;
