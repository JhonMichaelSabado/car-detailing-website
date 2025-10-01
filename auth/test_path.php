<?php
echo "Current directory: " . __DIR__ . "<br>";
echo "Looking for: " . __DIR__ . "/../config/database.php<br>";

if (file_exists(__DIR__ . "/../config/database.php")) {
    echo "File EXISTS!<br>";
    require_once __DIR__ . "/../config/database.php";
    echo "File included successfully!<br>";
    $db = new Database();
    echo "Database class created successfully!<br>";
} else {
    echo "File NOT FOUND!<br>";
}
?>