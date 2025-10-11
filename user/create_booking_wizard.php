<?php
session_start();

// Redirect if not logged in
// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../login.php');
//     exit;
// }

// Set dummy user_id for testing
$_SESSION['user_id'] = 1;

// Database connection
require_once '../config/database.php';
require_once '../includes/database_functions.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

if (!$db) {
    die("Database connection failed. Please check your configuration.");
}

// Maps configuration - use free version
require_once '../config/maps_config.php';

$booking_result = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    try {
        // Get user ID from session
        $user_id = $_SESSION['user_id'];
        
        // Validate required fields
        $required_fields = ['service_id', 'vehicle_size', 'booking_date', 'booking_time', 'service_address', 'payment_method'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
            }
        }

        // Extract form data
        $service_id = (int) $_POST['service_id'];
        $vehicle_size = $_POST['vehicle_size'];
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $service_address = trim($_POST['service_address']);
        $payment_method = $_POST['payment_method'];
        $total_amount = (float) $_POST['total_amount'];
        
        // Vehicle details
        $vehicle_year = $_POST['vehicle_year'] ?? '';
        $vehicle_make = $_POST['vehicle_make'] ?? '';
        $vehicle_model = $_POST['vehicle_model'] ?? '';
        $vehicle_body_type = $_POST['vehicle_body_type'] ?? '';
        $vehicle_color = $_POST['vehicle_color'] ?? '';
        $license_plate = $_POST['license_plate'] ?? '';
        $special_instructions = $_POST['special_instructions'] ?? '';
        
        // Create vehicle details string
        $vehicle_details = [];
        if ($vehicle_year) $vehicle_details[] = $vehicle_year;
        if ($vehicle_make) $vehicle_details[] = $vehicle_make;
        if ($vehicle_model) $vehicle_details[] = $vehicle_model;
        if ($vehicle_color) $vehicle_details[] = $vehicle_color;
        if ($license_plate) $vehicle_details[] = "Plate: " . $license_plate;
        
        $vehicle_info = implode(' ', $vehicle_details);
        if (empty($vehicle_info)) {
            $vehicle_info = ucfirst($vehicle_size) . ' vehicle';
        }
        
        // Get service details
        $service = $carDB->getService($service_id);
        
        if (!$service) {
            throw new Exception("Selected service not found.");
        }
        
        // Validate date and time
        $booking_datetime = $booking_date . ' ' . $booking_time;
        if (strtotime($booking_datetime) <= time()) {
            throw new Exception("Booking date and time must be in the future.");
        }
        
        // Check availability
        require_once '../includes/BookingAvailabilityChecker.php';
        $checker = new BookingAvailabilityChecker($db);
        
        if (!$checker->isTimeSlotAvailable($booking_date, $booking_time, null)) {
            throw new Exception("Selected time slot is not available. Please choose another time.");
        }
        
        // Insert booking
        $stmt = $db->prepare("
            INSERT INTO bookings (
                user_id, service_id, vehicle_size, booking_date, booking_time, 
                service_address, vehicle_details, payment_method, total_amount,
                vehicle_year, vehicle_make, vehicle_model, vehicle_body_type,
                vehicle_color, license_plate, special_instructions,
                status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                'pending', NOW()
            )
        ");
        
        $result = $stmt->execute([
            $user_id, $service_id, $vehicle_size, $booking_date, $booking_time,
            $service_address, $vehicle_info, $payment_method, $total_amount,
            $vehicle_year, $vehicle_make, $vehicle_model, $vehicle_body_type,
            $vehicle_color, $license_plate, $special_instructions
        ]);
        
        if ($result) {
            $booking_id = $db->lastInsertId();
            
            // Store success details in session
            $_SESSION['booking_success'] = [
                'booking_id' => $booking_id,
                'service_name' => $service['service_name'],
                'vehicle_size' => $vehicle_size,
                'booking_date' => $booking_date,
                'booking_time' => $booking_time,
                'service_address' => $service_address,
                'vehicle_info' => $vehicle_info,
                'payment_method' => $payment_method,
                'total_amount' => $total_amount
            ];
            
            $booking_result = [
                'type' => 'success',
                'message' => "Booking created successfully!",
                'details' => $_SESSION['booking_success']
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

// Get services from database using the same method as dashboard
$services = $carDB->getServices();

// Get service ID from URL if provided
$selected_service_id = $_GET['service_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Service - Car Detailing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Google Maps API Configuration -->
    <script>
        // Configuration from PHP
        const GOOGLE_MAPS_API_KEY = '<?= GOOGLE_MAPS_API_KEY ?>';
        const DEFAULT_MAP_LAT = <?= DEFAULT_MAP_LAT ?>;
        const DEFAULT_MAP_LNG = <?= DEFAULT_MAP_LNG ?>;
        const MAP_COUNTRY_RESTRICTION = '<?= MAP_COUNTRY_RESTRICTION ?>';
        
        // Load Google Maps API dynamically
        function loadGoogleMapsAPI() {
            if (GOOGLE_MAPS_API_KEY === 'YOUR_GOOGLE_MAPS_API_KEY') {
                console.warn('⚠️ Google Maps API key not configured. Please update config/maps_config.php');
                showMapFallback();
                return;
            }
            
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAPS_API_KEY}&libraries=places&callback=initMap`;
            script.async = true;
            script.defer = true;
            script.onerror = function() {
                console.error('❌ Failed to load Google Maps API. Please check your API key and billing settings.');
                showMapFallback();
            };
            document.head.appendChild(script);
        }
        
        function showMapFallback() {
            const mapElement = document.getElementById('map');
            if (mapElement) {
                mapElement.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: #FFD700; font-size: 1.1rem; text-align: center; padding: 20px; border-radius: 10px;">
                        <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.7;"></i>
                        <div style="margin-bottom: 10px; font-weight: bold;">Interactive Map Unavailable</div>
                        <div style="font-size: 0.9rem; color: #ccc; line-height: 1.4;">
                            Please enter your complete address above.<br>
                            Our team will contact you to confirm the exact location.
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: rgba(255,215,0,0.1); border-radius: 5px; font-size: 0.8rem; color: #FFD700;">
                            💡 To enable interactive maps, configure your Google Maps API key in config/maps_config.php
                        </div>
                    </div>
                `;
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Inter", "SF Pro Display", "SF Pro Text", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            font-weight: 400;
            letter-spacing: -0.003em;
            line-height: 1.47059;
            font-synthesis: none;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Remove animated particles for cleaner look */
        .particles {
            display: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            position: relative;
            z-index: 10;
        }

        .wizard-header {
            text-align: center;
            margin-bottom: 120px;
            padding: 80px 0;
            background: transparent;
            border-radius: 0;
            border: none;
            box-shadow: none;
            animation: none;
        }

        .wizard-header h1 {
            color: #ffffff;
            font-size: 5rem;
            font-weight: 100;
            margin-bottom: 30px;
            text-shadow: none;
            animation: none;
            letter-spacing: -0.05em;
            line-height: 1;
        }

        .wizard-header p {
            color: rgba(255, 255, 255, 0.4);
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 60px;
            letter-spacing: 0.02em;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Progress bar - Apple minimalist style */
        .progress-container {
            background: transparent;
            border-radius: 0;
            padding: 0;
            margin: 60px 0 120px 0;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            max-width: 800px;
            margin: 0 auto;
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
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-weight: normal;
            transition: all 0.3s ease;
            position: relative;
        }

        .step-circle.active {
            background: #FFD700;
            border-color: transparent;
            color: transparent;
            transform: scale(1.3);
            box-shadow: none;
        }

        .step-circle.completed {
            background: #FFD700;
            border-color: transparent;
            color: transparent;
        }

        .step-title {
            margin-top: 20px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
            transition: color 0.3s ease;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .step-circle.active + .step-title {
            color: #FFD700;
            font-weight: 400;
        }

        .progress-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
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

        /* Phase containers - Apple minimalist style */
        .phase {
            display: none;
            background: transparent;
            border-radius: 0;
            border: none;
            padding: 60px 0;
            margin: 0;
            box-shadow: none;
            animation: none;
        }

        .phase.active {
            display: block;
        }

        .phase-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .phase-header h2 {
            color: #ffffff;
            font-size: 3rem;
            font-weight: 200;
            margin-bottom: 20px;
            text-shadow: none;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .phase-header p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        /* Service selection styling */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }

        .service-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 2px solid #333;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            height: 320px;
            display: flex;
            flex-direction: column;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.1), transparent);
            transition: left 0.6s ease;
            z-index: 1;
        }

        .service-card:hover::before {
            left: 100%;
        }

        .service-card:hover {
            border-color: #FFD700;
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.3);
        }

        .service-card.selected {
            border-color: #FFD700;
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.6);
            transform: translateY(-5px);
        }

        .service-card-header {
            position: relative;
            height: 160px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .service-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26,26,26,0.8) 0%, rgba(45,45,45,0.6) 100%);
            z-index: 1;
        }

        .service-icon {
            font-size: 4rem;
            z-index: 2;
            position: relative;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .service-card-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .service-card h3 {
            color: #FFD700;
            margin: 0 0 10px 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .service-card p {
            color: #ccc;
            font-size: 0.9rem;
            line-height: 1.4;
            margin: 0 0 15px 0;
            flex: 1;
        }

        .service-pricing {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: auto;
        }

        .price-item {
            text-align: center;
            padding: 8px 6px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            flex: 1;
            transition: all 0.3s ease;
        }

        .price-item:hover {
            background: rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.4);
        }

        .price-item .size {
            font-size: 0.75rem;
            color: #FFD700;
            font-weight: 500;
            margin-bottom: 2px;
        }

        .price-item .price {
            font-weight: bold;
            color: #fff;
            font-size: 0.9rem;
        }

        .service-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 215, 0, 0.9);
            color: #1a1a1a;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 3;
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
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .vehicle-icon {
            font-size: 3rem;
            color: #FFD700;
            margin-bottom: 15px;
        }

        /* Form styling */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #FFD700;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #555;
            border-radius: 8px;
            background: #1a1a1a;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #FFD700;
            outline: none;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
        }

        /* Navigation buttons - Apple style */
        .phase-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 80px;
            padding-top: 0;
            border-top: none;
        }

        .nav-btn {
            padding: 15px 40px;
            border: none;
            border-radius: 0;
            font-size: 1rem;
            font-weight: 400;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            letter-spacing: 0.01em;
        }

        .btn-primary {
            background: #FFD700;
            color: #1a1a1a;
        }

        .btn-primary:hover {
            background: #ffffff;
            color: #1a1a1a;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: transparent;
            color: #FFD700;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 215, 0, 0.1);
            border-color: #FFD700;
        }

        .btn-primary:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.3);
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
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
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

        /* Map styling */
        #map {
            height: 300px; 
            border-radius: 10px; 
            margin: 20px 0; 
            border: 2px solid #555;
            background: #1a1a1a;
        }

        /* Vehicle info grid */
        .vehicle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .vehicle-field {
            display: flex;
            flex-direction: column;
        }

        .vehicle-field.full-width {
            grid-column: 1 / -1;
        }

        /* Payment options */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .payment-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 2px solid #555;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .payment-card:hover {
            border-color: #FFD700;
            transform: translateY(-5px);
        }

        .payment-card.selected {
            border-color: #FFD700;
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
        }

        .payment-card h4 {
            color: #FFD700;
            margin: 15px 0 10px 0;
        }

        /* Success styling */
        .success-details {
            background: linear-gradient(135deg, #155724 0%, #28a745 100%);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
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
            
            .payment-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <?php if ($booking_result && $booking_result['type'] === 'success'): ?>
            <!-- Success Page -->
            <div class="wizard-header">
                <h1>✅ Booking Confirmed!</h1>
                <p>Your car detailing service has been successfully scheduled</p>
            </div>

            <div class="success-details">
                <h3>🎉 Booking Details</h3>
                <div class="detail-item">
                    <span><strong>Booking ID:</strong></span>
                    <span>#<?= $booking_result['details']['booking_id'] ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Service:</strong></span>
                    <span><?= htmlspecialchars($booking_result['details']['service_name']) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Vehicle Size:</strong></span>
                    <span><?= ucfirst($booking_result['details']['vehicle_size']) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Date:</strong></span>
                    <span><?= date('F d, Y', strtotime($booking_result['details']['booking_date'])) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Time:</strong></span>
                    <span><?= date('h:i A', strtotime($booking_result['details']['booking_time'])) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Location:</strong></span>
                    <span><?= htmlspecialchars($booking_result['details']['service_address']) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Vehicle:</strong></span>
                    <span><?= htmlspecialchars($booking_result['details']['vehicle_info']) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Payment Method:</strong></span>
                    <span><?= ucfirst($booking_result['details']['payment_method']) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Total Amount:</strong></span>
                    <span>₱<?= number_format($booking_result['details']['total_amount'], 2) ?></span>
                </div>
                <div class="detail-item">
                    <span><strong>Status:</strong></span>
                    <span style="color: #ffd700;">Pending Approval</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="dashboard_CLEAN.php" class="nav-btn btn-primary">Return to Dashboard</a>
            </div>

        <?php else: ?>
            <!-- Wizard Header -->
            <div class="wizard-header">
                <h1>🚗 Premium Booking Experience</h1>
                <p>Your journey to a spotless vehicle starts here</p>
                
                <!-- Progress Bar -->
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-line">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>
                        
                        <div class="progress-step">
                            <div class="step-circle active" id="step1">1</div>
                            <div class="step-title">Choose Service</div>
                        </div>
                        
                        <div class="progress-step">
                            <div class="step-circle" id="step2">2</div>
                            <div class="step-title">Date & Time</div>
                        </div>
                        
                        <div class="progress-step">
                            <div class="step-circle" id="step3">3</div>
                            <div class="step-title">Location</div>
                        </div>
                        
                        <div class="progress-step">
                            <div class="step-circle" id="step4">4</div>
                            <div class="step-title">Payment</div>
                        </div>
                        
                        <div class="progress-step">
                            <div class="step-circle" id="step5">5</div>
                            <div class="step-title">Confirmation</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($booking_result && $booking_result['type'] === 'error'): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #f5c6cb;">
                    <strong>Error:</strong> <?= htmlspecialchars($booking_result['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Service Selection Summary (shown when pre-selected) -->
            <div id="serviceSelectionSummary" class="phase">
                <div style="background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); border-radius: 15px; padding: 25px; margin-bottom: 30px; color: white; text-align: center;">
                    <h3 style="margin-bottom: 15px;"><i class="fas fa-check-circle"></i> Service Selected</h3>
                    <div id="selectedServiceInfo" style="font-size: 1.1rem; font-weight: 600;"></div>
                    <div style="margin-top: 15px; font-size: 0.9rem; opacity: 0.9;">
                        <a href="#" onclick="showServiceSelection()" style="color: white; text-decoration: underline;">
                            Want to change? Click here to select a different service
                        </a>
                    </div>
                </div>

                <!-- Vehicle Size Selection -->
                <h3 style="color: #FFD700; margin: 30px 0 20px 0; text-align: center;">🚗 Select Your Vehicle Size</h3>
                <div class="vehicle-size-grid">
                    <div class="vehicle-size-card" data-size="small" data-label="Small Vehicle">
                        <div class="vehicle-icon">🚗</div>
                        <h4>Small Vehicle</h4>
                        <p>Sedan, Hatchback, Coupe</p>
                        <div class="selected-price" id="smallPriceSummary"></div>
                    </div>
                    <div class="vehicle-size-card" data-size="medium" data-label="Medium Vehicle">
                        <div class="vehicle-icon">🚙</div>
                        <h4>Medium Vehicle</h4>
                        <p>SUV, Crossover, Pickup</p>
                        <div class="selected-price" id="mediumPriceSummary"></div>
                    </div>
                    <div class="vehicle-size-card" data-size="large" data-label="Large Vehicle">
                        <div class="vehicle-icon">🚛</div>
                        <h4>Large Vehicle</h4>
                        <p>Van, Truck, Large SUV</p>
                        <div class="selected-price" id="largePriceSummary"></div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button class="nav-btn btn-primary" id="continueToDateTime" disabled>
                        <i class="fas fa-calendar-alt"></i> Continue to Date & Time
                    </button>
                </div>
            </div>

            <!-- Phase 1: Service Selection -->
            <div class="phase active" id="phase1">
                <div class="phase-header">
                    <h2>✨ Select Your Service</h2>
                    <p>Choose the perfect detailing package for your vehicle</p>
                </div>

                <div class="services-grid">
                    <?php 
                    $service_icons = [
                        'Interior Cleaning' => '🪟',
                        'Exterior Wash' => '🚗', 
                        'Full Detail Package' => '✨',
                        'Engine Cleaning' => '🔧',
                        'Tire & Rim Cleaning' => '🛞',
                        'Glass Polishing' => '💎',
                        'Wax & Polish' => '🌟'
                    ];
                    foreach ($services as $service): 
                    $icon = $service_icons[$service['service_name']] ?? '🚘';
                    ?>
                    <div class="service-card" data-service-id="<?= $service['service_id'] ?>" 
                         data-service-name="<?= htmlspecialchars($service['service_name']) ?>"
                         data-small="<?= $service['price_small'] ?>"
                         data-medium="<?= $service['price_medium'] ?>"
                         data-large="<?= $service['price_large'] ?>">
                        
                        <div class="service-card-header">
                            <div class="service-icon"><?= $icon ?></div>
                            <div class="service-badge">Premium</div>
                        </div>
                        
                        <div class="service-card-body">
                            <h3><?= htmlspecialchars($service['service_name']) ?></h3>
                            <p><?= htmlspecialchars($service['description']) ?></p>
                            
                            <div class="service-pricing">
                                <div class="price-item">
                                    <div class="size">Small</div>
                                    <div class="price">₱<?= number_format($service['price_small'], 0) ?></div>
                                </div>
                                <div class="price-item">
                                    <div class="size">Medium</div>
                                    <div class="price">₱<?= number_format($service['price_medium'], 0) ?></div>
                                </div>
                                <div class="price-item">
                                    <div class="size">Large</div>
                                    <div class="price">₱<?= number_format($service['price_large'], 0) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Vehicle Size Selection -->
                <div id="vehicleSizeSection" style="display: none;">
                    <h3 style="color: #FFD700; margin: 30px 0 20px 0; text-align: center;">🚗 Select Your Vehicle Size</h3>
                    <div class="vehicle-size-grid">
                        <div class="vehicle-size-card" data-size="small" data-label="Small Vehicle">
                            <div class="vehicle-icon">🚗</div>
                            <h4>Small Vehicle</h4>
                            <p>Sedan, Hatchback, Coupe</p>
                            <div class="selected-price" id="smallPrice"></div>
                        </div>
                        <div class="vehicle-size-card" data-size="medium" data-label="Medium Vehicle">
                            <div class="vehicle-icon">🚙</div>
                            <h4>Medium Vehicle</h4>
                            <p>SUV, Crossover, Pickup</p>
                            <div class="selected-price" id="mediumPrice"></div>
                        </div>
                        <div class="vehicle-size-card" data-size="large" data-label="Large Vehicle">
                            <div class="vehicle-icon">🚛</div>
                            <h4>Large Vehicle</h4>
                            <p>Van, Truck, Large SUV</p>
                            <div class="selected-price" id="largePrice"></div>
                        </div>
                    </div>
                </div>

                <div class="phase-navigation">
                    <div></div>
                    <button class="nav-btn btn-primary" id="nextToDateTime" disabled>Next: Date & Time</button>
                </div>
            </div>

            <!-- Phase 2: Date & Time Selection -->
            <div class="phase" id="phase2">
                <div class="phase-header">
                    <h2>🗓️ Schedule Your Service</h2>
                    <p>Pick the perfect date and time for your car detailing</p>
                </div>

                <div class="form-group">
                    <label for="booking_date"><i class="fas fa-calendar-alt"></i> Select Date</label>
                    <input type="date" id="booking_date" name="booking_date" class="form-control" required>
                </div>

                <div style="background: linear-gradient(135deg, rgba(74, 144, 226, 0.1) 0%, rgba(74, 144, 226, 0.05) 100%); border: 2px solid #4a90e2; border-radius: 15px; padding: 20px; margin: 20px 0;">
                    <h6 style="color: #4a90e2; margin-bottom: 15px; font-size: 1.1rem;"><i class="fas fa-info-circle"></i> Availability Information</h6>
                    <div id="availabilityDetails" style="color: #b3d4fc; line-height: 1.6;">Select a date to view available time slots</div>
                </div>

                <div class="form-group">
                    <label for="booking_time"><i class="fas fa-clock"></i> Available Time Slots</label>
                    <div class="time-slots-container" id="timeSlotsContainer">
                        <div style="color: #888; font-style: italic; text-align: center; grid-column: 1 / -1; display: flex; align-items: center; justify-content: center; min-height: 40px;">Please select a date first</div>
                    </div>
                    <input type="hidden" id="booking_time" name="booking_time" required>
                </div>

                <div class="phase-navigation">
                    <button class="nav-btn btn-secondary" id="backToService">← Back</button>
                    <button class="nav-btn btn-primary" id="nextToLocation" disabled>Next: Location</button>
                </div>
            </div>

            <!-- Phase 3: Location Selection -->
            <div class="phase" id="phase3">
                <div class="phase-header">
                    <h2>📍 Service Location</h2>
                    <p>Where should we come to detail your vehicle?</p>
                </div>

                <div class="form-group">
                    <label for="service_address"><i class="fas fa-map-marker-alt"></i> Service Address</label>
                    <input type="text" id="service_address" name="service_address" class="form-control" 
                           placeholder="Enter your address or search location..." required>
                    <small style="color: #ccc; font-size: 0.9rem; margin-top: 5px; display: block;">
                        We'll come to your location for convenient mobile service
                    </small>
                </div>

                <!-- Map Container -->
                <div id="map"></div>

                <!-- Vehicle Information -->
                <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border: 2px solid #555; border-radius: 12px; padding: 25px; margin-top: 20px;">
                    <h6 style="color: #FFD700; margin-bottom: 20px; font-size: 1rem;"><i class="fas fa-car"></i> Vehicle Information</h6>
                    <div class="vehicle-grid">
                        <div class="vehicle-field">
                            <label for="vehicle_year">Year</label>
                            <input type="number" id="vehicle_year" name="vehicle_year" class="form-control" 
                                   placeholder="2020" min="1990" max="2025">
                        </div>
                        <div class="vehicle-field">
                            <label for="vehicle_make">Make</label>
                            <input type="text" id="vehicle_make" name="vehicle_make" class="form-control" 
                                   placeholder="Toyota">
                        </div>
                        <div class="vehicle-field">
                            <label for="vehicle_model">Model</label>
                            <input type="text" id="vehicle_model" name="vehicle_model" class="form-control" 
                                   placeholder="Camry">
                        </div>
                        <div class="vehicle-field">
                            <label for="vehicle_body_type">Body Type</label>
                            <select id="vehicle_body_type" name="vehicle_body_type" class="form-control">
                                <option value="">Select body type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="Hatchback">Hatchback</option>
                                <option value="SUV">SUV</option>
                                <option value="Coupe">Coupe</option>
                                <option value="Convertible">Convertible</option>
                                <option value="Wagon">Wagon</option>
                                <option value="Pickup">Pickup Truck</option>
                                <option value="Van">Van</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="vehicle-field">
                            <label for="vehicle_color">Color</label>
                            <input type="text" id="vehicle_color" name="vehicle_color" class="form-control" 
                                   placeholder="White">
                        </div>
                        <div class="vehicle-field">
                            <label for="license_plate">License Plate</label>
                            <input type="text" id="license_plate" name="license_plate" class="form-control" 
                                   placeholder="ABC-1234" style="text-transform: uppercase;">
                        </div>
                        <div class="vehicle-field full-width">
                            <label for="special_instructions">Special Instructions (Optional)</label>
                            <textarea id="special_instructions" name="special_instructions" class="form-control" 
                                      rows="3" placeholder="Any specific requests or areas of concern..."></textarea>
                        </div>
                    </div>

                    <div id="vehicleSummary" style="background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%); border: 1px solid #FFD700; border-radius: 8px; padding: 15px; margin-top: 15px; display: none;">
                        <div style="color: #FFD700; font-weight: 600; margin-bottom: 10px;"><i class="fas fa-clipboard-list"></i> Vehicle Summary</div>
                        <div id="summaryContent" style="color: #fff; font-size: 0.95rem; line-height: 1.4;"></div>
                    </div>
                </div>

                <div class="phase-navigation">
                    <button class="nav-btn btn-secondary" id="backToDateTime">← Back</button>
                    <button class="nav-btn btn-primary" id="nextToPayment" disabled>Next: Payment</button>
                </div>
            </div>

            <!-- Phase 4: Payment Selection -->
            <div class="phase" id="phase4">
                <div class="phase-header">
                    <h2>💳 Payment Method</h2>
                    <p>Choose how you'd like to pay for your service</p>
                </div>

                <div style="background: rgba(255,215,0,0.1); border: 2px solid #FFD700; border-radius: 15px; padding: 25px; margin-bottom: 30px;">
                    <h3 style="color: #FFD700; margin-bottom: 15px;">📋 Booking Summary</h3>
                    <div id="bookingSummaryContent">
                        <!-- Summary will be populated by JavaScript -->
                    </div>
                </div>

                <div class="payment-options">
                    <div class="payment-card" data-payment="cash">
                        <input type="radio" name="payment_method" value="cash" id="payment_cash" style="display: none;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">💵</div>
                        <h4>Cash Payment</h4>
                        <p>Pay when we arrive</p>
                        <div style="font-size: 1.2rem; font-weight: bold; color: #FFD700; margin-top: 10px;" id="cashAmount"></div>
                    </div>
                    
                    <div class="payment-card" data-payment="gcash">
                        <input type="radio" name="payment_method" value="gcash" id="payment_gcash" style="display: none;">
                        <div style="font-size: 2rem; margin-bottom: 10px;">📱</div>
                        <h4>GCash</h4>
                        <p>Digital payment</p>
                        <div style="font-size: 1.2rem; font-weight: bold; color: #FFD700; margin-top: 10px;" id="gcashAmount"></div>
                    </div>
                </div>

                <div class="phase-navigation">
                    <button class="nav-btn btn-secondary" id="backToLocation">← Back</button>
                    <button class="nav-btn btn-primary" id="submitBooking" disabled>Complete Booking</button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden form for final submission -->
    <form id="bookingForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="create_booking">
        <input type="hidden" name="service_id" id="final_service_id">
        <input type="hidden" name="vehicle_size" id="final_vehicle_size">
        <input type="hidden" name="booking_date" id="final_booking_date">
        <input type="hidden" name="booking_time" id="final_booking_time">
        <input type="hidden" name="service_address" id="final_service_address">
        <input type="hidden" name="vehicle_year" id="final_vehicle_year">
        <input type="hidden" name="vehicle_make" id="final_vehicle_make">
        <input type="hidden" name="vehicle_model" id="final_vehicle_model">
        <input type="hidden" name="vehicle_body_type" id="final_vehicle_body_type">
        <input type="hidden" name="vehicle_color" id="final_vehicle_color">
        <input type="hidden" name="license_plate" id="final_license_plate">
        <input type="hidden" name="special_instructions" id="final_special_instructions">
        <input type="hidden" name="payment_method" id="final_payment_method">
        <input type="hidden" name="total_amount" id="final_total_amount">
    </form>

    <script>
        // Helper function to convert 12-hour format to 24-hour format
        function convertTo24Hour(time12h) {
            const [time, modifier] = time12h.split(' ');
            let [hours, minutes] = time.split(':');
            
            if (hours === '12') {
                hours = '00';
            }
            
            if (modifier === 'PM' || modifier === 'pm') {
                hours = parseInt(hours, 10) + 12;
            }
            
            return `${hours.toString().padStart(2, '0')}:${minutes}:00`;
        }

        // Global variables to store booking data
        let bookingData = {
            serviceId: null,
            serviceName: '',
            vehicleSize: '',
            priceData: {},
            selectedPrice: 0,
            bookingDate: '',
            bookingTime: '',
            serviceAddress: '',
            vehicleInfo: {},
            paymentMethod: ''
        };

        let currentPhase = 1;
        const totalPhases = 4;

        // Function to show service selection phase
        function showServiceSelection() {
            document.getElementById('serviceSelectionSummary').classList.remove('active');
            showPhase(1);
        }

        // Initialize particles
        function createParticles() {
            const particles = document.getElementById('particles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particles.appendChild(particle);
            }
        }

        // Update progress bar
        function updateProgress() {
            const progressFill = document.getElementById('progressFill');
            
            // Handle special case for service summary (phase 1.5)
            let progressPhase = currentPhase === 1.5 ? 1 : currentPhase;
            const progressPercentage = ((progressPhase - 1) / (totalPhases - 1)) * 100;
            progressFill.style.width = progressPercentage + '%';

            // Update step circles
            for (let i = 1; i <= totalPhases + 1; i++) {
                const stepCircle = document.getElementById(`step${i}`);
                if (stepCircle) {
                    stepCircle.classList.remove('active', 'completed');
                    if (i < progressPhase || (currentPhase === 1.5 && i === 1)) {
                        stepCircle.classList.add('completed');
                        stepCircle.innerHTML = '<i class="fas fa-check"></i>';
                    } else if (i === progressPhase || (currentPhase === 1.5 && i === 1)) {
                        stepCircle.classList.add('active');
                        stepCircle.innerHTML = i;
                    } else {
                        stepCircle.innerHTML = i;
                    }
                }
            }
        }

        // Show specific phase
        function showPhase(phaseNumber) {
            // Hide all phases including service summary
            document.querySelectorAll('.phase').forEach(phase => {
                phase.classList.remove('active');
            });
            
            // Show target phase
            if (phaseNumber === 1.5) {
                // Special case for service summary
                document.getElementById('serviceSelectionSummary').classList.add('active');
                currentPhase = 1.5;
            } else {
                document.getElementById(`phase${phaseNumber}`).classList.add('active');
                currentPhase = phaseNumber;
            }
            
            updateProgress();
        }

        // Phase 1: Service Selection
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove previous selections
                document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
                
                // Select this card
                this.classList.add('selected');
                
                // Store service data
                bookingData.serviceId = this.dataset.serviceId;
                bookingData.serviceName = this.dataset.serviceName;
                bookingData.priceData = {
                    small: parseFloat(this.dataset.small),
                    medium: parseFloat(this.dataset.medium),
                    large: parseFloat(this.dataset.large)
                };

                // Update prices in vehicle size cards
                document.getElementById('smallPrice').textContent = `₱${parseFloat(this.dataset.small).toLocaleString()}`;
                document.getElementById('mediumPrice').textContent = `₱${parseFloat(this.dataset.medium).toLocaleString()}`;
                document.getElementById('largePrice').textContent = `₱${parseFloat(this.dataset.large).toLocaleString()}`;

                // Show vehicle size section
                document.getElementById('vehicleSizeSection').style.display = 'block';
                
                // Reset vehicle size selection
                document.querySelectorAll('.vehicle-size-card').forEach(c => c.classList.remove('selected'));
                bookingData.vehicleSize = '';
                document.getElementById('nextToDateTime').disabled = true;
            });
        });

        // Vehicle size selection
        document.querySelectorAll('.vehicle-size-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.vehicle-size-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                bookingData.vehicleSize = this.dataset.size;
                bookingData.selectedPrice = bookingData.priceData[this.dataset.size];
                
                document.getElementById('nextToDateTime').disabled = false;
            });
        });

        // Phase 2: Date & Time
        document.getElementById('booking_date').addEventListener('change', function() {
            const selectedDate = this.value;
            bookingData.bookingDate = selectedDate;
            
            if (selectedDate) {
                loadTimeSlots(selectedDate);
                updateNextButtonState();
            }
        });

        async function loadTimeSlots(date) {
            try {
                const response = await fetch('../api/get_available_slots.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ date: date })
                });
                
                const data = await response.json();
                const container = document.getElementById('timeSlotsContainer');
                
                if (data.available_slots && data.available_slots.length > 0) {
                    container.innerHTML = '';
                    
                    data.available_slots.forEach(slot => {
                        const timeSlot = document.createElement('div');
                        timeSlot.className = 'time-slot';
                        timeSlot.textContent = slot; // Display 12-hour format (e.g., "9:00 AM")
                        
                        // Convert to 24-hour format for submission
                        const time24 = convertTo24Hour(slot);
                        timeSlot.dataset.time = time24;
                        
                        timeSlot.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot').forEach(ts => ts.classList.remove('selected'));
                            this.classList.add('selected');
                            bookingData.bookingTime = time24; // Store 24-hour format
                            updateNextButtonState();
                        });
                        
                        container.appendChild(timeSlot);
                    });
                } else {
                    container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #888; padding: 20px;">No available time slots for this date</div>';
                }

                // Update availability details
                const availabilityDetails = document.getElementById('availabilityDetails');
                if (data.message) {
                    availabilityDetails.textContent = data.message;
                }
                
            } catch (error) {
                console.error('Error loading time slots:', error);
                document.getElementById('timeSlotsContainer').innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #ff6b6b; padding: 20px;">Error loading time slots</div>';
            }
        }

        // Phase 3: Location & Vehicle Info
        document.getElementById('service_address').addEventListener('input', function() {
            bookingData.serviceAddress = this.value;
            updateNextButtonState();
        });

        // Vehicle info inputs
        ['vehicle_year', 'vehicle_make', 'vehicle_model', 'vehicle_body_type', 'vehicle_color', 'license_plate', 'special_instructions'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', function() {
                    bookingData.vehicleInfo[fieldId] = this.value;
                    updateVehicleSummary();
                });
            }
        });

        function updateVehicleSummary() {
            const summary = document.getElementById('vehicleSummary');
            const content = document.getElementById('summaryContent');
            
            const info = bookingData.vehicleInfo;
            const parts = [];
            
            if (info.vehicle_year) parts.push(info.vehicle_year);
            if (info.vehicle_make) parts.push(info.vehicle_make);
            if (info.vehicle_model) parts.push(info.vehicle_model);
            if (info.vehicle_color) parts.push(info.vehicle_color);
            if (info.license_plate) parts.push(`(${info.license_plate})`);
            
            if (parts.length > 0) {
                content.textContent = parts.join(' ');
                summary.style.display = 'block';
            } else {
                summary.style.display = 'none';
            }
        }

        // Phase 4: Payment
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                bookingData.paymentMethod = this.dataset.payment;
                
                // Update hidden radio button
                document.getElementById(`payment_${this.dataset.payment}`).checked = true;
                
                document.getElementById('submitBooking').disabled = false;
            });
        });

        // Navigation
        document.getElementById('nextToDateTime').addEventListener('click', () => showPhase(2));
        document.getElementById('backToService').addEventListener('click', () => showPhase(1));
        document.getElementById('nextToLocation').addEventListener('click', () => showPhase(3));
        document.getElementById('backToDateTime').addEventListener('click', () => showPhase(2));
        document.getElementById('nextToPayment').addEventListener('click', () => {
            updateBookingSummary();
            showPhase(4);
        });
        document.getElementById('backToLocation').addEventListener('click', () => showPhase(3));

        function updateNextButtonState() {
            // Phase 2 validation
            if (currentPhase === 2) {
                const hasDate = bookingData.bookingDate !== '';
                const hasTime = bookingData.bookingTime !== '';
                document.getElementById('nextToLocation').disabled = !(hasDate && hasTime);
            }
            
            // Phase 3 validation
            if (currentPhase === 3) {
                const hasAddress = bookingData.serviceAddress.trim() !== '';
                document.getElementById('nextToPayment').disabled = !hasAddress;
            }
        }

        function updateBookingSummary() {
            const summaryContent = document.getElementById('bookingSummaryContent');
            const cashAmount = document.getElementById('cashAmount');
            const gcashAmount = document.getElementById('gcashAmount');
            
            const price = bookingData.selectedPrice;
            
            summaryContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,215,0,0.3);">
                    <span><strong>Service:</strong></span>
                    <span>${bookingData.serviceName}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,215,0,0.3);">
                    <span><strong>Vehicle Size:</strong></span>
                    <span>${bookingData.vehicleSize.charAt(0).toUpperCase() + bookingData.vehicleSize.slice(1)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid rgba(255,215,0,0.3);">
                    <span><strong>Date & Time:</strong></span>
                    <span>${new Date(bookingData.bookingDate).toLocaleDateString()} at ${bookingData.bookingTime}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding: 8px 0; border-bottom: 1px solid rgba(255,215,0,0.3);">
                    <span><strong>Total Amount:</strong></span>
                    <span style="color: #FFD700; font-weight: bold; font-size: 1.2rem;">₱${price.toLocaleString()}</span>
                </div>
            `;
            
            cashAmount.textContent = `₱${price.toLocaleString()}`;
            gcashAmount.textContent = `₱${price.toLocaleString()}`;
        }

        // Final submission
        document.getElementById('submitBooking').addEventListener('click', function() {
            // Populate hidden form
            document.getElementById('final_service_id').value = bookingData.serviceId;
            document.getElementById('final_vehicle_size').value = bookingData.vehicleSize;
            document.getElementById('final_booking_date').value = bookingData.bookingDate;
            document.getElementById('final_booking_time').value = bookingData.bookingTime;
            document.getElementById('final_service_address').value = bookingData.serviceAddress;
            document.getElementById('final_payment_method').value = bookingData.paymentMethod;
            document.getElementById('final_total_amount').value = bookingData.selectedPrice;
            
            // Vehicle info
            Object.keys(bookingData.vehicleInfo).forEach(key => {
                const hiddenField = document.getElementById(`final_${key}`);
                if (hiddenField) {
                    hiddenField.value = bookingData.vehicleInfo[key] || '';
                }
            });
            
            // Submit form
            document.getElementById('bookingForm').submit();
        });

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('booking_date').min = today;

        // Google Maps functionality
        let map;
        let marker;
        let autocomplete;

        window.initMap = function() {
            try {
                // Default location from PHP configuration
                const defaultLocation = { lat: DEFAULT_MAP_LAT, lng: DEFAULT_MAP_LNG };
                
                map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 13,
                    center: defaultLocation,
                    styles: [
                        {
                            "featureType": "all",
                            "elementType": "geometry",
                            "stylers": [{"color": "#242f3e"}]
                        },
                        {
                            "featureType": "all",
                            "elementType": "labels.text.stroke",
                            "stylers": [{"lightness": -80}]
                        },
                        {
                            "featureType": "administrative",
                            "elementType": "labels.text.fill",
                            "stylers": [{"color": "#746855"}]
                        },
                        {
                            "featureType": "road",
                            "elementType": "geometry",
                            "stylers": [{"color": "#38414e"}]
                        },
                        {
                            "featureType": "road",
                            "elementType": "labels.text.fill",
                            "stylers": [{"color": "#9ca5b3"}]
                        },
                        {
                            "featureType": "water",
                            "elementType": "geometry",
                            "stylers": [{"color": "#17263c"}]
                        }
                    ]
                });

                marker = new google.maps.Marker({
                    position: defaultLocation,
                    map: map,
                    draggable: true,
                    title: 'Drag me to your exact location!',
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#FFD700">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(40, 40),
                        anchor: new google.maps.Point(20, 40)
                    }
                });

                // Address autocomplete with country restriction from config
                const addressInput = document.getElementById('service_address');
                const autocompleteOptions = {
                    fields: ['formatted_address', 'geometry', 'name'],
                    types: ['address']
                };
                
                // Add country restriction if configured
                if (MAP_COUNTRY_RESTRICTION && MAP_COUNTRY_RESTRICTION !== '') {
                    autocompleteOptions.componentRestrictions = { country: MAP_COUNTRY_RESTRICTION };
                }
                
                autocomplete = new google.maps.places.Autocomplete(addressInput, autocompleteOptions);
                autocomplete.bindTo('bounds', map);

                // When address is selected from autocomplete
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    if (place.geometry) {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                        marker.setPosition(place.geometry.location);
                        
                        // Update the address field with formatted address
                        bookingData.serviceAddress = place.formatted_address || addressInput.value;
                        addressInput.value = bookingData.serviceAddress;
                        updateNextButtonState();
                        
                        // Add a nice info window
                        const infoWindow = new google.maps.InfoWindow({
                            content: `<div style="color: #333; font-weight: 600;">📍 Service Location</div><div style="color: #666; font-size: 0.9rem;">${bookingData.serviceAddress}</div>`
                        });
                        infoWindow.open(map, marker);
                        setTimeout(() => infoWindow.close(), 3000);
                    }
                });

                // When marker is dragged
                marker.addListener('dragend', function() {
                    const position = marker.getPosition();
                    const geocoder = new google.maps.Geocoder();
                    
                    geocoder.geocode({ location: position }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            const address = results[0].formatted_address;
                            addressInput.value = address;
                            bookingData.serviceAddress = address;
                            updateNextButtonState();
                            
                            // Show confirmation
                            const infoWindow = new google.maps.InfoWindow({
                                content: `<div style="color: #333; font-weight: 600;">✅ Location Updated</div><div style="color: #666; font-size: 0.9rem;">${address}</div>`
                            });
                            infoWindow.open(map, marker);
                            setTimeout(() => infoWindow.close(), 3000);
                        }
                    });
                });

                // Click on map to place marker
                map.addListener('click', function(event) {
                    marker.setPosition(event.latLng);
                    map.panTo(event.latLng);
                    
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: event.latLng }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            const address = results[0].formatted_address;
                            addressInput.value = address;
                            bookingData.serviceAddress = address;
                            updateNextButtonState();
                            
                            // Show confirmation
                            const infoWindow = new google.maps.InfoWindow({
                                content: `<div style="color: #333; font-weight: 600;">📍 New Location</div><div style="color: #666; font-size: 0.9rem;">${address}</div>`
                            });
                            infoWindow.open(map, marker);
                            setTimeout(() => infoWindow.close(), 2000);
                        }
                    });
                });

                console.log('✅ Google Maps loaded successfully!');

            } catch (error) {
                console.error('Error initializing Google Maps:', error);
                showMapFallback();
            }
        };

        // Enhanced address input handling for manual entry
        document.getElementById('service_address').addEventListener('input', function() {
            bookingData.serviceAddress = this.value;
            updateNextButtonState();
            
            // If Google Maps is available and user types an address, try to geocode it
            if (typeof google !== 'undefined' && this.value.length > 10) {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: this.value }, function(results, status) {
                    if (status === 'OK' && results[0] && map && marker) {
                        const location = results[0].geometry.location;
                        map.setCenter(location);
                        marker.setPosition(location);
                    }
                });
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            updateProgress();
            
            // Load Google Maps API
            loadGoogleMapsAPI();
            
            // Check if service is pre-selected from URL
            const urlParams = new URLSearchParams(window.location.search);
            const serviceId = urlParams.get('service_id');
            
            if (serviceId) {
                // Service is pre-selected, find the service card and auto-select it
                const serviceCard = document.querySelector(`[data-service-id="${serviceId}"]`);
                if (serviceCard) {
                    // Auto-select the service data
                    bookingData.serviceId = serviceCard.dataset.serviceId;
                    bookingData.serviceName = serviceCard.dataset.serviceName;
                    bookingData.priceData = {
                        small: parseFloat(serviceCard.dataset.small),
                        medium: parseFloat(serviceCard.dataset.medium),
                        large: parseFloat(serviceCard.dataset.large)
                    };
                    
                    // Update the service selection summary and prices
                    const summaryInfo = document.getElementById('selectedServiceInfo');
                    summaryInfo.innerHTML = `
                        <div style="font-size: 1.3rem; margin-bottom: 10px;">${bookingData.serviceName}</div>
                        <div style="display: flex; justify-content: center; gap: 20px; font-size: 0.9rem;">
                            <span>Small: ₱${bookingData.priceData.small.toLocaleString()}</span>
                            <span>Medium: ₱${bookingData.priceData.medium.toLocaleString()}</span>
                            <span>Large: ₱${bookingData.priceData.large.toLocaleString()}</span>
                        </div>
                    `;
                    
                    // Update prices in vehicle size cards for summary phase
                    document.getElementById('smallPriceSummary').textContent = `₱${bookingData.priceData.small.toLocaleString()}`;
                    document.getElementById('mediumPriceSummary').textContent = `₱${bookingData.priceData.medium.toLocaleString()}`;
                    document.getElementById('largePriceSummary').textContent = `₱${bookingData.priceData.large.toLocaleString()}`;
                    
                    // Add event listeners for vehicle size selection in summary phase
                    document.querySelectorAll('#serviceSelectionSummary .vehicle-size-card').forEach(card => {
                        card.addEventListener('click', function() {
                            document.querySelectorAll('#serviceSelectionSummary .vehicle-size-card').forEach(c => c.classList.remove('selected'));
                            this.classList.add('selected');
                            
                            bookingData.vehicleSize = this.dataset.size;
                            bookingData.selectedPrice = bookingData.priceData[this.dataset.size];
                            
                            document.getElementById('continueToDateTime').disabled = false;
                        });
                    });
                    
                    // Add event listener for continue button
                    document.getElementById('continueToDateTime').addEventListener('click', () => showPhase(2));
                    
                    // Hide Phase 1 and show service summary
                    document.getElementById('phase1').classList.remove('active');
                    document.getElementById('serviceSelectionSummary').classList.add('active');
                    currentPhase = 1.5; // Special phase for pre-selected service
                }
            }
        });
    </script>
</body>
</html>