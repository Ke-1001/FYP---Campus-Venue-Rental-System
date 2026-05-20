-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 12:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(8002, 'Siti', 'Siti@mmu.edu.my', '$2y$10$Q3kh7pg/gGCiw.JnpGremOrH26gnUTq.y3LZohGD9qpaPt/k4YZyu', '0122233456', '', 'admin', 'active', '2026-04-28 15:55:39');

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
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `transaction_ref` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `aid` int(10) UNSIGNED DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bid`, `uid`, `vid`, `date_booked`, `time_start`, `time_end`, `status`, `payment_status`, `transaction_ref`, `purpose`, `aid`, `approve_date`, `created_at`) VALUES
(20000014, '242DT2430C', 'MSMR2016', '2026-04-29', '13:30:00', '14:30:00', 'completed', 'paid', 'TXN-9203A980', 'test', 8002, '2026-04-29 12:44:41', '2026-04-28 17:31:13'),
(20000015, '242DT2429C', 'MSMR2016', '2026-07-02', '11:00:00', '14:30:00', 'pending', '', NULL, 'test', NULL, NULL, '2026-05-06 02:33:41'),
(20000016, '242DT2429C', 'MSMR2016', '2026-05-08', '09:30:00', '10:30:00', 'pending', '', NULL, 'test', NULL, NULL, '2026-05-06 02:33:52'),
(20000017, '242DT2421C', 'MSMR2016', '2026-05-06', '16:30:00', '18:30:00', 'completed', 'paid', 'TXN-C80129AA', 'Discussion', 8000, '2026-05-06 14:47:59', '2026-05-06 06:33:38'),
(20000018, '242DT2429C', 'MSMR2016', '2026-05-07', '16:30:00', '17:30:00', 'pending', 'paid', 'TXN-7538644A', 'teae', NULL, NULL, '2026-05-06 06:45:07'),
(20000019, '242DT2429C', 'MSMX0003', '2026-05-13', '12:30:00', '18:30:00', 'pending', 'paid', 'TXN-WO89K1Z6', 'For large event\'s discussion', NULL, NULL, '2026-05-11 10:42:36'),
(20000020, '242DT2429C', 'MSMX2001', '2026-05-16', '09:00:00', '10:30:00', 'completed', 'paid', 'TXN-D5TU789Q', 'For project video recording', 8002, '2026-05-12 08:37:40', '2026-05-11 10:44:26');

-- --------------------------------------------------------

--
-- Table structure for table `inspection`
--

CREATE TABLE `inspection` (
  `ins_id` int(10) UNSIGNED NOT NULL,
  `bid` int(10) UNSIGNED NOT NULL,
  `sid` int(10) UNSIGNED NOT NULL,
  `ins_status` enum('passed','failed','pending') NOT NULL DEFAULT 'pending',
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
(30000010, 20000017, 9000, 'pending', NULL, 0.00, 0.00, NULL);

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
(40000004, 30000009, 0.00, '', 'none', '0000-00-00');

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
  `status` enum('available','maintenance','booked') NOT NULL DEFAULT 'available',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`vid`, `vname`, `vcid`, `max_cap`, `deposit`, `status`, `description`) VALUES
('MSMR2012', 'Tutorial Room B5', 1, 25, 20.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2013', 'Tutorial Room B4', 1, 40, 20.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2014', 'Tutorial Room B3', 1, 35, 20.00, 'available', 'A small room which is good for small group discussion.'),
('MSMR2015', 'Tutorial Room B2', 1, 30, 20.00, 'maintenance', 'A small room which is good for small group discussion.'),
('MSMR2016', 'Tutorial Room B1', 1, 30, 20.00, 'available', 'A room with 30 person of capacity.'),
('MSMR3012', 'Tutorial Room A8', 1, 25, 20.00, 'available', 'A room with 25 person of capacity.'),
('MSMR3013', 'Tutorial Room A9', 1, 40, 20.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3014', 'Tutorial Room A10', 1, 35, 20.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3015', 'Tutorial Room A11', 1, 40, 20.00, 'available', 'Small room with full equipment used for discussion.'),
('MSMR3016', 'Tutorial Room A12', 1, 20, 20.00, 'available', 'Small room with full equipment for discussion.'),
('MSMX0001', 'Lecture Hall A1', 3, 380, 120.00, 'available', 'A large hall for large events and exams.'),
('MSMX0002', 'Lecture Hall A2', 3, 380, 120.00, 'available', 'A large hall for large events and exams.'),
('MSMX0003', 'Lecture Hall A3', 3, 380, 120.00, 'available', 'A large hall for large events and exams.'),
('MSMX0004', 'Lecture Hall A4', 3, 380, 120.00, 'maintenance', 'A large hall for large events and exams.'),
('MSMX2001', 'Lecture Hall B1', 2, 100, 40.00, 'maintenance', 'Lecture hall that can accommodate 100 people.'),
('MSMX2002', 'Lecture Hall B2', 2, 90, 45.00, 'available', 'Lecture hall that can accommodate 90 people.'),
('MSMX2003', 'Lecture Hall B3', 2, 100, 40.00, 'available', 'A lecture hall with 100 person of capacity.');

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
  MODIFY `sch_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8003;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000021;

--
-- AUTO_INCREMENT for table `inspection`
--
ALTER TABLE `inspection`
  MODIFY `ins_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000011;

--
-- AUTO_INCREMENT for table `inspic`
--
ALTER TABLE `inspic`
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40000005;

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
  MODIFY `pic_id` int(11) NOT NULL AUTO_INCREMENT;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
