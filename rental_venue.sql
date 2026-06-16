
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET GLOBAL event_scheduler = ON;
START TRANSACTION;
SET time_zone = "+00:00";
;
;
;
;
CREATE TABLE `academic_schedule` (
  `sch_id` int(10) UNSIGNED NOT NULL,
  `sem_id` int(4) UNSIGNED NOT NULL,
  `vid` varchar(10) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject_name` varchar(100) DEFAULT 'Academic Class'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `academic_schedule` (`sch_id`, `sem_id`, `vid`, `day_of_week`, `start_time`, `end_time`, `subject_name`) VALUES
(49, 2610, 'MSMX0001', 'Monday', '08:00:00', '10:00:00', 'Data Structures'),
(50, 2610, 'MSMX0001', 'Monday', '10:00:00', '12:00:00', 'Algorithms'),
(51, 2610, 'MSMR2016', 'Tuesday', '14:00:00', '16:00:00', 'Database Systems'),
(52, 2610, 'MSMR2012', 'Wednesday', '09:00:00', '11:00:00', 'Software Engineering'),
(53, 2610, 'MSMX0002', 'Thursday', '10:00:00', '13:00:00', 'Operating Systems'),
(54, 2610, 'MSMX0003', 'Friday', '08:00:00', '10:00:00', 'Computer Networks'),
(55, 2610, 'MSMR3012', 'Monday', '14:00:00', '16:00:00', 'AI Fundamentals'),
(56, 2610, 'MSMR3013', 'Tuesday', '10:00:00', '12:00:00', 'Machine Learning'),
(57, 2610, 'MSMR3014', 'Wednesday', '14:00:00', '17:00:00', 'Deep Learning'),
(58, 2610, 'MSMR3015', 'Thursday', '08:00:00', '10:00:00', 'Cloud Computing'),
(59, 2610, 'MSMX2001', 'Friday', '14:00:00', '16:00:00', 'Cyber Security'),
(60, 2610, 'MSMX2002', 'Monday', '16:00:00', '18:00:00', 'Web Development'),
(61, 2610, 'MSMX0003', 'Saturday', '09:00:00', '12:00:00', 'Weekend Workshop'),
(62, 2610, 'MSMR2016', 'Monday', '08:00:00', '10:00:00', 'Calculus I'),
(63, 2610, 'MSMX2003', 'Wednesday', '12:00:00', '14:00:00', 'Ethics in IT'),
(64, 2610, 'MSMR2015', 'Wednesday', '08:00:00', '10:00:00', 'test');
CREATE TABLE `admin` (
  `aid` int(10) UNSIGNED NOT NULL,
  `admin_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `profile_pic` varchar(255) NOT NULL DEFAULT '',
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `admin` (`aid`, `admin_name`, `email`, `password`, `phone_num`, `profile_pic`, `role`, `status`, `created_at`) VALUES
(8000, 'SuperAdmin', 'SA@mmu.edu.my', '$2y$10$0av5Zh5QYMrLJyALrb8O5u60U292chJEz7SdcwfkthLTmx0j1RCw2', '06123456789', '', 'super_admin', 'active', '2026-04-28 09:43:39'),
(8002, 'Siti', 'Siti@mmu.edu.my', '$2y$10$Q3kh7pg/gGCiw.JnpGremOrH26gnUTq.y3LZohGD9qpaPt/k4YZyu', '0122233456', '', 'admin', 'active', '2026-04-28 15:55:39'),
(8013, 'Kam JIa Sheng', 'kam.jia.sheng@student.mmu.edu.my', '$2y$10$lQfIQyNEpl4ToZitw0e3Sul17km5kwqreopbPs1I8D7HZ5nBna2b6', '01298293812', '', 'admin', 'inactive', '2026-06-10 07:23:20');
CREATE TABLE `booking` (
  `bid` int(10) UNSIGNED NOT NULL,
  `uid` varchar(15) NOT NULL,
  `vid` varchar(10) NOT NULL,
  `date_booked` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(30) DEFAULT NULL,
  `payment_due_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `transaction_ref` varchar(50) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `aid` int(10) UNSIGNED DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `booking` (`bid`, `uid`, `vid`, `date_booked`, `time_start`, `time_end`, `status`, `payment_status`, `payment_method`, `payment_due_at`, `cancelled_at`, `cancel_reason`, `transaction_ref`, `paid_at`, `purpose`, `aid`, `approve_date`, `created_at`) VALUES
(20000039, '242DT2430C', 'MSMR3014', '2026-06-08', '09:00:00', '09:30:00', 'cancelled', 'refunded', NULL, '2026-06-08 09:21:44', '2026-06-08 09:30:44', 'SYS_TIMEOUT_ADMIN', 'TXN-97EC9959', NULL, 'test', NULL, NULL, '2026-06-08 01:06:44'),
(20000040, '242DT2429C', 'MSMX2002', '2026-06-30', '10:30:00', '11:30:00', 'completed', 'paid', NULL, '2026-06-10 10:28:55', NULL, NULL, 'TXN-0C145680', NULL, 'Group discussion', 8000, '2026-06-10 14:07:28', '2026-06-10 02:13:55'),
(20000045, '242DT2429C', 'MSMX0002', '2026-06-10', '13:00:00', '14:00:00', 'completed', 'paid', NULL, '2026-06-10 12:58:26', NULL, NULL, 'TXN-57FC36A8', NULL, 'teae', 8002, '2026-06-10 12:47:21', '2026-06-10 04:43:26'),
(20000046, '242DT2429C', 'MSMR2016', '2026-06-11', '10:30:00', '11:30:00', 'rejected', 'refunded', 'card', '2026-06-10 13:47:04', NULL, NULL, 'TXN-CARD-EABF7154', '2026-06-10 13:32:45', 'Group discussion', 8000, '2026-06-10 14:07:34', '2026-06-10 05:32:04'),
(20000047, '242DT2429C', 'MSMR3016', '2026-06-27', '13:00:00', '14:00:00', 'cancelled', 'unpaid', NULL, '2026-06-10 13:48:06', '2026-06-10 13:48:59', 'Payment deadline expired', NULL, NULL, 'Group discussion', NULL, NULL, '2026-06-10 05:33:06'),
(20000048, '242DT2429C', 'MSMR2012', '2026-07-12', '13:00:00', '14:00:00', 'rejected', 'refunded', 'tng', '2026-06-10 14:00:13', NULL, NULL, 'TXN-TNG-4D2B377F', '2026-06-10 13:49:00', 'Group discussion', 8013, '2026-06-10 15:27:45', '2026-06-10 05:45:13'),
(20000049, '242DT2429C', 'MSMR3014', '2026-06-29', '12:30:00', '13:30:00', 'approved', 'paid', 'tng', '2026-06-10 14:06:04', NULL, NULL, 'TXN-TNG-E0F1050C', '2026-06-10 13:51:44', 'Group discussion', 8000, '2026-06-13 11:51:52', '2026-06-10 05:51:04'),
(20000050, '242DT2429C', 'MSMR2016', '2026-06-10', '15:00:00', '16:00:00', 'completed', 'paid', 'tng', '2026-06-10 15:29:57', NULL, NULL, 'TXN-TNG-CB37DDB1', '2026-06-10 15:17:04', 'test', 8013, '2026-06-10 15:27:39', '2026-06-10 07:14:57'),
(20000051, '242DT24123', 'MSMX0001', '2026-06-13', '11:30:00', '12:00:00', 'completed', 'paid', 'tng', '2026-06-13 12:09:16', NULL, NULL, 'TXN-TNG-B844525D', '2026-06-13 11:54:19', 'test', 8000, '2026-06-13 11:54:30', '2026-06-13 03:54:16'),
(20000052, '242DT24123', 'MSMX0002', '2026-06-13', '12:00:00', '12:30:00', 'completed', 'paid', 'tng', '2026-06-13 12:24:11', NULL, NULL, 'TXN-TNG-D4E97DB2', '2026-06-13 12:09:13', 'test', 8000, '2026-06-13 12:11:30', '2026-06-13 04:09:11'),
(20000053, '242DT24123', 'MSMX0002', '2026-06-13', '13:00:00', '13:30:00', 'completed', 'paid', 'tng', '2026-06-13 12:25:44', NULL, NULL, 'TXN-TNG-A0825331', '2026-06-13 12:10:46', 'test', 8000, '2026-06-13 12:11:33', '2026-06-13 04:10:44'),
(20000054, '242DT24123', 'MSMX0003', '2026-06-13', '12:00:00', '12:30:00', 'completed', 'paid', 'tng', '2026-06-13 12:26:05', NULL, NULL, 'TXN-TNG-577BE044', '2026-06-13 12:11:06', 'test', 8000, '2026-06-13 12:12:36', '2026-06-13 04:11:05'),
(20000055, '242DT24123', 'MSMR2012', '2026-06-13', '12:00:00', '12:30:00', 'cancelled', 'paid', 'tng', '2026-06-13 12:26:46', NULL, 'SLA Violation: Automated threshold expiration purge.', 'TXN-TNG-6E7986DE', '2026-06-13 12:11:48', 'test', NULL, NULL, '2026-06-13 04:11:46'),
(20000056, '242DT24123', 'MSMX2002', '2026-06-13', '12:00:00', '12:30:00', 'cancelled', 'paid', 'tng', '2026-06-13 12:27:27', NULL, 'SLA Violation: Automated threshold expiration purge.', 'TXN-TNG-3CCE0866', '2026-06-13 12:12:28', 'test', NULL, NULL, '2026-06-13 04:12:27'),
(20000057, '242DT24123', 'MSMX0001', '2026-06-13', '12:30:00', '13:00:00', 'completed', 'paid', 'tng', '2026-06-13 12:48:10', NULL, NULL, 'TXN-TNG-8B3A4D31', '2026-06-13 12:33:12', 'test', 8000, '2026-06-13 12:42:14', '2026-06-13 04:33:10'),
(20000058, '242DT24123', 'MSMX0002', '2026-06-13', '14:00:00', '14:30:00', 'completed', 'paid', 'tng', '2026-06-13 12:56:06', NULL, NULL, 'TXN-TNG-4EA49C75', '2026-06-13 12:41:08', 'test', 8000, '2026-06-13 12:42:03', '2026-06-13 04:41:06'),
(20000059, '242DT24123', 'MSMX0003', '2026-06-13', '12:30:00', '13:00:00', 'completed', 'paid', 'tng', '2026-06-13 12:56:31', NULL, NULL, 'TXN-TNG-CF19008B', '2026-06-13 12:41:33', 'test', 8000, '2026-06-13 12:46:53', '2026-06-13 04:41:31');
CREATE TABLE `damage_report` (
  `report_id` int(11) NOT NULL,
  `bid` int(10) UNSIGNED NOT NULL,
  `uid` varchar(15) NOT NULL,
  `vid` varchar(10) NOT NULL,
  `damage_description` text NOT NULL,
  `damage_photo` varchar(255) DEFAULT NULL,
  `report_status` enum('submitted','reviewed') DEFAULT 'submitted',
  `admin_remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `damage_report` (`report_id`, `bid`, `uid`, `vid`, `damage_description`, `damage_photo`, `report_status`, `admin_remark`, `created_at`) VALUES
(1, 20000045, '242DT2429C', 'MSMX0002', 'The mic was not function.', 'damage_20000045_1781069384.png', 'reviewed', '', '2026-06-10 05:29:44'),
(2, 20000040, '242DT2429C', 'MSMX2002', 'test', NULL, 'reviewed', '', '2026-06-10 06:56:39');
CREATE TABLE `inspection` (
  `ins_id` int(10) UNSIGNED NOT NULL,
  `bid` int(10) UNSIGNED NOT NULL,
  `sid` int(10) UNSIGNED NOT NULL,
  `ins_status` enum('passed','failed','pending','overdue') NOT NULL DEFAULT 'pending',
  `damage_desc` text DEFAULT NULL,
  `damage_cost` decimal(10,2) UNSIGNED DEFAULT 0.00,
  `penalty` decimal(10,2) UNSIGNED DEFAULT 0.00,
  `inspected_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `inspection` (`ins_id`, `bid`, `sid`, `ins_status`, `damage_desc`, `damage_cost`, `penalty`, `inspected_at`) VALUES
(30000014, 20000045, 9000, 'failed', 'test', 0.00, 100.00, '2026-06-10 14:09:09'),
(30000015, 20000050, 9000, 'passed', 'SYS_TIMEOUT_24H_RELEASE', 0.00, 0.00, '2026-06-13 03:59:05'),
(30000016, 20000051, 9002, 'passed', 'No damage. Venue is in standard condition.', 0.00, 0.00, '2026-06-13 12:33:26'),
(30000017, 20000054, 9002, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL),
(30000018, 20000052, 9000, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL),
(30000019, 20000057, 9000, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL),
(30000020, 20000059, 9002, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL),
(30000021, 20000053, 9000, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL),
(30000022, 20000058, 9000, 'overdue', 'SLA Violation: Inspector Timeout. Auto-released.', 0.00, 0.00, NULL);
CREATE TABLE `inspic` (
  `pic_id` int(11) NOT NULL,
  `pic_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ins_id` int(10) UNSIGNED NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `password_resets` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `password_resets` (`id`, `email`, `token_hash`, `expires_at`, `created_at`) VALUES
(14, 'kam.jia.sheng@student.mmu.edu.my', '71fb9b04a25d37178b5c14af256b4243264f8990b7827e5adfb5d3304352678e', '2026-06-10 16:24:36', '2026-06-10 07:24:36'),
(16, 'SA@mmu.edu.my', '650768a287cb19666e3f8849c3199b203cb456828c1217e0028af45a04e7841b', '2026-06-10 16:55:00', '2026-06-10 07:55:00');
CREATE TABLE `report` (
  `rid` int(10) UNSIGNED NOT NULL,
  `ins_id` int(10) UNSIGNED NOT NULL,
  `final_deduct` decimal(10,2) DEFAULT 0.00,
  `refund_status` enum('none','pending','processed') DEFAULT 'none',
  `penalty_status` enum('none','pending','paid') DEFAULT 'none',
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `report` (`rid`, `ins_id`, `final_deduct`, `refund_status`, `penalty_status`, `created_at`) VALUES
(40000008, 30000014, 100.00, 'none', 'pending', '2026-06-10'),
(40000009, 30000015, 0.00, 'pending', 'none', '2026-06-13'),
(40000010, 30000016, 0.00, 'pending', 'none', '2026-06-13'),
(40000011, 30000017, 0.00, 'pending', 'none', '2026-06-14'),
(40000012, 30000018, 0.00, 'pending', 'none', '2026-06-14'),
(40000013, 30000019, 0.00, 'pending', 'none', '2026-06-14'),
(40000014, 30000020, 0.00, 'pending', 'none', '2026-06-14'),
(40000015, 30000021, 0.00, 'pending', 'none', '2026-06-14'),
(40000016, 30000022, 0.00, 'pending', 'none', '2026-06-14');
CREATE TABLE `semester_config` (
  `sem_id` int(4) UNSIGNED NOT NULL,
  `sem_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_booking_open` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `semester_config` (`sem_id`, `sem_name`, `start_date`, `end_date`, `is_active`, `is_booking_open`) VALUES
(2610, 'Trimester March/April 2026', '2026-03-30', '2026-07-09', 1, 0);
CREATE TABLE `staff` (
  `sid` int(10) UNSIGNED NOT NULL,
  `staff_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `profile_pic` varchar(255) NOT NULL DEFAULT '',
  `position` enum('inspector') NOT NULL DEFAULT 'inspector',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `staff` (`sid`, `staff_name`, `email`, `password`, `phone_num`, `profile_pic`, `position`, `status`, `created_at`) VALUES
(9000, 'Vikram', 'vikram@gmail.com', '$2y$10$hU8obf2c0SE317q2FH1Qs.sWcrUC3MneI6SKOOYTq2ux7AiouOzsO', '0122233456', '', 'inspector', 'active', '2026-04-28 15:25:58'),
(9002, 'Lim', 'LIM.LI.GUAN@student.mmu.edu.my', '$2y$10$Lnwb9IVO8evOBd4ro8yyx.Jf3EMjSHs1TRqYqZxV17AU3h8KMnyvq', '0112334456', '', 'inspector', 'active', '2026-06-10 07:48:44');
CREATE TABLE `user` (
  `uid` varchar(15) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `profile_pic` varchar(255) NOT NULL DEFAULT '',
  `outstanding_debt` decimal(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `account_status` enum('active','restricted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `user` (`uid`, `username`, `email`, `password`, `phone_num`, `profile_pic`, `outstanding_debt`, `account_status`, `created_at`) VALUES
('242DT24123', 'LIM', 'LIM.LI.GUAN@student.mmu.edu.my', '$2y$10$f7na0PcAMGz5ce/kSlabA.Qm8Q4Rn3bGytyhWSjDRQwaS8GbMwm3y', '0122341241', '', 0.00, 'active', '2026-06-13 03:53:11'),
('242DT2421C', 'test', 'test@gmail.com', '$2y$10$TjGMkbwlVVQO.fRym2HgAOWAv3yrUL3/a0GyZjYu5LwPZZjRg6wmm', '01241241124', '', 0.00, 'active', '2026-05-06 06:21:11'),
('242DT2429C', 'KamJS', 'kam@gmail.com', '$2y$10$b9IdO4GLAwsebemQD0x1Q.MKQL1UlyAn6ZhVyKtRkbYRghAbt4VMC', '01156811078', '', 80.00, 'active', '2026-05-05 02:46:08'),
('242DT242CL', 'KamJS', 'kam.jia.sheng@student.mmu.edu.my', '$2y$10$5SH.S0PrucSvkhJg6Mcdwe5OX/WTcibTOZ62ftvc8j1grDGyTbzFq', '01234567533', 'uploads/1781073919_0.jpg', 0.00, 'active', '2026-06-10 06:41:41'),
('242DT2430C', 'LIM', 'Lim@gmail.com', '$2y$10$7sFovpK/duwjvV1jbwrfROmzxvURhuIXPJfEj5t3ePzg3j4vmqnOO', '0122233456', '', 0.00, 'active', '2026-04-28 14:04:09'),
('242DT2431X', 'TestLim', 'TestLim@gmail.com', '$2y$10$IHgP9mcLA6Rc0Akf5rVUC.ZHUO2rYaNemocPx75QsXLtntTZXJDSG', '01223456789', '', 0.00, 'active', '2026-05-19 10:37:54'),
('242DT245Y6', 'Frank', 'kai@student.mmu.edu.my', '$2y$10$aM1vOkGZ/5mKORZGGsxWCOfWYZkoOhDDbQV.gyBZa5m/lH9u1Lt5C', '0112334456', '', 0.00, 'active', '2026-05-06 06:17:40'),
('242DT267S4', 'Adam', 'yai@student.mmu.edu.my', '$2y$10$7Ph4jv5qNttlbv3xFlEYDOjQ8RI/7u/F5YTmzF7WSdiMYeL353lW2', '0111232455', '', 0.00, 'active', '2026-05-06 06:19:43');
CREATE TABLE `vcategory` (
  `vcid` int(11) NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `vcategory` (`vcid`, `category`, `description`) VALUES
(1, 'Discussion Room', 'A small room with a max capacity of 30 people for discussion.'),
(2, 'Lecture Hall', 'A lecture hall with a max capacity of 100 people.'),
(3, 'Large Lecture Hall', 'The larger lecture hall that can accommodate up to 400 people at the same time.'),
(4, 'Sport Court', 'Court for sports activities.');
CREATE TABLE `venue` (
  `vid` varchar(10) NOT NULL,
  `vname` varchar(100) NOT NULL,
  `vcid` int(11) NOT NULL,
  `max_cap` int(10) UNSIGNED NOT NULL,
  `deposit` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('available','maintenance','closed') NOT NULL DEFAULT 'available',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `venue` (`vid`, `vname`, `vcid`, `max_cap`, `deposit`, `status`, `description`) VALUES
('MBMR4015', 'Discussion Room C1', 1, 30, 10.00, 'available', ''),
('MSMR2012', 'Tutorial Room B5', 1, 25, 5.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2013', 'Tutorial Room B4', 1, 40, 5.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2014', 'Tutorial Room B3', 1, 35, 5.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2015', 'Tutorial Room B2', 1, 30, 5.00, 'maintenance', 'A small room which is good for small group discussion.'),
('MSMR2016', 'Tutorial Room B1', 1, 30, 5.00, 'available', 'A room with 30 person of capacity.'),
('MSMR3012', 'Tutorial Room A8', 1, 25, 5.00, 'available', 'A room with 25 person of capacity.'),
('MSMR3013', 'Tutorial Room A9', 1, 35, 5.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3014', 'Tutorial Room A10', 1, 35, 5.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3015', 'Tutorial Room A11', 1, 40, 5.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3016', 'Tutorial Room A12', 1, 20, 5.00, 'available', 'Small room with full equipment for discussion.'),
('MSMX0001', 'Lecture Hall A1', 3, 380, 20.00, 'available', 'A large hall for large events and exams.'),
('MSMX0002', 'Lecture Hall A2', 3, 380, 20.00, 'available', 'A large hall for large events and exams.'),
('MSMX0003', 'Lecture Hall A3', 3, 380, 20.00, 'available', 'A large hall for large events and exams.'),
('MSMX0004', 'Lecture Hall A4', 3, 380, 20.00, 'maintenance', 'A large hall for large events and exams.'),
('MSMX2001', 'Lecture Hall B1', 2, 100, 10.00, 'maintenance', 'Lecture hall that can accommodate 100 people.'),
('MSMX2002', 'Lecture Hall B2', 2, 90, 10.00, 'available', 'Lecture hall that can accommodate 90 people.'),
('MSMX2003', 'Lecture Hall B3', 2, 100, 10.00, 'available', 'A lecture hall with 100 person of capacity.'),
('TEST1000', 'test', 1, 20, 5.00, 'available', '');
CREATE TABLE `vpic` (
  `pic_id` int(11) NOT NULL,
  `pic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vid` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `vpic` (`pic_id`, `pic`, `vid`, `description`) VALUES
(1, 'MSMX0001_1780464958_4360.jpg', 'MSMX0001', 'Gallery asset for node MSMX0001'),
(2, 'MSMX0001_1780464958_8527.jpg', 'MSMX0001', 'Gallery asset for node MSMX0001'),
(3, 'MSMX0001_1780464958_8488.jpg', 'MSMX0001', 'Gallery asset for node MSMX0001'),
(4, 'MSMX0001_1780464958_3918.jpg', 'MSMX0001', 'Gallery asset for node MSMX0001'),
(5, 'MSMX0002_1780465025_3559.jpg', 'MSMX0002', 'Gallery asset for node MSMX0002'),
(6, 'MSMX0002_1780465025_3022.jpg', 'MSMX0002', 'Gallery asset for node MSMX0002'),
(7, 'MSMX0002_1780465025_2012.jpg', 'MSMX0002', 'Gallery asset for node MSMX0002'),
(8, 'MSMX0002_1780465025_2706.jpg', 'MSMX0002', 'Gallery asset for node MSMX0002'),
(9, 'MSMX0003_1780465216_7946.jpg', 'MSMX0003', 'Gallery asset for node MSMX0003'),
(10, 'MSMX0003_1780465216_5052.jpg', 'MSMX0003', 'Gallery asset for node MSMX0003'),
(11, 'MSMX0003_1780465216_3114.jpg', 'MSMX0003', 'Gallery asset for node MSMX0003'),
(12, 'MSMX0003_1780465216_3854.jpg', 'MSMX0003', 'Gallery asset for node MSMX0003'),
(13, 'MSMX0004_1780465248_1719.jpg', 'MSMX0004', 'Gallery asset for node MSMX0004'),
(14, 'MSMX0004_1780465248_2421.jpg', 'MSMX0004', 'Gallery asset for node MSMX0004'),
(15, 'MSMX0004_1780465248_2714.jpg', 'MSMX0004', 'Gallery asset for node MSMX0004'),
(16, 'MSMX0004_1780465248_5113.jpg', 'MSMX0004', 'Gallery asset for node MSMX0004'),
(17, 'MSMX2001_1780465275_3756.jpg', 'MSMX2001', 'Gallery asset for node MSMX2001'),
(18, 'MSMX2001_1780465275_3446.jpg', 'MSMX2001', 'Gallery asset for node MSMX2001'),
(19, 'MSMX2001_1780465275_7540.jpg', 'MSMX2001', 'Gallery asset for node MSMX2001'),
(20, 'MSMX2002_1780465291_9599.jpg', 'MSMX2002', 'Gallery asset for node MSMX2002'),
(21, 'MSMX2002_1780465291_5831.jpg', 'MSMX2002', 'Gallery asset for node MSMX2002'),
(22, 'MSMX2002_1780465291_6747.jpg', 'MSMX2002', 'Gallery asset for node MSMX2002'),
(23, 'MSMX2003_1780465328_7857.jpg', 'MSMX2003', 'Gallery asset for node MSMX2003'),
(24, 'MSMX2003_1780465328_8241.jpg', 'MSMX2003', 'Gallery asset for node MSMX2003'),
(25, 'MSMX2003_1780465328_2701.jpg', 'MSMX2003', 'Gallery asset for node MSMX2003'),
(26, 'MSMR3014_1780465483_7692.jpg', 'MSMR3014', 'Gallery asset for node MSMR3014'),
(27, 'MSMR3014_1780465483_4708.jpg', 'MSMR3014', 'Gallery asset for node MSMR3014'),
(28, 'MSMR3014_1780465483_2706.jpg', 'MSMR3014', 'Gallery asset for node MSMR3014'),
(29, 'MSMR3015_1780465564_3831.jpg', 'MSMR3015', 'Gallery asset for node MSMR3015'),
(30, 'MSMR3015_1780465564_4869.jpg', 'MSMR3015', 'Gallery asset for node MSMR3015'),
(31, 'MSMR3015_1780465564_3632.jpg', 'MSMR3015', 'Gallery asset for node MSMR3015'),
(32, 'MSMR3016_1780465585_2349.jpg', 'MSMR3016', 'Gallery asset for node MSMR3016'),
(33, 'MSMR3016_1780465585_3184.jpg', 'MSMR3016', 'Gallery asset for node MSMR3016'),
(34, 'MSMR3016_1780465585_1777.jpg', 'MSMR3016', 'Gallery asset for node MSMR3016'),
(35, 'MSMR3012_1780465642_6542.jpg', 'MSMR3012', 'Gallery asset for node MSMR3012'),
(36, 'MSMR3012_1780465642_2909.jpg', 'MSMR3012', 'Gallery asset for node MSMR3012'),
(37, 'MSMR3012_1780465642_6101.jpg', 'MSMR3012', 'Gallery asset for node MSMR3012'),
(38, 'MSMR3013_1780465681_5160.jpg', 'MSMR3013', 'Gallery asset for node MSMR3013'),
(39, 'MSMR3013_1780465681_2764.jpg', 'MSMR3013', 'Gallery asset for node MSMR3013'),
(40, 'MSMR3013_1780465681_2557.jpg', 'MSMR3013', 'Gallery asset for node MSMR3013'),
(41, 'MSMR2016_1780465725_7090.jpg', 'MSMR2016', 'Gallery asset for node MSMR2016'),
(42, 'MSMR2016_1780465725_5607.jpg', 'MSMR2016', 'Gallery asset for node MSMR2016'),
(43, 'MSMR2016_1780465725_4717.jpg', 'MSMR2016', 'Gallery asset for node MSMR2016'),
(44, 'MSMR2015_1780465749_7454.jpg', 'MSMR2015', 'Gallery asset for node MSMR2015'),
(45, 'MSMR2015_1780465749_9489.jpg', 'MSMR2015', 'Gallery asset for node MSMR2015'),
(46, 'MSMR2015_1780465749_2679.jpg', 'MSMR2015', 'Gallery asset for node MSMR2015'),
(47, 'MSMR2014_1780465777_6769.jpg', 'MSMR2014', 'Gallery asset for node MSMR2014'),
(48, 'MSMR2014_1780465777_4607.jpg', 'MSMR2014', 'Gallery asset for node MSMR2014'),
(49, 'MSMR2014_1780465777_7713.jpg', 'MSMR2014', 'Gallery asset for node MSMR2014'),
(50, 'MSMR2013_1780465802_7723.jpg', 'MSMR2013', 'Gallery asset for node MSMR2013'),
(51, 'MSMR2013_1780465802_3911.jpg', 'MSMR2013', 'Gallery asset for node MSMR2013'),
(52, 'MSMR2013_1780465802_3431.jpg', 'MSMR2013', 'Gallery asset for node MSMR2013'),
(53, 'MSMR2012_1780465826_2206.jpg', 'MSMR2012', 'Gallery asset for node MSMR2012'),
(54, 'MSMR2012_1780465826_8273.jpg', 'MSMR2012', 'Gallery asset for node MSMR2012'),
(55, 'MSMR2012_1780465826_3027.jpg', 'MSMR2012', 'Gallery asset for node MSMR2012');
ALTER TABLE `academic_schedule`
  ADD PRIMARY KEY (`sch_id`),
  ADD KEY `fk_sch_venue` (`vid`),
  ADD KEY `fk_sch_semester` (`sem_id`);
ALTER TABLE `admin`
  ADD PRIMARY KEY (`aid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_name` (`admin_name`);
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bid`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `fk_booking_admin` (`aid`),
  ADD KEY `fk_booking_user` (`uid`),
  ADD KEY `fk_booking_venue` (`vid`);
ALTER TABLE `damage_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_booking_id` (`bid`),
  ADD KEY `fk_user_id` (`uid`),
  ADD KEY `fk_venue_id` (`vid`);
ALTER TABLE `inspection`
  ADD PRIMARY KEY (`ins_id`),
  ADD KEY `fk_ins_booking` (`bid`),
  ADD KEY `fk_ins_staff` (`sid`);
ALTER TABLE `inspic`
  ADD PRIMARY KEY (`pic_id`),
  ADD KEY `fk_inspic_inspection` (`ins_id`);
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);
ALTER TABLE `report`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `fk_report_ins` (`ins_id`);
ALTER TABLE `semester_config`
  ADD PRIMARY KEY (`sem_id`);
ALTER TABLE `staff`
  ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `staff_name` (`staff_name`);
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `vcategory`
  ADD PRIMARY KEY (`vcid`);
ALTER TABLE `venue`
  ADD PRIMARY KEY (`vid`),
  ADD UNIQUE KEY `vname` (`vname`),
  ADD KEY `vcategory_id` (`vcid`);
ALTER TABLE `vpic`
  ADD PRIMARY KEY (`pic_id`),
  ADD KEY `fk_vpic_venue` (`vid`);
ALTER TABLE `academic_schedule`
  MODIFY `sch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;
ALTER TABLE `admin`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8014;
ALTER TABLE `booking`
  MODIFY `bid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000060;
ALTER TABLE `damage_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `inspection`
  MODIFY `ins_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000023;
ALTER TABLE `inspic`
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `password_resets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
ALTER TABLE `report`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40000018;
ALTER TABLE `staff`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9003;
ALTER TABLE `vcategory`
  MODIFY `vcid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
ALTER TABLE `vpic`
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;
ALTER TABLE `academic_schedule`
  ADD CONSTRAINT `fk_sch_semester` FOREIGN KEY (`sem_id`) REFERENCES `semester_config` (`sem_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sch_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE;
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_admin` FOREIGN KEY (`aid`) REFERENCES `admin` (`aid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `damage_report`
  ADD CONSTRAINT `fk_booking_id` FOREIGN KEY (`bid`) REFERENCES `booking` (`bid`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`),
  ADD CONSTRAINT `fk_venue_id` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`);
ALTER TABLE `inspection`
  ADD CONSTRAINT `fk_ins_booking` FOREIGN KEY (`bid`) REFERENCES `booking` (`bid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ins_staff` FOREIGN KEY (`sid`) REFERENCES `staff` (`sid`);
ALTER TABLE `inspic`
  ADD CONSTRAINT `fk_inspic_inspection` FOREIGN KEY (`ins_id`) REFERENCES `inspection` (`ins_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_ins` FOREIGN KEY (`ins_id`) REFERENCES `inspection` (`ins_id`) ON DELETE CASCADE;
ALTER TABLE `venue`
  ADD CONSTRAINT `vcategory_id` FOREIGN KEY (`vcid`) REFERENCES `vcategory` (`vcid`);
ALTER TABLE `vpic`
  ADD CONSTRAINT `fk_vpic_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE;
DELIMITER $$
CREATE DEFINER=`root`@`localhost` EVENT `ev_master_sla_daemon` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-06-14 14:14:33' ON COMPLETION PRESERVE ENABLE DO BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_bid INT;
    DECLARE v_date_booked DATE;
    DECLARE v_time_end TIME;
    DECLARE v_optimal_sid INT;
    DECLARE cur_pending_assignments CURSOR FOR
        SELECT b.bid, b.date_booked, b.time_end
        FROM booking b
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status = 'approved'
          AND i.ins_id IS NULL
          AND TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(b.date_booked, ' ', b.time_end)) <= 15;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    START TRANSACTION;
  UPDATE booking
  SET status = 'cancelled',
      payment_status = 'refunded',
      cancel_reason = 'SLA Violation: Admin Timeout or Slot Minimum Viability Expiration.',
      cancelled_at = NOW()
  WHERE status = 'pending'
    AND payment_status = 'paid'
    AND (
        TIMESTAMPADD(MINUTE, -5, CAST(CONCAT(date_booked, ' ', time_end) AS DATETIME)) <= NOW()
        OR
        TIMESTAMPADD(MINUTE, 15, GREATEST(CAST(CONCAT(date_booked, ' ', time_start) AS DATETIME), created_at)) <= NOW()
    );
    OPEN cur_pending_assignments;
    assignment_loop: LOOP
        FETCH cur_pending_assignments INTO v_bid, v_date_booked, v_time_end;
        IF done THEN LEAVE assignment_loop; END IF;
        SET v_optimal_sid = NULL;
        SELECT s.sid INTO v_optimal_sid
        FROM staff s
        WHERE s.position = 'inspector' AND s.status = 'active'
          AND s.sid NOT IN (
              SELECT i2.sid
              FROM inspection i2 JOIN booking b2 ON i2.bid = b2.bid
              WHERE i2.ins_status = 'pending'
                AND b2.date_booked = v_date_booked
                AND ABS(TIMESTAMPDIFF(MINUTE, b2.time_end, v_time_end)) < 30
          )
        ORDER BY (
            SELECT COUNT(*) FROM inspection i3 JOIN booking b3 ON i3.bid = b3.bid
            WHERE i3.sid = s.sid AND b3.date_booked = v_date_booked
        ) ASC LIMIT 1;
        IF v_optimal_sid IS NOT NULL THEN
            INSERT INTO inspection (bid, sid, ins_status) VALUES (v_bid, v_optimal_sid, 'pending');
        END IF;
    END LOOP;
    CLOSE cur_pending_assignments;
    INSERT INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at)
    SELECT i.ins_id, 0.00, 'pending', 'none', CURDATE()
    FROM inspection i
    JOIN booking b ON i.bid = b.bid
    WHERE i.ins_status = 'pending'
      AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW()
      AND NOT EXISTS (SELECT 1 FROM report r WHERE r.ins_id = i.ins_id);
    UPDATE inspection i
    JOIN booking b ON i.bid = b.bid
    SET i.ins_status = 'overdue',
        i.damage_desc = 'SLA Violation: Inspector Timeout. Auto-released.'
    WHERE i.ins_status = 'pending'
      AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW();
    UPDATE booking b
    JOIN inspection i ON b.bid = i.bid
    SET b.status = 'completed'
    WHERE i.ins_status = 'overdue' AND b.status = 'approved';
    COMMIT;
END$$
DELIMITER ;
COMMIT;
;
;
;
