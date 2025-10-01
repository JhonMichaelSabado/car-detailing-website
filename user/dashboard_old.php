<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ride Revive Detailing - User Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css" />
    <style>
        body, html {
            height: 100%;
            margin: 0;
            background: linear-gradient(-45deg, #000000, #1a1a1a, #2d2d2d, #FFD70010);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: #e0e0e0;
            font-family: 'Arial', sans-serif;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        /* User Panel Loading Screen Styles */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: radial-gradient(circle at center, #0a0a0a 0%, #000000 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            overflow: hidden;
        }

        /* TO BE HERO X Style Animations */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideSkew {
            0% {
                opacity: 0;
                transform: translateX(-50%) translateY(20px) skewX(-15deg) scaleX(0);
            }
            100% {
                opacity: 0.8;
                transform: translateX(-50%) translateY(0) skewX(-15deg) scaleX(1);
            }
        }

        @keyframes glitchReveal {
            0% {
                opacity: 1;
                transform: translateX(0);
            }
            10% {
                opacity: 0.8;
                transform: translateX(-2px);
            }
            20% {
                opacity: 0.3;
                transform: translateX(4px);
            }
            30% {
                opacity: 0.9;
                transform: translateX(-3px);
            }
            40% {
                opacity: 0.1;
                transform: translateX(5px);
            }
            50% {
                opacity: 0.7;
                transform: translateX(-1px);
            }
            60% {
                opacity: 0.4;
                transform: translateX(3px);
            }
            70% {
                opacity: 0.9;
                transform: translateX(-2px);
            }
            80% {
                opacity: 0.2;
                transform: translateX(4px);
            }
            90% {
                opacity: 0.8;
                transform: translateX(-1px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes textFillUp {
            0% {
                clip-path: inset(100% 0 0 0);
            }
            100% {
                clip-path: inset(0% 0 0 0);
            }
        }

        @keyframes glitchFlash {
            0%, 90% {
                opacity: 0;
                transform: translateX(0);
            }
            91% {
                opacity: 0.3;
                transform: translateX(2px) skewX(2deg);
            }
            92% {
                opacity: 0;
                transform: translateX(-3px) skewX(-3deg);
            }
            93% {
                opacity: 0.6;
                transform: translateX(1px) skewX(1deg);
            }
            94% {
                opacity: 0;
                transform: translateX(-2px) skewX(-2deg);
            }
            95% {
                opacity: 0.4;
                transform: translateX(3px) skewX(3deg);
            }
            96% {
                opacity: 0;
                transform: translateX(-1px) skewX(-1deg);
            }
            97% {
                opacity: 0.7;
                transform: translateX(2px) skewX(2deg);
            }
            98% {
                opacity: 0;
                transform: translateX(-2px) skewX(-2deg);
            }
            99% {
                opacity: 0.5;
                transform: translateX(1px) skewX(1deg);
            }
            100% {
                opacity: 0;
                transform: translateX(0) skewX(0deg);
            }
        }

        @keyframes progressFill {
            0% {
                width: 0%;
            }
            100% {
                width: 100%;
            }
        }

        @keyframes carDrive {
            0% {
                left: -100px;
            }
            100% {
                left: calc(100% - 20px);
            }
        }

        /* Particle System */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 1px;
            height: 1px;
            background: #FFD700;
            border-radius: 50%;
            animation: particleFloat 4s infinite ease-in-out;
            opacity: 0;
        }

        .particle:nth-child(odd) {
            background: #FFA500;
            animation-duration: 6s;
        }

        @keyframes particleFloat {
            0% { 
                opacity: 0;
                transform: translateY(100vh) scale(0);
            }
            20% { 
                opacity: 0.3;
            }
            80% { 
                opacity: 0.3;
            }
            100% { 
                opacity: 0;
                transform: translateY(-100px) scale(1);
            }
        }

        /* Circuit Board Background Pattern */
        .circuit-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                linear-gradient(90deg, #FFD700 1px, transparent 1px),
                linear-gradient(0deg, #FFD700 1px, transparent 1px);
            background-size: 50px 50px;
            animation: circuitFlow 8s linear infinite;
        }

        @keyframes circuitFlow {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* User Panel Specific Animations */
        @keyframes userTextReveal {
            0% {
                opacity: 0;
                transform: translateY(50px) scale(0.8);
                filter: blur(10px);
            }
            50% {
                opacity: 0.7;
                transform: translateY(20px) scale(0.9);
                filter: blur(5px);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0px);
            }
        }

        @keyframes hologramEffect {
            0%, 100% {
                text-shadow: 
                    0 0 5px #FFD700,
                    0 0 10px #FFD700,
                    0 0 15px #FFD700;
            }
            50% {
                text-shadow: 
                    0 0 10px #FFD700,
                    0 0 20px #FFD700,
                    0 0 30px #FFD700,
                    0 0 40px #FFD700;
            }
        }

        @keyframes liquidFillHorizontal {
            0% {
                clip-path: inset(0 100% 0 0);
            }
            100% {
                clip-path: inset(0 0 0 0);
            }
        }

        @keyframes scanLine {
            0% {
                left: -100%;
            }
            100% {
                left: 100%;
            }
        }

        @keyframes energyPulse {
            0%, 100% {
                box-shadow: 
                    0 0 20px rgba(255, 215, 0, 0.3),
                    inset 0 0 20px rgba(255, 215, 0, 0.1);
            }
            50% {
                box-shadow: 
                    0 0 40px rgba(255, 215, 0, 0.6),
                    inset 0 0 30px rgba(255, 215, 0, 0.3);
            }
        }

        @keyframes carSlide {
            0% {
                right: -120px;
                transform: translateY(-50%) rotateY(0deg);
            }
            100% {
                right: calc(100% - 20px);
                transform: translateY(-50%) rotateY(0deg);
            }
        }

        @keyframes fadeInCorner {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Particle System for User Panel */
        .user-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .user-particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #FFD700;
            border-radius: 50%;
            animation: userParticleFloat 6s infinite linear;
            opacity: 0;
        }

        .user-particle:nth-child(even) {
            background: #FFA500;
            animation-duration: 8s;
        }

        @keyframes userParticleFloat {
            0% { 
                opacity: 0;
                transform: translateX(-100px) translateY(100vh) scale(0);
            }
            10% { 
                opacity: 0.6;
            }
            90% { 
                opacity: 0.6;
            }
            100% { 
                opacity: 0;
                transform: translateX(100px) translateY(-100px) scale(1);
            }
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        .dashboard {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
        .navbar {
            background: linear-gradient(90deg, #222222, #121212);
            border-bottom: 2px solid #FFD700;
            display: flex;
            align-items: center;
            padding: 0 30px;
            height: 60px;
            box-shadow: 0 2px 10px rgba(255, 215, 0, 0.3);
            position: relative;
            z-index: 10;
        }
        .navbar .logo {
            font-weight: bold;
            font-size: 24px;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
            flex: 1;
            user-select: none;
        }
        .navbar nav {
            display: flex;
            gap: 25px;
        }
        .navbar nav a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar nav a:hover {
            background-color: #FFD700;
            color: #000000;
            box-shadow: 0 0 10px #FFD700;
        }
        .dashboard-content {
            flex: 1;
            overflow-y: auto;
            padding: 30px 40px;
            background: linear-gradient(145deg, #121212, #222222);
            box-shadow: inset 0 0 30px rgba(255, 215, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 30px;
            animation: fadeInUp 0.8s ease-out;
        }
        .dashboard-content h2 {
            color: #FFD700;
            margin-bottom: 20px;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .dashboard-content p {
            font-size: 16px;
            line-height: 1.6;
            color: #ccc;
            max-width: 700px;
        }
        .btn-logout {
            background: linear-gradient(45deg, #FFD700, #bfa500);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
            user-select: none;
            align-self: flex-end;
            margin-bottom: 10px;
        }
        .btn-logout:hover {
            background: linear-gradient(45deg, #bfa500, #FFD700);
            box-shadow: 0 0 15px #FFD700;
        }
        /* Scrollbar styling */
        .dashboard-content::-webkit-scrollbar {
            width: 10px;
        }
        .dashboard-content::-webkit-scrollbar-track {
            background: #121212;
        }
        .dashboard-content::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 10px;
        }
        /* Widgets */
        .widgets {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .widget {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 15px;
            padding: 20px;
            flex: 1 1 300px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 25px rgba(255, 215, 0, 0.4);
        }
        .widget h3 {
            color: #FFD700;
            margin-bottom: 15px;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .widget p {
            color: #ccc;
            font-size: 14px;
            line-height: 1.4;
        }
        /* Booking calendar placeholder */
        .booking-calendar {
            background: #1a1a1a;
            border: 2px solid #FFD700;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(255, 215, 0, 0.2);
            color: #ccc;
            font-size: 16px;
            text-align: center;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .navbar nav {
                gap: 15px;
            }
            .dashboard-content {
                padding: 20px;
            }
            .widgets {
                flex-direction: column;
            }
        }
        #loading-screen {
            font-size: 1.5rem;
        }
        @keyframes drawText {
            to {
                stroke-dashoffset: 0;
            }
        }
        @keyframes fillText {
            to {
                opacity: 1;
                fill: white;
                stroke: none;
            }
        }
        @keyframes drawSlash {
            to {
                stroke-dashoffset: 0;
            }
        }
        @keyframes carDrive {
            0% {
                opacity: 0;
                transform: translateX(0);
            }
            100% {
                opacity: 1;
                transform: translateX(450px);
            }
        }
        @keyframes fadeInTagline {
            to {
                opacity: 1;
            }
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div id="loading-screen" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: radial-gradient(circle at center, #0a0a0a 0%, #000000 100%); display: flex; justify-content: center; align-items: center; z-index: 9999; overflow: hidden;">
        
        <!-- Loading Content Container -->
        <div class="loading__inner" style="position: relative; text-align: center; z-index: 10;">
            
            <div class="loading__logo" style="position: relative; margin-bottom: 60px;">
                
                <!-- Catch Text -->
                <div class="loading__catch" style="text-align: center; margin-bottom: 30px; position: relative;">
                    <h2 style="font-family: 'Arial', sans-serif; font-size: 20px; color: #FFD700; font-weight: 700; 
                               letter-spacing: 6px; margin: 0 0 8px 0; opacity: 0; 
                               animation: fadeInUp 1s ease-out 0.3s forwards; text-transform: uppercase;">
                        USER DASHBOARD
                    </h2>
                    <p style="font-family: 'Arial', sans-serif; font-size: 16px; color: rgba(255, 255, 255, 0.7); font-weight: 400; letter-spacing: 3px; text-transform: uppercase; opacity: 0; animation: fadeInUp 1s ease-out 0.7s forwards; margin: 0;">
                        Premium Car Detailing
                    </p>
                    <span class="bg-skew" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%) skewX(-15deg); width: 120%; height: 2px; background: linear-gradient(90deg, transparent, #FFD700, transparent); opacity: 0; animation: slideSkew 1s ease-out 1s forwards;"></span>
                </div>
                
                <!-- Main Title with Glitch -->
                <div class="glitch" style="position: relative; display: inline-block;">
                    <!-- Background transparent text with stroke -->
                    <h1 style="font-family: 'Impact', 'Arial Black', sans-serif; font-size: 72px; font-weight: 900; 
                               color: transparent; 
                               -webkit-text-stroke: 2px rgba(255, 255, 255, 0.3);
                               text-stroke: 2px rgba(255, 255, 255, 0.3);
                               text-transform: uppercase; letter-spacing: 4px; margin: 0; position: relative;">
                        RIDE REVIVE
                    </h1>
                    
                    <!-- White filling text overlay -->
                    <h1 style="font-family: 'Impact', 'Arial Black', sans-serif; font-size: 72px; font-weight: 900; 
                               color: #FFFFFF; text-transform: uppercase; letter-spacing: 4px; margin: 0; 
                               position: absolute; top: 0; left: 0; right: 0;
                               animation: textFillUp 3.5s ease-out forwards, glitchReveal 0.8s ease-out 3.2s;
                               clip-path: inset(100% 0 0 0);">
                        RIDE REVIVE
                    </h1>
                    
                    <!-- Glitch Layers -->
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #FFD700 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.05s ease-in-out 3.2s 15;"></span>
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #FF6B6B 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.07s ease-in-out 3.3s 10;"></span>
                    <span style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(90deg, transparent 0%, #4ECDC4 50%, transparent 100%); opacity: 0; animation: glitchFlash 0.06s ease-in-out 3.4s 8;"></span>
                </div>
                
                <!-- Loading Background Bar -->
                <div id="js-loadvalue" style="position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%); width: 400px; height: 3px; background: rgba(255, 215, 0, 0.2); border-radius: 2px; overflow: visible;">
                    <div class="loading__progress" style="height: 100%; width: 0%; background: linear-gradient(90deg, #FFD700, #FFA500); border-radius: 2px; animation: progressFill 4s ease-out forwards;"></div>
                    
                    <!-- Car driving along the progress line -->
                    <div class="car-container" style="position: absolute; top: -45px; left: -100px; width: 120px; height: 75px; animation: carDrive 4s ease-out forwards;">
                        <img src="../images/mini_car.png" alt="Car" style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.4));">
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Bottom Corner Loading Elements -->
        <!-- Loading Text - Bottom Left -->
        <div style="position: fixed; bottom: 30px; left: 30px; font-family: 'Arial', sans-serif;">
            <p style="font-size: 18px; color: #FFD700; font-weight: 700; letter-spacing: 4px; margin: 0; opacity: 0; animation: fadeInUp 1s ease-out 1s forwards;">
                LOADING
            </p>
        </div>
        
        <!-- Percentage - Bottom Right -->
        <div style="position: fixed; bottom: 30px; right: 30px; font-family: 'Arial', sans-serif;">
            <p style="font-size: 24px; color: #FFFFFF; font-weight: 900; letter-spacing: 2px; margin: 0; opacity: 0; animation: fadeInUp 1s ease-out 1s forwards;">
                <span class="js-loader-percentage">0</span>%
            </p>
        </div>
        
        <!-- Subtle Particles -->
        <div class="particles" id="particles" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></div>
        
    </div>
    <div class="dashboard" style="opacity: 0;">
        <header class="navbar">
            <div class="logo" style="animation: fadeInDown 1s ease forwards; opacity: 0;">Ride Revive Detailing</div>
            <nav>
                <a href="dashboard.php">Home</a>
                <a href="../about.php">About Us</a>
                <a href="../services.php">Services</a>
                <a href="../contact.php">Contact</a>
            </nav>
            <form action="../auth/logout.php" method="POST" style="margin-left: auto;">
                <button type="submit" class="btn-logout">Logout</button>
            </form>
        </header>
        <main class="dashboard-content" tabindex="0">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p>Manage your car detailing appointments, profile, and more here.</p>
            <div class="widgets">
                <div class="widget">
                    <h3>Upcoming Appointments</h3>
                    <p>You have no upcoming appointments.</p>
                </div>
                <div class="widget">
                    <h3>Profile Summary</h3>
                    <p>Name: <?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p>Role: User</p>
                </div>
                <div class="widget booking-calendar">
                    <h3>Booking Calendar</h3>
                    <p>Booking calendar will be implemented here.</p>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Create particle system (exact admin copy)
        function createParticles() {
            const container = document.getElementById('particles');
            if (!container) return;
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 4 + 's';
                particle.style.animationDuration = (4 + Math.random() * 2) + 's';
                container.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Animate loading percentage from 0% to 100% (exact admin copy)
        function animateLoadingPercentage() {
            const percentageElement = document.querySelector('.js-loader-percentage');
            if (!percentageElement) return;
            
            let currentPercent = 0;
            const duration = 4000; // 4 seconds to match text fill animation
            const increment = 100 / (duration / 50); // Update every 50ms
            
            const timer = setInterval(() => {
                currentPercent += increment;
                if (currentPercent >= 100) {
                    currentPercent = 100;
                    clearInterval(timer);
                }
                percentageElement.textContent = Math.floor(currentPercent);
            }, 50);
        }
        
        // Start percentage animation
        animateLoadingPercentage();
        
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loading-screen');
            const dashboard = document.querySelector('.dashboard');
            
            // Same loading time as admin panel
            const minLoadTime = 4000; // 4 seconds
            const startTime = Date.now();
            
            function showDashboard() {
                const elapsed = Date.now() - startTime;
                const remainingTime = Math.max(0, minLoadTime - elapsed);
                
                setTimeout(() => {
                    if (loadingScreen) {
                        // Same exit animation as admin
                        loadingScreen.style.transition = 'all 0.8s ease-out';
                        loadingScreen.style.transform = 'scale(1.05)';
                        loadingScreen.style.opacity = '0';
                        
                        setTimeout(() => {
                            loadingScreen.style.display = 'none';
                        }, 800);
                    }
                    
                    if (dashboard) {
                        dashboard.style.opacity = '1';
                        dashboard.style.transform = 'translateY(0)';
                    }
                }, remainingTime);
            }
            
            showDashboard();
        });
    </script>
</body>
</html>
