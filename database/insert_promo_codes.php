<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=car_detailing', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert sample promo codes with correct column names
    $insertSql = "INSERT IGNORE INTO `promo_codes` (`code`, `description`, `discount_type`, `discount_value`, `min_amount`, `max_discount`, `usage_limit`, `used_count`, `valid_from`, `valid_until`, `is_active`) VALUES
    ('WELCOME20', '20% off for new customers', 'percentage', 20.00, 500.00, 999999.00, 100, 0, NOW(), '2025-12-31 23:59:59', 1),
    ('SAVE100', '₱100 off your booking', 'fixed', 100.00, 1000.00, 100.00, 50, 0, NOW(), '2025-12-31 23:59:59', 1),
    ('LOYAL50', '50% off for loyal customers', 'percentage', 50.00, 800.00, 999999.00, 20, 0, NOW(), '2025-11-30 23:59:59', 1),
    ('CLEAN15', '15% off any service', 'percentage', 15.00, 300.00, 999999.00, NULL, 0, NOW(), '2025-12-31 23:59:59', 1),
    ('FIRST200', '₱200 off first booking', 'fixed', 200.00, 1500.00, 200.00, 30, 0, NOW(), '2025-12-31 23:59:59', 1)";
    
    $pdo->exec($insertSql);
    echo "✅ Sample promo codes inserted successfully!\n";
    
    // Add columns to bookings table if needed
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `promo_code` varchar(50) DEFAULT NULL");
        echo "✅ Added promo_code column to bookings\n";
    } catch (Exception $e) {
        echo "⚠️ promo_code column already exists or not needed\n";
    }
    
    echo "\n🎉 Promo code system ready!\n";
    
    // Show available codes
    echo "\n📋 Available Promo Codes:\n";
    $stmt = $pdo->query("SELECT code, description, discount_type, discount_value, min_amount FROM promo_codes WHERE is_active = 1");
    while ($row = $stmt->fetch()) {
        $discount = $row['discount_type'] === 'percentage' ? $row['discount_value'] . '% off' : '₱' . $row['discount_value'] . ' off';
        echo "- {$row['code']}: $discount (min ₱{$row['min_amount']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>