<?php
// Database Setup Script
// Run this file once to create all tables and insert sample data

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Setting up Car Detailing Database...</h2>";
    
    // Read and execute the SQL setup file
    $sql_content = file_get_contents(__DIR__ . '/setup_tables.sql');
    
    // Split SQL statements
    $statements = explode(';', $sql_content);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "<p style='color: green;'>✓ Executed SQL statement successfully</p>";
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3 style='color: green;'>✅ Database setup completed!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Login as a user and test booking services</li>";
    echo "<li>Login as admin to manage bookings</li>";
    echo "<li>All data will sync in real-time between user and admin dashboards</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error setting up database:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        h2, h3 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 20px 0; }
        li { margin: 5px 0; }
    </style>
</head>
<body>
    <a href="../user/dashboard_CLEAN.php" style="display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 5px 0 0;">Test User Dashboard</a>
    <a href="../admin/dashboard_NEW.php" style="display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0 0 0;">Test Admin Dashboard</a>
</body>
</html>