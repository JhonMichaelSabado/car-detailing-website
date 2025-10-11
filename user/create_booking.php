<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection - using the same structure as the working dashboard
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';
require_once __DIR__ . '/../includes/BookingAvailabilityChecker.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Initialize availability checker
    $availability_checker = new BookingAvailabilityChecker($db);
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];

// Handle form submission
$booking_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    
    // Debug logging
    error_log("=== DEDICATED BOOKING PAGE DEBUG ===");
    error_log("Full POST data: " . print_r($_POST, true));
    
    try {
        // Get and validate form data
        $service_id = $_POST['service_id'] ?? '';
        $vehicle_size = $_POST['vehicle_size'] ?? '';
        $booking_date = $_POST['booking_date'] ?? '';
        $booking_time = $_POST['booking_time'] ?? '';
        $service_address = trim($_POST['service_address'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $payment_option = $_POST['payment_option'] ?? 'partial';
        $special_requests = trim($_POST['special_requests'] ?? '');
        
        // Get detailed vehicle information
        $vehicle_year = $_POST['vehicle_year'] ?? '';
        $vehicle_make = $_POST['vehicle_make'] ?? '';
        $vehicle_model = trim($_POST['vehicle_model'] ?? '');
        $vehicle_trim = trim($_POST['vehicle_trim'] ?? '');
        $vehicle_body_type = $_POST['vehicle_body_type'] ?? '';
        $vehicle_color = $_POST['vehicle_color'] ?? '';
        $license_plate = trim($_POST['license_plate'] ?? '');
        
        // Create comprehensive vehicle details string
        $vehicle_details = '';
        if ($vehicle_year && $vehicle_make && $vehicle_model) {
            $vehicle_details = $vehicle_year . ' ' . $vehicle_make . ' ' . $vehicle_model;
            if ($vehicle_trim) $vehicle_details .= ' ' . $vehicle_trim;
            if ($vehicle_body_type) $vehicle_details .= ' (' . $vehicle_body_type . ')';
            if ($vehicle_color) $vehicle_details .= ' - ' . $vehicle_color;
            if ($license_plate) $vehicle_details .= ' | Plate: ' . $license_plate;
        } else {
            // Fallback to manual input if structured fields not provided
            $vehicle_details = trim($_POST['vehicle_details'] ?? '');
        }
        
        // Validation
        $errors = [];
        if (empty($service_id)) $errors[] = "Service is required";
        if (empty($vehicle_size)) $errors[] = "Vehicle size is required";
        if (empty($booking_date)) $errors[] = "Booking date is required";
        if (empty($booking_time)) $errors[] = "Booking time is required";
        if (empty($service_address)) $errors[] = "Service address is required";
        if (empty($contact_number)) $errors[] = "Contact number is required";
        
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors));
        }
        
        // Get service details
        $stmt = $db->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$service) {
            throw new Exception("Service not found");
        }
        
        // Calculate total amount
        $price_column = 'price_' . $vehicle_size;
        $total_amount = $service[$price_column];
        
        // Create comprehensive customer notes
        $customer_notes = "SERVICE ADDRESS: " . $service_address . "\n";
        $customer_notes .= "CONTACT NUMBER: " . $contact_number . "\n";
        $customer_notes .= "PAYMENT OPTION: " . $payment_option . "\n";
        $customer_notes .= "VEHICLE DETAILS: " . $vehicle_details . "\n";
        if (!empty($special_requests)) {
            $customer_notes .= "SPECIAL REQUESTS: " . $special_requests;
        }
        
        // Insert booking
        $stmt = $db->prepare("
            INSERT INTO bookings (
                user_id, service_id, vehicle_size, booking_date, booking_time,
                total_amount, vehicle_details, special_requests, status,
                payment_status, estimated_duration, customer_notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 120, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $user_id,
            $service_id,
            $vehicle_size,
            $booking_date,
            $booking_time,
            $total_amount,
            $vehicle_details,
            $special_requests,
            $customer_notes
        ]);
        
        if ($result) {
            $booking_id = $db->lastInsertId();
            $payment_amount = $payment_option === 'full' ? $total_amount : ($total_amount * 0.5);
            $payment_text = $payment_option === 'full' ? 'Full Payment' : '50% Down Payment';
            
            $booking_result = [
                'type' => 'success',
                'message' => "Booking created successfully!",
                'details' => [
                    'booking_id' => $booking_id,
                    'payment_amount' => $payment_amount,
                    'payment_text' => $payment_text,
                    'contact_number' => $contact_number
                ]
            ];
        } else {
            throw new Exception("Failed to create booking");
        }
        
    } catch (Exception $e) {
        $booking_result = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
        error_log("Booking Error: " . $e->getMessage());
    }
}

// Get services directly from database
$stmt = $db->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY service_name");
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get service ID from URL if provided
$selected_service_id = $_GET['service_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Booking - Car Detailing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
            opacity: 0.7;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .wizard-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            border-radius: 20px;
            border: 2px solid #FFD700;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: slideInFromTop 0.8s ease-out;
        }

        @keyframes slideInFromTop {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .wizard-header h1 {
            color: #FFD700;
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 10px #FFD700; }
            to { text-shadow: 2px 2px 4px rgba(0,0,0,0.5), 0 0 20px #FFD700, 0 0 30px #FFD700; }
        }

        .wizard-header p {
            color: #ccc;
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        /* Progress bar */
        .progress-container {
            background: rgba(255,255,255,0.1);
            border-radius: 25px;
            padding: 5px;
            margin-top: 20px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #555;
            border: 3px solid #666;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-weight: bold;
            transition: all 0.5s ease;
            position: relative;
        }

        .step-circle.active {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-color: #FFD700;
            color: #1a1a1a;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
        }

        .step-circle.completed {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-color: #4CAF50;
            color: white;
        }

        .step-title {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #ccc;
            text-align: center;
            transition: color 0.3s ease;
        }

        .step-circle.active + .step-title {
            color: #FFD700;
            font-weight: bold;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: #555;
            z-index: 1;
            transform: translateY(-50%);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #FFD700, #FFA500);
            border-radius: 3px;
            transition: width 0.8s ease;
            width: 0%;
        }

        /* Phase containers */
        .phase {
            display: none;
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            border-radius: 20px;
            border: 2px solid #FFD700;
            padding: 40px;
            margin: 30px 0;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: fadeInSlide 0.6s ease-out;
        }

        .phase.active {
            display: block;
        }

        @keyframes fadeInSlide {
            from { transform: translateX(50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .phase-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .phase-header h2 {
            color: #FFD700;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .phase-header p {
            color: #ccc;
            font-size: 1.1rem;
        }

        /* Service selection styling */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .service-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 2px solid #555;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .service-card:hover::before {
            left: 100%;
        }

        .service-card:hover {
            border-color: #FFD700;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.3);
        }

        .service-card.selected {
            border-color: #FFD700;
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .service-card h3 {
            color: #FFD700;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }

        .service-pricing {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 15px 0;
        }

        .price-item {
            text-align: center;
            padding: 8px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
        }

        .price-item .size {
            font-size: 0.8rem;
            color: #ccc;
        }

        .price-item .price {
            font-weight: bold;
            color: #FFD700;
        }

        /* Vehicle size selection */
        .vehicle-size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .vehicle-size-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 2px solid #555;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .vehicle-size-card:hover {
            border-color: #FFD700;
            transform: scale(1.05);
        }

        .vehicle-size-card.selected {
            border-color: #FFD700;
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .vehicle-icon {
            font-size: 3rem;
            color: #FFD700;
            margin-bottom: 15px;
        }

        /* Navigation buttons */
        .phase-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #555;
        }

        .nav-btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-secondary {
            background: #555;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #666;
        }

        .btn-primary:disabled {
            background: #666;
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Time slots styling */
        .time-slots-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            border: 2px dashed #555;
        }

        .time-slot {
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            border: 2px solid #555;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .time-slot::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.2), transparent);
            transition: left 0.3s ease;
        }

        .time-slot:hover::before {
            left: 100%;
        }

        .time-slot:hover {
            border-color: #FFD700;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 215, 0, 0.3);
        }

        .time-slot.selected {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-color: #FFD700;
            color: #1a1a1a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 215, 0, 0.5);
        }

        /* Availability info styling */
        .availability-info {
            background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%);
            border: 2px solid #4a90e2;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 144, 226, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(74, 144, 226, 0); }
            100% { box-shadow: 0 0 0 0 rgba(74, 144, 226, 0); }
        }

        .availability-info h6 {
            color: #4a90e2;
            margin-bottom: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .wizard-header h1 {
                font-size: 2rem;
            }
            
            .phase-header h2 {
                font-size: 1.8rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .step-circle {
                width: 40px;
                height: 40px;
            }
            
            .step-title {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-plus"></i> Create Booking</h1>
            <p>Fill out the form below to schedule your car detailing service</p>
        </div>

        <?php if ($booking_result): ?>
            <div class="alert alert-<?php echo $booking_result['type']; ?>">
                <strong><?php echo $booking_result['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
                <?php echo htmlspecialchars($booking_result['message']); ?>
                
                <?php if ($booking_result['type'] === 'success' && isset($booking_result['details'])): ?>
                    <div class="success-details">
                        <h3><i class="fas fa-check-circle"></i> Booking Details</h3>
                        <div class="detail-item">
                            <span>Booking ID:</span>
                            <strong>#<?php echo $booking_result['details']['booking_id']; ?></strong>
                        </div>
                        <div class="detail-item">
                            <span><?php echo $booking_result['details']['payment_text']; ?>:</span>
                            <strong>₱<?php echo number_format($booking_result['details']['payment_amount'], 2); ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Contact Number:</span>
                            <strong><?php echo $booking_result['details']['contact_number']; ?></strong>
                        </div>
                        <div class="detail-item">
                            <span>Status:</span>
                            <strong>Awaiting Admin Approval</strong>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="dashboard_CLEAN.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$booking_result || $booking_result['type'] === 'error'): ?>
        <form method="POST" action="" id="bookingForm">
            <input type="hidden" name="action" value="create_booking">
            <input type="hidden" id="vehicle_details" name="vehicle_details" value="">
            
            
            <div class="form-group">
                <label for="service_id"><i class="fas fa-car-wash"></i> Select Service</label>
                <select id="service_id" name="service_id" class="form-control" required>
                    <option value="">Choose a service...</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?php echo $service['service_id']; ?>" 
                                <?php echo $selected_service_id == $service['service_id'] ? 'selected' : ''; ?>
                                data-small="<?php echo $service['price_small']; ?>"
                                data-medium="<?php echo $service['price_medium']; ?>"
                                data-large="<?php echo $service['price_large']; ?>">
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="vehicle_size"><i class="fas fa-car-side"></i> Vehicle Size</label>
                <select id="vehicle_size" name="vehicle_size" class="form-control" required>
                    <option value="">Select vehicle size...</option>
                    <option value="small">Small (Sedan, Hatchback)</option>
                    <option value="medium">Medium (SUV, Pickup)</option>
                    <option value="large">Large (Van, Truck)</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="booking_date"><i class="fas fa-calendar"></i> Preferred Date</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>

                <div class="form-group">
                    <label for="booking_time"><i class="fas fa-clock"></i> Preferred Time</label>
                    <div id="timeSlots" class="time-slots-container">
                        <p class="placeholder-text">Select a date first to see available time slots</p>
                    </div>
                    <input type="hidden" id="booking_time" name="booking_time" required>
                </div>
            </div>

            <!-- Availability Info -->
            <div id="availabilityInfo" class="availability-info" style="display: none;">
                <h6><i class="fas fa-info-circle"></i> Booking Availability</h6>
                <div id="availabilityDetails"></div>
            </div>

            <div class="form-group">
                <label for="service_address"><i class="fas fa-map-marker-alt"></i> Service Address</label>
                <textarea id="service_address" name="service_address" rows="3" class="form-control" required
                          placeholder="Complete address where service will be performed (Street, City, Barangay, Landmarks)"></textarea>
            </div>

            <div class="form-group">
                <label for="contact_number"><i class="fas fa-phone"></i> Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" class="form-control" required
                       placeholder="Your active contact number (e.g., 09123456789)">
            </div>

            <div class="form-group">
                <label><i class="fas fa-car"></i> Vehicle Information</label>
                <div class="vehicle-info-card">
                    <h6><i class="fas fa-info-circle"></i> Get accurate pricing by providing your vehicle details</h6>
                    
                    <div class="vehicle-grid">
                        <div class="vehicle-field">
                            <label for="vehicle_year">Year</label>
                            <select id="vehicle_year" name="vehicle_year" class="form-control">
                                <option value="">Select Year</option>
                                <?php for ($year = date('Y') + 1; $year >= 1990; $year--): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="vehicle-field">
                            <label for="vehicle_make">Make</label>
                            <select id="vehicle_make" name="vehicle_make" class="form-control">
                                <option value="">Select Make</option>
                                <option value="Toyota">Toyota</option>
                                <option value="Honda">Honda</option>
                                <option value="Nissan">Nissan</option>
                                <option value="Mitsubishi">Mitsubishi</option>
                                <option value="Hyundai">Hyundai</option>
                                <option value="Kia">Kia</option>
                                <option value="Suzuki">Suzuki</option>
                                <option value="Mazda">Mazda</option>
                                <option value="Ford">Ford</option>
                                <option value="Chevrolet">Chevrolet</option>
                                <option value="Isuzu">Isuzu</option>
                                <option value="BMW">BMW</option>
                                <option value="Mercedes-Benz">Mercedes-Benz</option>
                                <option value="Audi">Audi</option>
                                <option value="Lexus">Lexus</option>
                                <option value="Infiniti">Infiniti</option>
                                <option value="Volvo">Volvo</option>
                                <option value="Subaru">Subaru</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="vehicle-field">
                            <label for="vehicle_model">Model</label>
                            <input type="text" id="vehicle_model" name="vehicle_model" class="form-control" 
                                   placeholder="e.g., Camry, Civic, Vios">
                        </div>
                        
                        <div class="vehicle-field">
                            <label for="vehicle_trim">Trim/Variant</label>
                            <input type="text" id="vehicle_trim" name="vehicle_trim" class="form-control" 
                                   placeholder="e.g., LE, EX, Base (Optional)">
                        </div>
                        
                        <div class="vehicle-field">
                            <label for="vehicle_body_type">Body Type</label>
                            <select id="vehicle_body_type" name="vehicle_body_type" class="form-control">
                                <option value="">Select Body Type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="Hatchback">Hatchback</option>
                                <option value="SUV">SUV</option>
                                <option value="Crossover">Crossover</option>
                                <option value="Pickup Truck">Pickup Truck</option>
                                <option value="Van">Van</option>
                                <option value="MPV">MPV (Multi-Purpose Vehicle)</option>
                                <option value="Coupe">Coupe</option>
                                <option value="Convertible">Convertible</option>
                                <option value="Wagon">Station Wagon</option>
                                <option value="Jeepney">Jeepney</option>
                                <option value="Tricycle">Tricycle</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="vehicle-field">
                            <label for="vehicle_color">Color</label>
                            <select id="vehicle_color" name="vehicle_color" class="form-control">
                                <option value="">Select Color</option>
                                <option value="White">White</option>
                                <option value="Black">Black</option>
                                <option value="Silver">Silver</option>
                                <option value="Gray">Gray</option>
                                <option value="Red">Red</option>
                                <option value="Blue">Blue</option>
                                <option value="Green">Green</option>
                                <option value="Yellow">Yellow</option>
                                <option value="Orange">Orange</option>
                                <option value="Brown">Brown</option>
                                <option value="Gold">Gold</option>
                                <option value="Purple">Purple</option>
                                <option value="Pink">Pink</option>
                                <option value="Maroon">Maroon</option>
                                <option value="Navy Blue">Navy Blue</option>
                                <option value="Dark Green">Dark Green</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="vehicle-field full-width">
                            <label for="license_plate">License Plate Number</label>
                            <input type="text" id="license_plate" name="license_plate" class="form-control" 
                                   placeholder="e.g., ABC123, XYZ456 (Optional)" style="text-transform: uppercase;">
                        </div>
                    </div>
                    
                    <div class="vehicle-summary" id="vehicleSummary" style="display: none;">
                        <div class="summary-header">
                            <i class="fas fa-car"></i> Vehicle Summary
                        </div>
                        <div class="summary-content" id="summaryContent"></div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="special_requests"><i class="fas fa-comment"></i> Special Requests (Optional)</label>
                <textarea id="special_requests" name="special_requests" rows="3" class="form-control"
                          placeholder="Any special instructions or requests for the service"></textarea>
            </div>

            <div class="form-group">
                <label><i class="fas fa-credit-card"></i> Payment Option</label>
                <div class="payment-options">
                    <div class="payment-card">
                        <input type="radio" name="payment_option" value="partial" id="partial" checked>
                        <label for="partial">
                            <div class="payment-amount">50% Down Payment</div>
                            <div>Pay half now, half after service</div>
                            <div id="partial-amount" style="color: #4caf50; margin-top: 5px;">₱0.00</div>
                        </label>
                    </div>
                    <div class="payment-card">
                        <input type="radio" name="payment_option" value="full" id="full">
                        <label for="full">
                            <div class="payment-amount">Full Payment</div>
                            <div>Pay complete amount now</div>
                            <div id="full-amount" style="color: #4caf50; margin-top: 5px;">₱0.00</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard_CLEAN.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Create Booking
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        // Payment option selection
        document.querySelectorAll('input[name="payment_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-card').forEach(card => {
                    card.classList.remove('selected');
                });
                this.closest('.payment-card').classList.add('selected');
                updatePricing();
            });
        });

        // Price calculation
        function updatePricing() {
            const serviceSelect = document.getElementById('service_id');
            const vehicleSize = document.getElementById('vehicle_size').value;
            
            if (!serviceSelect.value || !vehicleSize) {
                document.getElementById('partial-amount').textContent = '₱0.00';
                document.getElementById('full-amount').textContent = '₱0.00';
                return;
            }
            
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const price = parseFloat(selectedOption.getAttribute('data-' + vehicleSize)) || 0;
            
            document.getElementById('partial-amount').textContent = '₱' + (price * 0.5).toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('full-amount').textContent = '₱' + price.toLocaleString(undefined, {minimumFractionDigits: 2});
        }

        // Update pricing when service or vehicle size changes
        document.getElementById('service_id').addEventListener('change', updatePricing);
        document.getElementById('vehicle_size').addEventListener('change', updatePricing);

        // Initialize payment card selection
        document.querySelector('input[name="payment_option"]:checked').closest('.payment-card').classList.add('selected');

        // Form validation feedback
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ff6b6b';
                } else {
                    field.style.borderColor = '#51cf66';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });

        // Initialize pricing on page load
        updatePricing();

        // Vehicle information handling
        function updateVehicleSummary() {
            const year = document.getElementById('vehicle_year').value;
            const make = document.getElementById('vehicle_make').value;
            const model = document.getElementById('vehicle_model').value.trim();
            const trim = document.getElementById('vehicle_trim').value.trim();
            const bodyType = document.getElementById('vehicle_body_type').value;
            const color = document.getElementById('vehicle_color').value;
            const licensePlate = document.getElementById('license_plate').value.trim();

            const vehicleSummary = document.getElementById('vehicleSummary');
            const summaryContent = document.getElementById('summaryContent');

            // Check if at least year, make, and model are filled
            if (year && make && model) {
                let summary = `${year} ${make} ${model}`;
                
                if (trim) summary += ` ${trim}`;
                if (bodyType && bodyType !== make) summary += ` (${bodyType})`;
                if (color) summary += ` - ${color}`;
                if (licensePlate) summary += ` | Plate: ${licensePlate}`;

                summaryContent.textContent = summary;
                vehicleSummary.style.display = 'block';

                // Auto-update the hidden vehicle_details field for backend compatibility
                document.getElementById('vehicle_details').value = summary;
            } else {
                vehicleSummary.style.display = 'none';
                document.getElementById('vehicle_details').value = '';
            }
        }

        // Add event listeners to all vehicle fields
        document.querySelectorAll('.vehicle-field input, .vehicle-field select').forEach(field => {
            field.addEventListener('input', updateVehicleSummary);
            field.addEventListener('change', updateVehicleSummary);
        });

        // License plate uppercase transformation
        document.getElementById('license_plate').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        // Initialize vehicle summary on page load
        updateVehicleSummary();

        // Advanced booking time slot functionality
        let selectedTimeSlot = null;

        // Load available time slots when date changes
        document.getElementById('booking_date').addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
                loadAvailableTimeSlots(selectedDate);
            } else {
                document.getElementById('timeSlots').innerHTML = '<p class="placeholder-text">Select a date first to see available time slots</p>';
                document.getElementById('availabilityInfo').style.display = 'none';
                selectedTimeSlot = null;
                document.getElementById('booking_time').value = '';
            }
        });

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
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                            <div><strong>Available slots:</strong> ${data.available_slots.length}</div>
                            <div><strong>Business hours:</strong> 8:00 AM - 6:00 PM</div>
                        </div>
                        <p style="margin-top: 10px; font-style: italic;">Maximum 2 bookings per day with travel buffer between appointments</p>
                    `;
                    availabilityInfo.style.display = 'block';
                } else {
                    // No slots available
                    timeSlotsContainer.innerHTML = '<p class="placeholder-text">No available time slots for this date</p>';
                    availabilityDetails.innerHTML = `
                        <p style="color: #f57c00; margin-bottom: 10px;">${data.message || 'This date is fully booked or unavailable'}</p>
                        <div style="margin-top: 10px;">
                            <strong style="color: #4a90e2;">Possible reasons:</strong>
                            <ul style="margin: 8px 0 0 20px; color: #b3d4fc;">
                                <li>Maximum 2 bookings per day reached</li>
                                <li>Weekend selected (weekends not available)</li>
                                <li>Past date selected</li>
                                <li>Beyond 30-day advance booking limit</li>
                            </ul>
                        </div>
                    `;
                    availabilityInfo.style.display = 'block';
                }
                
                // Clear selected time slot when date changes
                selectedTimeSlot = null;
                document.getElementById('booking_time').value = '';
                
            } catch (error) {
                console.error('Error loading time slots:', error);
                document.getElementById('timeSlots').innerHTML = '<p class="placeholder-text" style="color: #d32f2f;">Error loading time slots</p>';
            }
        }

        // Select time slot
        function selectTimeSlot(slotElement) {
            // Remove previous selection
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            
            // Add selection to clicked slot
            slotElement.classList.add('selected');
            selectedTimeSlot = slotElement.dataset.time;
            
            // Update hidden field
            document.getElementById('booking_time').value = selectedTimeSlot;
            
            console.log('Selected time slot:', selectedTimeSlot);
        }

        // Format time for display
        function formatTime(time) {
            return new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        // Form validation update to include time slot selection
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ff6b6b';
                } else {
                    field.style.borderColor = '#51cf66';
                }
            });
            
            // Check if time slot is selected
            if (!selectedTimeSlot) {
                isValid = false;
                document.getElementById('timeSlots').style.borderColor = '#ff6b6b';
                if (!document.querySelector('.time-slot')) {
                    alert('Please select a date first to see available time slots');
                } else {
                    alert('Please select a time slot');
                }
            } else {
                document.getElementById('timeSlots').style.borderColor = '#51cf66';
            }
            
            if (!isValid) {
                e.preventDefault();
                if (selectedTimeSlot) {
                    alert('Please fill in all required fields');
                }
            }
        });
    </script>
</body>
</html>
