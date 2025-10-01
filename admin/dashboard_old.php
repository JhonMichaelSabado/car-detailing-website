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
    // Log error for debugging (you can remove this in production)
    error_log("Dashboard query error: " . $e->getMessage());
    // Values already initialized to safe defaults above
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ride Revive Detailing - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css" />
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
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        canvas {
            max-height: 300px;
        }

        #loading-screen {
            font-size: 1.5rem;
            background: radial-gradient(circle at center, #000000 0%, #1a1a1a 50%, #000000 100%);
            overflow: hidden;
        }

        /* TO BE HERO X Style Animations */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideSkew {
            0% {
                opacity: 0;
                transform: translateX(-50%) translateY(20px) skewX(-15deg) scaleX(0);
            }
            100% {
                opacity: 0.8;
                transform: translateX(-50%) translateY(0) skewX(-15deg) scaleX(1);
            }
        }

        @keyframes glitchReveal {
            0% {
                opacity: 1;
                transform: translateX(0);
            }
            10% {
                opacity: 0.8;
                transform: translateX(-2px);
            }
            20% {
                opacity: 0.3;
                transform: translateX(4px);
            }
            30% {
                opacity: 0.9;
                transform: translateX(-3px);
            }
            40% {
                opacity: 0.1;
                transform: translateX(5px);
            }
            50% {
                opacity: 0.7;
                transform: translateX(-1px);
            }
            60% {
                opacity: 0.4;
                transform: translateX(3px);
            }
            70% {
                opacity: 0.9;
                transform: translateX(-2px);
            }
            80% {
                opacity: 0.2;
                transform: translateX(4px);
            }
            90% {
                opacity: 0.8;
                transform: translateX(-1px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes textFillUp {
            0% {
                clip-path: inset(100% 0 0 0);
            }
            100% {
                clip-path: inset(0% 0 0 0);
            }
        }

        @keyframes glitchFlash {
            0%, 90% {
                opacity: 0;
                transform: translateX(0);
            }
            91% {
                opacity: 0.3;
                transform: translateX(2px) skewX(2deg);
            }
            92% {
                opacity: 0;
                transform: translateX(-3px) skewX(-3deg);
            }
            93% {
                opacity: 0.6;
                transform: translateX(1px) skewX(1deg);
            }
            94% {
                opacity: 0;
                transform: translateX(-2px) skewX(-2deg);
            }
            95% {
                opacity: 0.4;
                transform: translateX(3px) skewX(3deg);
            }
            96% {
                opacity: 0;
                transform: translateX(-1px) skewX(-1deg);
            }
            97% {
                opacity: 0.7;
                transform: translateX(2px) skewX(2deg);
            }
            98% {
                opacity: 0;
                transform: translateX(-2px) skewX(-2deg);
            }
            99% {
                opacity: 0.5;
                transform: translateX(1px) skewX(1deg);
            }
            100% {
                opacity: 0;
                transform: translateX(0) skewX(0deg);
            }
        }

        @keyframes progressFill {
            0% {
                width: 0%;
            }
            100% {
                width: 100%;
            }
        }

        @keyframes carDrive {
            0% {
                left: -100px;
            }
            100% {
                left: calc(100% - 20px);
            }
        }

        /* Particle System - Reduced */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 1px;
            height: 1px;
            background: #FFD700;
            border-radius: 50%;
            animation: particleFloat 4s infinite ease-in-out;
            opacity: 0;
        }

        .particle:nth-child(odd) {
            background: #FFA500;
            animation-duration: 6s;
        }

        @keyframes particleFloat {
            0% { 
                opacity: 0;
                transform: translateY(100vh) scale(0);
            }
            20% { 
                opacity: 0.3;
            }
            80% { 
                opacity: 0.3;
            }
            100% { 
                opacity: 0;
                transform: translateY(-100px) scale(1);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div id="loading-screen" style="position: fixed; top:0; left:0; width:100vw; height:100vh; background: linear-gradient(135deg, #000000 0%, #0a0a0a 50%, #000000 100%); display: flex; justify-content: center; align-items: center; z-index: 9999; overflow: hidden;">
        
        <!-- TO BE HERO X Style Loading -->
        <div class="loading__inner" style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%;">
            
            <!-- Main Logo with Glitch Effect -->
            <div class="loading__logo" style="position: relative; margin-bottom: 60px;">
                
                <!-- Catch Text (Like "新プロジェクト始動") -->
                <div class="loading__catch" style="text-align: center; margin-bottom: 30px; position: relative;">
                    <h2 style="font-family: 'Arial', sans-serif; font-size: 20px; color: #FFD700; font-weight: 700; 
                               letter-spacing: 6px; margin: 0 0 8px 0; opacity: 0; 
                               animation: fadeInUp 1s ease-out 0.3s forwards; text-transform: uppercase;">
                        ADMIN CONTROL PANEL
                    </h2>
                    <p style="font-family: 'Arial', sans-serif; font-size: 16px; color: rgba(255, 255, 255, 0.7); font-weight: 400; letter-spacing: 3px; text-transform: uppercase; opacity: 0; animation: fadeInUp 1s ease-out 0.7s forwards; margin: 0;">
                        Premium Car Detailing
                    </p>
                    <span class="bg-skew" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%) skewX(-15deg); width: 120%; height: 2px; background: linear-gradient(90deg, transparent, #FFD700, transparent); opacity: 0; animation: slideSkew 1s ease-out 1s forwards;"></span>
                </div>
                
                <!-- Main Title with Glitch -->
                <div class="glitch" style="position: relative; display: inline-block;">
                    <!-- Background transparent text with stroke -->
                    <h1 style="font-family: 'Impact', 'Arial Black', sans-serif; font-size: 72px; font-weight: 900; 
                               color: transparent; 
                               -webkit-text-stroke: 2px rgba(255, 255, 255, 0.3);
                               text-stroke: 2px rgba(255, 255, 255, 0.3);
                               text-transform: uppercase; letter-spacing: 4px; margin: 0; position: relative;">
                        RIDE REVIVE
                    </h1>
                    
                    <!-- White filling text overlay -->
                    <h1 style="font-family: 'Impact', 'Arial Black', sans-serif; font-size: 72px; font-weight: 900; 
                               color: #FFFFFF; text-transform: uppercase; letter-spacing: 4px; margin: 0; 
                               position: absolute; top: 0; left: 0; right: 0;
                               animation: textFillUp 3.5s ease-out forwards, glitchReveal 0.8s ease-out 3.2s;
                               clip-path: inset(100% 0 0 0);">
                        RIDE REVIVE
                    </h1>
                    
                    <!-- Glitch Layers -->
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #FFD700 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.05s ease-in-out 3.2s 15;"></span>
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #FF6B6B 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.07s ease-in-out 3.3s 10;"></span>
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #4ECDC4 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.06s ease-in-out 3.4s 8;"></span>
                </div>
                
                <!-- Loading Background Bar -->
                <div id="js-loadvalue" style="position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%); width: 400px; height: 3px; background: rgba(255, 215, 0, 0.2); border-radius: 2px; overflow: visible;">
                    <div class="loading__progress" style="height: 100%; width: 0%; background: linear-gradient(90deg, #FFD700, #FFA500); border-radius: 2px; animation: progressFill 4s ease-out forwards;"></div>
                    
                    <!-- Car driving along the progress line -->
                    <div class="car-container" style="position: absolute; top: -45px; left: -100px; width: 120px; height: 75px; animation: carDrive 4s ease-out forwards;">
                        <img src="../images/mini_car.png" alt="Car" style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
                    </div>
                </div>
            </div>
            
            <!-- Bottom Corner Loading Elements -->
            <!-- Loading Text - Bottom Left -->
            <div style="position: fixed; bottom: 30px; left: 30px; font-family: 'Arial', sans-serif;">
                <p style="font-size: 18px; color: #FFD700; font-weight: 700; letter-spacing: 4px; margin: 0; opacity: 0; animation: fadeInUp 1s ease-out 1s forwards;">
                    LOADING
                </p>
            </div>
            
            <!-- Percentage - Bottom Right -->
            <div style="position: fixed; bottom: 30px; right: 30px; font-family: 'Arial', sans-serif;">
                <p style="font-size: 24px; color: #FFFFFF; font-weight: 900; letter-spacing: 2px; margin: 0; opacity: 0; animation: fadeInUp 1s ease-out 1s forwards;">
                    <span class="js-loader-percentage">0</span>%
                </p>
            </div>
            
        </div>
        
        <!-- Subtle Particles -->
        <div class="particles" id="particles" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
        
    </div>
    <div class="dashboard" style="opacity: 0;">
        <header class="navbar">
            <div class="logo" style="animation: fadeInDown 1s ease forwards; opacity: 0;">Ride Revive Admin</div>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="manage-users.php">Manage Users</a>
                <a href="manage-services.php">Manage Services</a>
                <a href="manage-bookings.php">Manage Bookings</a>
            </nav>
            <form action="../auth/logout.php" method="POST" style="margin-left: auto;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </header>
        <main class="dashboard-content" tabindex="0">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p>Manage users, services, bookings, and system overview here.</p>
            <div class="widgets">
                <div class="widget">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo number_format($total_users); ?></p>
                    <p class="stat-label">Registered customers</p>
                </div>
                <div class="widget">
                    <h3>Active Bookings</h3>
                    <p class="stat-number"><?php echo number_format($active_bookings); ?></p>
                    <p class="stat-label">Current appointments</p>
                </div>
                <div class="widget">
                    <h3>Services</h3>
                    <p class="stat-number"><?php echo number_format($services_count); ?></p>
                    <p class="stat-label">Available service types</p>
                </div>
                <div class="widget">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($total_revenue, 2); ?></p>
                    <p class="stat-label">All-time earnings</p>
                </div>
            </div>

            <div class="charts-section">
                <div class="chart-container">
                    <h3>Revenue Trends (Last 6 Months)</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Service Breakdown</h3>
                    <canvas id="servicesChart"></canvas>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Pass PHP data to JS
        const revenueData = <?php echo json_encode($revenue_data); ?>;
        const servicesData = <?php echo json_encode($services_data); ?>;

        // Animate loading screen and then show dashboard
        document.body.style.opacity = 1;
        
        // Create particle system - reduced count
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            if (!particlesContainer) return;
            
            for (let i = 0; i < 20; i++) { // Reduced from 50 to 20
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 3 + 's';
                particlesContainer.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Animate loading percentage from 0% to 100%
        function animateLoadingPercentage() {
            const percentageElement = document.querySelector('.js-loader-percentage');
            if (!percentageElement) return;
            
            let currentPercent = 0;
            const duration = 4000; // 4 seconds to match text fill animation
            const increment = 100 / (duration / 50); // Update every 50ms
            
            const timer = setInterval(() => {
                currentPercent += increment;
                if (currentPercent >= 100) {
                    currentPercent = 100;
                    clearInterval(timer);
                }
                percentageElement.textContent = Math.floor(currentPercent);
            }, 50);
        }
        
        // Start percentage animation
        animateLoadingPercentage();
        
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loading-screen');
            const dashboard = document.querySelector('.dashboard');
            
            // Reduced loading time for better UX
            const minLoadTime = 4000; // Reduced from 8000 to 4000ms (4 seconds)
            const startTime = Date.now();
            
            function showDashboard() {
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, minLoadTime - elapsed);
                
                setTimeout(() => {
                    if (loadingScreen) {
                        // Faster, smoother exit animation
                        loadingScreen.style.transition = 'all 0.8s ease-out';
                        loadingScreen.style.transform = 'scale(1.05)';
                        loadingScreen.style.opacity = '0';
                        
                        setTimeout(() => {
                            loadingScreen.style.display = 'none';
                        }, 800);
                    }
                    
                    if (dashboard) {
                        dashboard.style.transition = 'opacity 0.8s ease-in';
                        dashboard.style.opacity = '1';
                        
                        const logo = document.querySelector('.logo');
                        if (logo) {
                            logo.style.opacity = '1';
                            logo.style.transform = 'translateY(0)';
                        }
                        
                        // Initialize charts after dashboard loads
                        setTimeout(initCharts, 500);
                    }
                }, remainingTime);
            }
            
            showDashboard();
        });

        function initCharts() {
            // Revenue Line Chart
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                const revenueLabels = revenueData.map(item => item.month);
                const revenueValues = revenueData.map(item => parseFloat(item.revenue) || 0);

                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: revenueLabels.length > 0 ? revenueLabels : ['No Data'],
                        datasets: [{
                            label: 'Revenue ($)',
                            data: revenueValues.length > 0 ? revenueValues : [0],
                            borderColor: '#FFD700',
                            backgroundColor: 'rgba(255, 215, 0, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { 
                                    color: '#e0e0e0',
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                },
                                grid: { color: 'rgba(255, 215, 0, 0.1)' }
                            },
                            x: {
                                ticks: { color: '#e0e0e0' },
                                grid: { color: 'rgba(255, 215, 0, 0.1)' }
                            }
                        },
                        plugins: {
                            legend: { labels: { color: '#e0e0e0' } }
                        }
                    }
                });
            }

            // Services Pie Chart
            const servicesCtx = document.getElementById('servicesChart');
            if (servicesCtx) {
                const servicesLabels = servicesData.map(item => item.service_type || 'Unknown');
                const servicesValues = servicesData.map(item => parseInt(item.count) || 0);

                new Chart(servicesCtx, {
                    type: 'pie',
                    data: {
                        labels: servicesLabels.length > 0 ? servicesLabels : ['No Services'],
                        datasets: [{
                            data: servicesValues.length > 0 ? servicesValues : [1],
                            backgroundColor: [
                                '#FFD700',
                                '#FFA500',
                                '#FF6347',
                                '#32CD32',
                                '#4169E1',
                                '#9370DB',
                                '#FF1493',
                                '#00CED1',
                                '#FFA07A',
                                '#98FB98'
                            ],
                            borderColor: '#1a1a1a',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: { color: '#e0e0e0' }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
