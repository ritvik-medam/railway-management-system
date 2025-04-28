-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2025 at 01:53 PM
-- Server version: 8.0.34
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `railway`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `train_id` int DEFAULT NULL,
  `journey_date` date DEFAULT NULL,
  `coach_type` varchar(10) DEFAULT NULL,
  `group_id` varchar(20) DEFAULT NULL,
  `booking_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `train_id`, `journey_date`, `coach_type`, `group_id`, `booking_time`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, '2025-04-24 14:28:20'),
(2, 4, 1, '2025-04-25', '3AC', '', '2025-04-25 04:10:54'),
(3, 4, 1, '2025-04-27', '3AC', '', '2025-04-25 06:12:45'),
(4, 4, 1, '2025-04-27', '3AC', '', '2025-04-26 12:56:39'),
(5, 4, 1, '2025-04-27', '3AC', '', '2025-04-27 04:16:23'),
(6, 4, 1, '2025-04-27', '3AC', '', '2025-04-27 08:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `lost_items`
--

CREATE TABLE `lost_items` (
  `id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `lost_date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `contact_info` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lost_items`
--

INSERT INTO `lost_items` (`id`, `item_name`, `description`, `lost_date`, `location`, `contact_info`, `created_at`) VALUES
(1, 'wallet', 'wallet , black wallet, with a god picture inside', '2025-04-24', 'coach 12, seat 8, rajdhani express', '9812799390', '2025-04-27 06:06:02'),
(2, 'wallet', 'wallet , blue color', '2025-04-22', 'seat number 12, coach 4', '96784653', '2025-04-27 11:42:46');

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `age` int DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `passenger_type` varchar(20) DEFAULT NULL,
  `seat_number` int DEFAULT NULL,
  `berth_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `booking_id`, `name`, `age`, `gender`, `passenger_type`, `seat_number`, `berth_type`) VALUES
(1, 2, 'Nandini', 19, 'female', 'Women', 1, 'Lower'),
(2, 3, 'SWAMY', 64, 'male', 'Senior', 7, 'Lower'),
(3, 4, 'ananya', 19, 'female', 'Women', 2, 'Side Lower'),
(4, 5, 'swamy', 25, 'male', 'Adult', 1, 'Side Lower'),
(5, 6, 'nandu', 19, 'female', 'Adult', 1, 'Lower');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `booking_id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `payment_details` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int NOT NULL,
  `train_id` int DEFAULT NULL,
  `journey_date` date DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `available_seats` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `train_id`, `journey_date`, `departure_time`, `arrival_time`, `available_seats`) VALUES
(1, 1, '2025-04-27', '06:00:00', '14:30:00', 120),
(2, 2, '2025-04-30', '08:15:00', '16:45:00', 100),
(3, 3, '2025-04-27', '10:00:00', '19:20:00', 95),
(4, 4, '2025-05-02', '12:30:00', '21:15:00', 110),
(5, 5, '2025-04-27', '14:00:00', '23:10:00', 130),
(6, 6, '2025-04-27', '16:45:00', '01:30:00', 80),
(7, 7, '2025-05-01', '18:30:00', '03:00:00', 90),
(8, 8, '2025-04-28', '20:00:00', '05:45:00', 115),
(9, 9, '2025-04-25', '05:45:00', '13:15:00', 125),
(10, 10, '2025-04-25', '07:20:00', '15:00:00', 98),
(11, 11, '2025-04-25', '09:00:00', '17:45:00', 105),
(12, 12, '2025-04-25', '11:10:00', '19:30:00', 94),
(13, 13, '2025-04-25', '13:25:00', '22:00:00', 99),
(14, 14, '2025-04-25', '15:40:00', '00:30:00', 100),
(15, 15, '2025-04-25', '17:00:00', '02:10:00', 75),
(16, 16, '2025-04-25', '19:20:00', '04:50:00', 102),
(17, 17, '2025-04-25', '21:30:00', '06:30:00', 87),
(18, 18, '2025-04-25', '23:00:00', '07:15:00', 93),
(19, 19, '2025-04-25', '04:30:00', '12:15:00', 120),
(20, 20, '2025-04-30', '06:15:00', '14:00:00', 101);

-- --------------------------------------------------------

--
-- Table structure for table `stations`
--

CREATE TABLE `stations` (
  `station_id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`station_id`, `name`) VALUES
(7, 'Ahmedabad Junction'),
(20, 'Amritsar Junction'),
(6, 'Bangalore City'),
(11, 'Bhopal Junction'),
(3, 'Chennai Central'),
(19, 'Coimbatore Junction'),
(13, 'Guwahati'),
(9, 'Jaipur Junction'),
(4, 'Kolkata Howrah'),
(10, 'Lucknow NR'),
(2, 'Mumbai Central'),
(16, 'Nagpur Junction'),
(1, 'New Delhi'),
(12, 'Patna Junction'),
(8, 'Pune Junction'),
(5, 'Secunderabad'),
(17, 'Surat'),
(14, 'Thiruvananthapuram Central'),
(18, 'Varanasi Junction'),
(15, 'Visakhapatnam');

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

CREATE TABLE `trains` (
  `train_id` int NOT NULL,
  `train_name` varchar(100) NOT NULL,
  `source_station_id` int DEFAULT NULL,
  `destination_station_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `trains`
--

INSERT INTO `trains` (`train_id`, `train_name`, `source_station_id`, `destination_station_id`) VALUES
(1, 'Rajdhani Express', 1, 2),
(2, 'Shatabdi Express', 3, 4),
(3, 'Duronto Express', 5, 6),
(4, 'Garib Rath Express', 7, 8),
(5, 'Intercity Express', 9, 10),
(6, 'Superfast Express', 11, 12),
(7, 'Jan Shatabdi', 13, 14),
(8, 'Humsafar Express', 15, 16),
(9, 'Vande Bharat Express', 17, 18),
(10, 'Sampark Kranti', 19, 20),
(11, 'Mail Express', 2, 11),
(12, 'Double Decker', 4, 17),
(13, 'Tejas Express', 6, 13),
(14, 'Yuva Express', 8, 5),
(15, 'Antyodaya Express', 10, 1),
(16, 'Uday Express', 12, 9),
(17, 'Sujata Express', 14, 3),
(18, 'Kamakhya Express', 16, 7),
(19, 'Punjab Mail', 18, 15),
(20, 'Golden Temple Mail', 20, 19);

-- --------------------------------------------------------

--
-- Table structure for table `trainseats`
--

CREATE TABLE `trainseats` (
  `seat_id` int NOT NULL,
  `train_id` int DEFAULT NULL,
  `journey_date` date DEFAULT NULL,
  `coach_type` varchar(10) DEFAULT NULL,
  `seat_number` int DEFAULT NULL,
  `is_booked` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`) VALUES
(2, 'NIKI', 'NIKI43@GMAIL.COM', '$2y$10$YctTcuUzH/vKESspBG6/Ku1CFV67TkdsEvt3MuUPpjWgnKJbUFNHO'),
(3, 'ROOT ', 'root@gmail.com', '$2y$10$TkXd2KcFAWwppg793LK/e.dO.UBAFWsUeFA/ykuWqg7K674I7E0xq'),
(4, 'NANDINI', 'nandini45396@gmail.com', '$2y$10$z.gYlxTyDaaPpu9O4VQ8xuu1J7vIYS91U3pfiaICqKo7R7pG/Kxp6'),
(5, 'krishna', 'krishna53@gmail.com', '$2y$10$GpspWHLv8cRoxPdjlKqVrOGSCJz9H5PKfiGl1B8Ef1YJZRe//jESS'),
(6, 'ANU', 'ANU45@GMAIL.COM', '$2y$10$a99bKFns6xgmrIXd6VC8U.A2Tmg0dMoRGq2ozQgcFxJ.iOb9m03JC'),
(7, 'wera', 'wera23@gmail.com', '$2y$10$ZPG1BNTNqook/we4n00LTuMY4BOPrXHXgyWj1KKIjcQ5WUXnq87b.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `train_id` (`train_id`);

--
-- Indexes for table `lost_items`
--
ALTER TABLE `lost_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `train_id` (`train_id`);

--
-- Indexes for table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`station_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`train_id`),
  ADD KEY `source_station_id` (`source_station_id`),
  ADD KEY `destination_station_id` (`destination_station_id`);

--
-- Indexes for table `trainseats`
--
ALTER TABLE `trainseats`
  ADD PRIMARY KEY (`seat_id`),
  ADD UNIQUE KEY `train_id` (`train_id`,`journey_date`,`coach_type`,`seat_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lost_items`
--
ALTER TABLE `lost_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `stations`
--
ALTER TABLE `stations`
  MODIFY `station_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `trains`
--
ALTER TABLE `trains`
  MODIFY `train_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `trainseats`
--
ALTER TABLE `trainseats`
  MODIFY `seat_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`train_id`) REFERENCES `trains` (`train_id`);

--
-- Constraints for table `passengers`
--
ALTER TABLE `passengers`
  ADD CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`train_id`);

--
-- Constraints for table `trains`
--
ALTER TABLE `trains`
  ADD CONSTRAINT `trains_ibfk_1` FOREIGN KEY (`source_station_id`) REFERENCES `stations` (`station_id`),
  ADD CONSTRAINT `trains_ibfk_2` FOREIGN KEY (`destination_station_id`) REFERENCES `stations` (`station_id`);

--
-- Constraints for table `trainseats`
--
ALTER TABLE `trainseats`
  ADD CONSTRAINT `trainseats_ibfk_1` FOREIGN KEY (`train_id`) REFERENCES `trains` (`train_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
