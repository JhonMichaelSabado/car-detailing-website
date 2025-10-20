<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Check if coming from step 2
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_address'])) {
    $_SESSION['booking_flow']['service_address'] = $_POST['service_address'];
    $_SESSION['booking_flow']['service_lat'] = $_POST['service_lat'];
    $_SESSION['booking_flow']['service_lng'] = $_POST['service_lng'];
    $_SESSION['booking_flow']['travel_fee'] = $_POST['travel_fee'];
    $_SESSION['booking_flow']['landmark_instructions'] = $_POST['landmark_instructions'] ?? '';
    $_SESSION['booking_step'] = 3;
} elseif (!isset($_SESSION['booking_flow']['service_address'])) {
    header("Location: step2_location.php");
    exit();
}

// Get available time slots and business settings
try {
    $slots_stmt = $pdo->query("SELECT * FROM time_slots WHERE is_active = 1 ORDER BY start_time");
    $time_slots = $slots_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM business_settings");
    $settings = [];
    while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Get service details for duration
    $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $service_stmt->execute([$_SESSION['booking_flow']['service_id']]);
    $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $time_slots = [];
    $settings = [];
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Car Detailing - Date & Time Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Apple-style progress bar */
        .booking-progress-bar {
            position: absolute;
            top: 32px;
            left: 48px;
            display: flex;
            flex-direction: row;
            gap: 18px;
            max-width: 420px;
            background: none;
            box-shadow: none;
            padding: 0 32px;
            z-index: 10;
            overflow: visible;
        }
        .progress-step {
            width: 38px;
            height: 38px;
            aspect-ratio: 1/1;
            border-radius: 50%;
            background: #fff;
            color: #3f67e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.25rem;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05);
            border: 2.5px solid #e3eafd;
            transition: background 0.2s, border 0.2s;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .progress-step.active {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.13);
        }
        .progress-step.completed {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            opacity: 0.7;
        }
        .booking-progress-header {
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 2.9rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #222;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .booking-progress-subtitle {
            font-size: 1.1rem;
            color: #888;
            font-weight: 400;
            letter-spacing: 0.01em;
            margin-bottom: 1.2rem;
            text-align: center;
        }
        .booking-progress-container {
            position: relative;
            width: 100%;
            height: 180px;
            margin-bottom: 0;
        }
        /* Button color override */
        .btn-primary, .btn-primary:active, .btn-primary:focus, .btn-primary:hover {
            background: #3f67e5 !important;
            border-color: #3f67e5 !important;
            color: #fff !important;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.08);
        }
        .btn-outline-primary, .btn-outline-primary:active, .btn-outline-primary:focus, .btn-outline-primary:hover {
            color: #3f67e5 !important;
            border-color: #3f67e5 !important;
            background: #f5f8ff !important;
        }
        .calendar-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .calendar-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #3f67e5;
            color: white;
            border-radius: 10px;
        }
        .calendar-nav {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .calendar-nav:hover {
            background: rgba(255,255,255,0.2);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }
        .calendar-day-header {
            text-align: center;
            font-weight: bold;
            padding: 10px;
            color: #666;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .calendar-day:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .calendar-day.unavailable {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        .calendar-day.unavailable:hover {
            transform: none;
            border-color: #e9ecef;
        }
        .calendar-day.selected {
            background: #3f67e5;
            color: #fff;
            border-color: #3f67e5;
        }
        .calendar-day.today {
            border-color: #28a745;
            font-weight: bold;
        }
        .time-slots-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .time-slot {
            display: inline-block;
            padding: 12px 20px;
            margin: 5px;
            border: 2px solid #dee2e6;
            border-radius: 25px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .time-slot:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }
        .time-slot.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        .time-slot.unavailable {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .time-slot.unavailable:hover {
            transform: none;
            box-shadow: none;
            border-color: #dee2e6;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .booking-info {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .availability-legend {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            border: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Apple-style Progress Bar and Header -->
    <div class="booking-progress-container">
        <div class="booking-progress-bar">
            <div class="progress-step completed">1</div>
            <div class="progress-step completed">2</div>
            <div class="progress-step active">3</div>
            <div class="progress-step">4</div>
            <div class="progress-step">5</div>
        </div>
        <div style="max-width: 1100px; margin: 0 auto; padding-top: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="booking-progress-header">Professional Car Detailing Booking</div>
            <span class="booking-progress-subtitle">Step 3 of 9<span style="margin: 0 0.5em;">•</span>Select Date & Time</span>
        </div>
        <hr style="border: none; border-top: 2.5px solid #e3e3ea; margin: 32px 0 0 0; width: 99%; opacity: 0.7;" />
        <div style="margin-bottom: 48px;"></div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <form id="datetimeForm" method="POST" action="step4_payment_mode.php">
                    <!-- Booking Information -->
                    <div class="booking-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Booking Guidelines</h6>
                        <ul class="mb-0 small">
                            <li>Service duration: <strong><?= $service['duration_minutes'] ?? 120 ?> minutes</strong></li>
                            <li>Advance booking required: Up to <?= $settings['booking_advance_days'] ?? 30 ?> days</li>
                            <li>Same-day booking cutoff: <?= $settings['same_day_booking_cutoff'] ?? '10:00 AM' ?></li>
                            <li>We operate: Monday to Sunday (time slots vary)</li>
                        </ul>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-container">
                        <div class="calendar-header">
                            <button type="button" class="calendar-nav" id="prevMonth">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h5 class="mb-0" id="calendarTitle">October 2025</h5>
                            <button type="button" class="calendar-nav" id="nextMonth">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <div class="calendar-grid" id="calendarGrid">
                            <!-- Calendar will be generated by JavaScript -->
                        </div>

                        <div class="availability-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: #28a745; border-color: #28a745;"></div>
                                <span class="small">Today</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: white;"></div>
                                <span class="small">Available</span>
                            </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #3f67e5; border-color: #3f67e5;"></div>
                                    <span class="small">Selected</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #f8f9fa; border-color: #adb5bd;"></div>
                                <span class="small">Unavailable</span>
                            </div>
                        </div>
                    </div>

                    <!-- Time Slots -->
                    <div class="time-slots-container" id="timeSlotsContainer" style="display: none;">
                        <h6 class="mb-3"><i class="fas fa-clock me-2"></i>Available Time Slots for <span id="selectedDateDisplay"></span></h6>
                        <div id="timeSlots">
                            <!-- Time slots will be generated by JavaScript -->
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                All times are shown in your local timezone. Duration includes setup and cleanup time.
                            </small>
                        </div>
                    </div>

                    <!-- Hidden inputs -->
                    <input type="hidden" id="selectedDate" name="booking_date" required>
                    <input type="hidden" id="selectedTime" name="booking_time" required>
                    <input type="hidden" id="estimatedDuration" name="estimated_duration" value="<?= $service['duration_minutes'] ?? 120 ?>">

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="step2_location.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Location
                        </a>
                        <button type="submit" id="continueBtn" class="btn btn-primary" disabled>
                            Continue to Payment Options <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <div class="summary-card sticky-top" style="top: 20px;">
                    <div class="summary-header">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Booking Summary</h6>
                    </div>
                    <div class="summary-body">
                        <?php
                        $vehicle_size = $_SESSION['booking_flow']['vehicle_size'];
                        $service_stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
                        $service_stmt->execute([$_SESSION['booking_flow']['service_id']]);
                        $service = $service_stmt->fetch(PDO::FETCH_ASSOC);
                        $base_price = $service["price_$vehicle_size"];
                        $travel_fee = $_SESSION['booking_flow']['travel_fee'];
                        $subtotal = $base_price + $travel_fee;
                        $vat = $subtotal * 0.12;
                        $total = $subtotal + $vat;
                        ?>

                        <div class="mb-3">
                            <h6>Service Details</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Service:</span>
                                <span><?= htmlspecialchars($service['service_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Vehicle Size:</span>
                                <span class="text-capitalize"><?= htmlspecialchars($vehicle_size) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Duration:</span>
                                <span><?= $service['duration_minutes'] ?> minutes</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Location:</span>
                                <span class="text-end small"><?= htmlspecialchars(substr($_SESSION['booking_flow']['service_address'], 0, 30)) ?>...</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Schedule</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Date:</span>
                                <span id="summaryDate" class="text-muted">Select date</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Time:</span>
                                <span id="summaryTime" class="text-muted">Select time</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Pricing</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Base Price:</span>
                                <span>₱<?= number_format($base_price, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Travel Fee:</span>
                                <span>₱<?= number_format($travel_fee, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>VAT (12%):</span>
                                <span>₱<?= number_format($vat, 2) ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total Amount:</strong>
                            <strong class="text-primary">₱<?= number_format($total, 2) ?></strong>
                        </div>

                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-2">Payment Options</h6>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>50% Deposit:</span>
                                <span class="text-success">₱<?= number_format($total * 0.5, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Full Payment:</span>
                                <span class="text-info">₱<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const timeSlots = <?= json_encode($time_slots) ?>;
        const businessSettings = <?= json_encode($settings) ?>;
        const serviceDuration = <?= $service['duration_minutes'] ?? 120 ?>;
        
        let currentDate = new Date();
        let selectedDate = null;
        let selectedTime = null;
        let selectedSlot = null;

        const monthNames = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

        function initCalendar() {
            updateCalendarTitle();
            generateCalendar();
            
            document.getElementById('prevMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateCalendarTitle();
                generateCalendar();
            });
            
            document.getElementById('nextMonth').addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateCalendarTitle();
                generateCalendar();
            });
        }

        function updateCalendarTitle() {
            document.getElementById('calendarTitle').textContent = 
                monthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
        }

        function generateCalendar() {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';
            
            // Add day headers
            dayNames.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                grid.appendChild(dayHeader);
            });
            
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const today = new Date();
            
            // Add empty cells for days before month starts
            for (let i = 0; i < firstDay.getDay(); i++) {
                const emptyCell = document.createElement('div');
                grid.appendChild(emptyCell);
            }
            
            // Add days of the month
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = day;
                
                const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
                const dateStr = dayDate.toISOString().split('T')[0];
                
                // Check if day is today
                if (dayDate.toDateString() === today.toDateString()) {
                    dayElement.classList.add('today');
                }
                
                // Check if day is in the past or too far in the future
                const maxAdvanceDays = parseInt(businessSettings.booking_advance_days || 30);
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + maxAdvanceDays);
                
                if (dayDate < today || dayDate > maxDate) {
                    dayElement.classList.add('unavailable');
                } else {
                    dayElement.addEventListener('click', () => selectDate(dayDate, dayElement));
                }
                
                grid.appendChild(dayElement);
            }
        }

        function selectDate(date, element) {
            // Remove previous selection
            document.querySelectorAll('.calendar-day.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Select new date
            element.classList.add('selected');
            selectedDate = date;
            
            const dateStr = date.toISOString().split('T')[0];
            document.getElementById('selectedDate').value = dateStr;
            document.getElementById('selectedDateDisplay').textContent = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('summaryDate').textContent = date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            
            // Show time slots
            generateTimeSlots(date);
            document.getElementById('timeSlotsContainer').style.display = 'block';
            
            checkFormComplete();
        }

        function generateTimeSlots(date) {
            const container = document.getElementById('timeSlots');
            container.innerHTML = '';
            
            const dayOfWeek = date.getDay() === 0 ? 7 : date.getDay(); // Convert Sunday from 0 to 7
            const today = new Date();
            const isToday = date.toDateString() === today.toDateString();
            const cutoffTime = businessSettings.same_day_booking_cutoff || '10:00:00';
            
            timeSlots.forEach(slot => {
                const daysOfWeek = slot.days_of_week.split(',').map(d => parseInt(d));
                
                if (daysOfWeek.includes(dayOfWeek)) {
                    const slotElement = document.createElement('div');
                    slotElement.className = 'time-slot';
                    
                    const startTime = new Date(`2000-01-01T${slot.start_time}`);
                    const endTime = new Date(`2000-01-01T${slot.end_time}`);
                    
                    slotElement.textContent = `${startTime.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })} - ${endTime.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })}`;
                    
                    // Check if slot is available
                    let isAvailable = true;
                    
                    // Check if it's today and past cutoff time
                    if (isToday) {
                        const now = new Date();
                        const cutoffDateTime = new Date(today.toDateString() + ' ' + cutoffTime);
                        if (now > cutoffDateTime) {
                            isAvailable = false;
                        }
                    }
                    
                    // TODO: Check against existing bookings in database
                    
                    if (!isAvailable) {
                        slotElement.classList.add('unavailable');
                    } else {
                        slotElement.addEventListener('click', () => selectTimeSlot(slot, slotElement));
                    }
                    
                    container.appendChild(slotElement);
                }
            });
        }

        function selectTimeSlot(slot, element) {
            // Remove previous selection
            document.querySelectorAll('.time-slot.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Select new time slot
            element.classList.add('selected');
            selectedSlot = slot;
            selectedTime = slot.start_time;
            
            document.getElementById('selectedTime').value = slot.start_time;
            
            const startTime = new Date(`2000-01-01T${slot.start_time}`);
            document.getElementById('summaryTime').textContent = startTime.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            checkFormComplete();
        }

        function checkFormComplete() {
            const continueBtn = document.getElementById('continueBtn');
            continueBtn.disabled = !(selectedDate && selectedTime);
        }

        // Initialize calendar when page loads
        window.onload = initCalendar;
    </script>
</body>
</html>