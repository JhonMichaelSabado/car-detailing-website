<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>Form Test Results</h1>";
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "<hr>";
    echo "<h3>Expected Values:</h3>";
    echo "payment_mode: " . ($_POST['payment_mode'] ?? 'NOT FOUND') . "<br>";
    echo "payment_method: " . ($_POST['payment_method'] ?? 'NOT FOUND') . "<br>";
    echo "<hr>";
    echo "<a href='step4_payment_mode.php'>Back to Step 4</a>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Form Test</title>
</head>
<body>
    <h1>Simple Payment Form Test</h1>
    <form method="POST" action="">
        <h3>Payment Mode:</h3>
        <input type="radio" name="payment_mode" value="50_percent" id="pm1">
        <label for="pm1">50% Deposit</label><br>
        <input type="radio" name="payment_mode" value="full_payment" id="pm2">
        <label for="pm2">Full Payment</label><br><br>
        
        <h3>Payment Method:</h3>
        <input type="radio" name="payment_method" value="gcash" id="pmethod1">
        <label for="pmethod1">GCash</label><br>
        <input type="radio" name="payment_method" value="paymaya" id="pmethod2">
        <label for="pmethod2">PayMaya</label><br><br>
        
        <button type="submit">Test Submit</button>
    </form>
</body>
</html>