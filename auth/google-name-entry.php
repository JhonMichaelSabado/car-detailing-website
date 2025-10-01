<?php
session_start();
if (!isset($_SESSION['google_temp'])) {
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Enter Your Full Name - Ride Revive</title>
    <style>
        body {
            margin: 0;
            font-family: 'Arial', sans-serif;
            background-image: url('../images/backg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #e0e0e0;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: flex-start; /* align box a little to the right */
            padding-left: 80px;
            position: relative;
            /* Add this CSS variable to control container horizontal position */
            --container-offset-x: 1100px;
            --panel-top: 100px;
            --panel-left: -760px;
        }
        .container {
            display: flex;
            width: var(--container-width, 600px); /* adjustable width via CSS variable */
            min-height: 700px; /* increased height to avoid cutting text */
            background: #121212;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
            overflow: visible; /* allow overflow to show text */
            /* Use CSS transform to move container horizontally and vertically */
            transform: translateX(var(--container-offset-x)) translateY(-100px); /* Negative Y value moves it up; adjust as needed */
            padding-top: 40px; /* add padding top to avoid cutting top text */
            padding-bottom: 40px; /* add padding bottom to avoid cutting bottom text */
        }
        .signup-form {
            flex: 1;
            padding: 40px 40px 40px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #222222;
            border-radius: 0 12px 12px 0;
            color: #e0e0e0;
        }
        .signup-form h1 {
            margin-bottom: 24px;
            font-weight: 700;
            font-size: 24px;
            color: #FFD700;
            user-select: none;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #e0e0e0;
        }
        input, select {
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #333;
            border-radius: 6px;
            outline-offset: 2px;
            outline-color: transparent;
            background: #333;
            color: #e0e0e0;
            transition: outline-color 0.2s ease;
        }
        input::placeholder {
            color: #999;
        }
        input:focus, select:focus {
            outline-color: #FFD700;
            border-color: #FFD700;
            background: #444;
        }
        button {
            margin-top: 12px;
            padding: 12px;
            background-color: #FFD700;
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            user-select: none;
        }
        button:hover {
            background-color: #e6c200;
        }
        .footer {
            margin-top: auto;
            font-size: 12px;
            color: #ccc;
            text-align: center;
            user-select: none;
        }
        .footer a {
            color: #FFD700;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-form">
            <h1>Enter Your Full Name</h1>
            <form onsubmit="return saveName(event)">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" required />
                <button type="submit">Continue to Dashboard</button>
            </form>
            <div class="footer">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>
    <script>
        function saveName(event) {
            event.preventDefault();
            const fullName = document.getElementById('full_name').value.trim();
            if (fullName === '') {
                alert('Please enter your full name!');
                return false;
            }

            fetch('google-signin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=save_name&full_name=' + encodeURIComponent(fullName)
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert('Registration failed: ' + (data.error || 'Unknown error'));
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    alert('Invalid response from server: ' + text);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error during registration: ' + error);
            });

            return false;
        }
    </script>
</body>
</html>
