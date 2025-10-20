<?php
// Email notification functions for the booking system
require_once '../includes/config.php';

class NotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($booking_id) {
        try {
            // Get booking details
            $stmt = $this->pdo->prepare("
                SELECT b.*, s.service_name, s.category, u.first_name, u.last_name, u.email
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            $subject = "Booking Confirmed - " . $booking['booking_reference'];
            $message = $this->getConfirmationEmailTemplate($booking);
            
            // Send email
            $this->sendEmail($booking['email'], $subject, $message);
            
            // Update notification sent flag
            $this->pdo->prepare("UPDATE bookings SET confirmation_email_sent = 1 WHERE booking_id = ?")
                     ->execute([$booking_id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send booking rejection email
     */
    public function sendBookingRejection($booking_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT b.*, s.service_name, u.first_name, u.last_name, u.email
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $subject = "Booking Update - " . $booking['booking_reference'];
            $message = $this->getRejectionEmailTemplate($booking);
            
            $this->sendEmail($booking['email'], $subject, $message);
            return true;
            
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMS notification (placeholder for SMS service integration)
     */
    public function sendSMS($phone, $message) {
        // Integrate with SMS service provider (e.g., Semaphore, Globe, Smart)
        // This is a placeholder implementation
        
        try {
            // SMS API integration would go here
            // For now, we'll just log the SMS
            error_log("SMS to $phone: $message");
            return true;
        } catch (Exception $e) {
            error_log("SMS error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using PHP mail() or SMTP
     */
    private function sendEmail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: CarDetailing Pro <noreply@cardetailing.com>',
            'Reply-To: support@cardetailing.com',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // For production, use a proper SMTP service
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Get booking confirmation email template
     */
    private function getConfirmationEmailTemplate($booking) {
        $pay_now = $booking['payment_mode'] === 'deposit_50' ? $booking['deposit_amount'] : $booking['total_amount'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .total-amount { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                .btn { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin: 10px 0; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Booking Confirmed!</h1>
                    <p>Your car detailing service has been approved</p>
                </div>
                
                <div class='content'>
                    <p>Dear " . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . ",</p>
                    
                    <p>Great news! Your booking has been confirmed by our team. Here are your booking details:</p>
                    
                    <div class='booking-details'>
                        <h3>Booking Information</h3>
                        <div class='detail-row'>
                            <span>Booking Reference:</span>
                            <strong>" . htmlspecialchars($booking['booking_reference']) . "</strong>
                        </div>
                        <div class='detail-row'>
                            <span>Service:</span>
                            <span>" . htmlspecialchars($booking['service_name']) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span>Vehicle Size:</span>
                            <span>" . ucfirst($booking['vehicle_size']) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span>Date:</span>
                            <span>" . date('l, F j, Y', strtotime($booking['booking_date'])) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span>Time:</span>
                            <span>" . date('g:i A', strtotime($booking['booking_time'])) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span>Location:</span>
                            <span>" . htmlspecialchars($booking['service_address']) . "</span>
                        </div>
                    </div>
                    
                    <div class='total-amount'>
                        <h3>Amount to Pay Now</h3>
                        <h2>₱" . number_format($pay_now, 2) . "</h2>
                        <p>" . ($booking['payment_mode'] === 'deposit_50' ? '50% Deposit' : 'Full Payment') . "</p>
                    </div>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/car-detailing/user/payment.php?booking=" . $booking['booking_id'] . "' class='btn'>
                            Complete Payment Now
                        </a>
                    </div>
                    
                    <h3>What's Next?</h3>
                    <ol>
                        <li>Click the button above to complete your payment</li>
                        <li>You'll receive a receipt once payment is processed</li>
                        <li>Our team will arrive at your location on the scheduled date</li>
                        <li>Enjoy your professionally detailed vehicle!</li>
                    </ol>
                    
                    <p><strong>Important:</strong> Please complete your payment within 24 hours to secure your booking.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>CarDetailing Pro</strong></p>
                    <p>📞 +63 (2) 123-4567 | 📧 support@cardetailing.com</p>
                    <p>Professional car detailing services at your doorstep</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get booking rejection email template
     */
    private function getRejectionEmailTemplate($booking) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin: 10px 0; }
                .footer { background: #2c3e50; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📋 Booking Update</h1>
                    <p>Regarding your booking request</p>
                </div>
                
                <div class='content'>
                    <p>Dear " . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . ",</p>
                    
                    <p>We regret to inform you that your booking request #" . htmlspecialchars($booking['booking_reference']) . " could not be approved.</p>
                    
                    <div class='booking-details'>
                        <h3>Reason for Rejection</h3>
                        <p>" . htmlspecialchars($booking['rejection_reason'] ?: 'Unfortunately, we cannot accommodate your booking at this time.') . "</p>
                        
                        " . ($booking['admin_notes'] ? "<p><strong>Additional Notes:</strong><br>" . htmlspecialchars($booking['admin_notes']) . "</p>" : "") . "
                    </div>
                    
                    <p>We sincerely apologize for any inconvenience this may cause. We'd be happy to help you reschedule or find an alternative solution.</p>
                    
                    <div style='text-align: center;'>
                        <a href='http://localhost/car-detailing/user/booking/step1_service_selection.php' class='btn'>
                            Book Again
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>CarDetailing Pro</strong></p>
                    <p>📞 +63 (2) 123-4567 | 📧 support@cardetailing.com</p>
                    <p>We appreciate your understanding</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Create in-app notification
     */
    public function createNotification($user_id, $type, $title, $message, $booking_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_booking_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$user_id, $type, $title, $message, $booking_id]);
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send reminder notifications
     */
    public function sendReminders() {
        try {
            // Send reminders for bookings 24 hours before service
            $stmt = $this->pdo->prepare("
                SELECT b.*, u.email, u.first_name, u.last_name
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.status = 'confirmed' 
                AND DATE(b.booking_date) = DATE(DATE_ADD(NOW(), INTERVAL 1 DAY))
                AND b.reminder_sent = 0
            ");
            $stmt->execute();
            $tomorrow_bookings = $stmt->fetchAll();
            
            foreach ($tomorrow_bookings as $booking) {
                $this->sendServiceReminder($booking);
                
                // Mark reminder as sent
                $this->pdo->prepare("UPDATE bookings SET reminder_sent = 1 WHERE booking_id = ?")
                         ->execute([$booking['booking_id']]);
            }
            
            return count($tomorrow_bookings);
            
        } catch (Exception $e) {
            error_log("Reminder sending error: " . $e->getMessage());
            return 0;
        }
    }
    
    private function sendServiceReminder($booking) {
        $subject = "Service Reminder - Tomorrow at " . date('g:i A', strtotime($booking['booking_time']));
        $message = "
        <h2>🚗 Service Reminder</h2>
        <p>Dear " . htmlspecialchars($booking['first_name']) . ",</p>
        <p>This is a friendly reminder that your car detailing service is scheduled for tomorrow:</p>
        <p><strong>Date:</strong> " . date('l, F j, Y', strtotime($booking['booking_date'])) . "<br>
        <strong>Time:</strong> " . date('g:i A', strtotime($booking['booking_time'])) . "<br>
        <strong>Service:</strong> " . htmlspecialchars($booking['service_name']) . "</p>
        <p>Our team will arrive at your location on time. Please ensure your vehicle is accessible.</p>
        ";
        
        $this->sendEmail($booking['email'], $subject, $message);
    }
}

// Auto-trigger notifications based on booking status changes
function triggerBookingNotification($booking_id, $status, $pdo) {
    $notificationService = new NotificationService($pdo);
    
    switch ($status) {
        case 'confirmed':
            $notificationService->sendBookingConfirmation($booking_id);
            break;
        case 'rejected':
            $notificationService->sendBookingRejection($booking_id);
            break;
    }
}
?>