-- ============================================================
-- Migration: Add document upload columns to admissions table
-- Compatible with MySQL 5.7+ and MariaDB 10.4+
-- Run this if upgrading from v2.0 to v2.1+
-- ============================================================

DROP PROCEDURE IF EXISTS _add_col;

DELIMITER $$
CREATE PROCEDURE _add_col(
    IN tbl  VARCHAR(64),
    IN col  VARCHAR(64),
    IN defn TEXT,
    IN aftr VARCHAR(64)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND COLUMN_NAME  = col
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', defn,
            ' AFTER `', aftr, '`'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL _add_col('admissions', 'doc_aadhar',        'VARCHAR(255) DEFAULT NULL', 'document');
CALL _add_col('admissions', 'doc_birth_cert',    'VARCHAR(255) DEFAULT NULL', 'doc_aadhar');
CALL _add_col('admissions', 'doc_transfer_cert', 'VARCHAR(255) DEFAULT NULL', 'doc_birth_cert');
CALL _add_col('admissions', 'doc_photo',         'VARCHAR(255) DEFAULT NULL', 'doc_transfer_cert');

DROP PROCEDURE IF EXISTS _add_col;
