<?php
session_start();
require_once '../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

if (!isset($_GET['booking'])) {
    header("Location: status_dashboard.php");
    exit();
}

$booking_id = (int)$_GET['booking'];
$user_id = $_SESSION['user_id'];

try {
    // Get booking and payment details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            s.service_name,
            s.category,
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
        WHERE b.booking_id = ? AND b.user_id = ? AND p.payment_status = 'paid'
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header("Location: status_dashboard.php");
        exit();
    }
    
    // Get add-on services
    $addons = $booking['addon_services'] ? json_decode($booking['addon_services'], true) : [];
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($booking['booking_reference']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .receipt-container {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .receipt-container {
            background: white;
            max-width: 800px;
            margin: 20px auto;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }
        
        .company-logo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }
        
        .company-name {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h5 {
            color: #667eea;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .service-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .itemized-list {
            margin-bottom: 30px;
        }
        
        .itemized-list table {
            width: 100%;
        }
        
        .itemized-list th {
            background: #667eea;
            color: white;
            padding: 15px;
            border: none;
        }
        
        .itemized-list td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .total-amount {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
        }
        
        .paid-stamp {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transform: rotate(15deg);
        }
        
        @media (max-width: 768px) {
            .receipt-info {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .receipt-container {
                margin: 10px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Action Buttons -->
        <div class="text-center my-3 no-print">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button onclick="downloadPDF()" class="btn btn-success me-2">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <a href="status_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <div class="receipt-container position-relative">
            <!-- Paid Stamp -->
            <div class="paid-stamp">PAID</div>
            
            <!-- Receipt Header -->
            <div class="receipt-header">
                <div class="company-logo">
                    <i class="fas fa-car"></i>
                </div>
                <div class="company-name">CarDetailing Pro</div>
                <div class="receipt-title">OFFICIAL RECEIPT</div>
                <div class="text-muted">Professional Car Detailing Services</div>
            </div>
            
            <!-- Receipt Information -->
            <div class="receipt-info">
                <div class="info-section">
                    <h5><i class="fas fa-receipt"></i> Receipt Details</h5>
                    <p><strong>Receipt #:</strong> RCP-<?= htmlspecialchars($booking['booking_reference']) ?></p>
                    <p><strong>Booking #:</strong> <?= htmlspecialchars($booking['booking_reference']) ?></p>
                    <p><strong>Issue Date:</strong> <?= date('F j, Y') ?></p>
                    <p><strong>Payment Date:</strong> <?= date('F j, Y g:i A', strtotime($booking['payment_date'])) ?></p>
                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($booking['transaction_id']) ?></p>
                </div>
                
                <div class="info-section">
                    <h5><i class="fas fa-user"></i> Customer Information</h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
                    <p><strong>Service Date:</strong> <?= date('F j, Y', strtotime($booking['booking_date'])) ?></p>
                    <p><strong>Service Time:</strong> <?= date('g:i A', strtotime($booking['booking_time'])) ?></p>
                </div>
            </div>
            
            <!-- Service Details -->
            <div class="service-details">
                <h5><i class="fas fa-car-side"></i> Service Information</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Service:</strong> <?= htmlspecialchars($booking['service_name']) ?></p>
                        <p><strong>Category:</strong> <?= ucfirst($booking['category']) ?></p>
                        <p><strong>Vehicle Size:</strong> <?= ucfirst($booking['vehicle_size']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Service Location:</strong></p>
                        <p class="text-muted"><?= htmlspecialchars($booking['service_address']) ?></p>
                        <p><strong>Duration:</strong> <?= $booking['estimated_duration'] ?> minutes</p>
                    </div>
                </div>
            </div>
            
            <!-- Itemized List -->
            <div class="itemized-list">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?= htmlspecialchars($booking['service_name']) ?><br>
                                <small class="text-muted"><?= ucfirst($booking['vehicle_size']) ?> Vehicle</small>
                            </td>
                            <td class="text-center">1</td>
                            <td class="text-end">₱<?= number_format($booking['base_price'], 2) ?></td>
                        </tr>
                        
                        <?php if (!empty($addons)): ?>
                            <?php foreach ($addons as $addon): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($addon['name']) ?><br>
                                        <small class="text-muted">Add-on Service</small>
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-end">₱<?= number_format($addon['price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($booking['travel_fee'] > 0): ?>
                            <tr>
                                <td>
                                    Travel Fee<br>
                                    <small class="text-muted">Distance: <?= $booking['travel_distance'] ?> km</small>
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-end">₱<?= number_format($booking['travel_fee'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if ($booking['promo_discount'] > 0): ?>
                            <tr class="table-success">
                                <td>
                                    Promo Discount<br>
                                    <small class="text-muted">Code: <?= htmlspecialchars($booking['promo_code']) ?></small>
                                </td>
                                <td class="text-center">1</td>
                                <td class="text-end">-₱<?= number_format($booking['promo_discount'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <tr class="table-primary">
                            <th colspan="2">TOTAL AMOUNT</th>
                            <th class="text-end">₱<?= number_format($booking['total_amount'], 2) ?></th>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Payment Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-credit-card"></i> Payment Information</h5>
                    <p><strong>Payment Method:</strong> <?= ucfirst(str_replace('_', ' ', $booking['payment_method'])) ?></p>
                    <p><strong>Payment Mode:</strong> <?= $booking['payment_mode'] === 'deposit_50' ? '50% Deposit Payment' : 'Full Payment' ?></p>
                    <p><strong>Amount Paid:</strong> ₱<?= number_format($booking['payment_amount'], 2) ?></p>
                    
                    <?php if ($booking['payment_mode'] === 'deposit_50'): ?>
                        <p><strong>Remaining Balance:</strong> ₱<?= number_format($booking['total_amount'] - $booking['payment_amount'], 2) ?></p>
                        <p class="text-muted"><em>Balance to be paid after service completion</em></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="total-section">
                        <div class="total-amount">₱<?= number_format($booking['payment_amount'], 2) ?></div>
                        <div>Amount Paid</div>
                        <div class="mt-2">
                            <i class="fas fa-check-circle"></i> Payment Confirmed
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Terms and Conditions -->
            <div class="mb-4">
                <h6><i class="fas fa-file-contract"></i> Terms & Conditions</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="small text-muted">
                            <li>Service will be performed on the scheduled date and time</li>
                            <li>Customer must ensure vehicle accessibility</li>
                            <li>Cancellation must be made 24 hours in advance</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="small text-muted">
                            <li>Additional charges may apply for extra services</li>
                            <li>Payment confirmation required before service</li>
                            <li>Quality guarantee on all services provided</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Footer -->
            <div class="receipt-footer">
                <div class="mb-3">
                    <strong>Thank you for choosing CarDetailing Pro!</strong>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-phone"></i> +63 (2) 123-4567
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-envelope"></i> support@cardetailing.com
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-globe"></i> www.cardetailing.com
                    </div>
                </div>
                <div class="mt-3">
                    <small>This is an official computer-generated receipt. No signature required.</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const element = document.querySelector('.receipt-container');
            
            // Hide the paid stamp temporarily for PDF
            const paidStamp = document.querySelector('.paid-stamp');
            paidStamp.style.display = 'none';
            
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const imgWidth = 210;
                const pageHeight = 295;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                
                let position = 0;
                
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                pdf.save('Receipt-<?= $booking['booking_reference'] ?>.pdf');
                
                // Show the paid stamp again
                paidStamp.style.display = 'block';
            });
        }
    </script>
</body>
</html>