<?php
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $result = $pdo->query('SHOW DATABASES');
    echo "Available databases:\n";
    foreach($result as $row) {
        echo "- " . $row[0] . "\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>