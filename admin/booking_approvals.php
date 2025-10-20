<?php
session_start();
require_once '../includes/config.php';

// Set test admin if not logged in (for development)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_id'] = 1; // Test admin
}

// Handle booking approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'approve') {
            // Approve booking
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'confirmed', 
                    admin_confirmed_by = ?, 
                    admin_confirmed_at = NOW(),
                    admin_notes = ?
                WHERE booking_id = ?
            ");
            $stmt->execute([$_SESSION['admin_id'], $admin_notes, $booking_id]);
            
            // Create notification for user
            $notification_stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_booking_id)
                SELECT user_id, 'confirmation', 'Booking Confirmed!', 
                       'Your booking has been approved. Please proceed with payment.', ?
                FROM bookings WHERE booking_id = ?
            ");
            $notification_stmt->execute([$booking_id, $booking_id]);
            
            // Send email notification
            require_once '../includes/notification_service.php';
            triggerBookingNotification($booking_id, 'confirmed', $pdo);
            
            $message = "Booking approved successfully";
            $alert_type = "success";
            
        } elseif ($action === 'reject') {
            // Reject booking
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'rejected', 
                    admin_confirmed_by = ?, 
                    admin_confirmed_at = NOW(),
                    admin_notes = ?,
                    rejection_reason = ?
                WHERE booking_id = ?
            ");
            $stmt->execute([$_SESSION['admin_id'], $admin_notes, $rejection_reason, $booking_id]);
            
            // Create notification for user
            $notification_stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_booking_id)
                SELECT user_id, 'booking', 'Booking Rejected', 
                       CONCAT('Your booking has been rejected. Reason: ', ?), ?
                FROM bookings WHERE booking_id = ?
            ");
            $notification_stmt->execute([$rejection_reason, $booking_id, $booking_id]);
            
            // Send email notification
            require_once '../includes/notification_service.php';
            triggerBookingNotification($booking_id, 'rejected', $pdo);
            
            $message = "Booking rejected successfully";
            $alert_type = "warning";
        }
        
        // Log activity
        $activity_stmt = $pdo->prepare("
            INSERT INTO activity_logs (admin_id, action, description, related_table, related_id)
            VALUES (?, ?, ?, 'bookings', ?)
        ");
        $activity_stmt->execute([
            $_SESSION['admin_id'],
            "booking_$action",
            "Booking $action by admin",
            $booking_id
        ]);
        
        $pdo->commit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = "Error processing booking: " . $e->getMessage();
        $alert_type = "danger";
    }
}

// Get pending bookings
try {
    $pending_stmt = $pdo->query("
        SELECT b.*, s.service_name, s.category, u.first_name, u.last_name, u.email, u.phone,
               TIMESTAMPDIFF(MINUTE, b.created_at, NOW()) as minutes_ago
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.status = 'pending'
        ORDER BY b.created_at ASC
    ");
    $pending_bookings = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent booking activity
    $recent_stmt = $pdo->query("
        SELECT b.*, s.service_name, u.first_name, u.last_name,
               CASE 
                   WHEN b.status = 'confirmed' THEN 'success'
                   WHEN b.status = 'rejected' THEN 'danger'
                   ELSE 'secondary'
               END as status_color
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.status IN ('confirmed', 'rejected')
        ORDER BY b.admin_confirmed_at DESC
        LIMIT 10
    ");
    $recent_activity = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $pending_bookings = [];
    $recent_activity = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Booking Approvals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 0;
        }
        .booking-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .urgent-booking {
            border-left: 5px solid #dc3545;
        }
        .booking-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
        }
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        .activity-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Admin Panel - Booking Approvals
                    </h3>
                    <p class="mb-0 opacity-75">Manage and approve customer bookings</p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="me-3">Welcome, Admin</span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <?php if (isset($message)): ?>
        <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Statistics -->
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h4><?= count($pending_bookings) ?></h4>
                    <p class="text-muted mb-0">Pending Approvals</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>
                        <?php
                        $today_approved = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND DATE(admin_confirmed_at) = CURDATE()")->fetchColumn();
                        echo $today_approved;
                        ?>
                    </h4>
                    <p class="text-muted mb-0">Approved Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h4>
                        <?php
                        $today_rejected = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'rejected' AND DATE(admin_confirmed_at) = CURDATE()")->fetchColumn();
                        echo $today_rejected;
                        ?>
                    </h4>
                    <p class="text-muted mb-0">Rejected Today</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon text-info">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>
                        <?php
                        $urgent_count = 0;
                        foreach ($pending_bookings as $booking) {
                            if ($booking['minutes_ago'] > 30) $urgent_count++;
                        }
                        echo $urgent_count;
                        ?>
                    </h4>
                    <p class="text-muted mb-0">Urgent (>30min)</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Bookings -->
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Pending Bookings</h5>
                    <button class="btn btn-outline-primary btn-sm" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>

                <?php if (empty($pending_bookings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>All caught up!</h5>
                    <p class="text-muted">No pending bookings require approval.</p>
                </div>
                <?php else: ?>
                
                <?php foreach ($pending_bookings as $booking): ?>
                <div class="booking-card <?= $booking['minutes_ago'] > 30 ? 'urgent-booking' : '' ?>">
                    <div class="booking-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Booking #<?= htmlspecialchars($booking['booking_reference']) ?></h6>
                                <small class="opacity-75">
                                    Submitted <?= $booking['minutes_ago'] ?> minutes ago
                                    <?= $booking['minutes_ago'] > 30 ? ' <i class="fas fa-exclamation-triangle text-warning"></i>' : '' ?>
                                </small>
                            </div>
                            <span class="status-badge bg-warning text-dark">
                                🟡 Pending Review
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <div class="detail-item">
                                    <strong><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['email']) ?></small><br>
                                    <small class="text-muted"><?= htmlspecialchars($booking['phone'] ?? 'No phone') ?></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Service Details</h6>
                                <div class="detail-item">
                                    <strong><?= htmlspecialchars($booking['service_name']) ?></strong><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($booking['category']) ?> • 
                                        <?= ucfirst($booking['vehicle_size']) ?> Vehicle
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <strong>📅 Date & Time</strong><br>
                                <?= date('M j, Y', strtotime($booking['booking_date'])) ?><br>
                                <?= date('g:i A', strtotime($booking['booking_time'])) ?>
                            </div>
                            <div class="detail-item">
                                <strong>📍 Location</strong><br>
                                <small><?= htmlspecialchars(substr($booking['service_address'], 0, 50)) ?>...</small>
                            </div>
                            <div class="detail-item">
                                <strong>💰 Total Amount</strong><br>
                                ₱<?= number_format($booking['total_amount'], 2) ?>
                            </div>
                            <div class="detail-item">
                                <strong>💳 Payment Mode</strong><br>
                                <?= $booking['payment_mode'] === 'deposit_50' ? '50% Deposit' : 'Full Payment' ?>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-approve" onclick="approveBooking(<?= $booking['booking_id'] ?>)">
                                <i class="fas fa-check me-2"></i>Approve Booking
                            </button>
                            <button class="btn btn-reject" onclick="showRejectModal(<?= $booking['booking_id'] ?>)">
                                <i class="fas fa-times me-2"></i>Reject Booking
                            </button>
                            <button class="btn btn-outline-info" onclick="viewDetails(<?= $booking['booking_id'] ?>)">
                                <i class="fas fa-eye me-2"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Activity Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="recent-activity">
                            <?php if (empty($recent_activity)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted mb-0">No recent activity</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <strong>#<?= htmlspecialchars($activity['booking_reference']) ?></strong>
                                        <span class="badge bg-<?= $activity['status_color'] ?>">
                                            <?= ucfirst($activity['status']) ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?><br>
                                        <?= htmlspecialchars($activity['service_name']) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-tools me-2"></i>Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="bookings_calendar.php" class="btn btn-outline-primary">
                                <i class="fas fa-calendar me-2"></i>View Calendar
                            </a>
                            <a href="booking_reports.php" class="btn btn-outline-info">
                                <i class="fas fa-chart-bar me-2"></i>Reports
                            </a>
                            <a href="manage_services.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i>Manage Services
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Booking</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="booking_id" id="rejectBookingId">
                        
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason *</label>
                            <select class="form-select" name="rejection_reason" required>
                                <option value="">Select reason...</option>
                                <option value="Time slot not available">Time slot not available</option>
                                <option value="Service area not covered">Service area not covered</option>
                                <option value="Insufficient information">Insufficient information</option>
                                <option value="Duplicate booking">Duplicate booking</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Notes</label>
                            <textarea class="form-control" name="admin_notes" rows="3" 
                                      placeholder="Additional information for the customer..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveBooking(bookingId) {
            if (confirm('Are you sure you want to approve this booking?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="booking_id" value="${bookingId}">
                    <input type="hidden" name="admin_notes" value="Booking approved by admin">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showRejectModal(bookingId) {
            document.getElementById('rejectBookingId').value = bookingId;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
        
        function viewDetails(bookingId) {
            window.open(`booking_details.php?id=${bookingId}`, '_blank');
        }
        
        // Auto-refresh every 2 minutes
        setInterval(function() {
            window.location.reload();
        }, 120000);
        
        // Show notification count in page title
        const pendingCount = <?= count($pending_bookings) ?>;
        if (pendingCount > 0) {
            document.title = `(${pendingCount}) Admin - Booking Approvals`;
        }
    </script>
</body>
</html>