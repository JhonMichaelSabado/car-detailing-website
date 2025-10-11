-- Fix for the minor integration issues
-- Run this script to complete the integration

-- Fix the booking_conflicts table
DROP TABLE IF EXISTS booking_conflicts;
CREATE TABLE booking_conflicts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    conflict_type ENUM('time_overlap', 'daily_limit', 'travel_buffer', 'business_hours') NOT NULL,
    conflict_details JSON,
    attempted_booking_data JSON,
    existing_booking_data JSON,
    resolved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_booking_date (booking_date),
    INDEX idx_conflict_type (conflict_type),
    INDEX idx_resolved (resolved)
);

-- Recreate the views with correct column references
DROP VIEW IF EXISTS todays_booking_schedule;
CREATE VIEW todays_booking_schedule AS
SELECT 
    b.booking_id as id,
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

-- Add sample data for testing
INSERT IGNORE INTO daily_availability (available_date, current_bookings, max_bookings) VALUES
(CURDATE(), 0, 2),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 0, 2),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1, 2),
(DATE_ADD(CURDATE(), INTERVAL 3 DAY), 0, 2);

SELECT 'Integration fixes applied successfully!' as Status;