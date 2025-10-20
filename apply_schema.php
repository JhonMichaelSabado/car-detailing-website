<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'car_detailing';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents(__DIR__ . '/database/simple_enhanced_schema.sql');
    $pdo->exec($sql);
    
    echo "Enhanced booking database schema applied successfully!\n";
    echo "Professional booking flow database is ready.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>