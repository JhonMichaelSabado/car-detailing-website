<?php
/**
 * Enhanced Booking Form with Advanced Booking System Integration
 * Uses the new BookingAvailabilityChecker and BookingManager classes
 */

session_start();
require_once '../config/database.php';
require_once '../includes/BookingAvailabilityChecker.php';
require_once '../includes/BookingManager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$database = new Database();
$pdo = $database->getConnection();
$availability_checker = new BookingAvailabilityChecker($pdo);
$booking_manager = new BookingManager($pdo);

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get available services
$stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY service_name");
$services = $stmt->fetchAll();

// Handle form submission
$booking_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create booking data
        $booking_data = [
            'user_id' => $_SESSION['user_id'],
            'service_id' => $_POST['service_id'],
            'vehicle_size' => $_POST['vehicle_size'],
            'booking_date' => $_POST['booking_date'] . ' ' . $_POST['booking_time'],
            'vehicle_details' => $_POST['vehicle_details'],
            'special_requests' => $_POST['special_requests']
        ];
        
        $result = $booking_manager->createBooking($booking_data);
        
        if ($result['success']) {
            $booking_result = [
                'type' => 'success',
                'message' => 'Booking created successfully! Your booking ID is: ' . $result['booking_id'] . '. Please wait for admin approval.'
            ];
        } else {
            $booking_result = [
                'type' => 'error',
                'message' => 'Booking failed: ' . $result['message']
            ];
        }
    } catch (Exception $e) {
        $booking_result = [
            'type' => 'error',
            'message' => 'An error occurred: ' . $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Advanced Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .availability-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .time-slot {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 8px;
            padding: 10px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .time-slot:hover {
            background: #bbdefb;
            transform: translateY(-2px);
        }
        .time-slot.selected {
            background: #2196f3;
            color: white;
        }
        .time-slot.unavailable {
            background: #ffebee;
            border-color: #f44336;
            color: #666;
            cursor: not-allowed;
        }
        .booking-summary {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .business-rules {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container booking-container">
        <div class="row">
            <div class="col-12">
                <h1><i class="fas fa-calendar-plus"></i> Book Car Detailing Service</h1>
                <p class="lead">Welcome <?php echo htmlspecialchars($user['first_name']); ?>! Book your advanced car detailing service.</p>
                
                <!-- Business Rules Info -->
                <div class="business-rules">
                    <h5><i class="fas fa-info-circle"></i> Booking Information</h5>
                    <ul class="mb-0">
                        <li><strong>Maximum 2 customers per day</strong> - Limited slots for quality service</li>
                        <li><strong>Business Hours:</strong> 8:00 AM - 6:00 PM</li>
                        <li><strong>Advance Booking:</strong> Up to 30 days in advance</li>
                        <li><strong>No Weekend Service</strong> - Monday to Friday only</li>
                        <li><strong>Admin Approval Required</strong> - All bookings need confirmation</li>
                    </ul>
                </div>

                <!-- Booking Result Alert -->
                <?php if ($booking_result): ?>
                <div class="alert alert-<?php echo $booking_result['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <strong><?php echo $booking_result['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
                    <?php echo htmlspecialchars($booking_result['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Booking Form -->
                <form method="POST" id="bookingForm">
                    <div class="row">
                        <!-- Service Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="service_id" class="form-label"><i class="fas fa-car-wash"></i> Service Type</label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Select a service...</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['service_id']; ?>" 
                                            data-price="<?php echo $service['price']; ?>"
                                            data-duration="<?php echo $service['estimated_duration']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?> - 
                                        $<?php echo number_format($service['price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Vehicle Size -->
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_size" class="form-label"><i class="fas fa-car"></i> Vehicle Size</label>
                            <select class="form-select" id="vehicle_size" name="vehicle_size" required>
                                <option value="">Select vehicle size...</option>
                                <option value="small">Small (Sedan, Hatchback)</option>
                                <option value="medium">Medium (SUV, Crossover)</option>
                                <option value="large">Large (Truck, Van, Large SUV)</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="booking_date" class="form-label"><i class="fas fa-calendar"></i> Booking Date</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>

                        <!-- Time Slots Display -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-clock"></i> Available Time Slots</label>
                            <div id="timeSlots" class="d-flex flex-wrap">
                                <p class="text-muted">Please select a date first</p>
                            </div>
                            <input type="hidden" id="booking_time" name="booking_time" required>
                        </div>

                        <!-- Vehicle Details -->
                        <div class="col-12 mb-3">
                            <label for="vehicle_details" class="form-label"><i class="fas fa-car-side"></i> Vehicle Details</label>
                            <textarea class="form-control" id="vehicle_details" name="vehicle_details" rows="3" 
                                      placeholder="Year, Make, Model, Color, License Plate (e.g., 2020 Toyota Camry, Blue, ABC123)"></textarea>
                        </div>

                        <!-- Special Requests -->
                        <div class="col-12 mb-3">
                            <label for="special_requests" class="form-label"><i class="fas fa-comments"></i> Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3" 
                                      placeholder="Any special instructions or areas of focus..."></textarea>
                        </div>

                        <!-- Availability Info -->
                        <div class="col-12">
                            <div class="availability-info" id="availabilityInfo" style="display:none;">
                                <h6><i class="fas fa-info-circle"></i> Booking Availability</h6>
                                <div id="availabilityDetails"></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn" disabled>
                                <i class="fas fa-calendar-check"></i> Book Service
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Booking Summary -->
                <div class="booking-summary" id="bookingSummary" style="display:none;">
                    <h5><i class="fas fa-clipboard-check"></i> Booking Summary</h5>
                    <div id="summaryContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedTimeSlot = null;
        let selectedService = null;

        // Date change handler
        document.getElementById('booking_date').addEventListener('change', function() {
            const date = this.value;
            if (date) {
                loadAvailableTimeSlots(date);
            }
        });

        // Service change handler
        document.getElementById('service_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            selectedService = {
                id: this.value,
                name: option.text,
                price: option.getAttribute('data-price'),
                duration: option.getAttribute('data-duration')
            };
            updateBookingSummary();
        });

        // Vehicle size change handler
        document.getElementById('vehicle_size').addEventListener('change', updateBookingSummary);

        // Load available time slots
        async function loadAvailableTimeSlots(date) {
            try {
                const response = await fetch(`../api/get_available_slots.php?date=${date}`);
                const data = await response.json();
                
                const timeSlotsContainer = document.getElementById('timeSlots');
                const availabilityInfo = document.getElementById('availabilityInfo');
                const availabilityDetails = document.getElementById('availabilityDetails');
                
                if (data.success && data.available_slots.length > 0) {
                    // Show available slots
                    timeSlotsContainer.innerHTML = '';
                    data.available_slots.forEach(slot => {
                        const slotBtn = document.createElement('div');
                        slotBtn.className = 'time-slot';
                        slotBtn.textContent = formatTime(slot.start_time);
                        slotBtn.dataset.time = slot.start_time;
                        slotBtn.addEventListener('click', () => selectTimeSlot(slotBtn));
                        timeSlotsContainer.appendChild(slotBtn);
                    });
                    
                    // Show availability info
                    availabilityDetails.innerHTML = `
                        <p><strong>Available slots:</strong> ${data.available_slots.length}</p>
                        <p><strong>Business hours:</strong> 8:00 AM - 6:00 PM</p>
                        <p><strong>Note:</strong> Maximum 2 bookings per day with travel buffer between appointments</p>
                    `;
                    availabilityInfo.style.display = 'block';
                } else {
                    // No slots available
                    timeSlotsContainer.innerHTML = '<p class="text-danger">No available time slots for this date</p>';
                    availabilityDetails.innerHTML = `
                        <p class="text-warning">${data.message || 'This date is fully booked or unavailable'}</p>
                        <p><strong>Reasons this might happen:</strong></p>
                        <ul>
                            <li>Maximum 2 bookings per day reached</li>
                            <li>Weekend (weekends are not available)</li>
                            <li>Past date selected</li>
                            <li>Beyond 30-day advance booking limit</li>
                        </ul>
                    `;
                    availabilityInfo.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading time slots:', error);
                document.getElementById('timeSlots').innerHTML = '<p class="text-danger">Error loading time slots</p>';
            }
        }

        // Select time slot
        function selectTimeSlot(slotElement) {
            // Remove selection from other slots
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Select this slot
            slotElement.classList.add('selected');
            selectedTimeSlot = slotElement.dataset.time;
            document.getElementById('booking_time').value = selectedTimeSlot;
            
            updateBookingSummary();
            updateSubmitButton();
        }

        // Update booking summary
        function updateBookingSummary() {
            const service = selectedService;
            const vehicleSize = document.getElementById('vehicle_size').value;
            const date = document.getElementById('booking_date').value;
            const time = selectedTimeSlot;
            
            if (service && vehicleSize && date && time) {
                const summaryContent = document.getElementById('summaryContent');
                summaryContent.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Service:</strong> ${service.name}<br>
                            <strong>Vehicle Size:</strong> ${vehicleSize.charAt(0).toUpperCase() + vehicleSize.slice(1)}<br>
                            <strong>Date:</strong> ${formatDate(date)}<br>
                            <strong>Time:</strong> ${formatTime(time)}
                        </div>
                        <div class="col-md-6">
                            <strong>Estimated Price:</strong> $${parseFloat(service.price).toFixed(2)}<br>
                            <strong>Duration:</strong> ~${service.duration} minutes<br>
                            <strong>Status:</strong> Pending Admin Approval<br>
                            <strong>Payment:</strong> Due after approval
                        </div>
                    </div>
                `;
                document.getElementById('bookingSummary').style.display = 'block';
            }
        }

        // Update submit button state
        function updateSubmitButton() {
            const form = document.getElementById('bookingForm');
            const submitBtn = document.getElementById('submitBtn');
            const isValid = form.checkValidity() && selectedTimeSlot;
            
            submitBtn.disabled = !isValid;
            if (isValid) {
                submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Book Service';
                submitBtn.className = 'btn btn-success btn-lg w-100';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> Complete Form to Book';
                submitBtn.className = 'btn btn-secondary btn-lg w-100';
            }
        }

        // Format time for display
        function formatTime(time) {
            return new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        // Format date for display
        function formatDate(date) {
            return new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Form validation
        document.getElementById('bookingForm').addEventListener('input', updateSubmitButton);
    </script>
</body>
</html>