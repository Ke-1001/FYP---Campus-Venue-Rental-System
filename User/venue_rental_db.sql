-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 02:47 PM
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
  `booking_status` enum('Pending','Approved','Returned','Rejected','Completed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `venue_id`, `booking_date`, `start_time`, `end_time`, `booking_status`, `created_at`) VALUES
(1, 1, 1, '2026-05-05', '11:11:00', '12:12:00', 'Pending', '2026-03-29 13:18:03');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `deposit_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Deposit_Held','Refunded','Forfeited','Paid') NOT NULL DEFAULT 'Deposit_Held',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `deposit_paid`, `payment_status`, `created_at`) VALUES
(1, 1, 100.00, 'Deposit_Held', '2026-03-29 13:18:03');

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
(2, 'Discussion Room 1', 'Discussion Room', 8, 20.00, 'Available', '2026-03-16 03:13:04'),
(7, 'Basket Ball Court', 'Sports Court', 20, 100.00, 'Available', '2026-03-17 08:58:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `venue_id` (`venue_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

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
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  ADD CONSTRAINT `fk_bookings_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
