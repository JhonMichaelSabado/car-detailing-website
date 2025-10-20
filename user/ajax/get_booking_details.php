<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['error' => 'Unauthorized or missing booking ID']);
    exit();
}

$booking_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get detailed booking information
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            s.service_name,
            s.category,
            s.description as service_description,
            p.amount as payment_amount,
            p.payment_method,
            p.payment_status,
            p.transaction_id,
            p.payment_date,
            u.first_name,
            u.last_name,
            u.email,
            u.phone
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.service_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        LEFT JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }
    
    // Get add-on services if any
    $addons = $booking['addon_services'] ? json_decode($booking['addon_services'], true) : [];
    
    // Calculate payment amounts
    $deposit_amount = $booking['payment_mode'] === 'deposit_50' ? $booking['deposit_amount'] : 0;
    $remaining_amount = $booking['total_amount'] - $deposit_amount;
    
    // Format the response as HTML
    ob_start();
?>
    <div class="booking-details-content">
        <!-- Booking Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h4 class="text-primary"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($booking['booking_reference']) ?></h4>
                <p class="text-muted mb-0">Created: <?= date('M j, Y g:i A', strtotime($booking['created_at'])) ?></p>
            </div>
            <div class="col-md-6 text-end">
                <div class="mb-2">
                    <?php
                    $status_badges = [
                        'pending' => '<span class="badge bg-warning fs-6">⏳ Pending Approval</span>',
                        'confirmed' => '<span class="badge bg-success fs-6">✅ Confirmed</span>',
                        'rejected' => '<span class="badge bg-danger fs-6">❌ Rejected</span>',
                        'completed' => '<span class="badge bg-primary fs-6">🎉 Completed</span>',
                        'cancelled' => '<span class="badge bg-secondary fs-6">🚫 Cancelled</span>',
                        'expired' => '<span class="badge bg-dark fs-6">⏰ Expired</span>'
                    ];
                    echo $status_badges[$booking['status']] ?? '<span class="badge bg-light fs-6">Unknown</span>';
                    ?>
                </div>
                <?php if ($booking['payment_status']): ?>
                    <div>
                        <?php
                        $payment_badges = [
                            'pending' => '<span class="badge bg-warning fs-6">💳 Payment Pending</span>',
                            'paid' => '<span class="badge bg-success fs-6">✅ Paid</span>',
                            'failed' => '<span class="badge bg-danger fs-6">❌ Payment Failed</span>',
                            'refunded' => '<span class="badge bg-info fs-6">💰 Refunded</span>'
                        ];
                        echo $payment_badges[$booking['payment_status']] ?? '<span class="badge bg-light fs-6">No Payment</span>';
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Service Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-car"></i> Service Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><?= htmlspecialchars($booking['service_name']) ?></h6>
                        <p class="text-muted"><?= htmlspecialchars($booking['service_description']) ?></p>
                        <p><strong>Category:</strong> <?= ucfirst($booking['category']) ?></p>
                        <p><strong>Vehicle Size:</strong> <?= ucfirst($booking['vehicle_size']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?= date('l, F j, Y', strtotime($booking['booking_date'])) ?></p>
                        <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['booking_time'])) ?></p>
                        <p><strong>Duration:</strong> <?= $booking['estimated_duration'] ?> minutes</p>
                    </div>
                </div>
                
                <?php if (!empty($addons)): ?>
                    <div class="mt-3">
                        <h6>Add-on Services:</h6>
                        <div class="row">
                            <?php foreach ($addons as $addon): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span><?= htmlspecialchars($addon['name']) ?></span>
                                        <span class="text-success">+₱<?= number_format($addon['price'], 2) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Location Information -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Location Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Service Address:</strong></p>
                        <p><?= htmlspecialchars($booking['service_address']) ?></p>
                        <?php if ($booking['address_notes']): ?>
                            <p><strong>Address Notes:</strong><br>
                            <?= htmlspecialchars($booking['address_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <?php if ($booking['travel_distance']): ?>
                            <p><strong>Distance:</strong> <?= $booking['travel_distance'] ?> km</p>
                        <?php endif; ?>
                        <?php if ($booking['travel_fee'] > 0): ?>
                            <p><strong>Travel Fee:</strong> ₱<?= number_format($booking['travel_fee'], 2) ?></p>
                        <?php endif; ?>
                        <?php if ($booking['coordinates']): ?>
                            <p><strong>Coordinates:</strong> <?= htmlspecialchars($booking['coordinates']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing Information -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Pricing Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <td>Base Service Price:</td>
                                <td class="text-end">₱<?= number_format($booking['base_price'], 2) ?></td>
                            </tr>
                            <?php if (!empty($addons)): ?>
                                <?php foreach ($addons as $addon): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($addon['name']) ?>:</td>
                                        <td class="text-end">+₱<?= number_format($addon['price'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if ($booking['travel_fee'] > 0): ?>
                                <tr>
                                    <td>Travel Fee:</td>
                                    <td class="text-end">+₱<?= number_format($booking['travel_fee'], 2) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($booking['promo_discount'] > 0): ?>
                                <tr class="text-success">
                                    <td>Promo Discount:</td>
                                    <td class="text-end">-₱<?= number_format($booking['promo_discount'], 2) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <th>Total Amount:</th>
                                <th class="text-end">₱<?= number_format($booking['total_amount'], 2) ?></th>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Information:</h6>
                        <p><strong>Payment Mode:</strong> 
                        <?= $booking['payment_mode'] === 'deposit_50' ? '50% Deposit' : '100% Full Payment' ?></p>
                        
                        <?php if ($booking['payment_mode'] === 'deposit_50'): ?>
                            <p><strong>Deposit Amount:</strong> ₱<?= number_format($booking['deposit_amount'], 2) ?></p>
                            <p><strong>Remaining Amount:</strong> ₱<?= number_format($remaining_amount, 2) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($booking['payment_status']): ?>
                            <p><strong>Payment Status:</strong> <?= ucfirst($booking['payment_status']) ?></p>
                            <?php if ($booking['payment_method']): ?>
                                <p><strong>Payment Method:</strong> <?= ucfirst($booking['payment_method']) ?></p>
                            <?php endif; ?>
                            <?php if ($booking['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?= htmlspecialchars($booking['transaction_id']) ?></p>
                            <?php endif; ?>
                            <?php if ($booking['payment_date']): ?>
                                <p><strong>Payment Date:</strong> <?= date('M j, Y g:i A', strtotime($booking['payment_date'])) ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Information -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Customer Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <?php if ($booking['special_requests']): ?>
                            <p><strong>Special Requests:</strong><br>
                            <?= htmlspecialchars($booking['special_requests']) ?></p>
                        <?php endif; ?>
                        <?php if ($booking['customer_notes']): ?>
                            <p><strong>Customer Notes:</strong><br>
                            <?= htmlspecialchars($booking['customer_notes']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin Information -->
        <?php if ($booking['status'] === 'rejected' && $booking['rejection_reason']): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Rejection Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Rejection Reason:</strong><br>
                    <?= htmlspecialchars($booking['rejection_reason']) ?></p>
                    <?php if ($booking['admin_notes']): ?>
                        <p><strong>Admin Notes:</strong><br>
                        <?= htmlspecialchars($booking['admin_notes']) ?></p>
                    <?php endif; ?>
                    <?php if ($booking['admin_action_date']): ?>
                        <p><strong>Action Date:</strong> <?= date('M j, Y g:i A', strtotime($booking['admin_action_date'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <?php if ($booking['status'] === 'confirmed' && $booking['payment_status'] === 'pending'): ?>
                <a href="../payment.php?booking=<?= $booking['booking_id'] ?>" class="btn btn-success btn-lg me-2">
                    <i class="fas fa-credit-card"></i> Complete Payment
                </a>
            <?php endif; ?>
            
            <?php if ($booking['payment_status'] === 'paid'): ?>
                <button class="btn btn-info btn-lg me-2" onclick="downloadReceipt(<?= $booking['booking_id'] ?>)">
                    <i class="fas fa-download"></i> Download Receipt
                </button>
            <?php endif; ?>
            
            <?php if ($booking['status'] === 'completed'): ?>
                <button class="btn btn-warning btn-lg me-2" onclick="leaveReview(<?= $booking['booking_id'] ?>)">
                    <i class="fas fa-star"></i> Leave Review
                </button>
            <?php endif; ?>
            
            <button class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

<?php
    $html = ob_get_clean();
    echo $html;
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>