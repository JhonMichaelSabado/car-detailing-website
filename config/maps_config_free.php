<?php
// Alternative Maps Configuration (No Credit Card Required)
// Using OpenStreetMap with Leaflet.js - completely free!

// Map provider options
define('USE_OPENSTREETMAP', true); // Set to true to use free OpenStreetMap instead of Google Maps

// Default map center (change to your preferred location)
define('DEFAULT_MAP_LAT', 14.5995);  // Manila, Philippines latitude
define('DEFAULT_MAP_LNG', 120.9842); // Manila, Philippines longitude

// OpenStreetMap tile server (free alternatives)
define('MAP_TILE_SERVER', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
define('MAP_ATTRIBUTION', '© OpenStreetMap contributors');

// Geocoding service (free alternative)
define('GEOCODING_SERVICE', 'https://nominatim.openstreetmap.org/search');

// If you later get Google Maps API key, set this:
define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY');
define('MAP_COUNTRY_RESTRICTION', 'ph'); // 'ph' for Philippines, 'us' for USA, etc.
?>