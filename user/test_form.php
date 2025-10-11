<!DOCTYPE html>
<html>
<head>
    <title>Form Test</title>
</head>
<body>
    <h1>Form Submission Test</h1>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div style="background: #d4edda; padding: 10px; margin: 10px 0;">
            <h3>POST Data Received:</h3>
            <pre><?php print_r($_POST); ?></pre>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="create_advanced_booking">
        
        <label>Service Address:</label><br>
        <textarea name="service_address" rows="2" required>123 Test Street</textarea><br><br>
        
        <label>Contact Number:</label><br>
        <input type="tel" name="contact_number" value="09123456789" required><br><br>
        
        <label>Payment Option:</label><br>
        <input type="radio" name="payment_option" value="partial" checked> Partial<br>
        <input type="radio" name="payment_option" value="full"> Full<br><br>
        
        <button type="submit">Submit Test</button>
    </form>
    
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form being submitted...');
            const formData = new FormData(this);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }
        });
    </script>
</body>
</html>