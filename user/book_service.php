<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['service_type', 'service_package', 'vehicle_year', 'vehicle_brand', 'vehicle_model', 'vehicle_color', 'service_date', 'service_time', 'location', 'total_amount'];

foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user information
    $stmt = $db->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Insert booking into database
    $sql = "INSERT INTO bookings (
        user_id, 
        customer_email, 
        customer_name,
        service_type, 
        service_package,
        vehicle_year,
        vehicle_brand,
        vehicle_model,
        vehicle_color,
        service_date,
        service_time,
        location,
        total_amount,
        status,
        date_created
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

    $stmt = $db->prepare($sql);
    
    $customer_name = $user['first_name'] . ' ' . $user['last_name'];
    
    $stmt->bindParam(1, $_SESSION['user_id']);
    $stmt->bindParam(2, $user['email']);
    $stmt->bindParam(3, $customer_name);
    $stmt->bindParam(4, $input['service_type']);
    $stmt->bindParam(5, $input['service_package']);
    $stmt->bindParam(6, $input['vehicle_year']);
    $stmt->bindParam(7, $input['vehicle_brand']);
    $stmt->bindParam(8, $input['vehicle_model']);
    $stmt->bindParam(9, $input['vehicle_color']);
    $stmt->bindParam(10, $input['service_date']);
    $stmt->bindParam(11, $input['service_time']);
    $stmt->bindParam(12, $input['location']);
    $stmt->bindParam(13, $input['total_amount']);

    if ($stmt->execute()) {
        $booking_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking created successfully!',
            'booking_id' => $booking_id,
            'data' => [
                'service_type' => $input['service_type'],
                'service_package' => $input['service_package'],
                'vehicle' => $input['vehicle_year'] . ' ' . $input['vehicle_brand'] . ' ' . $input['vehicle_model'],
                'service_date' => $input['service_date'],
                'service_time' => $input['service_time'],
                'location' => $input['location'],
                'total_amount' => $input['total_amount'],
                'status' => 'pending'
            ]
        ]);
    } else {
        throw new Exception("Failed to create booking");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>