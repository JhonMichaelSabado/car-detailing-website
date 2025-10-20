-- Create promo_codes table for voucher system
CREATE TABLE IF NOT EXISTS `promo_codes` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample promo codes for testing
INSERT INTO `promo_codes` (`code`, `discount_type`, `discount_value`, `expiry_date`, `min_spend`, `usage_limit`, `status`) VALUES
('WELCOME20', 'percent', 20.00, '2025-12-31 23:59:59', 500.00, 100, 'active'),
('SAVE100', 'fixed', 100.00, '2025-12-31 23:59:59', 1000.00, 50, 'active'),
('LOYAL50', 'percent', 50.00, '2025-11-30 23:59:59', 800.00, 20, 'active'),
('CLEAN15', 'percent', 15.00, '2025-12-31 23:59:59', 300.00, NULL, 'active'),
('FIRST200', 'fixed', 200.00, '2025-12-31 23:59:59', 1500.00, 30, 'active');

-- Add promo code tracking to bookings table if columns don't exist
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `promo_code` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `promo_discount` decimal(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `final_total` decimal(10,2) DEFAULT NULL;