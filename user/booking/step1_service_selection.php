<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

$user_id = $_SESSION['user_id'];

// Check if service was pre-selected from service details page
$preselected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : null;
$preselected_size = isset($_GET['size']) ? $_GET['size'] : 'medium'; // Default to medium if not specified

// Get services from database
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $addon_stmt = $pdo->query("SELECT * FROM addon_services WHERE is_active = 1 ORDER BY sort_order");
    $addons = $addon_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get preselected service details if available
    $preselected_service = null;
    if ($preselected_service_id) {
        $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
        $stmt->execute([$preselected_service_id]);
        $preselected_service = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $services = [];
    $addons = [];
    $preselected_service = null;
}

// Initialize session for booking flow
if (!isset($_SESSION['booking_flow'])) {
    $_SESSION['booking_flow'] = [];
    $_SESSION['booking_step'] = 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Car Detailing - Service Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-progress {
            background: #fff;
            color: #222;
            padding: 20px 0;
        }
        .progress-bar {
            background: #f6f6f7;
            border-radius: 16px;
            padding: 18px 0 10px 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.03);
        }
        .progress-step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ececec;
            color: #bfbfbf;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 10px;
            border: 2px solid #ececec;
            transition: background 0.25s, color 0.25s, border-color 0.25s;
            box-shadow: 0 1px 4px 0 rgba(0,0,0,0.04);
        }
        .progress-step.active {
            background: #3f67e5;
            color: #fff;
            border-color: #3f67e5;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.10);
        }
        .progress-step.completed {
            background: #3f67e5;
            color: #fff;
            border-color: #3f67e5;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.10);
        }
        .service-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .service-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            transform: translateY(-2px);
        }
        .service-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .vehicle-size-selector {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
        .size-option {
            flex: 1;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .size-option:hover, .size-option.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .addon-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .addon-item.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        .pricing-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            position: sticky;
            top: 20px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <!-- Apple-style Booking Header -->
                                <div style="position: relative; width: 100%; height: 0;">
                                            <div class="progress-bar" style="position: absolute; top: 32px; left: 32px; display: flex; flex-direction: row; gap: 18px; max-width: 420px; background: none; box-shadow: none; padding: 0 32px; z-index: 10; overflow: visible;">
                        <div class="progress-step active" style="width: 38px; height: 38px; aspect-ratio: 1/1; border-radius: 50%; background: #3f67e5; color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem; box-shadow: 0 2px 12px 0 rgba(63,103,229,0.13); border: 2.5px solid #3f67e5; transition: background 0.2s, border 0.2s; vertical-align: middle; box-sizing: border-box;">1</div>
                        <div class="progress-step" style="width: 38px; height: 38px; aspect-ratio: 1/1; border-radius: 50%; background: #fff; color: #3f67e5; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem; box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05); border: 2.5px solid #e3eafd; transition: background 0.2s, border 0.2s; vertical-align: middle; box-sizing: border-box;">2</div>
                        <div class="progress-step" style="width: 38px; height: 38px; aspect-ratio: 1/1; border-radius: 50%; background: #fff; color: #3f67e5; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem; box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05); border: 2.5px solid #e3eafd; transition: background 0.2s, border 0.2s; vertical-align: middle; box-sizing: border-box;">3</div>
                        <div class="progress-step" style="width: 38px; height: 38px; aspect-ratio: 1/1; border-radius: 50%; background: #fff; color: #3f67e5; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem; box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05); border: 2.5px solid #e3eafd; transition: background 0.2s, border 0.2s; vertical-align: middle; box-sizing: border-box;">4</div>
                        <div class="progress-step" style="width: 38px; height: 38px; aspect-ratio: 1/1; border-radius: 50%; background: #fff; color: #3f67e5; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 1.25rem; box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05); border: 2.5px solid #e3eafd; transition: background 0.2s, border 0.2s; vertical-align: middle; box-sizing: border-box;">5</div>
                                            </div>
                                </div>
                                <div class="booking-progress" style="border-bottom: 1px solid #ececec; box-shadow: 0 2px 8px 0 rgba(0,0,0,0.03);">
                                    <div style="max-width: 1100px; margin: 0 auto; padding: 32px 0 0 0; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <div style="font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 2.9rem; font-weight: 700; letter-spacing: -0.5px; color: #222; margin-bottom: 0.5rem; text-align: center;">Professional Car Detailing</div>
                                    <span style="font-size: 1.1rem; color: #888; font-weight: 400; letter-spacing: 0.01em; margin-bottom: 1.2rem; text-align: center;">Step 1 of 9<span style="margin: 0 0.5em;">•</span>Select Your Service</span>
                                    <hr style="border: none; border-top: 2px solid #e3e3ea; margin: 18px 0 0 0; width: 99%; opacity: 0.5;" />
                                    </div>
                                </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <form id="serviceSelectionForm" method="POST" action="step2_location.php">
                    <!-- Service Selection -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list-check me-2 text-primary"></i>Your Selected Services</h5>
                            <?php if ($preselected_service): ?>
                            <small class="text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Pre-selected from service page
                            </small>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- Vehicle Container -->
                            <div id="vehicleContainer">
                                <!-- Vehicle 1 (Primary) -->
                                <div class="vehicle-booking-section" id="vehicle-1" data-vehicle-number="1">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="fas fa-car me-2 text-primary"></i>Vehicle #1
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addAnotherVehicle()" style="display: none;" id="addVehicleBtn">
                                            <i class="fas fa-plus me-1"></i>Add Another Vehicle
                                        </button>
                                    </div>

                                    <!-- Services for this vehicle -->
                                    <div class="services-for-vehicle" id="services-vehicle-1">

                                        <?php if ($preselected_service): ?>
                                        <!-- Pre-selected Service -->
                                        <div class="selected-service-item mb-3" data-service-id="<?= $preselected_service['service_id'] ?>" data-vehicle="1">
                                            <div class="card border-success">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 text-success">
                                                                <i class="fas fa-check-circle me-2"></i>
                                                                <?= htmlspecialchars($preselected_service['service_name']) ?>
                                                            </h6>
                                                            <p class="text-muted small mb-2"><?= htmlspecialchars($preselected_service['description'] ?? 'Professional car detailing service') ?></p>
                                                            <span class="badge bg-primary"><?= htmlspecialchars($preselected_service['category']) ?></span>
                                                        </div>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeService(this)" style="margin-left: 10px;">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>

                                                    <!-- Vehicle Size Selection for Pre-selected Service -->
                                                    <div class="vehicle-size-selection mt-3">
                                                        <label class="form-label small">Vehicle Size:</label>
                                                        <div class="btn-group d-flex" role="group">
                                                            <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_1_service_<?= $preselected_service['service_id'] ?>" id="small_v1_s<?= $preselected_service['service_id'] ?>" value="small" data-price="<?= $preselected_service['price_small'] ?>" <?= $preselected_size === 'small' ? 'checked' : '' ?>>
                                                            <label class="btn btn-outline-primary flex-fill" for="small_v1_s<?= $preselected_service['service_id'] ?>">
                                                                Small<br><small>₱<?= number_format($preselected_service['price_small'], 0) ?></small>
                                                            </label>

                                                            <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_1_service_<?= $preselected_service['service_id'] ?>" id="medium_v1_s<?= $preselected_service['service_id'] ?>" value="medium" data-price="<?= $preselected_service['price_medium'] ?>" <?= $preselected_size === 'medium' ? 'checked' : '' ?>>
                                                            <label class="btn btn-outline-primary flex-fill" for="medium_v1_s<?= $preselected_service['service_id'] ?>">
                                                                Medium<br><small>₱<?= number_format($preselected_service['price_medium'], 0) ?></small>
                                                            </label>

                                                            <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_1_service_<?= $preselected_service['service_id'] ?>" id="large_v1_s<?= $preselected_service['service_id'] ?>" value="large" data-price="<?= $preselected_service['price_large'] ?>" <?= $preselected_size === 'large' ? 'checked' : '' ?>>
                                                            <label class="btn btn-outline-primary flex-fill" for="large_v1_s<?= $preselected_service['service_id'] ?>">
                                                                Large<br><small>₱<?= number_format($preselected_service['price_large'], 0) ?></small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                    </div>

                                    <!-- Add Another Service Button -->
                                    <div class="text-center mb-4">
                                        <button type="button" class="btn btn-outline-primary" onclick="showServiceSelector(1)">
                                            <i class="fas fa-plus me-2"></i>Add Another Service
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Another Vehicle Button -->
                            <div class="text-center">
                                <button type="button" class="btn btn-outline-secondary" onclick="addAnotherVehicle()">
                                    <i class="fas fa-car-side me-2"></i>Add Another Vehicle
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Service Selector Modal (for adding more services) -->
                    <div class="modal fade" id="serviceSelectorModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Choose Additional Service</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row" id="serviceModalGrid">
                                        <?php foreach ($services as $service): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="service-card-modal" data-service-id="<?= $service['service_id'] ?>"
                                                 data-category="<?= htmlspecialchars($service['category']) ?>"
                                                 onclick="selectServiceForVehicle(<?= $service['service_id'] ?>, currentVehicleForService)">
                                                <div class="card h-100 border-primary-subtle hover-shadow">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="service-name"><?= htmlspecialchars($service['service_name']) ?></h6>
                                                            <span class="badge bg-primary"><?= htmlspecialchars($service['category']) ?></span>
                                                        </div>
                                                        <p class="text-muted small mb-3"><?= htmlspecialchars($service['description'] ?? 'Professional car detailing service') ?></p>
                                                        <div class="pricing-info">
                                                            <div class="row text-center">
                                                                <div class="col-4">
                                                                    <small class="text-muted">Small</small><br>
                                                                    <strong class="text-primary">₱<?= number_format($service['price_small'], 0) ?></strong>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted">Medium</small><br>
                                                                    <strong class="text-primary">₱<?= number_format($service['price_medium'], 0) ?></strong>
                                                                </div>
                                                                <div class="col-4">
                                                                    <small class="text-muted">Large</small><br>
                                                                    <strong class="text-primary">₱<?= number_format($service['price_large'], 0) ?></strong>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add-On Services -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-circle me-2 text-primary"></i>
                                Add-On Services
                                <span class="badge bg-secondary ms-2">Optional</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($addons as $addon): ?>
                            <div class="addon-item" data-addon-id="<?= $addon['addon_id'] ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <input type="checkbox" class="form-check-input addon-checkbox"
                                               id="addon_<?= $addon['addon_id'] ?>" value="<?= $addon['addon_id'] ?>">
                                    </div>
                                    <div class="col-md-7">
                                        <label for="addon_<?= $addon['addon_id'] ?>" class="form-check-label">
                                            <h6 class="mb-1"><?= htmlspecialchars($addon['service_name']) ?></h6>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($addon['description']) ?></p>
                                        </label>
                                    </div>
                                    <div class="col-md-2">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= $addon['duration_minutes'] ?> min
                                        </small>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="addon-price text-primary fw-bold">
                                            ₱<span class="price-value"><?= number_format($addon['price_medium'], 2) ?></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Hidden inputs for form submission -->
                    <input type="hidden" id="bookingData" name="booking_data" required>
                    <input type="hidden" id="totalAmount" name="total_amount" required>

                    <!-- Legacy compatibility inputs for Step 2 -->
                    <input type="hidden" id="legacyServiceId" name="service_id" required>
                    <input type="hidden" id="legacyVehicleSize" name="vehicle_size" required>
                    <input type="hidden" id="legacyAddons" name="addon_services" value="[]">

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="../dashboard_CLEAN.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                            <button type="submit" id="continueBtn" class="btn btn-primary" style="background: #3f67e5; border-color: #3f67e5; color: #fff;">
                            Continue to Location <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pricing Summary Sidebar -->
                            <div class="col-lg-4 col-md-5 mb-4">
                                <div class="card" style="border-radius: 16px; box-shadow: 0 2px 16px 0 rgba(63,103,229,0.07); border: none;">
                                    <div class="card-body" style="padding: 2rem 1.5rem 1.5rem 1.5rem;">
                                        <h5 class="card-title mb-3" style="font-weight: 700;"><i class="fas fa-receipt me-2"></i>Booking Summary</h5>
                                        
                                        <!-- No Selection State -->
                                        <div id="noSelection" style="text-align: center; padding: 2rem 0; color: #888;">
                                            <i class="fas fa-clipboard-list" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                            <p>No services selected yet</p>
                                            <small>Select a service and vehicle size to see pricing</small>
                                        </div>
                                        
                                        <!-- Pricing Details (hidden by default) -->
                                        <div id="pricingSummary" style="display: none;">
                                            <div class="row mb-2">
                                                <div class="col-7" style="color: #444;">Service:</div>
                                                <div class="col-5 text-end" style="color: #222; font-weight: 500;" id="selectedServiceName">-</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-7" style="color: #444;">Vehicle Size:</div>
                                                <div class="col-5 text-end" style="color: #222; font-weight: 500;" id="selectedSizeName">-</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-7" style="color: #444;">Base Price:</div>
                                                <div class="col-5 text-end" style="color: #222; font-weight: 500;" id="basePrice">₱0.00</div>
                                            </div>
                                            <hr style="margin: 0.7rem 0 0.7rem 0; opacity: 0.3;" />
                                            <div class="row mb-2">
                                                <div class="col-7" style="color: #444;">Subtotal:</div>
                                                <div class="col-5 text-end" style="color: #222; font-weight: 500;" id="subtotal">₱0.00</div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-7" style="color: #444;">VAT (12%):</div>
                                                <div class="col-5 text-end" style="color: #222; font-weight: 500;" id="vatAmount">₱0.00</div>
                                            </div>
                                            <hr style="margin: 0.7rem 0 0.7rem 0; opacity: 0.3;" />
                                            <div class="row mb-2">
                                                <div class="col-7" style="font-weight: 700; color: #222; font-size: 1.1rem;">Total Amount:</div>
                                                <div class="col-5 text-end" style="font-weight: 700; color: #3f67e5; font-size: 1.1rem;" id="totalAmount">₱0.00</div>
                                            </div>
                                            <div class="mt-4 mb-2" style="font-weight: 700; color: #222;">Payment Options:</div>
                                            <div class="row mb-1">
                                                <div class="col-7" style="color: #444;">50% Deposit:</div>
                                                <div class="col-5 text-end" style="color: #3f67e5; font-weight: 600;" id="depositAmount">₱0.00</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-7" style="color: #444;">Full Payment:</div>
                                                <div class="col-5 text-end" style="color: #3f67e5; font-weight: 600;" id="fullPayment">₱0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const serviceData = <?= json_encode($services) ?>;
        const addonData = <?= json_encode($addons) ?>;
        const preselectedServiceId = <?= json_encode($preselected_service_id) ?>;
        const preselectedSize = <?= json_encode($preselected_size) ?>;
        
        // Global variables for booking management
        let vehicleCount = 1;
        let currentVehicleForService = 1;
        let bookingData = {
            vehicles: {
                1: {
                    services: <?= $preselected_service ? '[{serviceId: ' . $preselected_service['service_id'] . ', size: "' . $preselected_size . '", addons: []}]' : '[]' ?>
                }
            }
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($preselected_service): ?>
            // Auto-select the preselected size
            const preselectedRadio = document.querySelector('input[name="size_vehicle_1_service_<?= $preselected_service['service_id'] ?>"][value="<?= $preselected_size ?>"]');
            if (preselectedRadio) {
                preselectedRadio.checked = true;
                bookingData.vehicles[1].services[0].size = '<?= $preselected_size ?>';
                
                // Immediately set legacy inputs for preselected service
                document.getElementById('legacyServiceId').value = '<?= $preselected_service['service_id'] ?>';
                document.getElementById('legacyVehicleSize').value = '<?= $preselected_size ?>';
                
                updatePricingSummary();
                checkFormComplete();
            }
            <?php endif; ?>

            // Add event listeners to vehicle size radios
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('vehicle-size-radio')) {
                    const vehicleNum = e.target.name.match(/vehicle_(\d+)/)[1];
                    const serviceId = e.target.name.match(/service_(\d+)/)[1];
                    const size = e.target.value;
                    
                    // Update booking data
                    const vehicle = bookingData.vehicles[vehicleNum];
                    const service = vehicle.services.find(s => s.serviceId == serviceId);
                    if (service) {
                        service.size = size;
                        updatePricingSummary();
                        checkFormComplete();
                    }
                }
                
                // Handle addon checkbox changes
                if (e.target.classList.contains('addon-checkbox')) {
                    updatePricingSummary();
                    checkFormComplete();
                }
            });
            
            // Add form submission handler
            document.getElementById('serviceSelectionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Ensure all required data is set
                checkFormComplete();
                
                // Check if we have at least one complete service
                let hasCompleteService = false;
                Object.keys(bookingData.vehicles).forEach(vehicleNum => {
                    const vehicle = bookingData.vehicles[vehicleNum];
                    if (vehicle.services.some(service => service.size !== null)) {
                        hasCompleteService = true;
                    }
                });
                
                if (!hasCompleteService) {
                    alert('Please select at least one service and vehicle size before continuing.');
                    return;
                }
                
                // Submit the form
                console.log('Submitting form with data:', {
                    bookingData: bookingData,
                    serviceId: document.getElementById('legacyServiceId').value,
                    vehicleSize: document.getElementById('legacyVehicleSize').value
                });
                this.submit();
            });
        });

        // Show service selector modal
        function showServiceSelector(vehicleNumber) {
            currentVehicleForService = vehicleNumber;
            const modal = new bootstrap.Modal(document.getElementById('serviceSelectorModal'));
            modal.show();
        }

        // Select service for vehicle
        function selectServiceForVehicle(serviceId, vehicleNumber) {
            const service = serviceData.find(s => s.service_id == serviceId);
            if (!service) return;

            // Check if service already selected for this vehicle
            const vehicle = bookingData.vehicles[vehicleNumber];
            if (vehicle.services.some(s => s.serviceId == serviceId)) {
                alert('This service is already selected for this vehicle.');
                return;
            }

            // Add service to vehicle
            vehicle.services.push({
                serviceId: serviceId,
                size: null,
                addons: []
            });

            // Create service UI
            addServiceToVehicleUI(serviceId, vehicleNumber, service);
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('serviceSelectorModal')).hide();
        }

        // Add service to vehicle UI
        function addServiceToVehicleUI(serviceId, vehicleNumber, service) {
            const servicesContainer = document.getElementById(`services-vehicle-${vehicleNumber}`);
            const serviceDiv = document.createElement('div');
            serviceDiv.className = 'selected-service-item mb-3';
            serviceDiv.setAttribute('data-service-id', serviceId);
            serviceDiv.setAttribute('data-vehicle', vehicleNumber);
            
            serviceDiv.innerHTML = `
                <div class="card border-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-primary">
                                    <i class="fas fa-star me-2"></i>
                                    ${service.service_name}
                                </h6>
                                <p class="text-muted small mb-2">${service.description || 'Professional car detailing service'}</p>
                                <span class="badge bg-primary">${service.category}</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeService(this)" style="margin-left: 10px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Vehicle Size Selection -->
                        <div class="vehicle-size-selection mt-3">
                            <label class="form-label small">Vehicle Size:</label>
                            <div class="btn-group d-flex" role="group">
                                <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_${vehicleNumber}_service_${serviceId}" id="small_v${vehicleNumber}_s${serviceId}" value="small" data-price="${service.price_small}">
                                <label class="btn btn-outline-primary flex-fill" for="small_v${vehicleNumber}_s${serviceId}">
                                    Small<br><small>₱${Number(service.price_small).toLocaleString()}</small>
                                </label>
                                
                                <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_${vehicleNumber}_service_${serviceId}" id="medium_v${vehicleNumber}_s${serviceId}" value="medium" data-price="${service.price_medium}">
                                <label class="btn btn-outline-primary flex-fill" for="medium_v${vehicleNumber}_s${serviceId}">
                                    Medium<br><small>₱${Number(service.price_medium).toLocaleString()}</small>
                                </label>
                                
                                <input type="radio" class="btn-check vehicle-size-radio" name="size_vehicle_${vehicleNumber}_service_${serviceId}" id="large_v${vehicleNumber}_s${serviceId}" value="large" data-price="${service.price_large}">
                                <label class="btn btn-outline-primary flex-fill" for="large_v${vehicleNumber}_s${serviceId}">
                                    Large<br><small>₱${Number(service.price_large).toLocaleString()}</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            servicesContainer.appendChild(serviceDiv);
        }

        // Remove service
        function removeService(button) {
            const serviceItem = button.closest('.selected-service-item');
            const serviceId = serviceItem.getAttribute('data-service-id');
            const vehicleNumber = serviceItem.getAttribute('data-vehicle');
            
            // Remove from booking data
            const vehicle = bookingData.vehicles[vehicleNumber];
            vehicle.services = vehicle.services.filter(s => s.serviceId != serviceId);
            
            // Remove from UI
            serviceItem.remove();
            
            updatePricingSummary();
            checkFormComplete();
        }

        // Add another vehicle
        function addAnotherVehicle() {
            vehicleCount++;
            bookingData.vehicles[vehicleCount] = { services: [] };
            
            const vehicleContainer = document.getElementById('vehicleContainer');
            const newVehicleDiv = document.createElement('div');
            newVehicleDiv.className = 'vehicle-booking-section border-top pt-4 mt-4';
            newVehicleDiv.id = `vehicle-${vehicleCount}`;
            newVehicleDiv.setAttribute('data-vehicle-number', vehicleCount);
            
            newVehicleDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="fas fa-car me-2 text-primary"></i>Vehicle #${vehicleCount}
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeVehicle(${vehicleCount})">
                        <i class="fas fa-trash me-1"></i>Remove Vehicle
                    </button>
                </div>
                
                <!-- Services for this vehicle -->
                <div class="services-for-vehicle" id="services-vehicle-${vehicleCount}">
                </div>
                
                <!-- Add Another Service Button -->
                <div class="text-center mb-4">
                    <button type="button" class="btn btn-outline-primary" onclick="showServiceSelector(${vehicleCount})">
                        <i class="fas fa-plus me-2"></i>Add Service
                    </button>
                </div>
            `;
            
            vehicleContainer.appendChild(newVehicleDiv);
        }

        // Remove vehicle
        function removeVehicle(vehicleNumber) {
            if (vehicleCount <= 1) {
                alert('You must have at least one vehicle.');
                return;
            }
            
            delete bookingData.vehicles[vehicleNumber];
            document.getElementById(`vehicle-${vehicleNumber}`).remove();
            vehicleCount--;
            
            updatePricingSummary();
            checkFormComplete();
        }

        // Update pricing summary
        function updatePricingSummary() {
            let totalAmount = 0;
            let servicesList = [];
            
            // Calculate total from all vehicles and services
            Object.keys(bookingData.vehicles).forEach(vehicleNum => {
                const vehicle = bookingData.vehicles[vehicleNum];
                vehicle.services.forEach(service => {
                    if (service.size) {
                        const serviceInfo = serviceData.find(s => s.service_id == service.serviceId);
                        if (serviceInfo) {
                            const price = parseFloat(serviceInfo[`price_${service.size}`]);
                            totalAmount += price;
                            servicesList.push(`${serviceInfo.service_name} (${service.size.charAt(0).toUpperCase() + service.size.slice(1)})`);
                        }
                    }
                });
            });
            
            // Add selected addons to total
            const selectedAddons = document.querySelectorAll('.addon-checkbox:checked');
            let addonCount = 0;
            selectedAddons.forEach(checkbox => {
                const addonId = checkbox.value;
                const addon = addonData.find(a => a.addon_id == addonId);
                if (addon) {
                    const addonPrice = parseFloat(addon.price_medium);
                    totalAmount += addonPrice;
                    addonCount++;
                }
            });

            // Update UI
            const subtotal = totalAmount;
            const vat = subtotal * 0.12;
            const total = subtotal + vat;
            
            document.getElementById('basePrice').textContent = `₱${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('subtotal').textContent = `₱${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('vatAmount').textContent = `₱${vat.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('totalAmount').textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('depositAmount').textContent = `₱${(total * 0.5).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            document.getElementById('fullPayment').textContent = `₱${total.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
            
            // Show/hide pricing summary
            const pricingSummary = document.getElementById('pricingSummary');
            const noSelection = document.getElementById('noSelection');
            
            if (servicesList.length > 0 || addonCount > 0) {
                pricingSummary.style.display = 'block';
                noSelection.style.display = 'none';
                
                // Update service names display
                let displayText = servicesList.join(', ');
                if (addonCount > 0) {
                    displayText += servicesList.length > 0 ? ` + ${addonCount} addon${addonCount > 1 ? 's' : ''}` : `${addonCount} addon${addonCount > 1 ? 's' : ''}`;
                }
                document.getElementById('selectedServiceName').textContent = displayText || '-';
                document.getElementById('selectedSizeName').textContent = `${servicesList.length} service${servicesList.length > 1 ? 's' : ''}${addonCount > 0 ? ` + ${addonCount} addon${addonCount > 1 ? 's' : ''}` : ''}`;
            } else {
                pricingSummary.style.display = 'none';
                noSelection.style.display = 'block';
            }
        }

        // Check if form is complete
        function checkFormComplete() {
            let hasCompleteService = false;
            
            Object.keys(bookingData.vehicles).forEach(vehicleNum => {
                const vehicle = bookingData.vehicles[vehicleNum];
                if (vehicle.services.some(service => service.size !== null)) {
                    hasCompleteService = true;
                }
            });
            
            const continueBtn = document.getElementById('continueBtn');
            continueBtn.disabled = !hasCompleteService;
            
            // Update hidden inputs
            if (hasCompleteService) {
                // For backward compatibility with Step 2, send the first complete service
                let firstCompleteService = null;
                let firstVehicleSize = null;
                
                outer: for (const vehicleNum of Object.keys(bookingData.vehicles)) {
                    const vehicle = bookingData.vehicles[vehicleNum];
                    for (const service of vehicle.services) {
                        if (service.size !== null) {
                            firstCompleteService = service.serviceId;
                            firstVehicleSize = service.size;
                            break outer;
                        }
                    }
                }
                
                // Calculate total amount including addons
                let totalAmount = 0;
                Object.keys(bookingData.vehicles).forEach(vehicleNum => {
                    const vehicle = bookingData.vehicles[vehicleNum];
                    vehicle.services.forEach(service => {
                        if (service.size) {
                            const serviceInfo = serviceData.find(s => s.service_id == service.serviceId);
                            if (serviceInfo) {
                                const price = parseFloat(serviceInfo[`price_${service.size}`]);
                                totalAmount += price;
                            }
                        }
                    });
                });
                
                // Collect selected addon IDs and add their prices
                const selectedAddons = [];
                document.querySelectorAll('.addon-checkbox:checked').forEach(checkbox => {
                    const addonId = parseInt(checkbox.value);
                    selectedAddons.push(addonId);
                    
                    const addon = addonData.find(a => a.addon_id == addonId);
                    if (addon) {
                        const addonPrice = parseFloat(addon.price_medium);
                        totalAmount += addonPrice;
                    }
                });
                
                // Set hidden form inputs for Step 2 compatibility
                document.getElementById('bookingData').value = JSON.stringify(bookingData);
                document.getElementById('totalAmount').value = totalAmount + (totalAmount * 0.12); // Include VAT
                document.getElementById('legacyServiceId').value = firstCompleteService;
                document.getElementById('legacyVehicleSize').value = firstVehicleSize;
                document.getElementById('legacyAddons').value = JSON.stringify(selectedAddons);
                
                console.log('Form data ready:', {
                    serviceId: firstCompleteService,
                    vehicleSize: firstVehicleSize,
                    totalAmount: totalAmount + (totalAmount * 0.12),
                    addons: selectedAddons,
                    bookingData: bookingData
                });
            }
        }

        // Add CSS for hover effects
        const style = document.createElement('style');
        style.textContent = `
            .service-card-modal:hover {
                transform: translateY(-2px);
                cursor: pointer;
            }
            .hover-shadow {
                transition: all 0.3s ease;
            }
            .hover-shadow:hover {
                box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
