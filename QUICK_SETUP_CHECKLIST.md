# ✅ Quick Setup Checklist for Groupmates

## 📋 **Before You Start**
- [ ] XAMPP installed
- [ ] Git installed
- [ ] Got database SQL file from Jhon

## 🚀 **Setup Steps**
- [ ] Clone repository: `git clone https://github.com/JhonMichaelSabado/car-detailing-website.git`
- [ ] Move to XAMPP htdocs folder
- [ ] Start Apache and MySQL in XAMPP
- [ ] Create `car_detailing` database in phpMyAdmin
- [ ] Import database SQL file
- [ ] Create `config/database.php` file
- [ ] Create `config/google_config.php` file with shared OAuth credentials
- [ ] Test website: `http://127.0.0.1/car-detailing-website/`
- [ ] Test Google login

## 🔑 **Files You Need to Create**

### `config/database.php`
```php
<?php
$host = 'localhost';
$dbname = 'car_detailing';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

### `config/google_config.php`
```php
<?php
$google_config = [
    'client_id' => '551906749283-ve45j56noq4bm7r14gda9ustc7kaqla1.apps.googleusercontent.com',
    'client_secret' => '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',
    'redirect_uri' => 'http://127.0.0.1/car-detailing-website/auth/google-callback.php'
];
return $google_config;
?>
```

## ⚠️ **Important**
- Never commit these config files to Git
- They're already in .gitignore
- Ask Jhon for help if you get stuck!

## 🎯 **Success = Website loads + Google login works**
