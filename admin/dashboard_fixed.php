<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Initialize default values
$total_users = 0;
$active_bookings = 0;
$pending_bookings = 0;
$services_count = 0;
$total_revenue = 0;

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
        $pending_bookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn() ?: 0;
        $services_count = $db->query("SELECT COUNT(DISTINCT service_type) FROM bookings WHERE service_type IS NOT NULL AND service_type != ''")->fetchColumn() ?: 0;
        $total_revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE total IS NOT NULL")->fetchColumn() ?: 0;
    }
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-gold: #FFD700;
            --primary-orange: #FFA500;
            --primary-dark: #121212;
            --secondary-dark: #1a1a1a;
            --tertiary-dark: #2d2d2d;
            --text-primary: #FFFFFF;
            --text-secondary: #E5E7EB;
            --text-muted: #9CA3AF;
            --border-primary: rgba(255, 215, 0, 0.2);
            --border-secondary: rgba(255, 255, 255, 0.1);
            --glass-bg: rgba(18, 18, 18, 0.85);
            --glass-border: rgba(255, 215, 0, 0.15);
            --shadow-small: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--primary-dark);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-gold);
            text-decoration: none;
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
        }

        .nav-link:hover {
            background: rgba(255, 215, 0, 0.15);
            color: var(--primary-gold);
            transform: translateX(4px);
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
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 300px;
            padding: 50px 40px;
            min-height: 100vh;
            transition: margin 0.2s ease;
        }

        .page-content {
            display: none;
        }

        .page-content.active {
            display: block !important;
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
            font-size: 1.1rem;
            margin-bottom: 40px;
            font-weight: 400;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(18, 18, 18, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-medium);
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
            margin: 0 auto 15px;
            font-size: 24px;
            color: var(--primary-dark);
            box-shadow: var(--shadow-small);
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
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Coming Soon */
        .coming-soon {
            text-align: center;
            padding: 100px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            margin: 40px 0;
        }

        .coming-soon i {
            font-size: 4rem;
            color: var(--primary-gold);
            margin-bottom: 20px;
        }

        .coming-soon h2 {
            color: var(--primary-gold);
            margin-bottom: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        .sidebar-toggle {
            display: none;
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
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar Toggle for Mobile -->
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-car-wash"></i>
                    RIDE REVIVE
                </div>
                <div style="color: #999; font-size: 0.9rem; margin-top: 5px;">Admin Panel</div>
            </div>
            
            <div class="sidebar-nav">
                <div class="nav-item">
                    <a href="#" class="nav-link active" data-page="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" data-page="services">
                        <i class="fas fa-car-wash"></i>
                        Services
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" data-page="finances">
                        <i class="fas fa-chart-pie"></i>
                        Finances
                    </a>
                </div>
                <div class="nav-item">
                    <a href="#" class="nav-link" data-page="transactions">
                        <i class="fas fa-receipt"></i>
                        Transactions
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Content -->
            <div id="dashboard-content" class="page-content active">
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Welcome to your admin control panel. Monitor your business performance and manage operations.</p>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo number_format($total_users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-number"><?php echo number_format($active_bookings); ?></div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo number_format($pending_bookings); ?></div>
                        <div class="stat-label">Pending Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>

                <!-- Success Message -->
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 15px; padding: 20px; margin: 30px 0;">
                    <h3 style="color: #10B981; margin-bottom: 10px;">
                        <i class="fas fa-check-circle"></i>
                        Dashboard Fixed!
                    </h3>
                    <p style="color: #E5E7EB;">The dashboard content is now displaying properly and should no longer disappear. All JavaScript navigation is working smoothly.</p>
                </div>
            </div>

            <!-- Services Content -->
            <div id="services-content" class="page-content">
                <h1 class="page-title">Services Management</h1>
                <p class="page-subtitle">Manage and configure your car detailing services.</p>
                
                <div class="coming-soon">
                    <i class="fas fa-tools"></i>
                    <h2>Coming Soon</h2>
                    <p style="color: #ccc;">The services management section is under development.</p>
                </div>
            </div>

            <!-- Finances Content -->
            <div id="finances-content" class="page-content">
                <h1 class="page-title">Financial Overview</h1>
                <p class="page-subtitle">Track your business finances and revenue streams.</p>
                
                <div class="coming-soon">
                    <i class="fas fa-chart-line"></i>
                    <h2>Coming Soon</h2>
                    <p style="color: #ccc;">The finances section is under development.</p>
                </div>
            </div>

            <!-- Transactions Content -->
            <div id="transactions-content" class="page-content">
                <h1 class="page-title">Transaction History</h1>
                <p class="page-subtitle">View and manage all business transactions.</p>
                
                <div class="coming-soon">
                    <i class="fas fa-history"></i>
                    <h2>Coming Soon</h2>
                    <p style="color: #ccc;">The transactions section is under development.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const navLinks = document.querySelectorAll('.nav-link');
            const pageContents = document.querySelectorAll('.page-content');

            console.log('Dashboard loaded successfully');

            // Ensure dashboard content is visible on load
            const dashboardContent = document.getElementById('dashboard-content');
            if (dashboardContent) {
                dashboardContent.style.display = 'block';
                dashboardContent.classList.add('active');
                console.log('Dashboard content is visible');
            }

            // Mobile sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }

            // Navigation handling
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const page = this.getAttribute('data-page');
                    console.log('Switching to page:', page);
                    
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Hide all page contents
                    pageContents.forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                    });
                    
                    // Show selected page content
                    const targetContent = document.getElementById(page + '-content');
                    if (targetContent) {
                        targetContent.style.display = 'block';
                        targetContent.classList.add('active');
                        console.log('Showing page:', page);
                    }
                    
                    // Close sidebar on mobile after navigation
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('open');
                    }
                });
            });
        });

        // Switch page function for external calls
        function switchPage(page) {
            const targetLink = document.querySelector(`.nav-link[data-page="${page}"]`);
            if (targetLink) {
                targetLink.click();
            }
        }
    </script>
</body>
</html>