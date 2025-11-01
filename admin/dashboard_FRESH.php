<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Handle AJAX requests for notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_notification_read' || $_POST['action'] === 'mark_all_notifications_read') {
        header('Content-Type: application/json');
        
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit;
        }
        
        try {
            if ($_POST['action'] === 'mark_notification_read') {
                $notif_id = (int)$_POST['notification_id'];
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? OR id = ?");
                $stmt->execute([$notif_id, $notif_id]);
                echo json_encode(['success' => true]);
            } elseif ($_POST['action'] === 'mark_all_notifications_read') {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL OR type IN ('booking', 'payment', 'system')");
                $stmt->execute();
                echo json_encode(['success' => true]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle AJAX requests for user management
if ((isset($_GET['ajax']) && $_GET['ajax'] === 'users') || (isset($_GET['action']) && $_GET['action'] === 'view_user') || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action']))) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
        
        // determine available columns
        $dbName = $db->query('SELECT DATABASE()')->fetchColumn();
        $colStmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'users'");
        $colStmt->execute([':schema' => $dbName]);
        $availableCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!$availableCols) { $availableCols = ['id','username','email']; }

        $preferred = ['id','username','email','first_name','last_name','phone','wallet_balance','is_active','role','created_at','last_login','address','profile_picture','dob','email_verified'];
        $selectCols = array_values(array_intersect($preferred, $availableCols));
        if (empty($selectCols)) { $selectCols = array_slice($availableCols, 0, min(6, count($availableCols))); }
        $selectList = implode(', ', array_map(function($c){ return "`".str_replace('`','',$c)."`"; }, $selectCols));

        // handle POST admin actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
            $selfId = (int)($_SESSION['user_id'] ?? 0);
            $provided = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $provided)) { http_response_code(403); echo json_encode(['error'=>'Invalid CSRF token']); exit; }
            $action = $_POST['admin_action'];
            $target = (int)($_POST['user_id'] ?? 0);
            if ($target <= 0) { echo json_encode(['error'=>'Invalid user id']); exit; }

            if ($action === 'toggle_active') {
                $active = (int)($_POST['active'] ?? 0);
                if ($target === $selfId && $active === 0) { echo json_encode(['error'=>'Cannot deactivate your own account']); exit; }
                $up = $db->prepare('UPDATE `users` SET `is_active` = :active WHERE `id` = :id');
                $up->execute([':active'=>$active, ':id'=>$target]);
                echo json_encode(['success'=>true]); exit;
            }

            if ($action === 'reset_password') {
                try { $token = bin2hex(random_bytes(16)); } catch (Exception $e) { $token = bin2hex(openssl_random_pseudo_bytes(16)); }
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $up = $db->prepare('UPDATE `users` SET `reset_token` = :t, `reset_expires` = :e WHERE `id` = :id');
                $up->execute([':t'=>$token, ':e'=>$expires, ':id'=>$target]);
                echo json_encode(['success'=>true]); exit;
            }
            echo json_encode(['error'=>'Unknown action']); exit;
        }

        // view single user details
        if (isset($_GET['action']) && $_GET['action'] === 'view_user') {
            $uid = (int)($_GET['id'] ?? 0); if ($uid <= 0) { echo json_encode(['error'=>'Invalid id']); exit; }
            $detailCols = $selectList;
            $s = $db->prepare("SELECT $detailCols FROM `users` WHERE `id` = :id LIMIT 1"); $s->execute([':id'=>$uid]); $row = $s->fetch(PDO::FETCH_ASSOC);
            if (!$row) { echo json_encode(['error'=>'User not found']); exit; }
            try { $c = $db->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :uid'); $c->execute([':uid'=>$uid]); $row['bookings_count'] = (int)$c->fetchColumn(); } catch (Exception $e) { $row['bookings_count'] = 0; }
            try { $c = $db->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = :uid'); $c->execute([':uid'=>$uid]); $row['reviews_count'] = (int)$c->fetchColumn(); } catch (Exception $e) { $row['reviews_count'] = 0; }
            echo json_encode(['success'=>true,'user'=>$row]); exit;
        }

        // listing
        $page = max(1, (int)($_GET['page'] ?? 1)); $per_page = max(1, min(200, (int)($_GET['per_page'] ?? 20))); $offset = ($page -1) * $per_page;
        $q = trim((string)($_GET['q'] ?? ''));
        $where = ''; $params = [];
        if ($q !== '') { $parts = []; if (in_array('username', $availableCols)) $parts[] = "`username` LIKE :q"; if (in_array('email', $availableCols)) $parts[] = "`email` LIKE :q"; if (in_array('first_name', $availableCols)) $parts[] = "`first_name` LIKE :q"; if (in_array('last_name', $availableCols)) $parts[] = "`last_name` LIKE :q"; if ($parts) { $where = 'WHERE ('.implode(' OR ', $parts).')'; $params[':q'] = '%'.$q.'%'; } }
        $sql = "SELECT $selectList FROM `users` $where ORDER BY `created_at` DESC LIMIT :offset, :limit";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT); $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
        $stmt->execute(); $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $countSql = "SELECT COUNT(*) FROM `users` " . ($where ?: ''); $cstmt = $db->prepare($countSql); foreach ($params as $k=>$v) $cstmt->bindValue($k,$v); $cstmt->execute(); $total = (int)$cstmt->fetchColumn();
        echo json_encode(['success'=>true,'page'=>$page,'per_page'=>$per_page,'total'=>$total,'users'=>$users]); exit;
    } catch (Exception $e) {
        http_response_code(500); echo json_encode(['error'=>'Server error: ' . $e->getMessage()]); exit;
    }
}

// Get statistics
$admin_stats = $carDB->getAdminStats();

// Get additional stats
try {
    // Total users
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE status IN ('confirmed', 'in_progress')");
    $stmt->execute();
    $active_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending bookings
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
    $stmt->execute();
    $pending_bookings_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total revenue
    $stmt = $db->prepare("SELECT IFNULL(SUM(p.amount), 0) as revenue FROM payments p WHERE p.payment_status IN ('completed', 'paid', 'success')");
    $stmt->execute();
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
    
    // Success rate
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM bookings WHERE status IN ('pending', 'confirmed', 'completed')");
    $stmt->execute();
    $booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $success_rate = $booking_stats['total'] > 0 ? round(($booking_stats['completed'] / $booking_stats['total']) * 100) : 0;
    
    // Average revenue per user
    $avg_revenue = $total_users > 0 ? round($total_revenue / $total_users, 2) : 0;
    
    // Total transactions
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_status IN ('completed', 'paid', 'success')");
    $stmt->execute();
    $total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Revenue by month (last 6 months)
    $stmt = $db->prepare("SELECT 
        DATE_FORMAT(p.created_at, '%b') as month,
        IFNULL(SUM(p.amount), 0) as revenue
        FROM payments p
        WHERE p.payment_status IN ('completed', 'paid', 'success')
        AND p.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY MONTH(p.created_at)
        ORDER BY p.created_at ASC");
    $stmt->execute();
    $revenue_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Booking status breakdown
    $stmt = $db->prepare("SELECT 
        status,
        COUNT(*) as count
        FROM bookings
        WHERE status IN ('confirmed', 'pending', 'completed')
        GROUP BY status");
    $stmt->execute();
    $booking_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $active_count = 0;
    $pending_count = 0;
    $completed_count = 0;
    foreach ($booking_status as $bs) {
        if ($bs['status'] == 'confirmed') $active_count = $bs['count'];
        if ($bs['status'] == 'pending') $pending_count = $bs['count'];
        if ($bs['status'] == 'completed') $completed_count = $bs['count'];
    }
    
    // Popular services
    $stmt = $db->prepare("SELECT 
        s.service_name,
        COUNT(b.booking_id) as bookings
        FROM services s
        LEFT JOIN bookings b ON s.service_id = b.service_id
        GROUP BY s.service_id
        ORDER BY bookings DESC
        LIMIT 5");
    $stmt->execute();
    $popular_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily bookings (last 7 days)
    $stmt = $db->prepare("SELECT 
        DATE_FORMAT(created_at, '%a') as day,
        COUNT(*) as count
        FROM bookings
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY created_at ASC");
    $stmt->execute();
    $daily_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bookings data for bookings section
    $all_bookings = $carDB->getAllBookings(null, 50);
    $pending_bookings = $carDB->getAllBookings('pending', 20);
    
    // Get admin notifications
    $notifications = $carDB->getAdminNotifications(20);
    $unread_count = 0;
    foreach ($notifications as $notif) {
        if (!isset($notif['is_read']) || $notif['is_read'] == 0) {
            $unread_count++;
        }
    }
    
    // Analytics data
    // Revenue trends (last 12 months)
    $stmt = $db->prepare("SELECT 
        DATE_FORMAT(p.created_at, '%Y-%m') as month,
        DATE_FORMAT(p.created_at, '%b %Y') as month_name,
        IFNULL(SUM(p.amount), 0) as revenue,
        COUNT(p.payment_id) as transaction_count
        FROM payments p
        WHERE p.payment_status IN ('completed', 'paid', 'success')
        AND p.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(p.created_at, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $revenue_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Booking trends (last 12 months)
    $stmt = $db->prepare("SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        DATE_FORMAT(created_at, '%b %Y') as month_name,
        COUNT(*) as booking_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM bookings
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC");
    $stmt->execute();
    $booking_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Service performance
    $stmt = $db->prepare("SELECT 
        s.service_name,
        COUNT(b.booking_id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        IFNULL(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.review_id) as review_count,
        IFNULL(SUM(b.total_amount), 0) as total_revenue
        FROM services s
        LEFT JOIN bookings b ON s.service_id = b.service_id
        LEFT JOIN reviews r ON b.booking_id = r.booking_id
        GROUP BY s.service_id
        ORDER BY total_bookings DESC");
    $stmt->execute();
    $service_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Customer insights
    $stmt = $db->prepare("SELECT 
        COUNT(DISTINCT u.id) as total_customers,
        COUNT(DISTINCT CASE WHEN user_bookings.booking_count IS NOT NULL THEN u.id END) as active_customers,
        IFNULL(AVG(user_bookings.booking_count), 0) as avg_bookings_per_customer,
        IFNULL(MAX(user_bookings.booking_count), 0) as max_bookings_by_customer
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) as booking_count
            FROM bookings
            GROUP BY user_id
        ) user_bookings ON u.id = user_bookings.user_id
        WHERE u.role != 'admin'");
    $stmt->execute();
    $customer_insights = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Peak hours analysis
    $stmt = $db->prepare("SELECT 
        HOUR(booking_time) as hour,
        COUNT(*) as booking_count
        FROM bookings
        WHERE booking_time IS NOT NULL
        GROUP BY HOUR(booking_time)
        ORDER BY hour ASC");
    $stmt->execute();
    $peak_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top customers (by revenue)
    $stmt = $db->prepare("SELECT 
        u.username,
        u.email,
        COUNT(b.booking_id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
        IFNULL(SUM(b.total_amount), 0) as total_spent,
        MAX(b.created_at) as last_booking_date
        FROM users u
        INNER JOIN bookings b ON u.id = b.user_id
        WHERE u.role != 'admin'
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10");
    $stmt->execute();
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Payment method distribution
    $stmt = $db->prepare("SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total_amount
        FROM payments
        WHERE payment_status IN ('completed', 'paid', 'success')
        AND payment_method IS NOT NULL
        GROUP BY payment_method
        ORDER BY count DESC");
    $stmt->execute();
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Conversion rate (completed vs total bookings)
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM bookings");
    $stmt->execute();
    $conversion_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Average order value and lifetime value
    $stmt = $db->prepare("SELECT 
        AVG(total_amount) as avg_order_value,
        MAX(total_amount) as highest_order,
        MIN(total_amount) as lowest_order
        FROM bookings
        WHERE status = 'completed'");
    $stmt->execute();
    $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Dashboard query error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Display error in development
    echo "<!-- DEBUG ERROR: " . htmlspecialchars($e->getMessage()) . " -->";
    echo "<!-- File: " . $e->getFile() . " Line: " . $e->getLine() . " -->";
    
    $total_users = 0;
    $active_bookings = 0;
    $pending_bookings_count = 0;
    $total_revenue = 0;
    $success_rate = 0;
    $avg_revenue = 0;
    $total_transactions = 0;
    $revenue_by_month = [];
    $active_count = 0;
    $pending_count = 0;
    $completed_count = 0;
    $popular_services = [];
    $daily_bookings = [];
    $all_bookings = [];
    $pending_bookings = [];
    $revenue_trends = [];
    $booking_trends = [];
    $service_performance = [];
    $customer_insights = ['total_customers'=>0,'active_customers'=>0,'avg_bookings_per_customer'=>0,'max_bookings_by_customer'=>0];
    $peak_hours = [];
    $top_customers = [];
    $payment_methods = [];
    $conversion_stats = ['total_bookings'=>0,'completed'=>0,'cancelled'=>0,'pending'=>0];
    $order_stats = ['avg_order_value'=>0,'highest_order'=>0,'lowest_order'=>0];
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (Exception $e) { $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Car Detailing Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a1a;
            --bg-card: #202020;
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --text-tertiary: #606060;
            --accent-gold: #FFD700;
            --accent-green: #32CD32;
            --accent-yellow: #FFD700;
            --accent-cyan: #00CED1;
            --border-color: #2a2a2a;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #1a1a1a 0%, #2a2a2a 100%);
            border-right: 1px solid #333;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            text-align: center;
            flex-shrink: 0;
        }

        .logo {
            color: #FFD700;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .nav-menu {
            padding: 20px 0;
            flex: 1;
        }

        .nav-footer {
            padding: 20px 0;
            border-top: 1px solid #333;
            margin-top: auto;
            flex-shrink: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #cccccc;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            border-left-color: #FFD700;
        }

        .nav-link.active {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
            border-left-color: #FFD700;
        }

        .nav-link i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .sidebar-badge {
            background: #ff4757;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 700;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--accent-gold);
            color: #000;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .breadcrumb {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .breadcrumb span {
            color: var(--accent-gold);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notification-btn {
            position: relative;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 18px;
        }

        .notification-btn:hover {
            background: var(--bg-secondary);
            border-color: var(--accent-gold);
            transform: translateY(-1px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            min-width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            animation: pulse 2s infinite;
            border: 2px solid var(--bg-primary);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            width: 360px;
            max-height: 500px;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            display: none;
        }

        .notification-dropdown.active {
            display: block;
        }

        .notification-dropdown-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-dropdown-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--accent-gold);
            margin: 0;
        }

        .notification-item {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s;
        }

        .notification-item:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .notification-item.unread {
            background: rgba(255, 215, 0, 0.08);
        }

        .notification-item-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .notification-item-message {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            line-height: 1.4;
        }

        .notification-item-time {
            font-size: 11px;
            color: var(--text-tertiary);
        }

        .notification-dropdown-footer {
            padding: 12px 20px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .notification-dropdown-footer a {
            color: var(--accent-gold);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .notification-dropdown-footer a:hover {
            text-decoration: underline;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #000;
            cursor: pointer;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
        }

        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gold);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-title {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .stat-icon {
            font-size: 24px;
            color: var(--text-tertiary);
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--text-tertiary);
        }

        /* Performance Overview */
        .performance-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .performance-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .performance-title {
            font-size: 16px;
            font-weight: 600;
        }

        .performance-icon {
            color: var(--accent-gold);
        }

        .performance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .performance-item {
            text-align: center;
        }

        .performance-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .performance-value {
            font-size: 28px;
            font-weight: 700;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .chart-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 600;
        }

        .chart-icon {
            font-size: 16px;
        }

        canvas {
            max-height: 250px !important;
        }

        /* Bottom Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .list-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .list-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .list-title {
            font-size: 14px;
            font-weight: 600;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-label {
            font-size: 13px;
            color: var(--text-primary);
        }

        .list-item-bar {
            flex: 1;
            max-width: 200px;
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            margin: 0 16px;
            overflow: hidden;
        }

        .list-item-fill {
            height: 100%;
            background: var(--accent-gold);
            border-radius: 4px;
        }

        .list-item-value {
            font-size: 13px;
            font-weight: 600;
            color: var(--accent-gold);
            min-width: 50px;
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .charts-section,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .performance-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .mobile-menu-btn {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .container {
                padding: 16px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .content-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--accent-gold);
        }

        .card-content {
            padding: 24px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--accent-gold);
            color: #000;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #FFC700;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table thead {
            background: var(--bg-secondary);
        }

        .data-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--accent-gold);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .data-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .data-table td[data-status]::before {
            content: attr(data-status-text);
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .data-table td[data-status="active"]::before {
            background: rgba(50, 205, 50, 0.2);
            color: #32CD32;
        }

        .data-table td[data-status="inactive"]::before {
            background: rgba(255, 99, 71, 0.2);
            color: #FF6347;
        }

        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent-gold);
            transition: all 0.3s;
        }

        .btn-action:hover {
            background: var(--accent-gold);
            color: #000;
        }

        .btn-action.btn-info {
            background: rgba(0, 206, 209, 0.1);
            color: var(--accent-cyan);
        }

        .btn-action.btn-info:hover {
            background: var(--accent-cyan);
            color: #000;
        }

        input[type="search"], select {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: var(--accent-gold);
            font-size: 18px;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 24px;
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
        }

        /* Bookings specific styles */
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
        }

        .status-confirmed {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }

        .status-completed {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .vehicle-badge {
            padding: 4px 8px;
            background: rgba(255, 215, 0, 0.1);
            color: var(--accent-gold);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .datetime-info {
            font-size: 13px;
        }

        .datetime-info div {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 2px 0;
        }

        .datetime-info i {
            font-size: 11px;
            opacity: 0.7;
        }

        .amount {
            color: var(--accent-gold);
            font-weight: 700;
        }

        .btn-confirm {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .btn-confirm:hover {
            background: #4CAF50;
            color: #000;
        }

        .btn-decline {
            background: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        .btn-decline:hover {
            background: #F44336;
            color: #fff;
        }

        /* Analytics specific styles */
        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-cyan));
        }

        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent-gold);
            margin: 8px 0;
        }

        .metric-label {
            font-size: 13px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-change {
            font-size: 12px;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .metric-change.positive {
            color: var(--accent-green);
        }

        .metric-change.negative {
            color: #F44336;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .performance-table {
            width: 100%;
        }

        .performance-table td {
            vertical-align: middle;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            overflow: hidden;
            margin: 4px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-gold), var(--accent-cyan));
            border-radius: 4px;
            transition: width 0.3s;
        }

        .rating-stars {
            color: var(--accent-gold);
        }

        .insight-card {
            background: var(--bg-secondary);
            border-left: 4px solid var(--accent-gold);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .insight-title {
            font-weight: 600;
            color: var(--accent-gold);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .insight-content {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* Additional Analytics CSS */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
        }
        
        .chart-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 24px;
        }
        
        .chart-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .chart-icon {
            font-size: 20px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .bottom-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
        }
        
        .performance-table td:nth-child(1) {
            text-align: center;
        }
        
        .performance-table .amount {
            color: var(--accent-gold);
            font-weight: 600;
        }
        
        @media (max-width: 1200px) {
            .bottom-grid {
                grid-template-columns: 1fr;
            }
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        
        /* Notifications Styles */
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-card {
            display: flex;
            gap: 16px;
            padding: 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .notification-card:hover {
            background: rgba(255, 215, 0, 0.05);
            border-color: var(--accent-gold);
            transform: translateX(4px);
        }

        .notification-card.notification-unread {
            background: rgba(255, 215, 0, 0.08);
            border-left: 4px solid var(--accent-gold);
        }

        .notification-icon {
            font-size: 24px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-card);
            border-radius: 12px;
            flex-shrink: 0;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: #ff4757;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        .notification-message {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 8px;
        }

        .notification-time {
            font-size: 12px;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .notification-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notification-icon {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="logo">
                <i class="fas fa-car"></i>
                Ride Revive Admin
            </a>
        </div>
        <div class="nav-menu">
            <a href="#" class="nav-link active" onclick="showSection('dashboard', this); return false;">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#" class="nav-link" onclick="showSection('bookings', this); return false;">
                <i class="fas fa-calendar-check"></i>
                Bookings
            </a>
            <a href="#" class="nav-link" onclick="showSection('notifications', this); return false;">
                <i class="fas fa-bell"></i>
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="sidebar-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="reviews_manage.php" class="nav-link">
                <i class="fas fa-star"></i>
                Reviews
            </a>
            <a href="#" class="nav-link" onclick="showSection('users', this); return false;">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="#" class="nav-link" onclick="showSection('services', this); return false;">
                <i class="fas fa-car-wash"></i>
                Services
            </a>
            <a href="#" class="nav-link" onclick="showSection('analytics', this); return false;">
                <i class="fas fa-chart-line"></i>
                Analytics
            </a>
        </div>
        <div class="nav-footer">
            <a href="#" class="nav-link" onclick="showSection('settings', this); return false;">
                <i class="fas fa-cogs"></i>
                Settings
            </a>
            <a href="../auth/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
    <div class="container">
        <!-- Dashboard Section -->
        <section id="dashboard" class="content-section active">
        <!-- Header -->
        <header class="header">
            <div>
                <div class="breadcrumb">Dashboard / <span>Overview</span></div>
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Welcome back! Here's your business summary.</p>
            </div>
            <div class="user-menu">
                <!-- Notification Bell -->
                <div class="notification-btn" onclick="toggleNotificationDropdown(event)">
                    <i class="fas fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-dropdown-header">
                        <h3>Notifications</h3>
                        <span style="font-size: 12px; color: var(--text-secondary);"><?php echo $unread_count; ?> new</span>
                    </div>
                    <div class="notification-dropdown-body">
                        <?php if (empty($notifications)): ?>
                            <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                                <i class="fas fa-bell" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px;"></i>
                                <p>No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $preview_notifications = array_slice($notifications, 0, 5);
                            foreach ($preview_notifications as $notif): 
                                $is_read = isset($notif['is_read']) ? (int)$notif['is_read'] : 0;
                                $title = $notif['title'] ?? $notif['notification_title'] ?? 'Notification';
                                $message = $notif['message'] ?? $notif['notification_message'] ?? '';
                                $created = $notif['created_at'] ?? $notif['created'] ?? null;
                                $notif_id = $notif['notification_id'] ?? $notif['id'] ?? '';
                            ?>
                                <div class="notification-item <?php echo $is_read ? '' : 'unread'; ?>" 
                                     data-notif-id="<?php echo htmlspecialchars($notif_id); ?>"
                                     onclick="markNotificationRead(<?php echo $notif_id; ?>)">
                                    <div class="notification-item-title"><?php echo htmlspecialchars($title); ?></div>
                                    <div class="notification-item-message"><?php echo htmlspecialchars(substr($message, 0, 80)) . (strlen($message) > 80 ? '...' : ''); ?></div>
                                    <div class="notification-item-time">
                                        <?php 
                                        if ($created) {
                                            $time_ago = time() - strtotime($created);
                                            if ($time_ago < 60) echo 'Just now';
                                            elseif ($time_ago < 3600) echo floor($time_ago / 60) . 'm ago';
                                            elseif ($time_ago < 86400) echo floor($time_ago / 3600) . 'h ago';
                                            else echo date('M j, g:i A', strtotime($created));
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (count($notifications) > 5): ?>
                        <div class="notification-dropdown-footer">
                            <a href="#" onclick="showSection('notifications'); closeNotificationDropdown(); return false;">View all notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-info">
                    <div class="user-name">JL Robles</div>
                    <div class="user-role">Administrator</div>
                </div>
                <div class="user-avatar">JR</div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Users</div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="stat-value"><?= number_format($total_users) ?></div>
                <div class="stat-label">Registered members</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Bookings</div>
                    <i class="fas fa-clipboard-check stat-icon"></i>
                </div>
                <div class="stat-value"><?= number_format($active_bookings) ?></div>
                <div class="stat-label">Currently active</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Pending Bookings</div>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
                <div class="stat-value"><?= number_format($pending_bookings_count) ?></div>
                <div class="stat-label">Awaiting confirmation</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Revenue</div>
                    <i class="fas fa-peso-sign stat-icon"></i>
                </div>
                <div class="stat-value">₱<?= number_format($total_revenue, 2) ?></div>
                <div class="stat-label">All-time earnings</div>
            </div>
        </div>

        <!-- Performance Overview -->
        <div class="performance-section">
            <div class="performance-header">
                <i class="fas fa-chart-line performance-icon"></i>
                <h2 class="performance-title">Performance Overview</h2>
            </div>
            <div class="performance-grid">
                <div class="performance-item">
                    <div class="performance-label">Success Rate</div>
                    <div class="performance-value"><?= $success_rate ?>%</div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Avg Revenue/User</div>
                    <div class="performance-value">₱<?= number_format($avg_revenue, 2) ?></div>
                </div>
                <div class="performance-item">
                    <div class="performance-label">Total Transactions</div>
                    <div class="performance-value"><?= number_format($total_transactions) ?></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <!-- Monthly Revenue Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-line chart-icon" style="color: var(--accent-gold);"></i>
                    <h3 class="chart-title">Monthly Revenue</h3>
                </div>
                <canvas id="revenueChart"></canvas>
            </div>

            <!-- Booking Status Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-pie chart-icon" style="color: var(--accent-cyan);"></i>
                    <h3 class="chart-title">Booking Status</h3>
                </div>
                <canvas id="bookingStatusChart"></canvas>
            </div>
        </div>

        <!-- Bottom Grid -->
        <div class="bottom-grid">
            <!-- Popular Services -->
            <div class="list-card">
                <div class="list-header">
                    <i class="fas fa-concierge-bell chart-icon" style="color: var(--accent-gold);"></i>
                    <h3 class="list-title">Popular Services</h3>
                </div>
                <div>
                    <?php 
                    $max_bookings = !empty($popular_services) ? max(array_column($popular_services, 'bookings')) : 1;
                    foreach ($popular_services as $ps): 
                        $percentage = $max_bookings > 0 ? ($ps['bookings'] / $max_bookings) * 100 : 0;
                    ?>
                        <div class="list-item">
                            <div class="list-item-label"><?= htmlspecialchars($ps['service_name']) ?></div>
                            <div class="list-item-bar">
                                <div class="list-item-fill" style="width: <?= $percentage ?>%;"></div>
                            </div>
                            <div class="list-item-value"><?= $ps['bookings'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Daily Bookings -->
            <div class="list-card">
                <div class="list-header">
                    <i class="fas fa-calendar-alt chart-icon" style="color: var(--accent-green);"></i>
                    <h3 class="list-title">Daily Bookings (Last 7 Days)</h3>
                </div>
                <canvas id="dailyBookingsChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Chart.js default config
        Chart.defaults.color = '#a0a0a0';
        Chart.defaults.borderColor = '#2a2a2a';

        // Revenue Chart
        const revenueData = <?= json_encode(array_column($revenue_by_month, 'revenue')) ?>;
        const revenueLabels = <?= json_encode(array_column($revenue_by_month, 'month')) ?>;

        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: revenueLabels.length > 0 ? revenueLabels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: revenueData.length > 0 ? revenueData : [0, 0, 0, 0, 0, 0],
                    borderColor: '#FFD700',
                    backgroundColor: 'rgba(255, 215, 0, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#FFD700'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        titleColor: '#ffffff',
                        bodyColor: '#a0a0a0',
                        borderColor: '#2a2a2a',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#1a1a1a' },
                        ticks: {
                            callback: function(value) {
                                return '₱' + (value/1000).toFixed(0) + 'K';
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Booking Status Chart
        new Chart(document.getElementById('bookingStatusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Completed'],
                datasets: [{
                    data: [<?= $active_count ?>, <?= $pending_count ?>, <?= $completed_count ?>],
                    backgroundColor: ['#32CD32', '#FFD700', '#00CED1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        titleColor: '#ffffff',
                        bodyColor: '#a0a0a0',
                        borderColor: '#2a2a2a',
                        borderWidth: 1,
                        padding: 12
                    }
                }
            }
        });

        // Daily Bookings Chart
        const dailyData = <?= json_encode(array_column($daily_bookings, 'count')) ?>;
        const dailyLabels = <?= json_encode(array_column($daily_bookings, 'day')) ?>;

        new Chart(document.getElementById('dailyBookingsChart'), {
            type: 'bar',
            data: {
                labels: dailyLabels.length > 0 ? dailyLabels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Bookings',
                    data: dailyData.length > 0 ? dailyData : [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: '#32CD32',
                    borderRadius: 4,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        titleColor: '#ffffff',
                        bodyColor: '#a0a0a0',
                        borderColor: '#2a2a2a',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#1a1a1a' },
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
        </section>
        <!-- End Dashboard Section -->

        <!-- Notifications Section -->
        <section id="notifications" class="content-section">
            <header class="header" style="margin-bottom: 24px;">
                <div>
                    <div class="breadcrumb">Dashboard / <span>Notifications</span></div>
                    <h1 class="page-title">Notifications</h1>
                    <p class="page-subtitle">Stay updated with system alerts and important updates</p>
                </div>
            </header>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-bell"></i> All Notifications</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-primary" onclick="markAllNotificationsRead()">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notif): 
                                $is_read = isset($notif['is_read']) ? (int)$notif['is_read'] : 0;
                                $title = $notif['title'] ?? $notif['notification_title'] ?? 'Notification';
                                $message = $notif['message'] ?? $notif['notification_message'] ?? '';
                                $type = $notif['type'] ?? 'info';
                                $created = $notif['created_at'] ?? $notif['created'] ?? null;
                                $notif_id = $notif['notification_id'] ?? $notif['id'] ?? '';
                                
                                // Icon based on type
                                $icon = 'fa-bell';
                                $icon_color = 'var(--accent-cyan)';
                                if ($type == 'booking') { $icon = 'fa-calendar-check'; $icon_color = 'var(--accent-gold)'; }
                                elseif ($type == 'payment') { $icon = 'fa-money-bill-wave'; $icon_color = '#32CD32'; }
                                elseif ($type == 'system') { $icon = 'fa-cog'; $icon_color = 'var(--accent-cyan)'; }
                                elseif ($type == 'alert') { $icon = 'fa-exclamation-circle'; $icon_color = '#ff4757'; }
                            ?>
                                <div class="notification-card <?php echo $is_read ? '' : 'notification-unread'; ?>" 
                                     data-notif-id="<?php echo htmlspecialchars($notif_id); ?>"
                                     onclick="markNotificationRead(<?php echo $notif_id; ?>)">
                                    <div class="notification-icon" style="color: <?php echo $icon_color; ?>;">
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">
                                            <?php echo htmlspecialchars($title); ?>
                                            <?php if (!$is_read): ?>
                                                <span class="unread-dot"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-message"><?php echo htmlspecialchars($message); ?></div>
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php 
                                            if ($created) {
                                                echo date('F j, Y g:i A', strtotime($created));
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Bookings Section -->
        <section id="bookings" class="content-section">
            <div class="stats-grid" style="margin-bottom: 24px;">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Pending Bookings</div>
                        <i class="fas fa-clock stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo count($pending_bookings); ?></div>
                    <div class="stat-label">Awaiting approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Completed</div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                    <div class="stat-value"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Successfully finished</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Today's Revenue</div>
                        <i class="fas fa-peso-sign stat-icon"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($admin_stats['today_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Today's earnings</div>
                </div>
            </div>

            <!-- Pending Bookings -->
            <?php if (!empty($pending_bookings)): ?>
                <div class="content-card" style="margin-bottom: 24px;">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Pending Bookings - Action Required</h3>
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-content">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
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
                                                <div class="customer-info">
                                                    <i class="fas fa-user"></i>
                                                    <?php echo htmlspecialchars($booking['customer_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td>
                                                <span class="vehicle-badge">
                                                    <?php echo ucfirst($booking['vehicle_size'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="datetime-info">
                                                    <div><i class="fas fa-calendar"></i><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                                    <div><i class="fas fa-clock"></i><?php echo date('h:i A', strtotime($booking['booking_time'] ?? $booking['booking_date'])); ?></div>
                                                </div>
                                            </td>
                                            <td class="amount">₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <button class="btn-action btn-confirm" onclick="confirmBooking(<?php echo $booking['id']; ?>)" title="Confirm">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn-action btn-decline" onclick="declineBooking(<?php echo $booking['id']; ?>)" title="Decline">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button class="btn-action btn-info" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="Details">
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
            <?php endif; ?>

            <!-- All Bookings -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> All Bookings</h3>
                    <select onchange="filterBookings(this.value)" class="btn">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table" id="allBookingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_bookings as $booking): ?>
                                    <tr data-status="<?php echo $booking['status']; ?>">
                                        <td>#<?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['booking_reference'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td class="amount">₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($booking['payment_status'] ?? 'pending'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                            <?php if ($booking['status'] === 'confirmed'): ?>
                                                <button class="btn-action btn-primary" title="Mark Completed" onclick="markBookingCompleted(<?php echo (int)$booking['id']; ?>)" style="margin-left:8px;">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                function filterBookings(status) {
                    const table = document.getElementById('allBookingsTable');
                    const rows = table.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        if (status === 'all') {
                            row.style.display = '';
                        } else {
                            row.style.display = row.dataset.status === status ? '' : 'none';
                        }
                    });
                }

                async function confirmBooking(id) {
                    if (!confirm('Confirm this booking?')) return;
                    
                    const fd = new FormData();
                    fd.append('booking_id', id);
                    fd.append('action', 'confirm');
                    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
                    
                    try {
                        const res = await fetch('../admin/update_booking_status.php', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert('Booking confirmed successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Network error');
                    }
                }

                async function declineBooking(id) {
                    if (!confirm('Decline this booking?')) return;
                    
                    const fd = new FormData();
                    fd.append('booking_id', id);
                    fd.append('action', 'decline');
                    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
                    
                    try {
                        const res = await fetch('../admin/update_booking_status.php', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert('Booking declined');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Network error');
                    }
                }

                async function markBookingCompleted(id) {
                    if (!confirm('Mark this booking as completed?')) return;
                    
                    const fd = new FormData();
                    fd.append('booking_id', id);
                    fd.append('action', 'complete');
                    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');
                    
                    try {
                        const res = await fetch('../admin/update_booking_status.php', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (data.success) {
                            alert('Booking marked as completed!');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Network error');
                    }
                }

                async function viewBookingDetails(id) {
                    try {
                        const res = await fetch('../admin/get_booking_details.php?id=' + id, {
                            credentials: 'same-origin'
                        });
                        const data = await res.json();
                        if (data.success && data.booking) {
                            const b = data.booking;
                            let html = '<div style="padding:8px;color:var(--text-primary);">';
                            html += '<table style="width:100%;border-collapse:collapse;color:var(--text-primary);">';
                            const fields = ['booking_reference','customer_name','email','phone','service_name','vehicle_size','booking_date','booking_time','total_amount','payment_method','payment_status','status','special_requests'];
                            fields.forEach(f => {
                                if (typeof b[f] !== 'undefined') {
                                    let val = b[f];
                                    if (f === 'total_amount') val = '₱' + Number(val).toLocaleString();
                                    html += '<tr><td style="padding:6px 8px;width:35%;font-weight:700;color:var(--accent-gold);">' + f.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</td><td style="padding:6px 8px;">' + (val || 'N/A') + '</td></tr>';
                                }
                            });
                            html += '</table></div>';
                            showModal(html, 'Booking Details');
                        } else {
                            alert('Booking not found');
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Network error');
                    }
                }
            </script>
        </section>

        <!-- Users Section -->
        <section id="users" class="content-section">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> User Management</h3>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input id="usersSearchInput" type="search" placeholder="Search username, name or email" style="min-width:260px;">
                        <select id="usersPerPage">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                        </select>
                        <button id="usersReloadBtn" class="btn btn-primary" title="Reload users">Reload</button>
                    </div>
                </div>
                <div class="card-content">
                    <div style="padding:12px 20px;color:var(--text-secondary);font-size:13px;">Manage system users, view details, and control access.</div>
                    <div id="usersArea">
                        <div id="usersTableWrapper" class="table-responsive" style="display:none;">
                            <table id="usersTable" class="data-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Wallet Balance</th>
                                        <th>Status</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="usersEmpty" class="empty-state" style="display:none;">
                            <i class="fas fa-users"></i>
                            <h3>No users found</h3>
                            <p>Try adjusting your search or reload the list.</p>
                        </div>
                        <div id="usersLoading" style="padding:24px;display:none;color:var(--text-secondary);">Loading users...</div>
                        <div id="usersPagination" style="margin-top:12px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;"></div>
                    </div>
                </div>
            </div>

            <script>
            (function(){
                const ADMIN_CSRF = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>';
                let _lastPage = 1;
                const usersCache = { loaded: false, page: 1, perPage: 20, q: '' };

                async function fetchUsers(page=1) {
                    _lastPage = page;
                    const perPage = parseInt(document.getElementById('usersPerPage').value || '20', 10);
                    const qRaw = (document.getElementById('usersSearchInput').value || '').trim();
                    const q = encodeURIComponent(qRaw);
                    const wrapper = document.getElementById('usersTableWrapper');
                    const empty = document.getElementById('usersEmpty');
                    const loading = document.getElementById('usersLoading');
                    const pagination = document.getElementById('usersPagination');

                    if (usersCache.loaded && usersCache.page === page && usersCache.perPage === perPage && usersCache.q === qRaw) {
                        if (wrapper) wrapper.style.display = 'block';
                        if (empty) empty.style.display = 'none';
                        return;
                    }

                    if (wrapper) wrapper.style.display='none'; if (empty) empty.style.display='none'; if (pagination) pagination.innerHTML=''; if (loading) loading.style.display='block';
                    try {
                        const res = await fetch('?ajax=users&page='+encodeURIComponent(page)+'&per_page='+encodeURIComponent(perPage)+'&q='+q, { credentials:'same-origin' });
                        if (!res.ok) { alert('Failed to load users: '+res.status); return; }
                        const j = await res.json();
                        if (!j || !j.success) { alert('Error: '+(j && j.error? j.error : 'unknown')); return; }
                        renderUsers(j.users || []);
                        renderPagination(j.total||0, page, perPage);
                        usersCache.loaded = true; usersCache.page = page; usersCache.perPage = perPage; usersCache.q = qRaw;
                    } catch (e) { console.error(e); alert('Network error loading users'); }
                    finally { if (loading) loading.style.display='none'; }
                }

                function renderUsers(users) {
                    const wrapper = document.getElementById('usersTableWrapper');
                    const empty = document.getElementById('usersEmpty');
                    const tbody = document.querySelector('#usersTable tbody');
                    if (!tbody) return;
                    tbody.innerHTML = '';
                    if (!users || users.length === 0) { if (wrapper) wrapper.style.display='none'; if (empty) empty.style.display='block'; return; }
                    if (empty) empty.style.display='none'; if (wrapper) wrapper.style.display='block';
                    users.forEach(u => {
                        const tr = document.createElement('tr');
                        const username = u.username || '';
                        const full = ((u.first_name||'') + ' ' + (u.last_name||'')).trim();
                        const email = u.email || '';
                        const phone = u.phone || '';
                        const wallet = (typeof u.wallet_balance !== 'undefined') ? ('₱'+Number(u.wallet_balance).toLocaleString()) : '';
                        const status = (u.is_active == 1 || u.is_active === '1') ? 'Active' : 'Inactive';
                        const role = u.role || '';
                        const created = u.created_at || '';
                        const last = u.last_login || '';
                        const id = u.id || u.ID || u.user_id || '';

                        [username, full, email, phone].forEach((val) => { const td = document.createElement('td'); td.textContent = val; tr.appendChild(td); });
                        const tdWallet = document.createElement('td'); tdWallet.textContent = wallet; tr.appendChild(tdWallet);
                        const tdStatus = document.createElement('td'); 
                        tdStatus.setAttribute('data-status-text', status);
                        tdStatus.setAttribute('data-status', status.toLowerCase());
                        tr.appendChild(tdStatus);
                        const tdRole = document.createElement('td'); tdRole.textContent = role; tr.appendChild(tdRole);
                        const tdCreated = document.createElement('td'); tdCreated.textContent = created; tr.appendChild(tdCreated);
                        const tdLast = document.createElement('td'); tdLast.textContent = last; tr.appendChild(tdLast);

                        const tdActions = document.createElement('td');
                        const vbtn = document.createElement('button'); vbtn.className='btn-action btn-info'; vbtn.title='View Details'; vbtn.innerHTML='<i class="fas fa-eye"></i>'; vbtn.onclick = ()=> viewUser(id);
                        tdActions.appendChild(vbtn);
                        const toggle = document.createElement('button'); toggle.className='btn-action'; toggle.style.marginLeft='6px'; toggle.innerHTML = u.is_active==1 ? '<i class="fas fa-user-slash"></i>' : '<i class="fas fa-user-check"></i>';
                        toggle.title = u.is_active==1 ? 'Deactivate' : 'Reactivate';
                        toggle.onclick = ()=> { if (confirm((u.is_active==1?'Deactivate':'Reactivate')+' user '+username+'?')) { toggleActive(id, u.is_active==1?0:1); } };
                        tdActions.appendChild(toggle);
                        const rbtn = document.createElement('button'); rbtn.className='btn-action'; rbtn.style.marginLeft='6px'; rbtn.innerHTML='<i class="fas fa-key"></i>'; rbtn.title='Reset password'; rbtn.onclick = ()=> { if (confirm('Reset password for '+username+'?')) resetPassword(id); };
                        tdActions.appendChild(rbtn);

                        tr.appendChild(tdActions);
                        tbody.appendChild(tr);
                    });
                }

                function renderPagination(total, page, perPage) {
                    const container = document.getElementById('usersPagination'); if (!container) return; container.innerHTML='';
                    const pages = Math.max(1, Math.ceil(total / perPage));
                    const info = document.createElement('div'); info.style.color='var(--text-secondary)'; info.style.marginRight='8px'; info.textContent = 'Showing page '+page+' of '+pages+' — '+total+' users'; container.appendChild(info);
                    const prev = document.createElement('button'); prev.className='btn'; prev.textContent='Prev'; prev.disabled = page<=1; prev.onclick = ()=> fetchUsers(page-1); container.appendChild(prev);
                    const start = Math.max(1, page-3); const end = Math.min(pages, page+3);
                    for (let p=start;p<=end;p++){ const b=document.createElement('button'); b.className='btn'; b.textContent=p; if (p===page){ b.disabled=true; b.style.fontWeight='700'; } b.onclick=(function(pp){return function(){fetchUsers(pp);};})(p); container.appendChild(b); }
                    const next = document.createElement('button'); next.className='btn'; next.textContent='Next'; next.disabled = page>=pages; next.onclick = ()=> fetchUsers(page+1); container.appendChild(next);
                }

                async function viewUser(id) {
                    if (!id) return alert('Missing user id');
                    try {
                        const res = await fetch('?action=view_user&id='+encodeURIComponent(id), { credentials:'same-origin' });
                        if (!res.ok) return alert('Failed to load details');
                        const j = await res.json(); if (!j || !j.success) return alert('User not found');
                        const u = j.user; let html = '<div style="padding:8px;color:var(--text-primary);">';
                        html += '<table style="width:100%;border-collapse:collapse;color:var(--text-primary);">';
                        const fields = ['username','first_name','last_name','email','phone','address','dob','email_verified','wallet_balance','bookings_count','reviews_count','created_at','last_login'];
                        fields.forEach(f=>{ if (typeof u[f] !== 'undefined') { html += '<tr><td style="padding:6px 8px;width:35%;font-weight:700;color:var(--accent-gold);">'+escapeHtml(f.replace(/_/g,' '))+'</td><td style="padding:6px 8px;">'+escapeHtml(String(u[f]||''))+'</td></tr>'; }});
                        html += '</table></div>';
                        showModal(html, 'User Details');
                    } catch (e) { console.error(e); alert('Network error'); }
                }

                async function toggleActive(userId, active) {
                    const fd = new FormData(); fd.append('admin_action','toggle_active'); fd.append('user_id', userId); fd.append('active', active); fd.append('csrf_token', ADMIN_CSRF);
                    try { const res = await fetch(location.pathname, { method:'POST', body: fd, credentials:'same-origin' }); const j = await res.json(); if (j && j.success) { alert('Action successful'); usersCache.loaded = false; fetchUsers(_lastPage); } else alert('Error: '+(j && j.error? j.error: 'unknown')); } catch (e) { console.error(e); alert('Network error'); }
                }

                async function resetPassword(userId) {
                    const fd = new FormData(); fd.append('admin_action','reset_password'); fd.append('user_id', userId); fd.append('csrf_token', ADMIN_CSRF);
                    try { const res = await fetch(location.pathname, { method:'POST', body: fd, credentials:'same-origin' }); const j = await res.json(); if (j && j.success) { alert('Password reset token set'); usersCache.loaded = false; fetchUsers(_lastPage); } else alert('Error: '+(j && j.error? j.error: 'unknown')); } catch (e) { console.error(e); alert('Network error'); }
                }

                function escapeHtml(s){ return String(s||'').replace(/[&<>'"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[c]; }); }

                document.getElementById('usersReloadBtn').addEventListener('click', ()=> { usersCache.loaded=false; fetchUsers(1); });
                document.getElementById('usersPerPage').addEventListener('change', ()=> { usersCache.loaded=false; fetchUsers(1); });
                document.getElementById('usersSearchInput').addEventListener('keydown', function(e){ if (e.key === 'Enter') { usersCache.loaded=false; fetchUsers(1); } });

                window.fetchUsers = fetchUsers;
            })();
            </script>
        </section>

        <!-- Services Section -->
        <section id="services" class="content-section">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-car-wash"></i> Services Management</h3>
                </div>
                <div class="card-content">
                    <div class="empty-state">
                        <i class="fas fa-car-wash"></i>
                        <h3>Services Management Coming Soon</h3>
                        <p>This feature is under development.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Analytics Section -->
        <section id="analytics" class="content-section">
            <!-- Key Performance Metrics -->
            <div class="analytics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-value">₱<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> All Time
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Conversion Rate</div>
                    <div class="metric-value">
                        <?php 
                        $conversion_rate = $conversion_stats['total_bookings'] > 0 
                            ? round(($conversion_stats['completed'] / $conversion_stats['total_bookings']) * 100, 1) 
                            : 0;
                        echo $conversion_rate;
                        ?>%
                    </div>
                    <div class="metric-change <?php echo $conversion_rate >= 50 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-<?php echo $conversion_rate >= 50 ? 'check' : 'exclamation'; ?>"></i> 
                        <?php echo $conversion_stats['completed']; ?> / <?php echo $conversion_stats['total_bookings']; ?> completed
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Avg Order Value</div>
                    <div class="metric-value">₱<?php echo number_format($order_stats['avg_order_value'] ?? 0, 2); ?></div>
                    <div class="metric-change">
                        <i class="fas fa-receipt"></i> Per booking
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Active Customers</div>
                    <div class="metric-value"><?php echo $customer_insights['active_customers']; ?></div>
                    <div class="metric-change">
                        <i class="fas fa-users"></i> of <?php echo $customer_insights['total_customers']; ?> total
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Avg Bookings/Customer</div>
                    <div class="metric-value"><?php echo number_format($customer_insights['avg_bookings_per_customer'] ?? 0, 1); ?></div>
                    <div class="metric-change">
                        <i class="fas fa-chart-bar"></i> Customer retention
                    </div>
                </div>
            </div>

            <!-- Revenue & Booking Trends -->
            <div class="charts-section" style="margin-bottom: 24px;">
                <div class="chart-card">
                    <div class="chart-header">
                        <i class="fas fa-chart-line chart-icon" style="color: var(--accent-gold);"></i>
                        <h3 class="chart-title">Revenue Trends (12 Months)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <i class="fas fa-calendar-alt chart-icon" style="color: var(--accent-cyan);"></i>
                        <h3 class="chart-title">Booking Trends (12 Months)</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="bookingTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Service Performance Analysis -->
            <div class="content-card" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Service Performance Analysis</h3>
                    <span style="font-size: 13px; color: var(--text-secondary);">Ranking by bookings, revenue & ratings</span>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table performance-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Service</th>
                                    <th>Bookings</th>
                                    <th>Completion Rate</th>
                                    <th>Revenue</th>
                                    <th>Avg Rating</th>
                                    <th>Reviews</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($service_performance as $sp): 
                                    $completion_rate = $sp['total_bookings'] > 0 
                                        ? round(($sp['completed_bookings'] / $sp['total_bookings']) * 100) 
                                        : 0;
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo $rank++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($sp['service_name']); ?></td>
                                        <td><?php echo $sp['total_bookings']; ?></td>
                                        <td>
                                            <div><?php echo $completion_rate; ?>%</div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="amount">₱<?php echo number_format($sp['total_revenue'], 2); ?></td>
                                        <td>
                                            <span class="rating-stars">
                                                <?php 
                                                $rating = round($sp['avg_rating'], 1);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '★' : '☆';
                                                }
                                                echo ' ' . $rating;
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo $sp['review_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="bottom-grid">
                <!-- Top Customers -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-crown"></i> Top Customers</h3>
                    </div>
                    <div class="card-content">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Bookings</th>
                                        <th>Total Spent</th>
                                        <th>Last Booking</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_customers as $customer): ?>
                                        <tr>
                                            <td>
                                                <div><strong><?php echo htmlspecialchars($customer['username']); ?></strong></div>
                                                <div style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($customer['email']); ?></div>
                                            </td>
                                            <td><?php echo $customer['total_bookings']; ?> (<?php echo $customer['completed_bookings']; ?> done)</td>
                                            <td class="amount">₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($customer['last_booking_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Peak Hours & Payment Methods -->
                <div>
                    <!-- Peak Hours -->
                    <div class="content-card" style="margin-bottom: 24px;">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Peak Booking Hours</h3>
                        </div>
                        <div class="card-content">
                            <div class="chart-container" style="height: 200px;">
                                <canvas id="peakHoursChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3><i class="fas fa-credit-card"></i> Payment Methods</h3>
                        </div>
                        <div class="card-content">
                            <div class="chart-container" style="height: 200px;">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Insights -->
            <div class="content-card" style="margin-top: 24px;">
                <div class="card-header">
                    <h3><i class="fas fa-lightbulb"></i> Business Insights & Recommendations</h3>
                </div>
                <div class="card-content">
                    <?php
                    // Generate dynamic insights based on data
                    $insights = [];
                    
                    // Conversion rate insight
                    if ($conversion_rate < 50) {
                        $insights[] = [
                            'type' => 'warning',
                            'icon' => 'exclamation-triangle',
                            'title' => 'Low Conversion Rate Alert',
                            'content' => "Your conversion rate is {$conversion_rate}%. Consider investigating why {$conversion_stats['cancelled']} bookings were cancelled. Follow up with customers and improve service quality."
                        ];
                    } else {
                        $insights[] = [
                            'type' => 'success',
                            'icon' => 'check-circle',
                            'title' => 'Healthy Conversion Rate',
                            'content' => "Great job! Your {$conversion_rate}% conversion rate indicates strong customer satisfaction and service delivery."
                        ];
                    }
                    
                    // Customer retention insight
                    $avg_bookings = round($customer_insights['avg_bookings_per_customer'], 1);
                    if ($avg_bookings < 2) {
                        $insights[] = [
                            'type' => 'warning',
                            'icon' => 'users',
                            'title' => 'Customer Retention Opportunity',
                            'content' => "Average customer makes only {$avg_bookings} bookings. Implement loyalty programs, discounts for repeat customers, or follow-up campaigns to increase retention."
                        ];
                    } else {
                        $insights[] = [
                            'type' => 'success',
                            'icon' => 'heart',
                            'title' => 'Strong Customer Loyalty',
                            'content' => "Customers average {$avg_bookings} bookings each, showing good retention. Top customer has {$customer_insights['max_bookings_by_customer']} bookings!"
                        ];
                    }
                    
                    // Service performance insight
                    if (!empty($service_performance)) {
                        $top_service = $service_performance[0];
                        $bottom_service = end($service_performance);
                        $insights[] = [
                            'type' => 'info',
                            'icon' => 'star',
                            'title' => 'Service Performance Gap',
                            'content' => "'{$top_service['service_name']}' leads with {$top_service['total_bookings']} bookings and ₱" . number_format($top_service['total_revenue'], 2) . " revenue. Consider promoting underperforming services or improving their value proposition."
                        ];
                    }
                    
                    // Revenue insight
                    $revenue_per_customer = $customer_insights['active_customers'] > 0 
                        ? $total_revenue / $customer_insights['active_customers'] 
                        : 0;
                    $insights[] = [
                        'type' => 'info',
                        'icon' => 'chart-line',
                        'title' => 'Customer Lifetime Value',
                        'content' => "Average revenue per active customer: ₱" . number_format($revenue_per_customer, 2) . ". Focus on increasing this through upselling, premium services, and customer retention strategies."
                    ];
                    
                    foreach ($insights as $insight):
                    ?>
                        <div class="insight-card" style="border-left-color: <?php 
                            echo $insight['type'] === 'warning' ? '#FF6347' : 
                                ($insight['type'] === 'success' ? '#32CD32' : '#00CED1'); 
                        ?>;">
                            <div class="insight-title">
                                <i class="fas fa-<?php echo $insight['icon']; ?>"></i>
                                <?php echo $insight['title']; ?>
                            </div>
                            <div class="insight-content">
                                <?php echo $insight['content']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <script>
                // Revenue Trend Chart
                const revenueTrendData = <?php echo json_encode(array_column($revenue_trends, 'revenue')); ?>;
                const revenueTrendLabels = <?php echo json_encode(array_column($revenue_trends, 'month_name')); ?>;

                new Chart(document.getElementById('revenueTrendChart'), {
                    type: 'line',
                    data: {
                        labels: revenueTrendLabels.length > 0 ? revenueTrendLabels : ['No data'],
                        datasets: [{
                            label: 'Revenue (₱)',
                            data: revenueTrendData.length > 0 ? revenueTrendData : [0],
                            borderColor: '#FFD700',
                            backgroundColor: 'rgba(255, 215, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#FFD700'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                titleColor: '#ffffff',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                padding: 12,
                                callbacks: {
                                    label: function(context) {
                                        return '₱' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#1a1a1a' },
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + (value/1000).toFixed(0) + 'K';
                                    }
                                }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Booking Trend Chart
                const bookingTrendData = <?php echo json_encode(array_column($booking_trends, 'booking_count')); ?>;
                const bookingCompletedData = <?php echo json_encode(array_column($booking_trends, 'completed')); ?>;
                const bookingLabels = <?php echo json_encode(array_column($booking_trends, 'month_name')); ?>;

                new Chart(document.getElementById('bookingTrendChart'), {
                    type: 'bar',
                    data: {
                        labels: bookingLabels.length > 0 ? bookingLabels : ['No data'],
                        datasets: [{
                            label: 'Total Bookings',
                            data: bookingTrendData.length > 0 ? bookingTrendData : [0],
                            backgroundColor: 'rgba(0, 206, 209, 0.6)',
                            borderColor: '#00CED1',
                            borderWidth: 1
                        }, {
                            label: 'Completed',
                            data: bookingCompletedData.length > 0 ? bookingCompletedData : [0],
                            backgroundColor: 'rgba(50, 205, 50, 0.6)',
                            borderColor: '#32CD32',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                display: true,
                                position: 'top',
                                labels: { color: '#a0a0a0' }
                            },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                titleColor: '#ffffff',
                                bodyColor: '#a0a0a0',
                                borderColor: '#2a2a2a',
                                borderWidth: 1,
                                padding: 12
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: '#1a1a1a' },
                                ticks: { stepSize: 1 }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Peak Hours Chart
                const peakHoursData = <?php echo json_encode(array_column($peak_hours, 'booking_count')); ?>;
                const peakHoursLabels = <?php echo json_encode(array_map(function($h) { 
                    return ($h['hour'] % 12 ?: 12) . ($h['hour'] < 12 ? 'AM' : 'PM'); 
                }, $peak_hours)); ?>;

                new Chart(document.getElementById('peakHoursChart'), {
                    type: 'bar',
                    data: {
                        labels: peakHoursLabels.length > 0 ? peakHoursLabels : ['No data'],
                        datasets: [{
                            label: 'Bookings',
                            data: peakHoursData.length > 0 ? peakHoursData : [0],
                            backgroundColor: '#FFD700',
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                padding: 12
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true,
                                grid: { color: '#1a1a1a' },
                                ticks: { stepSize: 1 }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });

                // Payment Methods Chart
                const paymentMethodData = <?php echo json_encode(array_column($payment_methods, 'count')); ?>;
                const paymentMethodLabels = <?php echo json_encode(array_column($payment_methods, 'payment_method')); ?>;

                new Chart(document.getElementById('paymentMethodsChart'), {
                    type: 'doughnut',
                    data: {
                        labels: paymentMethodLabels.length > 0 ? paymentMethodLabels : ['No data'],
                        datasets: [{
                            data: paymentMethodData.length > 0 ? paymentMethodData : [1],
                            backgroundColor: ['#FFD700', '#00CED1', '#32CD32', '#FF6347', '#9370DB'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    color: '#a0a0a0',
                                    padding: 12,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                backgroundColor: '#1a1a1a',
                                padding: 12
                            }
                        }
                    }
                });
            </script>
        </section>

        <!-- Settings Section -->
        <section id="settings" class="content-section">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-cogs"></i> Settings</h3>
                </div>
                <div class="card-content">
                    <div class="empty-state">
                        <i class="fas fa-cogs"></i>
                        <h3>Settings Coming Soon</h3>
                        <p>This feature is under development.</p>
                    </div>
                </div>
            </div>
        </section>

    </div>
    </div>

    <!-- Modal Overlay -->
    <div id="modalOverlay" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function showSection(sectionId, linkElement) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.add('active');
            }
            
            // Add active class to clicked link
            if (linkElement) {
                linkElement.classList.add('active');
            }
            
            // Load data for specific sections
            if (sectionId === 'users' && typeof window.fetchUsers === 'function') {
                window.fetchUsers(1);
            }
            
            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        }

        function showModal(content, title) {
            document.getElementById('modalTitle').textContent = title || 'Details';
            document.getElementById('modalBody').innerHTML = content;
            document.getElementById('modalOverlay').classList.add('active');
        }

        function closeModal() {
            document.getElementById('modalOverlay').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Notification functions
        function toggleNotificationDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('active');
        }

        function closeNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.remove('active');
        }

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificationDropdown');
            const notifBtn = document.querySelector('.notification-btn');
            
            if (dropdown && !dropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                closeNotificationDropdown();
            }
        });

        async function markNotificationRead(notificationId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_notification_read');
                formData.append('notification_id', notificationId);
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    // Update UI
                    const notifElements = document.querySelectorAll(`[data-notif-id="${notificationId}"]`);
                    notifElements.forEach(el => {
                        el.classList.remove('unread', 'notification-unread');
                        const unreadDot = el.querySelector('.unread-dot');
                        if (unreadDot) unreadDot.remove();
                    });

                    // Update badge count
                    updateNotificationBadge();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        async function markAllNotificationsRead() {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_all_notifications_read');
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();
                if (data.success) {
                    // Reload page to update UI
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to mark notifications as read'));
                }
            } catch (error) {
                console.error('Error marking all notifications as read:', error);
                alert('Network error');
            }
        }

        function updateNotificationBadge() {
            const unreadCount = document.querySelectorAll('.notification-unread, .unread').length;
            const badges = document.querySelectorAll('.notification-badge, .sidebar-badge');
            
            badges.forEach(badge => {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
