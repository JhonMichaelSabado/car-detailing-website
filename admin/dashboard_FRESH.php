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

// Get real data from database
$admin_stats = $carDB->getAdminStats();
$all_bookings = $carDB->getAllBookings(null, 50);
$pending_bookings = $carDB->getAllBookings('pending', 20);
$recent_activity = $carDB->getRecentActivity(15);

// Extract stats for easier use with proper defaults
$total_users = $admin_stats['total_users'] ?? 0;
$total_bookings = $admin_stats['total_bookings'] ?? 0;
$pending_count = $admin_stats['pending_bookings'] ?? 0;
$completed_bookings = $admin_stats['completed_bookings'] ?? 0;
$total_revenue = $admin_stats['total_revenue'] ?? 0;
$today_revenue = $admin_stats['today_revenue'] ?? 0;
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            line-height: 1.6;
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
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid #333;
            text-align: center;
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: #0f0f0f;
        }

        .header {
            background: rgba(26, 26, 26, 0.95);
            padding: 20px 30px;
            border-bottom: 1px solid #333;
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 28px;
            font-weight: 600;
            color: #FFD700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-weight: bold;
        }

        /* Content Area */
        .content-area {
            padding: 30px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            border: 1px solid #333;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #FFD700;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.1);
        }

        .stat-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-info h3 {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #cccccc;
            font-size: 14px;
        }

        .stat-icon {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* Content Cards */
        .content-card {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            border: 1px solid #333;
            border-radius: 15px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            color: #FFD700;
            font-size: 20px;
            font-weight: 600;
        }

        .card-content {
            padding: 0;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #333;
        }

        .data-table th {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            color: #cccccc;
        }

        .data-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            color: #f44336;
        }

        /* Action Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .btn-action {
            padding: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 2px;
        }

        .btn-confirm {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .btn-confirm:hover {
            background: #4CAF50;
            color: white;
        }

        .btn-decline {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .btn-decline:hover {
            background: #f44336;
            color: white;
        }

        .btn-info {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }

        .btn-info:hover {
            background: #2196F3;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            color: #FFD700;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #cccccc;
            margin-bottom: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #FFD700;
                color: #1a1a1a;
                border: none;
                padding: 12px;
                border-radius: 8px;
                cursor: pointer;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .mobile-menu-btn {
            display: none;
        }

        /* Customer Info Styles */
        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .customer-info i {
            color: #FFD700;
        }

        .vehicle-badge {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .datetime-info {
            font-size: 13px;
        }

        .datetime-info div {
            margin-bottom: 3px;
        }

        .datetime-info i {
            color: #FFD700;
            margin-right: 5px;
            width: 12px;
        }

        .amount {
            font-weight: 600;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
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
            <a href="#" class="nav-link active" onclick="showSection('dashboard', this)">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="#" class="nav-link" onclick="showSection('bookings', this)">
                <i class="fas fa-calendar-check"></i>
                Bookings
            </a>
            <a href="#" class="nav-link" onclick="showSection('users', this)">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="#" class="nav-link" onclick="showSection('services', this)">
                <i class="fas fa-car-wash"></i>
                Services
            </a>
            <a href="#" class="nav-link" onclick="showSection('analytics', this)">
                <i class="fas fa-chart-line"></i>
                Analytics
            </a>
            <a href="#" class="nav-link" onclick="showSection('settings', this)">
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
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1 class="header-title">Admin Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 2)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($total_users); ?></h3>
                                <p>Total Users</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo number_format($total_bookings); ?></h3>
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
                                <h3><?php echo number_format($pending_count); ?></h3>
                                <p>Pending Bookings</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                                <p>Total Revenue</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-peso-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Activity</h3>
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-refresh"></i>
                            Refresh
                        </button>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($recent_activity)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Activity</th>
                                            <th>User</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_activity as $activity): ?>
                                            <tr>
                                                <td><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                                                <td><?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></td>
                                                <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <h3>No Recent Activity</h3>
                                <p>Activity will appear here as users interact with the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Bookings Section -->
            <section id="bookings" class="content-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo count($pending_bookings); ?></h3>
                                <p>Pending Bookings</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <h3><?php echo $completed_bookings; ?></h3>
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
                                <h3>₱<?php echo number_format($today_revenue, 2); ?></h3>
                                <p>Today's Revenue</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-peso-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Bookings -->
                <?php if (!empty($pending_bookings)): ?>
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Pending Bookings - Action Required</h3>
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh"></i>
                                Refresh
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
                                                        <?php echo ucfirst($booking['vehicle_size']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="datetime-info">
                                                        <div><i class="fas fa-calendar"></i><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                                        <div><i class="fas fa-clock"></i><?php echo date('h:i A', strtotime($booking['booking_date'])); ?></div>
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
                                                    <button class="btn-action btn-info" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="Details">
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
                        <h3>All Bookings</h3>
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
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Reference</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Payment Status</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_bookings as $booking): ?>
                                        <tr data-status="<?php echo $booking['status']; ?>">
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['booking_reference'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td class="amount">₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($booking['payment_method'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($booking['payment_status'] ?? ''); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Users Section -->
            <section id="users" class="content-section">
                <div class="content-card">
                    <div class="card-header">
                        <h3>User Management</h3>
                    </div>
                    <div class="card-content">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>User Management</h3>
                            <p>User management features coming soon.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section id="services" class="content-section">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Service Management</h3>
                    </div>
                    <div class="card-content">
                        <div class="empty-state">
                            <i class="fas fa-car-wash"></i>
                            <h3>Service Management</h3>
                            <p>Service management features coming soon.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Analytics Section -->
            <section id="analytics" class="content-section">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Analytics & Reports</h3>
                    </div>
                    <div class="card-content">
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h3>Analytics</h3>
                            <p>Analytics and reporting features coming soon.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings" class="content-section">
                <div class="content-card">
                    <div class="card-header">
                        <h3>System Settings</h3>
                    </div>
                    <div class="card-content">
                        <div class="empty-state">
                            <i class="fas fa-cogs"></i>
                            <h3>Settings</h3>
                            <p>System settings coming soon.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Navigation Functions
        function showSection(sectionId, linkEl) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });

            // Show selected section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            // Update nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            if (linkEl) {
                linkEl.classList.add('active');
            }

            // Close mobile sidebar
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('open');
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Booking Management Functions
        function confirmBooking(bookingId) {
            if (confirm('Are you sure you want to confirm this booking?')) {
                const formData = new FormData();
                formData.append('action', 'confirm');
                formData.append('booking_id', bookingId);

                fetch('manage_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Booking confirmed successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }

        function declineBooking(bookingId) {
            const reason = prompt('Enter reason for declining (optional):') || 'Declined by admin';
            
            if (confirm('Are you sure you want to decline this booking?')) {
                const formData = new FormData();
                formData.append('action', 'decline');
                formData.append('booking_id', bookingId);
                formData.append('reason', reason);

                fetch('manage_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Booking declined successfully!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }

        function viewBooking(bookingId) {
            // Fetch booking details from backend
            fetch('get_booking_details.php?id=' + bookingId)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        showNotification('Error: ' + data.error, 'error');
                        return;
                    }
                    // Build modal content
                    let html = `<div style='padding:20px;'>`;
                    html += `<h2>Booking Reference: <span style='color:#FFD700;'>${data.booking_reference}</span></h2>`;
                    html += `<hr>`;
                    html += `<h4>Customer Info</h4>`;
                    html += `<div><b>Name:</b> ${data.customer_name || ''}</div>`;
                    html += `<div><b>Email:</b> ${data.customer_email || ''}</div>`;
                    html += `<div><b>Phone:</b> ${data.customer_phone || ''}</div>`;
                    html += `<hr>`;
                    html += `<h4>Service Details</h4>`;
                    html += `<div><b>Service:</b> ${data.service_name || ''}</div>`;
                    html += `<div><b>Category:</b> ${data.category || ''}</div>`;
                    html += `<div><b>Vehicle Size:</b> ${data.vehicle_size || ''}</div>`;
                    html += `<div><b>Add-ons:</b> ${(data.addons && data.addons.length) ? data.addons.map(a=>a.service_name).join(', ') : 'None'}</div>`;
                    html += `<div><b>Date:</b> ${data.booking_date || ''}</div>`;
                    html += `<div><b>Time:</b> ${data.booking_time || ''}</div>`;
                    html += `<div><b>Address:</b> ${data.service_address || ''}</div>`;
                    html += `<div><b>Landmark:</b> ${data.landmark_instructions || ''}</div>`;
                    html += `<hr>`;
                    html += `<h4>Payment</h4>`;
                    html += `<div><b>Payment Method:</b> ${data.payment_method || ''}</div>`;
                    html += `<div><b>Payment Status:</b> ${data.payment_status || ''}</div>`;
                    html += `<div><b>Total Amount:</b> ₱${Number(data.total_amount).toLocaleString(undefined, {minimumFractionDigits:2})}</div>`;
                    if (data.payment) {
                        html += `<div><b>Deposit Amount:</b> ₱${Number(data.payment.amount).toLocaleString(undefined, {minimumFractionDigits:2})}</div>`;
                        html += `<div><b>Payment Type:</b> ${data.payment.payment_type || ''}</div>`;
                        html += `<div><b>Transaction ID:</b> ${data.payment.transaction_id || ''}</div>`;
                    }
                    html += `<hr>`;
                    html += `<div><b>Status:</b> <span style='color:#FFD700;'>${data.status || ''}</span></div>`;
                    html += `</div>`;
                    showModal(html, 'Booking Details');
                })
                .catch(err => {
                    showNotification('Failed to fetch booking details', 'error');
                });
        }

        // Modal utility
        function showModal(content, title) {
            let modal = document.getElementById('customModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'customModal';
                modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);z-index:10001;display:flex;align-items:center;justify-content:center;';
                modal.innerHTML = `<div id='modalInner' style='background:#222;padding:30px 30px 20px 30px;border-radius:16px;max-width:500px;width:95vw;box-shadow:0 8px 32px #000;position:relative;'>
                    <h2 style='margin-top:0;color:#FFD700;'>${title||''}</h2>
                    <div id='modalContent'></div>
                    <button id='closeModalBtn' style='position:absolute;top:10px;right:10px;background:#FFD700;color:#222;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;'>×</button>
                </div>`;
                document.body.appendChild(modal);
                document.getElementById('closeModalBtn').onclick = () => modal.remove();
            }
            document.getElementById('modalContent').innerHTML = content;
            modal.style.display = 'flex';
        }

        function filterBookings(status) {
            const rows = document.querySelectorAll('#bookings table tbody tr[data-status]');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const rowStatus = row.getAttribute('data-status');
                    row.style.display = rowStatus === status ? '' : 'none';
                }
            });
        }

        // Simple notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                font-weight: 600;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 4000);
        }

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });

        console.log('Fresh Admin Dashboard loaded successfully!');
    </script>
</body>
</html>