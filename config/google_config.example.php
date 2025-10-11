<?php
// Google OAuth Configuration Template
// Copy this file to google_config.php and add your actual credentials

$google_config = [
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID_HERE',
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET_HERE',
    'redirect_uri' => 'http://127.0.0.1/car-detailing/auth/google-callback.php'
];

return $google_config;
?>
