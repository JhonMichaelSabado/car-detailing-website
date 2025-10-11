<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Users table structure:\n";
$stmt = $db->prepare('DESCRIBE users');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo $column['Field'] . ' - ' . $column['Type'] . "\n";
}
?>