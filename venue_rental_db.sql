-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 07:06 AM
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
-- Database: `venue_rental_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `booking_status` enum('Pending','Approved','Rejected','Completed','Cancelled','Returned') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('Pending','Paid','Refunded') DEFAULT 'Pending',
  `transaction_ref` varchar(50) DEFAULT NULL,
  `purpose` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `venue_id`, `booking_date`, `start_time`, `end_time`, `booking_status`, `created_at`, `payment_status`, `transaction_ref`, `purpose`) VALUES
(1, 1, 2, '2026-03-17', '14:00:00', '16:00:00', 'Completed', '2026-03-16 03:13:04', 'Pending', NULL, NULL),
(11, 3, 2, '2026-03-19', '10:00:00', '12:00:00', 'Approved', '2026-03-17 07:48:57', 'Pending', NULL, NULL),
(15, 5, 1, '2026-04-05', '13:36:00', '14:36:00', 'Pending', '2026-04-05 04:39:41', 'Paid', 'TXN-06DD7201', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `inspection_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `inspected_by` int(11) NOT NULL,
  `inspection_status` enum('Good','Dirty','Minor_Damage','Major_Damage') DEFAULT 'Good',
  `damage_description` text DEFAULT NULL,
  `assessed_penalty` decimal(10,2) DEFAULT 0.00,
  `inspected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`inspection_id`, `booking_id`, `inspected_by`, `inspection_status`, `damage_description`, `assessed_penalty`, `inspected_at`) VALUES
(2, 1, 2, 'Minor_Damage', 'damaged two chairs', 100.00, '2026-03-17 03:08:17');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `deposit_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `final_deduction` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Deposit_Held','Refunded','Outstanding_Balance','Settled') DEFAULT 'Deposit_Held',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `deposit_paid`, `final_deduction`, `payment_status`, `updated_at`) VALUES
(1, 1, 20.00, 100.00, 'Outstanding_Balance', '2026-03-17 03:08:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('User','Normal_Admin','Super_Admin') DEFAULT 'User',
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `phone_number`, `created_at`) VALUES
(1, 'Student Abu', 'abu@student.mmu.edu.my', '123456', '', NULL, '2026-03-16 03:13:04'),
(2, 'Admin Siti', 'admin@mmu.edu.my', '$2y$10$4qfiQWytGNqZWE5dlDVio.BIKys.5AQli/TwSWTI9l7fFYOgK3VMu', 'Super_Admin', NULL, '2026-03-16 03:13:04'),
(3, 'ahmed', 'ahmed@student.mmu.edu.my', 'ahmed123', '', '0123456789', '2026-03-17 07:06:12'),
(4, 'Abdul', 'abdul@gmail.com', '$2y$10$hNRMw32b1hAlg5XjPu60v.fep6.4lC/Kr0YlS0Et6Fl8VQLj9oESi', 'Normal_Admin', NULL, '2026-04-05 03:36:19'),
(5, 'Lim', 'Lim@student.mmu.edu.my', '$2y$10$pVQHqZPp91nIGrGqurRsqe9xelg6vCEpaInQPzAiRwNKX.Z86VUvq', 'User', NULL, '2026-04-05 03:45:12');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL,
  `venue_name` varchar(100) NOT NULL,
  `category` enum('Discussion Room','Sports Court','Event Hall','Meeting Room') NOT NULL,
  `capacity` int(11) NOT NULL,
  `base_deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Available','Maintenance','Closed') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `venue_name`, `category`, `capacity`, `base_deposit`, `status`, `created_at`) VALUES
(1, 'Main Hall A', 'Event Hall', 200, 150.00, 'Available', '2026-03-16 03:13:04'),
(2, 'Discussion Room 1', 'Discussion Room', 8, 20.00, 'Closed', '2026-03-16 03:13:04'),
(7, 'Basket Ball Court', 'Sports Court', 20, 100.00, 'Available', '2026-03-17 08:58:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_booking_time` (`venue_id`,`booking_date`,`start_time`,`end_time`);

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`inspection_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`),
  ADD KEY `inspected_by` (`inspected_by`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `inspection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE;

--
-- Constraints for table `inspections`
--
ALTER TABLE `inspections`
  ADD CONSTRAINT `inspections_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inspections_ibfk_2` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
