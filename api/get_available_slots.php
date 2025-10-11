<?php
/**
 * API endpoint to get available time slots for a specific date
 * Usage: /api/get_available_slots.php?date=2025-10-15
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
    
    // Get date from either GET or POST request
    $date = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $date = $input['date'] ?? null;
    } else {
        $date = $_GET['date'] ?? null;
    }
    
    // Validate date parameter
    if (!$date || empty($date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Date parameter is required'
        ]);
        exit;
    }
    
    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format. Use Y-m-d format (e.g., 2025-10-15)'
        ]);
        exit;
    }
    
    // Check if date is in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        echo json_encode([
            'success' => true,
            'available_slots' => [],
            'message' => 'Cannot book for past dates'
        ]);
        exit;
    }
    
    // Initialize availability checker
    $availability_checker = new BookingAvailabilityChecker($pdo);
    
    // Get available time slots (returns array of objects)
    $time_slot_objects = $availability_checker->getAvailableTimeSlots($date);
    
    // Extract just the start times for the frontend
    $available_slots = [];
    foreach ($time_slot_objects as $slot) {
        if (isset($slot['start_time'])) {
            // Format time to be more user-friendly (e.g., "09:00 AM")
            $time_24 = $slot['start_time'];
            $time_12 = date('g:i A', strtotime($time_24));
            $available_slots[] = $time_12;
        }
    }
    
    // Get date summary
    $date_summary = $availability_checker->getDateBookingSummary($date);
    
    echo json_encode([
        'success' => true,
        'date' => $date,
        'available_slots' => $available_slots,
        'date_summary' => $date_summary,
        'message' => count($available_slots) > 0 ? 'Available slots found' : 'No available slots for this date'
    ]);
    
} catch (Exception $e) {
    error_log("API Error (get_available_slots): " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
?>