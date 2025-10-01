# 🚗 Ride Revive Detailing Website

A modern car detailing business website with comprehensive booking and management features.

## ✨ Features

### 🎨 **Modern Design**
- Glassmorphism UI with backdrop blur effects
- TO BE HERO X inspired loading animations
- Responsive design for all devices
- Gold gradient theme (#FFD700 to #FFA500)

### 🔐 **Authentication System**
- User registration and login
- Google OAuth integration
- Password reset functionality
- Role-based access (Admin/User)

### 📊 **Admin Dashboard**
- Business analytics and charts
- User and booking management
- Revenue tracking
- Service distribution analytics

### 👤 **User Dashboard**
- Personal booking history
- Service statistics
- Profile management
- Quick booking actions

### 🎪 **Loading Animations**
- Water bucket text fill effect
- Glitch reveal animations
- Particle systems
- Car driving progress indicator

## 🛠️ **Technology Stack**

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7+
- **Database**: MySQL
- **Charts**: Chart.js
- **Icons**: FontAwesome 6
- **Authentication**: Google OAuth 2.0

## 📋 **Setup Instructions**

### Prerequisites
- XAMPP or similar local server
- PHP 7+ with PDO extension
- MySQL database

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/car-detailing-website.git
   cd car-detailing-website
   ```

2. **Database Setup**
   - Create a MySQL database named `car_detailing`
   - Import the SQL schema (if provided)
   - Update database credentials in `config/database.php`

3. **Google OAuth Setup**
   - Create a project in [Google Cloud Console](https://console.cloud.google.com/)
   - Enable Google+ API
   - Create OAuth 2.0 credentials
   - Add authorized redirect URIs:
     - `http://localhost/car-detailing/auth/google-callback.php`
     - `http://127.0.0.1/car-detailing/auth/google-callback.php`
   - Update client ID and secret in authentication files

4. **Configure Database**
   ```php
   // config/database.php
   private $host = "localhost";
   private $db_name = "car_detailing";
   private $username = "root";
   private $password = "";
   ```

5. **Start Local Server**
   - Place files in `htdocs` folder (XAMPP)
   - Start Apache and MySQL
   - Access: `http://localhost/car-detailing/`

## 📁 **Project Structure**

```
car-detailing/
├── admin/
│   └── dashboard.php          # Admin control panel
├── auth/
│   ├── login.php             # User authentication
│   ├── register.php          # User registration
│   ├── reset_password.php    # Password reset
│   └── google-callback.php   # OAuth handler
├── user/
│   └── dashboard.php         # User dashboard
├── config/
│   └── database.php          # Database configuration
├── css/
│   └── styles.css           # Global styles
├── images/
│   ├── backg.png            # Background image
│   └── mini-car.png         # Loading animation car
└── index.php                # Landing page
```

## 🎯 **Key Features Demo**

### Authentication Flow
1. **Modern Login/Register**: Glassmorphism design with Google OAuth
2. **Password Reset**: Secure token-based system
3. **Role Management**: Automatic admin/user dashboard routing

### Loading Experience
- **TO BE HERO X Style**: Inspired by premium gaming websites
- **Water Bucket Effect**: Text fills up like liquid
- **Glitch Animations**: RGB color separation effects
- **Car Progress**: Mini car drives along progress bar

### Dashboard Analytics
- **Admin**: Revenue charts, user management, booking analytics
- **User**: Personal stats, booking history, quick actions

## 🔧 **Customization**

### Color Scheme
The website uses a gold gradient theme. To change colors, update:
```css
/* Primary gradient */
background: linear-gradient(135deg, #FFD700, #FFA500);

/* Accent colors */
color: #FFD700;
border-color: rgba(255, 215, 0, 0.2);
```

### Loading Animations
Customize loading text and timing in the dashboard files:
```javascript
// Change loading text
data-text="YOUR CUSTOM TEXT"

// Adjust animation timing
animation: textFillUp 3.5s ease-in-out 0.5s forwards;
```

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📞 **Support**

For questions or support, please contact:
- Email: support@riderevivedetailing.com
- GitHub Issues: Create an issue in this repository

## 📄 **License**

This project is licensed under the MIT License - see the LICENSE file for details.

## 🏆 **Acknowledgments**

- TO BE HERO X for loading animation inspiration
- FontAwesome for icons
- Chart.js for analytics
- Google for OAuth integration

---

**Made with ❤️ for car detailing enthusiasts**