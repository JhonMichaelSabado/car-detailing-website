<?php
// Google Maps API Configuration
// 
// To get your Google Maps API key:
// 1. Go to https://console.cloud.google.com/
// 2. Create a new project or select existing one
// 3. Enable these APIs:
//    - Maps JavaScript API
//    - Places API  
//    - Geocoding API
// 4. Go to Credentials → Create API Key
// 5. Restrict the key to your domains for security
// 6. Replace the key below

define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY');

// Alternative: You can also set this as an environment variable
// define('GOOGLE_MAPS_API_KEY', $_ENV['GOOGLE_MAPS_API_KEY'] ?? 'YOUR_GOOGLE_MAPS_API_KEY');

// Default map center (Cavite Civic Center coordinates)
define('DEFAULT_MAP_LAT', 14.4791);  // Cavite Civic Center latitude
define('DEFAULT_MAP_LNG', 120.9099); // Cavite Civic Center longitude

// Country restriction for address autocomplete (ISO 3166-1 Alpha-2 country code)
define('MAP_COUNTRY_RESTRICTION', 'ph'); // 'ph' for Philippines, 'us' for USA, etc.
?>