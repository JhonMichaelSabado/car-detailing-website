-- Enhanced Payment System Database Updates
-- Run this to add support for payment proof uploads and enhanced tracking

-- Add new columns to payments table for enhanced features
ALTER TABLE payments 
ADD COLUMN IF NOT EXISTS payment_proof_path VARCHAR(255) NULL COMMENT 'Path to uploaded payment proof image',
ADD COLUMN IF NOT EXISTS payment_type ENUM('partial', 'full') DEFAULT 'partial' COMMENT 'Type of payment: partial or full',
ADD COLUMN IF NOT EXISTS verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' COMMENT 'Admin verification status',
ADD COLUMN IF NOT EXISTS verified_by INT NULL COMMENT 'Admin who verified the payment',
ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL COMMENT 'When payment was verified',
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL COMMENT 'Reason if payment was rejected';

-- Add foreign key for verified_by
ALTER TABLE payments 
ADD CONSTRAINT fk_payments_verified_by 
FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing payments to have default values
UPDATE payments 
SET payment_type = 'partial', verification_status = 'verified' 
WHERE payment_type IS NULL;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_payments_verification_status ON payments(verification_status);
CREATE INDEX IF NOT EXISTS idx_payments_type ON payments(payment_type);
CREATE INDEX IF NOT EXISTS idx_payments_proof_path ON payments(payment_proof_path);

-- Add booking status tracking
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' COMMENT 'Payment verification status';

-- Create payment_logs table for audit trail
CREATE TABLE IF NOT EXISTS payment_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    action ENUM('created', 'verified', 'rejected', 'updated') NOT NULL,
    performed_by INT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert initial log entries for existing payments
INSERT IGNORE INTO payment_logs (payment_id, action, details, created_at)
SELECT payment_id, 'created', 'Payment created', payment_date
FROM payments 
WHERE payment_id NOT IN (SELECT DISTINCT payment_id FROM payment_logs WHERE action = 'created');

-- Create admin payment verification view
CREATE OR REPLACE VIEW admin_payment_verification AS
SELECT 
    p.payment_id,
    p.booking_id,
    b.booking_date,
    u.username,
    u.first_name,
    u.last_name,
    s.service_name,
    p.amount,
    p.payment_method,
    p.payment_type,
    p.transaction_id,
    p.payment_proof_path,
    p.verification_status,
    p.payment_date,
    CASE 
        WHEN p.payment_proof_path IS NOT NULL THEN 'Yes'
        ELSE 'No'
    END as has_proof
FROM payments p
JOIN bookings b ON p.booking_id = b.booking_id
JOIN users u ON p.user_id = u.id
JOIN services s ON b.service_id = s.service_id
WHERE p.verification_status = 'pending'
ORDER BY p.payment_date DESC;

-- Add sample notification for admin
INSERT IGNORE INTO notifications (user_id, type, title, message, created_at)
VALUES (NULL, 'system', 'Payment System Enhanced', 
        'Enhanced payment system with proof upload and verification is now active. Check pending verifications in admin panel.', 
        NOW());

COMMIT;