<?php
/**
 * Booking Availability Checker Class
 * Handles all time slot logic for the car detailing booking system
 */

class BookingAvailabilityChecker {
    private $db;
    private $travel_buffer_minutes = 60; // 1 hour default
    private $max_bookings_per_day = 2;
    private $business_hours_start = '08:00:00';
    private $business_hours_end = '18:00:00';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if a time slot is available for booking
     * @param string $date Date in Y-m-d format
     * @param string $start_time Time in H:i:s format
     * @param string $end_time Time in H:i:s format
     * @param int $exclude_booking_id Booking ID to exclude from conflict check
     * @return array Result with availability status and reason
     */
    public function isTimeSlotAvailable($date, $start_time, $end_time, $exclude_booking_id = null) {
        try {
            // Validate business hours
            if (!$this->isWithinBusinessHours($start_time, $end_time)) {
                return [
                    'available' => false,
                    'reason' => 'Time slot is outside business hours (8:00 AM - 6:00 PM)',
                    'error_code' => 'OUTSIDE_BUSINESS_HOURS'
                ];
            }
            
            // Check if date is in the past
            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                return [
                    'available' => false,
                    'reason' => 'Cannot book for past dates',
                    'error_code' => 'PAST_DATE'
                ];
            }
            
            // First check: Are there already 2 accepted bookings on this date?
            $daily_count = $this->getDailyAcceptedBookingsCount($date);
            if ($daily_count >= $this->max_bookings_per_day) {
                return [
                    'available' => false,
                    'reason' => 'Date is fully booked (2 bookings maximum per day)',
                    'error_code' => 'FULLY_BOOKED',
                    'current_bookings' => $daily_count
                ];
            }
            
            // Second check: Check for time conflicts with travel buffer
            $conflicts = $this->checkTimeConflicts($date, $start_time, $end_time, $exclude_booking_id);
            
            if (!empty($conflicts)) {
                return [
                    'available' => false,
                    'reason' => 'Time slot conflicts with existing booking (including travel time)',
                    'error_code' => 'TIME_CONFLICT',
                    'conflicts' => $conflicts
                ];
            }
            
            return [
                'available' => true,
                'reason' => 'Time slot is available',
                'error_code' => 'AVAILABLE'
            ];
            
        } catch (Exception $e) {
            error_log("BookingAvailabilityChecker Error: " . $e->getMessage());
            return [
                'available' => false,
                'reason' => 'System error checking availability',
                'error_code' => 'SYSTEM_ERROR'
            ];
        }
    }
    
    /**
     * Check if time is within business hours
     */
    private function isWithinBusinessHours($start_time, $end_time) {
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        $business_start = strtotime($this->business_hours_start);
        $business_end = strtotime($this->business_hours_end);
        
        return ($start >= $business_start && $end <= $business_end);
    }
    
    /**
     * Get count of accepted bookings for a specific date
     */
    public function getDailyAcceptedBookingsCount($date) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE DATE(booking_date) = ? AND status = 'confirmed'
        ");
        $stmt->execute([$date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Check for time conflicts including travel buffer
     */
    private function checkTimeConflicts($date, $start_time, $end_time, $exclude_booking_id = null) {
        $exclude_clause = $exclude_booking_id ? "AND booking_id != ?" : "";
        $params = [$this->travel_buffer_minutes, $this->travel_buffer_minutes, $date];
        
        if ($exclude_booking_id) {
            $params[] = $exclude_booking_id;
        }
        
        $stmt = $this->db->prepare("
            SELECT booking_id, booking_time as start_time, 
                   ADDTIME(booking_time, SEC_TO_TIME(estimated_duration * 60)) as end_time,
                   SUBTIME(booking_time, SEC_TO_TIME(? * 60)) as buffer_start,
                   ADDTIME(ADDTIME(booking_time, SEC_TO_TIME(estimated_duration * 60)), SEC_TO_TIME(? * 60)) as buffer_end,
                   CONCAT(u.first_name, ' ', u.last_name) as customer_name
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE DATE(booking_date) = ? AND status = 'confirmed' 
            $exclude_clause
        ");
        
        $stmt->execute($params);
        $existing_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $conflicts = [];
        foreach ($existing_bookings as $booking) {
            // Check if new booking overlaps with existing booking + buffer
            if ($this->timeRangesOverlap(
                $start_time, $end_time,
                $booking['buffer_start'], $booking['buffer_end']
            )) {
                $conflicts[] = [
                    'booking_id' => $booking['booking_id'],
                    'customer_name' => $booking['customer_name'],
                    'time_range' => $booking['start_time'] . ' - ' . $booking['end_time'],
                    'buffer_range' => $booking['buffer_start'] . ' - ' . $booking['buffer_end']
                ];
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check if two time ranges overlap
     */
    private function timeRangesOverlap($start1, $end1, $start2, $end2) {
        return (strtotime($start1) < strtotime($end2)) && (strtotime($end1) > strtotime($start2));
    }
    
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableTimeSlots($date, $service_duration_hours = 4) {
        try {
            // Check if date is in the past
            if (strtotime($date) < strtotime(date('Y-m-d'))) {
                return [];
            }
            
            // Check weekend policy
            $day_of_week = date('w', strtotime($date)); // 0 = Sunday, 6 = Saturday
            if (($day_of_week == 0 || $day_of_week == 6) && !$this->isWeekendBookingEnabled()) {
                return [];
            }
            
            // Check advance booking limit
            $advance_days = $this->getAdvanceBookingDays();
            $max_advance_date = date('Y-m-d', strtotime("+{$advance_days} days"));
            if (strtotime($date) > strtotime($max_advance_date)) {
                return [];
            }
            
            // If date is fully booked, return empty array
            if ($this->getDailyAcceptedBookingsCount($date) >= $this->max_bookings_per_day) {
                return [];
            }
            
            // Get all confirmed bookings for this date with buffer
            $stmt = $this->db->prepare("
                SELECT booking_time as start_time, 
                       ADDTIME(booking_time, SEC_TO_TIME(estimated_duration * 60)) as end_time,
                       SUBTIME(booking_time, SEC_TO_TIME(? * 60)) as buffer_start,
                       ADDTIME(ADDTIME(booking_time, SEC_TO_TIME(estimated_duration * 60)), SEC_TO_TIME(? * 60)) as buffer_end
                FROM bookings 
                WHERE DATE(booking_date) = ? AND status = 'confirmed'
                ORDER BY booking_time
            ");
            $stmt->execute([$this->travel_buffer_minutes, $this->travel_buffer_minutes, $date]);
            $blocked_ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate available slots based on predefined templates
            return $this->generateAvailableSlots($blocked_ranges, $service_duration_hours);
            
        } catch (Exception $e) {
            error_log("Error getting available time slots: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate available slots based on blocked ranges
     */
    private function generateAvailableSlots($blocked_ranges, $service_duration_hours) {
        // Get predefined time slots
        $stmt = $this->db->prepare("
            SELECT slot_time, max_duration, slot_type 
            FROM time_slots 
            WHERE is_active = 1 
            ORDER BY slot_time
        ");
        $stmt->execute();
        $slot_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $available_slots = [];
        
        foreach ($slot_templates as $template) {
            $slot_start = $template['slot_time'];
            $slot_duration_minutes = min($template['max_duration'], $service_duration_hours * 60);
            $slot_end = date('H:i:s', strtotime($slot_start) + ($slot_duration_minutes * 60));
            
            // Check business hours
            if (!$this->isWithinBusinessHours($slot_start, $slot_end)) {
                continue;
            }
            
            // Check if this slot conflicts with any blocked ranges
            $is_blocked = false;
            foreach ($blocked_ranges as $blocked) {
                if ($this->timesOverlap($slot_start, $slot_end, $blocked['buffer_start'], $blocked['buffer_end'])) {
                    $is_blocked = true;
                    break;
                }
            }
            
            if (!$is_blocked) {
                $available_slots[] = [
                    'start_time' => $slot_start,
                    'end_time' => $slot_end,
                    'duration_minutes' => $slot_duration_minutes,
                    'slot_type' => $template['slot_type']
                ];
            }
        }
        
        return $available_slots;
    }
    
    /**
     * Check if two time ranges overlap
     */
    private function timesOverlap($start1, $end1, $start2, $end2) {
        return (strtotime($start1) < strtotime($end2)) && (strtotime($end1) > strtotime($start2));
    }
    
    /**
     * Get unavailable dates for calendar display
     */
    public function getUnavailableDates($start_date, $end_date) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT DATE(booking_date) as booking_date
            FROM bookings 
            WHERE DATE(booking_date) BETWEEN ? AND ? 
            AND status = 'confirmed'
            GROUP BY DATE(booking_date) 
            HAVING COUNT(*) >= ?
        ");
        $stmt->execute([$start_date, $end_date, $this->max_bookings_per_day]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get booking summary for a specific date
     */
    public function getDateBookingSummary($date) {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_bookings,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
            FROM bookings 
            WHERE booking_date = ?
        ");
        $stmt->execute([$date]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $summary['is_fully_booked'] = $summary['accepted_bookings'] >= $this->max_bookings_per_day;
        $summary['available_slots'] = $this->max_bookings_per_day - $summary['accepted_bookings'];
        
        return $summary;
    }
    
    /**
     * Check if weekend bookings are enabled
     */
    private function isWeekendBookingEnabled() {
        $stmt = $this->db->prepare("SELECT setting_value FROM business_settings WHERE setting_key = 'weekend_bookings_enabled'");
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result === 'true';
    }
    
    /**
     * Get advance booking days limit
     */
    private function getAdvanceBookingDays() {
        $stmt = $this->db->prepare("SELECT setting_value FROM business_settings WHERE setting_key = 'advance_booking_days'");
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return (int)$result ?: 30;
    }
}
?>