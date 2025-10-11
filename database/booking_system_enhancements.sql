-- Enhanced Booking System with Time Slot Logic
-- Add these enhancements to your existing car detailing database

-- First, let's modify the existing bookings table to support the new time slot logic
ALTER TABLE `bookings` 
DROP COLUMN IF EXISTS `booking_date`,
ADD COLUMN `booking_date` DATE NOT NULL AFTER `vehicle_size`,
ADD COLUMN `start_time` TIME NOT NULL AFTER `booking_date`,
ADD COLUMN `end_time` TIME NOT NULL AFTER `start_time`,
ADD COLUMN `customer_address` TEXT NOT NULL AFTER `vehicle_details`,
ADD COLUMN `travel_buffer_minutes` INT DEFAULT 60 AFTER `customer_address`,
ADD COLUMN `payment_verification_status` ENUM('pending','verified','rejected') DEFAULT 'pending' AFTER `payment_status`,
ADD INDEX `idx_date_status` (`booking_date`, `status`),
ADD INDEX `idx_time_range` (`booking_date`, `start_time`, `end_time`);

-- Create daily availability tracking table
CREATE TABLE IF NOT EXISTS `daily_availability` (
  `availability_id` INT AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL UNIQUE,
  `accepted_bookings_count` INT DEFAULT 0,
  `is_fully_booked` BOOLEAN DEFAULT FALSE,
  `blocked_time_ranges` JSON,
  `business_hours_start` TIME DEFAULT '08:00:00',
  `business_hours_end` TIME DEFAULT '18:00:00',
  `max_bookings_per_day` INT DEFAULT 2,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_date` (`date`),
  INDEX `idx_fully_booked` (`is_fully_booked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create booking conflicts log table
CREATE TABLE IF NOT EXISTS `booking_conflicts` (
  `conflict_id` INT AUTO_INCREMENT PRIMARY KEY,
  `attempted_booking_date` DATE NOT NULL,
  `attempted_start_time` TIME NOT NULL,
  `attempted_end_time` TIME NOT NULL,
  `conflict_reason` ENUM('max_bookings_exceeded','time_overlap','travel_buffer_conflict') NOT NULL,
  `conflicting_booking_id` INT,
  `customer_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_date` (`attempted_booking_date`),
  FOREIGN KEY (`conflicting_booking_id`) REFERENCES `bookings`(`booking_id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create time slots template table (for admin to define available slots)
CREATE TABLE IF NOT EXISTS `time_slot_templates` (
  `slot_id` INT AUTO_INCREMENT PRIMARY KEY,
  `slot_name` VARCHAR(100) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `duration_hours` DECIMAL(3,1) NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default time slot templates
INSERT INTO `time_slot_templates` (`slot_name`, `start_time`, `end_time`, `duration_hours`) VALUES
('Morning Slot', '08:00:00', '12:00:00', 4.0),
('Afternoon Slot', '13:00:00', '17:00:00', 4.0),
('Extended Morning', '08:00:00', '13:00:00', 5.0),
('Extended Afternoon', '13:00:00', '18:00:00', 5.0);

-- Add triggers to automatically update daily availability
DELIMITER //

CREATE TRIGGER `update_daily_availability_after_booking_update` 
AFTER UPDATE ON `bookings`
FOR EACH ROW 
BEGIN
  -- Only update if status changed to/from 'accepted'
  IF OLD.status != NEW.status AND (OLD.status = 'accepted' OR NEW.status = 'accepted') THEN
    -- Update daily availability for the booking date
    INSERT INTO `daily_availability` (`date`, `accepted_bookings_count`, `is_fully_booked`)
    SELECT 
      NEW.booking_date,
      COUNT(*) as count,
      CASE WHEN COUNT(*) >= 2 THEN TRUE ELSE FALSE END
    FROM `bookings`
    WHERE `booking_date` = NEW.booking_date AND `status` = 'accepted'
    ON DUPLICATE KEY UPDATE
      `accepted_bookings_count` = VALUES(`accepted_bookings_count`),
      `is_fully_booked` = VALUES(`is_fully_booked`);
  END IF;
END//

CREATE TRIGGER `update_daily_availability_after_booking_insert`
AFTER INSERT ON `bookings`
FOR EACH ROW
BEGIN
  -- Update daily availability when new booking is inserted
  IF NEW.status = 'accepted' THEN
    INSERT INTO `daily_availability` (`date`, `accepted_bookings_count`, `is_fully_booked`)
    SELECT 
      NEW.booking_date,
      COUNT(*) as count,
      CASE WHEN COUNT(*) >= 2 THEN TRUE ELSE FALSE END
    FROM `bookings`
    WHERE `booking_date` = NEW.booking_date AND `status` = 'accepted'
    ON DUPLICATE KEY UPDATE
      `accepted_bookings_count` = VALUES(`accepted_bookings_count`),
      `is_fully_booked` = VALUES(`is_fully_booked`);
  END IF;
END//

DELIMITER ;

-- Create a view for easy booking availability checking
CREATE OR REPLACE VIEW `booking_availability_view` AS
SELECT 
  b.booking_date,
  b.start_time,
  b.end_time,
  TIME_SUB(b.start_time, INTERVAL b.travel_buffer_minutes MINUTE) as buffer_start,
  TIME_ADD(b.end_time, INTERVAL b.travel_buffer_minutes MINUTE) as buffer_end,
  b.booking_id,
  b.status,
  u.username as customer_name,
  s.service_name
FROM `bookings` b
LEFT JOIN `users` u ON b.user_id = u.id
LEFT JOIN `services` s ON b.service_id = s.service_id
WHERE b.status = 'accepted';

-- Create a view for daily booking summary
CREATE OR REPLACE VIEW `daily_booking_summary` AS
SELECT 
  booking_date,
  COUNT(*) as total_bookings,
  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
  SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_bookings,
  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
  SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
  CASE WHEN SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) >= 2 THEN 'FULLY_BOOKED' ELSE 'AVAILABLE' END as availability_status
FROM `bookings`
GROUP BY booking_date
ORDER BY booking_date;

-- Sample data for testing (optional - remove in production)
INSERT INTO `bookings` (
  `user_id`, `service_id`, `vehicle_size`, `booking_date`, `start_time`, `end_time`, 
  `total_amount`, `customer_address`, `special_requests`, `status`
) VALUES
(1, 1, 'medium', '2025-10-15', '08:00:00', '12:00:00', 1199.00, '123 Test Street, Manila', 'Please bring extra towels', 'pending'),
(1, 2, 'small', '2025-10-16', '13:00:00', '17:00:00', 1499.00, '456 Sample Avenue, Quezon City', 'Car is very dirty', 'pending');