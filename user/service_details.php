<?php
session_start();

// Set dummy user_id for testing
$_SESSION['user_id'] = 1;

// Database connection
require_once '../config/database.php';
require_once '../includes/database_functions.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();
$carDB = new CarDetailingDB($db);

if (!$db) {
    die("Database connection failed. Please check your configuration.");
}

// Get service ID from URL
$service_id = $_GET['id'] ?? 0;

// Get service details
$services = $carDB->getServices();
$service = null;
foreach ($services as $s) {
    if ($s['service_id'] == $service_id) {
        $service = $s;
        break;
    }
}

if (!$service) {
    header('Location: dashboard_CLEAN.php');
    exit;
}

// Get service icon or image
$service_name_clean = strtolower(str_replace([' ', '+', '(', ')'], ['-', '-', '-', ''], $service['service_name']));
$service_name_clean = preg_replace('/-+/', '-', $service_name_clean); // Remove multiple hyphens
$service_name_clean = trim($service_name_clean, '-'); // Remove leading/trailing hyphens

// Check if image exists for this service
$service_image = '';
$image_path = "../assets/images/services/{$service_name_clean}.jpg";
$image_path_png = "../assets/images/services/{$service_name_clean}.png";
$image_path_webp = "../assets/images/services/{$service_name_clean}.webp";

if (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.jpg")) {
    $service_image = $image_path;
} elseif (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.png")) {
    $service_image = $image_path_png;
} elseif (file_exists(__DIR__ . "/../assets/images/services/{$service_name_clean}.webp")) {
    $service_image = $image_path_webp;
}

// Fallback to emoji if no image
$icon_emoji = '🚗';
if (strpos($service['service_name'], 'Interior') !== false) $icon_emoji = '🪟';
elseif (strpos($service['service_name'], 'Exterior') !== false) $icon_emoji = '🚗';
elseif (strpos($service['service_name'], 'Full Detail') !== false || strpos($service['service_name'], 'Platinum') !== false) $icon_emoji = '✨';
elseif (strpos($service['service_name'], 'Engine') !== false) $icon_emoji = '🔧';
elseif (strpos($service['service_name'], 'Headlight') !== false) $icon_emoji = '💡';
elseif (strpos($service['service_name'], 'Glass') !== false) $icon_emoji = '💎';
elseif (strpos($service['service_name'], 'Ceramic') !== false) $icon_emoji = '🛡️';
elseif (strpos($service['service_name'], 'Tire') !== false) $icon_emoji = '🛞';
elseif (strpos($service['service_name'], 'Wax') !== false) $icon_emoji = '🌟';
elseif (strpos($service['service_name'], 'Odor') !== false) $icon_emoji = '🌬️';
elseif (strpos($service['service_name'], 'Pet Hair') !== false) $icon_emoji = '🐕';
elseif (strpos($service['service_name'], 'Upholstery') !== false) $icon_emoji = '🪑';
elseif (strpos($service['service_name'], 'Watermark') !== false) $icon_emoji = '💧';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['service_name']); ?> - Car Detailing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            line-height: 1.47059;
            min-height: 100vh;
            font-weight: 400;
            letter-spacing: 0.011em;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .header {
            padding: 40px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .back-button {
            background: transparent;
            border: none;
            color: #FFD700;
            padding: 0;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 1rem;
            font-weight: 400;
        }

        .back-button:hover {
            color: #ffffff;
        }

        .breadcrumb {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
            font-weight: 300;
        }

        .breadcrumb a {
            color: #FFD700;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #ffffff;
        }

        .product-hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 120px;
            padding: 120px 0;
            align-items: center;
        }

        .product-image {
            text-align: center;
            position: relative;
        }

        .service-icon-large {
            font-size: 16rem;
            opacity: 0.8;
            margin-bottom: 60px;
            transition: transform 0.6s ease;
        }

        .product-info {
            max-width: 500px;
        }

        .service-badge {
            background: transparent;
            color: rgba(255, 255, 255, 0.4);
            padding: 0;
            border: none;
            font-size: 1rem;
            font-weight: 300;
            display: inline-block;
            margin-bottom: 30px;
            letter-spacing: 0.02em;
        }

        .product-title {
            font-size: 4rem;
            font-weight: 200;
            color: #ffffff;
            margin-bottom: 40px;
            letter-spacing: -0.04em;
            line-height: 1.05;
        }

        .product-price {
            font-size: 2rem;
            color: #FFD700;
            margin-bottom: 60px;
            font-weight: 300;
            letter-spacing: -0.01em;
        }

        .vehicle-size-selector {
            margin-bottom: 60px;
        }

        .size-label {
            font-size: 1.2rem;
            color: #ffffff;
            margin-bottom: 30px;
            display: block;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .size-options {
            display: flex;
            gap: 20px;
        }

        .size-option {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            padding: 20px 30px;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .size-option:hover,
        .size-option.active {
            border-color: #FFD700;
            color: #FFD700;
            background: transparent;
        }

        .delivery-info {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0;
            padding: 40px;
            margin-bottom: 60px;
        }

        .delivery-info h4 {
            color: #ffffff;
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .delivery-info p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
            margin-bottom: 10px;
            font-weight: 300;
            line-height: 1.5;
        }

        .cta-button {
            background: #FFD700;
            color: #1a1a1a;
            border: none;
            padding: 20px 60px;
            border-radius: 0;
            font-weight: 400;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 0.01em;
        }

        .cta-button:hover {
            background: #ffffff;
            color: #1a1a1a;
        }

        .save-for-later {
            background: transparent;
            border: none;
            color: #FFD700;
            padding: 0;
            font-size: 1rem;
            cursor: pointer;
            transition: color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .save-for-later:hover {
            color: #ffffff;
        }

        .product-details {
            padding: 120px 0;
        }

        .details-section {
            margin-bottom: 120px;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 200;
            color: #ffffff;
            margin-bottom: 60px;
            letter-spacing: -0.03em;
            text-align: center;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 80px;
        }

        .details-content {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.2rem;
            line-height: 1.6;
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        .details-content p {
            margin-bottom: 30px;
        }

        .details-content strong {
            color: #FFD700;
            font-weight: 400;
        }

        .included-items,
        .compatibility-items {
            list-style: none;
            padding: 0;
        }

        .included-items li,
        .compatibility-items li {
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
            font-weight: 300;
        }

        .included-items li:before {
            content: "";
            margin-right: 15px;
            font-weight: normal;
        }

        .compatibility-items li:before {
            content: "";
            margin-right: 15px;
            font-weight: normal;
        }

        @media (max-width: 768px) {
            .product-hero {
                grid-template-columns: 1fr;
                gap: 40px;
                text-align: center;
            }

            .details-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .product-title {
                font-size: 2rem;
            }

            .service-icon-large {
                font-size: 8rem;
            }

            .size-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-nav">
                <a href="dashboard_CLEAN.php#services" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Services
                </a>
                <div class="breadcrumb">
                    <a href="dashboard_CLEAN.php">Home</a> > <a href="dashboard_CLEAN.php#services">Services</a> > <?php echo htmlspecialchars($service['service_name']); ?>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="product-hero">
            <div class="product-image">
                <?php if ($service_image): ?>
                    <img src="<?php echo htmlspecialchars($service_image); ?>" 
                         alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                         class="service-detail-image" 
                         style="width: 300px; height: 300px; border-radius: 24px; object-fit: cover; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                <?php else: ?>
                    <div class="service-icon-large"><?php echo $icon_emoji; ?></div>
                <?php endif; ?>
            </div>

            <div class="product-info">
                <div class="service-badge">
                    <?php 
                    if ($service['category'] == 'Premium Detailing') echo 'Premium';
                    elseif ($service['category'] == 'Add-On Service') echo 'Add-On';
                    else echo 'Essential';
                    ?>
                </div>
                
                <h1 class="product-title"><?php echo htmlspecialchars($service['service_name']); ?></h1>
                
                <div class="product-price" id="display-price">₱<?php echo number_format($service['price_medium'], 0); ?></div>

                <div class="vehicle-size-selector">
                    <label class="size-label">Vehicle Size</label>
                    <div class="size-options">
                        <button class="size-option" data-size="small" data-price="<?php echo $service['price_small']; ?>">
                            Small
                        </button>
                        <button class="size-option active" data-size="medium" data-price="<?php echo $service['price_medium']; ?>">
                            Medium
                        </button>
                        <button class="size-option" data-size="large" data-price="<?php echo $service['price_large']; ?>">
                            Large
                        </button>
                    </div>
                </div>

                <div class="delivery-info">
                    <h4><i class="fas fa-truck"></i> Service Information</h4>
                    <p><strong>Mobile Service Available</strong></p>
                    <p>We come to your location within 25km radius</p>
                    <p><strong>Duration:</strong> 2-4 hours depending on service</p>
                </div>

                <a href="booking/step1_service_selection.php?service_id=<?php echo $service['service_id']; ?>&size=medium" class="cta-button" id="bookNowBtn">
                    Book Now
                </a>
                
                <a href="#" class="save-for-later" onclick="saveForLater()">
                    <i class="fas fa-bookmark"></i> Save for later
                </a>
            </div>
        </section>

        <section class="product-details">
            <div class="details-section">
                <h2 class="section-title">Overview</h2>
                <div class="details-grid">
                    <div></div>
                    <div class="details-content">
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <p>Our professional car detailing service ensures your vehicle receives the highest quality care. We use premium products and advanced techniques to deliver exceptional results that exceed your expectations.</p>
                        <p><strong>Professional Service:</strong> Our certified technicians have years of experience in automotive detailing and use only the finest products and equipment.</p>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h2 class="section-title">What's Included</h2>
                <div class="details-grid">
                    <div></div>
                    <div class="details-content">
                        <ul class="included-items">
                            <?php 
                            $included_items = explode(',', $service['included_items']);
                            foreach ($included_items as $item): ?>
                                <li><?php echo trim(htmlspecialchars($item)); ?></li>
                            <?php endforeach; ?>
                            <?php if ($service['free_items']): ?>
                                <?php 
                                $free_items = explode(',', $service['free_items']);
                                foreach ($free_items as $item): ?>
                                    <li><?php echo trim(htmlspecialchars($item)); ?> <span style="color: #FFD700;">(Complimentary)</span></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h2 class="section-title">Compatibility</h2>
                <div class="details-grid">
                    <div></div>
                    <div class="details-content">
                        <p>This service is compatible with:</p>
                        <ul class="compatibility-items">
                            <li>All vehicle types (cars, SUVs, trucks, vans)</li>
                            <li>All paint types and finishes</li>
                            <li>Both new and pre-owned vehicles</li>
                            <li>Electric and hybrid vehicles</li>
                            <li>Luxury and exotic vehicles</li>
                            <li>Commercial and fleet vehicles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Vehicle size selection
        document.querySelectorAll('.size-option').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.size-option').forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update price display
                const price = this.dataset.price;
                document.getElementById('display-price').textContent = '₱' + parseInt(price).toLocaleString();
            });
        });

        // Save for later functionality
        function saveForLater() {
            alert('Service saved to your wishlist! You can find it in your dashboard.');
        }

        // Update booking link with selected size
        document.querySelectorAll('.size-option').forEach(button => {
            button.addEventListener('click', function() {
                const serviceId = <?php echo $service['service_id']; ?>;
                const size = this.dataset.size;
                const bookingLink = document.getElementById('bookNowBtn');
                bookingLink.href = `booking/step1_service_selection.php?service_id=${serviceId}&size=${size}`;
            });
        });
    </script>
</body>
</html>
