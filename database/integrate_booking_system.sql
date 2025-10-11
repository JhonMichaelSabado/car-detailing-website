-- Execute this script to integrate the booking system into your existing car-detailing database
-- Run this after backing up your current database

-- Start transaction to ensure all changes are applied together
START TRANSACTION;

-- 1. Add new columns to existing bookings table
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS booking_time TIME DEFAULT '09:00:00' COMMENT 'Specific time slot for the booking',
ADD COLUMN IF NOT EXISTS estimated_duration INT DEFAULT 120 COMMENT 'Estimated service duration in minutes',
ADD COLUMN IF NOT EXISTS travel_buffer INT DEFAULT 30 COMMENT 'Travel time buffer in minutes',
ADD COLUMN IF NOT EXISTS is_premium BOOLEAN DEFAULT FALSE COMMENT 'Premium service flag',
ADD COLUMN IF NOT EXISTS admin_notes TEXT COMMENT 'Internal admin notes',
ADD COLUMN IF NOT EXISTS customer_notes TEXT COMMENT 'Customer special requests';

-- 2. Create daily availability tracking table
CREATE TABLE IF NOT EXISTS daily_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    available_date DATE NOT NULL UNIQUE,
    max_bookings INT DEFAULT 2,
    current_bookings INT DEFAULT 0,
    is_holiday BOOLEAN DEFAULT FALSE,
    special_hours JSON COMMENT 'Special business hours for this date',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_available_date (available_date),
    INDEX idx_current_bookings (current_bookings)
);

-- 3. Create time slots management table
CREATE TABLE IF NOT EXISTS time_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    max_duration INT DEFAULT 150 COMMENT 'Maximum service duration for this slot in minutes',
    slot_type ENUM('standard', 'premium', 'express') DEFAULT 'standard',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_slot_time (slot_time),
    INDEX idx_slot_time (slot_time),
    INDEX idx_is_active (is_active)
);

-- 4. Insert default time slots
INSERT IGNORE INTO time_slots (slot_time, slot_type, max_duration) VALUES
('09:00:00', 'standard', 150),
('12:00:00', 'standard', 150),
('15:00:00', 'standard', 150),
('10:30:00', 'premium', 180),
('13:30:00', 'premium', 180);

-- 5. Create booking conflicts log table
CREATE TABLE IF NOT EXISTS booking_conflicts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    conflict_type ENUM('time_overlap', 'daily_limit', 'travel_buffer', 'business_hours') NOT NULL,
    conflict_details JSON,
    attempted_booking_id INT,
    existing_booking_id INT,
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_booking_date (booking_date),
    INDEX idx_conflict_type (conflict_type),
    INDEX idx_resolved (resolved),
    FOREIGN KEY (attempted_booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (existing_booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- 6. Create business settings table for configuration
CREATE TABLE IF NOT EXISTS business_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('integer', 'boolean', 'string', 'json', 'time') DEFAULT 'string',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_active (is_active)
);

-- 7. Insert default business settings
INSERT IGNORE INTO business_settings (setting_key, setting_value, setting_type, description) VALUES
('max_daily_bookings', '2', 'integer', 'Maximum number of bookings allowed per day'),
('business_start_time', '09:00:00', 'time', 'Daily business opening time'),
('business_end_time', '17:00:00', 'time', 'Daily business closing time'),
('default_travel_buffer', '30', 'integer', 'Default travel time between bookings in minutes'),
('weekend_bookings_enabled', 'false', 'boolean', 'Allow bookings on weekends'),
('advance_booking_days', '30', 'integer', 'How many days in advance customers can book'),
('auto_approve_bookings', 'false', 'boolean', 'Automatically approve bookings without admin review'),
('notification_email', 'admin@cardetailing.com', 'string', 'Email for booking notifications');

-- 8. Create trigger to update daily availability
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_daily_availability_on_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO daily_availability (available_date, current_bookings)
    VALUES (NEW.booking_date, 1)
    ON DUPLICATE KEY UPDATE current_bookings = current_bookings + 1;
END //
DELIMITER ;

-- 9. Create trigger to update daily availability on booking status change
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_daily_availability_on_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        IF NEW.status = 'accepted' AND OLD.status != 'accepted' THEN
            INSERT INTO daily_availability (available_date, current_bookings)
            VALUES (NEW.booking_date, 1)
            ON DUPLICATE KEY UPDATE current_bookings = current_bookings + 1;
        ELSEIF OLD.status = 'accepted' AND NEW.status != 'accepted' THEN
            UPDATE daily_availability 
            SET current_bookings = GREATEST(0, current_bookings - 1)
            WHERE available_date = NEW.booking_date;
        END IF;
    END IF;
END //
DELIMITER ;

-- 10. Create trigger to update daily availability on booking deletion
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_daily_availability_on_booking_delete
AFTER DELETE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.status = 'accepted' THEN
        UPDATE daily_availability 
        SET current_bookings = GREATEST(0, current_bookings - 1)
        WHERE available_date = OLD.booking_date;
    END IF;
END //
DELIMITER ;

-- 11. Create view for booking availability overview
CREATE OR REPLACE VIEW booking_availability_overview AS
SELECT 
    da.available_date,
    da.max_bookings,
    da.current_bookings,
    (da.max_bookings - da.current_bookings) AS available_slots,
    CASE 
        WHEN da.current_bookings >= da.max_bookings THEN 'Fully Booked'
        WHEN da.current_bookings > 0 THEN 'Partially Available'
        ELSE 'Available'
    END AS availability_status,
    da.is_holiday,
    da.special_hours,
    da.notes
FROM daily_availability da
WHERE da.available_date >= CURDATE()
ORDER BY da.available_date;

-- 12. Create view for today's bookings with time conflicts
CREATE OR REPLACE VIEW todays_booking_schedule AS
SELECT 
    b.id,
    b.customer_name,
    b.customer_phone,
    b.booking_time,
    b.estimated_duration,
    b.travel_buffer,
    TIME_FORMAT(b.booking_time, '%H:%i') AS start_time,
    TIME_FORMAT(
        ADDTIME(b.booking_time, SEC_TO_TIME(b.estimated_duration * 60)), 
        '%H:%i'
    ) AS end_time,
    TIME_FORMAT(
        ADDTIME(
            ADDTIME(b.booking_time, SEC_TO_TIME(b.estimated_duration * 60)),
            SEC_TO_TIME(b.travel_buffer * 60)
        ), 
        '%H:%i'
    ) AS next_available_time,
    b.status,
    b.service_type,
    b.is_premium
FROM bookings b
WHERE b.booking_date = CURDATE()
    AND b.status IN ('accepted', 'pending')
ORDER BY b.booking_time;

-- 13. Initialize daily availability for existing bookings
INSERT IGNORE INTO daily_availability (available_date, current_bookings)
SELECT 
    booking_date,
    COUNT(*) as current_bookings
FROM bookings 
WHERE status = 'accepted'
    AND booking_date >= CURDATE()
GROUP BY booking_date;

-- 14. Update existing bookings with default times if they don't have them
UPDATE bookings 
SET booking_time = CASE 
    WHEN id % 3 = 0 THEN '09:00:00'
    WHEN id % 3 = 1 THEN '12:00:00'
    ELSE '15:00:00'
END,
estimated_duration = CASE 
    WHEN service_type LIKE '%premium%' THEN 180
    WHEN service_type LIKE '%express%' THEN 90
    ELSE 120
END
WHERE booking_time IS NULL OR booking_time = '00:00:00';

COMMIT;

-- Display integration summary
SELECT 'Booking System Integration Complete!' as Status;
SELECT 'Tables Created/Modified:' as Info, '6 tables, 3 triggers, 2 views' as Details;
SELECT 'Default Settings Configured:' as Info, COUNT(*) as SettingsCount FROM business_settings;
SELECT 'Time Slots Available:' as Info, COUNT(*) as SlotsCount FROM time_slots WHERE is_active = TRUE;
SELECT 'Existing Bookings Updated:' as Info, COUNT(*) as BookingsCount FROM bookings WHERE booking_time IS NOT NULL;

-- Show next steps
SELECT 'Next Steps:' as Action, 
       '1. Test the BookingAvailabilityChecker class
        2. Integrate BookingManager with your booking forms
        3. Update frontend to use new API endpoints
        4. Configure business settings as needed' as Instructions;