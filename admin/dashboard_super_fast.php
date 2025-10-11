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