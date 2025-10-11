# 🗺️ Google Maps API Setup Guide

## Quick Setup Steps:

### 1. Get Your Google Maps API Key
1. **Go to Google Cloud Console**: https://console.cloud.google.com/
2. **Sign in** with your Google account
3. **Create a new project** or select an existing one
4. **Enable the required APIs**:
   - Maps JavaScript API
   - Places API
   - Geocoding API
5. **Create an API Key**:
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "API Key"
   - Copy the generated key

### 2. Configure Your API Key
1. **Open** `config/maps_config.php`
2. **Replace** `YOUR_GOOGLE_MAPS_API_KEY` with your actual API key:
   ```php
   define('GOOGLE_MAPS_API_KEY', 'YOUR_ACTUAL_API_KEY_HERE');
   ```

### 3. Secure Your API Key (Important!)
1. **In Google Cloud Console**, click on your API key to edit it
2. **Add Application Restrictions**:
   - Choose "HTTP referrers (web sites)"
   - Add these domains:
     - `localhost/*`
     - `127.0.0.1/*`
     - `yourdomain.com/*` (when you deploy)
3. **Add API Restrictions**:
   - Select "Restrict key"
   - Choose only the APIs you enabled

### 4. Customize Settings (Optional)
In `config/maps_config.php`, you can customize:
- **Default map location** (currently set to Manila, Philippines)
- **Country restriction** for address autocomplete
- **Map styling and behavior**

## Free Tier Limits:
- **$200 monthly credit** (usually enough for small businesses)
- **28,000 map loads per month** for free
- **17,000 autocomplete requests per month** for free

## Troubleshooting:
- ❌ **Map not loading**: Check API key and enabled APIs
- ❌ **"This page can't load Google Maps correctly"**: Check billing and API restrictions
- ❌ **Address autocomplete not working**: Enable Places API
- ❌ **Geocoding errors**: Enable Geocoding API

## Without API Key:
The booking system will still work! It will show a fallback message and users can manually enter their address. The admin will contact them to confirm the location.

## Support:
If you need help with API setup, the Google Cloud Console has excellent documentation and support.