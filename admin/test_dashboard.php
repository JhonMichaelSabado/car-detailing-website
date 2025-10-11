<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dashboard</title>
    <style>
        body {
            background: #1a1a1a;
            color: white;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .page-content {
            display: none;
        }
        .page-content.active {
            display: block !important;
        }
        .nav-link {
            color: #FFD700;
            text-decoration: none;
            margin: 10px;
            padding: 10px;
            background: #333;
            border-radius: 5px;
            display: inline-block;
        }
        .nav-link.active {
            background: #FFD700;
            color: #000;
        }
    </style>
</head>
<body>
    <h1>Test Dashboard</h1>
    
    <nav>
        <a href="#" class="nav-link active" data-page="dashboard">Dashboard</a>
        <a href="#" class="nav-link" data-page="services">Services</a>
    </nav>
    
    <div id="dashboard-content" class="page-content active">
        <h2>Dashboard Content</h2>
        <p>This content should stay visible!</p>
        <div style="padding: 20px; background: #333; margin: 20px 0;">
            <h3>Stats</h3>
            <p>Total Users: 10</p>
            <p>Total Bookings: 5</p>
        </div>
    </div>
    
    <div id="services-content" class="page-content">
        <h2>Services Content</h2>
        <p>Services section content here</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded');
            
            const navLinks = document.querySelectorAll('.nav-link');
            const pageContents = document.querySelectorAll('.page-content');
            
            console.log('Found nav links:', navLinks.length);
            console.log('Found page contents:', pageContents.length);
            
            // Ensure dashboard is visible
            const dashboardContent = document.getElementById('dashboard-content');
            if (dashboardContent) {
                dashboardContent.style.display = 'block';
                console.log('Dashboard made visible');
            }
            
            // Navigation
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Clicked:', this.getAttribute('data-page'));
                    
                    // Remove active from all
                    navLinks.forEach(l => l.classList.remove('active'));
                    pageContents.forEach(c => c.style.display = 'none');
                    
                    // Add active to current
                    this.classList.add('active');
                    const page = this.getAttribute('data-page');
                    const targetContent = document.getElementById(page + '-content');
                    if (targetContent) {
                        targetContent.style.display = 'block';
                        console.log('Showing:', page + '-content');
                    }
                });
            });
        });
    </script>
</body>
</html>