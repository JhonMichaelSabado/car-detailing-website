<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=car_detailing', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$sql = file_get_contents('create_promo_codes.sql');
$statements = explode(';', $sql);

try {
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
            echo "✅ Success\n\n";
        }
    }
    echo "🎉 Promo codes table created successfully with sample data!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>