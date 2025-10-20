<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=car_detailing', 'root', '');
    $result = $pdo->query('DESCRIBE users');
    if ($result) {
        echo "Users table structure:\n";
        while ($row = $result->fetch()) {
            echo $row['Field'] . ' - ' . $row['Type'] . "\n";
        }
    } else {
        echo "Users table does not exist\n";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>