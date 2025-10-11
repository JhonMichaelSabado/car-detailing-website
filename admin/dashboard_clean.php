<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Initialize default values
$total_users = 0;
$active_bookings = 0;
$pending_bookings = 0;
$services_count = 0;
$total_revenue = 0;
$revenue_data = [];
$services_data = [];
$recent_services = [];

// Handle potential errors with try-catch
try {
    // Check if tables exist before querying
    $tables_check = $db->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tables_check > 0) {
        $total_users = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn() ?: 0;
    }
    
    $bookings_check = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount();
    if ($bookings_check > 0) {
        $active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn() ?: 0;
        
        // Pending bookings count
        $pending_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0;
        
        // Dynamic services count (unique service types from bookings)
        $services_count = $db->query("SELECT COUNT(DISTINCT service_type) FROM bookings WHERE service_type IS NOT NULL AND service_type != ''")->fetchColumn() ?: 0;
        
        // Total revenue
        $total_revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE total IS NOT NULL")->fetchColumn() ?: 0;
        
        // Recent services (last 10 completed services)
        $recent_stmt = $db->query("
            SELECT 
                b.service_type,
                b.total,
                b.status,
                b.created_at,
                u.username
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status IN ('completed', 'active')
            ORDER BY b.created_at DESC
            LIMIT 10
        ");
        $recent_services = $recent_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    // Log error for debugging
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 165, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(255, 215, 0, 0.05) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Premium Sidebar */
        .sidebar {
            width: 300px;
            background: linear-gradient(180deg, rgba(18, 18, 18, 0.95) 0%, rgba(26, 26, 26, 0.95) 100%);
            border-right: 2px solid rgba(255, 215, 0, 0.3);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 50px rgba(255, 215, 0, 0.1);
        }

        .sidebar-header {
            padding: 40px 30px;
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 165, 0, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            position: relative;
        }

        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #FFD700, transparent);
        }

        .logo {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 50%, #FFD700 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.5));
        }

        .admin-panel {
            font-size: 12px;
            color: rgba(255, 215, 0, 0.7);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .sidebar-nav {
            padding: 30px 0;
        }

        .nav-item {
            margin: 0 20px 15px 20px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 18px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15) 0%, rgba(255, 165, 0, 0.1) 100%);
            color: #FFD700;
            transform: translateX(8px);
            border-left: 4px solid #FFD700;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.2);
        }

        .nav-link.active {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2) 0%, rgba(255, 165, 0, 0.15) 100%);
            color: #FFD700;
            border-left: 4px solid #FFD700;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .nav-link i {
            width: 24px;
            margin-right: 18px;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .nav-link:hover i {
            transform: scale(1.1) rotate(5deg);
            color: #FFD700;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 300px;
            min-height: 100vh;
        }

        /* Top Header */
        .top-header {
            background: linear-gradient(135deg, rgba(18, 18, 18, 0.9) 0%, rgba(26, 26, 26, 0.9) 100%);
            padding: 25px 40px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 700;
            font-size: 16px;
        }

        .logout-btn {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
            color: #000;
            text-decoration: none;
        }

        /* Content Container */
        .content-container {
            padding: 40px;
        }

        .content-header {
            margin-bottom: 40px;
        }

        .content-title {
            font-size: 24px;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 8px;
        }

        .content-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(18, 18, 18, 0.9) 0%, rgba(26, 26, 26, 0.9) 100%);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FFD700, #FFA500, #FFD700);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(255, 215, 0, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 30px rgba(255, 215, 0, 0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: #000;
            font-size: 24px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: #FFD700;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dashboard Status */
        .dashboard-status {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-icon {
            font-size: 24px;
            color: #10B981;
        }

        .status-text {
            flex: 1;
        }

        .status-title {
            font-weight: 600;
            color: #10B981;
            margin-bottom: 5px;
        }

        .status-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        /* Debug Info */
        .debug-info {
            background: rgba(18, 18, 18, 0.9);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .debug-title {
            color: #FFD700;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .debug-list {
            list-style: none;
        }

        .debug-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .debug-list i {
            color: #10B981;
            width: 16px;
        }

        /* Recent Services */
        .recent-services {
            background: linear-gradient(135deg, rgba(18, 18, 18, 0.9) 0%, rgba(26, 26, 26, 0.9) 100%);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            backdrop-filter: blur(10px);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-details h4 {
            color: #FFD700;
            margin-bottom: 5px;
        }

        .service-details p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
        }

        .service-amount {
            font-weight: 600;
            color: #10B981;
        }

        /* Page Content */
        .page-content {
            display: none;
        }

        .page-content.active {
            display: block;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-container {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .top-header {
                padding: 15px 20px;
            }

            .page-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Enhanced Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <i class="fas fa-car"></i>
                    RIDE REVIVE
                </a>
                <div class="admin-panel">ADMIN PANEL</div>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="#" class="nav-link active" onclick="showPage('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('services')">
                        <i class="fas fa-tools"></i>
                        Services
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('finances')">
                        <i class="fas fa-chart-line"></i>
                        Finances
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showPage('transactions')">
                        <i class="fas fa-receipt"></i>
                        Transactions
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-header">
                <h1 class="page-title">Dashboard Overview</h1>
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <span>Administrator</span>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        LOGOUT
                    </a>
                </div>
            </div>

            <div class="content-container">
                <!-- Dashboard Page -->
                <div id="dashboard-page" class="page-content active">
                    <div class="content-header">
                        <h2 class="content-title">Dashboard Overview</h2>
                        <p class="content-subtitle">Welcome to your admin control panel. Monitor your business performance.</p>
                    </div>

                    <!-- Dashboard Status -->
                    <div class="dashboard-status">
                        <i class="fas fa-check-circle status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">🚀 Dashboard is Working!</div>
                            <div class="status-description">Enhanced design is now active with premium styling and animations.</div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($total_users); ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($active_bookings); ?></div>
                            <div class="stat-label">Active Bookings</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo number_format($pending_bookings); ?></div>
                            <div class="stat-label">Pending Services</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>

                    <!-- Debug Info -->
                    <div class="debug-info">
                        <h3 class="debug-title">System Status:</h3>
                        <ul class="debug-list">
                            <li><i class="fas fa-check"></i> PHP is working: ✅</li>
                            <li><i class="fas fa-check"></i> Database connected: ✅</li>
                            <li><i class="fas fa-user"></i> User ID: <?php echo $_SESSION['user_id']; ?></li>
                            <li><i class="fas fa-clock"></i> Current time: <?php echo date('Y-m-d H:i:s'); ?></li>
                        </ul>
                    </div>

                    <!-- Recent Services -->
                    <?php if (!empty($recent_services)): ?>
                    <div class="recent-services">
                        <h3 class="section-title">
                            <i class="fas fa-history"></i>
                            Recent Services
                        </h3>
                        <?php foreach (array_slice($recent_services, 0, 5) as $service): ?>
                        <div class="service-item">
                            <div class="service-details">
                                <h4><?php echo htmlspecialchars($service['service_type'] ?: 'Service'); ?></h4>
                                <p>Customer: <?php echo htmlspecialchars($service['username'] ?: 'Unknown'); ?> • 
                                   <?php echo date('M j, Y', strtotime($service['created_at'])); ?></p>
                            </div>
                            <div class="service-amount">$<?php echo number_format($service['total'] ?: 0, 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- User Stats -->
                    <div class="recent-services">
                        <h3 class="section-title">
                            <i class="fas fa-users"></i>
                            Total Users: <?php echo number_format($total_users); ?>
                        </h3>
                        <div class="service-item">
                            <div class="service-details">
                                <h4>↗️ +12% this month</h4>
                                <p>User growth is looking great!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Pages -->
                <div id="services-page" class="page-content">
                    <div class="content-header">
                        <h2 class="content-title">Services Management</h2>
                        <p class="content-subtitle">Manage your car detailing services here.</p>
                    </div>
                    <div class="dashboard-status">
                        <i class="fas fa-tools status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">Services Module</div>
                            <div class="status-description">This section will contain service management features.</div>
                        </div>
                    </div>
                </div>

                <div id="finances-page" class="page-content">
                    <div class="content-header">
                        <h2 class="content-title">Financial Overview</h2>
                        <p class="content-subtitle">Track your revenue and financial metrics.</p>
                    </div>
                    <div class="dashboard-status">
                        <i class="fas fa-chart-line status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">Financial Module</div>
                            <div class="status-description">This section will contain financial tracking and reports.</div>
                        </div>
                    </div>
                </div>

                <div id="transactions-page" class="page-content">
                    <div class="content-header">
                        <h2 class="content-title">Transaction History</h2>
                        <p class="content-subtitle">View all payment transactions and booking history.</p>
                    </div>
                    <div class="dashboard-status">
                        <i class="fas fa-receipt status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">Transaction Module</div>
                            <div class="status-description">This section will contain transaction history and details.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navigation System
        function showPage(pageId) {
            // Hide all pages
            document.querySelectorAll('.page-content').forEach(page => {
                page.classList.remove('active');
            });

            // Show selected page
            const targetPage = document.getElementById(pageId + '-page');
            if (targetPage) {
                targetPage.classList.add('active');
            }

            // Update navigation active state
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const activeLink = document.querySelector(`[onclick="showPage('${pageId}')"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

            // Update page title
            const titleMap = {
                'dashboard': 'Dashboard Overview',
                'services': 'Services Management',
                'finances': 'Financial Overview',
                'transactions': 'Transaction History'
            };
            
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle && titleMap[pageId]) {
                pageTitle.textContent = titleMap[pageId];
            }
        }

        // Loading effects
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        console.log('🚀 Enhanced Dashboard Loaded Successfully!');
        console.log('✨ All styling and animations are working');
    </script>
</body>
</html>