-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 03:20 PM
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

INSERT INTO `bookings` (`booking_id`, `booking_reference`, `user_id`, `service_id`, `vehicle_size`, `add_on_services`, `booking_date`, `booking_time`, `estimated_duration`, `service_address`, `service_lat`, `service_lng`, `travel_fee`, `landmark_instructions`, `vehicle_year`, `vehicle_make`, `vehicle_model`, `vehicle_body_type`, `vehicle_color`, `license_plate`, `special_instructions`, `base_service_price`, `add_ons_total`, `subtotal`, `vat_amount`, `promo_discount`, `promo_code`, `total_amount`, `payment_mode`, `deposit_amount`, `remaining_amount`, `payment_method`, `status`, `payment_status`, `admin_confirmed_by`, `admin_confirmed_at`, `admin_notes`, `rejection_reason`, `slot_locked_until`, `auto_cancel_after`, `receipt_sent`, `confirmation_email_sent`, `confirmation_sms_sent`, `created_at`, `updated_at`) VALUES
(2, 'CD20251015AAABBC', 6, 1, 'medium', '[]', '2025-10-21', '12:00:00', 120, 'Ezra, Reveal Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.43310091', '120.94355106', '260.20', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1459.20', '175.10', '0.00', NULL, '1634.30', 'full_payment', '1634.30', '0.00', 'bank_transfer', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 08:55:22', '2025-10-15 08:55:22'),
(3, 'CD2025101509333D', 6, 7, 'medium', '[]', '2025-10-22', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '599.00', '0.00', '875.93', '105.11', '0.00', NULL, '981.04', 'deposit_50', '490.52', '490.52', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 09:24:48', '2025-10-15 09:24:48'),
(4, 'CD20251015660C66', 6, 7, 'medium', '[]', '2025-10-22', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '599.00', '0.00', '875.93', '105.11', '0.00', NULL, '981.04', 'deposit_50', '490.52', '490.52', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 09:25:42', '2025-10-15 09:25:42'),
(5, 'CD20251015BB3C3B', 6, 1, 'medium', '[]', '2025-10-21', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'full_payment', '1653.04', '0.00', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 10:22:51', '2025-10-15 10:22:51'),
(6, 'CD20251015C55450', 6, 4, 'large', '[]', '2025-10-30', '08:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2799.00', '0.00', '3075.93', '369.11', '0.00', NULL, '3445.04', 'full_payment', '3445.04', '0.00', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-15 10:29:16', '2025-10-15 10:29:16'),
(7, 'CD20251017ACCA51', 6, 1, 'medium', '[]', '2025-10-23', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'full_payment', '1653.04', '0.00', 'gcash', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 00:50:34', '2025-10-17 00:50:34'),
(8, 'CD2025101788160C', 6, 1, 'medium', '[]', '2025-10-21', '12:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1475.93', '177.11', '0.00', NULL, '1653.04', 'deposit_50', '826.52', '826.52', 'maya', 'pending', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 00:54:16', '2025-10-17 00:54:16'),
(9, 'CD202510178E5709', 6, 1, 'large', '[]', '2025-10-21', '10:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1399.00', '0.00', '1675.93', '201.11', '0.00', NULL, '1877.04', 'deposit_50', '938.52', '938.52', 'gcash', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:21:44', '2025-10-17 01:22:26'),
(10, 'CD20251017A729B7', 6, 5, 'large', '[]', '2025-10-21', '14:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '5299.00', '0.00', '5575.93', '669.11', '0.00', NULL, '6245.04', 'full_payment', '6245.04', '0.00', 'maya', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:26:18', '2025-10-17 01:26:31'),
(11, 'CD202510177A47FF', 6, 2, 'medium', '[]', '2025-10-21', '12:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1699.00', '0.00', '1975.93', '237.11', '0.00', NULL, '2213.04', 'full_payment', '2213.04', '0.00', 'bank_transfer', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:29:59', '2025-10-17 01:31:03'),
(12, 'CD20251017A456CA', 6, 2, 'medium', '[]', '2025-10-21', '08:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '276.93', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1699.00', '0.00', '1975.93', '237.11', '0.00', NULL, '2213.04', 'deposit_50', '1106.52', '1106.52', 'gcash', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 01:31:38', '2025-10-17 01:31:45'),
(13, 'CD20251017475166', 6, 1, 'medium', '[]', '2025-10-30', '14:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'deposit_50', '671.44', '671.44', 'gcash', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 12:48:52', '2025-10-17 12:49:05'),
(14, 'CD202510179275AF', 1, 7, 'large', '[]', '2025-10-29', '12:00:00', 120, 'Sol P. Bella Street, Poblacion II-A, Imus, Cavite, Calabarzon, 4103, Philippines', '14.43235281', '120.93664248', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '699.00', '0.00', '699.00', '83.88', '0.00', NULL, '782.88', 'full_payment', '782.88', '0.00', 'maya', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-17 13:25:29', '2025-10-17 13:25:34'),
(15, 'CD20251018F9C810', 1, 1, 'medium', '[]', '2025-10-18', '11:00:00', 120, 'Yakal Street, Vista Verde South Subdivision, Bacoor, Cavite, Calabarzon, 4102, Philippines', '14.42000000', '120.96000000', '0.00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1199.00', '0.00', '1199.00', '143.88', '0.00', NULL, '1342.88', 'deposit_50', '671.44', '671.44', 'gcash', 'pending', 'paid', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2025-10-18 03:32:31', '2025-10-18 03:32:40');

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
  `related_booking_id` int(11) DEFAULT NULL,
  `email_sent` tinyint(1) DEFAULT 0,
  `sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `payment_type`, `amount`, `payment_method`, `payment_status`, `transaction_id`, `gateway_reference`, `gateway_response`, `processed_by`, `payment_date`, `confirmation_date`, `refund_amount`, `refund_date`, `refund_reason`, `notes`, `created_at`, `updated_at`) VALUES
(2, 2, 6, 'full', '1634.30', 'bank_transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 08:55:22', '2025-10-15 08:55:22'),
(3, 3, 6, 'deposit', '490.52', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:24:48', '2025-10-15 09:24:48'),
(4, 4, 6, 'deposit', '490.52', 'maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 09:25:42', '2025-10-15 09:25:42'),
(5, 5, 6, 'full', '1653.04', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:22:51', '2025-10-15 10:22:51'),
(6, 6, 6, 'full', '3445.04', 'maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-15 10:29:16', '2025-10-15 10:29:16'),
(7, 7, 6, 'full', '1653.04', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:50:34', '2025-10-17 00:50:34'),
(8, 8, 6, 'deposit', '826.52', 'maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 00:54:16', '2025-10-17 00:54:16'),
(9, 9, 6, 'deposit', '938.52', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:21:44', '2025-10-17 01:21:44'),
(10, 9, 6, 'deposit', '938.52', 'gcash', 'completed', NULL, '8FBD9D80BF', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:22:26', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:22:26', '2025-10-17 01:22:26'),
(11, 10, 6, 'full', '6245.04', 'maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:18', '2025-10-17 01:26:18'),
(12, 10, 6, 'full', '6245.04', 'maya', 'completed', NULL, 'A73C23BC5B', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:26:31', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:26:31', '2025-10-17 01:26:31'),
(13, 11, 6, 'full', '2213.04', 'bank_transfer', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:29:59', '2025-10-17 01:29:59'),
(14, 11, 6, 'full', '2213.04', '', 'completed', NULL, 'C78503C2D7', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:30:24', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:24', '2025-10-17 01:30:24'),
(15, 11, 6, 'full', '2213.04', '', 'completed', NULL, '2F7CA56F44', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"TRX123456\"}', NULL, '2025-10-17 01:30:39', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:30:39', '2025-10-17 01:30:39'),
(16, 11, 6, 'full', '2213.04', '', 'completed', NULL, '06C4054A86', '{\"method\":\"bank\",\"note\":\"Mock successful transaction\",\"bank_reference\":\"1234567890\"}', NULL, '2025-10-17 01:31:03', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:03', '2025-10-17 01:31:03'),
(17, 12, 6, 'deposit', '1106.52', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:38', '2025-10-17 01:31:38'),
(18, 12, 6, 'deposit', '1106.52', 'gcash', 'completed', NULL, 'EA3CD10FD1', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 01:31:45', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 01:31:45', '2025-10-17 01:31:45'),
(19, 13, 6, 'deposit', '671.44', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:48:52', '2025-10-17 12:48:52'),
(20, 13, 6, 'deposit', '671.44', 'gcash', 'completed', NULL, '427E3E5F87', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 12:49:05', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 12:49:05', '2025-10-17 12:49:05'),
(21, 14, 1, 'full', '782.88', 'maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:29', '2025-10-17 13:25:29'),
(22, 14, 1, 'full', '782.88', 'maya', 'completed', NULL, '26F6460E81', '{\"method\":\"maya\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-17 13:25:34', NULL, '0.00', NULL, NULL, NULL, '2025-10-17 13:25:34', '2025-10-17 13:25:34'),
(23, 15, 1, 'deposit', '671.44', 'gcash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:31', '2025-10-18 03:32:31'),
(24, 15, 1, 'deposit', '671.44', 'gcash', 'completed', NULL, '3F78AF9152', '{\"method\":\"gcash\",\"note\":\"Mock successful transaction\",\"bank_reference\":null}', NULL, '2025-10-18 03:32:40', NULL, '0.00', NULL, NULL, NULL, '2025-10-18 03:32:40', '2025-10-18 03:32:40');

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

INSERT INTO `users` (`id`, `google_id`, `username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `created_at`, `updated_at`, `is_active`, `reset_token`, `reset_expires`, `address`, `date_of_birth`, `profile_picture`, `email_verified`, `last_login`) VALUES
(1, NULL, 'admin', 'admin@cardetailing.com', '$2y$10$Pg7ct9QDRq44MqYJIDrQ..rLWb16AR2.S5DFK2pXkrP0AJHQ3fNg6', 'Admin', 'User', NULL, 'admin', '2025-09-24 09:42:46', '2025-09-25 07:57:23', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(2, NULL, 'jhonny', 'jhonmichaelsabado123@gmail.com', '$2y$10$gJ8q6rlvxIzgRdUJ.vIiHux1qeOEhTQmakMxGwm6R7HxBfyFbdtQS', 'jhon', 'sabado', '09947064818', 'user', '2025-09-24 09:53:28', '2025-09-30 09:39:11', 1, '028e3bf52873fdfca1f90f1c7903cd4d8ce19c5144f8f2b093adb3d44ff4f73b', '2025-10-01 17:39:11', NULL, NULL, NULL, 0, NULL),
(6, NULL, 'sabadoggs', 'enjiqt@gmail.com', '$2y$10$vVzF/zTMs7/UoQg.iJR2FOcd/PPmFzsj.KTzmPY.r/tPD/wvCb/se', 'makol', 'sabado', '999485947', 'user', '2025-09-25 08:02:18', '2025-09-26 04:32:33', 1, '94f3788b670c5a27e349df70f0798aee3db72306dcf54ca0a8eb90a7c85942ad', '2025-09-26 07:32:33', NULL, NULL, NULL, 0, NULL),
(7, '108340474821680541139', 'psychomobpsycho', 'psychomobpsycho@gmail.com', '', 'Toshinori', 'oshinori', NULL, 'user', '2025-09-26 02:29:33', '2025-09-26 02:29:33', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(8, '111598267480119535036', 'ccamtest1231', 'ccamtest1231@gmail.com', '', 'jhon', 'michael salas sabado', NULL, 'user', '2025-09-26 02:40:24', '2025-09-26 02:40:24', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(9, NULL, 'ccamtest12', 'ccamtest12@gmail.com', '$2y$10$7kPt9l54e.6CAd1TDqjUPOMTtIWmxAUSVB4a88fx.RhgGo2SpPwWK', 'ccamtesting', '', '', 'user', '2025-09-26 04:45:22', '2025-09-26 04:45:22', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(10, NULL, 'johndoetest', 'john.doe.test@example.com', '$2y$10$mldy.u/4Wb/QvC6WOKXpHuWG5OBZPxak6oFIwppA9Jgex8MGkTlLW', 'John', 'Doe', '+1234567890', 'user', '2025-09-30 07:34:08', '2025-09-30 07:34:08', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL),
(11, '104311345767607948687', 'jhonmichael.sabado', 'jhonmichael.sabado@cvsu.edu.ph', '', 'Jhon Michael', 'Sabado', NULL, 'user', '2025-10-07 09:01:03', '2025-10-07 09:01:03', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL);

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
  ADD KEY `idx_payment_status` (`payment_status`);

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
  ADD KEY `idx_transaction_id` (`transaction_id`);

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
  ADD UNIQUE KEY `google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addon_services`
--
ALTER TABLE `addon_services`
  MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for dumped tables
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
