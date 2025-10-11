<?php
// Database Functions for Car Detailing System
// Include this file in your dashboards to access all database functions

class CarDetailingDB {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    // ==================== BOOKING FUNCTIONS ====================
    
    /**
     * Create a new booking
     */
    public function createBooking($user_id, $service_id, $vehicle_size, $booking_date, $vehicle_details = '', $special_requests = '') {
        try {
            // Get service details
            $service = $this->getService($service_id);
            if (!$service) {
                return ['success' => false, 'message' => 'Service not found'];
            }
            
            // Calculate price based on vehicle size
            $price_column = 'price_' . $vehicle_size;
            $total_amount = $service[$price_column];
            
            $stmt = $this->db->prepare("
                INSERT INTO bookings (user_id, service_id, vehicle_size, booking_date, total_amount, vehicle_details, special_requests, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([$user_id, $service_id, $vehicle_size, $booking_date, $total_amount, $vehicle_details, $special_requests]);
            $booking_id = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($user_id, null, 'booking_created', "New booking created: #{$booking_id}", 'bookings', $booking_id);
            
            // Create notification for admin
            $this->createNotification(null, 'booking', 'New Booking Request', 
                "User has requested a new booking for {$service['service_name']} ({$vehicle_size} vehicle)", $booking_id);
            
            return ['success' => true, 'booking_id' => $booking_id, 'message' => 'Booking created successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error creating booking: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update booking status
     */
    public function updateBookingStatus($booking_id, $status, $admin_notes = '', $admin_id = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?
            ");
            
            $stmt->execute([$status, $admin_notes, $booking_id]);
            
            // Get booking details for notification
            $booking = $this->getBooking($booking_id);
            if ($booking) {
                // Log activity
                $this->logActivity(null, $admin_id, 'booking_status_updated', 
                    "Booking #{$booking_id} status changed to {$status}", 'bookings', $booking_id);
                
                // Notify user
                $message = "Your booking #{$booking_id} has been {$status}";
                if ($admin_notes) {
                    $message .= ". Note: {$admin_notes}";
                }
                
                $this->createNotification($booking['user_id'], 'booking', 'Booking Status Update', $message, $booking_id);
            }
            
            return ['success' => true, 'message' => 'Booking status updated'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error updating booking: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user bookings
     */
    public function getUserBookings($user_id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, s.service_name, s.category, s.description, s.duration_minutes, s.included_items, s.free_items
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                WHERE b.user_id = ?
                ORDER BY b.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get all bookings for admin
     */
    public function getAllBookings($status = null, $limit = 50) {
        try {
            $sql = "
                SELECT 
                    b.booking_id as id,
                    b.*,
                    s.service_name, 
                    s.category, 
                    u.username, 
                    u.first_name, 
                    u.last_name, 
                    u.email,
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON b.user_id = u.id
            ";
            
            $params = [];
            if ($status) {
                $sql .= " WHERE b.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY b.created_at DESC LIMIT " . intval($limit);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $bookings;
            
        } catch (Exception $e) {
            error_log("getAllBookings error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get single booking
     */
    public function getBooking($booking_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, s.service_name, s.description, u.username, u.first_name, u.last_name
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON b.user_id = u.id
                WHERE b.booking_id = ?
            ");
            
            $stmt->execute([$booking_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    // ==================== SERVICE FUNCTIONS ====================
    
    /**
     * Get all active services
     */
    public function getServices() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY category, service_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get single service
     */
    public function getService($service_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM services WHERE service_id = ? AND is_active = 1");
            $stmt->execute([$service_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    // ==================== PAYMENT FUNCTIONS ====================
    
    /**
     * Create payment record
     */
    public function createPayment($booking_id, $user_id, $amount, $payment_method, $transaction_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments (booking_id, user_id, amount, payment_method, transaction_id, payment_status)
                VALUES (?, ?, ?, ?, ?, 'completed')
            ");
            
            $stmt->execute([$booking_id, $user_id, $amount, $payment_method, $transaction_id]);
            $payment_id = $this->db->lastInsertId();
            
            // Update booking payment status
            $this->db->prepare("UPDATE bookings SET payment_status = 'paid' WHERE booking_id = ?")->execute([$booking_id]);
            
            // Log activity
            $this->logActivity($user_id, null, 'payment_completed', 
                "Payment completed for booking #{$booking_id}, Amount: ₱{$amount}", 'payments', $payment_id);
            
            return ['success' => true, 'payment_id' => $payment_id];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user payments
     */
    public function getUserPayments($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, b.booking_id, s.service_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN services s ON b.service_id = s.service_id
                WHERE p.user_id = ?
                ORDER BY p.payment_date DESC
            ");
            
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get all payments for admin
     */
    public function getAllPayments($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, b.booking_id, s.service_name, u.username, u.first_name, u.last_name,
                       p.amount, p.payment_method as method, p.transaction_id as reference, 
                       p.payment_status as status, p.payment_date as created_at
                FROM payments p
                JOIN bookings b ON p.booking_id = b.booking_id
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON p.user_id = u.id
                ORDER BY p.payment_date DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    // ==================== NOTIFICATION FUNCTIONS ====================
    
    /**
     * Create notification
     */
    public function createNotification($user_id, $type, $title, $message, $related_booking_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_booking_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$user_id, $type, $title, $message, $related_booking_id]);
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? OR user_id IS NULL
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get admin notifications
     */
    public function getAdminNotifications($limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                WHERE user_id IS NULL OR type IN ('booking', 'payment', 'system')
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($notification_id) {
        try {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
            $stmt->execute([$notification_id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // ==================== STATISTICS FUNCTIONS ====================
    
    /**
     * Get user statistics
     */
    public function getUserStats($user_id) {
        try {
            $stats = [];
            
            // Total bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stats['total_bookings'] = $stmt->fetchColumn();
            
            // Pending bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            $stats['pending_bookings'] = $stmt->fetchColumn();
            
            // Completed bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'completed'");
            $stmt->execute([$user_id]);
            $stats['completed_bookings'] = $stmt->fetchColumn();
            
            // Total spent
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM payments 
                WHERE user_id = ? AND payment_status = 'completed'
            ");
            $stmt->execute([$user_id]);
            $stats['total_spent'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            return ['total_bookings' => 0, 'pending_bookings' => 0, 'completed_bookings' => 0, 'total_spent' => 0];
        }
    }
    
    /**
     * Get admin statistics
     */
    public function getAdminStats() {
        try {
            $stats = [];
            
            // Total users
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_active = 1");
            $stmt->execute();
            $stats['total_users'] = $stmt->fetchColumn();
            
            // Total bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings");
            $stmt->execute();
            $stats['total_bookings'] = $stmt->fetchColumn();
            
            // Pending bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_bookings'] = $stmt->fetchColumn();
            
            // Completed bookings
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
            $stmt->execute();
            $stats['completed_bookings'] = $stmt->fetchColumn();
            
            // Total revenue
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM payments 
                WHERE payment_status = 'completed'
            ");
            $stmt->execute();
            $stats['total_revenue'] = $stmt->fetchColumn();
            
            // Today's revenue
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) FROM payments 
                WHERE payment_status = 'completed' 
                AND DATE(payment_date) = CURDATE()
            ");
            $stmt->execute();
            $stats['today_revenue'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            return [
                'total_users' => 0, 
                'total_bookings' => 0, 
                'pending_bookings' => 0, 
                'completed_bookings' => 0,
                'total_revenue' => 0,
                'today_revenue' => 0
            ];
        }
    }
    
    // ==================== ACTIVITY LOG FUNCTIONS ====================
    
    /**
     * Log user/admin activity
     */
    public function logActivity($user_id, $admin_id, $action, $description, $related_table = null, $related_id = null) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (user_id, admin_id, action, description, related_table, related_id, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$user_id, $admin_id, $action, $description, $related_table, $related_id, $ip_address]);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get recent activity logs
     */
    public function getRecentActivity($limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT al.*, u.username as user_name, a.username as admin_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN users a ON al.admin_id = a.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
}
?>