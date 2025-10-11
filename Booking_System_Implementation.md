# 🚗 Car Detailing Booking System - Complete Implementation Guide
**Home-Based Mobile Service with Travel Time Logic**

## 🎯 **BUSINESS LOGIC SUMMARY**

### **Core Rules:**
- ✅ **Maximum 2 customers per day** (owner limitation)
- ✅ **Travel time buffer** (1-2 hours between bookings)
- ✅ **Home-based mobile service** (owner travels to customers)
- ✅ **Booking approval system** (pending → admin confirms)
- ✅ **Smart availability** (disable conflicting slots)

---

## 📊 **DATABASE STRUCTURE**

### **Enhanced Bookings Table:**
```sql
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('pending','accepted','rejected','cancelled') DEFAULT 'pending',
    service_address TEXT NOT NULL,
    customer_address VARCHAR(255) NOT NULL,
    travel_time_buffer INT DEFAULT 60, -- minutes
    special_requests TEXT,
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_date_status (booking_date, status),
    INDEX idx_customer (customer_id),
    INDEX idx_time_range (booking_date, start_time, end_time),
    
    -- Foreign keys
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);
```

### **Availability Tracking Table (Optional but Recommended):**
```sql
CREATE TABLE daily_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    accepted_bookings_count INT DEFAULT 0,
    is_fully_booked BOOLEAN DEFAULT FALSE,
    blocked_time_ranges JSON, -- Store blocked time ranges
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_date (date)
);
```

---

## 🔧 **PHP IMPLEMENTATION**

### **1. Booking Availability Checker**
```php
<?php
class BookingAvailabilityChecker {
    private $db;
    private $travel_buffer_minutes = 60; // 1 hour default
    private $max_bookings_per_day = 2;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if a time slot is available
     */
    public function isTimeSlotAvailable($date, $start_time, $end_time, $exclude_booking_id = null) {
        // First check: Are there already 2 accepted bookings on this date?
        if ($this->getDailyAcceptedBookingsCount($date) >= $this->max_bookings_per_day) {
            return [
                'available' => false,
                'reason' => 'Date is fully booked (2 bookings maximum per day)'
            ];
        }
        
        // Second check: Check for time conflicts with travel buffer
        $conflicts = $this->checkTimeConflicts($date, $start_time, $end_time, $exclude_booking_id);
        
        if (!empty($conflicts)) {
            return [
                'available' => false,
                'reason' => 'Time slot conflicts with existing booking (including travel time)',
                'conflicts' => $conflicts
            ];
        }
        
        return [
            'available' => true,
            'reason' => 'Time slot is available'
        ];
    }
    
    /**
     * Get count of accepted bookings for a specific date
     */
    private function getDailyAcceptedBookingsCount($date) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM bookings 
            WHERE booking_date = ? AND status = 'accepted'
        ");
        $stmt->execute([$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    /**
     * Check for time conflicts including travel buffer
     */
    private function checkTimeConflicts($date, $start_time, $end_time, $exclude_booking_id = null) {
        $exclude_clause = $exclude_booking_id ? "AND booking_id != ?" : "";
        $params = [$date];
        if ($exclude_booking_id) {
            $params[] = $exclude_booking_id;
        }
        
        $stmt = $this->db->prepare("
            SELECT booking_id, start_time, end_time, 
                   TIME_SUB(start_time, INTERVAL ? MINUTE) as buffer_start,
                   TIME_ADD(end_time, INTERVAL ? MINUTE) as buffer_end
            FROM bookings 
            WHERE booking_date = ? AND status = 'accepted' 
            $exclude_clause
        ");
        
        array_unshift($params, $this->travel_buffer_minutes, $this->travel_buffer_minutes);
        $stmt->execute($params);
        $existing_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $conflicts = [];
        foreach ($existing_bookings as $booking) {
            // Check if new booking overlaps with existing booking + buffer
            if ($this->timeRangesOverlap(
                $start_time, $end_time,
                $booking['buffer_start'], $booking['buffer_end']
            )) {
                $conflicts[] = $booking;
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Check if two time ranges overlap
     */
    private function timeRangesOverlap($start1, $end1, $start2, $end2) {
        return ($start1 < $end2) && ($end1 > $start2);
    }
    
    /**
     * Get available time slots for a specific date
     */
    public function getAvailableTimeSlots($date, $service_duration_hours = 4) {
        // Business hours (8 AM to 6 PM)
        $business_start = '08:00:00';
        $business_end = '18:00:00';
        
        // If date is fully booked, return empty array
        if ($this->getDailyAcceptedBookingsCount($date) >= $this->max_bookings_per_day) {
            return [];
        }
        
        // Get all accepted bookings for this date
        $stmt = $this->db->prepare("
            SELECT start_time, end_time,
                   TIME_SUB(start_time, INTERVAL ? MINUTE) as buffer_start,
                   TIME_ADD(end_time, INTERVAL ? MINUTE) as buffer_end
            FROM bookings 
            WHERE booking_date = ? AND status = 'accepted'
            ORDER BY start_time
        ");
        $stmt->execute([$this->travel_buffer_minutes, $date]);
        $blocked_ranges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate available slots
        $available_slots = [];
        $current_time = $business_start;
        
        foreach ($blocked_ranges as $blocked) {
            // Check if there's a gap before this blocked range
            $gap_end = $blocked['buffer_start'];
            if ($this->calculateMinutesDifference($current_time, $gap_end) >= ($service_duration_hours * 60)) {
                $available_slots[] = [
                    'start_time' => $current_time,
                    'end_time' => date('H:i:s', strtotime($current_time . " +{$service_duration_hours} hours"))
                ];
            }
            $current_time = $blocked['buffer_end'];
        }
        
        // Check if there's time after the last booking
        if ($this->calculateMinutesDifference($current_time, $business_end) >= ($service_duration_hours * 60)) {
            $available_slots[] = [
                'start_time' => $current_time,
                'end_time' => date('H:i:s', strtotime($current_time . " +{$service_duration_hours} hours"))
            ];
        }
        
        return $available_slots;
    }
    
    private function calculateMinutesDifference($time1, $time2) {
        return (strtotime($time2) - strtotime($time1)) / 60;
    }
}
?>
```

### **2. Booking Management Class**
```php
<?php
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
    public function createBooking($customer_id, $service_id, $date, $start_time, $end_time, $customer_address, $special_requests = '') {
        // Check availability first
        $availability = $this->availability_checker->isTimeSlotAvailable($date, $start_time, $end_time);
        
        if (!$availability['available']) {
            return [
                'success' => false,
                'message' => $availability['reason']
            ];
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bookings (customer_id, service_id, booking_date, start_time, end_time, 
                                    customer_address, special_requests, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $customer_id, $service_id, $date, $start_time, 
                $end_time, $customer_address, $special_requests
            ]);
            
            $booking_id = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'booking_id' => $booking_id,
                'message' => 'Booking created successfully. Waiting for admin approval.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Admin accepts a booking
     */
    public function acceptBooking($booking_id, $admin_notes = '') {
        // Get booking details
        $booking = $this->getBookingById($booking_id);
        if (!$booking) {
            return ['success' => false, 'message' => 'Booking not found'];
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
                'message' => 'Cannot accept: ' . $availability['reason']
            ];
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE bookings 
                SET status = 'accepted', admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE booking_id = ?
            ");
            $stmt->execute([$admin_notes, $booking_id]);
            
            // Update daily availability cache
            $this->updateDailyAvailability($booking['booking_date']);
            
            return [
                'success' => true,
                'message' => 'Booking accepted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error accepting booking: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get booking by ID
     */
    private function getBookingById($booking_id) {
        $stmt = $this->db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update daily availability cache
     */
    private function updateDailyAvailability($date) {
        $accepted_count = $this->availability_checker->getDailyAcceptedBookingsCount($date);
        $is_fully_booked = $accepted_count >= 2;
        
        $stmt = $this->db->prepare("
            INSERT INTO daily_availability (date, accepted_bookings_count, is_fully_booked) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            accepted_bookings_count = VALUES(accepted_bookings_count),
            is_fully_booked = VALUES(is_fully_booked)
        ");
        $stmt->execute([$date, $accepted_count, $is_fully_booked]);
    }
    
    /**
     * Get unavailable dates (for frontend calendar)
     */
    public function getUnavailableDates($start_date, $end_date) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT booking_date 
            FROM bookings 
            WHERE booking_date BETWEEN ? AND ? 
            AND status = 'accepted'
            GROUP BY booking_date 
            HAVING COUNT(*) >= 2
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
```

---

## 🎨 **FRONTEND IMPLEMENTATION**

### **Booking Calendar (JavaScript)**
```javascript
class BookingCalendar {
    constructor() {
        this.unavailableDates = [];
        this.selectedDate = null;
        this.availableTimeSlots = [];
    }
    
    async loadUnavailableDates() {
        try {
            const response = await fetch('api/get_unavailable_dates.php');
            this.unavailableDates = await response.json();
            this.updateCalendarDisplay();
        } catch (error) {
            console.error('Error loading unavailable dates:', error);
        }
    }
    
    updateCalendarDisplay() {
        // Disable fully booked dates in calendar
        this.unavailableDates.forEach(date => {
            const dateElement = document.querySelector(`[data-date="${date}"]`);
            if (dateElement) {
                dateElement.classList.add('fully-booked');
                dateElement.style.backgroundColor = '#ccc';
                dateElement.style.pointerEvents = 'none';
                dateElement.title = 'Fully booked (2 appointments)';
            }
        });
    }
    
    async selectDate(date) {
        if (this.unavailableDates.includes(date)) {
            alert('This date is fully booked. Please select another date.');
            return;
        }
        
        this.selectedDate = date;
        await this.loadAvailableTimeSlots(date);
        this.displayTimeSlots();
    }
    
    async loadAvailableTimeSlots(date) {
        try {
            const response = await fetch(`api/get_available_slots.php?date=${date}`);
            this.availableTimeSlots = await response.json();
        } catch (error) {
            console.error('Error loading time slots:', error);
        }
    }
    
    displayTimeSlots() {
        const slotsContainer = document.getElementById('time-slots');
        slotsContainer.innerHTML = '';
        
        if (this.availableTimeSlots.length === 0) {
            slotsContainer.innerHTML = '<p class="no-slots">No available time slots for this date.</p>';
            return;
        }
        
        this.availableTimeSlots.forEach(slot => {
            const slotElement = document.createElement('button');
            slotElement.className = 'time-slot-btn';
            slotElement.textContent = `${slot.start_time} - ${slot.end_time}`;
            slotElement.onclick = () => this.selectTimeSlot(slot);
            slotsContainer.appendChild(slotElement);
        });
    }
    
    selectTimeSlot(slot) {
        // Remove previous selection
        document.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        // Mark current selection
        event.target.classList.add('selected');
        
        // Store selected slot
        this.selectedTimeSlot = slot;
        
        // Enable booking button
        document.getElementById('book-now-btn').disabled = false;
    }
}
```

### **Booking Form HTML**
```html
<div class="booking-container">
    <div class="booking-calendar">
        <h3>Select Date</h3>
        <div id="calendar-widget">
            <!-- Calendar implementation -->
        </div>
        
        <div class="booking-legend">
            <div class="legend-item">
                <span class="legend-color available"></span>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <span class="legend-color fully-booked"></span>
                <span>Fully Booked (2 appointments)</span>
            </div>
        </div>
    </div>
    
    <div class="time-selection">
        <h3>Available Time Slots</h3>
        <div id="time-slots">
            <p>Please select a date first</p>
        </div>
    </div>
    
    <div class="booking-form">
        <h3>Booking Details</h3>
        <form id="booking-form">
            <div class="form-group">
                <label>Service Address:</label>
                <textarea name="customer_address" required 
                         placeholder="Enter your complete address where the service will be performed"></textarea>
            </div>
            
            <div class="form-group">
                <label>Special Requests:</label>
                <textarea name="special_requests" 
                         placeholder="Any special instructions or requests?"></textarea>
            </div>
            
            <div class="booking-summary">
                <h4>Booking Summary</h4>
                <p><strong>Date:</strong> <span id="summary-date">Not selected</span></p>
                <p><strong>Time:</strong> <span id="summary-time">Not selected</span></p>
                <p><strong>Service:</strong> <span id="summary-service">Car Detailing</span></p>
            </div>
            
            <button type="submit" id="book-now-btn" disabled>
                Book Appointment (Pending Approval)
            </button>
        </form>
    </div>
</div>
```

---

## 📱 **ADMIN DASHBOARD**

### **Booking Management Interface**
```php
// admin_bookings.php
<?php
require_once 'classes/BookingManager.php';

$booking_manager = new BookingManager($db);

// Get all bookings grouped by status
$pending_bookings = $booking_manager->getBookingsByStatus('pending');
$accepted_bookings = $booking_manager->getBookingsByStatus('accepted');
$today_bookings = $booking_manager->getTodayBookings();
?>

<div class="admin-dashboard">
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Today's Bookings</h3>
            <span class="stat-number"><?= count($today_bookings) ?>/2</span>
        </div>
        
        <div class="stat-card">
            <h3>Pending Approval</h3>
            <span class="stat-number"><?= count($pending_bookings) ?></span>
        </div>
        
        <div class="stat-card">
            <h3>This Week</h3>
            <span class="stat-number"><?= $booking_manager->getWeeklyBookingsCount() ?></span>
        </div>
    </div>
    
    <div class="booking-sections">
        <!-- Pending Bookings -->
        <div class="booking-section">
            <h3>Pending Bookings (Need Approval)</h3>
            <?php foreach ($pending_bookings as $booking): ?>
                <div class="booking-card pending">
                    <div class="booking-info">
                        <h4><?= $booking['customer_name'] ?></h4>
                        <p><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> - <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                        <p><strong>Address:</strong> <?= $booking['customer_address'] ?></p>
                        <p><strong>Service:</strong> <?= $booking['service_name'] ?></p>
                    </div>
                    
                    <div class="booking-actions">
                        <button class="btn-accept" onclick="acceptBooking(<?= $booking['booking_id'] ?>)">
                            Accept
                        </button>
                        <button class="btn-reject" onclick="rejectBooking(<?= $booking['booking_id'] ?>)">
                            Reject
                        </button>
                    </div>
                    
                    <!-- Conflict Warning -->
                    <?php 
                    $conflicts = $booking_manager->checkBookingConflicts($booking);
                    if (!empty($conflicts)): 
                    ?>
                        <div class="conflict-warning">
                            ⚠️ Warning: This booking conflicts with existing appointments or exceeds daily limit
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Accepted Bookings -->
        <div class="booking-section">
            <h3>Accepted Bookings</h3>
            <?php foreach ($accepted_bookings as $booking): ?>
                <div class="booking-card accepted">
                    <div class="booking-info">
                        <h4><?= $booking['customer_name'] ?></h4>
                        <p><strong>Date:</strong> <?= date('M d, Y', strtotime($booking['booking_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> - <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                        <p><strong>Address:</strong> <?= $booking['customer_address'] ?></p>
                    </div>
                    
                    <div class="booking-status">
                        ✅ Confirmed
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
```

---

## 🎯 **EXAMPLE SCENARIOS**

### **Scenario 1: Normal Day**
```
January 1, 2025:
- Customer A books: 8:00 AM - 12:00 PM → PENDING
- Admin accepts Customer A → Status: ACCEPTED
- Next available slot: 1:00 PM onwards (1-hour travel buffer)
- Customer B books: 2:00 PM - 6:00 PM → PENDING
- Admin accepts Customer B → Status: ACCEPTED
- January 1 becomes FULLY BOOKED (2/2 slots filled)
```

### **Scenario 2: Conflict Rejection**
```
January 2, 2025:
- Customer A: 9:00 AM - 1:00 PM → ACCEPTED
- Customer B tries: 12:00 PM - 4:00 PM → AUTOMATICALLY REJECTED
  (Conflicts with Customer A's slot + travel buffer)
- Next available: 2:00 PM onwards
```

### **Scenario 3: Cancellation**
```
January 3, 2025:
- Customer A: 8:00 AM - 12:00 PM → ACCEPTED
- Customer B: 2:00 PM - 6:00 PM → ACCEPTED
- January 3: FULLY BOOKED
- Customer A cancels → January 3 becomes AVAILABLE again
- New customers can book 8:00 AM - 1:00 PM slot
```

This implementation gives you a **professional booking system** that handles all the business logic you described perfectly! 🚀