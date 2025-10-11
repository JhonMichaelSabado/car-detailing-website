<?php
/**
 * Booking Manager Class
 * Handles all booking operations for the car detailing system
 */

require_once 'BookingAvailabilityChecker.php';

class BookingManager {
    private $db;
    private $availability_checker;
    
    public function __construct($database) {
        $this->db = $database;
        $this->availability_checker = new BookingAvailabilityChecker($database);
    }
    
    /**
     * Create a new booking (always starts as pending)
     */
    public function createBooking($data) {
        try {
            // Validate required fields
            $required_fields = ['customer_id', 'service_id', 'date', 'start_time', 'end_time', 'customer_address'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required field: $field"
                    ];
                }
            }
            
            // Extract data
            $customer_id = $data['customer_id'];
            $service_id = $data['service_id'];
            $date = $data['date'];
            $start_time = $data['start_time'];
            $end_time = $data['end_time'];
            $customer_address = $data['customer_address'];
            $vehicle_size = $data['vehicle_size'] ?? 'medium';
            $special_requests = $data['special_requests'] ?? '';
            $vehicle_details = $data['vehicle_details'] ?? '';
            
            // Get service details and calculate total amount
            $service = $this->getServiceDetails($service_id);
            if (!$service) {
                return [
                    'success' => false,
                    'message' => 'Invalid service selected'
                ];
            }
            
            // Calculate total based on vehicle size
            $price_column = 'price_' . $vehicle_size;
            $total_amount = $service[$price_column];
            
            // Check availability first
            $availability = $this->availability_checker->isTimeSlotAvailable($date, $start_time, $end_time);
            
            if (!$availability['available']) {
                // Log the conflict attempt
                $this->logBookingConflict($date, $start_time, $end_time, $availability['error_code'], $customer_id);
                
                return [
                    'success' => false,
                    'message' => $availability['reason'],
                    'error_code' => $availability['error_code']
                ];
            }
            
            // Create the booking
            $stmt = $this->db->prepare("
                INSERT INTO bookings (
                    user_id, service_id, vehicle_size, booking_date, start_time, end_time, 
                    total_amount, customer_address, vehicle_details, special_requests, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $result = $stmt->execute([
                $customer_id, $service_id, $vehicle_size, $date, $start_time, 
                $end_time, $total_amount, $customer_address, $vehicle_details, $special_requests
            ]);
            
            if ($result) {
                $booking_id = $this->db->lastInsertId();
                
                // Create notification for admin
                $this->createNotification([
                    'type' => 'booking',
                    'title' => 'New Booking Request',
                    'message' => "New booking request from customer for " . date('M d, Y', strtotime($date)),
                    'related_booking_id' => $booking_id
                ]);
                
                // Log the activity
                $this->logActivity($customer_id, 'booking_created', "Created new booking #$booking_id for $date");
                
                return [
                    'success' => true,
                    'booking_id' => $booking_id,
                    'message' => 'Booking created successfully. Waiting for admin approval.',
                    'total_amount' => $total_amount,
                    'service_name' => $service['service_name']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error creating booking in database'
                ];
            }
            
        } catch (Exception $e) {
            error_log("BookingManager::createBooking Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error creating booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Admin accepts a booking
     */
    public function acceptBooking($booking_id, $admin_id, $admin_notes = '') {
        try {
            // Get booking details
            $booking = $this->getBookingById($booking_id);
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }
            
            if ($booking['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Booking is not in pending status'];
            }
            
            // Check if still available (in case multiple admins or time passed)
            $availability = $this->availability_checker->isTimeSlotAvailable(
                $booking['booking_date'], 
                $booking['start_time'], 
                $booking['end_time'],
                $booking_id // Exclude this booking from conflict check
            );
            
            if (!$availability['available']) {
                return [
                    'success' => false,
                    'message' => 'Cannot accept: ' . $availability['reason'],
                    'error_code' => $availability['error_code']
                ];
            }
            
            // Update booking status
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'accepted', admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?
            ");
            $result = $stmt->execute([$admin_notes, $booking_id]);
            
            if ($result) {
                // Create notification for customer
                $this->createNotification([
                    'user_id' => $booking['user_id'],
                    'type' => 'booking',
                    'title' => 'Booking Confirmed',
                    'message' => "Your booking for " . date('M d, Y', strtotime($booking['booking_date'])) . " has been confirmed!",
                    'related_booking_id' => $booking_id
                ]);
                
                // Log the activity
                $this->logActivity($admin_id, 'booking_accepted', "Accepted booking #$booking_id", $booking_id);
                
                return [
                    'success' => true,
                    'message' => 'Booking accepted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error updating booking status'
                ];
            }
            
        } catch (Exception $e) {
            error_log("BookingManager::acceptBooking Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error accepting booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Admin rejects a booking
     */
    public function rejectBooking($booking_id, $admin_id, $rejection_reason = '') {
        try {
            $booking = $this->getBookingById($booking_id);
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'rejected', admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?
            ");
            $result = $stmt->execute([$rejection_reason, $booking_id]);
            
            if ($result) {
                // Create notification for customer
                $this->createNotification([
                    'user_id' => $booking['user_id'],
                    'type' => 'booking',
                    'title' => 'Booking Rejected',
                    'message' => "Your booking for " . date('M d, Y', strtotime($booking['booking_date'])) . " has been rejected. Reason: $rejection_reason",
                    'related_booking_id' => $booking_id
                ]);
                
                // Log the activity
                $this->logActivity($admin_id, 'booking_rejected', "Rejected booking #$booking_id: $rejection_reason", $booking_id);
                
                return [
                    'success' => true,
                    'message' => 'Booking rejected successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error updating booking status'
                ];
            }
            
        } catch (Exception $e) {
            error_log("BookingManager::rejectBooking Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error rejecting booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a booking (customer or admin can cancel)
     */
    public function cancelBooking($booking_id, $user_id, $cancellation_reason = '') {
        try {
            $booking = $this->getBookingById($booking_id);
            if (!$booking) {
                return ['success' => false, 'message' => 'Booking not found'];
            }
            
            // Check if user has permission to cancel this booking
            if ($booking['user_id'] != $user_id && !$this->isAdmin($user_id)) {
                return ['success' => false, 'message' => 'You do not have permission to cancel this booking'];
            }
            
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'cancelled', admin_notes = CONCAT(COALESCE(admin_notes, ''), '\nCancelled: ', ?), updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?
            ");
            $result = $stmt->execute([$cancellation_reason, $booking_id]);
            
            if ($result) {
                // Create appropriate notification
                if ($this->isAdmin($user_id)) {
                    // Admin cancelled - notify customer
                    $this->createNotification([
                        'user_id' => $booking['user_id'],
                        'type' => 'booking',
                        'title' => 'Booking Cancelled',
                        'message' => "Your booking for " . date('M d, Y', strtotime($booking['booking_date'])) . " has been cancelled. Reason: $cancellation_reason",
                        'related_booking_id' => $booking_id
                    ]);
                } else {
                    // Customer cancelled - notify admin
                    $this->createNotification([
                        'type' => 'booking',
                        'title' => 'Booking Cancelled by Customer',
                        'message' => "Customer cancelled booking for " . date('M d, Y', strtotime($booking['booking_date'])) . ". Reason: $cancellation_reason",
                        'related_booking_id' => $booking_id
                    ]);
                }
                
                // Log the activity
                $this->logActivity($user_id, 'booking_cancelled', "Cancelled booking #$booking_id: $cancellation_reason", $booking_id);
                
                return [
                    'success' => true,
                    'message' => 'Booking cancelled successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error cancelling booking'
                ];
            }
            
        } catch (Exception $e) {
            error_log("BookingManager::cancelBooking Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'System error cancelling booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get booking by ID with related data
     */
    public function getBookingById($booking_id) {
        $stmt = $this->db->prepare("
            SELECT b.*, u.username, u.email, s.service_name, s.category 
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN services s ON b.service_id = s.service_id
            WHERE b.booking_id = ?
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get bookings by status
     */
    public function getBookingsByStatus($status, $limit = null) {
        $limit_clause = $limit ? "LIMIT $limit" : "";
        $stmt = $this->db->prepare("
            SELECT b.*, u.username as customer_name, u.email, s.service_name, s.category 
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN services s ON b.service_id = s.service_id
            WHERE b.status = ?
            ORDER BY b.created_at DESC
            $limit_clause
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get user's bookings
     */
    public function getUserBookings($user_id, $limit = null) {
        $limit_clause = $limit ? "LIMIT $limit" : "";
        $stmt = $this->db->prepare("
            SELECT b.*, s.service_name, s.category 
            FROM bookings b
            LEFT JOIN services s ON b.service_id = s.service_id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC, b.created_at DESC
            $limit_clause
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get today's bookings
     */
    public function getTodayBookings() {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT b.*, u.username as customer_name, u.phone, s.service_name 
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN services s ON b.service_id = s.service_id
            WHERE DATE(b.booking_date) = ? AND b.status = 'confirmed'
            ORDER BY b.booking_time
        ");
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get service details
     */
    private function getServiceDetails($service_id) {
        $stmt = $this->db->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->execute([$service_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin($user_id) {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Create notification
     */
    private function createNotification($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, admin_id, type, title, message, related_booking_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_id'] ?? null,
                $data['admin_id'] ?? null,
                $data['type'],
                $data['title'],
                $data['message'],
                $data['related_booking_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
        }
    }
    
    /**
     * Log activity
     */
    private function logActivity($user_id, $action, $description, $related_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, action, description, related_table, related_id, ip_address) 
                VALUES (?, ?, ?, 'bookings', ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $action,
                $description,
                $related_id,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    /**
     * Log booking conflict for analysis
     */
    private function logBookingConflict($date, $start_time, $end_time, $reason, $customer_id) {
        try {
            // Map error codes to conflict reasons
            $conflict_reason_map = [
                'FULLY_BOOKED' => 'max_bookings_exceeded',
                'TIME_CONFLICT' => 'time_overlap',
                'OUTSIDE_BUSINESS_HOURS' => 'travel_buffer_conflict'
            ];
            
            $conflict_reason = $conflict_reason_map[$reason] ?? 'time_overlap';
            
            $stmt = $this->db->prepare("
                INSERT INTO booking_conflicts (attempted_booking_date, attempted_start_time, attempted_end_time, conflict_reason, customer_id) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$date, $start_time, $end_time, $conflict_reason, $customer_id]);
        } catch (Exception $e) {
            error_log("Error logging booking conflict: " . $e->getMessage());
        }
    }
    
    /**
     * Get weekly bookings count
     */
    public function getWeeklyBookingsCount() {
        $start_of_week = date('Y-m-d', strtotime('monday this week'));
        $end_of_week = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE booking_date BETWEEN ? AND ? AND status = 'accepted'
        ");
        $stmt->execute([$start_of_week, $end_of_week]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    /**
     * Get availability checker instance
     */
    public function getAvailabilityChecker() {
        return $this->availability_checker;
    }
}
?>