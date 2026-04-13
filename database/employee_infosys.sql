-- ===================================================
-- Municipal Employee Information System
-- Database Schema & Sample Data
-- ===================================================

CREATE DATABASE IF NOT EXISTS `employee_infosys`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `employee_infosys`;

-- ---------------------------------------------------
-- Table: users
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `full_name`  VARCHAR(100) NOT NULL,
  `role`       ENUM('admin','hr','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: departments
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150) NOT NULL,
  `code`        VARCHAR(20)  NOT NULL,
  `description` TEXT,
  `head_name`   VARCHAR(100) DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: positions
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `positions` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `title`         VARCHAR(150) NOT NULL,
  `department_id` INT(11)      DEFAULT NULL,
  `salary_grade`  INT(3)       DEFAULT NULL,
  `description`   TEXT,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pos_dept` (`department_id`),
  CONSTRAINT `fk_pos_dept` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: employees
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `employees` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `employee_id`         VARCHAR(20)  NOT NULL,
  `first_name`          VARCHAR(50)  NOT NULL,
  `last_name`           VARCHAR(50)  NOT NULL,
  `middle_name`         VARCHAR(50)  DEFAULT NULL,
  `suffix`              VARCHAR(10)  DEFAULT NULL,
  `gender`              ENUM('Male','Female') NOT NULL,
  `birthdate`           DATE         NOT NULL,
  `civil_status`        ENUM('Single','Married','Widowed','Separated','Divorced') NOT NULL,
  `address`             TEXT         NOT NULL,
  `contact_number`      VARCHAR(20)  DEFAULT NULL,
  `email`               VARCHAR(100) DEFAULT NULL,
  `department_id`       INT(11)      DEFAULT NULL,
  `position_id`         INT(11)      DEFAULT NULL,
  `employment_status`   ENUM('Active','Inactive','On Leave','Resigned','Retired') NOT NULL DEFAULT 'Active',
  `employment_type`     ENUM('Permanent','Job Order/ Contractual','Casual','Coterminous') NOT NULL,
  `date_hired`          DATE         NOT NULL,
  `photo`               VARCHAR(255) DEFAULT NULL,
  `tin_number`          VARCHAR(20)  DEFAULT NULL,
  `sss_number`          VARCHAR(20)  DEFAULT NULL,
  `gsis_number`         VARCHAR(30)  DEFAULT NULL,
  `philhealth_number`   VARCHAR(30)  DEFAULT NULL,
  `pagibig_number`              VARCHAR(20)   DEFAULT NULL,
  `height`                      VARCHAR(20)   DEFAULT NULL,
  `weight`                      VARCHAR(20)   DEFAULT NULL,
  `blood_type`                  ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `emergency_contact_name`      VARCHAR(100)  DEFAULT NULL,
  `emergency_contact_address`   TEXT          DEFAULT NULL,
  `emergency_contact_phone`     VARCHAR(20)   DEFAULT NULL,
  `created_at`                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employee_id` (`employee_id`),
  KEY `fk_emp_dept` (`department_id`),
  KEY `fk_emp_pos`  (`position_id`),
  CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_pos` FOREIGN KEY (`position_id`)
    REFERENCES `positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Default admin user
-- Password: admin123  (bcrypt hash)
-- ---------------------------------------------------
INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$YourHashHere', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE `id`=`id`;
-- NOTE: Run setup.php to create a properly-hashed admin account.

-- ---------------------------------------------------
-- Add gsis_number for existing installations
-- ---------------------------------------------------
ALTER TABLE `employees`
  ADD COLUMN IF NOT EXISTS `gsis_number` VARCHAR(30) DEFAULT NULL
  AFTER `sss_number`;

-- Update employment_type ENUM for existing installations
ALTER TABLE `employees`
  MODIFY COLUMN `employment_type`
  ENUM('Permanent','Job Order/ Contractual','Casual','Coterminous') NOT NULL;

-- Add physical info and emergency contact columns for existing installations
ALTER TABLE `employees`
  ADD COLUMN IF NOT EXISTS `height`                    VARCHAR(20)   DEFAULT NULL AFTER `pagibig_number`,
  ADD COLUMN IF NOT EXISTS `weight`                    VARCHAR(20)   DEFAULT NULL AFTER `height`,
  ADD COLUMN IF NOT EXISTS `blood_type`                ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL AFTER `weight`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_name`    VARCHAR(100)  DEFAULT NULL AFTER `blood_type`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_address` TEXT          DEFAULT NULL AFTER `emergency_contact_name`,
  ADD COLUMN IF NOT EXISTS `emergency_contact_phone`   VARCHAR(20)   DEFAULT NULL AFTER `emergency_contact_address`;

-- ---------------------------------------------------
-- Sample departments
-- ---------------------------------------------------
INSERT IGNORE INTO `departments` (`name`, `code`, `description`, `head_name`) VALUES
('Office of the Mayor',                      'OM',   'Office of the Local Chief Executive',                       'Mayor Juan dela Cruz'),
('Human Resources Management Office',        'HRMO', 'Manages recruitment, hiring, and employee welfare',         'Maria Santos'),
('Treasury Office',                          'TO',   'Handles municipal finances and tax collection',             'Pedro Reyes'),
('Engineering Office',                       'EO',   'Manages infrastructure and public works projects',          'Engr. Jose Lim'),
('Health Office',                            'HO',   'Provides public health services to constituents',           'Dr. Ana Garcia'),
('Social Welfare and Development Office',    'SWDO', 'Manages social services and welfare programs',              'Carmen Torres'),
('Agriculture Office',                       'AO',   'Supports agricultural development and programs',            'Ricardo Bautista'),
('Civil Registry Office',                    'CRO',  'Maintains civil registry documents and records',            'Liza Mendoza'),
('Business Permit and Licensing Office',     'BPLO', 'Issues business permits and licenses',                      'Roberto Cruz'),
('Information Technology Office',            'ITO',  'Manages IT infrastructure and information systems',         'Dennis Sy');

-- ---------------------------------------------------
-- Sample positions
-- ---------------------------------------------------
INSERT IGNORE INTO `positions` (`title`, `department_id`, `salary_grade`, `description`) VALUES
('Mayor',                        1,  28, 'Local Chief Executive'),
('Municipal Administrator',      1,  26, 'Administrative head of the municipality'),
('HRMO Chief',                   2,  24, 'Head of Human Resources Management Office'),
('HR Officer I',                 2,  15, 'Human Resources Officer'),
('Municipal Treasurer',          3,  24, 'Head of Treasury Office'),
('Revenue Collection Officer I', 3,  15, 'Handles revenue collection'),
('Municipal Engineer',           4,  24, 'Head of Engineering Office'),
('Civil Engineer I',             4,  15, 'Civil Engineering staff'),
('Municipal Health Officer',     5,  24, 'Head of Health Office'),
('Nurse I',                      5,  15, 'Municipal nurse'),
('MSWD Officer',                 6,  24, 'Head of Social Welfare and Development'),
('Social Welfare Officer I',     6,  15, 'Social welfare officer'),
('Municipal Agriculturist',      7,  24, 'Head of Agriculture Office'),
('Agricultural Technologist',    7,  11, 'Agricultural technologist'),
('Civil Registrar',              8,  22, 'Head of Civil Registry Office'),
('Registration Officer I',       8,  11, 'Civil registry officer'),
('BPLO Chief',                   9,  22, 'Head of Business Permit and Licensing Office'),
('Licensing Officer I',          9,  11, 'Licensing officer'),
('IT Officer',                   10, 22, 'Head of IT Office'),
('Computer Programmer I',        10, 15, 'Computer programmer');

-- ---------------------------------------------------
-- Sample employees
-- ---------------------------------------------------
INSERT IGNORE INTO `employees`
  (`employee_id`,`first_name`,`last_name`,`middle_name`,`gender`,`birthdate`,
   `civil_status`,`address`,`contact_number`,`email`,
   `department_id`,`position_id`,`employment_status`,`employment_type`,`date_hired`,
   `tin_number`,`sss_number`,`philhealth_number`,`pagibig_number`)
VALUES
('EMP-2010-001','Juan','dela Cruz','M.','Male','1970-05-15','Married',
  'Brgy. 1, Libona','09171234567','mayor@libona.gov.ph',
  1,1,'Active','Permanent','2010-01-01','123-456-789-000','12-3456789-1','12-345678901-2','1234-5678-9012'),
('EMP-2012-002','Maria','Santos','L.','Female','1978-08-22','Married',
  'Brgy. 3, Libona','09281234567','hrmo@libona.gov.ph',
  2,3,'Active','Permanent','2012-06-01','234-567-890-000','23-4567890-2','23-456789012-3','2345-6789-0123'),
('EMP-2015-003','Pedro','Reyes','A.','Male','1980-03-10','Single',
  'Brgy. 5, Libona','09391234567','treasury@libona.gov.ph',
  3,5,'Active','Permanent','2015-03-01','345-678-901-000','34-5678901-3','34-567890123-4','3456-7890-1234'),
('EMP-2016-004','Jose','Lim','B.','Male','1975-11-28','Married',
  'Brgy. 2, Libona','09451234567','engineering@libona.gov.ph',
  4,7,'Active','Permanent','2016-07-16','456-789-012-000','45-6789012-4','45-678901234-5','4567-8901-2345'),
('EMP-2017-005','Ana','Garcia','C.','Female','1982-07-04','Married',
  'Brgy. 8, Libona','09561234567','health@libona.gov.ph',
  5,9,'Active','Permanent','2017-01-03','567-890-123-000','56-7890123-5','56-789012345-6','5678-9012-3456'),
('EMP-2018-006','Carmen','Torres','D.','Female','1985-12-15','Single',
  'Brgy. 4, Libona','09671234567','swdo@libona.gov.ph',
  6,11,'Active','Permanent','2018-06-01','678-901-234-000','67-8901234-6','67-890123456-7','6789-0123-4567'),
('EMP-2019-007','Ricardo','Bautista','E.','Male','1979-09-30','Married',
  'Brgy. 6, Libona','09781234567','agri@libona.gov.ph',
  7,13,'Active','Permanent','2019-01-07','789-012-345-000','78-9012345-7','78-901234567-8','7890-1234-5678'),
('EMP-2020-008','Liza','Mendoza','F.','Female','1990-02-18','Single',
  'Brgy. 7, Libona','09891234567','cro@libona.gov.ph',
  8,15,'Active','Permanent','2020-03-01','890-123-456-000','89-0123456-8','89-012345678-9','8901-2345-6789'),
('EMP-2021-009','Roberto','Cruz','G.','Male','1988-06-25','Married',
  'Brgy. 9, Libona','09121234567','bplo@libona.gov.ph',
  9,17,'Active','Permanent','2021-01-04','901-234-567-000','90-1234567-9','90-123456789-0','9012-3456-7890'),
('EMP-2022-010','Dennis','Sy','H.','Male','1992-04-12','Single',
  'Brgy. 10, Libona','09231234567','ito@libona.gov.ph',
  10,19,'Active','Permanent','2022-06-01','012-345-678-000','01-2345678-0','01-234567890-1','0123-4567-8901');

-- ---------------------------------------------------
-- Table: employee_portal_accounts
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_portal_accounts` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11)      NOT NULL,
  `username`    VARCHAR(100) NOT NULL,
  `password`    VARCHAR(255) NOT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_username` (`username`),
  UNIQUE KEY `uq_portal_emp`      (`employee_id`),
  CONSTRAINT `fk_portal_emp` FOREIGN KEY (`employee_id`)
    REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: leave_types
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `max_days`   INT(3)       DEFAULT NULL,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: leave_requests
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `employee_id`   INT(11)       NOT NULL,
  `leave_type_id` INT(11)       NOT NULL,
  `date_from`     DATE          NOT NULL,
  `date_to`       DATE          NOT NULL,
  `total_days`    DECIMAL(4,1)  NOT NULL,
  `reason`        TEXT,
  `status`        ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_remarks` TEXT,
  `reviewed_by`   INT(11)       DEFAULT NULL,
  `reviewed_at`   DATETIME      DEFAULT NULL,
  `filed_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lr_emp` (`employee_id`),
  CONSTRAINT `fk_lr_emp`  FOREIGN KEY (`employee_id`)   REFERENCES `employees`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lr_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  CONSTRAINT `fk_lr_user` FOREIGN KEY (`reviewed_by`)   REFERENCES `users`       (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Table: cto_requests
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `cto_requests` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `employee_id`     INT(11)      NOT NULL,
  `cto_date`        DATE         NOT NULL,
  `earned_date`     DATE         DEFAULT NULL,
  `hours_requested` DECIMAL(4,1) NOT NULL,
  `reason`          TEXT,
  `status`          ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_remarks`   TEXT,
  `reviewed_by`     INT(11)      DEFAULT NULL,
  `reviewed_at`     DATETIME     DEFAULT NULL,
  `filed_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cto_emp` (`employee_id`),
  CONSTRAINT `fk_cto_emp`  FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cto_user` FOREIGN KEY (`reviewed_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------
-- Default leave types (Philippine CSC standard)
-- ---------------------------------------------------
INSERT IGNORE INTO `leave_types` (`id`, `name`, `max_days`) VALUES
(1, 'Vacation Leave',           15),
(2, 'Sick Leave',               15),
(3, 'Forced Leave',              5),
(4, 'Special Privilege Leave',   3),
(5, 'Maternity Leave',         105),
(6, 'Paternity Leave',           7),
(7, 'Emergency / Calamity Leave',5),
(8, 'Study Leave',              NULL);
