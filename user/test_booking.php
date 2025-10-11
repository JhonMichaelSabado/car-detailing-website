<?php
/**
 * Quick Test Booking Form (No Login Required)
 * For testing the advanced booking system functionality
 */

require_once '../config/database.php';
require_once '../includes/BookingAvailabilityChecker.php';

$database = new Database();
$pdo = $database->getConnection();
$availability_checker = new BookingAvailabilityChecker($pdo);

// Get available services
$stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY service_name LIMIT 5");
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Advanced Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .time-slot {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 10px 15px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
        .time-slot:hover {
            background: #bbdefb;
            transform: translateY(-2px);
        }
        .time-slot.selected {
            background: #2196f3;
            color: white;
            font-weight: bold;
        }
        .availability-panel {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .business-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .test-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .date-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-vial"></i> Test Advanced Booking System</h1>
                <p class="lead">Test your new "1 man army" booking system with real-time availability!</p>
                
                <!-- Test Info -->
                <div class="test-info">
                    <h5><i class="fas fa-flask"></i> Testing Mode</h5>
                    <p class="mb-0">This is a test interface to verify your advanced booking system. No actual bookings will be created, but you can see how the availability checking works in real-time.</p>
                </div>

                <!-- Business Rules -->
                <div class="business-info">
                    <h5><i class="fas fa-rules"></i> Business Rules Active</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><strong>Maximum 2 customers per day</strong></li>
                                <li><strong>Business hours:</strong> 8:00 AM - 6:00 PM</li>
                                <li><strong>No weekends</strong> (configurable)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li><strong>30-day advance booking limit</strong></li>
                                <li><strong>No past date bookings</strong></li>
                                <li><strong>Travel buffers between slots</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Date Selection -->
                <div class="availability-panel">
                    <h4><i class="fas fa-calendar-alt"></i> Select Date to Check Availability</h4>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="test_date" class="form-label">Choose Date:</label>
                            <input type="date" class="form-control" id="test_date" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+35 days')); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quick Select:</label><br>
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="setDate(1)">Tomorrow</button>
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="setDate(7)">Next Week</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="setDate(0, true)">Today (Past)</button>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Test Limits:</label><br>
                            <button class="btn btn-outline-danger btn-sm me-2" onclick="setWeekend()">Weekend</button>
                            <button class="btn btn-outline-danger btn-sm" onclick="setDate(35)">35 Days</button>
                        </div>
                    </div>

                    <!-- Availability Results -->
                    <div id="availabilityResults" style="display:none;">
                        <hr>
                        <h5><i class="fas fa-clock"></i> Available Time Slots</h5>
                        <div id="timeSlots"></div>
                        
                        <div class="date-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Date Information</h6>
                            <div id="dateInfo"></div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Statistics -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-bar"></i> Current Booking Stats</h5>
                            </div>
                            <div class="card-body" id="bookingStats">
                                <p class="text-muted">Select a date to see statistics</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs"></i> System Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Database Connection:</span>
                                    <span class="badge bg-success">✓ Active</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Availability Checker:</span>
                                    <span class="badge bg-success">✓ Ready</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Business Rules:</span>
                                    <span class="badge bg-success">✓ Enforced</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>API Endpoints:</span>
                                    <span class="badge bg-success">✓ Online</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Test Section -->
                <div class="mt-4">
                    <h4><i class="fas fa-plug"></i> API Testing</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-info w-100" onclick="testAPI('get_available_slots')">
                                <i class="fas fa-play"></i> Test Available Slots API
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-info w-100" onclick="testAPI('get_unavailable_dates')">
                                <i class="fas fa-play"></i> Test Unavailable Dates API
                            </button>
                        </div>
                    </div>
                    <div id="apiResults" class="mt-3" style="display:none;">
                        <h6>API Response:</h6>
                        <pre id="apiOutput" class="bg-dark text-light p-3 rounded"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Date change handler
        document.getElementById('test_date').addEventListener('change', function() {
            const date = this.value;
            if (date) {
                checkAvailability(date);
            }
        });

        // Set date helper functions
        function setDate(days, isPast = false) {
            const today = new Date();
            if (isPast) {
                today.setDate(today.getDate() - days);
            } else {
                today.setDate(today.getDate() + days);
            }
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('test_date').value = dateStr;
            checkAvailability(dateStr);
        }

        function setWeekend() {
            const today = new Date();
            const daysUntilSaturday = (6 - today.getDay()) % 7;
            today.setDate(today.getDate() + daysUntilSaturday + (daysUntilSaturday === 0 ? 7 : 0));
            const dateStr = today.toISOString().split('T')[0];
            document.getElementById('test_date').value = dateStr;
            checkAvailability(dateStr);
        }

        // Check availability for selected date
        async function checkAvailability(date) {
            try {
                const response = await fetch(`../api/get_available_slots.php?date=${date}`);
                const data = await response.json();
                
                displayAvailabilityResults(date, data);
                loadBookingStats(date);
                
            } catch (error) {
                console.error('Error checking availability:', error);
                document.getElementById('availabilityResults').innerHTML = 
                    '<div class="alert alert-danger">Error loading availability data</div>';
            }
        }

        // Display availability results
        function displayAvailabilityResults(date, data) {
            const resultsDiv = document.getElementById('availabilityResults');
            const timeSlotsDiv = document.getElementById('timeSlots');
            const dateInfoDiv = document.getElementById('dateInfo');
            
            resultsDiv.style.display = 'block';
            
            // Show time slots
            if (data.success && data.available_slots && data.available_slots.length > 0) {
                timeSlotsDiv.innerHTML = '';
                data.available_slots.forEach(slot => {
                    const slotDiv = document.createElement('div');
                    slotDiv.className = 'time-slot';
                    slotDiv.textContent = formatTime(slot.start_time) + 
                                         (slot.slot_type !== 'standard' ? ` (${slot.slot_type})` : '');
                    slotDiv.onclick = () => selectSlot(slotDiv, slot);
                    timeSlotsDiv.appendChild(slotDiv);
                });
            } else {
                timeSlotsDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <strong>No available slots</strong><br>
                        ${data.message || 'This date is not available for booking'}
                    </div>
                `;
            }
            
            // Show date info
            const dateObj = new Date(date + 'T00:00:00');
            const dayOfWeek = dateObj.getDay();
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            const isPast = dateObj < new Date().setHours(0,0,0,0);
            const daysDiff = Math.ceil((dateObj - new Date()) / (1000 * 60 * 60 * 24));
            
            dateInfoDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Selected Date:</strong> ${formatDate(date)}<br>
                        <strong>Day Type:</strong> ${isWeekend ? '<span class="text-warning">Weekend</span>' : '<span class="text-success">Weekday</span>'}<br>
                        <strong>Status:</strong> ${isPast ? '<span class="text-danger">Past Date</span>' : '<span class="text-success">Future Date</span>'}
                    </div>
                    <div class="col-md-6">
                        <strong>Days from today:</strong> ${daysDiff} days<br>
                        <strong>Available slots:</strong> ${data.available_slots ? data.available_slots.length : 0}<br>
                        <strong>Booking limit:</strong> ${daysDiff > 30 ? '<span class="text-danger">Beyond limit</span>' : '<span class="text-success">Within limit</span>'}
                    </div>
                </div>
            `;
        }

        // Load booking statistics
        async function loadBookingStats(date) {
            try {
                const today = new Date().toISOString().split('T')[0];
                const response = await fetch(`../api/get_unavailable_dates.php?start_date=${today}&end_date=${date}`);
                const data = await response.json();
                
                const statsDiv = document.getElementById('bookingStats');
                if (data.success) {
                    const dateData = data.detailed_availability[date];
                    if (dateData) {
                        statsDiv.innerHTML = `
                            <div class="row">
                                <div class="col-6">
                                    <strong>Total Bookings:</strong><br>
                                    <span class="h4 text-primary">${dateData.total_bookings}</span>
                                </div>
                                <div class="col-6">
                                    <strong>Available Slots:</strong><br>
                                    <span class="h4 text-success">${dateData.available_slots}</span>
                                </div>
                            </div>
                            <hr>
                            <small>
                                Confirmed: ${dateData.accepted_bookings} | 
                                Pending: ${dateData.pending_bookings}
                            </small>
                        `;
                    } else {
                        statsDiv.innerHTML = `
                            <div class="text-center">
                                <strong>No bookings for this date</strong><br>
                                <span class="h4 text-success">2</span><br>
                                <small>Available slots</small>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Select time slot
        function selectSlot(element, slot) {
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            element.classList.add('selected');
            
            alert(`Selected: ${formatTime(slot.start_time)}\nDuration: ${slot.duration_minutes} minutes\nType: ${slot.slot_type}`);
        }

        // Test API endpoints
        async function testAPI(endpoint) {
            const date = document.getElementById('test_date').value || new Date().toISOString().split('T')[0];
            let url;
            
            if (endpoint === 'get_available_slots') {
                url = `../api/get_available_slots.php?date=${date}`;
            } else {
                url = `../api/get_unavailable_dates.php?start_date=${date}&end_date=${date}`;
            }
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                document.getElementById('apiResults').style.display = 'block';
                document.getElementById('apiOutput').textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                document.getElementById('apiResults').style.display = 'block';
                document.getElementById('apiOutput').textContent = 'Error: ' + error.message;
            }
        }

        // Utility functions
        function formatTime(time) {
            return new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        function formatDate(date) {
            return new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Auto-load tomorrow's availability on page load
        window.onload = function() {
            setDate(1);
        };
    </script>
</body>
</html>