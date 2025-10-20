<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add debug for any POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== ANY POST REQUEST DEBUG ===");
    error_log("POST keys: " . implode(', ', array_keys($_POST)));
    error_log("Full POST data: " . print_r($_POST, true));
    error_log("==============================");
}

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';
require_once __DIR__ . '/../includes/BookingAvailabilityChecker.php';
require_once __DIR__ . '/../includes/BookingManager.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize advanced booking system
$availability_checker = new BookingAvailabilityChecker($db);
$booking_manager = new BookingManager($db);

// Handle advanced booking form submission
$booking_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_advanced_booking') {
    // Debug: Check what we actually received
    $debug_info = "POST Debug Info:\n";
    $debug_info .= "Service Address POST: '" . ($_POST['service_address'] ?? 'NOT_SET') . "'\n";
    $debug_info .= "Contact Number POST: '" . ($_POST['contact_number'] ?? 'NOT_SET') . "'\n";
    $debug_info .= "Payment Option POST: '" . ($_POST['payment_option'] ?? 'NOT_SET') . "'\n";
    $debug_info .= "All POST keys: " . implode(', ', array_keys($_POST)) . "\n";
    error_log($debug_info);
    
    try {
        // Get user address from database
        $stmt = $db->prepare("SELECT address FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_address = $stmt->fetchColumn();
        
        if (empty($user_address)) {
            $user_address = 'To be confirmed'; // Allow booking, address will be confirmed by admin
        }
        
        // Parse booking date and time
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        
        // Create booking directly using the actual database schema
        try {
            // Get form data with validation - only access if form was submitted
            $service_address = isset($_POST['service_address']) ? trim($_POST['service_address']) : '';
            $contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
            $payment_option = isset($_POST['payment_option']) ? $_POST['payment_option'] : 'partial';
            
            // Debug the actual values
            error_log("Validation Debug - Service Address: '" . $service_address . "' (length: " . strlen($service_address) . ")");
            error_log("Validation Debug - Contact Number: '" . $contact_number . "' (length: " . strlen($contact_number) . ")");
            error_log("Full POST data: " . print_r($_POST, true));
            
            // Validate required fields
            if (empty($service_address)) {
                throw new Exception("Service address is required. Received: '" . $service_address . "'");
            }
            if (empty($contact_number)) {
                throw new Exception("Contact number is required. Received: '" . $contact_number . "'");
            }
            
            // Get service details for total amount calculation
            $stmt = $db->prepare("SELECT * FROM services WHERE service_id = ?");
            $stmt->execute([$_POST['service_id']]);
            $service = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$service) {
                throw new Exception("Service not found");
            }
            
            // Calculate total amount based on vehicle size
            $vehicle_size = $_POST['vehicle_size'];
            $price_column = 'price_' . $vehicle_size;
            $total_amount = $service[$price_column];
            
            // Create booking with actual table structure
            $stmt = $db->prepare("
                INSERT INTO bookings (
                    user_id, service_id, vehicle_size, booking_date, booking_time,
                    total_amount, vehicle_details, special_requests, status,
                    payment_status, estimated_duration, customer_notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', 120, ?, NOW())
            ");
            
            // Combine date and time for booking_date field (if it's datetime)
            $booking_datetime = $booking_date . ' ' . $booking_time;
            
            // Prepare comprehensive customer notes for admin review
            $customer_notes = "SERVICE ADDRESS: " . $service_address . "\n";
            $customer_notes .= "CONTACT NUMBER: " . $contact_number . "\n";
            $customer_notes .= "PAYMENT OPTION: " . $payment_option . "\n";
            if (!empty($_POST['special_requests'])) {
                $customer_notes .= "SPECIAL REQUESTS: " . $_POST['special_requests'];
            }
            
            $result = $stmt->execute([
                $user_id,
                $_POST['service_id'],
                $vehicle_size,
                $booking_datetime,
                $booking_time,
                $total_amount,
                $_POST['vehicle_details'],
                $_POST['special_requests'],
                $customer_notes
            ]);
            
            if ($result) {
                $booking_id = $db->lastInsertId();
                
                // Get payment info for success message (already validated above)
                $payment_amount = $payment_option === 'full' ? $total_amount : ($total_amount * 0.5);
                $payment_text = $payment_option === 'full' ? 'Full Payment' : '50% Down Payment';
                
                $booking_result = [
                    'type' => 'success',
                    'message' => "Booking created successfully! Booking ID: #{$booking_id}<br>" .
                               "Payment Required: {$payment_text} - ₱" . number_format($payment_amount, 2) . "<br>" .
                               "Status: Awaiting admin approval<br>" .
                               "You will be contacted at {$contact_number} for confirmation.",
                    'booking_id' => $booking_id
                ];
            } else {
                throw new Exception("Failed to insert booking");
            }
            
        } catch (Exception $e) {
            $booking_result = [
                'type' => 'error',
                'message' => 'Booking failed: ' . $e->getMessage()
            ];
        }
    } catch (Exception $e) {
        $booking_result = [
            'type' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Get user information
$user_name = $_SESSION['username'] ?? 'User';

// Get real data from database
$user_stats = $carDB->getUserStats($user_id);
$user_bookings = $carDB->getUserBookings($user_id, 5);
$services = $carDB->getServices();
$user_payments = $carDB->getUserPayments($user_id);
$notifications = $carDB->getUserNotifications($user_id, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Apple SF Pro Font Import */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap');

        :root {
            /* Apple-style Color Palette */
            --accent-color: #FFD700;
            --accent-hover: #FFC107;
            --bg-primary: #000000;
            --bg-secondary: #0a0a0a;
            --bg-card: #1d1d1f;
            --bg-modal: rgba(0, 0, 0, 0.85);
            --text-primary: #f5f5f7;
            --text-secondary: #a1a1a6;
            --text-tertiary: #86868b;
            --border-subtle: #2d2d2f;
            --shadow-ambient: rgba(0, 0, 0, 0.7);
            --shadow-glow: rgba(255, 215, 0, 0.15);
            
            /* Apple Typography */
            --font-size: 17px;
            --font-size-large: 28px;
            --font-size-xlarge: 48px;
            --font-weight-light: 300;
            --font-weight-regular: 400;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
            --letter-spacing-tight: -0.022em;
            --letter-spacing-normal: -0.003em;
            --line-height-tight: 1.1;
            --line-height-normal: 1.47059;
            
            /* Apple Spacing */
            --border-radius: 12px;
            --border-radius-large: 20px;
            --spacing-xs: 8px;
            --spacing-sm: 16px;
            --spacing-md: 24px;
            --spacing-lg: 32px;
            --spacing-xl: 48px;
            --spacing-xxl: 64px;
            
            /* Apple Transitions */
            --transition-duration: 0.25s;
            --transition-ease: cubic-bezier(0.4, 0.0, 0.2, 1);
            --transition-bounce: cubic-bezier(0.25, 0.46, 0.45, 0.94);

            /* Apple SF Pro Font Stack */
            --sf-pro-text: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'SF Pro Display', system-ui, sans-serif;
            --sf-pro-display: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', system-ui, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all var(--transition-duration) ease;
        }

        body {
            font-family: var(--sf-pro-text);
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: var(--line-height-normal);
            font-size: var(--font-size);
            font-weight: var(--font-weight-regular);
            letter-spacing: var(--letter-spacing-normal);
            font-synthesis: none;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Compact mode styles */
        body.compact-mode {
            --font-size: 12px;
        }

        body.compact-mode .card {
            padding: 15px;
        }

        body.compact-mode .dashboard-header h1 {
            font-size: 1.5em;
        }

        /* High contrast mode */
        body.high-contrast {
            --bg-primary: #000000;
            --bg-secondary: #ffffff;
            --text-primary: #ffffff;
            --text-secondary: #000000;
        }

        body.high-contrast .card {
            border: 2px solid var(--text-primary);
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Dynamic Auto-Hide */
        .sidebar {
            width: 260px;
            background: rgba(26, 26, 26, 0.95);
            border-right: 1px solid rgba(51, 51, 51, 0.8);
            position: fixed;
            left: -200px; /* Hide by default, showing only 60px */
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            backdrop-filter: blur(20px);
            z-index: 1000;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            right: -20px;
            top: 0;
            width: 20px;
            height: 100%;
            background: transparent;
            z-index: -1;
        }

        .sidebar:hover,
        .sidebar.show {
            left: 0;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.3);
        }

        /* Hover trigger area */
        .sidebar-trigger {
            position: fixed;
            left: 0;
            top: 0;
            width: 20px;
            height: 100vh;
            z-index: 999;
            background: transparent;
        }

        /* Collapsed sidebar indicator */
        .sidebar-collapsed-indicator {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            opacity: 0.6;
            transition: opacity 0.3s ease;
        }

        .sidebar:hover .sidebar-collapsed-indicator {
            opacity: 0;
        }

        .collapsed-dot {
            width: 6px;
            height: 6px;
            background: #FFD700;
            border-radius: 50%;
            opacity: 0.7;
            animation: breathe 2s ease-in-out infinite;
        }

        .collapsed-dot:nth-child(2) {
            animation-delay: 0.3s;
        }

        .collapsed-dot:nth-child(3) {
            animation-delay: 0.6s;
        }

        @keyframes breathe {
            0%, 100% { opacity: 0.4; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        .sidebar-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-subtle);
            text-align: center;
        }

        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #FFD700;
            text-decoration: none;
        }

        .nav-menu {
            padding: 20px 0;
        }

        /* Sidebar links w/ gold accent + micro animation */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #cfcfcf;
            text-decoration: none;
            cursor: pointer;
            position: relative;
            border-radius: 10px;
            transition: color 0.2s ease, background 0.2s ease, transform 0.2s ease;
        }
        .nav-link i {
            width: 18px;
            text-align: center;
            color: #ffd76a;
            transition: transform 0.2s ease;
        }
        .nav-link::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 0;
            background: linear-gradient(180deg,#FFD700,#FFA500);
            border-radius: 2px;
            transition: height 0.2s ease;
        }
        .nav-link:hover {
            background: #262626;
            color: #fff;
            transform: translateX(2px);
        }
        .nav-link:hover i { transform: scale(1.05); }
        .nav-link.active {
            background: #2b2b2b;
            color: #fff;
            box-shadow: inset 0 0 0 1px #3a3a3a;
        }
        .nav-link.active::before { height: 60%; }

        /* Main Content - Dynamic for Collapsible Sidebar */
        .main-content {
            flex: 1;
            margin-left: 60px; /* Start with collapsed sidebar space */
            padding: 0;
            transition: margin-left var(--transition-duration) var(--transition-bounce);
            background: var(--bg-primary);
        }

        .sidebar:hover ~ .main-content,
        .sidebar.show ~ .main-content {
            margin-left: 260px;
        }

        /* Top Header Bar */
        .top-header {
            background: #1a1a1a;
            border-bottom: 1px solid #333;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-breadcrumb-header {
            color: #9e9e9e;
            font-size: 13px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* Notification Bell */
        .notification-btn {
            position: relative;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #e6e6e6;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .notification-btn:hover {
            background: #2f2f2f;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        /* User Dropdown */
        .user-dropdown {
            position: relative;
        }

        .user-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #e6e6e6;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-trigger:hover {
            background: #2f2f2f;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 700;
            font-size: 12px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
        }

        .user-role {
            font-size: 10px;
            color: #9e9e9e;
            line-height: 1;
        }

        /* Content Area */
        .content-area {
            padding: 20px 25px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: var(--font-size-xlarge);
            font-weight: var(--font-weight-bold);
            letter-spacing: var(--letter-spacing-tight);
            line-height: var(--line-height-tight);
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }

        .page-subtitle {
            color: #ccc;
            font-size: 14px;
        }

        /* Stats Grid - Simplified */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #ccc;
            font-size: 12px;
        }

        .stat-icon {
            font-size: 24px;
            color: #FFD700;
            opacity: 0.5;
        }

        /* Recent Services - Simplified */
        .recent-services {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-info h4 {
            font-size: 14px;
            margin-bottom: 3px;
        }

        .service-info p {
            font-size: 12px;
            color: #ccc;
        }

        .service-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-completed { background: #22c55e20; color: #22c55e; }
        .status-active { background: #f9731620; color: #f97316; }
        .status-pending { background: #eab30820; color: #eab308; }
        .status-confirmed { background: #3b82f620; color: #3b82f6; }
        .status-in_progress { background: #8b5cf620; color: #8b5cf6; }
        .status-cancelled { background: #ef444420; color: #ef4444; }
        .status-declined { background: #ef444420; color: #ef4444; }

        /* Placeholder - Simplified */
        .placeholder-content {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .placeholder-content i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #FFD700;
            opacity: 0.3;
        }

        .placeholder-content h3 {
            margin-bottom: 10px;
        }

        /* Table Styles - Minimal */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            background: #2c2c2c;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .stats-table th,
        .stats-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }

        .stats-table th {
            background: #333;
            font-weight: 600;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mobile responsive */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #FFD700;
            color: #1a1a1a;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .sidebar {
                left: -100% !important; /* Override the dynamic behavior on mobile */
                transform: translateX(0);
                transition: left 0.3s ease;
                backdrop-filter: blur(20px);
            }
            
            .sidebar.mobile-open {
                left: 0 !important;
            }
            
            .sidebar:hover {
                left: -100% !important; /* Disable hover on mobile */
            }
            
            .sidebar-trigger {
                display: none; /* Hide trigger area on mobile */
            }
            
            .sidebar-collapsed-indicator {
                display: none; /* Hide indicator on mobile */
            }
            
            .main-content {
                margin-left: 0 !important; /* Always full width on mobile */
            }
        }

        /* Service categories and cards */
        .service-category {
            margin-bottom: 40px;
        }

        .category-title {
            font-size: 22px;
            color: #FFD700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 60px;
            margin: 80px 0 120px 0;
            padding: 0 40px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .service-card {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 215, 0, 0.05));
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius-large);
            padding: var(--spacing-xl);
            cursor: pointer;
            transition: all var(--transition-duration) var(--transition-ease);
            text-align: center;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .service-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-color);
            box-shadow: 0 20px 40px var(--shadow-glow);
        }

        .service-card:hover .service-name {
            color: #FFD700;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1);
        }

        .service-icon {
            font-size: 4.5rem;
            margin-bottom: 40px;
            opacity: 0.8;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            display: block;
        }

        .service-image {
            width: 100%;
            max-width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            opacity: 0.9;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            margin: 0 auto;
            display: block;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .service-card:hover .service-image {
            opacity: 1;
            transform: scale(1.05);
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.2);
        }

        .service-name {
            font-size: 1.4rem;
            margin: 0 0 20px 0;
            color: #ffffff;
            font-weight: 400;
            line-height: 1.2;
            letter-spacing: -0.01em;
            transition: color 0.3s ease;
        }

        .service-price-range {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            font-weight: 400;
            margin-bottom: 40px;
            letter-spacing: 0.01em;
        }

        .service-card .btn-primary {
            background: transparent;
            color: #FFD700;
            border: none;
            padding: 0;
            font-weight: 400;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            letter-spacing: 0.01em;
        }

        .service-card .btn-primary:hover {
            color: #ffffff;
        }

        .category-title {
            color: #ffffff;
            font-size: 3.5rem;
            margin: 120px 0 20px 0;
            text-align: center;
            font-weight: 200;
            letter-spacing: -0.04em;
            line-height: 1.1;
        }

        .category-subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 80px;
            font-size: 1.3rem;
            font-weight: 300;
            letter-spacing: 0.01em;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.4;
        }

        .service-category {
            margin-bottom: 160px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 120px;
            padding-top: 80px;
        }

        .page-title {
            font-size: 5rem;
            font-weight: 100;
            color: #ffffff;
            margin-bottom: 30px;
            letter-spacing: -0.05em;
            line-height: 1;
        }

        .page-subtitle {
            font-size: 1.5rem;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 300;
            letter-spacing: 0.02em;
            max-width: 500px;
            margin: 0 auto;
        }

        /* ===== FEATURED SERVICES - APPLE STYLE ===== */
        
        #featured-services {
            margin-bottom: 160px;
        }

        .featured-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 80px 60px 120px;
            max-width: 1400px;
            margin: 0 auto;
            gap: 80px;
        }

        .featured-intro {
            flex: 1;
            max-width: 480px;
        }

        .featured-title {
            font-size: 4rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 30px;
            letter-spacing: -0.003em;
            line-height: 1.05;
        }

        .featured-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 40px;
            line-height: 1.47059;
            font-weight: 400;
            letter-spacing: -0.003em;
        }

        .featured-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .featured-link {
            color: #007AFF;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 400;
            letter-spacing: -0.003em;
            transition: color 0.2s ease;
        }

        .featured-link:hover {
            color: #0051D5;
        }

        .featured-link span {
            margin-left: 4px;
        }

        .featured-gallery {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            height: 400px;
            align-items: center;
        }

        .gallery-item {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .placeholder-circle {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .placeholder-rect {
            width: 100%;
            height: 160px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
        }

        .placeholder-square {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
        }

        /* Featured Carousel */
        .featured-carousel-section {
            padding: 0 60px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .carousel-title {
            font-size: 2.5rem;
            font-weight: 600;
            color: #ffffff;
            text-align: center;
            margin-bottom: 60px;
            letter-spacing: -0.003em;
        }

        .carousel-container {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
        }

        .carousel-track {
            display: flex;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            gap: 40px;
            padding: 40px;
        }

        .carousel-item {
            flex: 0 0 480px;
            opacity: 0.7;
            transform: scale(0.9);
            transition: all 0.6s ease;
        }

        .carousel-item.active {
            opacity: 1;
            transform: scale(1);
        }

        .featured-service-card {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 215, 0, 0.08));
            border-radius: var(--border-radius-large);
            padding: var(--spacing-xl);
            text-align: center;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            transition: all var(--transition-duration) var(--transition-ease);
            min-height: 500px;
        }

        .featured-service-card:hover {
            background: linear-gradient(135deg, var(--bg-card), rgba(255, 215, 0, 0.12));
            transform: translateY(-12px);
            border-color: var(--accent-color);
            box-shadow: 0 25px 50px var(--shadow-glow);
        }

        .featured-service-card:hover .service-featured-image {
            transform: scale(1.05);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .card-image {
            position: relative;
            margin-bottom: 30px;
        }

        .service-featured-image {
            width: 320px;
            height: 320px;
            object-fit: cover;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            transition: all 0.3s ease;
        }

        .service-featured-placeholder {
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .featured-emoji {
            font-size: 4rem;
        }

        .color-options {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 20px;
        }

        .color-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .color-dot.active {
            border-color: #007AFF;
            box-shadow: 0 0 0 2px rgba(0, 122, 255, 0.2);
        }

        .color-dot[data-color="default"] { background: #ffffff; }
        .color-dot[data-color="premium"] { background: #FFD700; }
        .color-dot[data-color="luxury"] { background: #FF6B35; }
        .color-dot[data-color="elite"] { background: #8B5CF6; }

        .card-content {
            position: relative;
        }

        .service-badge-new {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #FF3B30;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .service-featured-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.003em;
            line-height: 1.3;
        }

        .service-featured-price {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: #ffffff;
            font-size: 1.5rem;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 20px;
        }

        .carousel-nav.next {
            right: 20px;
        }

        .carousel-dots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 40px 0;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #007AFF;
            transform: scale(1.2);
        }

        .shop-all-link {
            text-align: center;
            margin-top: 40px;
        }

        .shop-all-link a {
            color: #007AFF;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 400;
            letter-spacing: -0.003em;
            transition: color 0.2s ease;
        }

        .shop-all-link a:hover {
            color: #0051D5;
        }

        .shop-all-link span {
            margin-left: 4px;
        }

        /* Remove all other visual noise */
        .content-section {
            padding: 0;
            margin: 0;
        }

        /* Ultra clean sidebar */
        .sidebar {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 215, 0, 0.05);
        }

        .nav-link {
            font-weight: 300;
            font-size: 1.1rem;
            letter-spacing: 0.01em;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .nav-link.active {
            background: rgba(255, 215, 0, 0.08);
            border-right: 2px solid #FFD700;
        }

        .btn-secondary {
            background: transparent;
            color: #FFD700;
            border: 1px solid #FFD700;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        /* Apple-style Mobile Menu Button */
        .mobile-menu-btn {
            position: fixed;
            top: var(--spacing-sm);
            left: var(--spacing-sm);
            z-index: 1001;
            background: var(--bg-card);
            color: var(--accent-color);
            border: 1px solid var(--border-subtle);
            border-radius: var(--border-radius);
            padding: var(--spacing-sm);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            cursor: pointer;
            transition: all var(--transition-duration) var(--transition-ease);
        }

        .mobile-menu-btn:hover {
            background: var(--accent-color);
            color: var(--bg-primary);
            transform: translateY(-1px);
            box-shadow: 0 4px 20px var(--shadow-glow);
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard">
        <!-- Hover Trigger Area -->
        <div class="sidebar-trigger" id="sidebarTrigger"></div>
        
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <!-- Collapsed State Indicator -->
            <div class="sidebar-collapsed-indicator">
                <div class="collapsed-dot"></div>
                <div class="collapsed-dot"></div>
                <div class="collapsed-dot"></div>
            </div>
            
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <i class="fas fa-car"></i> Ride Revive
                </a>
            </div>
            <div class="nav-menu">
                <a href="#" class="nav-link active" onclick="showSection('dashboard', this)">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="#" class="nav-link" onclick="showSection('bookings', this)">
                    <i class="fas fa-calendar-alt"></i> My Bookings
                </a>
                <a href="#" class="nav-link" onclick="showSection('services', this)">
                    <i class="fas fa-car-wash"></i> Book a Service
                </a>
                <a href="#" class="nav-link" onclick="showSection('payments', this)">
                    <i class="fas fa-credit-card"></i> Payments / Transactions
                </a>
                <a href="#" class="nav-link" onclick="showSection('reviews', this)" data-section="reviews">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="#" class="nav-link" onclick="showSection('notifications', this)">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <a href="booking_guide.php" class="nav-link" target="_blank">
                    <i class="fas fa-map-marked-alt"></i> Booking Guide
                </a>
            </div>
            
            <!-- Bottom Nav Section -->
            <div class="nav-menu" style="position: absolute; bottom: 20px; width: calc(100% - 40px); margin: 0 20px;">
                <a href="#" class="nav-link" onclick="showSection('settings', this)">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="#" class="nav-link" onclick="showSection('help', this)">
                    <i class="fas fa-question-circle"></i> Help / Support
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="header-left">
                    <span class="page-breadcrumb-header">User Dashboard</span>
                </div>
                <div class="header-right">
                    <!-- Notification Bell -->
                    <div class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-trigger">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                                <span class="user-role">Customer</span>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Dashboard Section -->
                <section id="dashboard" class="content-section active">
                    <div class="page-header">
                        <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p class="page-subtitle">Here's your service overview and recent activity.</p>
                        
                        <!-- Booking Guide CTA -->
                        <div style="margin: 20px 0; text-align: center;">
                            <a href="booking_guide.php" target="_blank" style="
                                display: inline-flex;
                                align-items: center;
                                gap: 10px;
                                background: linear-gradient(135deg, #FFD700, #e6c200);
                                color: #000;
                                padding: 15px 30px;
                                border-radius: 12px;
                                text-decoration: none;
                                font-weight: bold;
                                font-size: 16px;
                                transition: all 0.3s ease;
                                box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
                            " onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(255, 215, 0, 0.4)'" 
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 215, 0, 0.3)'">
                                <i class="fas fa-map-marked-alt"></i>
                                📖 Complete Booking Guide - Learn How It Works!
                            </a>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3><?php echo $user_stats['total_bookings']; ?></h3>
                                    <p>Total Bookings</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3><?php echo $user_stats['pending_bookings']; ?></h3>
                                    <p>Pending Services</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3><?php echo $user_stats['completed_bookings']; ?></h3>
                                    <p>Completed</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-info">
                                    <h3>₱<?php echo number_format($user_stats['total_spent'], 2); ?></h3>
                                    <p>Total Spent</p>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-peso-sign"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Services -->
                    <div class="recent-services">
                        <h2 class="section-title">Recent Activity</h2>
                        <?php if (empty($user_bookings)): ?>
                            <div class="placeholder-content">
                                <i class="fas fa-calendar-alt"></i>
                                <h3>No bookings yet</h3>
                                <p>Start by booking your first service!</p>
                                <button onclick="showSection('services', this)" style="
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    border: none;
                                    padding: 12px 24px;
                                    border-radius: 8px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    margin-top: 10px;
                                    transition: all 0.3s ease;
                                " onmouseover="this.style.transform='translateY(-2px)'" 
                                   onmouseout="this.style.transform='translateY(0)'">
                                    <i class="fas fa-plus"></i> Book Your First Service
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($user_bookings as $booking): ?>
                                <div class="service-item">
                                    <div class="service-info">
                                        <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                        <p>Booking #<?php echo $booking['booking_id']; ?> - <?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?></p>
                                    </div>
                                    <span class="service-status status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Bookings Section -->
                <section id="bookings" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">My Bookings</h1>
                        <p class="page-subtitle">Manage your car detailing appointments</p>
                    </div>

                    <div class="recent-services">
                        <h2 class="section-title">Upcoming Bookings</h2>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Full Car Detailing</h4>
                                <p>Booking #12346 - October 12, 2025 at 10:00 AM - ₱800</p>
                            </div>
                            <span class="service-status status-active">Confirmed</span>
                        </div>
                    </div>

                    <div class="recent-services">
                        <h2 class="section-title">Booking History</h2>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Interior Cleaning</h4>
                                <p>Booking #12345 - Completed October 5, 2025 - ₱400</p>
                            </div>
                            <span class="service-status status-completed">Completed</span>
                        </div>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Exterior Wash</h4>
                                <p>Booking #12344 - Completed September 28, 2025 - ₱300</p>
                            </div>
                            <span class="service-status status-completed">Completed</span>
                        </div>
                    </div>
                </section>

                <!-- Featured Services Section - Apple Style -->
                <section id="featured-services" class="content-section">
                    <div class="featured-hero">
                        <div class="featured-intro">
                            <h2 class="featured-title">Meet your match.</h2>
                            <p class="featured-subtitle">Pair your car with the perfect service,<br>ceramic protection or premium detailing in<br>fresh new levels of care and excellence.</p>
                            <div class="featured-links">
                                <a href="#services" class="featured-link">Shop All Services <span>›</span></a>
                                <a href="#premium" class="featured-link">Shop Premium Packages <span>›</span></a>
                            </div>
                        </div>
                        <div class="featured-gallery">
                            <div class="gallery-item item-1">
                                <img src="../assets/images/services/basic-exterior-care.jpg" alt="Basic Care" class="gallery-image">
                            </div>
                            <div class="gallery-item item-2">
                                <img src="../assets/images/services/express-care-wax.jpg" alt="Express Wax" class="gallery-image">
                            </div>
                            <div class="gallery-item item-3">
                                <div class="placeholder-circle"></div>
                            </div>
                            <div class="gallery-item item-4">
                                <div class="placeholder-rect"></div>
                            </div>
                            <div class="gallery-item item-5">
                                <div class="placeholder-square"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Services Carousel -->
                    <div class="featured-carousel-section">
                        <h2 class="carousel-title">Featured Car Detailing Services</h2>
                        <div class="carousel-container">
                            <div class="carousel-track" id="featuredTrack">
                                <?php 
                                // Get featured services (Premium and most popular)
                                $featured_services = [];
                                foreach ($services as $service) {
                                    if (in_array($service['service_name'], [
                                        'Platinum Package (Full Interior + Exterior Detail)',
                                        'Ceramic Coating (1-year Protection)',
                                        'Full Exterior Detailing'
                                    ])) {
                                        $featured_services[] = $service;
                                    }
                                }
                                
                                foreach ($featured_services as $index => $service):
                                    // Get service image
                                    $service_name_clean = strtolower(str_replace([' ', '+', '(', ')'], ['-', '-', '-', ''], $service['service_name']));
                                    $service_name_clean = preg_replace('/-+/', '-', $service_name_clean);
                                    $service_name_clean = trim($service_name_clean, '-');
                                    
                                    $service_image = '';
                                    $image_formats = ['jpg', 'png', 'webp'];
                                    foreach ($image_formats as $format) {
                                        if (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.{$format}")) {
                                            $service_image = "../assets/images/services/{$service_name_clean}.{$format}";
                                            break;
                                        }
                                    }
                                    
                                    // Fallback to emoji if no image
                                    $icon_emoji = '✨';
                                    if (strpos($service['service_name'], 'Ceramic') !== false) $icon_emoji = '🛡️';
                                    elseif (strpos($service['service_name'], 'Exterior') !== false) $icon_emoji = '🚗';
                                    elseif (strpos($service['service_name'], 'Platinum') !== false) $icon_emoji = '💎';
                                ?>
                                <div class="carousel-item">
                                    <div class="featured-service-card">
                                        <div class="card-image">
                                            <?php if ($service_image): ?>
                                                <img src="<?php echo htmlspecialchars($service_image); ?>" 
                                                     alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                                                     class="service-featured-image">
                                            <?php else: ?>
                                                <div class="service-featured-placeholder">
                                                    <span class="featured-emoji"><?php echo $icon_emoji; ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="color-options">
                                                <div class="color-dot active" data-color="default"></div>
                                                <div class="color-dot" data-color="premium"></div>
                                                <div class="color-dot" data-color="luxury"></div>
                                                <div class="color-dot" data-color="elite"></div>
                                            </div>
                                        </div>
                                        <div class="card-content">
                                            <?php if ($service['category'] == 'Premium Detailing'): ?>
                                                <span class="service-badge-new">New</span>
                                            <?php endif; ?>
                                            <h3 class="service-featured-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                            <p class="service-featured-price">₱<?php echo number_format($service['price_small']); ?></p>
                                            
                                            <div class="featured-actions" style="margin-top: 15px; display: flex; gap: 8px;">
                                                <button onclick="viewServiceDetails(<?php echo $service['service_id']; ?>)" 
                                                        style="
                                                            flex: 1;
                                                            background: rgba(255, 255, 255, 0.08);
                                                            color: rgba(255, 255, 255, 0.8);
                                                            border: 1px solid rgba(255, 255, 255, 0.15);
                                                            padding: 8px 12px;
                                                            border-radius: 10px;
                                                            cursor: pointer;
                                                            font-size: 12px;
                                                            font-weight: 500;
                                                            transition: all 0.2s ease;
                                                            backdrop-filter: blur(8px);
                                                        " onmouseover="this.style.background='rgba(255, 255, 255, 0.12)'; this.style.color='rgba(255, 255, 255, 1)';"
                                                           onmouseout="this.style.background='rgba(255, 255, 255, 0.08)'; this.style.color='rgba(255, 255, 255, 0.8)';">
                                                    Details
                                                </button>
                                                <button onclick="startBooking(<?php echo $service['service_id']; ?>)" 
                                                        style="
                                                            flex: 1;
                                                            background: #007AFF;
                                                            color: white;
                                                            border: none;
                                                            padding: 8px 12px;
                                                            border-radius: 10px;
                                                            cursor: pointer;
                                                            font-size: 12px;
                                                            font-weight: 600;
                                                            transition: all 0.2s ease;
                                                            box-shadow: 0 1px 4px rgba(0, 122, 255, 0.3);
                                                        " onmouseover="this.style.background='#0051D5'; this.style.transform='translateY(-0.5px)'; this.style.boxShadow='0 2px 6px rgba(0, 122, 255, 0.4)';"
                                                           onmouseout="this.style.background='#007AFF'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 4px rgba(0, 122, 255, 0.3)';">
                                                    Book
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-nav prev" id="carouselPrev">‹</button>
                            <button class="carousel-nav next" id="carouselNext">›</button>
                        </div>
                        <div class="carousel-dots">
                            <span class="dot active" data-slide="0"></span>
                            <span class="dot" data-slide="1"></span>
                            <span class="dot" data-slide="2"></span>
                        </div>
                        <div class="shop-all-link">
                            <a href="#services">Shop all car detailing services <span>›</span></a>
                        </div>
                    </div>
                </section>

                <!-- Services Section -->
                <section id="services" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Our Services</h1>
                        <p class="page-subtitle">Choose from our premium car detailing services</p>
                    </div>

                    <?php 
                    // Group services by category and reorganize order
                    $grouped_services = [];
                    foreach ($services as $service) {
                        $grouped_services[$service['category']][] = $service;
                    }
                    
                    // Define the desired order: Basic Package and Premium Detailing first, then Add-On Services
                    $category_order = ['Basic Package', 'Premium Detailing', 'Add-On Service'];
                    $ordered_services = [];
                    
                    foreach ($category_order as $category) {
                        if (isset($grouped_services[$category])) {
                            $ordered_services[$category] = $grouped_services[$category];
                        }
                    }
                    
                    // Add any remaining categories
                    foreach ($grouped_services as $category => $services_list) {
                        if (!in_array($category, $category_order)) {
                            $ordered_services[$category] = $services_list;
                        }
                    }
                    ?>

                    <?php foreach ($ordered_services as $category => $category_services): ?>
                        <div class="service-category">
                            <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                            <?php if ($category == 'Basic Package'): ?>
                                <p class="category-subtitle">Essential car care services for everyday maintenance</p>
                            <?php elseif ($category == 'Premium Detailing'): ?>
                                <p class="category-subtitle">Complete luxury detailing for the ultimate car care experience</p>
                            <?php elseif ($category == 'Add-On Service'): ?>
                                <p class="category-subtitle">Specialized services to enhance your vehicle's protection and appearance</p>
                            <?php endif; ?>
                            
                            <div class="services-grid">
                                <?php foreach ($category_services as $service): ?>
                                    <div class="service-card">
                                        <div class="service-icon">
                                            <?php
                                            // Service image mapping
                                            $service_image = '';
                                            $service_name_clean = strtolower(str_replace([' ', '+', '(', ')'], ['-', '-', '-', ''], $service['service_name']));
                                            $service_name_clean = preg_replace('/-+/', '-', $service_name_clean); // Remove multiple hyphens
                                            $service_name_clean = trim($service_name_clean, '-'); // Remove leading/trailing hyphens
                                            
                                            // Check if image exists for this service
                                            $image_path = "../assets/images/services/{$service_name_clean}.jpg";
                                            $image_path_png = "../assets/images/services/{$service_name_clean}.png";
                                            $image_path_webp = "../assets/images/services/{$service_name_clean}.webp";
                                            
                                            if (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.jpg")) {
                                                $service_image = $image_path;
                                            } elseif (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.png")) {
                                                $service_image = $image_path_png;
                                            } elseif (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.webp")) {
                                                $service_image = $image_path_webp;
                                            }
                                            
                                            if ($service_image): ?>
                                                <img src="<?php echo $service_image; ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>" class="service-image">
                                            <?php else:
                                                // Fallback to emoji if no image found
                                                $icon_emoji = '🚗';
                                                if (strpos($service['service_name'], 'Interior') !== false) $icon_emoji = '🪟';
                                                elseif (strpos($service['service_name'], 'Exterior') !== false) $icon_emoji = '🚗';
                                                elseif (strpos($service['service_name'], 'Full Detail') !== false || strpos($service['service_name'], 'Platinum') !== false) $icon_emoji = '✨';
                                                elseif (strpos($service['service_name'], 'Engine') !== false) $icon_emoji = '🔧';
                                                elseif (strpos($service['service_name'], 'Headlight') !== false) $icon_emoji = '💡';
                                                elseif (strpos($service['service_name'], 'Glass') !== false) $icon_emoji = '💎';
                                                elseif (strpos($service['service_name'], 'Ceramic') !== false) $icon_emoji = '🛡️';
                                                elseif (strpos($service['service_name'], 'Tire') !== false) $icon_emoji = '🛞';
                                                elseif (strpos($service['service_name'], 'Wax') !== false) $icon_emoji = '🌟';
                                                elseif (strpos($service['service_name'], 'Odor') !== false) $icon_emoji = '🌬️';
                                                elseif (strpos($service['service_name'], 'Pet Hair') !== false) $icon_emoji = '🐕';
                                                elseif (strpos($service['service_name'], 'Upholstery') !== false) $icon_emoji = '🪑';
                                                elseif (strpos($service['service_name'], 'Watermark') !== false) $icon_emoji = '💧';
                                                
                                                echo $icon_emoji;
                                            endif;
                                            ?>
                                        </div>
                                        
                                        <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                        
                                        <div class="service-price-range">
                                            ₱<?php echo number_format($service['price_small'], 0); ?> - ₱<?php echo number_format($service['price_large'], 0); ?>
                                        </div>
                                        
                                        <div class="service-actions" style="margin-top: 15px; display: flex; gap: 10px;">
                                            <button onclick="viewServiceDetails(<?php echo $service['service_id']; ?>)" 
                                                    class="btn-secondary" style="
                                                        flex: 1;
                                                        background: rgba(255, 255, 255, 0.1);
                                                        color: #ffffff;
                                                        border: 1px solid rgba(255, 255, 255, 0.2);
                                                        padding: 10px 16px;
                                                        border-radius: 12px;
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                        font-weight: 500;
                                                        transition: all 0.2s ease;
                                                        backdrop-filter: blur(10px);
                                                    " onmouseover="this.style.background='rgba(255, 255, 255, 0.15)'; this.style.borderColor='rgba(255, 255, 255, 0.3)';"
                                                       onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.borderColor='rgba(255, 255, 255, 0.2)';">
                                                Learn More
                                            </button>
                                            <button onclick="startBooking(<?php echo $service['service_id']; ?>)" 
                                                    class="btn-primary" style="
                                                        flex: 1;
                                                        background: #007AFF;
                                                        color: white;
                                                        border: none;
                                                        padding: 10px 16px;
                                                        border-radius: 12px;
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                        font-weight: 600;
                                                        transition: all 0.2s ease;
                                                        box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
                                                    " onmouseover="this.style.background='#0051D5'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0, 122, 255, 0.4)';"
                                                       onmouseout="this.style.background='#007AFF'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 122, 255, 0.3)';">
                                                Book Now
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>

                <!-- Finances Section -->
                <section id="finances" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Finances</h1>
                        <p class="page-subtitle">Track your payments and expenses</p>
                    </div>

                    <div class="recent-services">
                        <h2 class="section-title">Payment History</h2>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Payment #001 - Full Car Detailing</h4>
                                <p>Paid on October 5, 2025</p>
                            </div>
                            <span class="service-status status-completed">₱800</span>
                        </div>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Payment #002 - Interior Cleaning</h4>
                                <p>Due October 10, 2025</p>
                            </div>
                            <span class="service-status status-pending">₱400</span>
                        </div>
                    </div>
                </section>

                <!-- Reviews Section -->
                <section id="reviews" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Reviews</h1>
                        <p class="page-subtitle">Your feedback and service reviews</p>
                    </div>

                    <div class="recent-services">
                        <h2 class="section-title">Your Reviews</h2>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Full Car Detailing - ⭐⭐⭐⭐⭐</h4>
                                <p>"Excellent service! My car looks brand new. Highly recommended!" - October 6, 2025</p>
                            </div>
                            <span class="service-status status-completed">5 Stars</span>
                        </div>
                    </div>
                </section>

                <!-- Notifications Section -->
                <section id="notifications" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Notifications</h1>
                        <p class="page-subtitle">Stay updated with important alerts</p>
                    </div>

                    <div class="recent-services">
                        <h2 class="section-title">Recent Notifications</h2>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Booking Confirmed</h4>
                                <p>Your booking #12346 has been confirmed for October 12, 2025</p>
                            </div>
                            <span class="service-status status-active">2 hours ago</span>
                        </div>
                        <div class="service-item">
                            <div class="service-info">
                                <h4>Payment Received</h4>
                                <p>Payment of ₱800 received for booking #12345</p>
                            </div>
                            <span class="service-status status-completed">1 day ago</span>
                        </div>
                    </div>
                </section>

                <!-- Settings Section -->
                <section id="settings" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Settings</h1>
                        <p class="page-subtitle">Manage your account preferences</p>
                    </div>

                    <div class="placeholder-content">
                        <i class="fas fa-cog"></i>
                        <h3>Settings Panel</h3>
                        <p>Manage your profile, notifications, and account preferences here.</p>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-plus"></i> Advanced Booking System</h2>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <form id="advancedBookingForm" class="modal-body" method="POST">
                <input type="hidden" name="action" value="create_advanced_booking">
                <input type="hidden" id="service_id" name="service_id">
                
                <!-- Booking Result Alert -->
                <?php if ($booking_result): ?>
                <div style="background: <?php echo $booking_result['type'] === 'success' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $booking_result['type'] === 'success' ? '#155724' : '#721c24'; ?>; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid <?php echo $booking_result['type'] === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
                    <strong><?php echo $booking_result['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
                    <?php echo htmlspecialchars($booking_result['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Business Rules Info -->
                <div class="booking-rules-card">
                    <h6><i class="fas fa-shield-alt"></i> Advanced Booking Rules</h6>
                    <div class="rules-grid">
                        <div class="rule-item"><i class="fas fa-users"></i> Max 2 customers per day</div>
                        <div class="rule-item"><i class="fas fa-clock"></i> Business hours: 8 AM - 6 PM</div>
                        <div class="rule-item"><i class="fas fa-calendar-times"></i> No weekend service</div>
                        <div class="rule-item"><i class="fas fa-calendar-alt"></i> 30-day advance limit</div>
                        <div class="rule-item"><i class="fas fa-user-check"></i> Admin approval required</div>
                        <div class="rule-item"><i class="fas fa-route"></i> Travel buffers enforced</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="selected_service_name">Selected Service:</label>
                    <div id="selected_service_name" class="selected-service-display"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vehicle_size"><i class="fas fa-car"></i> Vehicle Size:</label>
                        <select id="vehicle_size" name="vehicle_size" required class="form-control">
                            <option value="">Select size...</option>
                            <option value="small">Small (Sedan, Hatchback)</option>
                            <option value="medium">Medium (SUV, Crossover)</option>
                            <option value="large">Large (Truck, Van)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="booking_date"><i class="fas fa-calendar"></i> Booking Date:</label>
                        <input type="date" id="booking_date" name="booking_date" required class="form-control"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Available Time Slots:</label>
                    <div id="timeSlots" class="time-slots-container">
                        <p class="placeholder-text">Please select a date first to see available time slots</p>
                    </div>
                    <input type="hidden" id="booking_time" name="booking_time" required>
                </div>

                <div class="form-group">
                    <label for="vehicle_details"><i class="fas fa-car-side"></i> Vehicle Details:</label>
                    <textarea id="vehicle_details" name="vehicle_details" rows="2" class="form-control"
                              placeholder="Year, Make, Model, Color, License Plate (e.g., 2020 Toyota Camry, Blue, ABC123)"></textarea>
                </div>

                <div class="form-group">
                    <label for="service_address"><i class="fas fa-map-marker-alt"></i> Service Address:</label>
                    <textarea id="service_address" name="service_address" rows="2" class="form-control" required
                              placeholder="Complete address where service will be performed (Street, City, Barangay, Landmarks)"></textarea>
                </div>

                <div class="form-group">
                    <label for="contact_number"><i class="fas fa-phone"></i> Contact Number:</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control" required
                           placeholder="Your contact number for service coordination">
                </div>

                <div class="form-group">
                    <label for="special_requests"><i class="fas fa-comments"></i> Special Requests:</label>
                    <textarea id="special_requests" name="special_requests" rows="2" class="form-control"
                              placeholder="Any special instructions or areas of focus..."></textarea>
                </div>

                <!-- Payment Options -->
                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Payment Option:</label>
                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" id="payment_partial" name="payment_option" value="partial" checked required>
                            <label for="payment_partial" class="payment-card">
                                <div class="payment-header">
                                    <i class="fas fa-credit-card"></i>
                                    <span class="payment-title">Partial Payment (50% Down)</span>
                                    <span class="payment-badge recommended">Recommended</span>
                                </div>
                                <div class="payment-details">
                                    <div class="payment-amount">
                                        Pay <span id="partial_amount">₱0.00</span> now
                                    </div>
                                    <div class="payment-remaining">
                                        Remaining <span id="remaining_amount">₱0.00</span> on service completion
                                    </div>
                                    <div class="payment-note">Secure your booking with 50% down payment</div>
                                </div>
                            </label>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" id="payment_full" name="payment_option" value="full">
                            <label for="payment_full" class="payment-card">
                                <div class="payment-header">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span class="payment-title">Full Payment</span>
                                    <span class="payment-badge convenient">Convenient</span>
                                </div>
                                <div class="payment-details">
                                    <div class="payment-amount">
                                        Pay <span id="full_amount">₱0.00</span> now
                                    </div>
                                    <div class="payment-convenience">
                                        No money needed on service day
                                    </div>
                                    <div class="payment-note">Complete payment online, hassle-free service</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Availability Info -->
                <div id="availabilityInfo" class="info-card availability-info" style="display: none;">
                    <h6><i class="fas fa-info-circle"></i> Booking Availability</h6>
                    <div id="availabilityDetails"></div>
                </div>

                <!-- Booking Summary -->
                <div id="bookingSummary" class="info-card booking-summary" style="display: none;">
                    <h6><i class="fas fa-clipboard-check"></i> Booking Summary</h6>
                    <div id="summaryContent"></div>
                </div>
            </form>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
                <button type="submit" class="btn-primary" form="advancedBookingForm" id="submitAdvancedBooking" disabled onclick="console.log('Submit button clicked!'); alert('Submit button clicked!');">
                    <i class="fas fa-calendar-check"></i> Create Booking
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            margin: 3% auto;
            padding: 0;
            border: 1px solid #444;
            border-radius: 16px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            animation: slideIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #000;
            padding: 20px 25px;
            border-radius: 16px 16px 0 0;
            position: relative;
            text-align: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #000;
            font-size: 28px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover {
            background: rgba(0,0,0,0.1);
            transform: translateY(-50%) rotate(90deg);
        }

        /* Form Styles */
        .modal-body {
            padding: 25px;
            color: var(--text-primary);
        }

        .booking-rules-card {
            background: linear-gradient(135deg, #2c1810 0%, #3d2414 100%);
            border: 1px solid #FFD700;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .booking-rules-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FFD700, #FFA500, #FFD700);
        }

        .booking-rules-card h6 {
            margin: 0 0 15px 0;
            color: #FFD700;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .rule-item {
            color: #e0c068;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
        }

        .rule-item i {
            color: #FFD700;
            width: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #FFD700;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label i {
            color: #FFA500;
        }

        .selected-service-display {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 165, 0, 0.15) 100%);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            color: #FFD700;
            text-align: center;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(42, 42, 42, 0.8);
            border: 1px solid #555;
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            background: rgba(42, 42, 42, 1);
        }

        .time-slots-container {
            min-height: 80px;
            padding: 15px;
            border: 1px solid #555;
            border-radius: 12px;
            background: rgba(42, 42, 42, 0.5);
            margin-top: 5px;
        }

        .placeholder-text {
            color: #888;
            font-style: italic;
            margin: 0;
            text-align: center;
            padding: 20px 0;
        }

        .time-slot {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 10px;
            padding: 10px 15px;
            margin: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            min-width: 100px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            color: #1976d2;
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
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }

        .time-slot:hover::before {
            left: 100%;
        }

        .time-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.3);
            border-color: #1976d2;
        }

        .time-slot.selected {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-color: #FF8F00;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .info-card {
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid;
            position: relative;
            overflow: hidden;
        }

        .availability-info {
            background: linear-gradient(135deg, #1a2332 0%, #243447 100%);
            border-color: #4a90e2;
        }

        .availability-info h6 {
            color: #4a90e2;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .availability-info #availabilityDetails {
            color: #b3d4fc;
            font-size: 13px;
            line-height: 1.5;
        }

        .booking-summary {
            background: linear-gradient(135deg, #1b4332 0%, #2d5a45 100%);
            border-color: #52b788;
        }

        .booking-summary h6 {
            color: #52b788;
            margin: 0 0 10px 0;
            font-weight: 600;
        }

        .booking-summary #summaryContent {
            color: #d1e7dd;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-actions {
            padding: 20px 25px;
            background: rgba(26, 26, 26, 0.8);
            border-top: 1px solid #444;
            border-radius: 0 0 16px 16px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #666 0%, #777 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #777 0%, #888 100%);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 2;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:not(:disabled):hover {
            background: linear-gradient(135deg, #45a049 0%, #3d8b40 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
        }

        .btn-primary:disabled {
            background: linear-gradient(135deg, #666 0%, #555 100%);
            cursor: not-allowed;
            transform: none;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-primary:not(:disabled):hover::before {
            left: 100%;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .rules-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .time-slot {
                min-width: 90px;
                font-size: 13px;
            }
        }

        /* Custom Scrollbar */
        .modal-content::-webkit-scrollbar {
            width: 6px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #1a1a1a;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 3px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #FFA500;
        }

        /* Payment Options */
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }

        .payment-option {
            position: relative;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-card {
            display: block;
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border: 2px solid #555;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .payment-card:hover {
            border-color: #FFD700;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.2);
        }

        .payment-option input[type="radio"]:checked + .payment-card {
            border-color: #FFD700;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 215, 0, 0.05));
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .payment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .payment-header i {
            color: #FFD700;
            font-size: 18px;
        }

        .payment-title {
            font-size: 14px;
            font-weight: 600;
            color: white;
            flex: 1;
        }

        .payment-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-badge.recommended {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }

        .payment-badge.convenient {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
        }

        .payment-details {
            font-size: 12px;
        }

        .payment-amount {
            font-size: 16px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }

        .payment-remaining, .payment-convenience {
            color: #ccc;
            margin-bottom: 5px;
        }

        .payment-note {
            color: #888;
            font-style: italic;
        }
    </style>

    <script>
        // Global variables for advanced booking
        let currentServiceId = null;
        let currentServiceData = null;
        let selectedTimeSlot = null;
        let selectedService = null;

        // Service data for pricing
        const serviceData = {
            <?php 
            $service_js_data = [];
            foreach ($services as $service) {
                $service_js_data[] = $service['service_id'] . ': {
                    name: "' . htmlspecialchars($service['service_name']) . '",
                    small: ' . $service['price_small'] . ',
                    medium: ' . $service['price_medium'] . ',
                    large: ' . $service['price_large'] . '
                }';
            }
            if (!empty($service_js_data)) {
                echo implode(',', $service_js_data);
            }
            ?>
        };

        // Simple, working navigation function
        function showSection(sectionId, linkElement) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
            
            // Update nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Add active class to clicked link
            if (linkElement) {
                linkElement.classList.add('active');
            }
        }

        // Booking modal functions - Advanced System
        function openBookingModal(serviceId, serviceName) {
            currentServiceId = serviceId;
            currentServiceData = serviceData[serviceId];

            document.getElementById('service_id').value = serviceId;
            document.getElementById('selected_service_name').textContent = serviceName;
            document.getElementById('bookingModal').style.display = 'block';

            selectedService = {
                id: serviceId,
                name: serviceName,
                price: currentServiceData?.small || 0
            };

            // Reset form and initialize pricing
            resetAdvancedBookingForm();
            
            // Initialize payment amounts to 0
            document.getElementById('partial_amount').textContent = '₱0.00';
            document.getElementById('remaining_amount').textContent = '₱0.00';
            document.getElementById('full_amount').textContent = '₱0.00';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            resetAdvancedBookingForm();
        }

        // Reset advanced booking form
        function resetAdvancedBookingForm() {
            document.getElementById('advancedBookingForm').reset();
            document.getElementById('timeSlots').innerHTML = '<p class="placeholder-text">Please select a date first to see available time slots</p>';
            document.getElementById('availabilityInfo').style.display = 'none';
            document.getElementById('bookingSummary').style.display = 'none';
            selectedTimeSlot = null;
            updateSubmitButton();
        }

        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookingModal');
            if (event.target == modal) {
                closeBookingModal();
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const menuBtn = document.querySelector('.mobile-menu-btn');
                
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        console.log('Dashboard loaded successfully!');

        // Date change handler for advanced booking
        document.getElementById('booking_date').addEventListener('change', function() {
            const date = this.value;
            if (date) {
                loadAvailableTimeSlots(date);
            }
        });

        // Vehicle size and form change handlers
        document.getElementById('vehicle_size').addEventListener('change', function() {
            updatePricing();
            updateBookingSummary();
        });
        document.getElementById('vehicle_details').addEventListener('input', updateBookingSummary);
        document.getElementById('service_address').addEventListener('input', updateBookingSummary);
        document.getElementById('contact_number').addEventListener('input', updateBookingSummary);
        document.getElementById('special_requests').addEventListener('input', updateBookingSummary);

        // Payment option handlers
        document.querySelectorAll('input[name="payment_option"]').forEach(radio => {
            radio.addEventListener('change', function() {
                updatePricing();
                updateBookingSummary();
            });
        });

        // Update pricing based on vehicle size and service
        function updatePricing() {
            const vehicleSize = document.getElementById('vehicle_size').value;
            
            if (vehicleSize && selectedService && currentServiceData) {
                const basePrice = currentServiceData[vehicleSize];
                
                // Update payment amounts
                const partialAmount = basePrice * 0.5;
                const remainingAmount = basePrice * 0.5;
                
                document.getElementById('partial_amount').textContent = '₱' + partialAmount.toFixed(2);
                document.getElementById('remaining_amount').textContent = '₱' + remainingAmount.toFixed(2);
                document.getElementById('full_amount').textContent = '₱' + basePrice.toFixed(2);
            }
        }

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
            } catch (error) {
                console.error('Error loading time slots:', error);
                document.getElementById('timeSlots').innerHTML = '<p class="placeholder-text" style="color: #d32f2f;">Error loading time slots</p>';
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
            const serviceAddress = document.getElementById('service_address').value;
            const contactNumber = document.getElementById('contact_number').value;
            const paymentOption = document.querySelector('input[name="payment_option"]:checked')?.value;
            
            if (service && vehicleSize && date && time) {
                let totalAmount = 0;
                let paymentAmount = 0;
                
                if (currentServiceData && currentServiceData[vehicleSize]) {
                    totalAmount = currentServiceData[vehicleSize];
                    paymentAmount = paymentOption === 'full' ? totalAmount : totalAmount * 0.5;
                }
                
                const summaryContent = document.getElementById('summaryContent');
                summaryContent.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 13px;">
                        <div>
                            <strong>Service:</strong> ${service.name}<br>
                            <strong>Vehicle Size:</strong> ${vehicleSize.charAt(0).toUpperCase() + vehicleSize.slice(1)}<br>
                            <strong>Date & Time:</strong> ${formatDate(date)} at ${formatTime(time)}<br>
                            <strong>Contact:</strong> ${contactNumber || 'Not provided'}<br>
                        </div>
                        <div>
                            <strong>Total Amount:</strong> ₱${totalAmount.toFixed(2)}<br>
                            <strong>Payment Option:</strong> ${paymentOption === 'full' ? 'Full Payment' : '50% Down Payment'}<br>
                            <strong>Amount Due Now:</strong> <span style="color: #FFD700;">₱${paymentAmount.toFixed(2)}</span><br>
                            <strong>Status:</strong> <span style="color: #f57c00;">Pending Admin Approval</span><br>
                        </div>
                    </div>
                    ${serviceAddress ? `<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #555;">
                        <strong>Service Address:</strong><br>
                        <span style="color: #d1e7dd;">${serviceAddress}</span>
                    </div>` : ''}
                `;
                document.getElementById('bookingSummary').style.display = 'block';
            }
        }

        // Update submit button state
        function updateSubmitButton() {
            const form = document.getElementById('advancedBookingForm');
            const submitBtn = document.getElementById('submitAdvancedBooking');
            const vehicleSize = document.getElementById('vehicle_size').value;
            const serviceAddress = document.getElementById('service_address').value;
            const contactNumber = document.getElementById('contact_number').value;
            const paymentOption = document.querySelector('input[name="payment_option"]:checked');
            
            const isValid = form.checkValidity() && selectedTimeSlot && vehicleSize && 
                           serviceAddress.trim() && contactNumber.trim() && paymentOption;
            
            // TEMPORARY: Always enable button for debugging
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-calendar-check"></i> DEBUG: Test Submission';
            submitBtn.style.background = '#ff9800';
        }

        // Handle advanced booking form submission
        document.getElementById('advancedBookingForm').addEventListener('submit', function(e) {
            // Debug: Log all form data before submission
            const formData = new FormData(this);
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
            
            // TEMPORARY: Remove all validation to test form submission
            console.log('Form submission allowed - bypassing validation for debugging');
            
            // Make sure the hidden booking_time field is set (if time selected)
            if (selectedTimeSlot) {
                document.getElementById('booking_time').value = selectedTimeSlot;
            }
            
            // Let the form submit naturally (don't prevent default)
        });

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
        document.getElementById('advancedBookingForm').addEventListener('input', updateSubmitButton);
        
        // Initialize submit button on page load
        updateSubmitButton();

        // Navigate to service details page
        function viewServiceDetails(serviceId) {
            // Show service details modal or redirect to service details page
            window.location.href = `service_details.php?id=${serviceId}`;
        }
        
        // Start booking flow - goes to professional 9-step booking
        function startBooking(serviceId) {
            window.location.href = `booking/step1_service_selection.php?service_id=${serviceId}`;
        }

        // Featured Services Carousel Functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-item');
        const dots = document.querySelectorAll('.dot');
        const track = document.getElementById('featuredTrack');

        function updateCarousel() {
            // Update transform
            const translateX = currentSlide * -520; // 480px width + 40px gap
            track.style.transform = `translateX(${translateX}px)`;

            // Update active states
            slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === currentSlide);
            });

            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            updateCarousel();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateCarousel();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateCarousel();
        }

        // Event listeners for carousel
        document.getElementById('carouselNext')?.addEventListener('click', nextSlide);
        document.getElementById('carouselPrev')?.addEventListener('click', prevSlide);

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });

        // Auto-play carousel
        setInterval(nextSlide, 5000);

        // Initialize carousel
        updateCarousel();

        // Color options functionality
        document.querySelectorAll('.color-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                const card = this.closest('.featured-service-card');
                card.querySelectorAll('.color-dot').forEach(d => d.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Dynamic Sidebar Auto-Hide Functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarTrigger = document.getElementById('sidebarTrigger');
        const mainContent = document.querySelector('.main-content');
        let sidebarTimeout;
        let isMouseOverSidebar = false;
        let isMouseOverTrigger = false;

        function showSidebar() {
            clearTimeout(sidebarTimeout);
            sidebar.classList.add('show');
            isMouseOverSidebar = true;
        }

        function hideSidebar() {
            if (!isMouseOverSidebar && !isMouseOverTrigger) {
                sidebar.classList.remove('show');
            }
        }

        function scheduleSidebarHide() {
            sidebarTimeout = setTimeout(() => {
                if (!isMouseOverSidebar && !isMouseOverTrigger) {
                    hideSidebar();
                }
            }, 300); // 300ms delay before hiding
        }

        // Trigger area events
        sidebarTrigger.addEventListener('mouseenter', () => {
            isMouseOverTrigger = true;
            showSidebar();
        });

        sidebarTrigger.addEventListener('mouseleave', () => {
            isMouseOverTrigger = false;
            scheduleSidebarHide();
        });

        // Sidebar events
        sidebar.addEventListener('mouseenter', () => {
            isMouseOverSidebar = true;
            clearTimeout(sidebarTimeout);
        });

        sidebar.addEventListener('mouseleave', () => {
            isMouseOverSidebar = false;
            scheduleSidebarHide();
        });

        // Optional: Show sidebar when hovering near the left edge of the screen
        document.addEventListener('mousemove', (e) => {
            if (e.clientX <= 30 && !sidebar.classList.contains('show')) {
                isMouseOverTrigger = true;
                showSidebar();
            } else if (e.clientX > 280 && sidebar.classList.contains('show') && !isMouseOverSidebar) {
                isMouseOverTrigger = false;
                scheduleSidebarHide();
            }
        });

        // Prevent sidebar from hiding when clicking on navigation links
        sidebar.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-link')) {
                // Keep sidebar open for a moment after clicking a link
                clearTimeout(sidebarTimeout);
                setTimeout(() => {
                    if (!isMouseOverSidebar) {
                        hideSidebar();
                    }
                }, 1000);
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                // On mobile, revert to normal behavior
                sidebar.classList.remove('show');
            }
        });

        console.log('Dashboard loaded successfully!');
    </script>
</body>
</html>