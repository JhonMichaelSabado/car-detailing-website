<?php
session_start();
require_once '../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

$user_id = $_SESSION['user_id'];

// Get user's bookings
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            s.service_name,
            s.category,
            p.amount as payment_amount,
            p.payment_method,
            p.payment_status,
            p.transaction_id,
            p.payment_date
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.service_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user notifications
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">⏳ Pending Approval</span>',
        'confirmed' => '<span class="badge bg-success">✅ Confirmed</span>',
        'rejected' => '<span class="badge bg-danger">❌ Rejected</span>',
        'completed' => '<span class="badge bg-primary">🎉 Completed</span>',
        'cancelled' => '<span class="badge bg-secondary">🚫 Cancelled</span>',
        'expired' => '<span class="badge bg-dark">⏰ Expired</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light">Unknown</span>';
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">💳 Payment Pending</span>',
        'paid' => '<span class="badge bg-success">✅ Paid</span>',
        'failed' => '<span class="badge bg-danger">❌ Payment Failed</span>',
        'refunded' => '<span class="badge bg-info">💰 Refunded</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-light">No Payment</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - CarDetailing Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin: 20px;
            overflow: hidden;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .dashboard-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .dashboard-header .subtitle {
            opacity: 0.9;
            margin-top: 10px;
        }
        
        .stats-row {
            background: white;
            padding: 20px;
            margin: 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
        }
        
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .stat-card.pending {
            background: linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%);
        }
        
        .stat-card.confirmed {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
        }
        
        .stat-card.completed {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
        }
        
        .booking-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .booking-ref {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .booking-body {
            padding: 25px;
        }
        
        .service-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-item i {
            color: #667eea;
            width: 20px;
        }
        
        .booking-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn-action {
            border-radius: 25px;
            padding: 8px 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .notification-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .notification-item.unread {
            background: #f8f9fa;
            border-left-color: #ffc107;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .nav-tabs .nav-link {
            border-radius: 25px 25px 0 0;
            border: none;
            color: #667eea;
            font-weight: 500;
            margin-right: 5px;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .filter-controls {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> My Dashboard</h1>
                <p class="subtitle">Track your bookings and manage your car detailing services</p>
            </div>
            
            <!-- Statistics Row -->
            <div class="stats-row">
                <div class="row">
                    <?php
                    $stats = [
                        'total' => count($bookings),
                        'pending' => count(array_filter($bookings, fn($b) => $b['status'] === 'pending')),
                        'confirmed' => count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed')),
                        'completed' => count(array_filter($bookings, fn($b) => $b['status'] === 'completed'))
                    ];
                    ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card">
                            <h3><?= $stats['total'] ?></h3>
                            <p><i class="fas fa-calendar-alt"></i> Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card pending">
                            <h3><?= $stats['pending'] ?></h3>
                            <p><i class="fas fa-clock"></i> Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card confirmed">
                            <h3><?= $stats['confirmed'] ?></h3>
                            <p><i class="fas fa-check-circle"></i> Confirmed</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card completed">
                            <h3><?= $stats['completed'] ?></h3>
                            <p><i class="fas fa-trophy"></i> Completed</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">
                        <i class="fas fa-calendar-check"></i> My Bookings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if (count(array_filter($notifications, fn($n) => !$n['is_read']))): ?>
                            <span class="badge bg-danger ms-1"><?= count(array_filter($notifications, fn($n) => !$n['is_read'])) ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="dashboardTabsContent">
                <!-- Bookings Tab -->
                <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                    <!-- Filter Controls -->
                    <div class="filter-controls">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="completed">Completed</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" id="dateFilter" placeholder="Filter by date">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary" onclick="clearFilters()">
                                    <i class="fas fa-refresh"></i> Clear Filters
                                </button>
                                <a href="booking/step1_service_selection.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> New Booking
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bookings List -->
                    <div id="bookingsList">
                        <?php if (empty($bookings)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Bookings Yet</h3>
                                <p>You haven't made any bookings yet. Start by booking your first car detailing service!</p>
                                <a href="booking/step1_service_selection.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus"></i> Book Now
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <div class="booking-card" data-status="<?= $booking['status'] ?>" data-date="<?= $booking['booking_date'] ?>">
                                    <div class="booking-header">
                                        <div class="booking-ref">
                                            <i class="fas fa-hashtag"></i> <?= htmlspecialchars($booking['booking_reference']) ?>
                                        </div>
                                        <div>
                                            <?= getStatusBadge($booking['status']) ?>
                                            <?php if ($booking['payment_status']): ?>
                                                <?= getPaymentStatusBadge($booking['payment_status']) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="booking-body">
                                        <div class="service-info">
                                            <h5><i class="fas fa-car"></i> <?= htmlspecialchars($booking['service_name']) ?></h5>
                                            <p class="text-muted mb-0">Vehicle: <?= ucfirst($booking['vehicle_size']) ?> • Category: <?= ucfirst($booking['category']) ?></p>
                                        </div>
                                        
                                        <div class="detail-grid">
                                            <div class="detail-item">
                                                <i class="fas fa-calendar"></i>
                                                <div>
                                                    <small class="text-muted">Date</small><br>
                                                    <?= date('M j, Y', strtotime($booking['booking_date'])) ?>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <div>
                                                    <small class="text-muted">Time</small><br>
                                                    <?= date('g:i A', strtotime($booking['booking_time'])) ?>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <div>
                                                    <small class="text-muted">Location</small><br>
                                                    <?= htmlspecialchars(substr($booking['service_address'], 0, 30)) ?>...
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-peso-sign"></i>
                                                <div>
                                                    <small class="text-muted">Total Amount</small><br>
                                                    ₱<?= number_format($booking['total_amount'], 2) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['addon_services']): ?>
                                            <div class="mb-3">
                                                <small class="text-muted">Add-on Services:</small>
                                                <div class="mt-1">
                                                    <?php
                                                    $addons = json_decode($booking['addon_services'], true);
                                                    foreach ($addons as $addon) {
                                                        echo '<span class="badge bg-light text-dark me-1">' . htmlspecialchars($addon['name']) . '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="booking-actions">
                                            <button class="btn btn-outline-primary btn-action" onclick="viewBookingDetails(<?= $booking['booking_id'] ?>)">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                            
                                            <?php if ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'pending'): ?>
                                                <a href="payment.php?booking=<?= $booking['booking_id'] ?>" class="btn btn-success btn-action">
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-danger btn-action" onclick="cancelBooking(<?= $booking['booking_id'] ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['payment_status'] === 'paid'): ?>
                                                <button class="btn btn-outline-info btn-action" onclick="downloadReceipt(<?= $booking['booking_id'] ?>)">
                                                    <i class="fas fa-download"></i> Receipt
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($booking['status'] === 'completed'): ?>
                                                <button class="btn btn-outline-warning btn-action" onclick="leaveReview(<?= $booking['booking_id'] ?>)">
                                                    <i class="fas fa-star"></i> Review
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notifications Tab -->
                <div class="tab-pane fade" id="notifications" role="tabpanel">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6><?= htmlspecialchars($notification['title']) ?></h6>
                                        <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn btn-sm btn-outline-primary" onclick="markAsRead(<?= $notification['id'] ?>)">
                                            Mark Read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('statusFilter').addEventListener('change', filterBookings);
        document.getElementById('dateFilter').addEventListener('change', filterBookings);
        
        function filterBookings() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const bookings = document.querySelectorAll('.booking-card');
            
            bookings.forEach(booking => {
                let show = true;
                
                if (statusFilter && booking.dataset.status !== statusFilter) {
                    show = false;
                }
                
                if (dateFilter && booking.dataset.date !== dateFilter) {
                    show = false;
                }
                
                booking.style.display = show ? 'block' : 'none';
            });
        }
        
        function clearFilters() {
            document.getElementById('statusFilter').value = '';
            document.getElementById('dateFilter').value = '';
            document.querySelectorAll('.booking-card').forEach(booking => {
                booking.style.display = 'block';
            });
        }
        
        function viewBookingDetails(bookingId) {
            fetch(`ajax/get_booking_details.php?id=${bookingId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('bookingDetailsContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading booking details');
                });
        }
        
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('ajax/cancel_booking.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `booking_id=${bookingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error cancelling booking');
                    }
                });
            }
        }
        
        function downloadReceipt(bookingId) {
            window.open(`receipt.php?booking=${bookingId}`, '_blank');
        }
        
        function leaveReview(bookingId) {
            window.location.href = `review.php?booking=${bookingId}`;
        }
        
        function markAsRead(notificationId) {
            fetch('ajax/mark_notification_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            if (document.getElementById('notifications-tab').classList.contains('active')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>