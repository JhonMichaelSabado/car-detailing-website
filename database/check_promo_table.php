<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=car_detailing', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current table structure
    $result = $pdo->query('DESCRIBE promo_codes');
    echo "Current promo_codes table structure:\n";
    while ($row = $result->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>