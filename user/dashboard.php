<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user information
$user_id = $_SESSION['user_id'];
$user_bookings = 0;
$user_spent = 0;
$recent_bookings = [];

try {
    // Check if bookings table exists
    $bookings_check = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount();
    if ($bookings_check > 0) {
        // User's bookings count
        $stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $user_bookings = $stmt->fetchColumn() ?: 0;
        
        // User's total spent
        $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM bookings WHERE user_id = ? AND total IS NOT NULL");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $user_spent = $stmt->fetchColumn() ?: 0;
        
        // Recent bookings
        $stmt = $db->prepare("
            SELECT service_type, total, status, created_at 
            FROM bookings 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (PDOException $e) {
    error_log("User dashboard query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard - Ride Revive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .user-badge {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: #fff;
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
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
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

        /* Recent Bookings Section */
        .recent-section {
            background: rgba(18, 18, 18, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #FFD700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .booking-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .booking-item:last-child {
            margin-bottom: 0;
        }

        .booking-info h4 {
            color: #FFD700;
            margin-bottom: 5px;
        }

        .booking-info p {
            color: #ccc;
            font-size: 0.9rem;
        }

        .booking-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #4CAF50; color: white; }
        .status-pending { background: #FF9800; color: white; }
        .status-completed { background: #2196F3; color: white; }

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

            .booking-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
                <span class="user-badge">User</span>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">U</div>
                    <span>User Panel</span>
                </div>
                <button class="btn-logout" onclick="window.location.href='../auth/logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <h1 class="page-title">My Dashboard</h1>
            <p class="page-subtitle">Welcome to your personal dashboard. Manage your bookings and view your service history.</p>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-number"><?php echo number_format($user_bookings); ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-number">$<?php echo number_format($user_spent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-number">4.8</div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-gift"></i></div>
                    <div class="stat-number">2</div>
                    <div class="stat-label">Rewards Points</div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="recent-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Recent Bookings
                </h2>
                <?php if (empty($recent_bookings)): ?>
                    <div style="text-align: center; padding: 40px; color: #888;">
                        <i class="fas fa-calendar-plus" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>No bookings yet. Book your first service!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['service_type'] ?: 'Car Detailing Service'); ?></h4>
                                <p>
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                    <?php if ($booking['total']): ?>
                                        • <i class="fas fa-dollar-sign"></i> $<?php echo number_format($booking['total'], 2); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="booking-status status-<?php echo strtolower($booking['status'] ?: 'pending'); ?>">
                                <?php echo ucfirst($booking['status'] ?: 'Pending'); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <h2 style="color: #FFD700; margin-bottom: 20px; font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div class="quick-actions">
                <a href="../booking/create.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-title">New Booking</div>
                    <div class="action-desc">Schedule a new detailing service</div>
                </a>
                <a href="../booking/history.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-history"></i></div>
                    <div class="action-title">Booking History</div>
                    <div class="action-desc">View all your past bookings</div>
                </a>
                <a href="../profile/settings.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-user-cog"></i></div>
                    <div class="action-title">Profile Settings</div>
                    <div class="action-desc">Update your account information</div>
                </a>
                <a href="../support/contact.php" class="action-card">
                    <div class="action-icon"><i class="fas fa-headset"></i></div>
                    <div class="action-title">Support</div>
                    <div class="action-desc">Get help and support</div>
                </a>
            </div>
        </main>
    </div>
</body>
</html>