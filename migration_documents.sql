-- Add multiple document upload columns to admissions table
-- Run this if upgrading from v2.0 (single document column) to v2.1+

ALTER TABLE `admissions`
  ADD COLUMN IF NOT EXISTS `doc_aadhar`       VARCHAR(255) DEFAULT NULL AFTER `document`,
  ADD COLUMN IF NOT EXISTS `doc_birth_cert`   VARCHAR(255) DEFAULT NULL AFTER `doc_aadhar`,
  ADD COLUMN IF NOT EXISTS `doc_transfer_cert`VARCHAR(255) DEFAULT NULL AFTER `doc_birth_cert`,
  ADD COLUMN IF NOT EXISTS `doc_photo`        VARCHAR(255) DEFAULT NULL AFTER `doc_transfer_cert`;
