<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=car_detailing', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create promo_codes table
    $sql = "CREATE TABLE IF NOT EXISTS `promo_codes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL UNIQUE,
      `discount_type` enum('percent','fixed') NOT NULL,
      `discount_value` decimal(10,2) NOT NULL,
      `expiry_date` datetime NOT NULL,
      `min_spend` decimal(10,2) DEFAULT 0.00,
      `usage_limit` int(11) DEFAULT NULL,
      `usage_count` int(11) DEFAULT 0,
      `status` enum('active','inactive') DEFAULT 'active',
      `created_by` int(11) DEFAULT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_code` (`code`),
      KEY `idx_status` (`status`),
      KEY `idx_expiry` (`expiry_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✅ Promo codes table created\n";
    
    // Insert sample promo codes
    $insertSql = "INSERT IGNORE INTO `promo_codes` (`code`, `discount_type`, `discount_value`, `expiry_date`, `min_spend`, `usage_limit`, `status`) VALUES
    ('WELCOME20', 'percent', 20.00, '2025-12-31 23:59:59', 500.00, 100, 'active'),
    ('SAVE100', 'fixed', 100.00, '2025-12-31 23:59:59', 1000.00, 50, 'active'),
    ('LOYAL50', 'percent', 50.00, '2025-11-30 23:59:59', 800.00, 20, 'active'),
    ('CLEAN15', 'percent', 15.00, '2025-12-31 23:59:59', 300.00, NULL, 'active'),
    ('FIRST200', 'fixed', 200.00, '2025-12-31 23:59:59', 1500.00, 30, 'active')";
    
    $pdo->exec($insertSql);
    echo "✅ Sample promo codes inserted\n";
    
    // Add columns to bookings table
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `promo_code` varchar(50) DEFAULT NULL");
        echo "✅ Added promo_code column to bookings\n";
    } catch (Exception $e) {
        echo "⚠️ promo_code column already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `promo_discount` decimal(10,2) DEFAULT 0.00");
        echo "✅ Added promo_discount column to bookings\n";
    } catch (Exception $e) {
        echo "⚠️ promo_discount column already exists\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `final_total` decimal(10,2) DEFAULT NULL");
        echo "✅ Added final_total column to bookings\n";
    } catch (Exception $e) {
        echo "⚠️ final_total column already exists\n";
    }
    
    echo "\n🎉 Promo code system setup complete!\n";
    
    // Show sample codes
    echo "\n📋 Sample Promo Codes Created:\n";
    echo "- WELCOME20: 20% off (min ₱500)\n";
    echo "- SAVE100: ₱100 off (min ₱1000)\n";
    echo "- LOYAL50: 50% off (min ₱800)\n";
    echo "- CLEAN15: 15% off (min ₱300)\n";
    echo "- FIRST200: ₱200 off (min ₱1500)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>