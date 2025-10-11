<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get user information
$user_id = $_SESSION['user_id'];
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
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
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
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .service-icon {
            font-size: 48px;
            color: #FFD700;
            margin-bottom: 15px;
        }

        .service-name {
            font-size: 18px;
            margin-bottom: 15px;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-pricing {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            background: #2a2a2a;
            border-radius: 8px;
            padding: 10px;
        }

        .price-option {
            text-align: center;
            flex: 1;
        }

        .vehicle-size {
            display: block;
            font-size: 12px;
            color: #ccc;
            margin-bottom: 5px;
        }

        .price {
            display: block;
            font-size: 16px;
            color: #FFD700;
            font-weight: bold;
        }

        .service-details {
            text-align: left;
            margin: 15px 0;
            font-size: 13px;
            line-height: 1.4;
        }

        .service-details p {
            margin-bottom: 8px;
        }

        .btn-primary {
            background: #FFD700;
            color: #1a1a1a;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
        }

        .btn-secondary {
            background: transparent;
            color: #FFD700;
            border: 1px solid #FFD700;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="dashboard">
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
                <a href="#" class="nav-link" onclick="showSection('bookings', this)">
                    <i class="fas fa-calendar-alt"></i> My Bookings
                </a>
                <a href="#" class="nav-link" onclick="showSection('services', this)">
                    <i class="fas fa-car-wash"></i> Services
                </a>
                <a href="booking_guide.php" class="nav-link" target="_blank">
                    <i class="fas fa-map-marked-alt"></i> Booking Guide
                </a>
                <a href="#" class="nav-link" onclick="showSection('finances', this)">
                    <i class="fas fa-chart-line"></i> Finances
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

                <!-- Services Section -->
                <section id="services" class="content-section">
                    <div class="page-header">
                        <h1 class="page-title">Our Services</h1>
                        <p class="page-subtitle">Choose from our premium car detailing services</p>
                    </div>

                    <?php 
                    $grouped_services = [];
                    foreach ($services as $service) {
                        $grouped_services[$service['category']][] = $service;
                    }
                    ?>

                    <?php foreach ($grouped_services as $category => $category_services): ?>
                        <div class="service-category">
                            <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                            <div class="services-grid">
                                <?php foreach ($category_services as $service): ?>
                                    <div class="service-card">
                                        <div class="service-icon">
                                            <?php
                                            // Icon mapping based on service type
                                            $icon = 'fas fa-car';
                                            if (strpos($service['service_name'], 'Interior') !== false) $icon = 'fas fa-couch';
                                            elseif (strpos($service['service_name'], 'Exterior') !== false) $icon = 'fas fa-spray-can';
                                            elseif (strpos($service['service_name'], 'Platinum') !== false) $icon = 'fas fa-crown';
                                            elseif (strpos($service['service_name'], 'Engine') !== false) $icon = 'fas fa-cogs';
                                            elseif (strpos($service['service_name'], 'Headlight') !== false) $icon = 'fas fa-lightbulb';
                                            elseif (strpos($service['service_name'], 'Glass') !== false) $icon = 'fas fa-window-maximize';
                                            elseif (strpos($service['service_name'], 'Ceramic') !== false) $icon = 'fas fa-shield-alt';
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <h3 class="service-name"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                        
                                        <div class="service-pricing">
                                            <div class="price-option">
                                                <span class="vehicle-size">Small</span>
                                                <span class="price">₱<?php echo number_format($service['price_small'], 2); ?></span>
                                            </div>
                                            <div class="price-option">
                                                <span class="vehicle-size">Medium</span>
                                                <span class="price">₱<?php echo number_format($service['price_medium'], 2); ?></span>
                                            </div>
                                            <div class="price-option">
                                                <span class="vehicle-size">Large</span>
                                                <span class="price">₱<?php echo number_format($service['price_large'], 2); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="service-details">
                                            <p><strong>Includes:</strong> <?php echo htmlspecialchars($service['included_items']); ?></p>
                                            <?php if ($service['free_items']): ?>
                                                <p><strong>Free:</strong> <?php echo htmlspecialchars($service['free_items']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button class="btn-primary" onclick="openBookingModal(<?php echo $service['service_id']; ?>, '<?php echo htmlspecialchars($service['service_name']); ?>')">
                                            Book Now
                                        </button>
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
                <h2>Book Service</h2>
                <span class="close" onclick="closeBookingModal()">&times;</span>
            </div>
            <form id="bookingForm" class="modal-body">
                <input type="hidden" id="service_id" name="service_id">
                
                <div class="form-group">
                    <label>Service:</label>
                    <p id="selected_service_name"></p>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_size">Vehicle Size:</label>
                    <select id="vehicle_size" name="vehicle_size" required onchange="updatePrice()">
                        <option value="">Select Vehicle Size</option>
                        <option value="small">Small (Sedan, Hatchback)</option>
                        <option value="medium">Medium (SUV, Crossover)</option>
                        <option value="large">Large (Van, Truck, Large SUV)</option>
                    </select>
                    <div id="selected_price" style="font-size: 18px; color: #FFD700; font-weight: bold; margin-top: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label for="booking_date">Preferred Date:</label>
                    <input type="date" id="booking_date" name="booking_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
                
                <div class="form-group">
                    <label for="booking_time">Preferred Time:</label>
                    <select id="booking_time" name="booking_time" required>
                        <option value="">Select Time</option>
                        <option value="08:00">8:00 AM</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_details">Vehicle Details:</label>
                    <input type="text" id="vehicle_details" name="vehicle_details" 
                           placeholder="e.g., Toyota Camry 2020, White" required>
                </div>
                
                <div class="form-group">
                    <label for="special_requests">Special Requests (Optional):</label>
                    <textarea id="special_requests" name="special_requests" rows="3"></textarea>
                </div>

                <!-- Payment Options -->
                <div class="form-group">
                    <label>Payment Option:</label>
                    <div class="payment-options">
                        <div class="payment-option" onclick="selectPaymentOption('partial')">
                            <input type="radio" id="payment_partial" name="payment_option" value="partial" required>
                            <label for="payment_partial" class="payment-card">
                                <div class="payment-header">
                                    <i class="fas fa-credit-card"></i>
                                    <span class="payment-title">Partial Payment</span>
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
                        
                        <div class="payment-option" onclick="selectPaymentOption('full')">
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
                                        No money needed in person
                                    </div>
                                    <div class="payment-note">Complete payment online, hassle-free service</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
                <button type="submit" class="btn-primary" form="bookingForm">
                    <span id="submit_text">Proceed to Payment</span>
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
            background-color: rgba(0,0,0,0.7);
        }

        .modal-content {
            background-color: #2a2a2a;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #444;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: #1a1a1a;
            padding: 20px;
            border-bottom: 1px solid #444;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
            flex-shrink: 0;
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .modal-header h2 {
            margin: 0;
            color: #FFD700;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #FFD700;
        }

        .form-group {
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .form-group:first-of-type {
            padding-top: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #fff;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 4px;
            background: #333;
            color: #fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #FFD700;
            outline: none;
        }

        .form-actions {
            padding: 20px;
            border-top: 1px solid #444;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-shrink: 0;
            background: #2a2a2a;
            border-radius: 0 0 8px 8px;
        }

        /* Payment Options Styles */
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 10px;
        }

        .payment-option {
            position: relative;
            cursor: pointer;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-card {
            display: block;
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border: 2px solid #444;
            border-radius: 12px;
            padding: 20px;
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
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .payment-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .payment-header i {
            color: #FFD700;
            font-size: 20px;
        }

        .payment-title {
            font-size: 16px;
            font-weight: 600;
            color: white;
            flex: 1;
        }

        .payment-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            margin-left: 30px;
        }

        .payment-amount {
            font-size: 18px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }

        .payment-remaining {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 5px;
        }

        .payment-saving {
            font-size: 14px;
            color: #4CAF50;
            margin-bottom: 5px;
        }

        .payment-note {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .payment-options {
                gap: 10px;
            }
            
            .payment-card {
                padding: 15px;
            }
            
            .payment-details {
                margin-left: 0;
                margin-top: 10px;
            }
        }

        #selected_service_name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }

        #selected_service_price {
            font-size: 20px;
            color: #FFD700;
            font-weight: bold;
            margin: 5px 0;
        }
    </style>

    <script>
        let currentServiceId = null;
        let currentServiceData = null;

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
            echo implode(',', $service_js_data);
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

        // Booking modal functions
        function openBookingModal(serviceId, serviceName) {
            currentServiceId = serviceId;
            currentServiceData = serviceData[serviceId];
            
            document.getElementById('service_id').value = serviceId;
            document.getElementById('selected_service_name').textContent = serviceName;
            document.getElementById('bookingModal').style.display = 'block';
            
            // Reset form
            document.getElementById('vehicle_size').value = '';
            document.getElementById('selected_price').textContent = '';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.getElementById('bookingForm').reset();
            document.getElementById('selected_price').textContent = '';
        }

        function updatePrice() {
            const vehicleSize = document.getElementById('vehicle_size').value;
            const priceDisplay = document.getElementById('selected_price');
            
            if (vehicleSize && currentServiceData) {
                const price = currentServiceData[vehicleSize];
                priceDisplay.textContent = 'Price: ₱' + price.toFixed(2);
                
                // Update payment amounts
                updatePaymentAmounts(price);
            } else {
                priceDisplay.textContent = '';
                clearPaymentAmounts();
            }
        }

        function updatePaymentAmounts(totalPrice) {
            const partialAmount = totalPrice * 0.5;
            const remainingAmount = totalPrice * 0.5;
            const fullAmount = totalPrice; // No discount, just full price
            
            document.getElementById('partial_amount').textContent = '₱' + partialAmount.toFixed(2);
            document.getElementById('remaining_amount').textContent = '₱' + remainingAmount.toFixed(2);
            document.getElementById('full_amount').textContent = '₱' + fullAmount.toFixed(2);
        }

        function clearPaymentAmounts() {
            document.getElementById('partial_amount').textContent = '₱0.00';
            document.getElementById('remaining_amount').textContent = '₱0.00';
            document.getElementById('full_amount').textContent = '₱0.00';
        }

        function selectPaymentOption(option) {
            const radio = document.getElementById('payment_' + option);
            radio.checked = true;
            
            // Update submit button text
            const submitText = document.getElementById('submit_text');
            if (option === 'partial') {
                submitText.textContent = 'Pay 50% Now (₱' + document.getElementById('partial_amount').textContent.replace('₱', '') + ')';
            } else {
                submitText.textContent = 'Pay Full Amount (₱' + document.getElementById('full_amount').textContent.replace('₱', '') + ')';
            }
        }

        // Handle booking form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const paymentOption = document.querySelector('input[name="payment_option"]:checked');
            
            if (!paymentOption) {
                alert('Please select a payment option.');
                return;
            }
            
            // Add payment information to form data
            const vehicleSize = document.getElementById('vehicle_size').value;
            const totalPrice = currentServiceData[vehicleSize];
            let paymentAmount, paymentType;
            
            if (paymentOption.value === 'partial') {
                paymentAmount = totalPrice * 0.5;
                paymentType = 'partial';
            } else {
                paymentAmount = totalPrice; // Full amount, no discount
                paymentType = 'full';
            }
            
            formData.append('payment_type', paymentType);
            formData.append('payment_amount', paymentAmount);
            formData.append('total_amount', totalPrice);
            
            fetch('create_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to enhanced payment page
                    window.location.href = 'payment_enhanced.php?booking_id=' + data.booking_id;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating your booking.');
            });
        });

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
    </script>
</body>
</html>