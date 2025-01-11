-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 11, 2025 at 01:51 PM
-- Server version: 8.3.0
-- PHP Version: 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `playsphere`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `futsal_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('pending','confirmed','cancelled','refunded','completed','playing') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid',
  `total_cost` decimal(10,2) NOT NULL,
  `cancel` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `futsal_id` (`futsal_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `futsals`
--

DROP TABLE IF EXISTS `futsals`;
CREATE TABLE IF NOT EXISTS `futsals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nic` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('customer','admin','staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `nic`, `phone_number`, `role`, `created_at`) VALUES
(64, 'Maizan', 'maiz.an', 'mohamedmaizanmunas@gmail.com', '$2y$10$zvhhSchoj6HbPVvAtyTbKu9ozyMwSOnWX.ngUVWsA3OBj.aSaopbS', NULL, '753357777', 'customer', '2025-01-02 09:16:33'),
(62, 'PlaySphere', 'playsphere', 'admin@playsphere.com', '$2y$10$gBqHo1bFpYX/JOO/72tf6u5ehHJccr8jf5lGpLXcksWESaMzJeXDm', NULL, '94753357777', 'admin', '2025-01-01 19:43:53'),
(0, 'unregistered', 'unreg', '', '', NULL, '', 'customer', '2025-01-02 03:46:33');

DELIMITER $$
--
-- Events
--
DROP EVENT IF EXISTS `auto_cancel_bookings`$$
CREATE DEFINER=`root`@`localhost` EVENT `auto_cancel_bookings` ON SCHEDULE EVERY 5 SECOND STARTS '2025-01-01 23:10:49' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Update the `cancel` column for bookings less than 30 minutes from the current time or past the start_time
    UPDATE `bookings`
    SET `cancel` = 1
    WHERE `cancel` = 0
      AND (
          TIMESTAMPDIFF(MINUTE, NOW(), `start_time`) < 30
          OR NOW() > `start_time`
      );
END$$

DROP EVENT IF EXISTS `UpdatePlayingStatus`$$
CREATE DEFINER=`root`@`localhost` EVENT `UpdatePlayingStatus` ON SCHEDULE EVERY 5 SECOND STARTS '2025-01-02 18:57:41' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Update status to 'playing' if the current time is the same as or past the start_time but before the end_time
    UPDATE bookings
    SET status = 'playing'
    WHERE NOW() >= start_time
      AND NOW() < end_time
      AND status = 'confirmed';

    -- Update status to 'completed' if the current time is past the end_time
    UPDATE bookings
    SET status = 'completed'
    WHERE NOW() >= end_time
      AND (status = 'confirmed' OR status = 'playing');
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
