-- Final fix for integration with your actual database structure
-- This adapts the booking system to work with your existing users and bookings tables

-- Drop and recreate the view with correct column references
DROP VIEW IF EXISTS todays_booking_schedule;
CREATE VIEW todays_booking_schedule AS
SELECT 
    b.booking_id as id,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
    u.phone as customer_phone,
    u.email as customer_email,
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
    s.service_name as service_type,
    b.is_premium,
    b.total_amount,
    b.vehicle_size
FROM bookings b
JOIN users u ON b.user_id = u.id
LEFT JOIN services s ON b.service_id = s.service_id
WHERE DATE(b.booking_date) = CURDATE()
    AND b.status IN ('confirmed', 'pending', 'in_progress')
ORDER BY b.booking_time;

-- Create a view for booking availability overview that works with your structure
DROP VIEW IF EXISTS booking_availability_overview;
CREATE VIEW booking_availability_overview AS
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

-- Update the triggers to work with your booking_date column (which is datetime, not date)
DROP TRIGGER IF EXISTS update_daily_availability_on_booking_insert;
DELIMITER //
CREATE TRIGGER update_daily_availability_on_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    INSERT INTO daily_availability (available_date, current_bookings)
    VALUES (DATE(NEW.booking_date), 1)
    ON DUPLICATE KEY UPDATE current_bookings = current_bookings + 1;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS update_daily_availability_on_booking_update;
DELIMITER //
CREATE TRIGGER update_daily_availability_on_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        IF NEW.status = 'confirmed' AND OLD.status != 'confirmed' THEN
            INSERT INTO daily_availability (available_date, current_bookings)
            VALUES (DATE(NEW.booking_date), 1)
            ON DUPLICATE KEY UPDATE current_bookings = current_bookings + 1;
        ELSEIF OLD.status = 'confirmed' AND NEW.status != 'confirmed' THEN
            UPDATE daily_availability 
            SET current_bookings = GREATEST(0, current_bookings - 1)
            WHERE available_date = DATE(NEW.booking_date);
        END IF;
    END IF;
END //
DELIMITER ;

DROP TRIGGER IF EXISTS update_daily_availability_on_booking_delete;
DELIMITER //
CREATE TRIGGER update_daily_availability_on_booking_delete
AFTER DELETE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.status = 'confirmed' THEN
        UPDATE daily_availability 
        SET current_bookings = GREATEST(0, current_bookings - 1)
        WHERE available_date = DATE(OLD.booking_date);
    END IF;
END //
DELIMITER ;

-- Initialize daily availability based on existing confirmed bookings
INSERT IGNORE INTO daily_availability (available_date, current_bookings)
SELECT 
    DATE(booking_date) as booking_date,
    COUNT(*) as current_bookings
FROM bookings 
WHERE status = 'confirmed'
    AND DATE(booking_date) >= CURDATE()
GROUP BY DATE(booking_date);

-- Check integration status
SELECT 'Integration completed successfully!' as Status;
SELECT 'Views fixed for your database structure' as Info;
SELECT 'Triggers updated to work with datetime booking_date' as Info;
SELECT COUNT(*) as 'Confirmed Bookings Tracked' FROM daily_availability WHERE current_bookings > 0;