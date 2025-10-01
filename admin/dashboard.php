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
$services_count = 0;
$total_revenue = 0;
$revenue_data = [];
$services_data = [];

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
        
        // Dynamic services count (unique service types from bookings)
        $services_count = $db->query("SELECT COUNT(DISTINCT service_type) FROM bookings WHERE service_type IS NOT NULL AND service_type != ''")->fetchColumn() ?: 0;
        
        // Total revenue
        $total_revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE total IS NOT NULL")->fetchColumn() ?: 0;
        
        // Monthly revenue for last 6 months
        $revenue_stmt = $db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COALESCE(SUM(total), 0) as revenue
            FROM bookings 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND total IS NOT NULL
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $revenue_data = $revenue_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        
        // Service breakdown for pie chart
        $services_stmt = $db->query("
            SELECT service_type, COUNT(*) as count
            FROM bookings 
            WHERE service_type IS NOT NULL AND service_type != ''
            GROUP BY service_type
            ORDER BY count DESC
            LIMIT 10
        ");
        $services_data = $services_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: #e0e0e0;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../images/backg.png') center/cover no-repeat;
            opacity: 0.05;
            z-index: -1;
        }

        .dashboard {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 900;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .admin-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

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

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            color: #ccc;
            font-size: 1.1rem;
            margin-bottom: 40px;
            font-weight: 300;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FFD700, #FFA500);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 215, 0, 0.2);
            border-color: rgba(255, 215, 0, 0.4);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.5rem;
            color: #000;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #FFD700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #ccc;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        @media (max-width: 1200px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .main-content {
                padding: 20px;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .user-info span {
                display: none;
            }
        }

        /* Loading Animation */
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
    <!-- Main Dashboard -->
    <div class="dashboard fade-in">
        <!-- Header -->
        <header class="header">
            <div class="logo-section">
                <h1 class="logo">RIDE REVIVE</h1>
                <span class="admin-badge">Admin</span>
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

        <!-- Main Content -->
        <main class="main-content">
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
                    <div class="stat-icon"><i class="fas fa-car"></i></div>
                    <div class="stat-number"><?php echo number_format($services_count); ?></div>
                    <div class="stat-label">Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
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
        </main>
    </div>

    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = <?php echo json_encode($revenue_data); ?>;
            
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
                }
            });
        });
    </script>
</body>
</html>