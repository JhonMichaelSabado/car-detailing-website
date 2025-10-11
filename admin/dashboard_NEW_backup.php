<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get real data from database
$admin_stats = $carDB->getAdminStats();
$all_bookings = $carDB->getAllBookings(null, 20);
$pending_bookings = $carDB->getAllBookings('pending', 10);
$recent_activity = $carDB->getRecentActivity(10);
$admin_notifications = $carDB->getAdminNotifications(10);

// Extract stats for easier use
$total_users = $admin_stats['total_users'];
$active_bookings = $admin_stats['total_bookings'];
$pending_bookings_count = $admin_stats['pending_bookings'];
$completed_bookings = $admin_stats['total_bookings'] - $admin_stats['pending_bookings'];
$total_revenue = $admin_stats['total_revenue'];

// Initialize arrays for compatibility
$recent_services = $all_bookings;
$monthly_revenue_data = [];
$service_popularity = [];

try {
    // Additional monthly revenue data could be added here in the future
    if (empty($monthly_revenue_data)) {
        $monthly_revenue_data = [];
    }
    
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Sidebar - Simplified */
        .sidebar {
            width: 260px;
            background: #1a1a1a;
            border-right: 1px solid #333;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #333;
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

        /* Main Content - Simplified */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 0;
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

        /* Notification Dropdown */
        .notification-dropdown {
            position: absolute;
            top: 48px;
            right: 0;
            background: #202020;
            border: 1px solid #3a3a3a;
            border-radius: 12px;
            width: 380px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
            display: none;
            z-index: 15;
            max-height: 500px;
            overflow: hidden;
        }

        .notification-dropdown.open {
            display: block;
            animation: fadeIn 150ms ease forwards;
        }

        .notification-header {
            padding: 18px 20px;
            border-bottom: 1px solid #3a3a3a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-title {
            font-weight: 600;
            color: #FFD700;
            font-size: 16px;
        }

        .notification-actions {
            display: flex;
            gap: 12px;
        }

        .notification-action {
            font-size: 12px;
            color: #9e9e9e;
            cursor: pointer;
            transition: color 0.15s ease;
        }

        .notification-action:hover {
            color: #FFD700;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid #333;
            cursor: pointer;
            transition: background 0.15s ease;
            position: relative;
        }

        .notification-item:hover {
            background: #262626;
        }

        .notification-item.unread {
            background: rgba(255, 215, 0, 0.05);
            border-left: 3px solid #FFD700;
        }

        .notification-item.unread::before {
            content: '';
            position: absolute;
            right: 20px;
            top: 20px;
            width: 8px;
            height: 8px;
            background: #FFD700;
            border-radius: 50%;
        }

        .notification-content {
            display: flex;
            gap: 12px;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .notification-icon.user { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .notification-icon.booking { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .notification-icon.system { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .notification-icon.payment { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

        .notification-details {
            flex: 1;
            min-width: 0;
        }

        .notification-message {
            color: #e6e6e6;
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .notification-time {
            color: #9e9e9e;
            font-size: 12px;
        }

        .notification-footer {
            padding: 15px 20px;
            border-top: 1px solid #3a3a3a;
            text-align: center;
        }

        .view-all-btn {
            color: #FFD700;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: opacity 0.15s ease;
        }

        .view-all-btn:hover {
            opacity: 0.8;
        }

        .empty-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #9e9e9e;
        }

        .empty-notifications i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.3;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Admin Dropdown */
        .admin-dropdown {
            position: relative;
        }

        .admin-trigger {
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

        .admin-trigger:hover {
            background: #2f2f2f;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .admin-avatar {
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

        .admin-info {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
        }

        .admin-role {
            font-size: 10px;
            color: #9e9e9e;
            line-height: 1;
        }

        .admin-menu {
            position: absolute;
            top: 48px;
            right: 0;
            background: #202020;
            border: 1px solid #3a3a3a;
            border-radius: 10px;
            min-width: 200px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.35);
            display: none;
            z-index: 10;
        }

        .admin-menu.open {
            display: block;
            animation: fadeIn 120ms ease forwards;
        }

        .admin-menu-header {
            padding: 15px;
            border-bottom: 1px solid #3a3a3a;
            text-align: center;
        }

        .admin-menu-item {
            padding: 12px 15px;
            cursor: pointer;
            color: #e6e6e6;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.15s ease;
            border-bottom: 1px solid #333;
        }

        .admin-menu-item:hover {
            background: #2a2a2a;
        }

        .admin-menu-item:last-child {
            border-bottom: none;
            border-radius: 0 0 10px 10px;
        }

        .admin-menu-item.danger:hover {
            background: #dc3545;
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
            font-size: 24px;
            margin-bottom: 5px;
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

        /* Chart Styles */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: #2c2c2c;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #444;
        }

        .chart-container h3 {
            margin-bottom: 15px;
            color: #fff;
            font-size: 16px;
        }

        .chart-container canvas {
            max-height: 300px;
        }

        /* Users Page (per screenshot) */
        .page-breadcrumb {
            color: #9e9e9e;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .toolbar {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 16px;
        }
        .search-box {
            flex: 1;
            position: relative;
        }
        .search-box input {
            width: 100%;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #e6e6e6;
            padding: 10px 12px 10px 36px;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .search-box input:focus {
            border-color: #FFD700;
            background: #262626;
        }
        .search-box .icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #a9a9a9;
            font-size: 14px;
        }
        .btn-filter {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2a2a2a;
            color: #e6e6e6;
            border: 1px solid #3a3a3a;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            position: relative;
        }
        .btn-filter:hover { background:#2f2f2f; border-color:#FFD700; transform: translateY(-1px); }
        
        .filter-dropdown {
            position: absolute;
            top: 42px;
            right: 0;
            background: #202020;
            border: 1px solid #3a3a3a;
            border-radius: 10px;
            min-width: 180px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.35);
            display: none;
            z-index: 10;
        }
        .filter-dropdown.open { display:block; animation: fadeIn 120ms ease forwards; }
        .filter-header {
            padding: 12px 14px;
            border-bottom: 1px solid #3a3a3a;
            font-weight: 600;
            color: #FFD700;
            font-size: 13px;
        }
        .filter-option {
            padding: 10px 14px;
            cursor: pointer;
            color: #e6e6e6;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s ease;
        }
        .filter-option:hover { background: #2a2a2a; }
        .filter-option input[type="checkbox"] {
            accent-color: #FFD700;
            margin: 0;
        }

        .data-table { width:100%; border-collapse: collapse; }
        .data-table th, .data-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #3a3a3a;
        }
        .data-table th {
            background: #2a2a2a;
            color: #bbbbbb;
            font-weight: 600;
            text-align: left;
        }
        .data-table tr:hover { background: #242424; }
        .data-table .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #1f3b22; color: #7CFC9A; border: 1px solid #2e6b36; }
        .badge-warning { background: #3b371f; color: #f5e6a6; border: 1px solid #6b5e2e; }

        /* Payment dropdown pill */
        .payment-pill {
            position: relative;
            user-select: none;
        }
        .pill-trigger {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            color: #e6e6e6;
            padding: 6px 10px;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.15s ease;
        }
        .pill-trigger:hover { background:#2f2f2f; border-color:#FFD700; transform: translateY(-1px); }
        .pill-menu {
            position: absolute;
            top: 36px;
            left: 0;
            background: #202020;
            border: 1px solid #3a3a3a;
            border-radius: 10px;
            min-width: 150px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.35);
            display: none;
            z-index: 10;
        }
        .pill-menu.open { display:block; animation: fadeIn 120ms ease forwards; }
        .pill-item { padding: 10px 12px; cursor: pointer; color: #e6e6e6; display:flex; align-items:center; gap:8px; }
        .pill-item:hover { background:#2a2a2a; }
        .actions { display:flex; gap:10px; align-items:center; }
        .action-btn { color:#bfbfbf; cursor:pointer; transition: transform 0.15s ease, color 0.15s ease; }
        .action-btn:hover { color:#FFD700; transform: translateY(-1px) scale(1.05); }
        @keyframes fadeIn { from { opacity:0; transform: translateY(-4px);} to { opacity:1; transform: translateY(0);} }

        /* Summary Stats */
        .stats-summary {
            margin-bottom: 30px;
        }

        .summary-card {
            background: #2c2c2c;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #444;
        }

        .summary-card h4 {
            margin-bottom: 15px;
            color: #FFD700;
            font-size: 16px;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .summary-item .metric {
            color: #ccc;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .summary-item .value {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #555;
        }

        .no-data p {
            margin: 0;
            font-style: italic;
        }

        /* Mobile - Simplified */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 15px;
            }

            .top-header {
                padding: 12px 15px;
            }

            .header-right {
                gap: 12px;
            }

            .admin-info {
                display: none;
            }

            .admin-menu {
                right: -20px;
                min-width: 160px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: #FFD700;
                color: #000;
                border: none;
                border-radius: 4px;
                padding: 10px;
                cursor: pointer;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        /* Notification page styles */
        .notification-controls .filter-btn {
            background: #333;
            border: 1px solid #555;
            color: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .notification-controls .filter-btn:hover {
            background: #444;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .notification-controls .filter-btn.active {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
            font-weight: 600;
        }

        .action-btn-secondary {
            background: #2563eb;
            border: 1px solid #3b82f6;
            color: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .action-btn-secondary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .action-btn-danger {
            background: #dc2626;
            border: 1px solid #ef4444;
            color: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .action-btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .full-notifications-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .notification-item-page {
            padding: 20px;
            border-bottom: 1px solid #333;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .notification-item-page:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .notification-item-page.unread {
            background: rgba(255, 215, 0, 0.08);
            border-left: 4px solid #FFD700;
        }

        .notification-item-page.unread::before {
            content: '';
            position: absolute;
            top: 20px;
            right: 20px;
            width: 8px;
            height: 8px;
            background: #FFD700;
            border-radius: 50%;
        }

        .notification-header-page {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .notification-icon-page {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .notification-details-page {
            flex: 1;
        }

        .notification-title-page {
            font-weight: 600;
            margin-bottom: 5px;
            color: #fff;
        }

        .notification-message-page {
            color: #aaa;
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .notification-meta-page {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #888;
        }

        .notification-type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .notification-type-user { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .notification-type-booking { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .notification-type-payment { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .notification-type-system { background: rgba(168, 85, 247, 0.2); color: #a855f7; }

        .no-notifications-page {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .no-notifications-page i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #555;
        }

        /* Responsive design for notification page */
        @media (max-width: 768px) {
            .notification-controls {
                flex-direction: column;
                align-items: stretch !important;
            }

            .notification-filters {
                flex-wrap: wrap;
            }

            .notification-actions {
                justify-content: center;
            }

            .notification-meta-page {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        /* Reviews page styles */
        .review-controls .filter-btn {
            background: #333;
            border: 1px solid #555;
            color: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .review-controls .filter-btn:hover {
            background: #444;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .review-controls .filter-btn.active {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
            font-weight: 600;
        }

        .reviews-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .review-item {
            padding: 20px;
            border-bottom: 1px solid #333;
            transition: all 0.3s ease;
        }

        .review-item:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .review-item:last-child {
            border-bottom: none;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #FFD700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #000;
            flex-shrink: 0;
        }

        .reviewer-details h4 {
            margin: 0 0 4px 0;
            color: #fff;
            font-size: 1rem;
        }

        .reviewer-details .service-info {
            color: #888;
            font-size: 0.85rem;
        }

        .rating-stars {
            display: flex;
            gap: 2px;
        }

        .rating-stars .star {
            color: #FFD700;
            font-size: 1rem;
        }

        .rating-stars .star.empty {
            color: #555;
        }

        .review-content {
            margin: 15px 0;
        }

        .review-text {
            color: #e6e6e6;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 15px;
        }

        .review-actions {
            display: flex;
            gap: 10px;
        }

        .review-btn {
            padding: 6px 12px;
            border: 1px solid #555;
            background: #333;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .review-btn:hover {
            background: #444;
            border-color: #FFD700;
            transform: translateY(-1px);
        }

        .review-btn.reply {
            background: #2563eb;
            border-color: #3b82f6;
        }

        .review-btn.reply:hover {
            background: #1d4ed8;
            border-color: #2563eb;
        }

        .review-response {
            background: rgba(255, 215, 0, 0.1);
            border-left: 3px solid #FFD700;
            padding: 15px;
            margin-top: 15px;
            border-radius: 0 8px 8px 0;
        }

        .response-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #FFD700;
            font-weight: 600;
        }

        .response-text {
            color: #e6e6e6;
            line-height: 1.5;
        }

        .no-reviews {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .no-reviews i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #555;
        }

        /* Responsive design for reviews */
        @media (max-width: 768px) {
            .review-controls {
                flex-direction: column;
                align-items: stretch !important;
            }

            .review-filters {
                flex-wrap: wrap;
            }

            .review-search {
                width: 100% !important;
            }

            .review-search input {
                width: 100% !important;
            }

            .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .review-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* Settings Page Styles */
        .settings-tabs {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid #333;
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .settings-tab {
            background: #333;
            border: none;
            color: #e6e6e6;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            white-space: nowrap;
        }

        .settings-tab:hover {
            background: #444;
            color: #FFD700;
        }

        .settings-tab.active {
            background: #FFD700;
            color: #000;
            font-weight: 600;
        }

        .settings-content {
            display: block;
        }

        .settings-section {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        .settings-section h3 {
            color: #FFD700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .setting-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .setting-item.full-width {
            grid-column: 1 / -1;
        }

        .setting-item.checkbox-item {
            flex-direction: row;
            align-items: center;
            gap: 12px;
        }

        .setting-item label {
            color: #e6e6e6;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .setting-input, .setting-select, .setting-textarea {
            padding: 12px 15px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 8px;
            color: #e6e6e6;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .setting-input:focus, .setting-select:focus, .setting-textarea:focus {
            outline: none;
            border-color: #FFD700;
        }

        .setting-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .color-input {
            width: 60px;
            height: 40px;
            border: 1px solid #444;
            border-radius: 8px;
            background: transparent;
            cursor: pointer;
        }

        /* Operating Hours */
        .operating-hours {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .day-schedule {
            display: grid;
            grid-template-columns: 100px 1fr auto 1fr auto;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #2a2a2a;
            border-radius: 8px;
        }

        .day-label {
            font-weight: 600;
            color: #e6e6e6;
        }

        .time-input {
            padding: 8px 12px;
            background: #333;
            border: 1px solid #444;
            border-radius: 6px;
            color: #e6e6e6;
        }

        /* Notification Settings */
        .notification-settings {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .notification-item, .security-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
        }

        .notification-info, .security-info, .backup-info {
            flex: 1;
        }

        .notification-info h4, .security-info h4, .backup-info h4 {
            margin: 0 0 5px 0;
            color: #e6e6e6;
            font-size: 1rem;
        }

        .notification-info p, .security-info p, .backup-info p {
            margin: 0;
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #444;
            transition: 0.3s;
            border-radius: 30px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: #e6e6e6;
            transition: 0.3s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #FFD700;
        }

        input:checked + .slider:before {
            transform: translateX(30px);
            background-color: #000;
        }

        /* Security Settings */
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .security-settings {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Theme Settings */
        .appearance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .theme-option {
            text-align: center;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-option:hover {
            background: #333;
        }

        .theme-option.active {
            border: 2px solid #FFD700;
        }

        .theme-preview {
            width: 100%;
            height: 80px;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr 3fr;
            grid-template-rows: 20px 1fr;
        }

        .theme-header {
            grid-column: 1 / -1;
        }

        .theme-sidebar {
            grid-row: 2;
        }

        .theme-content {
            grid-row: 2;
        }

        /* Backup & Export */
        .backup-options, .export-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
        }

        .backup-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .export-item {
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .export-item h4 {
            margin: 0 0 5px 0;
            color: #e6e6e6;
        }

        .export-item p {
            margin: 0;
            color: #aaa;
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #FFD700;
            color: #000;
        }

        .btn-primary:hover {
            background: #e6c200;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #666;
            color: #e6e6e6;
        }

        .btn-secondary:hover {
            background: #777;
        }

        .btn-accent {
            background: var(--accent-color);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: all var(--transition-duration) ease;
        }

        .btn-accent:hover {
            background: #e6c200;
            transform: translateY(-1px);
        }

        /* Theme preview styles */
        .appearance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .theme-option {
            text-align: center;
            position: relative;
        }

        .theme-preview {
            width: 100%;
            height: 120px;
            border-radius: var(--border-radius);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all var(--transition-duration) ease;
            margin-bottom: 10px;
            position: relative;
            display: grid;
            grid-template-areas: 
                "header header"
                "sidebar content";
            grid-template-rows: 20px 1fr;
            grid-template-columns: 60px 1fr;
        }

        .theme-preview.active {
            border-color: var(--accent-color);
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
        }

        .theme-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .theme-header {
            grid-area: header;
        }

        .theme-sidebar {
            grid-area: sidebar;
        }

        .theme-content {
            grid-area: content;
        }

        .theme-option h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .theme-option p {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .theme-option {
            cursor: pointer;
            transition: all var(--transition-duration) ease;
        }

        .theme-option:hover {
            transform: translateY(-2px);
        }

        .theme-option:hover .theme-preview {
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.2);
        }

        .theme-option input[type="radio"] {
            margin-top: 10px;
            cursor: pointer;
        }

        .theme-option label {
            cursor: pointer;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Color input styling */
        .color-input {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
        }

        /* Notification System */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .notification {
            padding: 16px 20px;
            border-radius: var(--border-radius);
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
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
            border-left: 4px solid #047857;
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-left: 4px solid #b91c1c;
        }

        .notification.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-left: 4px solid #b45309;
        }

        .notification.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-left: 4px solid #1d4ed8;
        }

        .notification-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .notification-message {
            font-size: 14px;
            opacity: 0.95;
        }

        .notification-close {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
            flex-shrink: 0;
        }

        .notification-close:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

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

        .notification.removing {
            animation: slideOutRight 0.3s ease-in forwards;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-outline {
            background: transparent;
            color: #e6e6e6;
            border: 1px solid #666;
        }

        .btn-outline:hover {
            background: #666;
            border-color: #777;
        }

        /* Settings Footer */
        .settings-footer {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 30px 0;
            border-top: 1px solid #333;
            margin-top: 30px;
        }

        /* Responsive Settings */
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .appearance-grid {
                grid-template-columns: 1fr;
            }

            .day-schedule {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }

            .notification-item, .security-item, .backup-item, .export-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .settings-footer {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Booking Management Styles */
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .user-info i {
            color: #FFD700;
            font-size: 16px;
        }
        
        .vehicle-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .datetime-info {
            font-size: 13px;
        }
        
        .datetime-info div {
            margin-bottom: 2px;
        }
        
        .datetime-info i {
            color: #FFD700;
            margin-right: 4px;
            width: 12px;
        }
        
        .amount {
            font-weight: 600;
            color: #4CAF50;
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 4px;
        }
        
        .btn-action {
            background: none;
            border: none;
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 12px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-action.confirm {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }
        
        .btn-action.confirm:hover {
            background: #4CAF50;
            color: white;
        }
        
        .btn-action.decline {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        .btn-action.decline:hover {
            background: #f44336;
            color: white;
        }
        
        .btn-action.info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }
        
        .btn-action.info:hover {
            background: #2196F3;
            color: white;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }
        
        .status-confirmed {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }
        
        .status-completed {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }
        
        .status-cancelled {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }
        
        .filter-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        .filter-select option {
            background: #1a1a1a;
            color: white;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .empty-state i {
            font-size: 48px;
            color: #FFD700;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: white;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard">
        <!-- Notification Container -->
        <div id="notification-container" class="notification-container"></div>
        
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <i class="fas fa-car"></i> Ride Revive
                </a>
            </div>
            <div class="nav-menu">
                <a href="#" class="nav-link active" onclick="showSection('dashboard', this)">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="#" class="nav-link" onclick="showSection('users', this)">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="#" class="nav-link" onclick="showSection('finances', this)">
                    <i class="fas fa-chart-line"></i> Finances
                </a>
                <a href="#" class="nav-link" onclick="showSection('services', this)">
                    <i class="fas fa-car-wash"></i> Services
                </a>
                <a href="#" class="nav-link" onclick="showSection('bookings', this)">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="#" class="nav-link" onclick="showSection('reviews', this)" data-section="reviews">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="#" class="nav-link" onclick="showSection('notifications', this)">
                    <i class="fas fa-bell"></i> Notifications
                </a>
            </div>
            
            <!-- Bottom Nav Section -->
            <div class="nav-menu" style="position: absolute; bottom: 20px; width: calc(100% - 40px); margin: 0 20px;">
                <a href="#" class="nav-link" onclick="showSection('settings', this)">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <div class="header-left">
                    <div class="page-breadcrumb-header">Dashboard / <span id="currentPageName" style="color:#FFD700">Overview</span></div>
                </div>
                <div class="header-right">
                    <!-- Notification Button -->
                    <div class="notification-btn" onclick="toggleNotifications()" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge">5</span>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <span class="notification-title">Notifications</span>
                                <div class="notification-actions">
                                    <span class="notification-action" onclick="markAllAsRead(event)">Mark all read</span>
                                    <span class="notification-action" onclick="clearAllNotifications(event)">Clear all</span>
                                </div>
                            </div>
                            <div class="notification-list" id="notificationList">
                                <!-- Notifications will be populated by JavaScript -->
                            </div>
                            <div class="notification-footer">
                                <a href="#" class="view-all-btn" onclick="showSection('notifications', document.querySelector('[data-section=notifications]')); closeNotificationDropdown(); return false;">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Admin Dropdown -->
                    <div class="admin-dropdown">
                        <div class="admin-trigger" onclick="toggleAdminMenu()">
                            <div class="admin-avatar">JR</div>
                            <div class="admin-info">
                                <span class="admin-name">JL Robles</span>
                                <span class="admin-role">Administrator</span>
                            </div>
                            <i class="fas fa-chevron-down" style="margin-left: 5px; font-size: 10px;"></i>
                        </div>
                        <div class="admin-menu" id="adminMenu">
                            <div class="admin-menu-header">
                                <div class="admin-avatar" style="width: 40px; height: 40px; font-size: 16px; margin: 0 auto 8px;">JR</div>
                                <div style="font-weight: 600; color: #FFD700;">JL Robles</div>
                                <div style="font-size: 11px; color: #9e9e9e;">admin@riderevive.com</div>
                            </div>
                            <div class="admin-menu-item" onclick="alert('Profile settings coming soon')">
                                <i class="fas fa-user"></i>
                                <span>My Profile</span>
                            </div>
                            <div class="admin-menu-item" onclick="showSection('settings', null)">
                                <i class="fas fa-cogs"></i>
                                <span>Settings</span>
                            </div>
                            <div class="admin-menu-item" onclick="alert('Activity log coming soon')">
                                <i class="fas fa-history"></i>
                                <span>Activity Log</span>
                            </div>
                            <div class="admin-menu-item" onclick="alert('Help center coming soon')">
                                <i class="fas fa-question-circle"></i>
                                <span>Help Center</span>
                            </div>
                            <div class="admin-menu-item danger" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="page-header">
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p class="page-subtitle">Welcome back! Here's your business summary.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($total_users); ?></h3>
                                <p>Total Users</p>
                            </div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($active_bookings); ?></h3>
                                <p>Active Bookings</p>
                            </div>
                            <i class="fas fa-calendar-check stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($pending_bookings); ?></h3>
                                <p>Pending Bookings</p>
                            </div>
                            <i class="fas fa-clock stat-icon"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                            <i class="fas fa-peso-sign stat-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Summary -->
                <div class="stats-summary">
                    <div class="summary-card">
                        <h4><i class="fas fa-tachometer-alt"></i> Performance Overview</h4>
                        <div class="summary-stats">
                            <div class="summary-item">
                                <span class="metric">Success Rate</span>
                                <span class="value"><?php echo $total_users > 0 ? round(($active_bookings / max(1, $total_users)) * 100, 1) : 0; ?>%</span>
                            </div>
                            <div class="summary-item">
                                <span class="metric">Avg Revenue/User</span>
                                <span class="value">₱<?php echo $total_users > 0 ? number_format($total_revenue / $total_users, 2) : '0.00'; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="metric">Total Transactions</span>
                                <span class="value"><?php echo $active_bookings + $pending_bookings; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-line"></i> Monthly Revenue</h3>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> Booking Status</h3>
                        <canvas id="bookingChart"></canvas>
                    </div>
                </div>

                <div class="charts-section">
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-bar"></i> Popular Services</h3>
                        <canvas id="servicesChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><i class="fas fa-calendar-alt"></i> Daily Bookings (Last 7 Days)</h3>
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <?php if (!empty($recent_services)): ?>
                <div class="recent-services">
                    <h2 class="section-title">Recent Services</h2>
                    <?php foreach ($recent_services as $service): ?>
                    <div class="service-item">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['service_type'] ?? 'General Service'); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($service['username'] ?? 'Customer'); ?> • 
                                ₱<?php echo number_format($service['amount'] ?? 0, 2); ?>
                                <?php if (isset($service['date_created'])): ?>
                                    • <?php echo date('M j, Y', strtotime($service['date_created'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="service-status status-<?php echo $service['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($service['status'] ?? 'pending'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="recent-services">
                    <h2 class="section-title">Recent Services</h2>
                    <div class="no-data">
                        <i class="fas fa-info-circle"></i>
                        <p>No recent bookings found. When customers book services, they will appear here.</p>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <!-- Users Section -->
            <section id="users" class="content-section">
                <div class="page-breadcrumb">Pages / <span style="color:#FFD700">Users</span></div>
                <div class="page-header" style="margin-top:0">
                    <h1 class="page-title">Users</h1>
                    <p class="page-subtitle">Search, filter, and manage user records.</p>
                </div>

                <div class="toolbar">
                    <div class="search-box">
                        <i class="fas fa-search icon"></i>
                        <input id="userSearch" type="text" placeholder="Search users..." oninput="filterUsers()" />
                    </div>
                    <div class="btn-filter" onclick="toggleFilterDropdown()">
                        <i class="fas fa-filter"></i> Filter
                        <div class="filter-dropdown" id="filterDropdown">
                            <div class="filter-header">Filter by Payment Status</div>
                            <div class="filter-option">
                                <input type="checkbox" id="filterAll" checked onchange="applyFilters()">
                                <label for="filterAll">All Users</label>
                            </div>
                            <div class="filter-option">
                                <input type="checkbox" id="filterPaid" checked onchange="applyFilters()">
                                <label for="filterPaid">Fully Paid</label>
                            </div>
                            <div class="filter-option">
                                <input type="checkbox" id="filterPartial" checked onchange="applyFilters()">
                                <label for="filterPartial">Partial Payment</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-wrap" style="background:#1a1a1a; border:1px solid #333; border-radius:10px; overflow:hidden;">
                    <table id="usersTable" class="data-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">User ID</th>
                                <th>Name</th>
                                <th style="width:90px;">Avails</th>
                                <th>Recent Service/s</th>
                                <th style="width:160px;">Payment</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build lightweight sample from DB if available
                            $users_rows = [];
                            try {
                                $has_users = $db->query("SHOW TABLES LIKE 'users'")->rowCount();
                                if ($has_users) {
                                    // Fetch up to 12 users
                                    $uStmt = $db->query("SELECT id, COALESCE(full_name, username, CONCAT('User ', id)) AS name FROM users ORDER BY id DESC LIMIT 12");
                                    $users_rows = $uStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                                }
                            } catch (Exception $e) { $users_rows = []; }

                            if (empty($users_rows)) {
                                // Fallback placeholder rows (non-random, small set)
                                $sample_names = ['John Smith', 'Sarah Johnson', 'Mike Davis', 'Emily Brown', 'David Wilson', 'Lisa Garcia', 'Tom Anderson', 'Anna Martinez'];
                                $sample_services = ['Car Wash', 'Exterior Detailing', 'Interior Clean', 'Full Service', 'Wax & Polish', 'Engine Clean'];
                                
                                for ($i=1; $i<=8; $i++) {
                                    $name = $sample_names[$i-1] ?? 'Sample Name';
                                    $service = $sample_services[($i-1) % count($sample_services)];
                                    $payment_status = ($i % 3 == 0) ? 'Partial' : 'Fully Paid';
                                    $dot_color = ($payment_status === 'Fully Paid') ? '#88f7a2' : '#f5e6a6';
                                    
                                    echo '<tr data-name="'.$name.'" data-service="'.$service.'" data-payment="'.$payment_status.'" data-id="'.$i.'">'
                                        .'<td>'.$i.'</td>'
                                        .'<td><i class="far fa-user" style="opacity:.7;margin-right:8px;"></i>'.$name.'</td>'
                                        .'<td>'.(($i%3)+1).'</td>'
                                        .'<td>'.$service.'</td>'
                                        .'<td>'
                                            .'<div class="payment-pill">'
                                                .'<div class="pill-trigger" onclick="togglePillMenu(this)">'
                                                    .'<span class="dot" style="width:8px;height:8px;background:'.$dot_color.';border-radius:50%;display:inline-block"></span>'
                                                    .$payment_status.' <i class="fas fa-caret-down" style="opacity:.8"></i>'
                                                .'</div>'
                                                .'<div class="pill-menu">'
                                                    .'<div class="pill-item" onclick="setPayment(this,\'Fully Paid\')"><i class="fas fa-circle" style="color:#88f7a2;font-size:10px"></i> Fully Paid</div>'
                                                    .'<div class="pill-item" onclick="setPayment(this,\'Partial\')"><i class="fas fa-circle" style="color:#f5e6a6;font-size:10px"></i> Partial</div>'
                                                .'</div>'
                                            .'</div>'
                                        .'</td>'
                                        .'<td>'
                                            .'<div class="actions">'
                                                .'<span class="action-btn" title="View" onclick="alert(\'View user: '.$name.'\')"><i class="fas fa-eye"></i></span>'
                                                .'<span class="action-btn" title="Edit" onclick="alert(\'Edit user: '.$name.'\')"><i class="fas fa-pen"></i></span>'
                                                .'<span class="action-btn" title="Delete" onclick="confirmDelete(\''.$name.'\')"><i class="fas fa-trash"></i></span>'
                                            .'</div>'
                                        .'</td>'
                                    .'</tr>';
                                }
                            } else {
                                $sample_services = ['Car Wash', 'Exterior Detailing', 'Interior Clean', 'Full Service', 'Wax & Polish', 'Engine Clean'];
                                foreach ($users_rows as $index => $row) {
                                    $id = (int)$row['id'];
                                    $name = htmlspecialchars($row['name'] ?: ('User '.$id));
                                    $service = $sample_services[$index % count($sample_services)];
                                    $payment_status = ($id % 3 == 0) ? 'Partial' : 'Fully Paid';
                                    $dot_color = ($payment_status === 'Fully Paid') ? '#88f7a2' : '#f5e6a6';
                                    
                                    echo '<tr data-name="'.$name.'" data-service="'.$service.'" data-payment="'.$payment_status.'" data-id="'.$id.'">'
                                        .'<td>'.$id.'</td>'
                                        .'<td><i class="far fa-user" style="opacity:.7;margin-right:8px;"></i>'.$name.'</td>'
                                        .'<td>'.(($id%4)+1).'</td>'
                                        .'<td>'.$service.'</td>'
                                        .'<td>'
                                            .'<div class="payment-pill">'
                                                .'<div class="pill-trigger" onclick="togglePillMenu(this)">'
                                                    .'<span class="dot" style="width:8px;height:8px;background:'.$dot_color.';border-radius:50%;display:inline-block"></span>'
                                                    .$payment_status.' <i class="fas fa-caret-down" style="opacity:.8"></i>'
                                                .'</div>'
                                                .'<div class="pill-menu">'
                                                    .'<div class="pill-item" onclick="setPayment(this,\'Fully Paid\')"><i class="fas fa-circle" style="color:#88f7a2;font-size:10px"></i> Fully Paid</div>'
                                                    .'<div class="pill-item" onclick="setPayment(this,\'Partial\')"><i class="fas fa-circle" style="color:#f5e6a6;font-size:10px"></i> Partial</div>'
                                                .'</div>'
                                            .'</div>'
                                        .'</td>'
                                        .'<td>'
                                            .'<div class="actions">'
                                                .'<span class="action-btn" title="View" onclick="alert(\'View user: '.$name.'\')"><i class="fas fa-eye"></i></span>'
                                                .'<span class="action-btn" title="Edit" onclick="alert(\'Edit user: '.$name.'\')"><i class="fas fa-pen"></i></span>'
                                                .'<span class="action-btn" title="Delete" onclick="confirmDelete(\''.$name.'\')"><i class="fas fa-trash"></i></span>'
                                            .'</div>'
                                        .'</td>'
                                    .'</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Full Notifications Page -->
            <section id="notifications" class="content-section">
                <div class="page-breadcrumb">Pages / <span style="color:#FFD700">Notifications</span></div>
                <div class="page-header" style="margin-top:0">
                    <h1 class="page-title">All Notifications</h1>
                    <p class="page-subtitle">Manage and view all your notifications in one place.</p>
                </div>

                <!-- Notification Stats -->
                <div class="stats-grid" style="margin-bottom: 25px;">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="totalNotifications">0</h3>
                                <p>Total Notifications</p>
                            </div>
                            <i class="fas fa-bell stat-icon"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="unreadNotifications">0</h3>
                                <p>Unread</p>
                            </div>
                            <i class="fas fa-envelope stat-icon" style="color: #ff4757;"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="todayNotifications">0</h3>
                                <p>Today</p>
                            </div>
                            <i class="fas fa-calendar-day stat-icon" style="color: #3b82f6;"></i>
                        </div>
                    </div>
                </div>

                <!-- Notification Controls -->
                <div class="notification-controls" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
                    <div class="notification-filters" style="display: flex; gap: 10px;">
                        <button class="filter-btn active" onclick="filterNotificationsPage('all')" data-filter="all">
                            <i class="fas fa-list"></i> All
                        </button>
                        <button class="filter-btn" onclick="filterNotificationsPage('unread')" data-filter="unread">
                            <i class="fas fa-envelope"></i> Unread
                        </button>
                        <button class="filter-btn" onclick="filterNotificationsPage('user')" data-filter="user">
                            <i class="fas fa-user"></i> Users
                        </button>
                        <button class="filter-btn" onclick="filterNotificationsPage('booking')" data-filter="booking">
                            <i class="fas fa-calendar"></i> Bookings
                        </button>
                        <button class="filter-btn" onclick="filterNotificationsPage('payment')" data-filter="payment">
                            <i class="fas fa-credit-card"></i> Payments
                        </button>
                        <button class="filter-btn" onclick="filterNotificationsPage('system')" data-filter="system">
                            <i class="fas fa-cog"></i> System
                        </button>
                    </div>
                    <div style="flex: 1;"></div>
                    <div class="notification-actions" style="display: flex; gap: 10px;">
                        <button class="action-btn-secondary" onclick="markAllAsReadPage()">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                        <button class="action-btn-danger" onclick="clearAllNotificationsPage()">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </div>
                </div>

                <!-- Full Notifications List -->
                <div class="notifications-page-container" style="background: #1a1a1a; border: 1px solid #333; border-radius: 12px; overflow: hidden;">
                    <div id="fullNotificationsList" class="full-notifications-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section id="services" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Services Management</h1>
                    <p class="page-subtitle">Manage your car detailing services.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-car-wash"></i>
                    <h3>Services Management</h3>
                    <p>Service management features coming soon.</p>
                </div>
            </section>

            <!-- Bookings Section -->
            <section id="bookings" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Bookings Management</h1>
                    <p class="page-subtitle">Manage customer bookings and appointments.</p>
                </div>

                <!-- Booking Stats -->
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo count($pending_bookings); ?></h3>
                                <p>Pending Bookings</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $admin_stats['confirmed_bookings']; ?></h3>
                                <p>Confirmed Today</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $admin_stats['completed_bookings']; ?></h3>
                                <p>Completed Today</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3>$<?php echo number_format($admin_stats['today_revenue'], 2); ?></h3>
                                <p>Today's Revenue</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Bookings Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Pending Bookings</h3>
                        <div class="header-actions">
                            <button class="btn btn-primary" onclick="refreshBookings()">
                                <i class="fas fa-refresh"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($pending_bookings)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Customer</th>
                                            <th>Service</th>
                                            <th>Vehicle</th>
                                            <th>Date & Time</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_bookings as $booking): ?>
                                            <tr>
                                                <td>#<?php echo $booking['id']; ?></td>
                                                <td>
                                                    <div class="user-info">
                                                        <i class="fas fa-user-circle"></i>
                                                        <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                                <td>
                                                    <span class="vehicle-badge"><?php echo ucfirst($booking['vehicle_size']); ?></span>
                                                </td>
                                                <td>
                                                    <div class="datetime-info">
                                                        <div><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                                        <div><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($booking['booking_time'])); ?></div>
                                                    </div>
                                                </td>
                                                <td class="amount">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-action confirm" onclick="confirmBooking(<?php echo $booking['id']; ?>)" title="Confirm Booking">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button class="btn-action decline" onclick="declineBooking(<?php echo $booking['id']; ?>)" title="Decline Booking">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <button class="btn-action info" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="View Details">
                                                            <i class="fas fa-info"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-check"></i>
                                <h3>No Pending Bookings</h3>
                                <p>All bookings are up to date! New booking requests will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- All Bookings -->
                <div class="content-card" style="margin-top: 30px;">
                    <div class="card-header">
                        <h3>All Bookings</h3>
                        <div class="header-actions">
                            <select class="filter-select" onchange="filterBookings(this.value)">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_bookings as $booking): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-action info" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reviews Section -->
            <section id="reviews" class="content-section">
                <div class="page-breadcrumb">Pages / <span style="color:#FFD700">Reviews</span></div>
                <div class="page-header" style="margin-top:0">
                    <h1 class="page-title">Customer Reviews</h1>
                    <p class="page-subtitle">Manage and respond to customer feedback and reviews.</p>
                </div>

                <!-- Review Stats -->
                <div class="stats-grid" style="margin-bottom: 25px;">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="totalReviews">0</h3>
                                <p>Total Reviews</p>
                            </div>
                            <i class="fas fa-star stat-icon"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="averageRating">0.0</h3>
                                <p>Average Rating</p>
                            </div>
                            <i class="fas fa-star-half-alt stat-icon" style="color: #FFD700;"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="pendingReviews">0</h3>
                                <p>Pending Response</p>
                            </div>
                            <i class="fas fa-clock stat-icon" style="color: #ff4757;"></i>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3 id="thisMonthReviews">0</h3>
                                <p>This Month</p>
                            </div>
                            <i class="fas fa-calendar-alt stat-icon" style="color: #3b82f6;"></i>
                        </div>
                    </div>
                </div>

                <!-- Review Controls -->
                <div class="review-controls" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
                    <div class="review-filters" style="display: flex; gap: 10px;">
                        <button class="filter-btn active" onclick="filterReviews('all')" data-filter="all">
                            <i class="fas fa-list"></i> All Reviews
                        </button>
                        <button class="filter-btn" onclick="filterReviews('5')" data-filter="5">
                            <i class="fas fa-star"></i> 5 Stars
                        </button>
                        <button class="filter-btn" onclick="filterReviews('4')" data-filter="4">
                            <i class="fas fa-star"></i> 4 Stars
                        </button>
                        <button class="filter-btn" onclick="filterReviews('3')" data-filter="3">
                            <i class="fas fa-star"></i> 3 Stars
                        </button>
                        <button class="filter-btn" onclick="filterReviews('pending')" data-filter="pending">
                            <i class="fas fa-clock"></i> Pending
                        </button>
                    </div>
                    <div style="flex: 1;"></div>
                    <div class="review-search" style="position: relative;">
                        <input type="text" id="reviewSearchInput" placeholder="Search reviews..." 
                               style="padding: 8px 35px 8px 12px; border: 1px solid #555; background: #333; color: #fff; border-radius: 8px; width: 250px;"
                               onkeyup="searchReviews()">
                        <i class="fas fa-search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #888;"></i>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="reviews-container" style="background: #1a1a1a; border: 1px solid #333; border-radius: 12px; overflow: hidden;">
                    <div id="reviewsList" class="reviews-list">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            </section>

            <!-- Finances Section -->
            <section id="finances" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Financial Overview</h1>
                    <p class="page-subtitle">Track your revenue and expenses.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-chart-line"></i>
                    <h3>Financial Dashboard</h3>
                    <p>Financial analytics coming soon.</p>
                </div>
            </section>

            <!-- Transactions Section -->
            <section id="transactions" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Transaction History</h1>
                    <p class="page-subtitle">View payment transactions.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-exchange-alt"></i>
                    <h3>Transaction Management</h3>
                    <p>Transaction history coming soon.</p>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <div class="page-breadcrumb">Pages / <span style="color:#FFD700">Settings</span></div>
                <div class="page-header" style="margin-top:0">
                    <h1 class="page-title">Settings</h1>
                    <p class="page-subtitle">Manage your application settings and preferences.</p>
                </div>

                <!-- Settings Navigation Tabs -->
                <div class="settings-tabs" style="margin-bottom: 30px;">
                    <button class="settings-tab active" onclick="showSettingsTab('general')" data-tab="general">
                        <i class="fas fa-cog"></i> General
                    </button>
                    <button class="settings-tab" onclick="showSettingsTab('business')" data-tab="business">
                        <i class="fas fa-building"></i> Business
                    </button>
                    <button class="settings-tab" onclick="showSettingsTab('notifications')" data-tab="notifications">
                        <i class="fas fa-bell"></i> Notifications
                    </button>
                    <button class="settings-tab" onclick="showSettingsTab('security')" data-tab="security">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="settings-tab" onclick="showSettingsTab('appearance')" data-tab="appearance">
                        <i class="fas fa-palette"></i> Appearance
                    </button>
                    <button class="settings-tab" onclick="showSettingsTab('backup')" data-tab="backup">
                        <i class="fas fa-database"></i> Backup
                    </button>
                </div>

                <!-- General Settings Tab -->
                <!-- General Settings Tab -->
                <div class="settings-content" id="general-settings">
                    <div class="settings-section">
                        <h3><i class="fas fa-info-circle"></i> System Information</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>System Name</label>
                                <input type="text" id="appName" value="Car Detailing Pro Admin" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Version</label>
                                <input type="text" id="appVersion" value="2.1.0" class="setting-input" readonly>
                            </div>
                            <div class="setting-item">
                                <label>Administrator Name</label>
                                <input type="text" id="adminName" value="John Smith" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Contact Email</label>
                                <input type="email" id="adminEmail" value="admin@cardetailing.com" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Timezone</label>
                                <select id="timezone" class="setting-select">
                                    <option value="UTC-8">Pacific Time (UTC-8)</option>
                                    <option value="UTC-7">Mountain Time (UTC-7)</option>
                                    <option value="UTC-6">Central Time (UTC-6)</option>
                                    <option value="UTC-5" selected>Eastern Time (UTC-5)</option>
                                    <option value="UTC+0">UTC</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Language</label>
                                <select id="language" class="setting-select">
                                    <option value="en" selected>English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-cog"></i> Display Preferences</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Dashboard Items per Page</label>
                                <select id="itemsPerPage" class="setting-select">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Date Format</label>
                                <select id="dateFormat" class="setting-select">
                                    <option value="MM/DD/YYYY" selected>MM/DD/YYYY</option>
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Currency</label>
                                <select id="currency" class="setting-select">
                                    <option value="USD" selected>USD ($)</option>
                                    <option value="CAD">CAD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Refresh Rate (seconds)</label>
                                <select id="refreshRate" class="setting-select">
                                    <option value="30">30 seconds</option>
                                    <option value="60" selected>1 minute</option>
                                    <option value="300">5 minutes</option>
                                    <option value="600">10 minutes</option>
                                </select>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="showTooltips" checked> Enable Tooltips
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="autoSave" checked> Auto-save Changes
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="soundNotifications" checked> Sound Notifications
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="compactView"> Compact View Mode
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-car"></i> Car Detailing Preferences</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Default Service Duration</label>
                                <select id="defaultServiceDuration" class="setting-select">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>1 hour</option>
                                    <option value="90">1.5 hours</option>
                                    <option value="120">2 hours</option>
                                    <option value="180">3 hours</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Booking Lead Time (hours)</label>
                                <select id="bookingLeadTime" class="setting-select">
                                    <option value="2">2 hours</option>
                                    <option value="4">4 hours</option>
                                    <option value="12">12 hours</option>
                                    <option value="24" selected>24 hours</option>
                                    <option value="48">48 hours</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Weather Alert Threshold</label>
                                <select id="weatherThreshold" class="setting-select">
                                    <option value="light">Light Rain</option>
                                    <option value="moderate" selected>Moderate Rain</option>
                                    <option value="heavy">Heavy Rain</option>
                                    <option value="severe">Severe Weather</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Quality Check Level</label>
                                <select id="qualityCheckLevel" class="setting-select">
                                    <option value="basic">Basic</option>
                                    <option value="standard" selected>Standard</option>
                                    <option value="thorough">Thorough</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="autoScheduleReminders" checked> Auto-schedule Reminders
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="photoDocumentation" checked> Require Photo Documentation
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="customerFeedback" checked> Request Customer Feedback
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="inventoryTracking" checked> Track Inventory Usage
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button onclick="saveGeneralSettings()" class="btn-primary">
                            <i class="fas fa-save"></i> Save General Settings
                        </button>
                        <button onclick="resetGeneralSettings()" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                </div>

                <!-- Business Settings Tab -->
                <!-- Business Settings Tab -->
                <div class="settings-content" id="business-settings" style="display: none;">
                    <!-- Business Information -->
                    <div class="settings-section">
                        <h3><i class="fas fa-building"></i> Business Information</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Business Name</label>
                                <input type="text" id="businessName" value="Premium Car Detailing" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Phone Number</label>
                                <input type="tel" id="businessPhone" value="+1 (555) 123-4567" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Email Address</label>
                                <input type="email" id="businessEmail" value="info@cardetailing.com" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Website</label>
                                <input type="url" id="businessWebsite" value="https://cardetailing.com" class="setting-input">
                            </div>
                            <div class="setting-item full-width">
                                <label>Business Address</label>
                                <textarea id="businessAddress" class="setting-textarea" rows="3">123 Main Street, City, State 12345</textarea>
                            </div>
                            <div class="setting-item">
                                <label>License Number</label>
                                <input type="text" id="licenseNumber" value="DET-2024-001" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Tax ID / EIN</label>
                                <input type="text" id="taxId" placeholder="XX-XXXXXXX" class="setting-input">
                            </div>
                        </div>
                    </div>

                    <!-- Service Areas & Coverage -->
                    <div class="settings-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Service Areas</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Primary Service Radius (miles)</label>
                                <input type="number" id="serviceRadius" value="25" min="1" max="100" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Emergency Service Radius (miles)</label>
                                <input type="number" id="emergencyRadius" value="15" min="1" max="50" class="setting-input">
                            </div>
                            <div class="setting-item full-width">
                                <label>Service Cities/Zip Codes</label>
                                <textarea id="serviceCities" class="setting-textarea" rows="3" placeholder="Enter cities or zip codes separated by commas">Downtown, Midtown, Uptown, 12345, 12346, 12347</textarea>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="mobileService" checked> Mobile Service Available
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="shopService" checked> In-Shop Service Available
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Operating Hours -->
                    <div class="settings-section">
                        <h3><i class="fas fa-clock"></i> Operating Hours</h3>
                        <div class="operating-hours">
                            <div class="day-schedule">
                                <span class="day-label">Monday</span>
                                <input type="time" value="08:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="18:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Tuesday</span>
                                <input type="time" value="08:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="18:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Wednesday</span>
                                <input type="time" value="08:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="18:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Thursday</span>
                                <input type="time" value="08:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="18:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Friday</span>
                                <input type="time" value="08:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="18:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Saturday</span>
                                <input type="time" value="09:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="17:00" class="time-input">
                                <label><input type="checkbox" checked> Open</label>
                            </div>
                            <div class="day-schedule">
                                <span class="day-label">Sunday</span>
                                <input type="time" value="10:00" class="time-input">
                                <span>to</span>
                                <input type="time" value="16:00" class="time-input">
                                <label><input type="checkbox"> Open</label>
                            </div>
                        </div>
                        <div class="settings-grid" style="margin-top: 20px;">
                            <div class="setting-item">
                                <label>Appointment Duration (minutes)</label>
                                <select id="appointmentDuration" class="setting-select">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>60 minutes</option>
                                    <option value="90">90 minutes</option>
                                    <option value="120">2 hours</option>
                                    <option value="180">3 hours</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Max Daily Appointments</label>
                                <input type="number" id="maxAppointments" value="12" min="1" max="50" class="setting-input">
                            </div>
                        </div>
                    </div>

                    <!-- Service Pricing -->
                    <div class="settings-section">
                        <h3><i class="fas fa-dollar-sign"></i> Service Pricing</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Basic Wash & Wax</label>
                                <input type="number" id="basicWash" value="25" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Full Interior Detail</label>
                                <input type="number" id="interiorDetail" value="75" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Full Exterior Detail</label>
                                <input type="number" id="exteriorDetail" value="85" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Complete Detail Package</label>
                                <input type="number" id="completeDetail" value="150" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Paint Correction</label>
                                <input type="number" id="paintCorrection" value="200" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Ceramic Coating</label>
                                <input type="number" id="ceramicCoating" value="500" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Mobile Service Fee</label>
                                <input type="number" id="mobileServiceFee" value="15" step="0.01" class="setting-input">
                                <span class="input-suffix">$</span>
                            </div>
                            <div class="setting-item">
                                <label>Rush Service Surcharge (%)</label>
                                <input type="number" id="rushSurcharge" value="25" min="0" max="100" class="setting-input">
                                <span class="input-suffix">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment & Booking Settings -->
                    <div class="settings-section">
                        <h3><i class="fas fa-credit-card"></i> Payment & Booking</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Deposit Required (%)</label>
                                <input type="number" id="depositPercent" value="20" min="0" max="100" class="setting-input">
                                <span class="input-suffix">%</span>
                            </div>
                            <div class="setting-item">
                                <label>Cancellation Policy (hours)</label>
                                <input type="number" id="cancellationPolicy" value="24" min="1" max="168" class="setting-input">
                                <span class="input-suffix">hrs</span>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="acceptCash" checked> Accept Cash
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="acceptCard" checked> Accept Credit Cards
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="acceptDigital" checked> Accept Digital Payments
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="autoConfirm" checked> Auto-confirm Bookings
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Insurance & Liability -->
                    <div class="settings-section">
                        <h3><i class="fas fa-shield-alt"></i> Insurance & Liability</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Insurance Provider</label>
                                <input type="text" id="insuranceProvider" value="Business Insurance Co." class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Policy Number</label>
                                <input type="text" id="policyNumber" value="POL-123456789" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Coverage Amount</label>
                                <input type="text" id="coverageAmount" value="$1,000,000" class="setting-input">
                            </div>
                            <div class="setting-item">
                                <label>Policy Expiration</label>
                                <input type="date" id="policyExpiration" value="2025-12-31" class="setting-input">
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="bonded" checked> Business is Bonded
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="liability" checked> Liability Coverage
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button onclick="saveBusinessSettings()" class="btn-primary">
                            <i class="fas fa-save"></i> Save Business Settings
                        </button>
                        <button onclick="resetBusinessSettings()" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                    </div>
                </div>
                <div class="settings-content" id="notifications-settings" style="display: none;">
                    <div class="settings-section">
                        <h3><i class="fas fa-envelope"></i> Email Notifications</h3>
                        <div class="notification-settings">
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>New Booking Alerts</h4>
                                    <p>Get notified when customers make new bookings</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>Payment Confirmations</h4>
                                    <p>Receive notifications for completed payments</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>Review Notifications</h4>
                                    <p>Get alerts for new customer reviews</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>Daily Reports</h4>
                                    <p>Receive daily business summary reports</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-mobile-alt"></i> Push Notifications</h3>
                        <div class="notification-settings">
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>Browser Notifications</h4>
                                    <p>Show desktop notifications in your browser</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="notification-item">
                                <div class="notification-info">
                                    <h4>Sound Notifications</h4>
                                    <p>Play sound for important notifications</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings Tab -->
                <div class="settings-content" id="security-settings" style="display: none;">
                    <div class="settings-section">
                        <h3><i class="fas fa-key"></i> Password & Authentication</h3>
                        <div class="security-grid">
                            <div class="setting-item">
                                <label>Current Password</label>
                                <input type="password" class="setting-input" placeholder="Enter current password">
                            </div>
                            <div class="setting-item">
                                <label>New Password</label>
                                <input type="password" class="setting-input" placeholder="Enter new password">
                            </div>
                            <div class="setting-item">
                                <label>Confirm Password</label>
                                <input type="password" class="setting-input" placeholder="Confirm new password">
                            </div>
                            <div class="setting-item">
                                <button class="btn btn-primary">Update Password</button>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-shield-alt"></i> Security Options</h3>
                        <div class="security-settings">
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Two-Factor Authentication</h4>
                                    <p>Add an extra layer of security to your account</p>
                                </div>
                                <button class="btn btn-outline">Enable 2FA</button>
                            </div>
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Login Alerts</h4>
                                    <p>Get notified of new login attempts</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div class="security-item">
                                <div class="security-info">
                                    <h4>Session Timeout</h4>
                                    <p>Automatically log out after inactivity</p>
                                </div>
                                <select class="setting-select" style="width: 150px;">
                                    <option value="30">30 minutes</option>
                                    <option value="60" selected>1 hour</option>
                                    <option value="120">2 hours</option>
                                    <option value="480">8 hours</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance Settings Tab -->
                <!-- Appearance Settings Tab -->
                <div class="settings-content" id="appearance-settings" style="display: none;">
                    <div class="settings-section">
                        <h3><i class="fas fa-paint-brush"></i> Theme Settings</h3>
                        <div class="appearance-grid">
                            <div class="theme-option" onclick="selectTheme('dark')">
                                <div class="theme-preview dark-theme active">
                                    <div class="theme-header" style="background: #1a1a1a;"></div>
                                    <div class="theme-sidebar" style="background: #2a2a2a;"></div>
                                    <div class="theme-content" style="background: #1a1a1a;"></div>
                                </div>
                                <h4>Dark Theme</h4>
                                <p>Current theme</p>
                                <input type="radio" name="theme" id="dark" value="dark" checked style="margin-top: 10px;">
                                <label for="dark" style="margin-left: 5px; cursor: pointer;">Select</label>
                            </div>
                            <div class="theme-option" onclick="selectTheme('light')">
                                <div class="theme-preview light-theme">
                                    <div class="theme-header" style="background: #ffffff; border: 1px solid #ddd;"></div>
                                    <div class="theme-sidebar" style="background: #f8f9fa; border: 1px solid #ddd;"></div>
                                    <div class="theme-content" style="background: #ffffff; border: 1px solid #ddd;"></div>
                                </div>
                                <h4>Light Theme</h4>
                                <p>Classic light mode</p>
                                <input type="radio" name="theme" id="light" value="light" style="margin-top: 10px;">
                                <label for="light" style="margin-left: 5px; cursor: pointer;">Select</label>
                            </div>
                            <div class="theme-option" onclick="selectTheme('blue')">
                                <div class="theme-preview blue-theme">
                                    <div class="theme-header" style="background: #1e3a8a;"></div>
                                    <div class="theme-sidebar" style="background: #1e40af;"></div>
                                    <div class="theme-content" style="background: #1e3a8a;"></div>
                                </div>
                                <h4>Blue Theme</h4>
                                <p>Professional blue</p>
                                <input type="radio" name="theme" id="blue" value="blue" style="margin-top: 10px;">
                                <label for="blue" style="margin-left: 5px; cursor: pointer;">Select</label>
                            </div>
                            <div class="theme-option" onclick="selectTheme('green')">
                                <div class="theme-preview green-theme">
                                    <div class="theme-header" style="background: #064e3b;"></div>
                                    <div class="theme-sidebar" style="background: #059669;"></div>
                                    <div class="theme-content" style="background: #064e3b;"></div>
                                </div>
                                <h4>Green Theme</h4>
                                <p>Nature-inspired</p>
                                <input type="radio" name="theme" id="green" value="green" style="margin-top: 10px;">
                                <label for="green" style="margin-left: 5px; cursor: pointer;">Select</label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-palette"></i> Customization</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Accent Color</label>
                                <input type="color" id="accentColor" value="#FFD700" class="color-input">
                                <small>Used for highlights and buttons</small>
                            </div>
                            <div class="setting-item">
                                <label>Sidebar Width</label>
                                <select id="sidebarWidth" class="setting-select">
                                    <option value="240">Compact (240px)</option>
                                    <option value="280" selected>Default (280px)</option>
                                    <option value="320">Wide (320px)</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Font Size</label>
                                <select id="fontSize" class="setting-select">
                                    <option value="12">Small (12px)</option>
                                    <option value="14" selected>Default (14px)</option>
                                    <option value="16">Large (16px)</option>
                                    <option value="18">Extra Large (18px)</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Border Radius</label>
                                <select id="borderRadius" class="setting-select">
                                    <option value="0">Sharp (0px)</option>
                                    <option value="4">Subtle (4px)</option>
                                    <option value="8" selected>Default (8px)</option>
                                    <option value="12">Rounded (12px)</option>
                                    <option value="16">Very Rounded (16px)</option>
                                </select>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="animations" checked> Enable Animations
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="compactMode"> Compact Mode
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="reducedMotion"> Reduce Motion
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="highContrast"> High Contrast Mode
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-desktop"></i> Layout Options</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Dashboard Layout</label>
                                <select id="dashboardLayout" class="setting-select">
                                    <option value="grid" selected>Grid Layout</option>
                                    <option value="list">List Layout</option>
                                    <option value="cards">Card Layout</option>
                                    <option value="compact">Compact Layout</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Header Style</label>
                                <select id="headerStyle" class="setting-select">
                                    <option value="fixed" selected>Fixed Header</option>
                                    <option value="static">Static Header</option>
                                    <option value="floating">Floating Header</option>
                                </select>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="showBreadcrumbs" checked> Show Breadcrumbs
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="collapsibleSidebar" checked> Collapsible Sidebar
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-eye"></i> Accessibility</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label>Screen Reader Support</label>
                                <select id="screenReader" class="setting-select">
                                    <option value="auto" selected>Auto-detect</option>
                                    <option value="enabled">Always Enabled</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                            </div>
                            <div class="setting-item">
                                <label>Keyboard Navigation</label>
                                <select id="keyboardNav" class="setting-select">
                                    <option value="standard" selected>Standard</option>
                                    <option value="enhanced">Enhanced</option>
                                    <option value="vim">Vim-style</option>
                                </select>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="focusIndicators" checked> Enhanced Focus Indicators
                                </label>
                            </div>
                            <div class="setting-item checkbox-item">
                                <label>
                                    <input type="checkbox" id="skipLinks" checked> Skip Navigation Links
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button onclick="saveAppearanceSettings()" class="btn-primary">
                            <i class="fas fa-save"></i> Save Appearance Settings
                        </button>
                        <button onclick="resetAppearanceSettings()" class="btn-secondary">
                            <i class="fas fa-undo"></i> Reset to Defaults
                        </button>
                        <button onclick="previewTheme()" class="btn-accent">
                            <i class="fas fa-eye"></i> Preview Changes
                        </button>
                    </div>
                </div>

                <!-- Backup Settings Tab -->
                <div class="settings-content" id="backup-settings" style="display: none;">
                    <div class="settings-section">
                        <h3><i class="fas fa-cloud-download-alt"></i> Data Backup</h3>
                        <div class="backup-options">
                            <div class="backup-item">
                                <div class="backup-info">
                                    <h4>Automatic Backups</h4>
                                    <p>Last backup: October 6, 2025 at 2:30 AM</p>
                                </div>
                                <div class="backup-controls">
                                    <select class="setting-select">
                                        <option value="daily" selected>Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="disabled">Disabled</option>
                                    </select>
                                    <button class="btn btn-primary">Backup Now</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-download"></i> Export Data</h3>
                        <div class="export-options">
                            <div class="export-item">
                                <h4>User Data</h4>
                                <p>Export all customer information and profiles</p>
                                <button class="btn btn-outline">Export Users</button>
                            </div>
                            <div class="export-item">
                                <h4>Booking History</h4>
                                <p>Export all booking records and transactions</p>
                                <button class="btn btn-outline">Export Bookings</button>
                            </div>
                            <div class="export-item">
                                <h4>Financial Reports</h4>
                                <p>Export revenue and expense reports</p>
                                <button class="btn btn-outline">Export Finances</button>
                            </div>
                            <div class="export-item">
                                <h4>Complete Database</h4>
                                <p>Export entire database backup</p>
                                <button class="btn btn-primary">Full Export</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Settings Button -->
                <div class="settings-footer">
                    <button class="btn btn-success" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                    <button class="btn btn-secondary" onclick="resetSettings()">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                </div>
            </section>
            </div> <!-- Close content-area -->
        </main>
    </div>

    <script>
        function showSection(sectionId, linkEl) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
            // Show selected section
            const sec = document.getElementById(sectionId);
            if (sec) sec.classList.add('active');

            // Update nav links
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            if (linkEl) linkEl.classList.add('active');

            // Update breadcrumb
            const pageNames = {
                'dashboard': 'Overview',
                'users': 'Users',
                'services': 'Services', 
                'finances': 'Finances',
                'settings': 'Settings'
            };
            const breadcrumbEl = document.getElementById('currentPageName');
            if (breadcrumbEl) {
                breadcrumbEl.textContent = pageNames[sectionId] || 'Page';
            }

            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }

        // Notification System
        let notifications = [
            {
                id: 1,
                type: 'user',
                icon: 'fas fa-user-plus',
                message: 'New user registration: Sarah Johnson',
                time: '2 minutes ago',
                unread: true
            },
            {
                id: 2,
                type: 'booking',
                icon: 'fas fa-calendar-plus',
                message: 'New booking request for Car Detailing Service',
                time: '15 minutes ago',
                unread: true
            },
            {
                id: 3,
                type: 'payment',
                icon: 'fas fa-credit-card',
                message: 'Payment received: ₱1,500.00 from Mike Davis',
                time: '1 hour ago',
                unread: true
            },
            {
                id: 4,
                type: 'system',
                icon: 'fas fa-cog',
                message: 'System maintenance scheduled for tonight',
                time: '2 hours ago',
                unread: false
            },
            {
                id: 5,
                type: 'booking',
                icon: 'fas fa-check-circle',
                message: 'Booking completed: Interior Cleaning by John Smith',
                time: '3 hours ago',
                unread: true
            }
        ];

        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            if (!dropdown) return;
            
            // Close other dropdowns
            document.querySelectorAll('.pill-menu.open, .filter-dropdown.open, .admin-menu.open').forEach(m => m.classList.remove('open'));
            
            const isOpen = dropdown.classList.contains('open');
            if (isOpen) {
                dropdown.classList.remove('open');
            } else {
                dropdown.classList.add('open');
                renderNotifications();
            }
        }

        function closeNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.remove('open');
            }
        }

        function renderNotifications() {
            const listEl = document.getElementById('notificationList');
            if (!listEl) return;

            if (notifications.length === 0) {
                listEl.innerHTML = `
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <div>No notifications</div>
                    </div>
                `;
                return;
            }

            listEl.innerHTML = notifications.map(notif => `
                <div class="notification-item ${notif.unread ? 'unread' : ''}" onclick="markAsRead(${notif.id})">
                    <div class="notification-content">
                        <div class="notification-icon ${notif.type}">
                            <i class="${notif.icon}"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-message">${notif.message}</div>
                            <div class="notification-time">${notif.time}</div>
                        </div>
                    </div>
                </div>
            `).join('');

            updateNotificationBadge();
        }

        function markAsRead(notificationId) {
            const notif = notifications.find(n => n.id === notificationId);
            if (notif && notif.unread) {
                notif.unread = false;
                renderNotifications();
                
                // Optional: Show brief feedback
                const item = event.currentTarget;
                item.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 150);
            }
        }

        function markAllAsRead(event) {
            event.stopPropagation();
            notifications.forEach(notif => notif.unread = false);
            renderNotifications();
            
            // Show feedback
            const action = event.target;
            const originalText = action.textContent;
            action.textContent = 'Marked!';
            action.style.color = '#22c55e';
            setTimeout(() => {
                action.textContent = originalText;
                action.style.color = '';
            }, 1500);
        }

        function clearAllNotifications(event) {
            event.stopPropagation();
            if (confirm('Clear all notifications? This action cannot be undone.')) {
                notifications = [];
                renderNotifications();
            }
        }

        function updateNotificationBadge() {
            const badge = document.getElementById('notificationBadge');
            const unreadCount = notifications.filter(n => n.unread).length;
            
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // Simulate real-time notifications (optional)
        function addNotification(type, message, icon = null) {
            const newNotif = {
                id: Date.now(),
                type: type,
                icon: icon || getDefaultIcon(type),
                message: message,
                time: 'Just now',
                unread: true
            };
            
            notifications.unshift(newNotif);
            
            // Keep only last 20 notifications
            if (notifications.length > 20) {
                notifications = notifications.slice(0, 20);
            }
            
            updateNotificationBadge();
            
            // If dropdown is open, refresh it
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown && dropdown.classList.contains('open')) {
                renderNotifications();
            }
        }

        function getDefaultIcon(type) {
            const icons = {
                'user': 'fas fa-user',
                'booking': 'fas fa-calendar',
                'payment': 'fas fa-credit-card',
                'system': 'fas fa-cog'
            };
            return icons[type] || 'fas fa-bell';
        }

        // Header functions
        function toggleAdminMenu() {
            const menu = document.getElementById('adminMenu');
            if (!menu) return;
            
            // Close other dropdowns
            document.querySelectorAll('.pill-menu.open, .filter-dropdown.open, .notification-dropdown.open').forEach(m => m.classList.remove('open'));
            menu.classList.toggle('open');
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }

        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            setupAdvancedSearch();
            updateNotificationBadge(); // Initialize notification badge
            
            // Demo: Add a new notification after 10 seconds (remove in production)
            setTimeout(() => {
                addNotification('user', 'New user "Alex Chen" has registered');
            }, 10000);
            
            // Close any open menus when clicking outside
            document.addEventListener('click', (e) => {
                // Close payment pill menus
                document.querySelectorAll('.pill-menu.open').forEach(menu => {
                    if (!menu.contains(e.target) && !menu.previousElementSibling.contains(e.target)) {
                        menu.classList.remove('open');
                    }
                });
                
                // Close filter dropdown
                const filterDropdown = document.getElementById('filterDropdown');
                const filterButton = filterDropdown?.closest('.btn-filter');
                if (filterDropdown && filterDropdown.classList.contains('open')) {
                    if (!filterButton?.contains(e.target)) {
                        filterDropdown.classList.remove('open');
                    }
                }

                // Close admin menu
                const adminMenu = document.getElementById('adminMenu');
                const adminDropdown = adminMenu?.closest('.admin-dropdown');
                if (adminMenu && adminMenu.classList.contains('open')) {
                    if (!adminDropdown?.contains(e.target)) {
                        adminMenu.classList.remove('open');
                    }
                }

                // Close notification dropdown
                const notificationDropdown = document.getElementById('notificationDropdown');
                const notificationButton = notificationDropdown?.closest('.notification-btn');
                if (notificationDropdown && notificationDropdown.classList.contains('open')) {
                    if (!notificationButton?.contains(e.target)) {
                        notificationDropdown.classList.remove('open');
                    }
                }
            });
        });

        function initializeCharts() {
            // Revenue Chart (Line Chart) - Using real data only
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                const monthlyData = <?php echo json_encode($monthly_revenue_data); ?>;
                const labels = monthlyData.length > 0 
                    ? monthlyData.map(item => item.month_name || 'Month')
                    : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
                const revenues = monthlyData.length > 0 
                    ? monthlyData.map(item => parseFloat(item.revenue) || 0)
                    : [0, 0, 0, 0, 0, 0]; // Show zeros when no data

                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: revenues,
                            borderColor: '#FFD700',
                            backgroundColor: 'rgba(255, 215, 0, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#fff' }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#ccc' },
                                grid: { color: '#444' }
                            },
                            y: {
                                ticks: { 
                                    color: '#ccc',
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                },
                                grid: { color: '#444' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Booking Status Chart (Doughnut Chart) - Using real data
            const bookingCtx = document.getElementById('bookingChart');
            if (bookingCtx) {
                const activeBookings = <?php echo $active_bookings; ?>;
                const pendingBookings = <?php echo $pending_bookings; ?>;
                const completedBookings = <?php echo $completed_bookings; ?>;
                
                new Chart(bookingCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Pending', 'Completed'],
                        datasets: [{
                            data: [activeBookings, pendingBookings, completedBookings],
                            backgroundColor: ['#28a745', '#FFD700', '#17a2b8'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#fff' }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                        return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Services Chart (Bar Chart) - Show zeros when no data
            const servicesCtx = document.getElementById('servicesChart');
            if (servicesCtx) {
                const totalBookings = <?php echo $active_bookings + $pending_bookings + $completed_bookings; ?>;
                const serviceData = totalBookings > 0 ? [
                    Math.floor(totalBookings * 0.35), // Car Wash - 35%
                    Math.floor(totalBookings * 0.25), // Waxing - 25%
                    Math.floor(totalBookings * 0.25), // Detailing - 25%
                    Math.floor(totalBookings * 0.15)  // Interior - 15%
                ] : [0, 0, 0, 0]; // Show zeros when no bookings

                new Chart(servicesCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Car Wash', 'Waxing', 'Detailing', 'Interior Clean'],
                        datasets: [{
                            label: 'Bookings',
                            data: serviceData,
                            backgroundColor: ['#FFD700', '#FFA500', '#FF8C00', '#DAA520'],
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#fff' }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#ccc' },
                                grid: { color: '#444' }
                            },
                            y: {
                                ticks: { 
                                    color: '#ccc',
                                    stepSize: 1
                                },
                                grid: { color: '#444' },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Daily Bookings Chart (Area Chart) - Show zeros when no data
            const dailyCtx = document.getElementById('dailyChart');
            if (dailyCtx) {
                const totalBookings = <?php echo $active_bookings + $pending_bookings; ?>;
                const avgDaily = totalBookings > 0 ? Math.ceil(totalBookings / 7) : 0;
                
                // Generate realistic daily booking data or show zeros
                const dailyData = [];
                if (totalBookings > 0) {
                    for (let i = 0; i < 7; i++) {
                        const variation = Math.floor(Math.random() * (avgDaily * 0.6)) - (avgDaily * 0.3);
                        dailyData.push(Math.max(0, avgDaily + variation));
                    }
                } else {
                    // Show all zeros when no bookings
                    for (let i = 0; i < 7; i++) {
                        dailyData.push(0);
                    }
                }

                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Daily Bookings',
                            data: dailyData,
                            backgroundColor: 'rgba(40, 167, 69, 0.2)',
                            borderColor: '#28a745',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#fff' }
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#ccc' },
                                grid: { color: '#444' }
                            },
                            y: {
                                ticks: { 
                                    color: '#ccc',
                                    stepSize: 1
                                },
                                grid: { color: '#444' },
                                beginAtZero: true
                            }
                        }
                    }
                });
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

        // Users page helpers - Enhanced search functionality
        function filterUsers() {
            const query = (document.getElementById('userSearch')?.value || '').trim();
            applyFilters(query);
        }

        function toggleFilterDropdown() {
            const dropdown = document.getElementById('filterDropdown');
            if (!dropdown) return;
            // Close other dropdowns
            document.querySelectorAll('.pill-menu.open').forEach(m => m.classList.remove('open'));
            dropdown.classList.toggle('open');
        }

        function applyFilters(searchQuery = '') {
            const query = searchQuery || (document.getElementById('userSearch')?.value || '').trim().toLowerCase();
            const showPaid = document.getElementById('filterPaid')?.checked;
            const showPartial = document.getElementById('filterPartial')?.checked;
            const showAll = document.getElementById('filterAll')?.checked;

            // If "All Users" is checked, show everything regardless of payment filters
            const shouldFilterByPayment = !showAll && (showPaid || showPartial);

            let visibleCount = 0;
            
            document.querySelectorAll('#usersTable tbody tr').forEach(tr => {
                // Enhanced search - check multiple fields
                const name = (tr.getAttribute('data-name') || '').toLowerCase();
                const service = (tr.getAttribute('data-service') || '').toLowerCase();
                const userId = (tr.getAttribute('data-id') || '').toLowerCase();
                const payment = (tr.getAttribute('data-payment') || '').toLowerCase();
                
                // Multi-field search: name, service, user ID, or payment status
                const matchesSearch = !query || 
                    name.includes(query) || 
                    service.includes(query) || 
                    userId.includes(query) || 
                    payment.includes(query);
                
                let matchesFilter = true;
                if (shouldFilterByPayment) {
                    const paymentStatus = tr.getAttribute('data-payment') || '';
                    const isFullyPaid = paymentStatus.includes('Fully Paid');
                    const isPartial = paymentStatus.includes('Partial');
                    
                    matchesFilter = (showPaid && isFullyPaid) || (showPartial && isPartial);
                }
                
                const shouldShow = matchesSearch && matchesFilter;
                tr.style.display = shouldShow ? '' : 'none';
                
                if (shouldShow) visibleCount++;
            });
            
            // Show search results count
            updateSearchResults(query, visibleCount);
        }

        function updateSearchResults(query, count) {
            // Remove existing result indicator
            const existingIndicator = document.querySelector('.search-results');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Add result indicator if there's a search query
            if (query) {
                const indicator = document.createElement('div');
                indicator.className = 'search-results';
                indicator.style.cssText = 'padding: 8px 12px; background: #2a2a2a; border-radius: 6px; margin-bottom: 10px; font-size: 13px; color: #FFD700;';
                indicator.innerHTML = `<i class="fas fa-search" style="margin-right: 6px;"></i>Found ${count} result${count !== 1 ? 's' : ''} for "${query}"`;
                
                const tableWrap = document.querySelector('.table-wrap');
                if (tableWrap) {
                    tableWrap.parentNode.insertBefore(indicator, tableWrap);
                }
            }
        }

        // Enhanced search with real-time suggestions
        function setupAdvancedSearch() {
            const searchInput = document.getElementById('userSearch');
            if (!searchInput) return;
            
            // Add search suggestions on focus
            searchInput.addEventListener('focus', function() {
                this.placeholder = 'Search by name, service, ID, or payment status...';
            });
            
            searchInput.addEventListener('blur', function() {
                this.placeholder = 'Search users...';
            });
            
            // Clear search button
            const searchBox = document.querySelector('.search-box');
            if (searchBox && !searchBox.querySelector('.clear-search')) {
                const clearBtn = document.createElement('button');
                clearBtn.className = 'clear-search';
                clearBtn.innerHTML = '<i class="fas fa-times"></i>';
                clearBtn.style.cssText = 'position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #888; cursor: pointer; padding: 4px; border-radius: 3px; display: none;';
                clearBtn.onclick = clearSearch;
                searchBox.appendChild(clearBtn);
                
                // Show/hide clear button
                searchInput.addEventListener('input', function() {
                    clearBtn.style.display = this.value ? 'block' : 'none';
                });
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('userSearch');
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
                applyFilters('');
            }
        }

        function togglePillMenu(trigger) {
            const menu = trigger.nextElementSibling;
            if (!menu) return;
            // Close others
            document.querySelectorAll('.pill-menu.open').forEach(m => { if (m!==menu) m.classList.remove('open'); });
            menu.classList.toggle('open');
        }

        function setPayment(item, label) {
            const menu = item.closest('.pill-menu');
            const trigger = menu.previousElementSibling;
            if (trigger) {
                trigger.innerHTML = (label === 'Fully Paid'
                    ? '<span class="dot" style="width:8px;height:8px;background:#88f7a2;border-radius:50%;display:inline-block"></span> '
                    : '<span class="dot" style="width:8px;height:8px;background:#f5e6a6;border-radius:50%;display:inline-block"></span> ')
                    + label + ' <i class="fas fa-caret-down" style="opacity:.8"></i>';
            }
            menu.classList.remove('open');
        }

        function confirmDelete(name) {
            if (confirm('Delete user '+ name + '?')) {
                alert('Delete action placeholder. Hook to backend as needed.');
            }
        }

        // Notifications page functionality
        let currentPageFilter = 'all';
        let notificationsPageData = [];

        function initializeNotificationsPage() {
            // Generate sample notifications for the page
            notificationsPageData = [
                {
                    id: 1,
                    type: 'user',
                    title: 'New User Registration',
                    message: 'John Doe has registered a new account and is awaiting approval.',
                    time: '5 minutes ago',
                    date: new Date(),
                    unread: true
                },
                {
                    id: 2,
                    type: 'booking',
                    title: 'New Booking Request',
                    message: 'Sarah Johnson has requested a Premium Wash service for tomorrow at 2:00 PM.',
                    time: '15 minutes ago',
                    date: new Date(Date.now() - 15 * 60000),
                    unread: true
                },
                {
                    id: 3,
                    type: 'payment',
                    title: 'Payment Received',
                    message: 'Payment of $75.00 received from Mike Wilson for Express Detail service.',
                    time: '1 hour ago',
                    date: new Date(Date.now() - 60 * 60000),
                    unread: false
                },
                {
                    id: 4,
                    type: 'booking',
                    title: 'Booking Cancelled',
                    message: 'Emma Davis has cancelled her Full Detail appointment scheduled for today.',
                    time: '2 hours ago',
                    date: new Date(Date.now() - 2 * 60 * 60000),
                    unread: false
                },
                {
                    id: 5,
                    type: 'system',
                    title: 'System Maintenance',
                    message: 'Scheduled system maintenance completed successfully. All services are now operational.',
                    time: '3 hours ago',
                    date: new Date(Date.now() - 3 * 60 * 60000),
                    unread: false
                },
                {
                    id: 6,
                    type: 'payment',
                    title: 'Payment Failed',
                    message: 'Payment attempt failed for Lisa Chen. Please follow up on the Premium Detail booking.',
                    time: '4 hours ago',
                    date: new Date(Date.now() - 4 * 60 * 60000),
                    unread: true
                },
                {
                    id: 7,
                    type: 'user',
                    title: 'Profile Updated',
                    message: 'Robert Brown has updated his profile information and contact details.',
                    time: '5 hours ago',
                    date: new Date(Date.now() - 5 * 60 * 60000),
                    unread: false
                },
                {
                    id: 8,
                    type: 'booking',
                    title: 'Booking Confirmed',
                    message: 'Automated confirmation sent to Alex Martinez for Express Wash on Friday.',
                    time: '6 hours ago',
                    date: new Date(Date.now() - 6 * 60 * 60000),
                    unread: false
                }
            ];

            updateNotificationStats();
            renderNotificationsPage();
        }

        function updateNotificationStats() {
            const total = notificationsPageData.length;
            const unread = notificationsPageData.filter(n => n.unread).length;
            const today = notificationsPageData.filter(n => {
                const now = new Date();
                const notifDate = n.date;
                return notifDate.toDateString() === now.toDateString();
            }).length;

            document.getElementById('totalNotifications').textContent = total;
            document.getElementById('unreadNotifications').textContent = unread;
            document.getElementById('todayNotifications').textContent = today;
        }

        function filterNotificationsPage(filter) {
            currentPageFilter = filter;

            // Update filter button states
            document.querySelectorAll('.notification-controls .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

            renderNotificationsPage();
        }

        function renderNotificationsPage() {
            const container = document.getElementById('fullNotificationsList');
            let filteredNotifications = notificationsPageData;

            // Apply filter
            if (currentPageFilter !== 'all') {
                if (currentPageFilter === 'unread') {
                    filteredNotifications = notificationsPageData.filter(n => n.unread);
                } else {
                    filteredNotifications = notificationsPageData.filter(n => n.type === currentPageFilter);
                }
            }

            if (filteredNotifications.length === 0) {
                container.innerHTML = `
                    <div class="no-notifications-page">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No notifications found</h3>
                        <p>There are no notifications matching your current filter.</p>
                    </div>
                `;
                return;
            }

            const html = filteredNotifications.map(notification => {
                const iconConfig = getNotificationIcon(notification.type);
                return `
                    <div class="notification-item-page ${notification.unread ? 'unread' : ''}" 
                         onclick="markNotificationAsRead(${notification.id})">
                        <div class="notification-header-page">
                            <div class="notification-icon-page" style="background: ${iconConfig.bg}; color: ${iconConfig.color};">
                                <i class="${iconConfig.icon}"></i>
                            </div>
                            <div class="notification-details-page">
                                <div class="notification-title-page">${notification.title}</div>
                                <div class="notification-message-page">${notification.message}</div>
                                <div class="notification-meta-page">
                                    <span>${notification.time}</span>
                                    <span class="notification-type-badge notification-type-${notification.type}">
                                        ${notification.type}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
        }

        function markNotificationAsRead(notificationId) {
            const notification = notificationsPageData.find(n => n.id === notificationId);
            if (notification && notification.unread) {
                notification.unread = false;
                updateNotificationStats();
                updateNotificationBadge(); // Update header badge too
                renderNotificationsPage();
            }
        }

        function markAllAsReadPage() {
            if (confirm('Mark all notifications as read?')) {
                notificationsPageData.forEach(n => n.unread = false);
                updateNotificationStats();
                updateNotificationBadge(); // Update header badge too
                renderNotificationsPage();
            }
        }

        function clearAllNotificationsPage() {
            if (confirm('Clear all notifications? This action cannot be undone.')) {
                notificationsPageData = [];
                updateNotificationStats();
                updateNotificationBadge(); // Update header badge too
                renderNotificationsPage();
            }
        }

        // Initialize notifications page when the section is first shown
        document.addEventListener('DOMContentLoaded', function() {
            // Check if notifications section is active and initialize
            const notificationSection = document.getElementById('notifications');
            if (notificationSection && !notificationSection.style.display === 'none') {
                initializeNotificationsPage();
            }
        });

        // Override showSection to initialize notifications page when shown
        const originalShowSection = showSection;
        showSection = function(sectionId, linkEl) {
            originalShowSection(sectionId, linkEl);
            if (sectionId === 'notifications') {
                setTimeout(initializeNotificationsPage, 100);
            } else if (sectionId === 'reviews') {
                setTimeout(initializeReviewsPage, 100);
            }
        };

        // Reviews page functionality
        let currentReviewFilter = 'all';
        let reviewsData = [];

        function initializeReviewsPage() {
            // Generate sample reviews data
            reviewsData = [
                {
                    id: 1,
                    customer: 'John Doe',
                    service: 'Premium Wash',
                    rating: 5,
                    date: '2025-10-05',
                    time: '2 hours ago',
                    review: 'Absolutely fantastic service! My car looks brand new. The team was professional and thorough. Highly recommend!',
                    hasResponse: true,
                    response: 'Thank you so much for your kind words, John! We\'re thrilled you loved our Premium Wash service.',
                    responseDate: '2025-10-05'
                },
                {
                    id: 2,
                    customer: 'Sarah Johnson',
                    service: 'Interior Detailing',
                    rating: 4,
                    date: '2025-10-04',
                    time: '1 day ago',
                    review: 'Great job on the interior! Very clean and fresh. Only minor issue was timing - took a bit longer than expected.',
                    hasResponse: false
                },
                {
                    id: 3,
                    customer: 'Mike Wilson',
                    service: 'Full Detail',
                    rating: 5,
                    date: '2025-10-03',
                    time: '2 days ago',
                    review: 'Outstanding work! Both interior and exterior look amazing. Worth every penny. Will definitely be back!',
                    hasResponse: true,
                    response: 'We appreciate your business, Mike! Looking forward to serving you again soon.',
                    responseDate: '2025-10-03'
                },
                {
                    id: 4,
                    customer: 'Emma Davis',
                    service: 'Express Wash',
                    rating: 3,
                    date: '2025-10-02',
                    time: '3 days ago',
                    review: 'Service was okay, but I\'ve had better. The wash was thorough but some spots were missed on the wheels.',
                    hasResponse: false
                },
                {
                    id: 5,
                    customer: 'Robert Brown',
                    service: 'Premium Detail',
                    rating: 5,
                    date: '2025-10-01',
                    time: '4 days ago',
                    review: 'Exceptional attention to detail! The car has never looked better. Professional team and great customer service.',
                    hasResponse: true,
                    response: 'Thank you Robert! We take pride in our attention to detail and are glad it shows.',
                    responseDate: '2025-10-01'
                },
                {
                    id: 6,
                    customer: 'Lisa Chen',
                    service: 'Interior Clean',
                    rating: 4,
                    date: '2025-09-30',
                    time: '5 days ago',
                    review: 'Very satisfied with the interior cleaning. Removed all stains and odors. Friendly staff too!',
                    hasResponse: false
                }
            ];

            // Load saved responses from localStorage
            loadSavedResponses();

            updateReviewStats();
            renderReviews();
        }

        function loadSavedResponses() {
            try {
                const savedResponses = localStorage.getItem('reviewResponses');
                if (savedResponses) {
                    const responses = JSON.parse(savedResponses);
                    reviewsData.forEach(review => {
                        if (responses[review.id]) {
                            review.hasResponse = true;
                            review.response = responses[review.id].response;
                            review.responseDate = responses[review.id].responseDate;
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading saved responses:', error);
            }
        }

        function saveResponse(reviewId, response) {
            try {
                let savedResponses = {};
                const existing = localStorage.getItem('reviewResponses');
                if (existing) {
                    savedResponses = JSON.parse(existing);
                }
                
                savedResponses[reviewId] = {
                    response: response,
                    responseDate: new Date().toISOString().split('T')[0]
                };
                
                localStorage.setItem('reviewResponses', JSON.stringify(savedResponses));
            } catch (error) {
                console.error('Error saving response:', error);
            }
        }

        function updateReviewStats() {
            const total = reviewsData.length;
            const totalRating = reviewsData.reduce((sum, review) => sum + review.rating, 0);
            const average = total > 0 ? (totalRating / total).toFixed(1) : '0.0';
            const pending = reviewsData.filter(r => !r.hasResponse).length;
            const thisMonth = reviewsData.filter(r => {
                const reviewDate = new Date(r.date);
                const now = new Date();
                return reviewDate.getMonth() === now.getMonth() && reviewDate.getFullYear() === now.getFullYear();
            }).length;

            document.getElementById('totalReviews').textContent = total;
            document.getElementById('averageRating').textContent = average;
            document.getElementById('pendingReviews').textContent = pending;
            document.getElementById('thisMonthReviews').textContent = thisMonth;
        }

        function filterReviews(filter) {
            currentReviewFilter = filter;

            // Update filter button states
            document.querySelectorAll('.review-controls .filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-filter="${filter}"]`).classList.add('active');

            renderReviews();
        }

        function searchReviews() {
            renderReviews();
        }

        function renderReviews() {
            const container = document.getElementById('reviewsList');
            const searchTerm = document.getElementById('reviewSearchInput').value.toLowerCase();
            let filteredReviews = reviewsData;

            // Apply filter
            if (currentReviewFilter !== 'all') {
                if (currentReviewFilter === 'pending') {
                    filteredReviews = reviewsData.filter(r => !r.hasResponse);
                } else {
                    filteredReviews = reviewsData.filter(r => r.rating == currentReviewFilter);
                }
            }

            // Apply search
            if (searchTerm) {
                filteredReviews = filteredReviews.filter(r => 
                    r.customer.toLowerCase().includes(searchTerm) ||
                    r.service.toLowerCase().includes(searchTerm) ||
                    r.review.toLowerCase().includes(searchTerm)
                );
            }

            if (filteredReviews.length === 0) {
                container.innerHTML = `
                    <div class="no-reviews">
                        <i class="fas fa-star"></i>
                        <h3>No reviews found</h3>
                        <p>No reviews match your current filter or search criteria.</p>
                    </div>
                `;
                return;
            }

            const html = filteredReviews.map(review => {
                const stars = Array.from({length: 5}, (_, i) => 
                    `<i class="fas fa-star ${i < review.rating ? '' : 'empty'}"></i>`
                ).join('');

                const initials = review.customer.split(' ').map(n => n[0]).join('');

                return `
                    <div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">${initials}</div>
                                <div class="reviewer-details">
                                    <h4>${review.customer}</h4>
                                    <div class="service-info">${review.service}</div>
                                </div>
                            </div>
                            <div class="rating-stars">${stars}</div>
                        </div>
                        
                        <div class="review-content">
                            <div class="review-text">${review.review}</div>
                            <div class="review-meta">
                                <span>${review.time}</span>
                                <div class="review-actions">
                                    ${!review.hasResponse ? 
                                        `<button class="review-btn reply" onclick="replyToReview(${review.id})">
                                            <i class="fas fa-reply"></i> Reply
                                        </button>` : 
                                        ''
                                    }
                                    <button class="review-btn" onclick="flagReview(${review.id})">
                                        <i class="fas fa-flag"></i> Flag
                                    </button>
                                </div>
                            </div>
                        </div>

                        ${review.hasResponse ? 
                            `<div class="review-response">
                                <div class="response-header">
                                    <i class="fas fa-reply"></i>
                                    <span>Admin Response</span>
                                </div>
                                <div class="response-text">${review.response}</div>
                            </div>` : 
                            ''
                        }
                    </div>
                `;
            }).join('');

            container.innerHTML = html;
        }

        function replyToReview(reviewId) {
            const review = reviewsData.find(r => r.id === reviewId);
            if (!review) return;

            const response = prompt(`Reply to ${review.customer}'s review:`);
            if (response && response.trim()) {
                review.hasResponse = true;
                review.response = response.trim();
                review.responseDate = new Date().toISOString().split('T')[0];
                
                // Save response to localStorage
                saveResponse(reviewId, response.trim());
                
                updateReviewStats();
                renderReviews();
                alert('Response posted successfully and will persist across page refreshes!');
            }
        }

        function flagReview(reviewId) {
            if (confirm('Flag this review for admin attention?')) {
                alert('Review has been flagged for review.');
            }
        }

        // Settings functionality
        let activeSettingsTab = 'general';

        function showSettingsTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.settings-content');
            tabContents.forEach(content => content.style.display = 'none');

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.settings-tab');
            tabButtons.forEach(btn => btn.classList.remove('active'));

            // Show selected tab content
            const selectedTab = document.getElementById(tabName + '-settings');
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }

            // Add active class to clicked tab button
            const clickedButton = document.querySelector(`[onclick="showSettingsTab('${tabName}')"]`);
            if (clickedButton) {
                clickedButton.classList.add('active');
            }

            activeSettingsTab = tabName;
        }

        // Notification System
        function showNotification(message, type = 'info', title = null, duration = 5000) {
            const container = document.getElementById('notification-container');
            if (!container) return;

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;

            // Get appropriate icon based on type
            let icon = '';
            let notificationTitle = title;
            
            switch(type) {
                case 'success':
                    icon = 'fas fa-check-circle';
                    notificationTitle = notificationTitle || 'Success';
                    break;
                case 'error':
                    icon = 'fas fa-exclamation-circle';
                    notificationTitle = notificationTitle || 'Error';
                    break;
                case 'warning':
                    icon = 'fas fa-exclamation-triangle';
                    notificationTitle = notificationTitle || 'Warning';
                    break;
                case 'info':
                default:
                    icon = 'fas fa-info-circle';
                    notificationTitle = notificationTitle || 'Information';
                    break;
            }

            notification.innerHTML = `
                <i class="${icon} notification-icon"></i>
                <div class="notification-content">
                    <div class="notification-title">${notificationTitle}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" onclick="removeNotification(this.parentElement)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Add to container
            container.appendChild(notification);

            // Auto-remove after duration
            if (duration > 0) {
                setTimeout(() => {
                    removeNotification(notification);
                }, duration);
            }

            // Play sound if enabled
            playNotificationSound(type);
        }

        function removeNotification(notification) {
            if (!notification || !notification.parentElement) return;
            
            notification.classList.add('removing');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.parentElement.removeChild(notification);
                }
            }, 300);
        }

        function playNotificationSound(type) {
            // Only play if sound notifications are enabled
            const soundEnabled = document.getElementById('soundNotifications');
            if (!soundEnabled || !soundEnabled.checked) return;

            // Create audio context for better browser support
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                // Different frequencies for different notification types
                switch(type) {
                    case 'success':
                        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                        oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);
                        break;
                    case 'error':
                        oscillator.frequency.setValueAtTime(400, audioContext.currentTime);
                        oscillator.frequency.setValueAtTime(300, audioContext.currentTime + 0.1);
                        break;
                    case 'warning':
                        oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
                        break;
                    case 'info':
                    default:
                        oscillator.frequency.setValueAtTime(500, audioContext.currentTime);
                        break;
                }

                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            } catch (e) {
                // Fallback for older browsers or when audio context is not available
                console.log('Audio notification not available');
            }
        }

        // Clear all notifications
        function clearAllNotifications() {
            const container = document.getElementById('notification-container');
            if (!container) return;
            
            const notifications = container.querySelectorAll('.notification');
            notifications.forEach(notification => {
                removeNotification(notification);
            });
        }

        // Show system status notifications
        function showSystemNotification() {
            const now = new Date();
            const hour = now.getHours();
            
            if (hour >= 6 && hour < 12) {
                showNotification('Good morning! Car Detailing Pro is ready.', 'info', 'System Ready');
            } else if (hour >= 12 && hour < 18) {
                showNotification('Good afternoon! All systems operational.', 'info', 'System Status');
            } else {
                showNotification('Good evening! System running smoothly.', 'info', 'System Status');
            }
        }

        function loadSettings() {
            const settings = JSON.parse(localStorage.getItem('adminSettings') || '{}');
            
            // General Settings
            if (settings.appName) document.getElementById('appName').value = settings.appName;
            if (settings.appDescription) document.getElementById('appDescription').value = settings.appDescription;
            if (settings.language) document.getElementById('language').value = settings.language;
            if (settings.timezone) document.getElementById('timezone').value = settings.timezone;
            if (settings.dateFormat) document.getElementById('dateFormat').value = settings.dateFormat;
            if (settings.currency) document.getElementById('currency').value = settings.currency;

            // Business Settings
            if (settings.companyName) document.getElementById('companyName').value = settings.companyName;
            if (settings.companyEmail) document.getElementById('companyEmail').value = settings.companyEmail;
            if (settings.companyPhone) document.getElementById('companyPhone').value = settings.companyPhone;
            if (settings.companyAddress) document.getElementById('companyAddress').value = settings.companyAddress;
            if (settings.businessHours) document.getElementById('businessHours').value = settings.businessHours;
            if (settings.serviceRadius) document.getElementById('serviceRadius').value = settings.serviceRadius;

            // Notification Settings
            document.getElementById('emailNotifications').checked = settings.emailNotifications !== false;
            document.getElementById('pushNotifications').checked = settings.pushNotifications !== false;
            document.getElementById('smsNotifications').checked = settings.smsNotifications !== false;
            document.getElementById('newOrderNotifs').checked = settings.newOrderNotifs !== false;
            document.getElementById('paymentNotifs').checked = settings.paymentNotifs !== false;
            document.getElementById('reviewNotifs').checked = settings.reviewNotifs !== false;

            // Security Settings
            document.getElementById('twoFactorAuth').checked = settings.twoFactorAuth === true;
            document.getElementById('autoLogout').checked = settings.autoLogout !== false;
            if (settings.sessionTimeout) document.getElementById('sessionTimeout').value = settings.sessionTimeout;

            // Appearance Settings
            if (settings.theme) {
                document.getElementById(settings.theme).checked = true;
                applyTheme(settings.theme);
            }
            if (settings.accentColor) document.getElementById('accentColor').value = settings.accentColor;
            document.getElementById('animations').checked = settings.animations !== false;
            document.getElementById('compactMode').checked = settings.compactMode === true;

            // Backup Settings
            document.getElementById('autoBackup').checked = settings.autoBackup === true;
            if (settings.backupFrequency) document.getElementById('backupFrequency').value = settings.backupFrequency;
        }

        function saveSettings() {
            const currentSettings = JSON.parse(localStorage.getItem('adminSettings') || '{}');
            
            const newSettings = {
                // General Settings
                appName: document.getElementById('appName')?.value || '',
                appDescription: document.getElementById('appDescription')?.value || '',
                language: document.getElementById('language')?.value || 'en',
                timezone: document.getElementById('timezone')?.value || 'UTC-5',
                dateFormat: document.getElementById('dateFormat')?.value || 'MM/DD/YYYY',
                currency: document.getElementById('currency')?.value || 'USD',

                // Business Settings
                companyName: document.getElementById('businessName')?.value || '',
                companyEmail: document.getElementById('businessEmail')?.value || '',
                companyPhone: document.getElementById('businessPhone')?.value || '',
                companyAddress: document.getElementById('businessAddress')?.value || '',
                businessHours: document.getElementById('businessHours')?.value || '',
                serviceRadius: document.getElementById('serviceRadius')?.value || '25',

                // Notification Settings
                emailNotifications: document.getElementById('emailNotifications')?.checked ?? true,
                pushNotifications: document.getElementById('pushNotifications')?.checked ?? true,
                smsNotifications: document.getElementById('smsNotifications')?.checked ?? false,
                newOrderNotifs: document.getElementById('newOrderNotifs')?.checked ?? true,
                paymentNotifs: document.getElementById('paymentNotifs')?.checked ?? true,
                reviewNotifs: document.getElementById('reviewNotifs')?.checked ?? true,

                // Security Settings
                twoFactorAuth: document.getElementById('twoFactorAuth')?.checked ?? false,
                autoLogout: document.getElementById('autoLogout')?.checked ?? true,
                sessionTimeout: document.getElementById('sessionTimeout')?.value || '30',

                // Appearance Settings
                theme: document.querySelector('input[name="theme"]:checked')?.id || 'dark',
                accentColor: document.getElementById('accentColor')?.value || '#FFD700',
                animations: document.getElementById('animations')?.checked ?? true,
                compactMode: document.getElementById('compactMode')?.checked ?? false
            };

            // Check if settings actually changed
            const hasChanges = JSON.stringify(currentSettings) !== JSON.stringify(newSettings);
            
            localStorage.setItem('adminSettings', JSON.stringify(newSettings));
            
            return hasChanges;
        }

        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default? This action cannot be undone.')) {
                localStorage.removeItem('adminSettings');
                location.reload();
            }
        }

        function applyTheme(theme) {
            const root = document.documentElement;
            
            switch(theme) {
                case 'light':
                    root.style.setProperty('--bg-primary', '#ffffff');
                    root.style.setProperty('--bg-secondary', '#f8f9fa');
                    root.style.setProperty('--text-primary', '#333333');
                    root.style.setProperty('--text-secondary', '#666666');
                    break;
                case 'blue':
                    root.style.setProperty('--bg-primary', '#1e3a8a');
                    root.style.setProperty('--bg-secondary', '#3b82f6');
                    root.style.setProperty('--text-primary', '#ffffff');
                    root.style.setProperty('--text-secondary', '#e5e7eb');
                    break;
                case 'green':
                    root.style.setProperty('--bg-primary', '#064e3b');
                    root.style.setProperty('--bg-secondary', '#059669');
                    root.style.setProperty('--text-primary', '#ffffff');
                    root.style.setProperty('--text-secondary', '#e5e7eb');
                    break;
                default: // dark
                    root.style.setProperty('--bg-primary', '#1a1a1a');
                    root.style.setProperty('--bg-secondary', '#2a2a2a');
                    root.style.setProperty('--text-primary', '#ffffff');
                    root.style.setProperty('--text-secondary', '#cccccc');
            }
        }

        function changePassword() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!currentPassword || !newPassword || !confirmPassword) {
                showNotification('Please fill in all password fields', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                showNotification('New passwords do not match', 'error');
                return;
            }

            if (newPassword.length < 8) {
                showNotification('Password must be at least 8 characters long', 'error');
                return;
            }

            // Simulate password change
            showNotification('Password changed successfully!', 'success');
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        }

        function enable2FA() {
            const isEnabled = document.getElementById('twoFactorAuth').checked;
            if (isEnabled) {
                showNotification('Two-Factor Authentication enabled. Please check your email for setup instructions.', 'success');
            } else {
                if (confirm('Are you sure you want to disable Two-Factor Authentication?')) {
                    showNotification('Two-Factor Authentication disabled.', 'info');
                } else {
                    document.getElementById('twoFactorAuth').checked = true;
                }
            }
        }

        function exportData() {
            const data = {
                settings: JSON.parse(localStorage.getItem('adminSettings') || '{}'),
                users: JSON.parse(localStorage.getItem('users') || '[]'),
                reviews: JSON.parse(localStorage.getItem('reviews') || '[]'),
                notifications: JSON.parse(localStorage.getItem('notifications') || '[]'),
                exportDate: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `admin-data-export-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showNotification('Data exported successfully!', 'success');
        }

        function importData() {
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = '.json';
            fileInput.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const data = JSON.parse(e.target.result);
                        
                        if (confirm('This will overwrite all current data. Are you sure you want to continue?')) {
                            if (data.settings) localStorage.setItem('adminSettings', JSON.stringify(data.settings));
                            if (data.users) localStorage.setItem('users', JSON.stringify(data.users));
                            if (data.reviews) localStorage.setItem('reviews', JSON.stringify(data.reviews));
                            if (data.notifications) localStorage.setItem('notifications', JSON.stringify(data.notifications));
                            
                            showNotification('Data imported successfully! Refreshing page...', 'success');
                            setTimeout(() => location.reload(), 2000);
                        }
                    } catch (error) {
                        showNotification('Invalid file format. Please select a valid JSON export file.', 'error');
                    }
                };
                reader.readAsText(file);
            };
            fileInput.click();
        }

        function performBackup() {
            showNotification('Creating backup...', 'info');
            setTimeout(() => {
                exportData();
                showNotification('Backup completed successfully!', 'success');
            }, 1000);
        }

        function restoreBackup() {
            importData();
        }

        // Theme change handlers
        document.addEventListener('DOMContentLoaded', function() {
            const themeInputs = document.querySelectorAll('input[name="theme"]');
            themeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.checked) {
                        selectTheme(this.value);
                    }
                });
                
                // Also add click handler to labels
                const label = document.querySelector(`label[for="${input.id}"]`);
                if (label) {
                    label.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectTheme(input.value);
                    });
                }
            });

            // Load settings on page load
            loadSettings();

            // Apply appearance settings on load
            setTimeout(() => {
                applyAppearanceSettings();
            }, 100);
        });

        // Auto-save settings when form fields change
        function setupAutoSave() {
            const settingsForm = document.getElementById('settingsForm');
            if (settingsForm) {
                settingsForm.addEventListener('change', function() {
                    setTimeout(saveSettings, 500); // Auto-save after 500ms
                });
            }
        }

        // Initialize settings functionality
        document.addEventListener('DOMContentLoaded', function() {
            setupAutoSave();
            showSettingsTab('general'); // Default to general tab
            
            // Show system ready notification after a brief delay
            setTimeout(() => {
                showSystemNotification();
            }, 1000);
        });

        // Additional settings functions
        function saveGeneralSettings() {
            const hasChanges = saveSettings();
            if (hasChanges) {
                showNotification('General settings have been updated successfully.', 'success', 'Settings Saved');
            }
        }

        function resetGeneralSettings() {
            if (confirm('Are you sure you want to reset all general settings to their default values? This action cannot be undone.')) {
                document.getElementById('appName').value = 'Car Detailing Pro Admin';
                document.getElementById('adminName').value = '';
                document.getElementById('adminEmail').value = '';
                document.getElementById('timezone').value = 'UTC-5';
                document.getElementById('language').value = 'en';
                document.getElementById('itemsPerPage').value = '25';
                document.getElementById('dateFormat').value = 'MM/DD/YYYY';
                document.getElementById('currency').value = 'USD';
                document.getElementById('refreshRate').value = '60';
                document.getElementById('showTooltips').checked = true;
                document.getElementById('autoSave').checked = true;
                document.getElementById('soundNotifications').checked = true;
                document.getElementById('compactView').checked = false;
                document.getElementById('defaultServiceDuration').value = '60';
                document.getElementById('bookingLeadTime').value = '24';
                document.getElementById('weatherThreshold').value = 'moderate';
                document.getElementById('qualityCheckLevel').value = 'standard';
                document.getElementById('autoScheduleReminders').checked = true;
                document.getElementById('photoDocumentation').checked = true;
                document.getElementById('customerFeedback').checked = true;
                document.getElementById('inventoryTracking').checked = true;
                saveSettings();
                showNotification('General settings have been reset to default values.', 'info', 'Settings Reset');
            }
        }

        function saveBusinessSettings() {
            const hasChanges = saveSettings();
            if (hasChanges) {
                showNotification('Business settings have been updated successfully.', 'success', 'Settings Saved');
            }
        }

        function resetBusinessSettings() {
            if (confirm('Are you sure you want to reset all business settings to their default values? This action cannot be undone.')) {
                document.getElementById('businessName').value = 'Premium Car Detailing';
                document.getElementById('businessPhone').value = '+1 (555) 123-4567';
                document.getElementById('businessEmail').value = 'info@cardetailing.com';
                document.getElementById('businessWebsite').value = 'https://cardetailing.com';
                document.getElementById('businessAddress').value = '123 Main Street, City, State 12345';
                document.getElementById('licenseNumber').value = '';
                document.getElementById('taxId').value = '';
                document.getElementById('serviceRadius').value = '25';
                document.getElementById('emergencyRadius').value = '15';
                document.getElementById('serviceCities').value = '';
                document.getElementById('mobileService').checked = true;
                document.getElementById('shopService').checked = true;
                // Reset pricing
                document.getElementById('basicWash').value = '25';
                document.getElementById('interiorDetail').value = '75';
                document.getElementById('exteriorDetail').value = '85';
                document.getElementById('completeDetail').value = '150';
                document.getElementById('paintCorrection').value = '200';
                document.getElementById('ceramicCoating').value = '500';
                document.getElementById('mobileServiceFee').value = '15';
                document.getElementById('rushSurcharge').value = '25';
                saveSettings();
                showNotification('Business settings have been reset to default values.', 'info', 'Settings Reset');
            }
        }

        function saveAppearanceSettings() {
            const hasChanges = saveSettings();
            applyAppearanceSettings();
            if (hasChanges) {
                showNotification('Appearance settings have been updated successfully.', 'success', 'Settings Saved');
            }
        }

        function resetAppearanceSettings() {
            if (confirm('Reset appearance settings to default values?')) {
                document.getElementById('dark').checked = true;
                document.getElementById('accentColor').value = '#FFD700';
                document.getElementById('sidebarWidth').value = '280';
                document.getElementById('fontSize').value = '14';
                document.getElementById('borderRadius').value = '8';
                document.getElementById('animations').checked = true;
                document.getElementById('compactMode').checked = false;
                document.getElementById('reducedMotion').checked = false;
                document.getElementById('highContrast').checked = false;
                document.getElementById('dashboardLayout').value = 'grid';
                document.getElementById('headerStyle').value = 'fixed';
                document.getElementById('showBreadcrumbs').checked = true;
                document.getElementById('collapsibleSidebar').checked = true;
                document.getElementById('screenReader').value = 'auto';
                document.getElementById('keyboardNav').value = 'standard';
                document.getElementById('focusIndicators').checked = true;
                document.getElementById('skipLinks').checked = true;
                applyTheme('dark');
                applyAppearanceSettings();
                saveSettings();
                showNotification('Appearance settings reset to defaults', 'info');
            }
        }

        function previewTheme() {
            const selectedTheme = document.querySelector('input[name="theme"]:checked')?.value || 'dark';
            applyTheme(selectedTheme);
            applyAppearanceSettings();
            // Theme is already applied, no need for notification
        }

        function applyAppearanceSettings() {
            const settings = {
                accentColor: document.getElementById('accentColor').value,
                sidebarWidth: document.getElementById('sidebarWidth').value,
                fontSize: document.getElementById('fontSize').value,
                borderRadius: document.getElementById('borderRadius').value,
                animations: document.getElementById('animations').checked,
                compactMode: document.getElementById('compactMode').checked,
                reducedMotion: document.getElementById('reducedMotion').checked,
                highContrast: document.getElementById('highContrast').checked
            };

            const root = document.documentElement;

            // Apply accent color
            root.style.setProperty('--accent-color', settings.accentColor);

            // Apply sidebar width
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.style.width = settings.sidebarWidth + 'px';
            }

            // Apply font size
            root.style.setProperty('--font-size', settings.fontSize + 'px');

            // Apply border radius
            root.style.setProperty('--border-radius', settings.borderRadius + 'px');

            // Apply animations
            if (!settings.animations || settings.reducedMotion) {
                root.style.setProperty('--transition-duration', '0s');
            } else {
                root.style.setProperty('--transition-duration', '0.3s');
            }

            // Apply compact mode
            if (settings.compactMode) {
                document.body.classList.add('compact-mode');
            } else {
                document.body.classList.remove('compact-mode');
            }

            // Apply high contrast
            if (settings.highContrast) {
                document.body.classList.add('high-contrast');
            } else {
                document.body.classList.remove('high-contrast');
            }
        }

        // Theme selection function
        function selectTheme(themeName) {
            // Update radio button
            const themeRadio = document.getElementById(themeName);
            if (themeRadio) {
                themeRadio.checked = true;
            }

            // Update visual state
            document.querySelectorAll('.theme-preview').forEach(preview => {
                preview.classList.remove('active');
            });
            
            const selectedPreview = document.querySelector(`.${themeName}-theme`);
            if (selectedPreview) {
                selectedPreview.classList.add('active');
            }

            // Apply theme immediately
            applyTheme(themeName);
            
            // Show meaningful feedback only when settings are actually saved
            // No notification here - will show when user saves settings
        }

        // Booking Management Functions
        function confirmBooking(bookingId) {
            if (confirm('Are you sure you want to confirm this booking?')) {
                const formData = new FormData();
                formData.append('action', 'confirm');
                formData.append('booking_id', bookingId);

                fetch('manage_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Booking confirmed successfully!', 'success', 'Success');
                        refreshBookings();
                    } else {
                        showNotification('Error confirming booking: ' + data.message, 'error', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error', 'Error');
                });
            }
        }

        function declineBooking(bookingId) {
            const reason = prompt('Please enter a reason for declining this booking (optional):') || 'Booking declined by admin';
            
            if (confirm('Are you sure you want to decline this booking?')) {
                const formData = new FormData();
                formData.append('action', 'decline');
                formData.append('booking_id', bookingId);
                formData.append('reason', reason);

                fetch('manage_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Booking declined successfully!', 'success', 'Success');
                        refreshBookings();
                    } else {
                        showNotification('Error declining booking: ' + data.message, 'error', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error', 'Error');
                });
            }
        }

        function viewBookingDetails(bookingId) {
            // This can be expanded to show a modal with full booking details
            alert('Booking details modal coming soon for booking #' + bookingId);
        }

        function refreshBookings() {
            showNotification('Refreshing bookings...', 'info', 'Info');
            location.reload();
        }

        function filterBookings(status) {
            const rows = document.querySelectorAll('#bookings table tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusBadge = row.querySelector('.status-badge');
                    const rowStatus = statusBadge.textContent.toLowerCase().trim();
                    row.style.display = rowStatus === status ? '' : 'none';
                }
            });
        }
    </script>
</body>
</html>