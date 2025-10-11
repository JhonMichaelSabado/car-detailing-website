<?php
/**
 * API endpoint to get unavailable dates for calendar display
 * Usage: /api/get_unavailable_dates.php?start_date=2025-10-01&end_date=2025-12-31
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress HTML error display for clean JSON response
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config/database.php';
require_once '../includes/BookingAvailabilityChecker.php';

try {
    // Initialize database connection
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception("Database connection failed");
    }
    
    // Set default date range if not provided
    $start_date = $_GET['start_date'] ?? date('Y-m-d');
    $end_date = $_GET['end_date'] ?? date('Y-m-d', strtotime('+3 months'));
    
    // Validate date formats
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use Y-m-d format'
        ]);
        exit;
    }
    
    // Initialize availability checker
    $availability_checker = new BookingAvailabilityChecker($pdo);
    
    // Get unavailable dates
    $unavailable_dates = $availability_checker->getUnavailableDates($start_date, $end_date);
    
    // Get detailed availability info for the date range
    $stmt = $pdo->prepare("
        SELECT 
            booking_date,
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings
        FROM bookings 
        WHERE booking_date BETWEEN ? AND ?
        GROUP BY booking_date
        ORDER BY booking_date
    ");
    $stmt->execute([$start_date, $end_date]);
    $date_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response
    $detailed_availability = [];
    foreach ($date_details as $detail) {
        $detailed_availability[$detail['booking_date']] = [
            'total_bookings' => (int)$detail['total_bookings'],
            'accepted_bookings' => (int)$detail['accepted_bookings'],
            'pending_bookings' => (int)$detail['pending_bookings'],
            'is_fully_booked' => (int)$detail['accepted_bookings'] >= 2,
            'available_slots' => 2 - (int)$detail['accepted_bookings']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'unavailable_dates' => $unavailable_dates,
        'detailed_availability' => $detailed_availability,
        'message' => 'Availability data retrieved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_unavailable_dates): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>