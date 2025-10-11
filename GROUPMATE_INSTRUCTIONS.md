# 🚗 Car Detailing Website - Complete Setup Instructions for Groupmates

## 📋 **What You'll Get**
- Complete Apple-style car detailing website
- User authentication system with Google OAuth
- Admin and user dashboards
- Booking system
- Beautiful responsive design

---

## 🔧 **Step-by-Step Setup Guide**

### **Step 1: Install Prerequisites**
1. **Download and Install XAMPP**
   - Go to: https://www.apachefriends.org/download.html
   - Download XAMPP for your operating system
   - Install with default settings
   - Make sure Apache and MySQL are included

2. **Install Git** (if not already installed)
   - Go to: https://git-scm.com/downloads
   - Download and install for your OS

### **Step 2: Clone the GitHub Repository**
1. **Open Command Prompt/Terminal**
2. **Navigate to your XAMPP htdocs folder:**
   ```bash
   # Windows
   cd C:\xampp\htdocs
   
   # Mac
   cd /Applications/XAMPP/htdocs
   
   # Linux
   cd /opt/lampp/htdocs
   ```

3. **Clone the repository:**
   ```bash
   git clone https://github.com/JhonMichaelSabado/car-detailing-website.git
   cd car-detailing-website
   ```

### **Step 3: Start XAMPP Services**
1. **Open XAMPP Control Panel**
2. **Start Apache** (click Start button)
3. **Start MySQL** (click Start button)
4. **Verify both are running** (should show green "Running" status)

### **Step 4: Create Database**
1. **Open your browser**
2. **Go to:** `http://localhost/phpmyadmin`
3. **Click "New" on the left sidebar**
4. **Database name:** `car_detailing`
5. **Click "Create"**
6. **Ask Jhon for the database SQL file and import it:**
   - Click on your `car_detailing` database
   - Click "Import" tab
   - Choose the SQL file Jhon provides
   - Click "Go"

### **Step 5: Configure Database Connection**
1. **Navigate to your project folder:** `C:\xampp\htdocs\car-detailing-website`
2. **Go to the `config` folder**
3. **Create a new file:** `database.php`
4. **Copy and paste this code:**
   ```php
   <?php
   $host = 'localhost';
   $dbname = 'car_detailing';
   $username = 'root';
   $password = ''; // Leave empty for default XAMPP setup
   
   try {
       $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
       $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch(PDOException $e) {
       die("Connection failed: " . $e->getMessage());
   }
   ?>
   ```
5. **Save the file**

### **Step 6: Configure Google OAuth (Shared Credentials)**
1. **In the same `config` folder**
2. **Create a new file:** `google_config.php`
3. **Copy and paste this code:**
   ```php
   <?php
   // Google OAuth Configuration (Shared by Jhon)
   $google_config = [
       'client_id' => '551906749283-ve45j56noq4bm7r14gda9ustc7kaqla1.apps.googleusercontent.com',
       'client_secret' => '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',
       'redirect_uri' => 'http://127.0.0.1/car-detailing-website/auth/google-callback.php'
   ];
   
   return $google_config;
   ?>
   ```
4. **Save the file**

### **Step 7: Test Your Setup**
1. **Open your browser**
2. **Go to:** `http://127.0.0.1/car-detailing-website/`
3. **You should see the beautiful car detailing homepage!**

### **Step 8: Test User Registration/Login**
1. **Click "Sign In" in the top navigation**
2. **Try creating a new account**
3. **Try "Continue with Google" - it should work!**
4. **Test logging in with your new account**

---

## 🔐 **Important Security Notes**

### **Files You Should NEVER Commit to Git:**
- `config/database.php`
- `config/google_config.php`

These files contain sensitive information and are already in `.gitignore`.

### **If You Make Changes to the Code:**
1. **Add your changes:**
   ```bash
   git add .
   ```
2. **Commit your changes:**
   ```bash
   git commit -m "Description of your changes"
   ```
3. **Push to GitHub:**
   ```bash
   git push origin main
   ```

---

## 🆘 **Troubleshooting**

### **Common Issues:**

1. **"Access forbidden" error**
   - Make sure you're accessing `http://127.0.0.1/car-detailing-website/` (not localhost)
   - Check that Apache is running in XAMPP

2. **Database connection error**
   - Verify MySQL is running in XAMPP
   - Check your `config/database.php` settings
   - Make sure you imported the database SQL file

3. **Google OAuth not working**
   - Verify your `config/google_config.php` file is correct
   - Make sure the redirect URI matches exactly

4. **Page not found errors**
   - Check that all files were cloned properly
   - Verify you're in the correct directory

### **Getting Updates from GitHub:**
When Jhon makes updates to the code:
```bash
cd C:\xampp\htdocs\car-detailing-website
git pull origin main
```

---

## 📞 **Need Help?**

**Contact Jhon Michael Sabado if you encounter any issues!**

**What to include when asking for help:**
- Screenshot of the error
- What step you were on
- Your operating system
- XAMPP version

---

## 🎉 **You're All Set!**

Once everything is working, you'll have:
- ✅ Beautiful Apple-style car detailing website
- ✅ Working user registration and login
- ✅ Google OAuth authentication
- ✅ Admin and user dashboards
- ✅ Complete booking system
- ✅ Responsive design for all devices

**Welcome to the team! 🚗✨**
