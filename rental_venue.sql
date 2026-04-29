-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 04:50 AM
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
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `aid` int(10) UNSIGNED NOT NULL,
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
(8000, 'SuperAdmin', 'SA@mmu.edu.my', '$2y$10$0av5Zh5QYMrLJyALrb8O5u60U292chJEz7SdcwfkthLTmx0j1RCw2', '06123456789', 'super_admin', '2026-04-28 09:43:39'),
(8002, 'Siti', 'Siti@mmu.edu.my', '$2y$10$Q3kh7pg/gGCiw.JnpGremOrH26gnUTq.y3LZohGD9qpaPt/k4YZyu', '0122233456', 'admin', '2026-04-28 15:55:39');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `bid` int(10) UNSIGNED NOT NULL,
  `uid` varchar(15) NOT NULL,
  `vid` int(10) UNSIGNED NOT NULL,
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
(20000014, '242DT2430C', 1000, '2026-04-29', '01:30:00', '02:30:00', 'completed', 'paid', 'TXN-9203A980', 'test', NULL, NULL, '2026-04-28 17:31:13');

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
(30000009, 20000014, 9000, 'pending', NULL, 0.00, 0.00, NULL);

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
  `position` enum('inspector','manager','admin') DEFAULT 'inspector',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`sid`, `staff_name`, `email`, `password`, `phone_num`, `position`, `created_at`) VALUES
(9000, 'Vikram', 'vikram@gmail.com', '$2y$10$hU8obf2c0SE317q2FH1Qs.sWcrUC3MneI6SKOOYTq2ux7AiouOzsO', '0122233456', 'inspector', '2026-04-28 15:25:58');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`uid`, `username`, `email`, `password`, `phone_num`, `created_at`) VALUES
('242DT2430C', 'LIM', 'Lim@gmail.com', '$2y$10$7sFovpK/duwjvV1jbwrfROmzxvURhuIXPJfEj5t3ePzg3j4vmqnOO', '0122233456', '2026-04-28 14:04:09');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `vid` int(10) UNSIGNED NOT NULL,
  `vname` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `max_cap` int(10) UNSIGNED NOT NULL,
  `deposit` decimal(10,2) UNSIGNED NOT NULL,
  `status` enum('available','maintenance','booked') NOT NULL DEFAULT 'available',
  `pic` varchar(255) DEFAULT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`vid`, `vname`, `category`, `max_cap`, `deposit`, `status`, `pic`, `description`) VALUES
(1000, 'MNBR2002', 'Hall', 20, 20.00, 'available', NULL, 'Standard discussion room with AV support.');

-- --------------------------------------------------------

--
-- Table structure for table `venue_category`
--

CREATE TABLE `venue_category` (
  `category_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `venue_category`
--

INSERT INTO `venue_category` (`category_name`, `created_at`) VALUES
('Classroom', '2026-04-29 02:21:31'),
('Hall', '2026-04-29 02:21:31'),
('Lab', '2026-04-29 02:21:31'),
('Meeting Room', '2026-04-29 02:21:31');

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
-- Indexes for table `venue_category`
--
ALTER TABLE `venue_category`
  ADD PRIMARY KEY (`category_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `aid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8003;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `bid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20000015;

--
-- AUTO_INCREMENT for table `inspection`
--
ALTER TABLE `inspection`
  MODIFY `ins_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30000010;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `rid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40000004;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `sid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9001;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `vid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1001;

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
