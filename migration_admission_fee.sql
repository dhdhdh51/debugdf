-- Run this if upgrading from v1 to v2
ALTER TABLE `fees` MODIFY COLUMN `student_id` INT UNSIGNED DEFAULT NULL;
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('admission_fee_enabled',  '0'),
  ('admission_fee_amount',   '500'),
  ('admission_fee_type',     'Admission Fee'),
  ('admission_fee_due_days', '30'),
  ('admission_fee_note',     '')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
