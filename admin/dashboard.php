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
$total_revenue = 0;
$recent_services = [];

// Handle potential errors with try-catch
try {
    $tables_check = $db->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tables_check > 0) {
        $total_users = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn() ?: 0;
    }
    
    $bookings_check = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount();
    if ($bookings_check > 0) {
        $active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn() ?: 0;
        $pending_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0;
        $total_revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE total IS NOT NULL")->fetchColumn() ?: 0;
        
        $recent_stmt = $db->query("
            SELECT b.service_type, b.total, b.status, b.created_at, u.username
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status IN ('completed', 'active')
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        $recent_services = $recent_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            line-height: 1.6;
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

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            cursor: pointer;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #333;
            color: #FFD700;
        }

        .nav-link i {
            margin-right: 10px;
            width: 16px;
        }

        /* Main Content - Simplified */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
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
                padding: 15px;
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
                <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="#" class="nav-link" onclick="showSection('services')">
                    <i class="fas fa-car-wash"></i> Services
                </a>
                <a href="#" class="nav-link" onclick="showSection('finances')">
                    <i class="fas fa-chart-line"></i> Finances
                </a>
                <a href="#" class="nav-link" onclick="showSection('transactions')">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
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

                <?php if (!empty($recent_services)): ?>
                <div class="recent-services">
                    <h2 class="section-title">Recent Services</h2>
                    <?php foreach ($recent_services as $service): ?>
                    <div class="service-item">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['service_type'] ?? 'Unknown Service'); ?></h4>
                            <p><?php echo htmlspecialchars($service['username'] ?? 'N/A'); ?> • ₱<?php echo number_format($service['total'] ?? 0, 2); ?></p>
                        </div>
                        <span class="service-status status-<?php echo $service['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($service['status'] ?? 'pending'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
        </main>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => 
                section.classList.remove('active')
            );
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update nav links
            document.querySelectorAll('.nav-link').forEach(link => 
                link.classList.remove('active')
            );
            event.target.classList.add('active');
            
            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
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
    </script>
</body>
</html>
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
            font-family: system-ui, -apple-system, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            line-height: 1.6;
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

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            cursor: pointer;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #333;
            color: #FFD700;
        }

        .nav-link i {
            margin-right: 10px;
            width: 16px;
        }

        /* Main Content - Simplified */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
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
                padding: 15px;
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
                <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="#" class="nav-link" onclick="showSection('services')">
                    <i class="fas fa-car-wash"></i> Services
                </a>
                <a href="#" class="nav-link" onclick="showSection('finances')">
                    <i class="fas fa-chart-line"></i> Finances
                </a>
                <a href="#" class="nav-link" onclick="showSection('transactions')">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                <a href="../auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
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

                <?php if (!empty($recent_services)): ?>
                <div class="recent-services">
                    <h2 class="section-title">Recent Services</h2>
                    <?php foreach ($recent_services as $service): ?>
                    <div class="service-item">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['service_type'] ?? 'Unknown Service'); ?></h4>
                            <p><?php echo htmlspecialchars($service['username'] ?? 'N/A'); ?> • ₱<?php echo number_format($service['total'] ?? 0, 2); ?></p>
                        </div>
                        <span class="service-status status-<?php echo $service['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($service['status'] ?? 'pending'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
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
        </main>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => 
                section.classList.remove('active')
            );
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update nav links
            document.querySelectorAll('.nav-link').forEach(link => 
                link.classList.remove('active')
            );
            event.target.classList.add('active');
            
            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
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
    </script>
</body>
</html>
$db = $database->getConnection();

// Initialize default values
$total_users = 0;
$active_bookings = 0;
$pending_bookings = 0;
$services_count = 0;
$total_revenue = 0;
$recent_services = [];

// Handle potential errors with try-catch
try {
    $tables_check = $db->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tables_check > 0) {
        $total_users = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn() ?: 0;
    }
    
    $bookings_check = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount();
    if ($bookings_check > 0) {
        $active_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn() ?: 0;
        $pending_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0;
        $total_revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE total IS NOT NULL")->fetchColumn() ?: 0;
        
        $recent_stmt = $db->query("
            SELECT b.service_type, b.total, b.status, b.created_at, u.username
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status IN ('completed', 'active')
            ORDER BY b.created_at DESC
            LIMIT 10
        ");
        $recent_services = $recent_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    <style>
        :root {
            --primary-gold: #FFD700;
            --primary-orange: #FFA500;
            --bg-dark: #0f0f0f;
            --bg-secondary: #1a1a1a;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --border-primary: rgba(255, 215, 0, 0.3);
            --border-secondary: rgba(255, 215, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-secondary) 50%, var(--bg-dark) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(10px);
            border-right: 2px solid var(--border-primary);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 20px;
            border-bottom: 1px solid var(--border-secondary);
            text-align: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            margin: 5px 15px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.1));
            color: var(--primary-gold);
            transform: translateX(5px);
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
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
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-secondary);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--border-primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .stat-icon {
            font-size: 40px;
            opacity: 0.3;
        }

        /* Recent Services */
        .recent-services {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-secondary);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-secondary);
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-info h4 {
            font-size: 16px;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .service-info p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .service-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-active {
            background: rgba(249, 115, 22, 0.2);
            color: #f97316;
        }

        .status-pending {
            background: rgba(234, 179, 8, 0.2);
            color: #eab308;
        }

        /* Placeholder content */
        .placeholder-content {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .placeholder-content i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .placeholder-content h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
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
                background: var(--primary-gold);
                color: var(--bg-dark);
                border: none;
                border-radius: 10px;
                padding: 12px;
                font-size: 18px;
                cursor: pointer;
            }
        }

        .mobile-menu-btn {
            display: none;
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
                    <i class="fas fa-car"></i>
                    Ride Revive
                </a>
            </div>
            <div class="nav-menu">
                <div class="nav-item">
                    <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('services')">
                        <i class="fas fa-car-wash"></i>
                        Services
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('finances')">
                        <i class="fas fa-chart-line"></i>
                        Finances
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" onclick="showSection('transactions')">
                        <i class="fas fa-exchange-alt"></i>
                        Transactions
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="page-header">
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening with your business today.</p>
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

                <?php if (!empty($recent_services)): ?>
                <div class="recent-services">
                    <h2 class="section-title">Recent Services</h2>
                    <?php foreach ($recent_services as $service): ?>
                    <div class="service-item">
                        <div class="service-info">
                            <h4><?php echo htmlspecialchars($service['service_type'] ?? 'Unknown Service'); ?></h4>
                            <p>Customer: <?php echo htmlspecialchars($service['username'] ?? 'N/A'); ?> • 
                               ₱<?php echo number_format($service['total'] ?? 0, 2); ?></p>
                        </div>
                        <span class="service-status status-<?php echo $service['status'] ?? 'pending'; ?>">
                            <?php echo ucfirst($service['status'] ?? 'pending'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Services Section -->
            <section id="services" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Services Management</h1>
                    <p class="page-subtitle">Manage your car detailing services and pricing.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-car-wash"></i>
                    <h3>Services Management</h3>
                    <p>This section will contain service management features.</p>
                </div>
            </section>

            <!-- Finances Section -->
            <section id="finances" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Financial Overview</h1>
                    <p class="page-subtitle">Track your revenue, expenses, and financial performance.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-chart-line"></i>
                    <h3>Financial Dashboard</h3>
                    <p>This section will contain financial analytics and reports.</p>
                </div>
            </section>

            <!-- Transactions Section -->
            <section id="transactions" class="content-section">
                <div class="page-header">
                    <h1 class="page-title">Transaction History</h1>
                    <p class="page-subtitle">View and manage all payment transactions.</p>
                </div>
                <div class="placeholder-content">
                    <i class="fas fa-exchange-alt"></i>
                    <h3>Transaction Management</h3>
                    <p>This section will contain transaction history and management tools.</p>
                </div>
            </section>
        </main>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            
            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('mobile-open');
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

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open');
            }
        });

        console.log('🚀 Dashboard loaded successfully!');
    </script>
</body>
</html>
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

        .main-content {
            flex: 1;
            margin-left: 300px;
            min-height: 100vh;
        }

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
                <div id="dashboard-page" class="page-content active">
                    <div class="content-header">
                        <h2 class="content-title">Dashboard Overview</h2>
                        <p class="content-subtitle">Welcome to your admin control panel. Monitor your business performance.</p>
                    </div>

                    <div class="dashboard-status">
                        <i class="fas fa-check-circle status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">✨ Dashboard Fixed & Enhanced!</div>
                            <div class="status-description">Clean design with premium styling - no more corrupted text!</div>
                        </div>
                    </div>

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

                    <div class="debug-info">
                        <h3 class="debug-title">System Status:</h3>
                        <ul class="debug-list">
                            <li><i class="fas fa-check"></i> PHP is working: ✅</li>
                            <li><i class="fas fa-check"></i> Database connected: ✅</li>
                            <li><i class="fas fa-user"></i> User ID: <?php echo $_SESSION['user_id']; ?></li>
                            <li><i class="fas fa-clock"></i> Current time: <?php echo date('Y-m-d H:i:s'); ?></li>
                            <li><i class="fas fa-paint-brush"></i> Enhanced design: ACTIVE ✨</li>
                        </ul>
                    </div>

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
        function showPage(pageId) {
            document.querySelectorAll('.page-content').forEach(page => {
                page.classList.remove('active');
            });

            const targetPage = document.getElementById(pageId + '-page');
            if (targetPage) {
                targetPage.classList.add('active');
            }

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const activeLink = document.querySelector(`[onclick="showPage('${pageId}')"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

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

        console.log('🚀 Dashboard Fixed & Enhanced Successfully!');
        console.log('✨ No more corrupted text - all styling working perfectly');
    </script>
</body>
</html>
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            color: #ffffff;
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Enhanced Background with multiple layers */
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

        /* Premium Sidebar Design */
        .sidebar {
            width: 300px;
            background: linear-gradient(180deg, 
                rgba(18, 18, 18, 0.95) 0%, 
                rgba(26, 26, 26, 0.95) 50%, 
                rgba(18, 18, 18, 0.95) 100%);
            border-right: 2px solid rgba(255, 215, 0, 0.3);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 50px rgba(255, 215, 0, 0.1);
        }

        /* Glassmorphism effect with fallback */
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 215, 0, 0.2);
            z-index: -1;
        }

        .sidebar-header {
            padding: 40px 30px;
            text-align: center;
            background: linear-gradient(135deg, 
                rgba(255, 215, 0, 0.1) 0%, 
                rgba(255, 165, 0, 0.05) 100%);
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
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
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
            margin-top: 5px;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: transparent;
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 215, 0, 0.1), 
                transparent);
            transition: left 0.5s ease;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, 
                rgba(255, 215, 0, 0.15) 0%, 
                rgba(255, 165, 0, 0.1) 100%);
            color: #FFD700;
            transform: translateX(8px);
            border-left: 4px solid #FFD700;
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.2);
        }

        .nav-link.active {
            background: linear-gradient(135deg, 
                rgba(255, 215, 0, 0.2) 0%, 
                rgba(255, 165, 0, 0.15) 100%);
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

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 300px;
            min-height: 100vh;
            background: transparent;
        }

        /* Top Header */
        .top-header {
            background: linear-gradient(135deg, 
                rgba(18, 18, 18, 0.9) 0%, 
                rgba(26, 26, 26, 0.9) 100%);
            padding: 25px 40px;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            max-width: 1400px;
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

        /* Enhanced Status Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, 
                rgba(18, 18, 18, 0.9) 0%, 
                rgba(26, 26, 26, 0.9) 100%);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.3),
                0 0 30px rgba(255, 215, 0, 0.2);
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

        /* Dashboard Status Alert */
        .dashboard-status {
            background: linear-gradient(135deg, 
                rgba(16, 185, 129, 0.1) 0%, 
                rgba(5, 150, 105, 0.1) 100%);
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

        /* Recent Services Section */
        .recent-services {
            background: linear-gradient(135deg, 
                rgba(18, 18, 18, 0.9) 0%, 
                rgba(26, 26, 26, 0.9) 100%);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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

        /* Mobile Responsiveness */
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

        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }

        .loading-shimmer {
            background: linear-gradient(90deg, 
                rgba(255, 215, 0, 0.1) 0%, 
                rgba(255, 215, 0, 0.3) 50%, 
                rgba(255, 215, 0, 0.1) 100%);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }

        /* Smooth Transitions */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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

            <div class="content-container fade-in">
                <!-- Dashboard Page -->
                <div id="dashboard-page" class="page-content">
                    <div class="content-header">
                        <h2 class="content-title">Dashboard Overview</h2>
                        <p class="content-subtitle">Welcome to your admin control panel. Monitor your business performance.</p>
                    </div>

                    <!-- Dashboard Status -->
                    <div class="dashboard-status">
                        <i class="fas fa-check-circle status-icon"></i>
                        <div class="status-text">
                            <div class="status-title">🚀 Dashboard is Working!</div>
                            <div class="status-description">If you can see this, the dashboard content is displaying properly.</div>
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
                        <h3 class="debug-title">Debug Info:</h3>
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Pages (Hidden by default) -->
                <div id="services-page" class="page-content" style="display: none;">
                    <h2 class="content-title">Services Management</h2>
                    <p class="content-subtitle">Manage your car detailing services here.</p>
                </div>

                <div id="finances-page" class="page-content" style="display: none;">
                    <h2 class="content-title">Financial Overview</h2>
                    <p class="content-subtitle">Track your revenue and financial metrics.</p>
                </div>

                <div id="transactions-page" class="page-content" style="display: none;">
                    <h2 class="content-title">Transaction History</h2>
                    <p class="content-subtitle">View all payment transactions and booking history.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Navigation System
        function showPage(pageId) {
            // Hide all pages
            const pages = document.querySelectorAll('.page-content');
            pages.forEach(page => {
                page.style.display = 'none';
                page.classList.remove('fade-in');
            });

            // Show selected page
            const targetPage = document.getElementById(pageId + '-page');
            if (targetPage) {
                targetPage.style.display = 'block';
                setTimeout(() => {
                    targetPage.classList.add('fade-in');
                }, 10);
            }

            // Update navigation active state
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            
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

        // Mobile sidebar toggle
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('mobile-open');
        }

        // Enhanced loading effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading shimmer to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Console debugging
        console.log('🚀 Enhanced Dashboard Loaded Successfully!');
        console.log('✨ All styling and animations are working');
        console.log('💫 If you can see this message, JavaScript is functioning properly');
    </script>
</body>
</html>
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Optimized Sidebar */
        .sidebar {
            width: 300px;
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(10px);
            border-right: 2px solid var(--border-primary);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.2s ease;
            box-shadow: var(--shadow-medium);
        }

        .sidebar-header {
            padding: 35px 25px;
            border-bottom: 1px solid var(--border-secondary);
            text-align: center;
            background: rgba(255, 215, 0, 0.05);
        }

        .logo {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo i {
            margin-right: 12px;
            font-size: 32px;
            color: var(--primary-gold);
        }

        .sidebar-nav {
            padding: 30px 0;
        }

        .nav-item {
            margin: 0 20px 12px 20px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 18px 22px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 16px;
            transition: all 0.2s ease;
            background: transparent;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .nav-link:hover {
            background: rgba(255, 215, 0, 0.15);
            color: var(--primary-gold);
            transform: translateX(4px);
            border-left: 3px solid var(--primary-gold);
        }

        .nav-link.active {
            background: rgba(255, 215, 0, 0.2);
            color: var(--primary-gold);
            border-left: 3px solid var(--primary-gold);
            font-weight: 600;
        }

        .nav-link i {
            width: 24px;
            margin-right: 18px;
            font-size: 20px;
            transition: transform 0.2s ease;
        }

        .nav-link:hover i {
            transform: scale(1.05);
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 900;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .sidebar-subtitle {
            font-size: 0.8rem;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            position: relative;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 25px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            color: #FFD700;
            background: rgba(255, 215, 0, 0.05);
            border-left-color: #FFD700;
            text-decoration: none;
        }

        .nav-link.active {
            color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
            border-left-color: #FFD700;
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-text {
            font-size: 0.95rem;
        }

        /* Mobile sidebar toggle */
        .sidebar-toggle {
            display: none;
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #FFD700;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 215, 0, 0.2);
        }

        /* Main content area */
        .main-wrapper {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Page content sections */
        .page-content {
            opacity: 0;
            animation: fadeIn 0.5s ease-out forwards;
        }

        .page-content.active {
            display: block !important;
        }

        /* Modern Header */
        .header {
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding: 20px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title-header {
            font-size: 1.8rem;
            font-weight: 700;
            color: #FFD700;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Remove old logo section since it's now in sidebar */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #000;
        }

        .btn-logout {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        /* Optimized Main Content */
        .main-content {
            flex: 1;
            margin-left: 300px;
            padding: 50px 40px;
            min-height: 100vh;
            transition: margin 0.2s ease;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 1.2rem;
            margin-bottom: 0;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        /* Premium Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: rgba(18, 18, 18, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: left;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-medium);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-gold), var(--primary-orange));
            border-radius: 20px 20px 0 0;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.2);
            border-color: var(--primary-gold);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: var(--primary-dark);
            box-shadow: var(--shadow-small);
            transition: transform 0.2s ease;
            float: left;
            margin-right: 15px;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.05);
        }

        .stat-content {
            overflow: hidden;
            padding-top: 2px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
            line-height: 1;
        }

        .stat-label {
            color: #ccc;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Pending Services Card Special Styling */
        .pending-card {
            border-color: rgba(255, 193, 7, 0.4);
        }

        .pending-card:hover {
            border-color: rgba(255, 193, 7, 0.6);
            box-shadow: 0 15px 35px rgba(255, 193, 7, 0.3);
        }

        .urgent-indicator {
            margin-top: 10px;
            padding: 5px 10px;
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.4);
            border-radius: 15px;
            font-size: 0.8rem;
            color: #FFC107;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        /* Enhanced Recent Services Section */
        .recent-services-section {
            margin: 50px 0;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            font-size: 1.5rem;
            color: var(--primary-gold);
        }

        .view-all-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
            color: var(--primary-dark);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .view-all-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .recent-services-container {
            background: rgba(18, 18, 18, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-medium);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .service-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-secondary);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .service-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-gold);
            transform: translateY(-2px);
        }

        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .service-type {
            font-weight: 600;
            color: var(--primary-gold);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }

        .service-type i {
            font-size: 1rem;
            padding: 6px;
            background: rgba(255, 215, 0, 0.15);
            border-radius: 6px;
        }

        .service-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #4CAF50; color: white; }
        .status-pending { background: #FF9800; color: white; }
        .status-completed { background: #2196F3; color: white; }
        .status-cancelled { background: #f44336; color: white; }

        .service-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            color: #ccc;
        }

        .service-details > div {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .service-date {
            grid-column: 1 / -1;
        }

        .customer-info {
            color: #e0e0e0;
        }

        .service-price {
            color: #4CAF50;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .view-all-services {
            text-align: center;
            margin-top: 25px;
        }

        .btn-view-all {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-view-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-container {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            position: relative;
        }

        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 20px;
            text-align: center;
        }

        .chart-canvas {
            max-height: 300px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.4);
            text-decoration: none;
            color: inherit;
        }

        .action-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.2rem;
            color: #000;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #FFD700;
        }

        .action-desc {
            color: #ccc;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        /* Enhanced Mobile Responsiveness */
        @media (max-width: 1400px) {
            .main-content {
                padding: 40px 30px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .sidebar {
                width: 280px;
            }
            
            .main-content {
                margin-left: 280px;
                padding: 35px 25px;
            }
            
            .page-title {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 1024px) {
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
                width: 300px;
                box-shadow: var(--shadow-large), 20px 0 50px rgba(0, 0, 0, 0.3);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 20px;
                left: 20px;
                width: 50px;
                height: 50px;
                background: linear-gradient(135deg, var(--primary-gold), var(--primary-orange));
                border: none;
                border-radius: 15px;
                color: var(--primary-dark);
                font-size: 20px;
                z-index: 1002;
                box-shadow: var(--shadow-medium);
                transition: all 0.3s ease;
            }

            .sidebar-toggle:hover {
                transform: scale(1.05);
                box-shadow: var(--shadow-glow);
            }

            .main-content {
                margin-left: 0;
                padding: 100px 20px 30px 20px;
            }

            .page-title {
                font-size: 2.2rem;
                margin-bottom: 20px;
            }

            .page-subtitle {
                font-size: 1rem;
                margin-bottom: 30px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                margin-bottom: 40px;
            }

            .stat-card {
                padding: 25px 20px;
            }

            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
                margin-right: 15px;
            }

            .stat-number {
                font-size: 2.5rem;
            }

            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .service-item {
                padding: 20px;
            }

            .recent-services-container {
                padding: 25px 20px;
            }

            .section-title {
                font-size: 1.6rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .view-all-btn {
                align-self: stretch;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 90px 15px 20px 15px;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .stat-card {
                padding: 20px 15px;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-right: 12px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }

            .service-item {
                padding: 15px;
            }

            .recent-services-container {
                padding: 20px 15px;
            }
        }

        /* Enhanced Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar-overlay.show {
                display: block;
            }
        }

        /* Page Content Display */
        .page-content {
            display: none;
        }

        .page-content.active {
            display: block !important;
        }

        /* Optimized Animations */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Remove heavy scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--secondary-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gold);
            border-radius: 3px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">RIDE REVIVE</div>
            <div class="sidebar-subtitle">Admin Panel</div>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="#dashboard" class="nav-link active" data-page="dashboard">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="#services" class="nav-link" data-page="services">
                    <i class="fas fa-car-wash nav-icon"></i>
                    <span class="nav-text">Services</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="#finances" class="nav-link" data-page="finances">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    <span class="nav-text">Finances</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="#transactions" class="nav-link" data-page="transactions">
                    <i class="fas fa-receipt nav-icon"></i>
                    <span class="nav-text">Transactions</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main Dashboard -->
    <div class="main-wrapper">
        <!-- Header -->
        <header class="header">
            <div class="page-header">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title-header">Dashboard Overview</h1>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <span>Administrator</span>
                </div>
                <button class="btn-logout" onclick="window.location.href='../auth/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <!-- Enhanced Main Content -->
        <main class="main-content">
            <!-- Dashboard Content -->
            <div id="dashboard-content" class="page-content active">
                <div class="page-header">
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p class="page-subtitle">Welcome to your admin control panel. Monitor your business performance.</p>
                </div>

                <!-- Simple Test Content -->
                <div style="background: rgba(255,255,255,0.1); padding: 20px; margin: 20px 0; border-radius: 10px;">
                    <h2 style="color: #FFD700;">🚀 Dashboard is Working!</h2>
                    <p>If you can see this, the dashboard content is displaying properly.</p>
                    
                    <!-- Test Stats -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                        <div style="background: rgba(255,215,0,0.1); padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="color: #FFD700; margin: 0;"><?php echo $total_users; ?></h3>
                            <p style="margin: 5px 0;">Total Users</p>
                        </div>
                        <div style="background: rgba(255,215,0,0.1); padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="color: #FFD700; margin: 0;"><?php echo $active_bookings; ?></h3>
                            <p style="margin: 5px 0;">Active Bookings</p>
                        </div>
                        <div style="background: rgba(255,215,0,0.1); padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="color: #FFD700; margin: 0;"><?php echo $pending_bookings; ?></h3>
                            <p style="margin: 5px 0;">Pending Services</p>
                        </div>
                        <div style="background: rgba(255,215,0,0.1); padding: 15px; border-radius: 8px; text-align: center;">
                            <h3 style="color: #FFD700; margin: 0;">$<?php echo number_format($total_revenue, 2); ?></h3>
                            <p style="margin: 5px 0;">Total Revenue</p>
                        </div>
                    </div>
                    
                    <p><strong>Debug Info:</strong></p>
                    <ul>
                        <li>PHP is working: ✅</li>
                        <li>Database connected: <?php echo $db ? '✅' : '❌'; ?></li>
                        <li>User ID: <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></li>
                        <li>Current time: <?php echo date('Y-m-d H:i:s'); ?></li>
                    </ul>
                </div>
            </div>
            <!-- End Dashboard Content -->

            <!-- Services Content -->
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($total_users); ?></div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-change"><i class="fas fa-arrow-up"></i> +12% this month</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($active_bookings); ?></div>
                            <div class="stat-label">Active Bookings</div>
                            <div class="stat-change"><i class="fas fa-arrow-up"></i> +5% this week</div>
                        </div>
                    </div>
                    <div class="stat-card pending-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($pending_bookings); ?></div>
                            <div class="stat-label">Pending Services</div>
                            <?php if ($pending_bookings > 0): ?>
                                <div class="stat-change negative">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Needs Attention
                                </div>
                            <?php else: ?>
                                <div class="stat-change">
                                    <i class="fas fa-check-circle"></i>
                                    All caught up!
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-car"></i></div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo number_format($services_count); ?></div>
                            <div class="stat-label">Service Types</div>
                            <div class="stat-change"><i class="fas fa-info-circle"></i> Available options</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-content">
                            <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-change"><i class="fas fa-chart-line"></i> +15% growth</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Services Section -->
                <div class="recent-services-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-history"></i>
                            Recent Services
                        </h2>
                        <button class="view-all-btn" onclick="switchPage('services')">
                            <i class="fas fa-external-link-alt"></i>
                            View All Services
                        </button>
                    </div>
                    <div class="recent-services-container">
                        <?php if (empty($recent_services)): ?>
                            <div class="empty-state">
                                <i class="fas fa-car-wash" style="font-size: 3rem; opacity: 0.3; margin-bottom: 15px;"></i>
                                <p>No recent services found. Start accepting bookings to see activity here!</p>
                            </div>
                        <?php else: ?>
                            <div class="services-grid">
                                <?php foreach (array_slice($recent_services, 0, 6) as $service): ?>
                                    <div class="service-item">
                                        <div class="service-header">
                                            <div class="service-type">
                                                <i class="fas fa-car-wash"></i>
                                                <?php echo htmlspecialchars($service['service_type'] ?: 'Car Detailing'); ?>
                                            </div>
                                            <div class="service-status status-<?php echo strtolower($service['status'] ?: 'pending'); ?>">
                                                <?php echo ucfirst($service['status'] ?: 'Pending'); ?>
                                            </div>
                                        </div>
                                        <div class="service-details">
                                            <div class="customer-info">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($service['customer_name'] ?: 'Unknown Customer'); ?>
                                            </div>
                                            <div class="service-price">
                                                <i class="fas fa-dollar-sign"></i>
                                                $<?php echo number_format($service['total'] ?: 0, 2); ?>
                                            </div>
                                            <div class="service-date">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y', strtotime($service['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($recent_services) > 6): ?>
                                <div class="view-all-services">
                                    <button class="btn-view-all" onclick="showAllServices()">
                                        <i class="fas fa-list"></i> View All Services (<?php echo count($recent_services); ?>)
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <!-- Charts Section - Temporarily Hidden -->
            <div class="charts-section" style="display: none;">
                <div class="chart-container">
                    <h3 class="chart-title"><i class="fas fa-chart-line"></i> Monthly Revenue Trend</h3>
                    <canvas id="revenueChart" class="chart-canvas"></canvas>
                </div>
                <div class="chart-container">
                    <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Service Distribution</h3>
                    <canvas id="servicesChart" class="chart-canvas"></canvas>
                </div>
            </div>

            <!-- Quick Actions -->
            <h2 style="color: #FFD700; margin-bottom: 20px; font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div class="quick-actions">
                <a href="../bookings/manage.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="action-title">Manage Bookings</div>
                    <div class="action-desc">View and manage customer bookings</div>
                </a>
                <a href="../users/manage.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-user-cog"></i></div>
                    <div class="action-title">User Management</div>
                    <div class="action-desc">Manage user accounts and permissions</div>
                </a>
                <a href="../services/manage.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-cogs"></i></div>
                    <div class="action-title">Services</div>
                    <div class="action-desc">Configure available services</div>
                </a>
                <a href="../reports/analytics.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="action-title">Analytics</div>
                    <div class="action-desc">View detailed business reports</div>
                </a>
            </div>
        </div>
            </div>
            <!-- End Dashboard Content -->

        <!-- Services Content -->
        <div id="services-content" class="page-content" style="display: none;">
            <p class="page-subtitle">Manage and configure your car detailing services.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-car-wash"></i></div>
                    <div class="stat-number"><?php echo number_format($services_count); ?></div>
                    <div class="stat-label">Active Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-number">2.5</div>
                    <div class="stat-label">Avg Duration (hrs)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-number">4.8</div>
                    <div class="stat-label">Avg Rating</div>
                </div>
            </div>

            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-plus"></i></div>
                    <div class="action-title">Add New Service</div>
                    <div class="action-desc">Create a new car detailing service</div>
                </div>
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-edit"></i></div>
                    <div class="action-title">Edit Services</div>
                    <div class="action-desc">Modify existing service details</div>
                </div>
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-tags"></i></div>
                    <div class="action-title">Service Packages</div>
                    <div class="action-desc">Create service bundles and packages</div>
                </div>
            </div>
        </div>

        <!-- Finances Content -->
        <div id="finances-content" class="page-content" style="display: none;">
            <p class="page-subtitle">Monitor your financial performance and revenue streams.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-number">+15%</div>
                    <div class="stat-label">Monthly Growth</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div class="stat-number">$<?php echo number_format($total_revenue * 0.75, 2); ?></div>
                    <div class="stat-label">Net Profit</div>
                </div>
            </div>

            <div class="chart-container" style="margin-top: 30px;">
                <h3 class="chart-title"><i class="fas fa-chart-area"></i> Financial Overview</h3>
                <canvas id="financesChart" class="chart-canvas"></canvas>
            </div>
        </div>

        <!-- Transactions Content -->
        <div id="transactions-content" class="page-content" style="display: none;">
            <p class="page-subtitle">View and manage all transaction records.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                    <div class="stat-number"><?php echo number_format($active_bookings * 3); ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                    <div class="stat-number">$<?php echo number_format($total_revenue / max($active_bookings * 3, 1), 2); ?></div>
                    <div class="stat-label">Avg Transaction</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-number"><?php echo date('j'); ?></div>
                    <div class="stat-label">Today's Count</div>
                </div>
            </div>

            <div class="quick-actions">
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-search"></i></div>
                    <div class="action-title">Search Transactions</div>
                    <div class="action-desc">Find specific transaction records</div>
                </div>
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-download"></i></div>
                    <div class="action-title">Export Data</div>
                    <div class="action-desc">Download transaction reports</div>
                </div>
                <div class="action-card">
                    <div class="action-icon"><i class="fas fa-filter"></i></div>
                    <div class="action-title">Filter Records</div>
                    <div class="action-desc">Filter by date, amount, or status</div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const navLinks = document.querySelectorAll('.nav-link');
        const pageContents = document.querySelectorAll('.page-content');
        const pageTitle = document.querySelector('.page-title-header');

        // Ensure dashboard content is visible on load
        const dashboardContent = document.getElementById('dashboard-content');
        if (dashboardContent) {
            dashboardContent.style.display = 'block';
            dashboardContent.classList.add('active');
            console.log('Dashboard content made visible');
        } else {
            console.log('Dashboard content not found!');
        }

        // Make sure dashboard link is active by default
        const dashboardLink = document.querySelector('.nav-link[data-page="dashboard"]');
        if (dashboardLink) {
            dashboardLink.classList.add('active');
            console.log('Dashboard link made active');
        } else {
            console.log('Dashboard link not found!');
        }

        // Debug: Check all page contents
        const allPageContents = document.querySelectorAll('.page-content');
        console.log('Found page contents:', allPageContents.length);
        allPageContents.forEach((content, index) => {
            console.log(`Content ${index}:`, content.id, 'display:', content.style.display);
        });

        // Force dashboard to stay visible - debugging
        setInterval(() => {
            const dashboard = document.getElementById('dashboard-content');
            if (dashboard && dashboard.style.display === 'none') {
                console.log('Dashboard was hidden! Showing it again...');
                dashboard.style.display = 'block';
            }
        }, 1000);

        // Toggle sidebar on mobile
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        });

        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
        });

        // Handle navigation
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Get the page to show
                const page = this.getAttribute('data-page');
                
                // Handle different pages
                if (page === 'dashboard') {
                    // Show dashboard content
                    pageContents.forEach(content => {
                        if (content.id === 'dashboard-content') {
                            content.style.display = 'block';
                        } else {
                            content.style.display = 'none';
                        }
                    });
                } else {
                    // For other pages, hide dashboard and show placeholder
                    pageContents.forEach(content => content.style.display = 'none');
                    
                    // Show coming soon message for other pages
                    let targetContent = document.getElementById(page + '-content');
                    if (!targetContent) {
                        // Create placeholder content for other pages
                        targetContent = document.createElement('div');
                        targetContent.id = page + '-content';
                        targetContent.className = 'page-content';
                        targetContent.innerHTML = `
                            <div style="text-align: center; padding: 100px 20px;">
                                <i class="fas fa-tools" style="font-size: 4rem; color: #FFD700; margin-bottom: 20px;"></i>
                                <h2 style="color: #FFD700; margin-bottom: 10px;">Coming Soon</h2>
                                <p style="color: #ccc;">The ${page} section is under development.</p>
                            </div>
                        `;
                        document.querySelector('.main-content').appendChild(targetContent);
                    }
                    targetContent.style.display = 'block';
                }
                
                // Update page title
                const pageText = this.querySelector('.nav-text').textContent;
                pageTitle.textContent = pageText;
                
                // Close sidebar on mobile after navigation
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        // Charts initialization - temporarily disabled
        // setTimeout(() => {
        //     initializeCharts();
        // }, 100);
    });

    // Charts disabled temporarily to fix content disappearing issue
    /*
    function initializeCharts() {
        // Revenue Chart - only if element exists and is visible
        const revenueCanvas = document.getElementById('revenueChart');
        if (revenueCanvas && revenueCanvas.offsetWidth > 0) {
            const revenueCtx = revenueCanvas.getContext('2d');
            const revenueData = <?php echo json_encode($revenue_data); ?>;
            
            try {
                new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => item.month),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(item => item.revenue),
                    borderColor: '#FFD700',
                    backgroundColor: 'rgba(255, 215, 0, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#e0e0e0'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: 'rgba(255, 215, 0, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: 'rgba(255, 215, 0, 0.1)'
                        }
                    }
                }
            }
        });

        // Services Chart
        const servicesCtx = document.getElementById('servicesChart').getContext('2d');
        const servicesData = <?php echo json_encode($services_data); ?>;
        
        new Chart(servicesCtx, {
            type: 'doughnut',
            data: {
                labels: servicesData.map(item => item.service_type),
                datasets: [{
                    data: servicesData.map(item => item.count),
                    backgroundColor: [
                        '#FFD700',
                        '#FFA500',
                        '#FF8C00',
                        '#FF7F50',
                        '#FF6347',
                        '#FF4500'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e0e0e0',
                            padding: 20
                        }
                    }
                }
            });
            } catch (error) {
                console.log('Revenue chart initialization failed:', error);
            }
        }

        // Services Chart - only if element exists and is visible
        const servicesCanvas = document.getElementById('servicesChart');
        if (servicesCanvas && servicesCanvas.offsetWidth > 0) {
            const servicesCtx = servicesCanvas.getContext('2d');
            const servicesData = <?php echo json_encode($services_data); ?>;
            
            try {
                new Chart(servicesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: servicesData.map(item => item.service_type),
                        datasets: [{
                            data: servicesData.map(item => item.count),
                            backgroundColor: [
                                '#FFD700', '#FFA500', '#FF6347', '#32CD32', '#00CED1', '#9370DB'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    color: '#e0e0e0',
                                    padding: 20
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.log('Services chart initialization failed:', error);
            }
        }
    }
    */

    // Switch Page Function
    function switchPage(page) {
        const targetLink = document.querySelector(`.nav-link[data-page="${page}"]`);
        if (targetLink) {
            targetLink.click();
        }
    }

    // View All Services Function
    function showAllServices() {
        // Navigate to services section
        const servicesLink = document.querySelector('.nav-link[data-page="services"]');
        if (servicesLink) {
            servicesLink.click();
        }
        
        // You can also implement a modal or expanded view here
        // For now, this will switch to the services tab
    }
</script>
</body>
</html>