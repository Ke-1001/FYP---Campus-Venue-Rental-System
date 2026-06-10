-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2026 at 05:33 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_venue`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_schedule`
--

CREATE TABLE `academic_schedule` (
  `sch_id` int(10) UNSIGNED NOT NULL,
  `sem_id` int(4) UNSIGNED NOT NULL,
  `vid` varchar(10) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject_name` varchar(100) DEFAULT 'Academic Class'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_schedule`
--

INSERT INTO `academic_schedule` (`sch_id`, `sem_id`, `vid`, `day_of_week`, `start_time`, `end_time`, `subject_name`) VALUES
(16, 2610, 'MSMR2012', 'Monday', '08:00:00', '10:00:00', 'TCC 4223'),
(17, 2610, 'MSMX0001', 'Monday', '08:00:00', '10:00:00', 'Data Structures'),
(18, 2610, 'MSMX0001', 'Monday', '10:00:00', '12:00:00', 'Algorithms'),
(19, 2610, 'MSMR2016', 'Tuesday', '14:00:00', '16:00:00', 'Database Systems'),
(20, 2610, 'MSMR2012', 'Wednesday', '09:00:00', '11:00:00', 'Software Engineering'),
(21, 2610, 'MSMX0002', 'Thursday', '10:00:00', '13:00:00', 'Operating Systems'),
(22, 2610, 'MSMX0003', 'Friday', '08:00:00', '10:00:00', 'Computer Networks'),
(23, 2610, 'MSMR3012', 'Monday', '14:00:00', '16:00:00', 'AI Fundamentals'),
(24, 2610, 'MSMR3013', 'Tuesday', '10:00:00', '12:00:00', 'Machine Learning'),
(25, 2610, 'MSMR3014', 'Wednesday', '14:00:00', '17:00:00', 'Deep Learning'),
(26, 2610, 'MSMR3015', 'Thursday', '08:00:00', '10:00:00', 'Cloud Computing'),
(27, 2610, 'MSMX2001', 'Friday', '14:00:00', '16:00:00', 'Cyber Security'),
(28, 2610, 'MSMX2002', 'Monday', '16:00:00', '18:00:00', 'Web Development'),
(29, 2610, 'MSMX0003', 'Saturday', '09:00:00', '12:00:00', 'Weekend Workshop'),
(30, 2610, 'MSMR2016', 'Monday', '08:00:00', '10:00:00', 'Calculus I'),
(31, 2610, 'MSMX2003', 'Wednesday', '12:00:00', '14:00:00', 'Ethics in IT');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

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

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`aid`, `admin_name`, `email`, `password`, `phone_num`, `profile_pic`, `role`, `status`, `created_at`) VALUES
(8000, 'SuperAdmin', 'SA@mmu.edu.my', '$2y$10$0av5Zh5QYMrLJyALrb8O5u60U292chJEz7SdcwfkthLTmx0j1RCw2', '06123456789', '', 'super_admin', 'active', '2026-04-28 09:43:39'),
(8002, 'Siti', 'Siti@mmu.edu.my', '$2y$10$Q3kh7pg/gGCiw.JnpGremOrH26gnUTq.y3LZohGD9qpaPt/k4YZyu', '0122233456', '', 'admin', 'active', '2026-04-28 15:55:39'),
(8009, 'testLim', 'LIM.LI.GUAN@student.mmu.edu.my', '$2y$10$e7zgRGN9UdAGmSLemLKK5Or8isHMrcQ0WzC3YDFVJblxK5iY.sAyK', '0122233456', '', 'admin', 'active', '2026-06-02 13:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bid` int(10) UNSIGNED NOT NULL,
  `uid` varchar(15) NOT NULL,
  `vid` varchar(10) NOT NULL,
  `date_booked` date NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `payment_due_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `transaction_ref` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `aid` int(10) UNSIGNED DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bid`, `uid`, `vid`, `date_booked`, `time_start`, `time_end`, `status`, `payment_status`, `payment_due_at`, `cancelled_at`, `cancel_reason`, `transaction_ref`, `purpose`, `aid`, `approve_date`, `created_at`) VALUES
(20000014, '242DT2430C', 'MSMR2016', '2026-04-29', '13:30:00', '14:30:00', 'completed', 'paid', NULL, NULL, NULL, 'TXN-9203A980', 'test', 8002, '2026-04-29 12:44:41', '2026-04-28 17:31:13'),
(20000015, '242DT2429C', 'MSMR2016', '2026-07-02', '11:00:00', '14:30:00', 'cancelled', 'unpaid', '2026-05-06 10:48:41', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test', NULL, NULL, '2026-05-06 02:33:41'),
(20000016, '242DT2429C', 'MSMR2016', '2026-05-08', '09:30:00', '10:30:00', 'cancelled', 'unpaid', '2026-05-06 10:48:52', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test', NULL, NULL, '2026-05-06 02:33:52'),
(20000017, '242DT2421C', 'MSMR2016', '2026-05-06', '16:30:00', '18:30:00', 'completed', 'paid', NULL, NULL, NULL, 'TXN-C80129AA', 'Discussion', 8000, '2026-05-06 14:47:59', '2026-05-06 06:33:38'),
(20000018, '242DT2429C', 'MSMR2016', '2026-05-07', '16:30:00', '17:30:00', 'rejected', 'paid', NULL, NULL, NULL, 'TXN-7538644A', 'teae', NULL, NULL, '2026-05-06 06:45:07'),
(20000019, '242DT2429C', 'MSMX0003', '2026-05-13', '12:30:00', '18:30:00', 'rejected', 'paid', NULL, NULL, NULL, 'TXN-WO89K1Z6', 'For large event discussion', NULL, NULL, '2026-05-11 10:42:36'),
(20000020, '242DT2429C', 'MSMX2001', '2026-05-16', '09:00:00', '10:30:00', 'completed', 'paid', NULL, NULL, NULL, 'TXN-D5TU789Q', 'For project video recording', 8002, '2026-05-12 08:37:40', '2026-05-11 10:44:26'),
(20000021, '242DT2431X', 'MSMR2012', '2026-05-19', '18:30:00', '19:30:00', 'completed', 'paid', NULL, NULL, NULL, 'TXN-156FD6DE', 'test semester', NULL, NULL, '2026-05-19 10:38:54'),
(20000022, '242DT2431X', 'MSMR2012', '2026-05-19', '20:00:00', '21:00:00', 'rejected', 'paid', NULL, NULL, NULL, NULL, 'test123', NULL, NULL, '2026-05-19 11:56:40'),
(20000023, '242DT2431X', 'MSMR2012', '2026-05-19', '21:00:00', '22:00:00', 'cancelled', 'unpaid', '2026-05-19 20:12:29', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 11:57:29'),
(20000024, '242DT2431X', 'MSMR2012', '2026-05-19', '22:00:00', '22:30:00', 'cancelled', 'unpaid', '2026-05-19 20:13:43', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 11:58:43'),
(20000025, '242DT2431X', 'MSMR2012', '2026-05-19', '22:30:00', '23:00:00', 'cancelled', 'unpaid', '2026-05-19 20:14:14', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 11:59:14'),
(20000026, '242DT2431X', 'MSMR2012', '2026-05-19', '23:00:00', '23:30:00', 'cancelled', 'unpaid', '2026-05-19 20:16:59', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 12:01:59'),
(20000027, '242DT2431X', 'MSMR2012', '2026-05-19', '23:30:00', '00:00:00', 'rejected', 'paid', NULL, NULL, NULL, 'TXN-72711AB7', 'test123', NULL, NULL, '2026-05-19 12:02:44'),
(20000028, '242DT2431X', 'MSMR2013', '2026-05-19', '20:00:00', '20:30:00', 'cancelled', 'unpaid', '2026-05-19 20:18:13', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 12:03:13'),
(20000029, '242DT2431X', 'MSMR2013', '2026-05-19', '20:30:00', '21:00:00', 'cancelled', 'unpaid', '2026-05-19 20:19:08', '2026-06-03 10:21:14', 'Payment deadline expired', NULL, 'test123', NULL, NULL, '2026-05-19 12:04:08'),
(20000030, '242DT2431X', 'MSMX0001', '2026-05-20', '14:30:00', '15:30:00', 'completed', 'paid', NULL, NULL, NULL, NULL, 'ter', NULL, NULL, '2026-05-20 06:33:59'),
(20000031, '242DT2431X', 'MSMX0001', '2026-05-20', '15:30:00', '16:00:00', 'pending', '', NULL, NULL, NULL, NULL, 'ter', NULL, NULL, '2026-05-20 06:35:07'),
(20000032, '242DT2429C', 'MSMX0001', '2026-06-17', '10:30:00', '12:00:00', 'pending', 'unpaid', NULL, NULL, NULL, NULL, 'test', NULL, NULL, '2026-06-03 02:02:21'),
(20000033, '242DT2429C', 'MSMX0001', '2026-06-17', '13:30:00', '15:00:00', 'cancelled', 'unpaid', '2026-06-03 10:23:05', '2026-06-03 10:23:09', 'Payment deadline expired', NULL, 'test', NULL, NULL, '2026-06-03 02:08:05'),
(20000034, '242DT2430C', 'MSMX0001', '2026-06-08', '12:00:00', '12:30:00', 'approved', 'paid', '2026-06-08 09:19:49', NULL, NULL, 'TXN-43338C76', 'test', 8000, '2026-06-08 09:05:04', '2026-06-08 01:04:49'),
(20000035, '242DT2430C', 'MSMX0002', '2026-06-08', '09:00:00', '09:30:00', 'rejected', 'paid', '2026-06-08 09:20:25', NULL, NULL, 'TXN-283F5585', 'test', 8000, '2026-06-08 09:10:25', '2026-06-08 01:05:25'),
(20000036, '242DT2430C', 'MSMX0003', '2026-06-08', '09:00:00', '09:30:00', 'cancelled', 'refunded', '2026-06-08 09:20:45', '2026-06-08 09:30:44', 'SYS_TIMEOUT_ADMIN', 'TXN-07BD9FA5', 'test', NULL, NULL, '2026-06-08 01:05:45'),
(20000037, '242DT2430C', 'MSMX2002', '2026-06-08', '09:00:00', '09:30:00', 'cancelled', 'refunded', '2026-06-08 09:21:04', '2026-06-08 09:30:44', 'SYS_TIMEOUT_ADMIN', 'TXN-52A14C22', 'test', NULL, NULL, '2026-06-08 01:06:04'),
(20000038, '242DT2430C', 'MSMX2003', '2026-06-08', '09:00:00', '09:30:00', 'cancelled', 'refunded', '2026-06-08 09:21:24', '2026-06-08 09:30:44', 'SYS_TIMEOUT_ADMIN', 'TXN-6DD34D71', 'test', NULL, NULL, '2026-06-08 01:06:24'),
(20000039, '242DT2430C', 'MSMR3014', '2026-06-08', '09:00:00', '09:30:00', 'cancelled', 'refunded', '2026-06-08 09:21:44', '2026-06-08 09:30:44', 'SYS_TIMEOUT_ADMIN', 'TXN-97EC9959', 'test', NULL, NULL, '2026-06-08 01:06:44'),
(20000040, '242DT2429C', 'MSMX2002', '2026-06-30', '10:30:00', '11:30:00', 'pending', 'paid', '2026-06-10 10:28:55', NULL, NULL, 'TXN-0C145680', 'Group discussion', NULL, NULL, '2026-06-10 02:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `damage_report`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `inspection`
--

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

--
-- Dumping data for table `inspection`
--

INSERT INTO `inspection` (`ins_id`, `bid`, `sid`, `ins_status`, `damage_desc`, `damage_cost`, `penalty`, `inspected_at`) VALUES
(30000009, 20000014, 9000, 'failed', '', 0.00, 0.00, NULL),
(30000012, 20000021, 9000, 'passed', 'SYS_TIMEOUT_24H_RELEASE', 0.00, 0.00, '2026-06-09 20:09:05'),
(30000013, 20000034, 9000, 'passed', 'SYS_TIMEOUT_24H_RELEASE', 0.00, 0.00, '2026-06-09 20:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `inspic`
--

CREATE TABLE `inspic` (
  `pic_id` int(11) NOT NULL,
  `pic_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ins_id` int(10) UNSIGNED NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token_hash`, `expires_at`, `created_at`) VALUES
(1, 'LIM.LI.GUAN@student.mmu.edu.my', '677530785bd3ba7e88139db31a496d5182f0023c446b1607954fc59b41c7add6', '2026-06-02 15:30:36', '2026-06-02 12:30:36'),
(2, 'LIM.LI.GUAN@student.mmu.edu.my', 'f81fd734f7636cfe63354d02cb14c831b92df122a6b17ac253dc5fce6de98a4c', '2026-06-02 15:39:40', '2026-06-02 12:39:40'),
(3, 'LIM.LI.GUAN@student.mmu.edu.my', '10e575daba5adaaf497e7e9d42b62e3ea6d7209089265e0fc20b8f62d5363146', '2026-06-02 15:46:25', '2026-06-02 12:46:25'),
(4, 'LIM.LI.GUAN@student.mmu.edu.my', 'e514242ab64de08b0f721070a6055d542f1e8d874947001def602aafc18fd284', '2026-06-02 15:46:45', '2026-06-02 12:46:45'),
(6, 'LIM.LI.GUAN@student.mmu.edu.my', '3fc9278fb637d746f0f6c855fc45b00d1b3b11de38e624098e3f32ffdb76323c', '2026-06-02 16:02:19', '2026-06-02 13:02:19'),
(7, 'LIM.LI.GUAN@student.mmu.edu.my', '967dbf77994d3375ee6a8a7fcca440838938e6f6ded7ab487021c858b1ddbdd3', '2026-06-02 16:02:45', '2026-06-02 13:02:45');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `rid` int(10) UNSIGNED NOT NULL,
  `ins_id` int(10) UNSIGNED NOT NULL,
  `final_deduct` decimal(10,2) DEFAULT 0.00,
  `refund_status` enum('none','pending','processed') DEFAULT 'none',
  `penalty_status` enum('none','pending','paid') DEFAULT 'none',
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`rid`, `ins_id`, `final_deduct`, `refund_status`, `penalty_status`, `created_at`) VALUES
(40000004, 30000009, 0.00, '', 'none', '0000-00-00'),
(40000005, 30000012, 0.00, 'pending', 'none', '2026-06-09'),
(40000006, 30000013, 0.00, 'pending', 'none', '2026-06-09');

-- --------------------------------------------------------

--
-- Table structure for table `semester_config`
--

CREATE TABLE `semester_config` (
  `sem_id` int(4) UNSIGNED NOT NULL,
  `sem_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_booking_open` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semester_config`
--

INSERT INTO `semester_config` (`sem_id`, `sem_name`, `start_date`, `end_date`, `is_active`, `is_booking_open`) VALUES
(2610, 'Trimester March/April 2026', '2026-03-30', '2026-07-09', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

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

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`sid`, `staff_name`, `email`, `password`, `phone_num`, `profile_pic`, `position`, `status`, `created_at`) VALUES
(9000, 'Vikram', 'vikram@gmail.com', '$2y$10$hU8obf2c0SE317q2FH1Qs.sWcrUC3MneI6SKOOYTq2ux7AiouOzsO', '0122233456', '', 'inspector', 'active', '2026-04-28 15:25:58');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

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

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`uid`, `username`, `email`, `password`, `phone_num`, `profile_pic`, `outstanding_debt`, `account_status`, `created_at`) VALUES
('242DT2421C', 'test', 'test@gmail.com', '$2y$10$TjGMkbwlVVQO.fRym2HgAOWAv3yrUL3/a0GyZjYu5LwPZZjRg6wmm', '01241241124', '', 0.00, 'active', '2026-05-06 06:21:11'),
('242DT2429C', 'KamJS', 'kam@gmail.com', '$2y$10$b9IdO4GLAwsebemQD0x1Q.MKQL1UlyAn6ZhVyKtRkbYRghAbt4VMC', '01156811078', '', 0.00, 'active', '2026-05-05 02:46:08'),
('242DT2430C', 'LIM', 'Lim@gmail.com', '$2y$10$7sFovpK/duwjvV1jbwrfROmzxvURhuIXPJfEj5t3ePzg3j4vmqnOO', '0122233456', '', 0.00, 'active', '2026-04-28 14:04:09'),
('242DT2431X', 'TestLim', 'TestLim@gmail.com', '$2y$10$IHgP9mcLA6Rc0Akf5rVUC.ZHUO2rYaNemocPx75QsXLtntTZXJDSG', '01223456789', '', 0.00, 'active', '2026-05-19 10:37:54'),
('242DT245Y6', 'Frank', 'kai@student.mmu.edu.my', '$2y$10$aM1vOkGZ/5mKORZGGsxWCOfWYZkoOhDDbQV.gyBZa5m/lH9u1Lt5C', '0112334456', '', 0.00, 'active', '2026-05-06 06:17:40'),
('242DT267S4', 'Adam', 'yai@student.mmu.edu.my', '$2y$10$7Ph4jv5qNttlbv3xFlEYDOjQ8RI/7u/F5YTmzF7WSdiMYeL353lW2', '0111232455', '', 0.00, 'active', '2026-05-06 06:19:43');

-- --------------------------------------------------------

--
-- Table structure for table `vcategory`
--

CREATE TABLE `vcategory` (
  `vcid` int(11) NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vcategory`
--

INSERT INTO `vcategory` (`vcid`, `category`, `description`) VALUES
(1, 'Discussion Room', 'A small room with a max capacity of 30 people for discussion.'),
(2, 'Lecture Hall', 'A lecture hall with a max capacity of 100 people.'),
(3, 'Large Lecture Hall', 'The larger lecture hall that can accommodate up to 400 people at the same time.'),
(4, 'Sport Court', 'Court for sports activities.');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `vid` varchar(10) NOT NULL,
  `vname` varchar(100) NOT NULL,
  `vcid` int(11) NOT NULL,
  `max_cap` int(10) UNSIGNED NOT NULL,
  `deposit` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('available','maintenance','closed') NOT NULL DEFAULT 'available',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`vid`, `vname`, `vcid`, `max_cap`, `deposit`, `status`, `description`) VALUES
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
('MSMX2003', 'Lecture Hall B3', 2, 100, 10.00, 'available', 'A lecture hall with 100 person of capacity.');

-- --------------------------------------------------------

--
-- Table structure for table `vpic`
--

CREATE TABLE `vpic` (
  `pic_id` int(11) NOT NULL,
  `pic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vid` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vpic`
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_schedule`
--
ALTER TABLE `academic_schedule`
  ADD PRIMARY KEY (`sch_id`),
  ADD KEY `fk_sch_venue` (`vid`),
  ADD KEY `fk_sch_semester` (`sem_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`aid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_name` (`admin_name`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`bid`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `fk_booking_admin` (`aid`),
  ADD KEY `fk_booking_user` (`uid`),
  ADD KEY `fk_booking_venue` (`vid`);

--
-- Indexes for table `damage_report`
--
ALTER TABLE `damage_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_booking_id` (`bid`),
  ADD KEY `fk_user_id` (`uid`),
  ADD KEY `fk_venue_id` (`vid`);

--
-- Indexes for table `inspection`
--
ALTER TABLE `inspection`
  ADD PRIMARY KEY (`ins_id`),
  ADD KEY `fk_ins_booking` (`bid`),
  ADD KEY `fk_ins_staff` (`sid`);

--
-- Indexes for table `inspic`
--
ALTER TABLE `inspic`
  ADD PRIMARY KEY (`pic_id`),
  ADD KEY `fk_inspic_inspection` (`ins_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `fk_report_ins` (`ins_id`);

--
-- Indexes for table `semester_config`
--
ALTER TABLE `semester_config`
  ADD PRIMARY KEY (`sem_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `staff_name` (`staff_name`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vcategory`
--
ALTER TABLE `vcategory`
  ADD PRIMARY KEY (`vcid`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`vid`),
  ADD UNIQUE KEY `vname` (`vname`),
  ADD KEY `vcategory_id` (`vcid`);

--
-- Indexes for table `vpic`
--
ALTER TABLE `vpic`
  ADD PRIMARY KEY (`pic_id`),
  ADD KEY `fk_vpic_venue` (`vid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_schedule`
--
ALTER TABLE `academic_schedule`
  MODIFY `sch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8011;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000041;

--
-- AUTO_INCREMENT for table `damage_report`
--
ALTER TABLE `damage_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inspection`
--
ALTER TABLE `inspection`
  MODIFY `ins_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000014;

--
-- AUTO_INCREMENT for table `inspic`
--
ALTER TABLE `inspic`
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40000008;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9001;

--
-- AUTO_INCREMENT for table `vcategory`
--
ALTER TABLE `vcategory`
  MODIFY `vcid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vpic`
--
ALTER TABLE `vpic`
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_schedule`
--
ALTER TABLE `academic_schedule`
  ADD CONSTRAINT `fk_sch_semester` FOREIGN KEY (`sem_id`) REFERENCES `semester_config` (`sem_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sch_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE;

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_admin` FOREIGN KEY (`aid`) REFERENCES `admin` (`aid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `damage_report`
--
ALTER TABLE `damage_report`
  ADD CONSTRAINT `fk_booking_id` FOREIGN KEY (`bid`) REFERENCES `booking` (`bid`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`),
  ADD CONSTRAINT `fk_venue_id` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`);

--
-- Constraints for table `inspection`
--
ALTER TABLE `inspection`
  ADD CONSTRAINT `fk_ins_booking` FOREIGN KEY (`bid`) REFERENCES `booking` (`bid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ins_staff` FOREIGN KEY (`sid`) REFERENCES `staff` (`sid`);

--
-- Constraints for table `inspic`
--
ALTER TABLE `inspic`
  ADD CONSTRAINT `fk_inspic_inspection` FOREIGN KEY (`ins_id`) REFERENCES `inspection` (`ins_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_ins` FOREIGN KEY (`ins_id`) REFERENCES `inspection` (`ins_id`) ON DELETE CASCADE;

--
-- Constraints for table `venue`
--
ALTER TABLE `venue`
  ADD CONSTRAINT `vcategory_id` FOREIGN KEY (`vcid`) REFERENCES `vcategory` (`vcid`);

--
-- Constraints for table `vpic`
--
ALTER TABLE `vpic`
  ADD CONSTRAINT `fk_vpic_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_inspection_lifecycle_manager` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-06-08 08:54:05' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    START TRANSACTION;

    INSERT IGNORE INTO report (ins_id, final_deduct, refund_status, penalty_status, created_at)
    SELECT i.ins_id, 0.00, 'pending', 'none', CURDATE()
    FROM inspection i
    JOIN booking b ON i.bid = b.bid
    WHERE i.ins_status = 'overdue'
      AND TIMESTAMPADD(HOUR, 24, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW();

    UPDATE inspection i
    JOIN booking b ON i.bid = b.bid
    SET i.ins_status = 'passed',
        i.damage_desc = 'SYS_TIMEOUT_24H_RELEASE',
        i.penalty = 0.00,
        i.inspected_at = NOW()
    WHERE i.ins_status = 'overdue'
      AND TIMESTAMPADD(HOUR, 24, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW();

    UPDATE inspection i
    JOIN booking b ON i.bid = b.bid
    SET i.ins_status = 'overdue',
        i.damage_desc = 'SYS_TIMEOUT_30M_LOCK'
    WHERE i.ins_status = 'pending'
      AND TIMESTAMPADD(MINUTE, 30, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW();

    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` EVENT `ev_pre_usage_handler` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-06-08 09:30:44' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    START TRANSACTION;

    UPDATE booking
    SET status = 'cancelled', 
        payment_status = 'refunded', 
        cancel_reason = 'SYS_TIMEOUT_ADMIN',
        cancelled_at = NOW()
    WHERE status = 'pending' 
      AND payment_status = 'paid'
      AND TIMESTAMPADD(MINUTE, -5, CAST(CONCAT(date_booked, ' ', time_end) AS DATETIME)) <= NOW();

    INSERT INTO inspection (bid, sid, ins_status)
    SELECT target_booking.bid, optimal_staff.sid, 'pending'
    FROM (
        SELECT b.bid
        FROM booking b
        LEFT JOIN inspection i ON b.bid = i.bid
        WHERE b.status = 'approved' 
          AND i.ins_id IS NULL
          AND TIMESTAMPADD(MINUTE, -5, CAST(CONCAT(b.date_booked, ' ', b.time_end) AS DATETIME)) <= NOW()
    ) AS target_booking
    CROSS JOIN (
        SELECT s.sid
        FROM staff s
        LEFT JOIN inspection i ON s.sid = i.sid AND i.ins_status = 'pending'
        WHERE s.position = 'inspector' AND s.status = 'active'
        GROUP BY s.sid
        ORDER BY COUNT(i.ins_id) ASC
        LIMIT 1
    ) AS optimal_staff;

    COMMIT;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
