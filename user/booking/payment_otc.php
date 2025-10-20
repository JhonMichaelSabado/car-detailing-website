<?php
// payment_otc.php
session_start();
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$otc_code = 'OTC' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Over-the-Counter Payment (Mock)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body text-center">
            <h4 class="mb-3">🏪 Over-the-Counter (Demo)</h4>
            <p>Pay at any 7-Eleven, Cebuana, or MLhuillier branch.</p>
            <div class="mb-2">Payment Code:</div>
            <div class="display-6 mb-2"><b><?=$otc_code?></b></div>
            <div class="mb-2">Amount: <b>₱1,250.00</b></div>
            <form method="post" action="payment_callback.php">
                <input type="hidden" name="booking_id" value="<?=$booking_id?>">
                <input type="hidden" name="method" value="otc">
                <input type="text" name="payer_name" class="form-control mb-2" placeholder="Your Name" required>
                <button type="submit" class="btn btn-warning w-100">Mark as Paid</button>
            </form>
            <div class="mt-3 text-muted">This is a demo. No real payment will be made.</div>
        </div>
    </div>
</div>
</body>
</html>
