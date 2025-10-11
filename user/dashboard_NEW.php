<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user information
$user_id = $_SESSION['user_id'];
$user_bookings = 0;
$user_spent = 0;
$pending_bookings = 0;
$completed_bookings = 0;
$recent_bookings = [];

try {
    // Check if bookings table exists
    $bookings_check = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount();
    if ($bookings_check > 0) {
        // User's total bookings count
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $user_bookings = $stmt->fetchColumn() ?: 0;

        // User's pending bookings
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending'");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $pending_bookings = $stmt->fetchColumn() ?: 0;

        // User's completed bookings
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'completed'");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $completed_bookings = $stmt->fetchColumn() ?: 0;
        
        // User's total spent (try multiple column names)
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = ? AND total_amount IS NOT NULL");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $user_spent = $stmt->fetchColumn() ?: 0;

        // If total_amount doesn't exist, try 'total' column
        if ($user_spent == 0) {
            $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE user_id = ? AND total IS NOT NULL");
            $stmt->bindParam(1, $user_id);
            $stmt->execute();
            $user_spent = $stmt->fetchColumn() ?: 0;
        }
        
        // Recent bookings with enhanced data
        $stmt = $db->prepare("
            SELECT 
                COALESCE(service_type, 'Car Detailing Service') as service_type,
                COALESCE(total_amount, total, 0) as amount,
                status,
                COALESCE(booking_date, created_at, NOW()) as date_created
            FROM bookings 
            WHERE user_id = ? 
            ORDER BY COALESCE(booking_date, created_at, NOW()) DESC 
            LIMIT 5
        ");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log("User dashboard query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #FFD700;
            --font-size: 14px;
            --border-radius: 8px;
            --transition-duration: 0.3s;
            --bg-primary: #1a1a1a;
            --bg-secondary: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: all var(--transition-duration) ease;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            font-size: var(--font-size);
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

        /* Sidebar - Dark Theme with Gold Accents */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-right: 1px solid rgba(255, 215, 0, 0.2);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all var(--transition-duration) ease;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            text-align: center;
            background: rgba(255, 215, 0, 0.05);
        }

        .logo {
            color: #FFD700;
            font-size: 26px;
            font-weight: 900;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
        }

        .logo i {
            font-size: 30px;
            color: #FFD700;
        }

        .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            text-align: center;
            background: rgba(255, 215, 0, 0.03);
        }

        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            font-size: 22px;
            color: #1a1a2e;
            font-weight: 700;
            border: 3px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .user-badge {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a2e;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .nav-menu {
            padding: 15px 0;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            font-size: 14px;
            margin: 2px 0;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            border-left-color: #FFD700;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 215, 0, 0.05) 100%);
            color: #FFD700;
            border-left-color: #FFD700;
            box-shadow: inset 0 0 10px rgba(255, 215, 0, 0.1);
        }

        .nav-link i {
            width: 18px;
            text-align: center;
            font-size: 16px;
            opacity: 0.8;
        }

        .nav-link:hover i,
        .nav-link.active i {
            opacity: 1;
            color: #FFD700;
        }

        /* Bottom navigation section */
        .nav-bottom {
            border-top: 1px solid rgba(255, 215, 0, 0.2);
            padding: 15px 0 20px 0;
            margin-top: auto;
        }

        .nav-bottom .nav-link {
            color: rgba(255, 255, 255, 0.6);
        }

        .nav-bottom .nav-link:hover {
            color: #FFD700;
            background: rgba(255, 215, 0, 0.08);
        }

        /* Main Content - Admin Match */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: var(--bg-primary);
            min-height: 100vh;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Page Header - Admin Match */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(42, 42, 42, 0.3);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
            margin-top: 5px;
        }

        /* Card System - Admin Match */
        .section-card {
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            transition: all var(--transition-duration) ease;
        }

        .section-card:hover {
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .section-card h3 {
            color: var(--accent-color);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-card h3 i {
            font-size: 18px;
        }

        /* Stats Grid - Admin Match */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 25px;
            transition: all var(--transition-duration) ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 24px;
            color: var(--accent-color);
            background: rgba(255, 215, 0, 0.1);
            padding: 15px;
            border-radius: var(--border-radius);
        }

        /* Recent Bookings Section - Admin Match */
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            font-size: 18px;
        }

        /* Booking Guide Styles */
        .booking-guide {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, rgba(42, 42, 42, 0.8) 100%);
            border: 2px solid rgba(255, 215, 0, 0.2);
            margin-bottom: 30px;
        }

        .booking-steps {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 30px;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
            background: rgba(26, 26, 26, 0.5);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            transition: all var(--transition-duration) ease;
        }

        .step-item:hover {
            border-color: rgba(255, 215, 0, 0.3);
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .step-number {
            background: var(--accent-color);
            color: var(--bg-primary);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-content h3 {
            color: var(--accent-color);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .step-content p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0;
        }

        .step-icon {
            color: var(--accent-color);
            font-size: 24px;
            opacity: 0.7;
            flex-shrink: 0;
        }

        .guide-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid rgba(42, 42, 42, 0.3);
        }

        @media (max-width: 768px) {
            .step-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .guide-actions {
                flex-direction: column;
            }
            
            .guide-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Car Information Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .car-info-modal {
            background: var(--bg-primary);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: var(--border-radius);
            max-width: 450px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            padding: 25px 25px 20px;
            border-bottom: 1px solid rgba(42, 42, 42, 0.3);
            position: relative;
        }

        .modal-header h2 {
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }

        .modal-header p {
            color: var(--text-secondary);
            font-size: 16px;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all var(--transition-duration) ease;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-color);
        }

        .modal-body {
            padding: 25px;
        }

        .car-info-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .car-select {
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 15px;
            color: var(--text-primary);
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            transition: all var(--transition-duration) ease;
            cursor: pointer;
        }

        .car-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .car-select option {
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 10px;
        }

        .price-btn {
            background: var(--accent-color);
            color: var(--bg-primary);
            border: none;
            border-radius: var(--border-radius);
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-duration) ease;
            margin-top: 10px;
        }

        .price-btn:hover {
            background: #e6c200;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .cant-find {
            text-align: center;
            margin-top: 15px;
        }

        .cant-find a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            transition: color var(--transition-duration) ease;
        }

        .cant-find a:hover {
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .car-info-modal {
                margin: 10px;
                max-width: none;
                width: calc(100% - 20px);
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-body {
                padding: 20px;
            }
        }

        /* Booking Modal Styles */
        .booking-modal {
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .booking-step {
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 20px;
            background: rgba(42, 42, 42, 0.2);
        }

        .booking-step h3 {
            color: var(--accent-color);
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .booking-summary {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: var(--border-radius);
            padding: 20px;
        }

        .booking-summary h3 {
            color: var(--accent-color);
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(42, 42, 42, 0.2);
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-row.total {
            font-weight: 600;
            font-size: 18px;
            color: var(--accent-color);
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 215, 0, 0.3);
        }

        .booking-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .booking-modal {
                margin: 10px;
                max-width: none;
                width: calc(100% - 20px);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-actions {
                flex-direction: column-reverse;
            }
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(18, 18, 18, 0.6);
            border: 1px solid rgba(255, 215, 0, 0.1);
            border-radius: 12px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            border-color: rgba(255, 215, 0, 0.3);
            transform: translateX(5px);
        }

        .booking-info h4 {
            color: var(--accent-color);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .booking-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .booking-status {
            padding: 8px 16px;
            border-radius: var(--border-radius);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-pending {
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-active {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.3);
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            color: rgba(255, 215, 0, 0.3);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: var(--text-primary);
            font-size: 20px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 25px;
            opacity: 0.8;
        }

        .empty-state .btn-primary {
            background: linear-gradient(135deg, var(--accent-color) 0%, #FFA500 100%);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            color: var(--bg-primary);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .empty-state .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: rgba(34, 34, 34, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.15);
            border-radius: 16px;
            padding: 25px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.1);
        }

        .action-icon {
            background: var(--accent-color);
            color: var(--bg-primary);
            padding: 20px;
            border-radius: var(--border-radius);
            font-size: 24px;
            min-width: 64px;
            text-align: center;
        }

        .action-content h3 {
            color: var(--accent-color);
            font-size: 18px;
            margin-bottom: 5px;
        }

        .action-content p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--text-secondary);
        }

        .no-data p {
            margin: 0;
            font-style: italic;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--accent-color);
                color: var(--bg-primary);
                border: none;
                border-radius: 8px;
                padding: 12px;
                cursor: pointer;
                font-size: 18px;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        /* Logout Button */
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 14px;
            transition: all var(--transition-duration) ease;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            width: 100%;
            justify-content: center;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        /* Button System - Admin Match */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-duration) ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            line-height: 1;
        }

        .btn-primary {
            background: var(--accent-color);
            color: var(--bg-primary);
        }

        .btn-primary:hover {
            background: #e6c200;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid rgba(42, 42, 42, 0.3);
        }

        .btn-secondary:hover {
            background: #333;
            border-color: var(--accent-color);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 16px;
        }

        /* Booking Components */
        .booking-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-duration) ease;
            font-size: 14px;
            font-weight: 500;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--accent-color);
            color: var(--bg-primary);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }

        .booking-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .booking-item {
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 25px;
            transition: all var(--transition-duration) ease;
        }

        .booking-item:hover {
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .booking-info h4 {
            color: var(--accent-color);
            margin-bottom: 8px;
            font-size: 18px;
            font-weight: 600;
        }

        .booking-info p {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 2px 0;
        }

        .booking-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent-color);
        }

        .booking-status {
            text-align: right;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: var(--border-radius);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: inline-block;
            border: 1px solid transparent;
        }

        .status-badge.pending {
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent-color);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .status-badge.confirmed {
            background: rgba(40, 167, 69, 0.1);
            color: #28A745;
            border-color: rgba(40, 167, 69, 0.3);
        }

        .status-badge.completed {
            background: rgba(32, 201, 151, 0.1);
            color: #20C997;
            border-color: rgba(32, 201, 151, 0.3);
        }

        .status-badge.cancelled {
            background: rgba(220, 53, 69, 0.1);
            color: #DC3545;
            border-color: rgba(220, 53, 69, 0.3);
        }

        .booking-details {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 15px;
        }

        .booking-details p {
            margin: 4px 0;
        }

        .booking-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .booking-actions button {
            flex: 1;
            min-width: 120px;
        }

        /* Service Components */
        .service-categories {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .category-btn {
            padding: 10px 20px;
            background: rgba(42, 42, 42, 0.8);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 25px;
            color: #ccc;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .category-btn.active,
        .category-btn:hover {
            background: var(--accent-color);
            color: var(--bg-primary);
            border-color: var(--accent-color);
            transform: translateY(-1px);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .service-card {
            background: var(--bg-secondary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            padding: 25px;
            transition: all var(--transition-duration) ease;
            position: relative;
            overflow: hidden;
        }

        .service-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 215, 0, 0.3);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .service-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 4px 12px;
            background: var(--accent-color);
            color: var(--bg-primary);
            border-radius: var(--border-radius);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .service-badge.premium {
            background: linear-gradient(135deg, #9C27B0, #673AB7);
            color: #fff;
        }

        .service-image {
            text-align: center;
            margin-bottom: 20px;
        }

        .service-image i {
            font-size: 48px;
            color: #FFD700;
        }

        .service-content h3 {
            color: #FFD700;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .service-content > p {
            color: #aaa;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .service-features {
            list-style: none;
            margin-bottom: 20px;
        }

        .service-features li {
            color: #ccc;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-features i {
            color: #28A745;
            font-size: 12px;
        }

        .service-pricing {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-top: 1px solid rgba(255, 215, 0, 0.1);
        }

        .price {
            font-size: 28px;
            font-weight: 700;
            color: #FFD700;
        }

        .original-price {
            font-size: 16px;
            color: #888;
            text-decoration: line-through;
            margin-left: 10px;
        }

        .duration {
            color: #aaa;
            font-size: 14px;
        }

        .addon-services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .addon-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(42, 42, 42, 0.6);
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .addon-info h4 {
            color: #FFD700;
            margin-bottom: 4px;
        }

        .addon-info p {
            color: #aaa;
            font-size: 14px;
        }

        .addon-price {
            color: #FFD700;
            font-weight: 600;
            font-size: 16px;
        }

        /* Profile Components */
        .profile-form {
            max-width: 800px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 12px 15px;
            background: var(--bg-primary);
            border: 1px solid rgba(42, 42, 42, 0.3);
            border-radius: var(--border-radius);
            color: var(--text-primary);
            font-size: 14px;
            transition: all var(--transition-duration) ease;
            width: 100%;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: var(--text-secondary);
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .vehicle-card {
            background: rgba(42, 42, 42, 0.6);
            border: 1px solid rgba(255, 215, 0, 0.15);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .vehicle-card:hover {
            border-color: rgba(255, 215, 0, 0.3);
            transform: translateY(-2px);
        }

        .vehicle-image {
            text-align: center;
            margin-bottom: 15px;
        }

        .vehicle-image i {
            font-size: 36px;
            color: #FFD700;
        }

        .vehicle-info h4 {
            color: #FFD700;
            margin-bottom: 10px;
            text-align: center;
        }

        .vehicle-info p {
            color: #ccc;
            margin: 4px 0;
            font-size: 14px;
        }

        .vehicle-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .vehicle-actions button {
            flex: 1;
        }

        .preference-group {
            margin-bottom: 30px;
        }

        .preference-group h4 {
            color: #FFD700;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ccc;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            accent-color: #FFD700;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .security-options {
            margin-top: 25px;
        }

        .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        .security-item:last-child {
            border-bottom: none;
        }

        .security-info h4 {
            color: #FFD700;
            margin-bottom: 4px;
        }

        .security-info p {
            color: #aaa;
            font-size: 14px;
        }

        /* Support Components */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-action-card {
            background: rgba(34, 34, 34, 0.8);
            border: 1px solid rgba(255, 215, 0, 0.15);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .action-icon {
            margin-bottom: 15px;
        }

        .action-icon i {
            font-size: 36px;
            color: #FFD700;
        }

        .quick-action-card h3 {
            color: #FFD700;
            margin-bottom: 8px;
        }

        .quick-action-card p {
            color: #aaa;
            margin-bottom: 20px;
        }

        .faq-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .faq-item {
            border: 1px solid rgba(255, 215, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: rgba(42, 42, 42, 0.6);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .faq-question h4 {
            color: #FFD700;
            margin: 0;
        }

        .faq-question i {
            color: #FFD700;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-question i {
            transform: rotate(180deg);
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item.active .faq-answer {
            padding: 20px;
            max-height: 200px;
        }

        .faq-answer p {
            color: #ccc;
            line-height: 1.6;
            margin: 0;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: rgba(42, 42, 42, 0.6);
            border-radius: 8px;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .contact-icon i {
            font-size: 24px;
            color: #FFD700;
        }

        .contact-info h4 {
            color: #FFD700;
            margin-bottom: 4px;
        }

        .contact-info p {
            color: #ccc;
            font-size: 14px;
            line-height: 1.4;
        }

        .support-form {
            max-width: 600px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .booking-actions {
                flex-direction: column;
            }

            .booking-actions button {
                min-width: auto;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .vehicles-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard fade-in">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-car"></i>
                    <span>Ride Revive</span>
                </div>
            </div>
            
            <div class="nav-menu">
                <a href="#" class="nav-link active" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('dashboard');
                    if (target) { target.style.display = 'block'; } else { alert('Dashboard section not found'); }
                    return false;">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('bookings');
                    if (target) { target.style.display = 'block'; } else { alert('Bookings section not found'); }
                    return false;">
                    <i class="fas fa-users"></i>
                    <span>My Bookings</span>
                </a>
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('finances');
                    if (target) { target.style.display = 'block'; } else { alert('Finances section not found'); }
                    return false;">
                    <i class="fas fa-chart-line"></i>
                    <span>Finances</span>
                </a>
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('services');
                    if (target) { target.style.display = 'block'; } else { alert('Services section not found'); }
                    return false;">
                    <span>Services</span>
                </a>
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('reviews');
                    if (target) { target.style.display = 'block'; } else { alert('Reviews section not found'); }
                    return false;">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('notifications');
                    if (target) { target.style.display = 'block'; } else { alert('Notifications section not found'); }
                    return false;">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </div>

            <div class="nav-bottom">
                <a href="#" class="nav-link" onclick="
                    var sections = document.getElementsByClassName('content-section');
                    for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                    var target = document.getElementById('settings');
                    if (target) { target.style.display = 'block'; } else { alert('Settings section not found'); }
                    return false;">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="#" class="nav-link logout-link" onclick="window.location.href='../auth/logout.php'">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="page-header">
                    <h1 class="page-title">My Dashboard</h1>
                    <p class="page-subtitle">Welcome back! Here's your service overview and recent activity.</p>
                </div>

                <!-- How to Book Guide -->
                <div class="section-card booking-guide">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i> How to Book Your Car Detailing Service
                    </h2>
                    <div class="booking-steps">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Enter Your Car Information</h3>
                                <p>Start by entering your vehicle details — year, make, model, trim, body type, and color. This helps us generate a personalized service and accurate pricing just for your car. Once complete, click "See My Price" to get an instant, transparent quote with no hidden fees.</p>
                            </div>
                            <div class="step-icon">
                                <i class="fas fa-car"></i>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Choose Your Package</h3>
                                <p>Select the detailing package that fits your car's needs. Each option includes a clear breakdown of what's covered, estimated duration, and who it's best for — whether you need quick maintenance or deep cleaning. You can also switch between Full Detail and Interior Only packages to match your priorities.</p>
                            </div>
                            <div class="step-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>

                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Enter Your Location</h3>
                                <p>Tell us where to meet you! Panda Hub is fully mobile — our professional detailers come to your home, office, or any location you choose.</p>
                            </div>
                            <div class="step-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="guide-actions">
                        <button class="btn btn-primary btn-lg" onclick="openCarInfoModal()">
                            <i class="fas fa-car"></i> Enter Your Car Information
                        </button>
                        <button class="btn btn-secondary" onclick="
                            var sections = document.getElementsByClassName('content-section');
                            for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                            document.getElementById('services').style.display = 'block';">
                            <i class="fas fa-rocket"></i> Browse Services
                        </button>
                        <button class="btn btn-secondary" onclick="
                            var sections = document.getElementsByClassName('content-section');
                            for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                            var target = document.getElementById('profile') || document.getElementById('settings');
                            if (target) target.style.display = 'block';">
                            <i class="fas fa-user-cog"></i> Update Profile
                        </button>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($user_bookings); ?></h3>
                                <p>Total Bookings</p>
                            </div>
                            <i class="fas fa-calendar-check stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($pending_bookings); ?></h3>
                                <p>Pending Services</p>
                            </div>
                            <i class="fas fa-clock stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($completed_bookings); ?></h3>
                                <p>Completed Services</p>
                            </div>
                            <i class="fas fa-check-circle stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3>₱<?php echo number_format($user_spent, 2); ?></h3>
                                <p>Total Spent</p>
                            </div>
                            <i class="fas fa-peso-sign stat-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i> Recent Bookings
                    </h2>
                    <?php if (!empty($recent_bookings)): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['service_type']); ?></h4>
                                <p>
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('M j, Y', strtotime($booking['date_created'])); ?>
                                    <?php if ($booking['amount'] > 0): ?>
                                        • <i class="fas fa-peso-sign"></i> ₱<?php echo number_format($booking['amount'], 2); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-calendar-plus"></i>
                            <p>No bookings yet. Book your first service to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="section-card">
                    <h2 class="section-title">
                        <i class="fas fa-bolt"></i> Quick Actions
                    </h2>
                    <div class="actions-grid">
                        <a href="#" class="action-card" onclick="
                            var sections = document.getElementsByClassName('content-section');
                            for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                            document.getElementById('services').style.display = 'block';
                            return false;">
                            <div class="action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="action-content">
                                <h3>Book New Service</h3>
                                <p>Schedule your next car detailing appointment</p>
                            </div>
                        </a>
                        <a href="#" class="action-card" onclick="
                            var sections = document.getElementsByClassName('content-section');
                            for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                            document.getElementById('bookings').style.display = 'block';
                            return false;">
                            <div class="action-icon">
                                <i class="fas fa-list-alt"></i>
                            </div>
                            <div class="action-content">
                                <h3>View All Bookings</h3>
                                <p>See your complete booking history</p>
                            </div>
                        </a>
                        <a href="#" class="action-card" onclick="
                            var sections = document.getElementsByClassName('content-section');
                            for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                            var target = document.getElementById('profile') || document.getElementById('settings');
                            if (target) target.style.display = 'block';
                            return false;">
                            <div class="action-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div class="action-content">
                                <h3>Update Profile</h3>
                                <p>Manage your account settings</p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Bookings Section -->
            <section id="bookings" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">My Bookings</h1>
                    <p class="page-subtitle">View and manage all your service bookings.</p>
                    <button class="btn-primary" onclick="showNewBookingModal()">
                        <i class="fas fa-plus"></i> New Booking
                    </button>
                </div>

                <!-- Booking Filters -->
                <div class="booking-filters">
                    <button class="filter-btn active" onclick="filterBookings('all')">All Bookings</button>
                    <button class="filter-btn" onclick="filterBookings('pending')">Pending</button>
                    <button class="filter-btn" onclick="filterBookings('confirmed')">Confirmed</button>
                    <button class="filter-btn" onclick="filterBookings('in-progress')">In Progress</button>
                    <button class="filter-btn" onclick="filterBookings('completed')">Completed</button>
                    <button class="filter-btn" onclick="filterBookings('cancelled')">Cancelled</button>
                </div>

                <!-- Active Bookings -->
                <div class="section-card">
                    <h3><i class="fas fa-clock"></i> Upcoming Appointments</h3>
                    <div class="booking-list" id="upcomingBookings">
                        <?php
                        // Fetch upcoming bookings (pending, confirmed, in-progress) for the current user
                        $upcoming_query = "SELECT * FROM bookings WHERE customer_email = ? AND status IN ('pending', 'confirmed', 'in-progress') AND booking_date >= CURDATE() ORDER BY booking_date ASC, booking_time ASC";
                        $upcoming_stmt = $conn->prepare($upcoming_query);
                        $upcoming_stmt->bind_param("s", $_SESSION['user_email']);
                        $upcoming_stmt->execute();
                        $upcoming_result = $upcoming_stmt->get_result();
                        
                        if ($upcoming_result->num_rows > 0):
                            while ($booking = $upcoming_result->fetch_assoc()):
                                $status_class = strtolower(str_replace(' ', '-', $booking['status']));
                                $service_location = $booking['service_location'] === 'mobile' ? 'Mobile Service - ' . $booking['location'] : 'In-Shop Service';
                                $formatted_date = date('F j, Y', strtotime($booking['booking_date']));
                                $formatted_time = date('g:i A', strtotime($booking['booking_time']));
                        ?>
                        <div class="booking-item <?php echo $status_class; ?>">
                            <div class="booking-header">
                                <div class="booking-info">
                                    <h4><?php echo htmlspecialchars($booking['service_type']); ?></h4>
                                    <p><i class="fas fa-calendar"></i> <?php echo $formatted_date . ' at ' . $formatted_time; ?></p>
                                    <p><i class="fas fa-<?php echo $booking['service_location'] === 'mobile' ? 'map-marker-alt' : 'building'; ?>"></i> <?php echo htmlspecialchars($service_location); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                    <div class="booking-price">₱<?php echo number_format($booking['total_amount'], 2); ?></div>
                                </div>
                            </div>
                            <div class="booking-details">
                                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_year'] . ' ' . $booking['vehicle_make'] . ' ' . $booking['vehicle_model'] . ' - ' . $booking['license_plate']); ?></p>
                                <?php if ($booking['special_requests']): ?>
                                <p><strong>Special Requests:</strong> <?php echo htmlspecialchars($booking['special_requests']); ?></p>
                                <?php endif; ?>
                                <p><strong>Package:</strong> <?php echo htmlspecialchars($booking['service_package']); ?></p>
                            </div>
                            <div class="booking-actions">
                                <button class="btn-secondary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if ($booking['status'] !== 'in-progress'): ?>
                                <button class="btn-warning" onclick="rescheduleBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                                <button class="btn-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No Upcoming Appointments</h4>
                            <p>You don't have any upcoming bookings. Ready to book a service?</p>
                            <button class="btn-primary" onclick="
                                var sections = document.getElementsByClassName('content-section');
                                for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                                document.getElementById('services').style.display = 'block';">
                                <i class="fas fa-plus"></i> Book a Service
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Booking History -->
                <div class="section-card">
                    <h3><i class="fas fa-history"></i> Booking History</h3>
                    <div class="booking-list" id="bookingHistory">
                        <?php
                        // Fetch completed and cancelled bookings for the current user
                        $history_query = "SELECT * FROM bookings WHERE customer_email = ? AND status IN ('completed', 'cancelled') ORDER BY booking_date DESC, booking_time DESC LIMIT 10";
                        $history_stmt = $conn->prepare($history_query);
                        $history_stmt->bind_param("s", $_SESSION['user_email']);
                        $history_stmt->execute();
                        $history_result = $history_stmt->get_result();
                        
                        if ($history_result->num_rows > 0):
                            while ($booking = $history_result->fetch_assoc()):
                                $status_class = strtolower(str_replace(' ', '-', $booking['status']));
                                $service_location = $booking['service_location'] === 'mobile' ? 'Mobile Service - ' . $booking['location'] : 'In-Shop Service';
                                $formatted_date = date('F j, Y', strtotime($booking['booking_date']));
                        ?>
                        <div class="booking-item <?php echo $status_class; ?>">
                            <div class="booking-header">
                                <div class="booking-info">
                                    <h4><?php echo htmlspecialchars($booking['service_type']); ?></h4>
                                    <p><i class="fas fa-calendar"></i> <?php echo $formatted_date; ?></p>
                                    <p><i class="fas fa-<?php echo $booking['service_location'] === 'mobile' ? 'map-marker-alt' : 'building'; ?>"></i> <?php echo htmlspecialchars($service_location); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['status']); ?></span>
                                    <div class="booking-price">₱<?php echo number_format($booking['total_amount'], 2); ?></div>
                                </div>
                            </div>
                            <div class="booking-details">
                                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_year'] . ' ' . $booking['vehicle_make'] . ' ' . $booking['vehicle_model'] . ' - ' . $booking['license_plate']); ?></p>
                                <p><strong>Package:</strong> <?php echo htmlspecialchars($booking['service_package']); ?></p>
                            </div>
                            <div class="booking-actions">
                                <button class="btn-secondary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if ($booking['status'] === 'completed'): ?>
                                <button class="btn-primary" onclick="rebookService(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-redo"></i> Book Again
                                </button>
                                <button class="btn-accent" onclick="leaveReview(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-star"></i> Leave Review
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h4>No Booking History</h4>
                            <p>You haven't completed any services yet. Your booking history will appear here.</p>
                            <button class="btn-primary" onclick="
                                var sections = document.getElementsByClassName('content-section');
                                for (var i = 0; i < sections.length; i++) { sections[i].style.display = 'none'; }
                                document.getElementById('services').style.display = 'block';">
                                <i class="fas fa-plus"></i> Book Your First Service
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section id="services" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Book a Service</h1>
                    <p class="page-subtitle">Choose from our premium car detailing services.</p>
                </div>

                <!-- Service Categories -->
                <div class="service-categories">
                    <button class="category-btn active" onclick="filterServices('all')">All Services</button>
                    <button class="category-btn" onclick="filterServices('exterior')">Exterior</button>
                    <button class="category-btn" onclick="filterServices('interior')">Interior</button>
                    <button class="category-btn" onclick="filterServices('protection')">Protection</button>
                    <button class="category-btn" onclick="filterServices('packages')">Packages</button>
                </div>

                <!-- Service Grid -->
                <div class="services-grid">
                    <!-- Basic Services -->
                    <div class="service-card" data-category="exterior">
                        <div class="service-image">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="service-content">
                            <h3>Basic Wash & Wax</h3>
                            <p>Complete exterior wash, dry, and protective wax application.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> Hand wash & dry</li>
                                <li><i class="fas fa-check"></i> Tire shine</li>
                                <li><i class="fas fa-check"></i> Protective wax</li>
                                <li><i class="fas fa-check"></i> Window cleaning</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱1,500</div>
                                <div class="duration">~1 hour</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('basic-wash', 1500, 60)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>

                    <div class="service-card" data-category="interior">
                        <div class="service-image">
                            <i class="fas fa-car-side"></i>
                        </div>
                        <div class="service-content">
                            <h3>Interior Detail</h3>
                            <p>Deep cleaning and conditioning of all interior surfaces.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> Vacuum all surfaces</li>
                                <li><i class="fas fa-check"></i> Steam clean upholstery</li>
                                <li><i class="fas fa-check"></i> Leather conditioning</li>
                                <li><i class="fas fa-check"></i> Dashboard protection</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱3,500</div>
                                <div class="duration">~2 hours</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('interior-detail', 3500, 120)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>

                    <div class="service-card" data-category="exterior">
                        <div class="service-image">
                            <i class="fas fa-spray-can"></i>
                        </div>
                        <div class="service-content">
                            <h3>Exterior Detail</h3>
                            <p>Premium exterior cleaning, polishing, and protection.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> Clay bar treatment</li>
                                <li><i class="fas fa-check"></i> Machine polishing</li>
                                <li><i class="fas fa-check"></i> Paint sealant</li>
                                <li><i class="fas fa-check"></i> Trim restoration</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱4,500</div>
                                <div class="duration">~3 hours</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('exterior-detail', 4500, 180)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>

                    <div class="service-card" data-category="packages" data-featured="true">
                        <div class="service-badge">Most Popular</div>
                        <div class="service-image">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="service-content">
                            <h3>Complete Detail Package</h3>
                            <p>Our most comprehensive service combining interior and exterior detailing.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> Everything from Interior Detail</li>
                                <li><i class="fas fa-check"></i> Everything from Exterior Detail</li>
                                <li><i class="fas fa-check"></i> Engine bay cleaning</li>
                                <li><i class="fas fa-check"></i> Headlight restoration</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱7,000</div>
                                <div class="original-price">₱8,000</div>
                                <div class="duration">~4 hours</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('complete-package', 7000, 240)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>

                    <div class="service-card" data-category="protection">
                        <div class="service-image">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="service-content">
                            <h3>Paint Correction</h3>
                            <p>Professional paint correction to remove swirls and scratches.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> Multi-stage polishing</li>
                                <li><i class="fas fa-check"></i> Scratch removal</li>
                                <li><i class="fas fa-check"></i> Swirl mark elimination</li>
                                <li><i class="fas fa-check"></i> Paint depth analysis</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱12,000</div>
                                <div class="duration">~6 hours</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('paint-correction', 12000, 360)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>

                    <div class="service-card" data-category="protection" data-premium="true">
                        <div class="service-badge premium">Premium</div>
                        <div class="service-image">
                            <i class="fas fa-gem"></i>
                        </div>
                        <div class="service-content">
                            <h3>Ceramic Coating</h3>
                            <p>Ultimate paint protection with professional ceramic coating application.</p>
                            <ul class="service-features">
                                <li><i class="fas fa-check"></i> 2-year protection warranty</li>
                                <li><i class="fas fa-check"></i> Paint correction included</li>
                                <li><i class="fas fa-check"></i> Hydrophobic properties</li>
                                <li><i class="fas fa-check"></i> UV protection</li>
                            </ul>
                            <div class="service-pricing">
                                <div class="price">₱25,000</div>
                                <div class="duration">2 days</div>
                            </div>
                            <button class="btn-primary" onclick="bookService('ceramic-coating', 25000, 960)">
                                <i class="fas fa-calendar-plus"></i> Book Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Add-on Services -->
                <div class="section-card">
                    <h3><i class="fas fa-plus-circle"></i> Add-on Services</h3>
                    <div class="addon-services">
                        <div class="addon-item">
                            <div class="addon-info">
                                <h4>Mobile Service</h4>
                                <p>We come to your location</p>
                            </div>
                            <div class="addon-price">+$25</div>
                        </div>
                        <div class="addon-item">
                            <div class="addon-info">
                                <h4>Rush Service</h4>
                                <p>Same-day booking (4hr notice)</p>
                            </div>
                            <div class="addon-price">+25%</div>
                        </div>
                        <div class="addon-item">
                            <div class="addon-info">
                                <h4>Pet Hair Removal</h4>
                                <p>Extra cleaning for pet owners</p>
                            </div>
                            <div class="addon-price">+$15</div>
                        </div>
                        <div class="addon-item">
                            <div class="addon-info">
                                <h4>Odor Elimination</h4>
                                <p>Ozone treatment for strong odors</p>
                            </div>
                            <div class="addon-price">+$35</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Profile Settings</h1>
                    <p class="page-subtitle">Manage your account information and preferences.</p>
                </div>

                <!-- Personal Information -->
                <div class="section-card">
                    <h3><i class="fas fa-user"></i> Personal Information</h3>
                    <form id="personalInfoForm" class="profile-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" value="John" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" value="Smith" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="john.smith@email.com" class="form-input">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="+1 (555) 123-4567" class="form-input">
                            </div>
                            <div class="form-group full-width">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-textarea" rows="3">123 Main Street, Apartment 4B, City, State 12345</textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Vehicles -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-car"></i> My Vehicles</h3>
                        <button class="btn-secondary" onclick="showAddVehicleModal()">
                            <i class="fas fa-plus"></i> Add Vehicle
                        </button>
                    </div>
                    <div class="vehicles-grid">
                        <div class="vehicle-card">
                            <div class="vehicle-image">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="vehicle-info">
                                <h4>2022 Toyota Camry</h4>
                                <p><strong>License Plate:</strong> ABC123</p>
                                <p><strong>Color:</strong> Silver</p>
                                <p><strong>Last Service:</strong> Sep 28, 2025</p>
                            </div>
                            <div class="vehicle-actions">
                                <button class="btn-secondary" onclick="editVehicle(1)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-danger" onclick="deleteVehicle(1)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                        <div class="vehicle-card">
                            <div class="vehicle-image">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="vehicle-info">
                                <h4>2020 Honda Civic</h4>
                                <p><strong>License Plate:</strong> XYZ789</p>
                                <p><strong>Color:</strong> Blue</p>
                                <p><strong>Last Service:</strong> Never</p>
                            </div>
                            <div class="vehicle-actions">
                                <button class="btn-secondary" onclick="editVehicle(2)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-danger" onclick="deleteVehicle(2)">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preferences -->
                <div class="section-card">
                    <h3><i class="fas fa-cog"></i> Preferences</h3>
                    <form id="preferencesForm" class="profile-form">
                        <div class="preference-group">
                            <h4>Notifications</h4>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" checked> Email notifications for booking confirmations
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" checked> SMS reminders 24 hours before service
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox"> Promotional offers and deals
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" checked> Service completion notifications
                                </label>
                            </div>
                        </div>

                        <div class="preference-group">
                            <h4>Service Preferences</h4>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="preferredTime">Preferred Service Time</label>
                                    <select id="preferredTime" class="form-select">
                                        <option value="morning">Morning (8 AM - 12 PM)</option>
                                        <option value="afternoon" selected>Afternoon (12 PM - 5 PM)</option>
                                        <option value="evening">Evening (5 PM - 8 PM)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="serviceType">Preferred Service Type</label>
                                    <select id="serviceType" class="form-select">
                                        <option value="mobile" selected>Mobile Service</option>
                                        <option value="shop">In-Shop Service</option>
                                        <option value="both">No Preference</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="specialInstructions">Special Instructions / Notes</label>
                                <textarea id="specialInstructions" class="form-textarea" rows="3" placeholder="Any special instructions for our technicians..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                    </form>
                </div>

                <!-- Account Security -->
                <div class="section-card">
                    <h3><i class="fas fa-shield-alt"></i> Account Security</h3>
                    <form id="securityForm" class="profile-form">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" id="currentPassword" name="currentPassword" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" name="newPassword" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-input">
                        </div>
                        <button type="submit" class="btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>

                    <div class="security-options">
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Two-Factor Authentication</h4>
                                <p>Add an extra layer of security to your account</p>
                            </div>
                            <button class="btn-secondary">Enable 2FA</button>
                        </div>
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Login Sessions</h4>
                                <p>Manage your active login sessions</p>
                            </div>
                            <button class="btn-secondary">View Sessions</button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Support Section -->
            <section id="support" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Support & Help</h1>
                    <p class="page-subtitle">Get assistance and find answers to your questions.</p>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions-grid">
                    <div class="quick-action-card">
                        <div class="action-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Live Chat</h3>
                        <p>Chat with our support team</p>
                        <button class="btn-primary" onclick="startLiveChat()">Start Chat</button>
                    </div>
                    <div class="quick-action-card">
                        <div class="action-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Call Support</h3>
                        <p>Speak directly with a representative</p>
                        <button class="btn-primary" onclick="callSupport()">Call Now</button>
                    </div>
                    <div class="quick-action-card">
                        <div class="action-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Support</h3>
                        <p>Send us a detailed message</p>
                        <button class="btn-primary" onclick="showEmailForm()">Send Email</button>
                    </div>
                    <div class="quick-action-card">
                        <div class="action-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3>Report Issue</h3>
                        <p>Report a problem with your service</p>
                        <button class="btn-warning" onclick="showReportForm()">Report Issue</button>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="section-card">
                    <h3><i class="fas fa-question-circle"></i> Frequently Asked Questions</h3>
                    <div class="faq-list">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>How do I schedule a car detailing appointment?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>You can schedule an appointment by going to the "Book a Service" section, selecting your desired service, choosing a date and time, and providing your vehicle details. You'll receive a confirmation email once your booking is confirmed.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>What's included in the Complete Detail Package?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Our Complete Detail Package includes everything from our Interior and Exterior Detail services, plus engine bay cleaning and headlight restoration. It's our most comprehensive service that will make your car look like new.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>Do you offer mobile services?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Yes! We offer mobile services where we come to your location. There's a $25 mobile service fee. We serve within a 25-mile radius of our main location. Mobile service is available for most of our services.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>How can I cancel or reschedule my appointment?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>You can cancel or reschedule your appointment up to 24 hours before your scheduled time through your dashboard or by calling our support line. Cancellations made less than 24 hours in advance may be subject to a fee.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>What payment methods do you accept?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>We accept all major credit cards (Visa, MasterCard, American Express), debit cards, cash, and digital payments (Apple Pay, Google Pay). Payment is due upon completion of service.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <h4>How long does each service take?</h4>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer">
                                <p>Service times vary: Basic Wash & Wax (1 hour), Interior Detail (2 hours), Exterior Detail (3 hours), Complete Package (4 hours), Paint Correction (6 hours), and Ceramic Coating (2 days). Actual time may vary based on vehicle condition.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="section-card">
                    <h3><i class="fas fa-info-circle"></i> Contact Information</h3>
                    <div class="contact-grid">
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-info">
                                <h4>Our Location</h4>
                                <p>123 Main Street<br>City, State 12345</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-info">
                                <h4>Phone Support</h4>
                                <p>+1 (555) 123-4567<br>Available 8 AM - 6 PM</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info">
                                <h4>Email Support</h4>
                                <p>support@cardetailing.com<br>Response within 24 hours</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-info">
                                <h4>Business Hours</h4>
                                <p>Mon-Fri: 8 AM - 6 PM<br>Sat-Sun: 9 AM - 5 PM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Ticket -->
                <div class="section-card">
                    <h3><i class="fas fa-ticket-alt"></i> Submit Support Ticket</h3>
                    <form id="supportTicketForm" class="support-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="ticketSubject">Subject</label>
                                <input type="text" id="ticketSubject" name="subject" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="ticketCategory">Category</label>
                                <select id="ticketCategory" name="category" class="form-select" required>
                                    <option value="">Select a category</option>
                                    <option value="booking">Booking Issues</option>
                                    <option value="payment">Payment Problems</option>
                                    <option value="service">Service Quality</option>
                                    <option value="technical">Technical Issues</option>
                                    <option value="billing">Billing Questions</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ticketPriority">Priority</label>
                                <select id="ticketPriority" name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ticketBooking">Related Booking (if applicable)</label>
                                <select id="ticketBooking" name="booking" class="form-select">
                                    <option value="">Select a booking</option>
                                    <option value="1">Complete Detail - Oct 15, 2025</option>
                                    <option value="2">Basic Wash - Oct 20, 2025</option>
                                    <option value="3">Interior Detail - Sep 28, 2025</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label for="ticketDescription">Description</label>
                                <textarea id="ticketDescription" name="description" class="form-textarea" rows="5" placeholder="Please describe your issue in detail..." required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Submit Ticket
                        </button>
                    </form>
                </div>
            </section>

            <!-- Finances Section -->
            <section id="finances" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Finances</h1>
                    <p class="page-subtitle">Track your payments and financial history</p>
                </div>
                <div class="section-card">
                    <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    <p>Your payment history and financial details will appear here.</p>
                </div>
            </section>

            <!-- Reviews Section -->
            <section id="reviews" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Reviews</h1>
                    <p class="page-subtitle">Your feedback and service reviews</p>
                </div>
                <div class="section-card">
                    <h3><i class="fas fa-star"></i> Service Reviews</h3>
                    <p>Your reviews and ratings will appear here.</p>
                </div>
            </section>

            <!-- Notifications Section -->
            <section id="notifications" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Notifications</h1>
                    <p class="page-subtitle">Stay updated with important alerts</p>
                </div>
                <div class="section-card">
                    <h3><i class="fas fa-bell"></i> Recent Notifications</h3>
                    <p>Your notifications will appear here.</p>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Settings</h1>
                    <p class="page-subtitle">Manage your account preferences</p>
                </div>
                <div class="section-card">
                    <h3><i class="fas fa-cog"></i> Account Settings</h3>
                    <p>Your account settings and preferences will appear here.</p>
                </div>
            </section>
        </main>
    </div>

    <!-- SIMPLE TEST SCRIPT -->
    <script>
        function showSection(sectionId) {
            alert('showSection called: ' + sectionId);
            
            var sections = document.getElementsByClassName('content-section');
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = 'none';
            }
            
            var target = document.getElementById(sectionId);
            if (target) {
                target.style.display = 'block';
            }
        }
    </script>
</body>
</html>
            if (targetSection) {
                targetSection.style.display = 'block';
                targetSection.classList.add('active');
            }
            
            // Update navigation links
            var navLinks = document.getElementsByClassName('nav-link');
            for (var i = 0; i < navLinks.length; i++) {
                navLinks[i].classList.remove('active');
            }
        }

        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('mobile-open')) {
                sidebar.classList.remove('mobile-open');
            } else {
                sidebar.classList.add('mobile-open');
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

        // Booking Management Functions
        function filterBookings(status) {
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const bookings = document.querySelectorAll('.booking-item');
            bookings.forEach(booking => {
                if (status === 'all' || booking.classList.contains(status)) {
                    booking.style.display = 'block';
                } else {
                    booking.style.display = 'none';
                }
            });
        }

        function showNewBookingModal() {
            showNotification('Redirecting to service booking...', 'info');
            setTimeout(() => showSection('services'), 1000);
        }

        function viewBookingDetails(bookingId) {
            showNotification(`Loading details for booking #${bookingId}...`, 'info');
            // In real implementation, this would open a modal or navigate to details page
        }

        function rescheduleBooking(bookingId) {
            if (confirm('Would you like to reschedule this appointment?')) {
                showNotification('Reschedule request submitted. We will contact you shortly.', 'success');
            }
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
                showNotification('Booking has been cancelled. Any applicable refunds will be processed within 3-5 business days.', 'warning');
                // Hide the booking item
                event.target.closest('.booking-item').style.opacity = '0.5';
            }
        }

        function rebookService(bookingId) {
            showNotification('Redirecting to service booking with previous details...', 'info');
            setTimeout(() => showSection('services'), 1000);
        }

        function leaveReview(bookingId) {
            showNotification('Review form will open in a new window...', 'info');
            // In real implementation, this would open a review modal
        }

        // Service Functions
        function filterServices(category) {
            const buttons = document.querySelectorAll('.category-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            const services = document.querySelectorAll('.service-card');
            services.forEach(service => {
                if (category === 'all' || service.dataset.category === category) {
                    service.style.display = 'block';
                } else {
                    service.style.display = 'none';
                }
            });
        }

        function bookService(serviceType, price, duration) {
            const serviceName = event.target.closest('.service-card').querySelector('h3').textContent;
            showNotification(`Booking ${serviceName} for $${price}. Please select date and time...`, 'info');
            
            // Simulate booking process
            setTimeout(() => {
                showNotification('Booking request submitted! We will contact you within 1 hour to confirm.', 'success');
            }, 2000);
        }

        // Profile Functions
        function showAddVehicleModal() {
            showNotification('Add vehicle form will open...', 'info');
            // In real implementation, this would open a modal
        }

        function editVehicle(vehicleId) {
            showNotification(`Edit vehicle #${vehicleId} form will open...`, 'info');
        }

        function deleteVehicle(vehicleId) {
            if (confirm('Are you sure you want to remove this vehicle?')) {
                showNotification('Vehicle removed successfully.', 'success');
                event.target.closest('.vehicle-card').style.opacity = '0.5';
            }
        }

        // Form Submissions
        document.addEventListener('DOMContentLoaded', function() {
            // Personal Info Form
            const personalForm = document.getElementById('personalInfoForm');
            if (personalForm) {
                personalForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showNotification('Personal information updated successfully!', 'success');
                });
            }

            // Preferences Form
            const preferencesForm = document.getElementById('preferencesForm');
            if (preferencesForm) {
                preferencesForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showNotification('Preferences saved successfully!', 'success');
                });
            }

            // Security Form
            const securityForm = document.getElementById('securityForm');
            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const currentPassword = document.getElementById('currentPassword').value;
                    const newPassword = document.getElementById('newPassword').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (!currentPassword || !newPassword || !confirmPassword) {
                        showNotification('Please fill in all password fields.', 'error');
                        return;
                    }

                    if (newPassword !== confirmPassword) {
                        showNotification('New passwords do not match.', 'error');
                        return;
                    }

                    if (newPassword.length < 8) {
                        showNotification('Password must be at least 8 characters long.', 'error');
                        return;
                    }

                    showNotification('Password changed successfully!', 'success');
                    securityForm.reset();
                });
            }

            // Support Ticket Form
            const supportForm = document.getElementById('supportTicketForm');
            if (supportForm) {
                supportForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    showNotification('Support ticket submitted successfully! We will respond within 24 hours.', 'success');
                    supportForm.reset();
                });
            }
        });

        // Support Functions
        function startLiveChat() {
            showNotification('Connecting to live chat support...', 'info');
            setTimeout(() => {
                showNotification('Live chat is currently offline. Please try email support or call us directly.', 'warning');
            }, 2000);
        }

        function callSupport() {
            showNotification('Calling support at +1 (555) 123-4567...', 'info');
            // In real implementation, this could open a phone app or display a modal
        }

        function showEmailForm() {
            showNotification('Opening email support form...', 'info');
            document.getElementById('supportTicketForm').scrollIntoView({ behavior: 'smooth' });
        }

        function showReportForm() {
            showNotification('Opening issue report form...', 'info');
            const categorySelect = document.getElementById('ticketCategory');
            categorySelect.value = 'service';
            const prioritySelect = document.getElementById('ticketPriority');
            prioritySelect.value = 'high';
            document.getElementById('supportTicketForm').scrollIntoView({ behavior: 'smooth' });
        }

        function toggleFAQ(element) {
            const faqItem = element.closest('.faq-item');
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't already active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        }

        // Notification System (matching admin dashboard)
        function showNotification(message, type = 'info', duration = 4000) {
            // Create notification container if it doesn't exist
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-width: 400px;
                `;
                document.body.appendChild(container);
            }

            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                padding: 16px 20px;
                border-radius: 8px;
                color: #fff;
                font-weight: 500;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                animation: slideInRight 0.3s ease-out;
                position: relative;
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                max-width: 400px;
                word-wrap: break-word;
            `;

            // Set background color based on type
            switch(type) {
                case 'success':
                    notification.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    notification.style.borderLeft = '4px solid #047857';
                    break;
                case 'error':
                    notification.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                    notification.style.borderLeft = '4px solid #b91c1c';
                    break;
                case 'warning':
                    notification.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                    notification.style.borderLeft = '4px solid #b45309';
                    break;
                case 'info':
                default:
                    notification.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
                    notification.style.borderLeft = '4px solid #1d4ed8';
                    break;
            }

            // Get appropriate icon
            let icon = '';
            switch(type) {
                case 'success': icon = 'fas fa-check-circle'; break;
                case 'error': icon = 'fas fa-exclamation-circle'; break;
                case 'warning': icon = 'fas fa-exclamation-triangle'; break;
                case 'info': default: icon = 'fas fa-info-circle'; break;
            }

            notification.innerHTML = `
                <i class="${icon}" style="font-size: 18px; flex-shrink: 0;"></i>
                <span style="flex: 1;">${message}</span>
                <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #fff; cursor: pointer; font-size: 16px; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s; flex-shrink: 0;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.2)'" onmouseout="this.style.backgroundColor='transparent'">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Add to container
            container.appendChild(notification);

            // Auto-remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
                        setTimeout(() => {
                            if (notification.parentElement) {
                                notification.remove();
                            }
                        }, 300);
                    }
                }, duration);
            }
        }

        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Show welcome notification on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const hour = new Date().getHours();
                let greeting;
                if (hour < 12) greeting = 'Good morning!';
                else if (hour < 18) greeting = 'Good afternoon!';
                else greeting = 'Good evening!';
                
                showNotification(`${greeting} Welcome back to your Car Detailing dashboard.`, 'info');
            }, 1000);
        });

        // Car Information Modal Functions
        function openCarInfoModal() {
            document.getElementById('carInfoModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCarInfoModal() {
            document.getElementById('carInfoModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function updateModels() {
            const brand = document.getElementById('carBrand').value;
            const modelSelect = document.getElementById('carModel');
            
            // Clear existing options
            modelSelect.innerHTML = '<option value="">Select Model</option>';
            
            // Car models database
            const carModels = {
                'Toyota': ['Camry', 'Corolla', 'Prius', 'RAV4', 'Highlander', 'Sienna', 'Tacoma', 'Tundra'],
                'Honda': ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey', 'Ridgeline', 'Passport', 'HR-V'],
                'Ford': ['F-150', 'Mustang', 'Explorer', 'Escape', 'Fusion', 'Edge', 'Expedition', 'Ranger'],
                'Chevrolet': ['Silverado', 'Malibu', 'Equinox', 'Traverse', 'Tahoe', 'Suburban', 'Camaro', 'Corvette'],
                'BMW': ['3 Series', '5 Series', '7 Series', 'X3', 'X5', 'X7', 'Z4', 'i3'],
                'Mercedes-Benz': ['C-Class', 'E-Class', 'S-Class', 'GLC', 'GLE', 'GLS', 'A-Class', 'CLA'],
                'Audi': ['A3', 'A4', 'A6', 'A8', 'Q3', 'Q5', 'Q7', 'Q8'],
                'Nissan': ['Altima', 'Sentra', 'Maxima', 'Rogue', 'Pathfinder', 'Armada', 'Titan', '370Z'],
                'Hyundai': ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Palisade', 'Genesis', 'Veloster', 'Kona'],
                'Kia': ['Forte', 'Optima', 'Sportage', 'Sorento', 'Telluride', 'Stinger', 'Soul', 'Niro'],
                'Subaru': ['Impreza', 'Legacy', 'Outback', 'Forester', 'Ascent', 'WRX', 'BRZ', 'Crosstrek'],
                'Mazda': ['Mazda3', 'Mazda6', 'CX-3', 'CX-5', 'CX-9', 'MX-5 Miata', 'CX-30', 'CX-50'],
                'Volkswagen': ['Jetta', 'Passat', 'Golf', 'Tiguan', 'Atlas', 'Arteon', 'ID.4', 'Beetle'],
                'Lexus': ['ES', 'IS', 'LS', 'RX', 'GX', 'LX', 'NX', 'LC'],
                'Infiniti': ['Q50', 'Q60', 'QX50', 'QX60', 'QX80', 'Q70', 'QX30', 'Q30'],
                'Acura': ['ILX', 'TLX', 'RLX', 'RDX', 'MDX', 'NSX', 'Integra', 'TL'],
                'Cadillac': ['ATS', 'CTS', 'XTS', 'XT4', 'XT5', 'XT6', 'Escalade', 'CT6'],
                'Lincoln': ['MKC', 'MKX', 'MKZ', 'Navigator', 'Continental', 'Corsair', 'Nautilus', 'Aviator'],
                'Jeep': ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass', 'Renegade', 'Gladiator', 'Grand Wagoneer', 'Wagoneer'],
                'Ram': ['1500', '2500', '3500', 'ProMaster', 'ProMaster City'],
                'GMC': ['Sierra', 'Canyon', 'Acadia', 'Terrain', 'Yukon', 'Savana'],
                'Buick': ['Encore', 'Envision', 'Enclave', 'Regal', 'LaCrosse'],
                'Chrysler': ['300', 'Pacifica', 'Voyager'],
                'Dodge': ['Charger', 'Challenger', 'Durango', 'Journey', 'Grand Caravan'],
                'Tesla': ['Model S', 'Model 3', 'Model X', 'Model Y', 'Cybertruck', 'Roadster'],
                'Porsche': ['911', 'Cayenne', 'Macan', 'Panamera', 'Taycan', 'Boxster', 'Cayman'],
                'Jaguar': ['XE', 'XF', 'XJ', 'F-PACE', 'E-PACE', 'I-PACE', 'F-Type'],
                'Land Rover': ['Range Rover', 'Range Rover Sport', 'Range Rover Evoque', 'Discovery', 'Discovery Sport', 'Defender'],
                'Volvo': ['S60', 'S90', 'XC40', 'XC60', 'XC90', 'V60', 'V90'],
                'Genesis': ['G70', 'G80', 'G90', 'GV70', 'GV80'],
                'Alfa Romeo': ['Giulia', 'Stelvio', '4C', 'Giulietta'],
                'Maserati': ['Ghibli', 'Quattroporte', 'Levante', 'GranTurismo'],
                'Bentley': ['Continental', 'Flying Spur', 'Bentayga', 'Mulsanne'],
                'Rolls-Royce': ['Ghost', 'Phantom', 'Wraith', 'Dawn', 'Cullinan'],
                'Ferrari': ['488', '812', 'Portofino', 'Roma', 'SF90', 'F8'],
                'Lamborghini': ['Huracán', 'Aventador', 'Urus'],
                'McLaren': ['570S', '720S', '765LT', 'GT', 'Artura'],
                'Aston Martin': ['DB11', 'Vantage', 'DBS', 'DBX']
            };
            
            if (brand && carModels[brand]) {
                carModels[brand].forEach(model => {
                    const option = document.createElement('option');
                    option.value = model;
                    option.textContent = model;
                    modelSelect.appendChild(option);
                });
            }
        }

        function calculatePrice() {
            const year = document.getElementById('carYear').value;
            const brand = document.getElementById('carBrand').value;
            const model = document.getElementById('carModel').value;
            const bodyType = document.getElementById('carBodyType').value;
            const color = document.getElementById('carColor').value;

            if (!year || !brand || !model || !bodyType || !color) {
                showNotification('Please fill in all car information fields.', 'warning');
                return;
            }

            // Basic pricing calculation
            let basePrice = 150; // Base price for detailing
            
            // Year adjustment
            const carAge = new Date().getFullYear() - parseInt(year);
            if (carAge <= 2) basePrice += 50; // Newer cars cost more
            else if (carAge <= 5) basePrice += 25;
            else if (carAge > 15) basePrice -= 25; // Older cars cost less
            
            // Body type adjustment
            const bodyTypePricing = {
                'Sedan': 0,
                'SUV': 50,
                'Truck': 75,
                'Coupe': -25,
                'Convertible': 25,
                'Hatchback': -15,
                'Wagon': 15,
                'Van': 100,
                'Motorcycle': -100
            };
            basePrice += bodyTypePricing[bodyType] || 0;
            
            // Premium brand adjustment
            const premiumBrands = ['BMW', 'Mercedes-Benz', 'Audi', 'Lexus', 'Porsche', 'Jaguar', 'Land Rover', 'Tesla', 'Genesis', 'Bentley', 'Rolls-Royce', 'Ferrari', 'Lamborghini', 'McLaren', 'Aston Martin'];
            if (premiumBrands.includes(brand)) {
                basePrice += 75;
            }

            // Show price and redirect to services
            showNotification(`Estimated price for your ${year} ${brand} ${model}: ₱${basePrice.toLocaleString()}`, 'success');
            
            setTimeout(() => {
                closeCarInfoModal();
                showSection('services');
            }, 2000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const carModal = document.getElementById('carInfoModal');
            const bookingModal = document.getElementById('bookingModal');
            if (event.target === carModal) {
                closeCarInfoModal();
            }
            if (event.target === bookingModal) {
                closeBookingModal();
            }
        }

        // Booking Modal Functions
        let currentServiceData = {};

        function bookService(serviceType, price, duration) {
            currentServiceData = {
                type: serviceType,
                price: price,
                duration: duration
            };

            // Format service name
            const serviceName = serviceType.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            const durationHours = Math.floor(duration / 60);
            const durationMinutes = duration % 60;
            const durationText = durationHours > 0 ? 
                (durationMinutes > 0 ? `${durationHours}h ${durationMinutes}m` : `${durationHours}h`) : 
                `${durationMinutes}m`;

            // Update modal content
            document.getElementById('serviceTitle').textContent = serviceName;
            document.getElementById('summaryService').textContent = serviceName;
            document.getElementById('summaryDuration').textContent = `~${durationText}`;
            document.getElementById('summaryPrice').textContent = `₱${price}`;

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('bookingDate').min = today;

            openBookingModal();
        }

        function openBookingModal() {
            document.getElementById('bookingModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.getElementById('bookingForm').reset();
        }

        function updateBookingModels() {
            const brand = document.getElementById('bookingBrand').value;
            const modelSelect = document.getElementById('bookingModel');
            
            // Clear existing options
            modelSelect.innerHTML = '<option value="">Select Model</option>';
            
            // Car models database (simplified version)
            const carModels = {
                'Toyota': ['Camry', 'Corolla', 'Prius', 'RAV4', 'Highlander'],
                'Honda': ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey'],
                'Ford': ['F-150', 'Mustang', 'Explorer', 'Escape', 'Fusion'],
                'Chevrolet': ['Silverado', 'Malibu', 'Equinox', 'Traverse', 'Camaro'],
                'BMW': ['3 Series', '5 Series', 'X3', 'X5', 'Z4'],
                'Mercedes-Benz': ['C-Class', 'E-Class', 'GLC', 'GLE', 'A-Class'],
                'Audi': ['A3', 'A4', 'Q3', 'Q5', 'Q7'],
                'Nissan': ['Altima', 'Sentra', 'Rogue', 'Pathfinder', '370Z'],
                'Hyundai': ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Genesis'],
                'Kia': ['Forte', 'Optima', 'Sportage', 'Sorento', 'Stinger']
            };
            
            if (brand && carModels[brand]) {
                carModels[brand].forEach(model => {
                    const option = document.createElement('option');
                    option.value = model;
                    option.textContent = model;
                    modelSelect.appendChild(option);
                });
            }
        }

        // Handle booking form submission
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                service_type: currentServiceData.type,
                service_package: document.getElementById('serviceTitle').textContent,
                vehicle_year: document.getElementById('bookingYear').value,
                vehicle_brand: document.getElementById('bookingBrand').value,
                vehicle_model: document.getElementById('bookingModel').value,
                vehicle_color: document.getElementById('bookingColor').value,
                service_date: document.getElementById('bookingDate').value,
                service_time: document.getElementById('bookingTime').value,
                location: document.getElementById('bookingLocation').value,
                total_amount: currentServiceData.price
            };

            // Validate all fields
            for (const [key, value] of Object.entries(formData)) {
                if (!value) {
                    showNotification(`Please fill in all required fields.`, 'warning');
                    return;
                }
            }

            try {
                const response = await fetch('book_service.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Booking created successfully! Your booking is pending admin confirmation.', 'success');
                    closeBookingModal();
                    
                    // Refresh the page to show updated bookings
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification(result.message || 'Failed to create booking.', 'error');
                }
            } catch (error) {
                console.error('Booking error:', error);
                showNotification('An error occurred while creating your booking.', 'error');
            }
        });
    </script>

    <!-- Car Information Modal -->
    <div id="carInfoModal" class="modal">
        <div class="modal-content car-info-modal">
            <div class="modal-header">
                <h2>Get a quick and easy price</h2>
                <p>Enter your car info, to get an exact price</p>
                <button class="modal-close" onclick="closeCarInfoModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form class="car-info-form">
                    <div class="form-group">
                        <select id="carYear" class="form-control car-select">
                            <option value="">Select Year</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= 1990; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <select id="carBrand" class="form-control car-select" onchange="updateModels()">
                            <option value="">Select Brand</option>
                            <option value="Toyota">Toyota</option>
                            <option value="Honda">Honda</option>
                            <option value="Ford">Ford</option>
                            <option value="Chevrolet">Chevrolet</option>
                            <option value="BMW">BMW</option>
                            <option value="Mercedes-Benz">Mercedes-Benz</option>
                            <option value="Audi">Audi</option>
                            <option value="Nissan">Nissan</option>
                            <option value="Hyundai">Hyundai</option>
                            <option value="Kia">Kia</option>
                            <option value="Subaru">Subaru</option>
                            <option value="Mazda">Mazda</option>
                            <option value="Volkswagen">Volkswagen</option>
                            <option value="Lexus">Lexus</option>
                            <option value="Infiniti">Infiniti</option>
                            <option value="Acura">Acura</option>
                            <option value="Cadillac">Cadillac</option>
                            <option value="Lincoln">Lincoln</option>
                            <option value="Jeep">Jeep</option>
                            <option value="Ram">Ram</option>
                            <option value="GMC">GMC</option>
                            <option value="Buick">Buick</option>
                            <option value="Chrysler">Chrysler</option>
                            <option value="Dodge">Dodge</option>
                            <option value="Tesla">Tesla</option>
                            <option value="Porsche">Porsche</option>
                            <option value="Jaguar">Jaguar</option>
                            <option value="Land Rover">Land Rover</option>
                            <option value="Volvo">Volvo</option>
                            <option value="Genesis">Genesis</option>
                            <option value="Alfa Romeo">Alfa Romeo</option>
                            <option value="Maserati">Maserati</option>
                            <option value="Bentley">Bentley</option>
                            <option value="Rolls-Royce">Rolls-Royce</option>
                            <option value="Ferrari">Ferrari</option>
                            <option value="Lamborghini">Lamborghini</option>
                            <option value="McLaren">McLaren</option>
                            <option value="Aston Martin">Aston Martin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <select id="carModel" class="form-control car-select">
                            <option value="">Select Model</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <select id="carBodyType" class="form-control car-select">
                            <option value="">Select Body Type</option>
                            <option value="Sedan">Sedan</option>
                            <option value="SUV">SUV</option>
                            <option value="Truck">Truck</option>
                            <option value="Coupe">Coupe</option>
                            <option value="Convertible">Convertible</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="Wagon">Wagon</option>
                            <option value="Van">Van</option>
                            <option value="Motorcycle">Motorcycle</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <select id="carColor" class="form-control car-select">
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
                            <option value="Purple">Purple</option>
                            <option value="Brown">Brown</option>
                            <option value="Gold">Gold</option>
                            <option value="Beige">Beige</option>
                            <option value="Pink">Pink</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <button type="button" class="btn btn-primary btn-lg price-btn" onclick="calculatePrice()">
                        See my price
                    </button>

                    <div class="cant-find">
                        <a href="#" onclick="showNotification('Please contact support for assistance with your specific vehicle.', 'info')">
                            Can't find my car
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Service Booking Modal -->
    <div id="bookingModal" class="modal">
        <div class="modal-content booking-modal">
            <div class="modal-header">
                <h2>Book Service</h2>
                <p id="serviceTitle">Complete Detail Package</p>
                <button class="modal-close" onclick="closeBookingModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="bookingForm" class="booking-form">
                    <div class="booking-step" id="step1">
                        <h3>Vehicle Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Year</label>
                                <select id="bookingYear" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php 
                                    $currentYear = date('Y');
                                    for ($year = $currentYear; $year >= 1990; $year--) {
                                        echo "<option value='$year'>$year</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Brand</label>
                                <select id="bookingBrand" class="form-control" onchange="updateBookingModels()" required>
                                    <option value="">Select Brand</option>
                                    <option value="Toyota">Toyota</option>
                                    <option value="Honda">Honda</option>
                                    <option value="Ford">Ford</option>
                                    <option value="Chevrolet">Chevrolet</option>
                                    <option value="BMW">BMW</option>
                                    <option value="Mercedes-Benz">Mercedes-Benz</option>
                                    <option value="Audi">Audi</option>
                                    <option value="Nissan">Nissan</option>
                                    <option value="Hyundai">Hyundai</option>
                                    <option value="Kia">Kia</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Model</label>
                                <select id="bookingModel" class="form-control" required>
                                    <option value="">Select Model</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Color</label>
                                <select id="bookingColor" class="form-control" required>
                                    <option value="">Select Color</option>
                                    <option value="White">White</option>
                                    <option value="Black">Black</option>
                                    <option value="Silver">Silver</option>
                                    <option value="Gray">Gray</option>
                                    <option value="Red">Red</option>
                                    <option value="Blue">Blue</option>
                                    <option value="Green">Green</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="booking-step" id="step2">
                        <h3>Service Details</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Preferred Date</label>
                                <input type="date" id="bookingDate" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Preferred Time</label>
                                <select id="bookingTime" class="form-control" required>
                                    <option value="">Select Time</option>
                                    <option value="09:00">9:00 AM</option>
                                    <option value="10:00">10:00 AM</option>
                                    <option value="11:00">11:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="13:00">1:00 PM</option>
                                    <option value="14:00">2:00 PM</option>
                                    <option value="15:00">3:00 PM</option>
                                    <option value="16:00">4:00 PM</option>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label class="form-label">Service Location</label>
                                <textarea id="bookingLocation" class="form-control" placeholder="Enter your address or preferred location..." required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="booking-summary">
                        <h3>Booking Summary</h3>
                        <div class="summary-row">
                            <span>Service:</span>
                            <span id="summaryService">Complete Detail Package</span>
                        </div>
                        <div class="summary-row">
                            <span>Duration:</span>
                            <span id="summaryDuration">~4 hours</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Price:</span>
                            <span id="summaryPrice">₱150</span>
                        </div>
                    </div>

                    <div class="booking-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeBookingModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>