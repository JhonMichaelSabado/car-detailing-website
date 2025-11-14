-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 11:11 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `car_detailing`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `related_table` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `admin_id`, `action`, `description`, `related_table`, `related_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, NULL, 1, 'booking_status_updated', 'Booking #18 status changed to confirmed', 'bookings', 18, NULL, NULL, '127.0.0.1', NULL, '2025-10-26 04:41:03'),
(2, NULL, 1, 'booking_status_updated', 'Booking #17 status changed to declined', 'bookings', 17, NULL, NULL, '127.0.0.1', NULL, '2025-10-26 05:23:03'),
(3, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #24, Amount: ₱1342.88', 'payments', 42, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:21:10'),
(4, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #25, Amount: ₱1342.88', 'payments', 44, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:21:41'),
(5, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #25, Amount: ₱1342.88', 'payments', 45, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:23:45'),
(6, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #26, Amount: ₱1342.88', 'payments', 47, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:24:01'),
(7, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #27, Amount: ₱1342.88', 'payments', 49, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:34:55'),
(8, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #28, Amount: ₱1594.56', 'payments', 51, NULL, NULL, '127.0.0.1', NULL, '2025-10-28 13:40:24'),
(9, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #29, Amount: ₱1566.88', 'payments', 53, NULL, NULL, '127.0.0.1', NULL, '2025-10-29 00:13:08'),
(10, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #30, Amount: ₱1342.88', 'payments', 55, NULL, NULL, '127.0.0.1', NULL, '2025-10-29 00:13:57'),
(11, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #31, Amount: ₱1202.32', 'payments', 57, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 00:34:44'),
(12, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #32, Amount: ₱1594.56', 'payments', 59, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 01:02:09'),
(13, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #33, Amount: ₱1594.56', 'payments', 61, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 01:08:11'),
(14, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #34, Amount: ₱1566.88', 'payments', 63, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 01:10:38'),
(15, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #35, Amount: ₱1902.88', 'payments', 65, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 01:16:53'),
(16, 6, NULL, 'payment_recorded', 'Payment recorded (status: pending) for booking #36, Amount: ₱2798.88', 'payments', 67, NULL, NULL, '127.0.0.1', NULL, '2025-11-01 01:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `addon_services`
--

CREATE TABLE `addon_services` (
  `addon_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_small` decimal(10,2) NOT NULL,
  `price_medium` decimal(10,2) NOT NULL,
  `price_large` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addon_services`
--

INSERT INTO `addon_services` (`addon_id`, `service_name`, `description`, `price_small`, `price_medium`, `price_large`, `duration_minutes`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Headlight Restoration', 'Professional headlight oxidation removal and clarity enhancement', '299.00', '349.00', '399.00', 45, 1, 1, '2025-10-13 11:30:40'),
(2, 'Engine Bay Cleaning', 'Thorough engine compartment cleaning and detailing', '499.00', '599.00', '699.00', 60, 1, 2, '2025-10-13 11:30:40'),
(3, 'Watermark Removal', 'Complete water spot and acid rain damage removal', '699.00', '799.00', '899.00', 90, 1, 3, '2025-10-13 11:30:40'),
(4, 'Leather Treatment', 'Professional leather conditioning and protection', '699.00', '899.00', '1099.00', 90, 1, 4, '2025-10-13 11:30:40'),
(5, 'Glass Polishing', 'Professional glass polishing for crystal clarity', '499.00', '599.00', '699.00', 60, 1, 5, '2025-10-13 11:30:40'),
(6, 'Pet Hair Removal', 'Specialized pet hair removal from vehicle interior', '299.00', '399.00', '499.00', 45, 1, 6, '2025-10-13 11:30:40'),
(7, 'Ozone Treatment', 'Advanced ozone treatment for odor elimination', '899.00', '999.00', '1199.00', 120, 1, 7, '2025-10-13 11:30:40');

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_payment_verification`
-- (See below for the actual view)
--
CREATE TABLE `admin_payment_verification` (
);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `vehicle_size` enum('small','medium','large') NOT NULL DEFAULT 'medium',
  `vehicle_id` int(11) DEFAULT NULL COMMENT 'Reference to user_vehicles table',
  `add_on_services` text DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `estimated_duration` int(11) DEFAULT 120,
  `service_address` text NOT NULL,
  `service_lat` decimal(10,8) DEFAULT NULL,
  `service_lng` decimal(11,8) DEFAULT NULL,
  `travel_fee` decimal(10,2) DEFAULT 0.00,
  `landmark_instructions` text DEFAULT NULL,
  `vehicle_year` int(4) DEFAULT NULL,
  `vehicle_make` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(50) DEFAULT NULL,
  `vehicle_body_type` varchar(30) DEFAULT NULL,
  `vehicle_color` varchar(30) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `base_service_price` decimal(10,2) NOT NULL,
  `add_ons_total` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `vat_amount` decimal(10,2) DEFAULT 0.00,
  `promo_discount` decimal(10,2) DEFAULT 0.00,
  `promo_code` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('deposit_50','full_payment') NOT NULL DEFAULT 'deposit_50',
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') DEFAULT NULL,
  `status` enum('pending','confirmed','assigned','in_progress','completed','cancelled','rejected') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `admin_confirmed_by` int(11) DEFAULT NULL,
  `admin_confirmed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `slot_locked_until` timestamp NULL DEFAULT NULL,
  `auto_cancel_after` timestamp NULL DEFAULT NULL,
  `receipt_sent` tinyint(1) DEFAULT 0,
  `confirmation_email_sent` tinyint(1) DEFAULT 0,
  `confirmation_sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_reference`, `user_id`, `service_id`, `vehicle_size`, `vehicle_id`, `add_on_services`, `booking_date`, `booking_time`, `estimated_duration`, `service_address`, `service_lat`, `service_lng`, `travel_fee`, `landmark_instructions`, `vehicle_year`, `vehicle_make`, `vehicle_model`, `vehicle_body_type`, `vehicle_color`, `license_plate`, `special_instructions`, `base_service_price`, `add_ons_total`, `subtotal`, `vat_amount`, `promo_discount`, `promo_code`, `total_amount`, `payment_mode`, `deposit_amount`, `remaining_amount`, `payment_method`, `status`, `payment_status`, `admin_confirmed_by`, `admin_confirmed_at`, `admin_notes`, `rejection_reason`, `slot_locked_until`, `auto_cancel_after`, `receipt_sent`, `confirmation_email_sent`, `confirmation_sms_sent`, `created_at`, `updated_at`) VALUES
(2, 'CD20251015AAABBC', 6, 1, 'medium', NULL, '[]', '2025-10-21', '12:00:00', 120, 'Ezra, Reveal Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.43310091', '120.94355106', '260.20', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1459.20', '175.10', '0.00', NULL, '1634.30', 'full_payment', '1634.30', '0.00', 'bank_transfer', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 08:55:22', '2025-10-15 08:55:22'),
(3, 'CD2025101509333D', 6, 7, 'medium', NULL, '[]', '2025-10-22', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '599.00', '0.00', '875.93', '105.11', '0.00', NULL, '981.04', 'deposit_50', '490.52', '490.52', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 09:24:48', '2025-10-15 09:24:48'),
(4, 'CD20251015660C66', 6, 7, 'medium', NULL, '[]', '2025-10-22', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '599.00', '0.00', '875.93', '105.11', '0.00', NULL, '981.04', 'deposit_50', '490.52', '490.52', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 09:25:42', '2025-10-15 09:25:42'),
(5, 'CD20251015BB3C3B', 6, 1, 'medium', NULL, '[]', '2025-10-21', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'full_payment', '1653.04', '0.00', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 10:22:51', '2025-10-15 10:22:51'),
(6, 'CD20251015C55450', 6, 4, 'large', NULL, '[]', '2025-10-30', '08:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2799.00', '0.00', '3075.93', '369.11', '0.00', NULL, '3445.04', 'full_payment', '3445.04', '0.00', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 10:29:16', '2025-10-15 10:29:16'),
(7, 'CD20251017ACCA51', 6, 1, 'medium', NULL, '[]', '2025-10-23', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'full_payment', '1653.04', '0.00', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 00:50:34', '2025-10-17 00:50:34'),
(8, 'CD2025101788160C', 6, 1, 'medium', NULL, '[]', '2025-10-21', '12:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'deposit_50', '826.52', '826.52', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 00:54:16', '2025-10-17 00:54:16'),
(9, 'CD202510178E5709', 6, 1, 'large', NULL, '[]', '2025-10-21', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1399.00', '0.00', '1675.93', '201.11', '0.00', NULL, '1877.04', 'deposit_50', '938.52', '938.52', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:21:44', '2025-10-28 12:19:00'),
(10, 'CD20251017A729B7', 6, 5, 'large', NULL, '[]', '2025-10-21', '14:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5299.00', '0.00', '5575.93', '669.11', '0.00', NULL, '6245.04', 'full_payment', '6245.04', '0.00', 'maya', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:26:18', '2025-10-17 01:26:31'),
(11, 'CD202510177A47FF', 6, 2, 'medium', NULL, '[]', '2025-10-21', '12:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1699.00', '0.00', '1975.93', '237.11', '0.00', NULL, '2213.04', 'full_payment', '2213.04', '0.00', 'bank_transfer', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:29:59', '2025-10-17 01:31:03'),
(12, 'CD20251017A456CA', 6, 2, 'medium', NULL, '[]', '2025-10-21', '08:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1699.00', '0.00', '1975.93', '237.11', '0.00', NULL, '2213.04', 'deposit_50', '1106.52', '1106.52', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:31:38', '2025-10-28 12:19:00'),
(13, 'CD20251017475166', 6, 1, 'medium', NULL, '[]', '2025-10-30', '14:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'deposit_50', '671.44', '671.44', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 12:48:52', '2025-10-28 12:19:00'),
(14, 'CD202510179275AF', 1, 7, 'large', NULL, '[]', '2025-10-29', '12:00:00', 120, 'Sol P. Bella Street, Poblacion II-A, Imus, Cavite, Calabarzon, 4103, Philippines', '14.43235281', '120.93664248', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '699.00', '0.00', '699.00', '83.88', '0.00', NULL, '782.88', 'full_payment', '782.88', '0.00', 'maya', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 13:25:29', '2025-10-17 13:25:34'),
(15, 'CD20251018F9C810', 1, 1, 'medium', NULL, '[]', '2025-10-18', '11:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'deposit_50', '671.44', '671.44', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-18 03:32:31', '2025-10-28 12:19:00'),
(16, 'CD2025102663676E', 6, 1, 'medium', NULL, '[1,2,3,4,5,6,7]', '2025-10-28', '14:00:00', 120, 'Sampaguita Street, Palico I, Imus, Cavite, Calabarzon, 4103, Philippines', '14.43148003', '120.94389439', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '4643.00', '5842.00', '701.04', '0.00', NULL, '6543.04', 'full_payment', '6543.04', '0.00', 'gcash', 'completed', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-26 02:19:02', '2025-10-28 12:19:00'),
(17, 'CD2025102668329B', 6, 1, 'medium', NULL, '[1,2,3,4,5,6,7]', '2025-10-26', '16:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '4643.00', '5842.00', '701.04', '0.00', NULL, '6543.04', 'full_payment', '6543.04', '0.00', 'maya', '', 'paid', NULL, NULL, 'date not available', NULL, NULL, NULL, 0, 0, 0, '2025-10-26 02:29:10', '2025-10-26 05:23:03'),
(18, 'CD20251026E532FE', 6, 10, 'large', NULL, '[2]', '2025-10-27', '16:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '699.00', '699.00', '1398.00', '167.76', '0.00', NULL, '1565.76', 'full_payment', '1565.76', '0.00', 'gcash', 'completed', 'pending', NULL, NULL, 'Booking confirmed by admin', NULL, NULL, NULL, 0, 0, 0, '2025-10-26 02:55:10', '2025-10-28 12:19:00'),
(19, 'CD20251026F63B11', 6, 5, 'small', NULL, '[1,2,3,4,5,6,7]', '2025-10-26', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '4499.00', '3893.00', '8392.00', '1007.04', '0.00', NULL, '9399.04', 'full_payment', '9399.04', '0.00', 'gcash', 'confirmed', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-26 10:47:27', '2025-10-28 12:19:00'),
(20, 'CD20251026E0FC5C', 1, 1, 'medium', NULL, '[1]', '2025-10-26', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '349.00', '1548.00', '185.76', '0.00', NULL, '1733.76', 'deposit_50', '866.88', '866.88', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-26 12:13:18', '2025-10-28 12:19:00'),
(21, 'CD20251026C8E3BA', 1, 1, 'small', NULL, '[]', '2025-10-26', '08:00:00', 120, 'Jesus Good Shepherd School, Good Shepherd Avenue, Good Shepherd Subdivision, Imus, Cavite, Calabarzon, 4103, Philippines', '14.42930422', '120.94367713', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '999.00', '0.00', '999.00', '119.88', '0.00', NULL, '1118.88', 'full_payment', '1118.88', '0.00', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-26 12:44:12', '2025-10-28 12:19:00'),
(22, 'CD20251026114BC2', 6, 1, 'small', NULL, '[]', '2025-10-26', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '999.00', '0.00', '999.00', '119.88', '0.00', NULL, '1118.88', 'deposit_50', '559.44', '559.44', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-26 12:53:21', '2025-10-28 12:19:00'),
(23, 'CD202510282BAEE5', 6, 1, 'medium', NULL, '[]', '2025-10-28', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:13:54', '2025-10-28 13:13:54'),
(24, 'CD20251028D045C3', 6, 1, 'medium', NULL, '[]', '2025-10-28', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:15:57', '2025-10-28 13:15:57'),
(25, 'CD20251028556A34', 6, 1, 'medium', NULL, '[]', '2025-10-28', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:21:41', '2025-10-28 13:21:41'),
(26, 'CD202510280E7630', 6, 1, 'medium', NULL, '[]', '2025-10-28', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:24:00', '2025-10-28 13:24:00'),
(27, 'CD20251028FBA58D', 6, 1, 'medium', NULL, '[]', '2025-10-28', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:34:55', '2025-10-28 13:34:55'),
(28, 'CD202510287DD769', 6, 1, 'medium', NULL, '[]', '2025-10-28', '16:00:00', 120, 'Lat: 14.456000, Lng: 120.940100', '14.45600000', '120.94010000', '224.71', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1423.71', '170.85', '0.00', NULL, '1594.56', 'full_payment', '1594.56', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-28 13:40:23', '2025-10-28 13:40:23'),
(29, 'CD202510293F0D48', 6, 1, 'large', NULL, '[]', '2025-10-31', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1399.00', '0.00', '1399.00', '167.88', '0.00', NULL, '1566.88', 'full_payment', '1566.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-29 00:13:07', '2025-10-29 00:13:07'),
(30, 'CD2025102953AB09', 6, 1, 'medium', NULL, '[]', '2025-10-29', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'full_payment', '1342.88', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-29 00:13:57', '2025-10-29 00:13:57'),
(31, 'CD202511013ED64F', 6, 1, 'medium', NULL, '[1,2]', '2025-11-01', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '948.00', '2147.00', '257.64', '0.00', NULL, '2404.64', 'deposit_50', '1202.32', '1202.32', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 00:34:43', '2025-11-01 00:34:43'),
(32, 'CD2025110112401E', 6, 1, 'medium', NULL, '[]', '2025-11-01', '08:00:00', 120, 'Lat: 14.456000, Lng: 120.940100', '14.45600000', '120.94010000', '224.71', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1423.71', '170.85', '0.00', NULL, '1594.56', 'full_payment', '1594.56', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 01:02:09', '2025-11-01 01:02:09'),
(33, 'CD20251101B98FC9', 6, 1, 'medium', NULL, '[]', '2025-11-01', '08:00:00', 120, 'Lat: 14.456000, Lng: 120.940100', '14.45600000', '120.94010000', '224.71', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1423.71', '170.85', '0.00', NULL, '1594.56', 'full_payment', '1594.56', '0.00', '', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 01:08:11', '2025-11-01 01:08:11'),
(34, 'CD20251101E0C861', 6, 1, 'large', NULL, '[]', '2025-11-01', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1399.00', '0.00', '1399.00', '167.88', '0.00', NULL, '1566.88', 'full_payment', '1566.88', '0.00', '', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 01:10:38', '2025-11-01 01:23:47'),
(35, 'CD20251101523966', 6, 2, 'medium', NULL, '[]', '2025-11-01', '08:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1699.00', '0.00', '1699.00', '203.88', '0.00', NULL, '1902.88', 'full_payment', '1902.88', '0.00', '', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 01:16:53', '2025-11-01 01:18:41'),
(36, 'CD20251101465D1E', 6, 4, 'medium', NULL, '[]', '2025-11-01', '11:00:00', 120, 'Casimiro Baytown Village, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.45600000', '120.94010000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2499.00', '0.00', '2499.00', '299.88', '0.00', NULL, '2798.88', 'full_payment', '2798.88', '0.00', '', 'confirmed', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 01:20:04', '2025-11-01 01:26:16'),
(39, 'TEST-UNLIMITED-17619', 6, 1, 'medium', NULL, NULL, '2025-11-03', '08:00:00', 120, '', NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '0.00', '0.00', '0.00', '0.00', NULL, '500.00', 'deposit_50', '0.00', '0.00', NULL, 'confirmed', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 03:02:59', '2025-11-01 03:02:59'),
(41, 'TEST-UNLIMITED-69057', 6, 1, 'medium', NULL, NULL, '2025-11-03', '08:00:00', 120, '', NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '0.00', '0.00', '0.00', '0.00', NULL, '500.00', 'deposit_50', '0.00', '0.00', NULL, 'confirmed', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 03:03:51', '2025-11-01 03:03:51'),
(43, 'TEST-UL-202511010404', 6, 1, 'medium', NULL, NULL, '2025-11-03', '08:00:00', 120, '', NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '0.00', '0.00', '0.00', '0.00', NULL, '500.00', 'deposit_50', '0.00', '0.00', NULL, 'confirmed', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-11-01 03:04:24', '2025-11-01 03:04:24');

-- --------------------------------------------------------

--
-- Stand-in structure for view `booking_availability_overview`
-- (See below for the actual view)
--
CREATE TABLE `booking_availability_overview` (
`available_date` date
,`max_bookings` int(11)
,`current_bookings` int(11)
,`available_slots` bigint(12)
,`availability_status` varchar(19)
,`is_holiday` tinyint(1)
,`special_hours` longtext
,`notes` text
);

-- --------------------------------------------------------

--
-- Table structure for table `booking_conflicts`
--

CREATE TABLE `booking_conflicts` (
  `id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `conflict_type` enum('time_overlap','daily_limit','travel_buffer','business_hours') NOT NULL,
  `conflict_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conflict_details`)),
  `attempted_booking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attempted_booking_data`)),
  `existing_booking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`existing_booking_data`)),
  `resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_settings`
--

CREATE TABLE `business_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_settings`
--

INSERT INTO `business_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'base_travel_fee', '50.00', 'number', 'Base travel fee within city', '2025-10-13 11:30:41'),
(2, 'travel_fee_per_km', '15.00', 'number', 'Additional fee per kilometer beyond base range', '2025-10-13 11:30:41'),
(3, 'free_travel_radius_km', '5', 'number', 'Free travel radius in kilometers', '2025-10-13 11:30:41'),
(4, 'max_travel_radius_km', '25', 'number', 'Maximum service radius in kilometers', '2025-10-13 11:30:41'),
(5, 'vat_rate', '0.12', 'number', 'VAT rate (12%)', '2025-10-13 11:30:41'),
(6, 'vat_enabled', 'true', 'boolean', 'Enable VAT calculation', '2025-10-13 11:30:41'),
(7, 'auto_cancel_hours', '48', 'number', 'Auto-cancel pending bookings after hours', '2025-10-13 11:30:41'),
(8, 'slot_lock_minutes', '10', 'number', 'Lock time slot for minutes during booking', '2025-10-13 11:30:41'),
(9, 'booking_advance_days', '30', 'number', 'Maximum days in advance for booking', '2025-10-13 11:30:41'),
(10, 'same_day_booking_cutoff', '10:00:00', 'string', 'Cutoff time for same-day bookings', '2025-10-13 11:30:41'),
(11, 'business_phone', '+63 (2) 123-4567', 'string', 'Business contact phone', '2025-10-13 11:30:41'),
(12, 'business_email', 'info@cardetailing.com', 'string', 'Business contact email', '2025-10-13 11:30:41'),
(13, 'notification_email', 'notifications@cardetailing.com', 'string', 'Email for admin notifications', '2025-10-13 11:30:41');

-- --------------------------------------------------------

--
-- Table structure for table `daily_availability`
--

CREATE TABLE `daily_availability` (
  `id` int(11) NOT NULL,
  `available_date` date NOT NULL,
  `max_bookings` int(11) DEFAULT 2,
  `current_bookings` int(11) DEFAULT 0,
  `is_holiday` tinyint(1) DEFAULT 0,
  `special_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Special business hours for this date' CHECK (json_valid(`special_hours`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_availability`
--

INSERT INTO `daily_availability` (`id`, `available_date`, `max_bookings`, `current_bookings`, `is_holiday`, `special_hours`, `notes`, `created_at`, `updated_at`) VALUES
(1, '2025-10-10', 2, 0, 0, NULL, NULL, '2025-10-10 01:03:57', '2025-10-10 01:03:57'),
(2, '2025-10-11', 2, 0, 0, NULL, NULL, '2025-10-10 01:03:57', '2025-10-10 01:03:57'),
(3, '2025-10-12', 2, 1, 0, NULL, NULL, '2025-10-10 01:03:57', '2025-10-10 01:03:57'),
(4, '2025-10-13', 2, 0, 0, NULL, NULL, '2025-10-10 01:03:57', '2025-10-10 01:03:57'),
(5, '2025-10-30', 2, 4, 0, NULL, NULL, '2025-10-10 01:05:27', '2025-10-10 02:14:54'),
(6, '2025-10-24', 2, 1, 0, NULL, NULL, '2025-10-10 02:08:02', '2025-10-10 02:08:02'),
(10, '2025-11-04', 2, 2, 0, NULL, NULL, '2025-10-10 02:39:54', '2025-10-10 02:51:45');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('booking','payment','system','review','general','confirmation','reminder') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `related_booking_id` int(11) DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `admin_id`, `type`, `title`, `message`, `action_url`, `is_read`, `read_at`, `related_booking_id`, `email_sent`, `sms_sent`, `created_at`) VALUES
(1, 1, NULL, 'system', 'Test notification', 'This is a test.', '/user/dashboard.php', 1, '2025-10-26 20:15:20', NULL, 0, 0, '2025-10-26 03:33:46'),
(2, 6, NULL, '', 'Test Notification', 'This is a test notification inserted by tools/send_test_notification.php', NULL, 1, NULL, NULL, 0, 0, '2025-10-26 03:39:53'),
(3, 6, NULL, 'booking', 'Booking Status Update', 'Your booking #18 has been confirmed. Note: Booking confirmed by admin', NULL, 1, '2025-10-26 15:32:59', 18, 0, 0, '2025-10-26 04:41:03'),
(4, 6, NULL, 'booking', 'Booking Status Update', 'Your booking #17 has been declined. Note: date not available', NULL, 1, '2025-10-26 13:46:20', 17, 0, 0, '2025-10-26 05:23:04'),
(5, 6, NULL, 'booking', 'Booking marked completed', 'Your booking #CD20251026E532FE has been marked as completed. You can now leave a review.', NULL, 1, '2025-10-26 15:30:30', 18, 0, 0, '2025-10-26 07:06:41'),
(10, NULL, NULL, 'payment', 'Payment Received', 'Payment of ₱9,399.04 received for booking CD20251026F63B11.', NULL, 0, NULL, 19, 0, 0, '2025-10-26 10:47:34'),
(11, NULL, NULL, 'payment', 'Payment Received', 'Payment of ₱866.88 received for booking CD20251026E0FC5C.', NULL, 0, NULL, 20, 0, 0, '2025-10-26 12:13:22'),
(13, 6, NULL, 'booking', 'Booking marked completed', 'Your booking #CD2025102663676E has been marked as completed. You can now leave a review.', NULL, 0, NULL, 16, 0, 0, '2025-10-26 12:28:44'),
(14, 6, NULL, 'review', 'Review removed', 'Your review for Basic Exterior Care was removed by an administrator.', NULL, 0, NULL, NULL, 0, 0, '2025-10-26 12:29:36'),
(15, 6, NULL, 'review', 'Review approved', 'Your review for Basic Exterior Care (rating 5) has been approved by our team.', NULL, 0, NULL, NULL, 0, 0, '2025-10-26 12:29:49'),
(16, 6, NULL, '', 'Admin replied to your review', 'An admin replied to your review: ayus ba gawa ko', NULL, 1, '2025-10-26 20:34:17', 2, 0, 0, '2025-10-26 12:30:15'),
(17, 1, NULL, '', 'User replied to a review', 'A user replied: sakto lang g', NULL, 0, NULL, 2, 0, 0, '2025-10-26 12:30:39'),
(18, NULL, NULL, 'payment', 'Payment Received', 'Payment of ₱1,118.88 received for booking CD20251026C8E3BA.', NULL, 0, NULL, 21, 0, 0, '2025-10-26 12:44:21'),
(19, NULL, NULL, 'payment', 'Payment Received', 'Payment of ₱559.44 received for booking CD20251026114BC2.', NULL, 0, NULL, 22, 0, 0, '2025-10-26 12:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('deposit','final','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `refund_status` enum('none','pending','refunded') NOT NULL DEFAULT 'none',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_test` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `refund_status`, `transaction_id`, `gateway_reference`, `gateway_response`, `processed_by`, `payment_date`, `confirmation_date`, `refund_amount`, `refund_date`, `refund_reason`, `notes`, `created_at`, `updated_at`, `is_test`) VALUES
(2, 2, 6, 'full', '1634.30', 'bank_transfer', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 08:55:22', '2025-10-28 12:41:43', 1),
(3, 3, 6, 'deposit', '490.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:24:48', '2025-10-28 12:18:59', 1),
(4, 4, 6, 'deposit', '490.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:25:42', '2025-10-15 09:25:42', 0),
(5, 5, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:22:51', '2025-10-28 12:18:59', 1),
(6, 6, 6, 'full', '3445.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:29:16', '2025-10-15 10:29:16', 0),
(7, 7, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:50:34', '2025-10-28 12:18:59', 1),
(8, 8, 6, 'deposit', '826.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:54:16', '2025-10-17 00:54:16', 0),
(9, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:21:44', '2025-10-28 12:18:59', 1),
(10, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, '8FBD9D80BF', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:22:26', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:22:26', '2025-10-28 12:18:59', 1),
(11, 10, 6, 'full', '6245.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:18', '2025-10-17 01:26:18', 0),
(12, 10, 6, 'full', '6245.04', 'maya', 'completed', 'none', NULL, 'A73C23BC5B', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:26:31', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:31', '2025-10-17 01:26:31', 0),
(13, 11, 6, 'full', '2213.04', 'bank_transfer', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:29:59', '2025-10-28 12:41:43', 1),
(14, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, 'C78503C2D7', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:30:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:24', '2025-10-17 01:30:24', 0),
(15, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '2F7CA56F44', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"TRX123456\"}', NULL, '2025-10-17 01:30:39', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:39', '2025-10-17 01:30:39', 0),
(16, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '06C4054A86', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:31:03', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:03', '2025-10-17 01:31:03', 0),
(17, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:38', '2025-10-28 12:18:59', 1),
(18, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, 'EA3CD10FD1', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:31:45', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:45', '2025-10-28 12:18:59', 1),
(19, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:48:52', '2025-10-28 12:18:59', 1),
(20, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, '427E3E5F87', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 12:49:05', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:49:05', '2025-10-28 12:18:59', 1),
(21, 14, 1, 'full', '782.88', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:29', '2025-10-17 13:25:29', 0),
(22, 14, 1, 'full', '782.88', 'maya', 'completed', 'none', NULL, '26F6460E81', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 13:25:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:34', '2025-10-17 13:25:34', 0),
(23, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:31', '2025-10-28 12:18:59', 1),
(24, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, '3F78AF9152', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-18 03:32:40', NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:40', '2025-10-28 12:18:59', 1),
(25, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:02', '2025-10-28 12:18:59', 1),
(26, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, '20DBE7ACB4', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:19:21', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:21', '2025-10-28 12:18:59', 1),
(27, 17, 6, 'full', '6543.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:10', '2025-10-26 02:29:10', 0),
(28, 17, 6, 'full', '6543.04', 'maya', 'completed', 'none', NULL, 'E19B1344B5', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:29:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:16', '2025-10-26 02:29:16', 0),
(29, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:10', '2025-10-28 12:18:59', 1),
(30, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, '076F786082', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:55:13', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:13', '2025-10-28 12:18:59', 1),
(31, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:27', '2025-10-28 12:18:59', 1),
(32, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, 'C5CC74EEB6', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 10:47:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:34', '2025-10-28 12:18:59', 1),
(33, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:18', '2025-10-28 12:18:59', 1),
(34, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, '4C706912EB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:13:22', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:22', '2025-10-28 12:18:59', 1),
(35, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:12', '2025-10-28 12:18:59', 1),
(36, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '7D14723DBB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:16', '2025-10-28 12:18:59', 1),
(37, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '0179524DEA', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:20', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:20', '2025-10-28 12:18:59', 1),
(38, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:21', '2025-10-28 12:18:59', 1),
(39, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, 'E1E82E5FEE', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:53:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:24', '2025-10-28 12:18:59', 1),
(40, 23, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:13:54', '2025-10-28 13:13:54', 0),
(41, 24, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:15:57', '2025-10-28 13:15:57', 0),
(42, 24, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_d73b89b77f504547', NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:21:10', '2025-10-28 13:21:10', 1),
(43, 25, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:21:41', '2025-10-28 13:21:41', 0),
(44, 25, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_24e91480d3a7c56e', NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:21:41', '2025-10-28 13:21:41', 1),
(45, 25, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_2a9a88cb7410280a', NULL, '{\"data\":{\"id\":\"src_ycoWXMmsZK1kMsCXWdLVrXux\",\"type\":\"source\",\"attributes\":{\"amount\":134288,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_ycoWXMmsZK1kMsCXWdLVrXux\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=25\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=25\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"local_payment_id\":\"45\",\"booking_id\":\"25\",\"transaction_id\":\"local_pm_2a9a88cb7410280a\"},\"created_at\":1761657826,\"updated_at\":1761657826}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:23:45', '2025-10-28 13:23:46', 1),
(46, 26, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:24:00', '2025-10-28 13:24:00', 0),
(47, 26, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_142298a7167c7a2e', NULL, '{\"data\":{\"id\":\"src_fmRwFNu3RrJjkjUj97uVUAWa\",\"type\":\"source\",\"attributes\":{\"amount\":134288,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_fmRwFNu3RrJjkjUj97uVUAWa\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=26\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=26\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"booking_id\":\"26\",\"local_payment_id\":\"47\",\"transaction_id\":\"local_pm_142298a7167c7a2e\"},\"created_at\":1761657842,\"updated_at\":1761657842}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:24:01', '2025-10-28 13:24:01', 1),
(48, 27, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:34:55', '2025-10-28 13:34:55', 0),
(49, 27, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_c37c8db1d3bb2a76', NULL, '{\"data\":{\"id\":\"src_1tbbNyVkW2WwMLKTWPrPDxU5\",\"type\":\"source\",\"attributes\":{\"amount\":134288,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_1tbbNyVkW2WwMLKTWPrPDxU5\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=27\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=27\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"booking_id\":\"27\",\"transaction_id\":\"local_pm_c37c8db1d3bb2a76\",\"local_payment_id\":\"49\"},\"created_at\":1761658496,\"updated_at\":1761658496}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:34:55', '2025-10-28 13:34:56', 1),
(50, 28, 6, 'full', '1594.56', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:40:23', '2025-10-28 13:40:23', 0),
(51, 28, 6, 'deposit', '1594.56', '', 'pending', 'none', 'local_pm_d9d214ceb6d2fa35', NULL, '{\"data\":{\"id\":\"src_3CNk672zDXNV8MxGB5kTCuG3\",\"type\":\"source\",\"attributes\":{\"amount\":159456,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_3CNk672zDXNV8MxGB5kTCuG3\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=28\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=28\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"booking_id\":\"28\",\"transaction_id\":\"local_pm_d9d214ceb6d2fa35\",\"local_payment_id\":\"51\"},\"created_at\":1761658825,\"updated_at\":1761658825}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-28 13:40:23', '2025-10-28 13:40:24', 1),
(52, 29, 6, 'full', '1566.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-29 00:13:07', '2025-10-29 00:13:07', 0),
(53, 29, 6, 'deposit', '1566.88', '', 'pending', 'none', 'local_pm_c8e527cf331bb7dd', NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-29 00:13:08', '2025-10-29 00:13:08', 1),
(54, 30, 6, 'full', '1342.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-29 00:13:57', '2025-10-29 00:13:57', 0),
(55, 30, 6, 'deposit', '1342.88', '', 'pending', 'none', 'local_pm_5d531cd7da1e41d0', NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-29 00:13:57', '2025-10-29 00:13:57', 1),
(56, 31, 6, 'deposit', '1202.32', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 00:34:44', '2025-11-01 00:34:44', 0),
(57, 31, 6, 'deposit', '1202.32', '', 'pending', 'none', 'local_pm_65141958ec80bf6a', NULL, '{\"data\":{\"id\":\"src_zZNx5JvtT1s9EXYzBrdVs8c5\",\"type\":\"source\",\"attributes\":{\"amount\":120232,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_zZNx5JvtT1s9EXYzBrdVs8c5\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=31\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=31\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"transaction_id\":\"local_pm_65141958ec80bf6a\",\"local_payment_id\":\"57\",\"booking_id\":\"31\"},\"created_at\":1761957285,\"updated_at\":1761957285}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 00:34:44', '2025-11-01 00:34:44', 1),
(58, 32, 6, 'full', '1594.56', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:02:09', '2025-11-01 01:02:09', 0),
(59, 32, 6, 'deposit', '1594.56', '', 'pending', 'none', 'local_pm_1828eff831587528', NULL, '{\"data\":{\"id\":\"src_LzU7Es2UnsKbySYVXHQyHUNV\",\"type\":\"source\",\"attributes\":{\"amount\":159456,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_LzU7Es2UnsKbySYVXHQyHUNV\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=32\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=32\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"transaction_id\":\"local_pm_1828eff831587528\",\"local_payment_id\":\"59\",\"booking_id\":\"32\"},\"created_at\":1761958938,\"updated_at\":1761958938}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:02:09', '2025-11-01 01:02:17', 1),
(60, 33, 6, 'full', '1594.56', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:08:11', '2025-11-01 01:08:11', 0),
(61, 33, 6, 'deposit', '1594.56', '', 'pending', 'none', 'local_pm_3e82b4673f9767ec', NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:08:11', '2025-11-01 01:08:11', 1),
(62, 34, 6, 'full', '1566.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:10:38', '2025-11-01 01:10:38', 0),
(63, 34, 6, 'deposit', '1566.88', '', 'completed', 'none', 'local_pm_3d17c05e382980ff', NULL, '{\"data\":{\"id\":\"src_5fQe7GZJsvnwnh21oD2oLDGT\",\"type\":\"source\",\"attributes\":{\"amount\":156688,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_5fQe7GZJsvnwnh21oD2oLDGT\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=34\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=34\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"transaction_id\":\"local_pm_3d17c05e382980ff\",\"booking_id\":\"34\",\"local_payment_id\":\"63\"},\"created_at\":1761959439,\"updated_at\":1761959439}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:10:38', '2025-11-01 01:23:47', 0),
(64, 35, 6, 'full', '1902.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:16:53', '2025-11-01 01:16:53', 0),
(65, 35, 6, 'deposit', '1902.88', '', 'completed', 'none', 'local_pm_bd48dd2d173bc7c5', NULL, '{\"data\":{\"id\":\"src_CkGCWgLMPpDrn6dHWCf51BZg\",\"type\":\"source\",\"attributes\":{\"amount\":190288,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_CkGCWgLMPpDrn6dHWCf51BZg\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=35\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=35\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"booking_id\":\"35\",\"transaction_id\":\"local_pm_bd48dd2d173bc7c5\",\"local_payment_id\":\"65\"},\"created_at\":1761959815,\"updated_at\":1761959815}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:16:53', '2025-11-01 01:18:41', 0),
(66, 36, 6, 'full', '2798.88', '', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:20:04', '2025-11-01 01:20:04', 0),
(67, 36, 6, 'deposit', '2798.88', '', 'completed', 'none', 'local_pm_8cee6b3567727a41', NULL, '{\"data\":{\"id\":\"src_4xQEjvpsp5YmGPgvVd96pNBc\",\"type\":\"source\",\"attributes\":{\"amount\":279888,\"billing\":null,\"currency\":\"PHP\",\"description\":null,\"livemode\":false,\"redirect\":{\"checkout_url\":\"https:\\/\\/secure-authentication.paymongo.com\\/sources?id=src_4xQEjvpsp5YmGPgvVd96pNBc\",\"failed\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_failed.php?booking_id=36\",\"success\":\"http:\\/\\/127.0.0.1\\/car-detailing\\/user\\/pay_success.php?booking_id=36\"},\"statement_descriptor\":null,\"status\":\"pending\",\"type\":\"gcash\",\"metadata\":{\"booking_id\":\"36\",\"local_payment_id\":\"67\",\"transaction_id\":\"local_pm_8cee6b3567727a41\"},\"created_at\":1761960006,\"updated_at\":1761960006}}}', NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-11-01 01:20:04', '2025-11-01 01:20:07', 0);

-- --------------------------------------------------------

--
-- Table structure for table `payments_backup_20251028`
--

CREATE TABLE `payments_backup_20251028` (
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('deposit','final','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `refund_status` enum('none','pending','refunded') NOT NULL DEFAULT 'none',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments_backup_20251028`
--

INSERT INTO `payments_backup_20251028` (`payment_id`, `booking_id`, `user_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `refund_status`, `transaction_id`, `gateway_reference`, `gateway_response`, `processed_by`, `payment_date`, `confirmation_date`, `refund_amount`, `refund_date`, `refund_reason`, `notes`, `created_at`, `updated_at`) VALUES
(2, 2, 6, 'full', '1634.30', 'bank_transfer', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 08:55:22', '2025-10-15 08:55:22'),
(3, 3, 6, 'deposit', '490.52', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:24:48', '2025-10-15 09:24:48'),
(4, 4, 6, 'deposit', '490.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:25:42', '2025-10-15 09:25:42'),
(5, 5, 6, 'full', '1653.04', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:22:51', '2025-10-15 10:22:51'),
(6, 6, 6, 'full', '3445.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:29:16', '2025-10-15 10:29:16'),
(7, 7, 6, 'full', '1653.04', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:50:34', '2025-10-17 00:50:34'),
(8, 8, 6, 'deposit', '826.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:54:16', '2025-10-17 00:54:16'),
(9, 9, 6, 'deposit', '938.52', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:21:44', '2025-10-17 01:21:44'),
(10, 9, 6, 'deposit', '938.52', 'gcash', 'completed', 'none', NULL, '8FBD9D80BF', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:22:26', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:22:26', '2025-10-17 01:22:26'),
(11, 10, 6, 'full', '6245.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:18', '2025-10-17 01:26:18'),
(12, 10, 6, 'full', '6245.04', 'maya', 'completed', 'none', NULL, 'A73C23BC5B', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:26:31', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:31', '2025-10-17 01:26:31'),
(13, 11, 6, 'full', '2213.04', 'bank_transfer', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:29:59', '2025-10-17 01:29:59'),
(14, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, 'C78503C2D7', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:30:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:24', '2025-10-17 01:30:24'),
(15, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '2F7CA56F44', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"TRX123456\"}', NULL, '2025-10-17 01:30:39', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:39', '2025-10-17 01:30:39'),
(16, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '06C4054A86', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:31:03', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:03', '2025-10-17 01:31:03'),
(17, 12, 6, 'deposit', '1106.52', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:38', '2025-10-17 01:31:38'),
(18, 12, 6, 'deposit', '1106.52', 'gcash', 'completed', 'none', NULL, 'EA3CD10FD1', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:31:45', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:45', '2025-10-17 01:31:45'),
(19, 13, 6, 'deposit', '671.44', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:48:52', '2025-10-17 12:48:52'),
(20, 13, 6, 'deposit', '671.44', 'gcash', 'completed', 'none', NULL, '427E3E5F87', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 12:49:05', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:49:05', '2025-10-17 12:49:05'),
(21, 14, 1, 'full', '782.88', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:29', '2025-10-17 13:25:29'),
(22, 14, 1, 'full', '782.88', 'maya', 'completed', 'none', NULL, '26F6460E81', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 13:25:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:34', '2025-10-17 13:25:34'),
(23, 15, 1, 'deposit', '671.44', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:31', '2025-10-18 03:32:31'),
(24, 15, 1, 'deposit', '671.44', 'gcash', 'completed', 'none', NULL, '3F78AF9152', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-18 03:32:40', NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:40', '2025-10-18 03:32:40'),
(25, 16, 6, 'full', '6543.04', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:02', '2025-10-26 02:19:02'),
(26, 16, 6, 'full', '6543.04', 'gcash', 'completed', 'none', NULL, '20DBE7ACB4', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:19:21', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:21', '2025-10-26 02:19:21'),
(27, 17, 6, 'full', '6543.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:10', '2025-10-26 02:29:10'),
(28, 17, 6, 'full', '6543.04', 'maya', 'completed', 'none', NULL, 'E19B1344B5', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:29:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:16', '2025-10-26 02:29:16'),
(29, 18, 6, 'full', '1565.76', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:10', '2025-10-26 02:55:10'),
(30, 18, 6, 'full', '1565.76', 'gcash', 'completed', 'none', NULL, '076F786082', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:55:13', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:13', '2025-10-26 02:55:13'),
(31, 19, 6, 'full', '9399.04', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:27', '2025-10-26 10:47:27'),
(32, 19, 6, 'full', '9399.04', 'gcash', 'completed', 'none', NULL, 'C5CC74EEB6', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 10:47:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:34', '2025-10-26 10:47:34'),
(33, 20, 1, 'deposit', '866.88', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:18', '2025-10-26 12:13:18'),
(34, 20, 1, 'deposit', '866.88', 'gcash', 'completed', 'none', NULL, '4C706912EB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:13:22', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:22', '2025-10-26 12:13:22'),
(35, 21, 1, 'full', '1118.88', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:12', '2025-10-26 12:44:12'),
(36, 21, 1, 'full', '1118.88', 'gcash', 'completed', 'none', NULL, '7D14723DBB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:16', '2025-10-26 12:44:16'),
(37, 21, 1, 'full', '1118.88', 'gcash', 'completed', 'none', NULL, '0179524DEA', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:20', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:20', '2025-10-26 12:44:20'),
(38, 22, 6, 'deposit', '559.44', 'gcash', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:21', '2025-10-26 12:53:21'),
(39, 22, 6, 'deposit', '559.44', 'gcash', 'completed', 'none', NULL, 'E1E82E5FEE', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:53:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:24', '2025-10-26 12:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `payments_backup_20251028_204143`
--

CREATE TABLE `payments_backup_20251028_204143` (
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('deposit','final','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `refund_status` enum('none','pending','refunded') NOT NULL DEFAULT 'none',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_test` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments_backup_20251028_204143`
--

INSERT INTO `payments_backup_20251028_204143` (`payment_id`, `booking_id`, `user_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `refund_status`, `transaction_id`, `gateway_reference`, `gateway_response`, `processed_by`, `payment_date`, `confirmation_date`, `refund_amount`, `refund_date`, `refund_reason`, `notes`, `created_at`, `updated_at`, `is_test`) VALUES
(2, 2, 6, 'full', '1634.30', 'bank_transfer', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 08:55:22', '2025-10-15 08:55:22', 0),
(3, 3, 6, 'deposit', '490.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:24:48', '2025-10-28 12:18:59', 1),
(4, 4, 6, 'deposit', '490.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:25:42', '2025-10-15 09:25:42', 0),
(5, 5, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:22:51', '2025-10-28 12:18:59', 1),
(6, 6, 6, 'full', '3445.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:29:16', '2025-10-15 10:29:16', 0),
(7, 7, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:50:34', '2025-10-28 12:18:59', 1),
(8, 8, 6, 'deposit', '826.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:54:16', '2025-10-17 00:54:16', 0),
(9, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:21:44', '2025-10-28 12:18:59', 1),
(10, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, '8FBD9D80BF', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:22:26', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:22:26', '2025-10-28 12:18:59', 1),
(11, 10, 6, 'full', '6245.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:18', '2025-10-17 01:26:18', 0),
(12, 10, 6, 'full', '6245.04', 'maya', 'completed', 'none', NULL, 'A73C23BC5B', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:26:31', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:31', '2025-10-17 01:26:31', 0),
(13, 11, 6, 'full', '2213.04', 'bank_transfer', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:29:59', '2025-10-17 01:29:59', 0),
(14, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, 'C78503C2D7', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:30:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:24', '2025-10-17 01:30:24', 0),
(15, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '2F7CA56F44', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"TRX123456\"}', NULL, '2025-10-17 01:30:39', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:39', '2025-10-17 01:30:39', 0),
(16, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '06C4054A86', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:31:03', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:03', '2025-10-17 01:31:03', 0),
(17, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:38', '2025-10-28 12:18:59', 1),
(18, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, 'EA3CD10FD1', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:31:45', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:45', '2025-10-28 12:18:59', 1),
(19, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:48:52', '2025-10-28 12:18:59', 1),
(20, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, '427E3E5F87', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 12:49:05', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:49:05', '2025-10-28 12:18:59', 1),
(21, 14, 1, 'full', '782.88', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:29', '2025-10-17 13:25:29', 0),
(22, 14, 1, 'full', '782.88', 'maya', 'completed', 'none', NULL, '26F6460E81', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 13:25:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:34', '2025-10-17 13:25:34', 0),
(23, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:31', '2025-10-28 12:18:59', 1),
(24, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, '3F78AF9152', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-18 03:32:40', NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:40', '2025-10-28 12:18:59', 1),
(25, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:02', '2025-10-28 12:18:59', 1),
(26, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, '20DBE7ACB4', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:19:21', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:21', '2025-10-28 12:18:59', 1),
(27, 17, 6, 'full', '6543.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:10', '2025-10-26 02:29:10', 0),
(28, 17, 6, 'full', '6543.04', 'maya', 'completed', 'none', NULL, 'E19B1344B5', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:29:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:16', '2025-10-26 02:29:16', 0),
(29, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:10', '2025-10-28 12:18:59', 1),
(30, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, '076F786082', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:55:13', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:13', '2025-10-28 12:18:59', 1),
(31, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:27', '2025-10-28 12:18:59', 1),
(32, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, 'C5CC74EEB6', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 10:47:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:34', '2025-10-28 12:18:59', 1),
(33, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:18', '2025-10-28 12:18:59', 1),
(34, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, '4C706912EB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:13:22', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:22', '2025-10-28 12:18:59', 1),
(35, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:12', '2025-10-28 12:18:59', 1),
(36, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '7D14723DBB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:16', '2025-10-28 12:18:59', 1),
(37, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '0179524DEA', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:20', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:20', '2025-10-28 12:18:59', 1),
(38, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:21', '2025-10-28 12:18:59', 1),
(39, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, 'E1E82E5FEE', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:53:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:24', '2025-10-28 12:18:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments_backup_20251028_204155`
--

CREATE TABLE `payments_backup_20251028_204155` (
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('deposit','final','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  `refund_status` enum('none','pending','refunded') NOT NULL DEFAULT 'none',
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_date` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` timestamp NULL DEFAULT NULL,
  `refund_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_test` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments_backup_20251028_204155`
--

INSERT INTO `payments_backup_20251028_204155` (`payment_id`, `booking_id`, `user_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `refund_status`, `transaction_id`, `gateway_reference`, `gateway_response`, `processed_by`, `payment_date`, `confirmation_date`, `refund_amount`, `refund_date`, `refund_reason`, `notes`, `created_at`, `updated_at`, `is_test`) VALUES
(2, 2, 6, 'full', '1634.30', 'bank_transfer', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 08:55:22', '2025-10-28 12:41:43', 1),
(3, 3, 6, 'deposit', '490.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:24:48', '2025-10-28 12:18:59', 1),
(4, 4, 6, 'deposit', '490.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:25:42', '2025-10-15 09:25:42', 0),
(5, 5, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:22:51', '2025-10-28 12:18:59', 1),
(6, 6, 6, 'full', '3445.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:29:16', '2025-10-15 10:29:16', 0),
(7, 7, 6, 'full', '1653.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:50:34', '2025-10-28 12:18:59', 1),
(8, 8, 6, 'deposit', '826.52', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:54:16', '2025-10-17 00:54:16', 0),
(9, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:21:44', '2025-10-28 12:18:59', 1),
(10, 9, 6, 'deposit', '938.52', 'gcash', '', 'none', NULL, '8FBD9D80BF', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:22:26', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:22:26', '2025-10-28 12:18:59', 1),
(11, 10, 6, 'full', '6245.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:18', '2025-10-17 01:26:18', 0),
(12, 10, 6, 'full', '6245.04', 'maya', 'completed', 'none', NULL, 'A73C23BC5B', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:26:31', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:31', '2025-10-17 01:26:31', 0),
(13, 11, 6, 'full', '2213.04', 'bank_transfer', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:29:59', '2025-10-28 12:41:43', 1),
(14, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, 'C78503C2D7', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:30:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:24', '2025-10-17 01:30:24', 0),
(15, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '2F7CA56F44', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"TRX123456\"}', NULL, '2025-10-17 01:30:39', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:39', '2025-10-17 01:30:39', 0),
(16, 11, 6, 'full', '2213.04', '', 'completed', 'none', NULL, '06C4054A86', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:31:03', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:03', '2025-10-17 01:31:03', 0),
(17, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:38', '2025-10-28 12:18:59', 1),
(18, 12, 6, 'deposit', '1106.52', 'gcash', '', 'none', NULL, 'EA3CD10FD1', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:31:45', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:45', '2025-10-28 12:18:59', 1),
(19, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:48:52', '2025-10-28 12:18:59', 1),
(20, 13, 6, 'deposit', '671.44', 'gcash', '', 'none', NULL, '427E3E5F87', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 12:49:05', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:49:05', '2025-10-28 12:18:59', 1),
(21, 14, 1, 'full', '782.88', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:29', '2025-10-17 13:25:29', 0),
(22, 14, 1, 'full', '782.88', 'maya', 'completed', 'none', NULL, '26F6460E81', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 13:25:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:34', '2025-10-17 13:25:34', 0),
(23, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:31', '2025-10-28 12:18:59', 1),
(24, 15, 1, 'deposit', '671.44', 'gcash', '', 'none', NULL, '3F78AF9152', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-18 03:32:40', NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:40', '2025-10-28 12:18:59', 1),
(25, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:02', '2025-10-28 12:18:59', 1),
(26, 16, 6, 'full', '6543.04', 'gcash', '', 'none', NULL, '20DBE7ACB4', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:19:21', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:19:21', '2025-10-28 12:18:59', 1),
(27, 17, 6, 'full', '6543.04', 'maya', 'pending', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:10', '2025-10-26 02:29:10', 0),
(28, 17, 6, 'full', '6543.04', 'maya', 'completed', 'none', NULL, 'E19B1344B5', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:29:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:29:16', '2025-10-26 02:29:16', 0),
(29, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:10', '2025-10-28 12:18:59', 1),
(30, 18, 6, 'full', '1565.76', 'gcash', '', 'none', NULL, '076F786082', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 02:55:13', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 02:55:13', '2025-10-28 12:18:59', 1),
(31, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:27', '2025-10-28 12:18:59', 1),
(32, 19, 6, 'full', '9399.04', 'gcash', '', 'none', NULL, 'C5CC74EEB6', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 10:47:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 10:47:34', '2025-10-28 12:18:59', 1),
(33, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:18', '2025-10-28 12:18:59', 1),
(34, 20, 1, 'deposit', '866.88', 'gcash', '', 'none', NULL, '4C706912EB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:13:22', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:13:22', '2025-10-28 12:18:59', 1),
(35, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:12', '2025-10-28 12:18:59', 1),
(36, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '7D14723DBB', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:16', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:16', '2025-10-28 12:18:59', 1),
(37, 21, 1, 'full', '1118.88', 'gcash', '', 'none', NULL, '0179524DEA', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:44:20', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:44:20', '2025-10-28 12:18:59', 1),
(38, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:21', '2025-10-28 12:18:59', 1),
(39, 22, 6, 'deposit', '559.44', 'gcash', '', 'none', NULL, 'E1E82E5FEE', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-26 12:53:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-26 12:53:24', '2025-10-28 12:18:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_logs`
--

CREATE TABLE `payment_logs` (
  `log_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `action` enum('created','verified','rejected','updated') NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_logs`
--

INSERT INTO `payment_logs` (`log_id`, `payment_id`, `action`, `performed_by`, `details`, `created_at`) VALUES
(1, 1, 'created', NULL, 'Payment created', '2025-10-09 04:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `promo_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`promo_id`, `code`, `description`, `discount_type`, `discount_value`, `min_amount`, `max_discount`, `usage_limit`, `used_count`, `valid_from`, `valid_until`, `is_active`, `created_at`) VALUES
(1, 'FIRST20', 'First-time customer 20% discount', 'percentage', '20.00', '1000.00', '500.00', 100, 0, '2025-10-13 11:30:41', '2026-10-13 11:30:41', 1, '2025-10-13 11:30:41'),
(2, 'SAVE100', 'Save ₱100 on orders over ₱1500', 'fixed', '100.00', '1500.00', NULL, 50, 0, '2025-10-13 11:30:41', '2026-04-13 11:30:41', 1, '2025-10-13 11:30:41'),
(3, 'PREMIUM15', '15% off Premium services', 'percentage', '15.00', '2000.00', '750.00', 200, 0, '2025-10-13 11:30:41', '2026-10-13 11:30:41', 1, '2025-10-13 11:30:41'),
(4, 'WELCOME20', '20% off for new customers', 'percentage', '20.00', '500.00', '999999.00', 100, 0, '2025-10-15 09:18:02', '2025-12-31 15:59:59', 1, '2025-10-15 09:18:02'),
(5, 'LOYAL50', '50% off for loyal customers', 'percentage', '50.00', '800.00', '999999.00', 20, 0, '2025-10-15 09:18:02', '2025-11-30 15:59:59', 1, '2025-10-15 09:18:02'),
(6, 'CLEAN15', '15% off any service', 'percentage', '15.00', '300.00', '999999.00', NULL, 0, '2025-10-15 09:18:02', '2025-12-31 15:59:59', 1, '2025-10-15 09:18:02'),
(7, 'FIRST200', '₱200 off first booking', 'fixed', '200.00', '1500.00', '200.00', 30, 0, '2025-10-15 09:18:02', '2025-12-31 15:59:59', 1, '2025-10-15 09:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `user_id`, `service_id`, `rating`, `review_text`, `is_approved`, `admin_response`, `created_at`) VALUES
(1, 18, 6, 10, 5, 'service was really great! quick and accurate', 1, NULL, '2025-10-26 07:10:41'),
(2, 16, 6, 1, 5, NULL, 1, NULL, '2025-10-26 12:29:15');

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `reply_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `sender_role` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_replies`
--

INSERT INTO `review_replies` (`reply_id`, `review_id`, `sender_role`, `message`, `created_at`) VALUES
(1, 1, 'admin', 'this is a test only: thank you i appreciate it !', '2025-10-26 07:51:41'),
(2, 1, 'admin', 'try', '2025-10-26 07:58:01'),
(3, 1, 'admin', 'paldo ba ako o hindi?', '2025-10-26 08:05:37'),
(4, 1, 'user', 'paldo ka ya', '2025-10-26 08:14:25'),
(5, 1, 'admin', 'yun oh ty', '2025-10-26 12:27:02'),
(6, 2, 'admin', 'ayus ba gawa ko', '2025-10-26 12:30:15'),
(7, 2, 'user', 'sakto lang g', '2025-10-26 12:30:39');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_small` decimal(10,2) NOT NULL,
  `price_medium` decimal(10,2) NOT NULL,
  `price_large` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `included_items` text DEFAULT NULL,
  `free_items` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `category`, `service_name`, `description`, `price_small`, `price_medium`, `price_large`, `duration_minutes`, `included_items`, `free_items`, `is_active`, `created_at`) VALUES
(1, 'Basic Package', 'Basic Exterior Care', 'Premium car shampoo, foam care, tire dressing', '999.00', '1199.00', '1399.00', 90, 'Premium car shampoo, foam care, tire dressing', 'Acid Rain Removal', 1, '2025-10-09 03:05:18'),
(2, 'Basic Package', 'Express Care + Wax', 'Basic Exterior Care plus Graphene Ceramic Wax (up to 6 months protection)', '1499.00', '1699.00', '1899.00', 120, 'Basic Exterior Care plus Graphene Ceramic Wax (up to 6 months protection)', 'Quick Glass Polish', 1, '2025-10-09 03:05:18'),
(3, 'Premium Detailing', 'Full Exterior Detailing', 'Deep exterior wash, clay bar treatment, acid rain removal, graphene ceramic wax', '2499.00', '2799.00', '3099.00', 180, 'Deep exterior wash, clay bar treatment, acid rain removal, graphene ceramic wax', 'Paint Decontamination', 1, '2025-10-09 03:05:18'),
(4, 'Premium Detailing', 'Interior Deep Clean', 'Vacuuming, shampooing seats and carpets, dashboard and panel conditioning', '2299.00', '2499.00', '2799.00', 150, 'Vacuuming, shampooing seats and carpets, dashboard and panel conditioning', 'Basic Car Exterior Shampooing', 1, '2025-10-09 03:05:18'),
(5, 'Premium Detailing', 'Platinum Package (Full Interior + Exterior Detail)', 'Combination of Full Exterior and Interior Deep Clean, seat shampooing, mat lining and matting included', '4499.00', '4899.00', '5299.00', 300, 'Combination of Full Exterior and Interior Deep Clean, seat shampooing, mat lining and matting included', 'Tire Black and Odor Neutralizer', 1, '2025-10-09 03:05:18'),
(6, 'Add-On Service', 'Headlight Oxidation Removal', 'Professional headlight restoration and clarity enhancement', '299.00', '349.00', '399.00', 45, 'Headlight oxidation removal and restoration', NULL, 1, '2025-10-09 03:05:18'),
(7, 'Add-On Service', 'Engine Bay Cleaning', 'Thorough engine compartment cleaning and detailing', '499.00', '599.00', '699.00', 60, 'Complete engine bay cleaning and detailing', NULL, 1, '2025-10-09 03:05:18'),
(8, 'Add-On Service', 'Watermark and Acid Rain Removal (Full)', 'Complete water spot and acid rain damage removal', '699.00', '799.00', '899.00', 90, 'Full watermark and acid rain removal treatment', NULL, 1, '2025-10-09 03:05:18'),
(9, 'Add-On Service', 'Upholstery or Leather Treatment', 'Professional upholstery cleaning and leather conditioning', '699.00', '899.00', '1099.00', 90, 'Upholstery cleaning or leather treatment and conditioning', NULL, 1, '2025-10-09 03:05:18'),
(10, 'Add-On Service', 'Glass Polishing', 'Professional glass polishing for crystal clear visibility', '499.00', '599.00', '699.00', 60, 'Complete glass polishing service', NULL, 1, '2025-10-09 03:05:18'),
(11, 'Add-On Service', 'Pet Hair Removal', 'Specialized pet hair removal from vehicle interior', '299.00', '399.00', '499.00', 45, 'Professional pet hair removal service', NULL, 1, '2025-10-09 03:05:18'),
(12, 'Add-On Service', 'Odor Elimination (Ozone Treatment)', 'Advanced ozone treatment for odor elimination', '899.00', '999.00', '1199.00', 120, 'Professional ozone odor elimination treatment', NULL, 1, '2025-10-09 03:05:18'),
(13, 'Add-On Service', 'Ceramic Coating (1-year Protection)', 'Premium ceramic coating with 1-year protection guarantee', '4999.00', '5999.00', '6999.00', 240, '1-year ceramic coating protection with professional application', NULL, 1, '2025-10-09 03:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `slot_id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_bookings` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `days_of_week` varchar(20) DEFAULT '1,2,3,4,5,6,7'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`slot_id`, `start_time`, `end_time`, `max_bookings`, `is_active`, `days_of_week`) VALUES
(1, '08:00:00', '10:00:00', 2, 1, '1,2,3,4,5,6'),
(2, '10:00:00', '12:00:00', 2, 1, '1,2,3,4,5,6'),
(3, '12:00:00', '14:00:00', 2, 1, '1,2,3,4,5,6'),
(4, '14:00:00', '16:00:00', 2, 1, '1,2,3,4,5,6'),
(5, '16:00:00', '18:00:00', 2, 1, '1,2,3,4,5,6'),
(6, '08:00:00', '11:00:00', 1, 1, '7'),
(7, '11:00:00', '14:00:00', 1, 1, '7'),
(8, '14:00:00', '17:00:00', 1, 1, '7');

-- --------------------------------------------------------

--
-- Stand-in structure for view `todays_booking_schedule`
-- (See below for the actual view)
--
CREATE TABLE `todays_booking_schedule` (
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `google_id`, `username`, `email`, `wallet_balance`, `password`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `updated_at`, `is_active`, `reset_token`, `reset_expires`, `address`, `date_of_birth`, `profile_picture`, `email_verified`, `last_login`) VALUES
(1, NULL, 'admin', 'admin@cardetailing.com', '0.00', '$2y$10$Pg7ct9QDRq44MqYJIDrQ..rLWb16AR2.S5DFK2pXkrP0AJHQ3fNg6', 'Admin', 'User', NULL, 'admin', '2025-09-24 09:42:46', '2025-09-25 07:57:23', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, NULL, 'jhonny', 'jhonmichaelsabado123@gmail.com', '0.00', '$2y$10$gJ8q6rlvxIzgRdUJ.vIiHux1qeOEhTQmakMxGwm6R7HxBfyFbdtQS', 'jhon', 'sabado', '09947064818', 'user', '2025-09-24 09:53:28', '2025-09-30 09:39:11', 1, '028e3bf52873fdfca1f90f1c7903cd4d8ce19c5144f8f2b093adb3d44ff4f73b', '2025-10-01 17:39:11', NULL, NULL, NULL, 0, NULL),
(6, NULL, 'sabadoggs', 'enjiqt@gmail.com', '0.00', '$2y$10$vVzF/zTMs7/UoQg.iJR2FOcd/PPmFzsj.KTzmPY.r/tPD/wvCb/se', 'makol', 'sabado', '999485947', 'user', '2025-09-25 08:02:18', '2025-09-26 04:32:33', 1, '94f3788b670c5a27e349df70f0798aee3db72306dcf54ca0a8eb90a7c85942ad', '2025-09-26 07:32:33', NULL, NULL, NULL, 0, NULL),
(7, '108340474821680541139', 'psychomobpsycho', 'psychomobpsycho@gmail.com', '0.00', '', 'Toshinori', 'oshinori', NULL, 'user', '2025-09-26 02:29:33', '2025-09-26 02:29:33', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(8, '111598267480119535036', 'ccamtest1231', 'ccamtest1231@gmail.com', '0.00', '', 'jhon', 'michael salas sabado', NULL, 'user', '2025-09-26 02:40:24', '2025-09-26 02:40:24', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(9, NULL, 'ccamtest12', 'ccamtest12@gmail.com', '0.00', '$2y$10$7kPt9l54e.6CAd1TDqjUPOMTtIWmxAUSVB4a88fx.RhgGo2SpPwWK', 'ccamtesting', '', '', 'user', '2025-09-26 04:45:22', '2025-09-26 04:45:22', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(10, NULL, 'johndoetest', 'john.doe.test@example.com', '0.00', '$2y$10$mldy.u/4Wb/QvC6WOKXpHuWG5OBZPxak6oFIwppA9Jgex8MGkTlLW', 'John', 'Doe', '+1234567890', 'user', '2025-09-30 07:34:08', '2025-09-30 07:34:08', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(11, '104311345767607948687', 'jhonmichael.sabado', 'jhonmichael.sabado@cvsu.edu.ph', '0.00', '', 'Jhon Michael', 'Sabado', NULL, 'user', '2025-10-07 09:01:03', '2025-10-07 09:01:03', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_make` varchar(100) NOT NULL COMMENT 'e.g., Toyota, Honda, Ford',
  `vehicle_model` varchar(100) NOT NULL COMMENT 'e.g., Camry, Civic, F-150',
  `vehicle_year` year(4) NOT NULL COMMENT 'Year of manufacture',
  `vehicle_color` varchar(50) DEFAULT NULL COMMENT 'Vehicle color',
  `license_plate` varchar(50) NOT NULL COMMENT 'License plate number',
  `vehicle_type` enum('sedan','suv','truck','van','hatchback','coupe','convertible','wagon','other') DEFAULT 'sedan',
  `vehicle_size` enum('small','medium','large') DEFAULT 'medium' COMMENT 'Used for pricing',
  `vin_number` varchar(17) DEFAULT NULL COMMENT 'Vehicle Identification Number (optional)',
  `is_default` tinyint(1) DEFAULT 0 COMMENT '1 if this is the default vehicle for the user',
  `notes` text DEFAULT NULL COMMENT 'Additional notes about the vehicle',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores user vehicles for reusable vehicle management';

--
-- Dumping data for table `user_vehicles`
--

INSERT INTO `user_vehicles` (`vehicle_id`, `user_id`, `vehicle_make`, `vehicle_model`, `vehicle_year`, `vehicle_color`, `license_plate`, `vehicle_type`, `vehicle_size`, `vin_number`, `is_default`, `notes`, `created_at`, `updated_at`) VALUES
(6, 2, 'Mazda', 'MX-5 Miata', 0000, 'White', 'ABD 1234', 'convertible', 'small', '', 0, '', '2025-11-06 08:06:52', '2025-11-06 08:06:52');

--
-- Triggers `user_vehicles`
--
DELIMITER $$
CREATE TRIGGER `trg_user_vehicles_default_check` BEFORE INSERT ON `user_vehicles` FOR EACH ROW BEGIN
    IF NEW.is_default = 1 THEN
        UPDATE user_vehicles 
        SET is_default = 0 
        WHERE user_id = NEW.user_id AND vehicle_id != NEW.vehicle_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_user_vehicles_default_update` BEFORE UPDATE ON `user_vehicles` FOR EACH ROW BEGIN
    IF NEW.is_default = 1 AND OLD.is_default = 0 THEN
        UPDATE user_vehicles 
        SET is_default = 0 
        WHERE user_id = NEW.user_id AND vehicle_id != NEW.vehicle_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_user_vehicles_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_user_vehicles_summary` (
`vehicle_id` int(11)
,`user_id` int(11)
,`owner_name` varchar(101)
,`vehicle_full_name` varchar(206)
,`vehicle_make` varchar(100)
,`vehicle_model` varchar(100)
,`vehicle_year` year(4)
,`vehicle_color` varchar(50)
,`license_plate` varchar(50)
,`vehicle_type` enum('sedan','suv','truck','van','hatchback','coupe','convertible','wagon','other')
,`vehicle_size` enum('small','medium','large')
,`is_default` tinyint(1)
,`total_bookings` bigint(21)
,`last_booking_date` date
,`added_date` timestamp
,`last_updated` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `admin_payment_verification`
--
DROP TABLE IF EXISTS `admin_payment_verification`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_payment_verification`  AS SELECT `p`.`payment_id` AS `payment_id`, `p`.`booking_id` AS `booking_id`, `b`.`booking_date` AS `booking_date`, `u`.`username` AS `username`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `s`.`service_name` AS `service_name`, `p`.`amount` AS `amount`, `p`.`payment_method` AS `payment_method`, `p`.`payment_type` AS `payment_type`, `p`.`transaction_id` AS `transaction_id`, `p`.`payment_proof_path` AS `payment_proof_path`, `p`.`verification_status` AS `verification_status`, `p`.`payment_date` AS `payment_date`, CASE WHEN `p`.`payment_proof_path` is not null THEN 'Yes' ELSE 'No' END AS `has_proof` FROM (((`payments` `p` join `bookings` `b` on(`p`.`booking_id` = `b`.`booking_id`)) join `users` `u` on(`p`.`user_id` = `u`.`id`)) join `services` `s` on(`b`.`service_id` = `s`.`service_id`)) WHERE `p`.`verification_status` = 'pending' ORDER BY `p`.`payment_date` AS `DESCdesc` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `booking_availability_overview`
--
DROP TABLE IF EXISTS `booking_availability_overview`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `booking_availability_overview`  AS SELECT `da`.`available_date` AS `available_date`, `da`.`max_bookings` AS `max_bookings`, `da`.`current_bookings` AS `current_bookings`, `da`.`max_bookings`- `da`.`current_bookings` AS `available_slots`, CASE WHEN `da`.`current_bookings` >= `da`.`max_bookings` THEN 'Fully Booked' WHEN `da`.`current_bookings` > 0 THEN 'Partially Available' ELSE 'Available' END AS `availability_status`, `da`.`is_holiday` AS `is_holiday`, `da`.`special_hours` AS `special_hours`, `da`.`notes` AS `notes` FROM `daily_availability` AS `da` WHERE `da`.`available_date` >= curdate() ORDER BY `da`.`available_date` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `todays_booking_schedule`
--
DROP TABLE IF EXISTS `todays_booking_schedule`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `todays_booking_schedule`  AS SELECT `b`.`booking_id` AS `id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`, `u`.`phone` AS `customer_phone`, `u`.`email` AS `customer_email`, `b`.`booking_time` AS `booking_time`, `b`.`estimated_duration` AS `estimated_duration`, `b`.`travel_buffer` AS `travel_buffer`, time_format(`b`.`booking_time`,'%H:%i') AS `start_time`, time_format(addtime(`b`.`booking_time`,sec_to_time(`b`.`estimated_duration` * 60)),'%H:%i') AS `end_time`, time_format(addtime(addtime(`b`.`booking_time`,sec_to_time(`b`.`estimated_duration` * 60)),sec_to_time(`b`.`travel_buffer` * 60)),'%H:%i') AS `next_available_time`, `b`.`status` AS `status`, `s`.`service_name` AS `service_type`, `b`.`is_premium` AS `is_premium`, `b`.`total_amount` AS `total_amount`, `b`.`vehicle_size` AS `vehicle_size` FROM ((`bookings` `b` join `users` `u` on(`b`.`user_id` = `u`.`id`)) left join `services` `s` on(`b`.`service_id` = `s`.`service_id`)) WHERE cast(`b`.`booking_date` as date) = curdate() AND `b`.`status` in ('confirmed','pending','in_progress') ORDER BY `b`.`booking_time` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `v_user_vehicles_summary`
--
DROP TABLE IF EXISTS `v_user_vehicles_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_vehicles_summary`  AS SELECT `uv`.`vehicle_id` AS `vehicle_id`, `uv`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `owner_name`, concat(`uv`.`vehicle_year`,' ',`uv`.`vehicle_make`,' ',`uv`.`vehicle_model`) AS `vehicle_full_name`, `uv`.`vehicle_make` AS `vehicle_make`, `uv`.`vehicle_model` AS `vehicle_model`, `uv`.`vehicle_year` AS `vehicle_year`, `uv`.`vehicle_color` AS `vehicle_color`, `uv`.`license_plate` AS `license_plate`, `uv`.`vehicle_type` AS `vehicle_type`, `uv`.`vehicle_size` AS `vehicle_size`, `uv`.`is_default` AS `is_default`, count(`b`.`booking_id`) AS `total_bookings`, max(`b`.`booking_date`) AS `last_booking_date`, `uv`.`created_at` AS `added_date`, `uv`.`updated_at` AS `last_updated` FROM ((`user_vehicles` `uv` join `users` `u` on(`uv`.`user_id` = `u`.`id`)) left join `bookings` `b` on(`uv`.`vehicle_id` = `b`.`vehicle_id`)) GROUP BY `uv`.`vehicle_id` ORDER BY `uv`.`is_default` DESC, `uv`.`created_at` AS `DESCdesc` ASC  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_related_table_id` (`related_table`,`related_id`);

--
-- Indexes for table `addon_services`
--
ALTER TABLE `addon_services`
  ADD PRIMARY KEY (`addon_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `unique_booking_reference` (`booking_reference`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_service_id` (`service_id`),
  ADD KEY `idx_booking_date_time` (`booking_date`,`booking_time`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_vehicle_id` (`vehicle_id`);

--
-- Indexes for table `booking_conflicts`
--
ALTER TABLE `booking_conflicts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_booking_date` (`booking_date`),
  ADD KEY `idx_conflict_type` (`conflict_type`),
  ADD KEY `idx_resolved` (`resolved`);

--
-- Indexes for table `business_settings`
--
ALTER TABLE `business_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_setting_key` (`setting_key`);

--
-- Indexes for table `daily_availability`
--
ALTER TABLE `daily_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `available_date` (`available_date`),
  ADD KEY `idx_available_date` (`available_date`),
  ADD KEY `idx_current_bookings` (`current_bookings`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_related_booking_id` (`related_booking_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_payments_refund_status` (`refund_status`);

--
-- Indexes for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `unique_promo_code` (`code`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_service_id` (`service_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`slot_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_users_wallet_balance` (`wallet_balance`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_license_plate` (`license_plate`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `addon_services`
--
ALTER TABLE `addon_services`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `booking_conflicts`
--
ALTER TABLE `booking_conflicts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_settings`
--
ALTER TABLE `business_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `daily_availability`
--
ALTER TABLE `daily_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `user_vehicles` (`vehicle_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`related_booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD CONSTRAINT `payment_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_logs_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD CONSTRAINT `fk_user_vehicles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
