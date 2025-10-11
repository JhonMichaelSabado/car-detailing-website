<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/database_functions.php';

$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

// Get recent bookings and payments
$recent_bookings = $carDB->getAllBookings(20);
$recent_payments = $carDB->getAllPayments(20);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Integration Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #1a1a1a;
            color: #ffffff;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #444;
        }

        .header h1 {
            color: #FFD700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #444;
        }

        .section h2 {
            color: #FFD700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }

        th {
            background: #333;
            color: #FFD700;
            font-weight: 600;
        }

        tr:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.pending {
            background: #ff9800;
            color: #000;
        }

        .status.confirmed {
            background: #4caf50;
            color: #000;
        }

        .status.completed {
            background: #2196f3;
            color: #fff;
        }

        .payment-status.paid {
            background: #4caf50;
            color: #000;
        }

        .payment-status.pending {
            background: #ff9800;
            color: #000;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            padding: 20px;
            border-radius: 12px;
            border: 2px solid #333;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
        }

        .stat-label {
            color: #ccc;
            margin-top: 5px;
        }

        .refresh-btn {
            background: #FFD700;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }

        .refresh-btn:hover {
            background: #e6c200;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-cogs"></i>
                Admin Integration Test Dashboard
            </h1>
            <p>Monitor bookings and payments in real-time</p>
            <a href="javascript:location.reload()" class="refresh-btn">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($recent_bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($recent_payments); ?></div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $pending_count = 0;
                    foreach($recent_bookings as $booking) {
                        if($booking['status'] === 'pending') $pending_count++;
                    }
                    echo $pending_count;
                    ?>
                </div>
                <div class="stat-label">Pending Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    ₱<?php 
                    $total_revenue = 0;
                    foreach($recent_payments as $payment) {
                        if($payment['status'] === 'paid') {
                            $total_revenue += $payment['amount'];
                        }
                    }
                    echo number_format($total_revenue, 2);
                    ?>
                </div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="section">
            <h2>
                <i class="fas fa-calendar-alt"></i>
                Recent Bookings
            </h2>
            <?php if (empty($recent_bookings)): ?>
                <p style="color: #ccc; font-style: italic;">No bookings found. Create a test booking to see data here.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Service</th>
                            <th>Vehicle Size</th>
                            <th>Date & Time</th>
                            <th>Vehicle Details</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td><strong>#<?php echo $booking['booking_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['username'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($booking['service_name'] ?? 'N/A'); ?></td>
                            <td><?php echo ucfirst($booking['vehicle_size']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['vehicle_details']); ?></td>
                            <td><span class="status <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            <td><?php echo date('M j, g:i A', strtotime($booking['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Payments -->
        <div class="section">
            <h2>
                <i class="fas fa-credit-card"></i>
                Recent Payments
            </h2>
            <?php if (empty($recent_payments)): ?>
                <p style="color: #ccc; font-style: italic;">No payments found. Complete a booking to see payment data here.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                        <tr>
                            <td><strong>#<?php echo $payment['payment_id']; ?></strong></td>
                            <td><strong>#<?php echo $payment['booking_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($payment['username'] ?? 'N/A'); ?></td>
                            <td><strong>₱<?php echo number_format($payment['amount'], 2); ?></strong></td>
                            <td><?php echo ucfirst($payment['method']); ?></td>
                            <td><?php echo htmlspecialchars($payment['reference'] ?? 'N/A'); ?></td>
                            <td><span class="payment-status <?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span></td>
                            <td><?php echo date('M j, g:i A', strtotime($payment['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Integration Test Instructions -->
        <div class="section">
            <h2>
                <i class="fas fa-flask"></i>
                Integration Test Steps
            </h2>
            <ol style="color: #ccc; padding-left: 20px;">
                <li>Go to <strong>User Dashboard</strong> → <a href="../user/dashboard_CLEAN.php" target="_blank" style="color: #FFD700;">dashboard_CLEAN.php</a></li>
                <li>Click <strong>"Booking Guide"</strong> to understand the workflow</li>
                <li>Select a service and click <strong>"Book Now"</strong></li>
                <li>Fill in all details and select payment option</li>
                <li>Submit booking → should redirect to <strong>payment.php</strong></li>
                <li>Complete payment → should create payment record</li>
                <li>Check this admin dashboard for new entries</li>
                <li>Verify booking and payment data appears in tables above</li>
            </ol>
        </div>
    </div>
</body>
</html>