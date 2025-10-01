<?php
// This script will hash the password 'admin123' and output the hash for comparison
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password\n";
echo "Hash: $hash\n";
?>
