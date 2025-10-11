-- Car Detailing Database Tables Setup
-- Run this in phpMyAdmin or MySQL to create all necessary tables

-- First, drop existing tables if they exist (in correct order to avoid foreign key issues)
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `services`;

-- Services table with vehicle size pricing
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text,
  `price_small` decimal(10,2) NOT NULL,
  `price_medium` decimal(10,2) NOT NULL,
  `price_large` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `included_items` text,
  `free_items` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert actual car detailing services
INSERT INTO `services` (`category`, `service_name`, `description`, `price_small`, `price_medium`, `price_large`, `duration_minutes`, `included_items`, `free_items`) VALUES
-- Basic Packages
('Basic Package', 'Basic Exterior Care', 'Premium car shampoo, foam care, tire dressing', 999.00, 1199.00, 1399.00, 90, 'Premium car shampoo, foam care, tire dressing', 'Acid Rain Removal'),
('Basic Package', 'Express Care + Wax', 'Basic Exterior Care plus Graphene Ceramic Wax (up to 6 months protection)', 1499.00, 1699.00, 1899.00, 120, 'Basic Exterior Care plus Graphene Ceramic Wax (up to 6 months protection)', 'Quick Glass Polish'),

-- Premium Detailing
('Premium Detailing', 'Full Exterior Detailing', 'Deep exterior wash, clay bar treatment, acid rain removal, graphene ceramic wax', 2499.00, 2799.00, 3099.00, 180, 'Deep exterior wash, clay bar treatment, acid rain removal, graphene ceramic wax', 'Paint Decontamination'),
('Premium Detailing', 'Interior Deep Clean', 'Vacuuming, shampooing seats and carpets, dashboard and panel conditioning', 2299.00, 2499.00, 2799.00, 150, 'Vacuuming, shampooing seats and carpets, dashboard and panel conditioning', 'Basic Car Exterior Shampooing'),
('Premium Detailing', 'Platinum Package (Full Interior + Exterior Detail)', 'Combination of Full Exterior and Interior Deep Clean, seat shampooing, mat lining and matting included', 4499.00, 4899.00, 5299.00, 300, 'Combination of Full Exterior and Interior Deep Clean, seat shampooing, mat lining and matting included', 'Tire Black and Odor Neutralizer'),

-- Specialized and Add-On Services
('Add-On Service', 'Headlight Oxidation Removal', 'Professional headlight restoration and clarity enhancement', 299.00, 349.00, 399.00, 45, 'Headlight oxidation removal and restoration', NULL),
('Add-On Service', 'Engine Bay Cleaning', 'Thorough engine compartment cleaning and detailing', 499.00, 599.00, 699.00, 60, 'Complete engine bay cleaning and detailing', NULL),
('Add-On Service', 'Watermark and Acid Rain Removal (Full)', 'Complete water spot and acid rain damage removal', 699.00, 799.00, 899.00, 90, 'Full watermark and acid rain removal treatment', NULL),
('Add-On Service', 'Upholstery or Leather Treatment', 'Professional upholstery cleaning and leather conditioning', 699.00, 899.00, 1099.00, 90, 'Upholstery cleaning or leather treatment and conditioning', NULL),
('Add-On Service', 'Glass Polishing', 'Professional glass polishing for crystal clear visibility', 499.00, 599.00, 699.00, 60, 'Complete glass polishing service', NULL),
('Add-On Service', 'Pet Hair Removal', 'Specialized pet hair removal from vehicle interior', 299.00, 399.00, 499.00, 45, 'Professional pet hair removal service', NULL),
('Add-On Service', 'Odor Elimination (Ozone Treatment)', 'Advanced ozone treatment for odor elimination', 899.00, 999.00, 1199.00, 120, 'Professional ozone odor elimination treatment', NULL),
('Add-On Service', 'Ceramic Coating (1-year Protection)', 'Premium ceramic coating with 1-year protection guarantee', 4999.00, 5999.00, 6999.00, 240, '1-year ceramic coating protection with professional application', NULL);

-- Bookings table
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `vehicle_size` enum('small','medium','large') NOT NULL DEFAULT 'medium',
  `booking_date` datetime NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled','declined') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','paid','refunded') DEFAULT 'pending',
  `vehicle_details` text,
  `special_requests` text,
  `admin_notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`),
  FOREIGN KEY (`service_id`) REFERENCES `services`(`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','gcash','bank_transfer') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` varchar(100),
  `payment_date` timestamp DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_text` text,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_response` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`service_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('booking','payment','system','review','general') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `related_booking_id` (`related_booking_id`),
  FOREIGN KEY (`related_booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs table for tracking all actions
CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `related_table` varchar(50),
  `related_id` int(11),
  `ip_address` varchar(45),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update users table to include additional fields if not exists
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `phone` varchar(20),
ADD COLUMN IF NOT EXISTS `address` text,
ADD COLUMN IF NOT EXISTS `date_of_birth` date,
ADD COLUMN IF NOT EXISTS `profile_picture` varchar(255),
ADD COLUMN IF NOT EXISTS `email_verified` tinyint(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL,
ADD COLUMN IF NOT EXISTS `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;