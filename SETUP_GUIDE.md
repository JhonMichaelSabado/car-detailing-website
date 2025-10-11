# 🚗 Car Detailing Website - Setup Guide for Groupmates

## 📋 **Prerequisites**
- XAMPP installed and running
- Git installed
- GitHub account

## 🔧 **Setup Instructions**

### **Step 1: Clone the Repository**
```bash
git clone https://github.com/JhonMichaelSabado/car-detailing-website.git
cd car-detailing-website
```

### **Step 2: Move to XAMPP Directory**
Move the project folder to your XAMPP htdocs:
- Windows: `C:\xampp\htdocs\car-detailing`
- Mac: `/Applications/XAMPP/htdocs/car-detailing`

### **Step 3: Database Setup**
1. Start XAMPP (Apache + MySQL)
2. Go to `http://localhost/phpmyadmin`
3. Create database: `car_detailing`
4. Import the SQL file (ask Jhon for the database export)

### **Step 4: Database Configuration**
Create `config/database.php`:
```php
<?php
$host = 'localhost';
$dbname = 'car_detailing';
$username = 'root';
$password = ''; // Your MySQL password

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

### **Step 5: Google OAuth Setup**

#### **Option A: Use Shared Credentials (Quick)**
Create `config/google_config.php`:
```php
<?php
$google_config = [
    'client_id' => '551906749283-ve45j56noq4bm7r14gda9ustc7kaqla1.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => 'http://127.0.0.1/car-detailing/auth/google-callback.php'
];
return $google_config;
?>
```

#### **Option B: Create Your Own Google OAuth App (Recommended)**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create new project: "Car Detailing Website"
3. Enable Google+ API
4. Create OAuth 2.0 Client ID
5. Set Authorized redirect URI: `http://127.0.0.1/car-detailing/auth/google-callback.php`
6. Copy your credentials to `config/google_config.php`

### **Step 6: Test the Setup**
1. Visit: `http://127.0.0.1/car-detailing/`
2. Try registering/logging in
3. Test Google OAuth login

## 🚨 **Important Notes**
- Never commit `config/database.php` or `config/google_config.php` to Git
- These files are already in `.gitignore`
- Ask Jhon for the database SQL export file
- Make sure XAMPP Apache and MySQL are running

## 🆘 **Need Help?**
Contact Jhon Michael Sabado if you encounter any issues!
