-- Car Detailing System - ERD SQL Schema
-- This script shows the complete database structure for ERD creation

-- ==========================================
-- 1. USERS TABLE (Central Entity)
-- ==========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(255) UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    address TEXT,
    date_of_birth DATE,
    profile_picture VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ==========================================
-- 2. SERVICES TABLE
-- ==========================================
CREATE TABLE services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    price_small DECIMAL(10,2) NOT NULL,
    price_medium DECIMAL(10,2) NOT NULL,
    price_large DECIMAL(10,2) NOT NULL,
    duration_minutes INT DEFAULT 60,
    included_items TEXT,
    free_items TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 3. BOOKINGS TABLE (Junction Entity)
-- ==========================================
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    vehicle_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    booking_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'declined') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    payment_verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    vehicle_details TEXT,
    special_requests TEXT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- ==========================================
-- 4. PAYMENTS TABLE
-- ==========================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'gcash', 'bank_transfer') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_type ENUM('partial', 'full') DEFAULT 'partial',
    transaction_id VARCHAR(100),
    payment_proof_path VARCHAR(255),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    -- Foreign Key Constraints
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================
-- 5. REVIEWS TABLE
-- ==========================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    
    -- Unique constraint - one review per booking
    UNIQUE KEY unique_booking_review (booking_id)
);

-- ==========================================
-- 6. NOTIFICATIONS TABLE
-- ==========================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL for admin notifications
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT, -- Generic reference to booking/payment/etc
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- 7. ACTIVITY_LOGS TABLE (Audit Trail)
-- ==========================================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    admin_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    table_name VARCHAR(50),
    record_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================
-- 8. PAYMENT_LOGS TABLE (Payment Audit)
-- ==========================================
CREATE TABLE payment_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    action ENUM('created', 'verified', 'rejected', 'updated') NOT NULL,
    performed_by INT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ==========================================
-- 9. ADMIN PAYMENT VERIFICATION VIEW
-- ==========================================
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

-- ==========================================
-- INDEXES FOR PERFORMANCE
-- ==========================================

-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_active ON users(is_active);

-- Bookings table indexes
CREATE INDEX idx_bookings_user_id ON bookings(user_id);
CREATE INDEX idx_bookings_service_id ON bookings(service_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_date ON bookings(booking_date);

-- Payments table indexes
CREATE INDEX idx_payments_booking_id ON payments(booking_id);
CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_payments_status ON payments(payment_status);
CREATE INDEX idx_payments_verification ON payments(verification_status);
CREATE INDEX idx_payments_method ON payments(payment_method);

-- Reviews table indexes
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_reviews_service_id ON reviews(service_id);
CREATE INDEX idx_reviews_approved ON reviews(is_approved);

-- Activity logs indexes
CREATE INDEX idx_activity_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_admin_id ON activity_logs(admin_id);
CREATE INDEX idx_activity_action ON activity_logs(action);
CREATE INDEX idx_activity_date ON activity_logs(created_at);

-- ==========================================
-- RELATIONSHIP SUMMARY FOR ERD
-- ==========================================

/*
MAIN RELATIONSHIPS:

1. users (1) ←→ bookings (M)
   - One user can make many bookings
   - FK: bookings.user_id → users.id

2. services (1) ←→ bookings (M)
   - One service can be booked many times
   - FK: bookings.service_id → services.service_id

3. bookings (1) ←→ payments (M)
   - One booking can have multiple payments (partial/full)
   - FK: payments.booking_id → bookings.booking_id

4. users (1) ←→ payments (M)
   - One user can make many payments
   - FK: payments.user_id → users.id

5. users (1) ←→ payments (M) [verification]
   - One admin can verify many payments
   - FK: payments.verified_by → users.id

6. bookings (1) ←→ reviews (1)
   - One booking can have one review
   - FK: reviews.booking_id → bookings.booking_id

7. users (1) ←→ reviews (M)
   - One user can write many reviews
   - FK: reviews.user_id → users.id

8. services (1) ←→ reviews (M)
   - One service can have many reviews
   - FK: reviews.service_id → services.service_id

9. users (1) ←→ notifications (M)
   - One user can have many notifications
   - FK: notifications.user_id → users.id

10. users (1) ←→ activity_logs (M)
    - One user can have many activity logs
    - FK: activity_logs.user_id → users.id
    - FK: activity_logs.admin_id → users.id

11. payments (1) ←→ payment_logs (M)
    - One payment can have many log entries
    - FK: payment_logs.payment_id → payments.payment_id

BUSINESS RULES:
- Users can be 'admin' or 'user' role
- Bookings require payment to be confirmed
- Payments can be partial (50%) or full (100%)
- Online payments require admin verification
- Each booking can have only one review
- All actions are logged for audit trail
*/