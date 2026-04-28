-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 28, 2026 at 11:24 AM
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
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `aid` varchar(10) NOT NULL,
  `admin_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `role` enum('super_admin','admin') NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`aid`, `admin_name`, `email`, `password`, `phone_num`, `role`, `created_at`) VALUES
('mmu001', 'SuperAdmin', 'SA@mmu.edu.my', 'SA123', '06123456789', 'super_admin', '2026-04-28 03:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bid` varchar(12) NOT NULL,
  `uid` varchar(10) NOT NULL,
  `vid` varchar(12) NOT NULL,
  `date_booked` date NOT NULL,
  `time_start` time NOT NULL,
  `duration` int(10) UNSIGNED NOT NULL COMMENT 'minutes',
  `status` enum('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `transaction_ref` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) NOT NULL,
  `aid` varchar(10) DEFAULT NULL,
  `approve_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`bid`, `uid`, `vid`, `date_booked`, `time_start`, `duration`, `status`, `payment_status`, `transaction_ref`, `purpose`, `aid`, `approve_date`, `created_at`) VALUES
('BKG-F63FE0C2', '242DT2421C', 'VEN001', '2026-04-28', '17:30:00', 30, 'pending', 'unpaid', NULL, 'test', NULL, NULL, '2026-04-28 09:17:38');

-- --------------------------------------------------------

--
-- Table structure for table `inspection`
--

CREATE TABLE `inspection` (
  `ins_id` varchar(12) NOT NULL,
  `bid` varchar(12) NOT NULL,
  `sid` varchar(10) NOT NULL,
  `ins_status` enum('passed','failed','pending') NOT NULL DEFAULT 'pending',
  `damage_desc` text DEFAULT NULL,
  `damage_cost` decimal(10,2) UNSIGNED DEFAULT 0.00,
  `penalty` decimal(10,2) UNSIGNED DEFAULT 0.00,
  `inspected_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `rid` varchar(12) NOT NULL,
  `ins_id` varchar(12) NOT NULL,
  `final_deduct` decimal(10,2) DEFAULT 0.00,
  `refund_status` enum('none','pending','processed') DEFAULT 'none',
  `penalty_status` enum('none','pending','paid') DEFAULT 'none',
  `created_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `sid` varchar(10) NOT NULL,
  `staff_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `uid` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_num` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`uid`, `username`, `email`, `password`, `phone_num`, `created_at`) VALUES
('242DT2421C', 'KAM', 'KAM@mmu.edu.my', '$2y$10$GbLQrylvoBkdteBOBmO9g.Xq8y0MkWAzsz1Gy9IPwjNUqZG6lq0ge', '012222222', '2026-04-28 09:16:56'),
('242DT2430C', 'LIM', 'LIM@mmu.edu.my', 'Lim123', '01122222222', '2026-04-28 07:55:15');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `vid` varchar(12) NOT NULL,
  `vname` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `max_cap` int(11) UNSIGNED NOT NULL,
  `deposit` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('available','maintenance','booked') NOT NULL DEFAULT 'available',
  `pic` varchar(255) DEFAULT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`vid`, `vname`, `category`, `max_cap`, `deposit`, `status`, `pic`, `description`) VALUES
('VEN001', 'MNBR2002', 'Discussion Room', 20, 20.00, 'available', NULL, '');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `fk_report_ins` (`ins_id`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`vid`),
  ADD UNIQUE KEY `vname` (`vname`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_admin` FOREIGN KEY (`aid`) REFERENCES `admin` (`aid`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_venue` FOREIGN KEY (`vid`) REFERENCES `venue` (`vid`) ON DELETE CASCADE;

--
-- Constraints for table `inspection`
--
ALTER TABLE `inspection`
  ADD CONSTRAINT `fk_ins_booking` FOREIGN KEY (`bid`) REFERENCES `booking` (`bid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ins_staff` FOREIGN KEY (`sid`) REFERENCES `staff` (`sid`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `fk_report_ins` FOREIGN KEY (`ins_id`) REFERENCES `inspection` (`ins_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
