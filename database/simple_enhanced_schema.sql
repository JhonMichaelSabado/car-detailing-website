-- Enhanced Car Detailing Booking System Database Schema
-- Professional booking flow with admin approval, payment modes, and status tracking

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `addon_services`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `time_slots`;
DROP TABLE IF EXISTS `business_settings`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Enhanced bookings table for professional flow
CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  
  -- Service details
  `vehicle_size` enum('small','medium','large') NOT NULL DEFAULT 'medium',
  `add_on_services` text, -- JSON array of add-on service IDs
  
  -- Scheduling
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `estimated_duration` int(11) DEFAULT 120, -- minutes
  
  -- Location and travel
  `service_address` text NOT NULL,
  `service_lat` decimal(10,8) DEFAULT NULL,
  `service_lng` decimal(11,8) DEFAULT NULL,
  `travel_fee` decimal(10,2) DEFAULT 0.00,
  `landmark_instructions` text,
  
  -- Vehicle information
  `vehicle_year` int(4) DEFAULT NULL,
  `vehicle_make` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(50) DEFAULT NULL,
  `vehicle_body_type` varchar(30) DEFAULT NULL,
  `vehicle_color` varchar(30) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `special_instructions` text,
  
  -- Pricing breakdown
  `base_service_price` decimal(10,2) NOT NULL,
  `add_ons_total` decimal(10,2) DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `vat_amount` decimal(10,2) DEFAULT 0.00,
  `promo_discount` decimal(10,2) DEFAULT 0.00,
  `promo_code` varchar(50) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  
  -- Payment configuration
  `payment_mode` enum('deposit_50','full_payment') NOT NULL DEFAULT 'deposit_50',
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') DEFAULT NULL,
  
  -- Status tracking
  `status` enum('pending','confirmed','assigned','in_progress','completed','cancelled','rejected') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `admin_confirmed_by` int(11) DEFAULT NULL,
  `admin_confirmed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text,
  `rejection_reason` text,
  
  -- Slot management
  `slot_locked_until` timestamp NULL DEFAULT NULL,
  `auto_cancel_after` timestamp NULL DEFAULT NULL,
  
  -- Receipt and notifications
  `receipt_sent` tinyint(1) DEFAULT 0,
  `confirmation_email_sent` tinyint(1) DEFAULT 0,
  `confirmation_sms_sent` tinyint(1) DEFAULT 0,
  
  -- Timestamps
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `unique_booking_reference` (`booking_reference`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_booking_date_time` (`booking_date`, `booking_time`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced payments table for detailed tracking
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('deposit','final','full','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','credit_card','bank_transfer','cash') NOT NULL,
  `payment_status` enum('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  
  -- Transaction details
  `transaction_id` varchar(100) DEFAULT NULL,
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_response` text,
  
  -- Processing info
  `processed_by` int(11) DEFAULT NULL, -- admin who processed
  `payment_date` timestamp NULL DEFAULT NULL,
  `confirmation_date` timestamp NULL DEFAULT NULL,
  
  -- Refund details
  `refund_amount` decimal(10,2) DEFAULT 0.00,
  `refund_date` timestamp NULL DEFAULT NULL,
  `refund_reason` text,
  
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`payment_id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_transaction_id` (`transaction_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add-on services table
CREATE TABLE `addon_services` (
  `addon_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(100) NOT NULL,
  `description` text,
  `price_small` decimal(10,2) NOT NULL,
  `price_medium` decimal(10,2) NOT NULL,
  `price_large` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`addon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promo codes table
CREATE TABLE `promo_codes` (
  `promo_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(200),
  `discount_type` enum('percentage','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `valid_from` timestamp DEFAULT CURRENT_TIMESTAMP,
  `valid_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`promo_id`),
  UNIQUE KEY `unique_promo_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Time slots management
CREATE TABLE `time_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `max_bookings` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `days_of_week` varchar(20) DEFAULT '1,2,3,4,5,6,7', -- 1=Monday, 7=Sunday
  PRIMARY KEY (`slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Business settings for travel fees and configuration
CREATE TABLE `business_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced notifications table
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_related_booking_id` (`related_booking_id`),
  KEY `idx_is_read` (`is_read`),
  FOREIGN KEY (`related_booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews table
CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text,
  `is_approved` tinyint(1) DEFAULT 0,
  `admin_response` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `idx_booking_id` (`booking_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_id` (`service_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced activity logs
CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `related_table` varchar(50),
  `related_id` int(11),
  `old_values` text, -- JSON of old values
  `new_values` text, -- JSON of new values
  `ip_address` varchar(45),
  `user_agent` varchar(500),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_related_table_id` (`related_table`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default add-on services
INSERT INTO `addon_services` (`service_name`, `description`, `price_small`, `price_medium`, `price_large`, `duration_minutes`, `sort_order`) VALUES
('Headlight Restoration', 'Professional headlight oxidation removal and clarity enhancement', 299.00, 349.00, 399.00, 45, 1),
('Engine Bay Cleaning', 'Thorough engine compartment cleaning and detailing', 499.00, 599.00, 699.00, 60, 2),
('Watermark Removal', 'Complete water spot and acid rain damage removal', 699.00, 799.00, 899.00, 90, 3),
('Leather Treatment', 'Professional leather conditioning and protection', 699.00, 899.00, 1099.00, 90, 4),
('Glass Polishing', 'Professional glass polishing for crystal clarity', 499.00, 599.00, 699.00, 60, 5),
('Pet Hair Removal', 'Specialized pet hair removal from vehicle interior', 299.00, 399.00, 499.00, 45, 6),
('Ozone Treatment', 'Advanced ozone treatment for odor elimination', 899.00, 999.00, 1199.00, 120, 7);

-- Insert default time slots
INSERT INTO `time_slots` (`start_time`, `end_time`, `max_bookings`, `days_of_week`) VALUES
('08:00:00', '10:00:00', 2, '1,2,3,4,5,6'),
('10:00:00', '12:00:00', 2, '1,2,3,4,5,6'),
('12:00:00', '14:00:00', 2, '1,2,3,4,5,6'),
('14:00:00', '16:00:00', 2, '1,2,3,4,5,6'),
('16:00:00', '18:00:00', 2, '1,2,3,4,5,6'),
('08:00:00', '11:00:00', 1, '7'), -- Sunday extended slots
('11:00:00', '14:00:00', 1, '7'),
('14:00:00', '17:00:00', 1, '7');

-- Insert business settings
INSERT INTO `business_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('base_travel_fee', '50.00', 'number', 'Base travel fee within city'),
('travel_fee_per_km', '15.00', 'number', 'Additional fee per kilometer beyond base range'),
('free_travel_radius_km', '5', 'number', 'Free travel radius in kilometers'),
('max_travel_radius_km', '25', 'number', 'Maximum service radius in kilometers'),
('vat_rate', '0.12', 'number', 'VAT rate (12%)'),
('vat_enabled', 'true', 'boolean', 'Enable VAT calculation'),
('auto_cancel_hours', '48', 'number', 'Auto-cancel pending bookings after hours'),
('slot_lock_minutes', '10', 'number', 'Lock time slot for minutes during booking'),
('booking_advance_days', '30', 'number', 'Maximum days in advance for booking'),
('same_day_booking_cutoff', '10:00:00', 'string', 'Cutoff time for same-day bookings'),
('business_phone', '+63 (2) 123-4567', 'string', 'Business contact phone'),
('business_email', 'info@cardetailing.com', 'string', 'Business contact email'),
('notification_email', 'notifications@cardetailing.com', 'string', 'Email for admin notifications');

-- Insert sample promo codes
INSERT INTO `promo_codes` (`code`, `description`, `discount_type`, `discount_value`, `min_amount`, `max_discount`, `usage_limit`, `valid_until`) VALUES
('FIRST20', 'First-time customer 20% discount', 'percentage', 20.00, 1000.00, 500.00, 100, DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('SAVE100', 'Save ₱100 on orders over ₱1500', 'fixed', 100.00, 1500.00, NULL, 50, DATE_ADD(NOW(), INTERVAL 6 MONTH)),
('PREMIUM15', '15% off Premium services', 'percentage', 15.00, 2000.00, 750.00, 200, DATE_ADD(NOW(), INTERVAL 1 YEAR));