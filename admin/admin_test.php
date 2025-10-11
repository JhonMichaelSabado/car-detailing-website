<?php
session_start();

// Simple admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Please log in as admin first.";
    exit();
}

echo "Session data: ";
print_r($_SESSION);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: white; }
        .nav-link { display: block; padding: 10px; margin: 5px 0; background: #333; color: white; text-decoration: none; border-radius: 5px; cursor: pointer; }
        .nav-link:hover { background: #555; }
        .nav-link.active { background: #FFD700; color: #1a1a1a; }
        .content-section { display: none; padding: 20px; border: 1px solid #ccc; margin: 20px 0; }
        .content-section.active { display: block; }
    </style>
</head>
<body>
    <h1>Admin Dashboard Test - User: <?php echo $_SESSION['username']; ?></h1>
    
    <div onclick="alert('Click test works!')">Click me to test JavaScript</div>
    
    <nav>
        <a class="nav-link active" onclick="showSection('dashboard', this)">Dashboard</a>
        <a class="nav-link" onclick="showSection('bookings', this)">Bookings</a>
        <a class="nav-link" onclick="showSection('users', this)">Users</a>
    </nav>
    
    <div class="content">
        <section id="dashboard" class="content-section active">
            <h2>Dashboard Content</h2>
            <p>This is the dashboard section. JavaScript is working!</p>
        </section>
        
        <section id="bookings" class="content-section">
            <h2>Bookings Content</h2>
            <p>This is the bookings section.</p>
        </section>
        
        <section id="users" class="content-section">
            <h2>Users Content</h2>
            <p>This is the users section.</p>
        </section>
    </div>
    
    <script>
        console.log('JavaScript is loading...');
        
        function showSection(sectionId, linkEl) {
            console.log('showSection called with:', sectionId, linkEl);
            
            try {
                // Hide all sections
                const sections = document.querySelectorAll('.content-section');
                sections.forEach(section => section.classList.remove('active'));
                
                // Show target section
                const targetSection = document.getElementById(sectionId);
                if (targetSection) {
                    targetSection.classList.add('active');
                    console.log('Showing section:', sectionId);
                } else {
                    console.error('Section not found:', sectionId);
                }
                
                // Update nav links
                const navLinks = document.querySelectorAll('.nav-link');
                navLinks.forEach(link => link.classList.remove('active'));
                
                if (linkEl) {
                    linkEl.classList.add('active');
                }
            } catch (error) {
                console.error('Error in showSection:', error);
            }
        }
        
        console.log('JavaScript loaded successfully');
    </script>
</body>
</html>