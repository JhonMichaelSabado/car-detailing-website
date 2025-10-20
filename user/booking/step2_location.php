<?php
session_start();
require_once '../../includes/config.php';

// Set test user if not logged in (for development)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Test user
}

// Check if coming from step 1
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log what we received
    error_log("Step 2 received POST data: " . print_r($_POST, true));
    
    if (isset($_POST['service_id'])) {
        $_SESSION['booking_flow']['service_id'] = $_POST['service_id'];
        $_SESSION['booking_flow']['vehicle_size'] = $_POST['vehicle_size'];
        $_SESSION['booking_flow']['addon_services'] = $_POST['addon_services'] ?? '[]';
        
        // Store new booking data if available
        if (isset($_POST['booking_data'])) {
            $_SESSION['booking_flow']['booking_data'] = $_POST['booking_data'];
            $_SESSION['booking_flow']['total_amount'] = $_POST['total_amount'];
        }
        
        $_SESSION['booking_step'] = 2;
    } else {
        error_log("Step 2 error: service_id not found in POST data");
        header("Location: step1_service_selection.php");
        exit();
    }
} elseif (!isset($_SESSION['booking_flow']['service_id'])) {
    error_log("Step 2 error: No booking flow service_id in session");
    header("Location: step1_service_selection.php");
    exit();
}

// Get service details for pricing
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$_SESSION['booking_flow']['service_id']]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get business settings for travel calculation
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM business_settings");
    $settings = [];
    while ($row = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: step1_service_selection.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Car Detailing - Service Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Apple-style progress bar */
        .booking-progress-bar {
            position: absolute;
            top: 32px;
            left: 48px;
            display: flex;
            flex-direction: row;
            gap: 18px;
            max-width: 420px;
            background: none;
            box-shadow: none;
            padding: 0 32px;
            z-index: 10;
            overflow: visible;
        }
        .progress-step {
            width: 38px;
            height: 38px;
            aspect-ratio: 1/1;
            border-radius: 50%;
            background: #fff;
            color: #3f67e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.25rem;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.05);
            border: 2.5px solid #e3eafd;
            transition: background 0.2s, border 0.2s;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .progress-step.active {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            box-shadow: 0 2px 12px 0 rgba(63,103,229,0.13);
        }
        .progress-step.completed {
            background: #3f67e5;
            color: #fff;
            border: 2.5px solid #3f67e5;
            opacity: 0.7;
        }
        .booking-progress-header {
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 2.9rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #222;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .booking-progress-subtitle {
            font-size: 1.1rem;
            color: #888;
            font-weight: 400;
            letter-spacing: 0.01em;
            margin-bottom: 1.2rem;
            text-align: center;
        }
        .booking-progress-container {
            position: relative;
            width: 100%;
            height: 180px;
            margin-bottom: 0;
        }
        /* Button color override */
        .btn-primary, .btn-primary:active, .btn-primary:focus, .btn-primary:hover {
            background: #3f67e5 !important;
            border-color: #3f67e5 !important;
            color: #fff !important;
            box-shadow: 0 2px 8px 0 rgba(63,103,229,0.08);
        }
        .btn-outline-primary, .btn-outline-primary:active, .btn-outline-primary:focus, .btn-outline-primary:hover {
            color: #3f67e5 !important;
            border-color: #3f67e5 !important;
            background: #f5f8ff !important;
        }
        .location-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .location-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #dee2e6;
        }
        .travel-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .fee-breakdown {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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
        .address-suggestions {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            position: absolute;
            width: 100%;
            z-index: 1000;
        }
        .address-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
        }
        .address-suggestion:hover {
            background: #f8f9fa;
        }
        .address-suggestion:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <!-- Apple-style Progress Bar and Header -->
    <div class="booking-progress-container">
        <div class="booking-progress-bar">
            <div class="progress-step completed">1</div>
            <div class="progress-step active">2</div>
            <div class="progress-step">3</div>
            <div class="progress-step">4</div>
            <div class="progress-step">5</div>
        </div>
        <div style="max-width: 1100px; margin: 0 auto; padding-top: 32px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div class="booking-progress-header">Professional Car Detailing Booking</div>
            <span class="booking-progress-subtitle">Step 2 of 9<span style="margin: 0 0.5em;">•</span>Service Location & Travel</span>
        </div>
    <hr style="border: none; border-top: 2.5px solid #e3e3ea; margin: 32px 0 0 0; width: 99%; opacity: 0.7;" />
    <div style="margin-bottom: 48px;"></div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <form id="locationForm" method="POST" action="step3_datetime.php">
                    <!-- Service Address -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Service Address</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="serviceAddress" class="form-label">Full Address</label>
                                    <div class="position-relative">
                                        <input type="text" class="form-control" id="serviceAddress" name="service_address" 
                                               placeholder="Enter your complete address..." required>
                                        <div id="addressSuggestions" class="address-suggestions"></div>
                                    </div>
                                    <div class="form-text">We'll come to your location for the service</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="landmarkInstructions" class="form-label">Landmark / Special Instructions</label>
                                    <textarea class="form-control" id="landmarkInstructions" name="landmark_instructions" 
                                              rows="3" placeholder="e.g., Near McDonald's, Blue gate, Guard post..."></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="travel-info">
                                        <h6 class="mb-3"><i class="fas fa-route me-2"></i>Travel Information</h6>
                                        <div id="travelDetails">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Distance from our location:</span>
                                                <span id="travelDistance" class="text-muted">Calculating...</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Estimated travel time:</span>
                                                <span id="travelTime" class="text-muted">Calculating...</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Within free travel radius:</span>
                                                <span id="freeRadius" class="text-muted">Checking...</span>
                                            </div>
                                        </div>
                                        
                                        <div class="fee-breakdown" id="feeBreakdown" style="display: none;">
                                            <h6 class="mb-2">Travel Fee Breakdown</h6>
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>Base travel fee:</span>
                                                <span id="baseFee">₱<?= number_format($settings['base_travel_fee'] ?? 50, 2) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>Additional distance fee:</span>
                                                <span id="distanceFee">₱0.00</span>
                                            </div>
                                            <hr class="my-2">
                                            <div class="d-flex justify-content-between">
                                                <strong>Total Travel Fee:</strong>
                                                <strong id="totalTravelFee" class="text-primary">₱0.00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Map Display -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-map me-2 text-primary"></i>Location Confirmation</h5>
                            <small class="text-muted">Drag the pin to adjust your exact location</small>
                        </div>
                        <div class="card-body">
                            <div class="map-container" id="mapContainer" style="height: 400px; border-radius: 8px; border: 1px solid #dee2e6;">
                                <div id="loadingMap" class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary mb-3" role="status">
                                            <span class="visually-hidden">Loading map...</span>
                                        </div>
                                        <p>Loading interactive map...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary" id="getCurrentLocation">
                                    <i class="fas fa-crosshairs me-2"></i>Use My Current Location
                                </button>
                                <button type="button" class="btn btn-success" id="confirmLocation" style="display: none;">
                                    <i class="fas fa-check me-2"></i>Confirm This Location
                                </button>
                                <div id="locationStatus" class="d-flex align-items-center ms-2 text-muted small" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="statusText">Ready to select location</span>
                                </div>
                            </div>
                            <div id="locationDetails" class="mt-3 p-3 bg-light rounded" style="display: none;">
                                <h6 class="mb-2"><i class="fas fa-map-pin me-2 text-success"></i>Selected Location</h6>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-2">
                                            <strong>Address:</strong>
                                            <div id="selectedAddress" class="text-muted">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <strong>Coordinates:</strong>
                                            <div class="text-muted small">
                                                <div>Lat: <span id="selectedLat">-</span></div>
                                                <div>Lng: <span id="selectedLng">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs -->
                    <input type="hidden" id="serviceLat" name="service_lat">
                    <input type="hidden" id="serviceLng" name="service_lng">
                    <input type="hidden" id="travelFeeAmount" name="travel_fee" value="0">

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="step1_service_selection.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Service Selection
                        </a>
                        <button type="submit" id="continueBtn" class="btn btn-primary" disabled>
                            Continue to Date & Time <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Booking Summary Sidebar -->
            <div class="col-lg-4">
                <div class="summary-card sticky-top" style="top: 20px;">
                    <div class="summary-header">
                        <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Booking Summary</h6>
                    </div>
                    <div class="summary-body">
                        <div class="mb-3">
                            <h6>Service Details</h6>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Service:</span>
                                <span><?= htmlspecialchars($service['service_name']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Vehicle Size:</span>
                                <span class="text-capitalize"><?= htmlspecialchars($_SESSION['booking_flow']['vehicle_size']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Duration:</span>
                                <span><?= $service['duration_minutes'] ?> minutes</span>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h6>Pricing</h6>
                            <?php
                            $vehicle_size = $_SESSION['booking_flow']['vehicle_size'];
                            $base_price = $service["price_$vehicle_size"];
                            $addons = json_decode($_SESSION['booking_flow']['addon_services'], true) ?: [];
                            ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Base Price:</span>
                                <span>₱<?= number_format($base_price, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Travel Fee:</span>
                                <span id="sidebarTravelFee">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal:</span>
                                <span id="sidebarSubtotal">₱<?= number_format($base_price, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>VAT (12%):</span>
                                <span id="sidebarVat">₱<?= number_format($base_price * 0.12, 2) ?></span>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total Amount:</strong>
                            <strong id="sidebarTotal" class="text-primary">₱<?= number_format($base_price * 1.12, 2) ?></strong>
                        </div>

                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-2">Payment Options</h6>
                            <div class="d-flex justify-content-between small mb-1">
                                <span>50% Deposit:</span>
                                <span id="sidebarDeposit" class="text-success">₱<?= number_format($base_price * 1.12 * 0.5, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Full Payment:</span>
                                <span id="sidebarFull" class="text-info">₱<?= number_format($base_price * 1.12, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet CSS and JS for FREE interactive maps (no API key required) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        let map;
        let marker;
        let businessMarker;
        let currentLocation = null;
        
        const businessSettings = <?= json_encode($settings) ?>;
        const basePrice = <?= $base_price ?>;
        // Enforce a hard service radius of 25 km around the business address
        const SERVICE_RADIUS_KM = 25; // user requirement: only accept bookings within 25km
        const BUSINESS_ADDRESS_STRING = '146-171 Aragon, Bacoor, Cavite, Philippines';

         // Business location (Manila, Philippines - replace with your actual coordinates)
        // Start with a reasonable default center (Manila) in case forward-geocoding is slow/fails
        const businessLocation = { lat: 14.5995, lng: 120.9842 };

         // Initialize Leaflet map (FREE - no API key required!)
         function initMap() {
            // Hide loading spinner
            document.getElementById('loadingMap').style.display = 'none';
            
            // Create map centered on Manila
            map = L.map('mapContainer').setView([businessLocation.lat, businessLocation.lng], 13);
            
            // Add OpenStreetMap tiles (FREE!)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Add business location marker (blue icon)
            businessMarker = L.marker([businessLocation.lat, businessLocation.lng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                })
            }).addTo(map);
            businessMarker.bindPopup('<b>Our Business Location</b><br>Car Detailing Service').openPopup();

            // Try to forward-geocode the official business address to get precise coordinates
            geocodeBusinessAddress();

             setStatus('Map loaded. Click "Use My Current Location" or click on the map to set your location.');
         }

        // Forward-geocode the business address using Nominatim so we have precise coordinates for radius checks
        function geocodeBusinessAddress() {
            // Nominatim search endpoint
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(BUSINESS_ADDRESS_STRING)}&limit=1`;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0 && data[0].lat && data[0].lon) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        businessLocation.lat = lat;
                        businessLocation.lng = lon;

                        // Move business marker to precise location and update map view slightly
                        if (businessMarker) {
                            businessMarker.setLatLng([lat, lon]);
                            businessMarker.bindPopup('<b>Our Business Location</b><br>146-171 Aragon, Bacoor, Cavite').openPopup();
                        }

                        // If user has already selected a location, re-evaluate fees and serviceability
                        if (currentLocation) {
                            calculateTravelFee(currentLocation.lat, currentLocation.lng);
                        }
                    } else {
                        console.warn('Business geocode returned no result - using fallback coordinates');
                    }
                })
                .catch(err => {
                    console.warn('Business geocode failed:', err);
                });
        }

        function updateLocation(lat, lng, address = null) {
            currentLocation = { lat: lat, lng: lng };
            
            // Update hidden form inputs
            document.getElementById('serviceLat').value = lat;
            document.getElementById('serviceLng').value = lng;
            
            // Update address field if provided
            if (address) {
                const addrInput = document.getElementById('serviceAddress');
                addrInput.value = address;
                // Fire input event so any listeners (including checkFormComplete) run
                addrInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            
            // Update map view
            map.setView([lat, lng], 15);
            
            // Remove existing service location marker
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Add new draggable marker at service location (red icon)
            marker = L.marker([lat, lng], {
                draggable: true,
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34]
                })
            }).addTo(map);
            
            marker.bindPopup('<b>Service Location</b><br>Drag me to adjust location').openPopup();
            
            // Handle marker drag events
            marker.on('dragend', function(e) {
                const newPos = e.target.getLatLng();
                updateLocation(newPos.lat, newPos.lng);
                reverseGeocode(newPos.lat, newPos.lng);
                setStatus('Location updated! Drag the red pin to fine-tune your exact location.');
            });
            
            // Show location details
            showLocationDetails(lat, lng, address);
            
            // Calculate travel fee and update pricing
            calculateTravelFee(lat, lng);
            
            // Show confirm button and enable form submission
            document.getElementById('confirmLocation').style.display = 'inline-block';
            setStatus('Location set! You can drag the red pin to adjust, or click "Confirm This Location" to continue.');
            checkFormComplete();
        }
        
        function showLocationDetails(lat, lng, address) {
            document.getElementById('selectedLat').textContent = lat.toFixed(6);
            document.getElementById('selectedLng').textContent = lng.toFixed(6);
            document.getElementById('selectedAddress').textContent = address || 'Address will be detected...';
            document.getElementById('locationDetails').style.display = 'block';
        }
        
        // FREE reverse geocoding using Nominatim (OpenStreetMap)
        function reverseGeocode(lat, lng) {
            setStatus('Getting address details...');
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        const address = data.display_name;
                        document.getElementById('serviceAddress').value = address;
                        document.getElementById('selectedAddress').textContent = address;
                        setStatus('Address found! Location is ready for confirmation.');
                        // After setting the address programmatically, re-check form completeness
                        // Trigger input listeners and re-check completeness
                        const addrInput = document.getElementById('serviceAddress');
                        addrInput.dispatchEvent(new Event('input', { bubbles: true }));
                        if (typeof checkFormComplete === 'function') checkFormComplete();
                    } else {
                        setStatus('Could not determine address. You can still continue with the coordinates.');
                        // Re-check form completeness: coordinates may already be present
                        if (typeof checkFormComplete === 'function') checkFormComplete();
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    setStatus('Could not get address details, but coordinates are saved.');
                    // Re-check form completeness on error as well.
                    if (typeof checkFormComplete === 'function') checkFormComplete();
                });
        }
        
        // Calculate travel fee using Haversine formula (distance calculation)
        function calculateTravelFee(lat, lng) {
            const distanceKm = calculateDistance(businessLocation.lat, businessLocation.lng, lat, lng);
            const estimatedDuration = Math.max(15, Math.round(distanceKm * 2.5)); // Rough estimate: ~2.5 min per km
            
            updateTravelInfo(distanceKm, estimatedDuration + ' minutes (estimated)');
        }
        
        // Haversine formula for distance calculation
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Earth's radius in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }
        
        function updateTravelInfo(distanceKm, duration) {
            const freeRadius = parseFloat(businessSettings.free_travel_radius_km || 5);
            const baseFee = parseFloat(businessSettings.base_travel_fee || 50);
            const feePerKm = parseFloat(businessSettings.travel_fee_per_km || 15);

            document.getElementById('travelDistance').textContent = distanceKm.toFixed(1) + ' km';
            document.getElementById('travelTime').textContent = duration;

            // Enforce service radius: if distance > SERVICE_RADIUS_KM, disallow booking
            const outsideServiceArea = distanceKm > SERVICE_RADIUS_KM;
            const confirmBtn = document.getElementById('confirmLocation');
            const continueBtn = document.getElementById('continueBtn');
            if (outsideServiceArea) {
                document.getElementById('freeRadius').innerHTML = `<span class="text-danger">No — Outside ${SERVICE_RADIUS_KM} km service area</span>`;
                // Hide or disable confirm button and ensure continue is disabled
                if (confirmBtn) { confirmBtn.style.display = 'none'; confirmBtn.disabled = true; }
                if (continueBtn) continueBtn.disabled = true;
                setStatus(`Selected location is ${distanceKm.toFixed(1)} km away — outside our ${SERVICE_RADIUS_KM} km service area. Please choose a closer location.`);
                // No travel fee (not applicable) and don't show fee breakdown
                document.getElementById('feeBreakdown').style.display = 'none';
                document.getElementById('sidebarTravelFee').textContent = 'N/A';
                document.getElementById('sidebarSubtotal').textContent = 'N/A';
                document.getElementById('sidebarVat').textContent = 'N/A';
                document.getElementById('sidebarTotal').textContent = 'N/A';
                return;
            } else {
                // Ensure confirm button is visible again when inside area
                if (confirmBtn) { confirmBtn.style.display = 'inline-block'; confirmBtn.disabled = false; }
            }

            let totalFee = 0;
            let distanceFee = 0;

            if (distanceKm <= freeRadius) {
                document.getElementById('freeRadius').innerHTML = '<span class="text-success">Yes (Free travel)</span>';
                document.getElementById('feeBreakdown').style.display = 'none';
            } else {
                document.getElementById('freeRadius').innerHTML = '<span class="text-warning">No</span>';
                totalFee = baseFee;

                if (distanceKm > freeRadius) {
                    distanceFee = (distanceKm - freeRadius) * feePerKm;
                    totalFee += distanceFee;
                }

                document.getElementById('distanceFee').textContent = '₱' + distanceFee.toFixed(2);
                document.getElementById('totalTravelFee').textContent = '₱' + totalFee.toFixed(2);
                document.getElementById('feeBreakdown').style.display = 'block';
            }

            document.getElementById('travelFeeAmount').value = totalFee;
            updateSidebarPricing(totalFee);
        }
        
        function updateSidebarPricing(travelFee) {
            const subtotal = basePrice + travelFee;
            const vat = subtotal * 0.12;
            const total = subtotal + vat;
            const deposit = total * 0.5;
            
            document.getElementById('sidebarTravelFee').textContent = '₱' + travelFee.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('sidebarSubtotal').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('sidebarVat').textContent = '₱' + vat.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('sidebarTotal').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('sidebarDeposit').textContent = '₱' + deposit.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('sidebarFull').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2});
        }
        
        function getCurrentLocation() {
            setStatus('Requesting location permission...');
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setStatus('Location found! Setting up your location...');
                        updateLocation(lat, lng);
                        reverseGeocode(lat, lng);
                    },
                    function(error) {
                        let errorMessage = '';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location access denied. Please enable location services and refresh the page.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information unavailable. Please enter your address manually.';
                                break;
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out. Please try again or enter address manually.';
                                break;
                            default:
                                errorMessage = 'An unknown error occurred while retrieving location.';
                                break;
                        }
                        setStatus(errorMessage);
                        alert(errorMessage);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 600000 // 10 minutes
                    }
                );
            } else {
                const message = 'Geolocation is not supported by this browser. Please enter your address manually.';
                setStatus(message);
                alert(message);
            }
        }
        
        function setStatus(message) {
            document.getElementById('statusText').textContent = message;
            document.getElementById('locationStatus').style.display = 'flex';
        }
        
        function checkFormComplete() {
            const addressInput = document.getElementById('serviceAddress').value.trim();
            const hasCoordinates = document.getElementById('serviceLat').value && document.getElementById('serviceLng').value;
            
            document.getElementById('continueBtn').disabled = !(addressInput && hasCoordinates);
        }
        
        // Event listeners
        document.getElementById('getCurrentLocation').addEventListener('click', getCurrentLocation);
        document.getElementById('serviceAddress').addEventListener('input', checkFormComplete);
        
        // Handle map clicks to set location
        function setupMapClickHandler() {
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                updateLocation(lat, lng);
                reverseGeocode(lat, lng);
                setStatus('Location set by map click! Drag the red pin to fine-tune your exact location.');
            });
        }
        
        // Confirm location function
        document.getElementById('confirmLocation').addEventListener('click', function() {
            if (currentLocation) {
                setStatus('Location confirmed! Ready to proceed to next step.');
                alert('Location confirmed! You can now continue to the next step.');
                this.style.display = 'none';
                const confirmedBtn = document.createElement('button');
                confirmedBtn.type = 'button';
                confirmedBtn.className = 'btn btn-success';
                confirmedBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Location Confirmed';
                confirmedBtn.disabled = true;
                this.parentNode.appendChild(confirmedBtn);

                // Ensure hidden inputs are populated from currentLocation (in case updateLocation was not called)
                if (currentLocation.lat && currentLocation.lng) {
                    document.getElementById('serviceLat').value = currentLocation.lat;
                    document.getElementById('serviceLng').value = currentLocation.lng;
                }

                // If reverse geocoding hasn't populated a readable address yet, set a fallback address
                const addrInput = document.getElementById('serviceAddress');
                if (addrInput && !addrInput.value.trim()) {
                    addrInput.value = `Lat: ${currentLocation.lat.toFixed(6)}, Lng: ${currentLocation.lng.toFixed(6)}`;
                    document.getElementById('selectedAddress').textContent = addrInput.value;
                    // Dispatch input event so listeners update the Continue button
                    addrInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                // Enable the Continue button and re-run the completeness check as a final guard
                const continueBtn = document.getElementById('continueBtn');
                if (continueBtn) continueBtn.disabled = false;
                if (typeof checkFormComplete === 'function') checkFormComplete();
            }
        });
        
        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            setTimeout(setupMapClickHandler, 1000); // Setup click handler after map loads
         });
    </script>
</body>
</html>
