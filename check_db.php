<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query('DESCRIBE bookings');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Bookings table structure:\n";
    foreach ($columns as $column) {
        echo $column['Field'] . " - " . $column['Type'] . " - " . $column['Null'] . " - " . $column['Key'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>