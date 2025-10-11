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