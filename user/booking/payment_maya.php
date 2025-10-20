<?php
session_start();
require_once '../../includes/config.php';
/** @var PDO $pdo */

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

try {
    $stmt = $pdo->prepare("SELECT b.booking_id, b.booking_reference, b.total_amount, b.payment_status, b.user_id FROM bookings b WHERE b.booking_id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$booking) throw new Exception('Booking not found');
    if ($booking['user_id'] != $_SESSION['user_id']) throw new Exception('Unauthorized access to booking');
    $amount = number_format($booking['total_amount'], 2);
} catch (Exception $e) {
    die('Error: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Maya Payment - RideReviveDetailing (Demo)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #0f0f10; color: #f8f7f3; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }
        .maya-accent { color: #00d4aa; }
        .btn-maya { background: linear-gradient(90deg,#00e6b8,#00d4aa); color: #012; border-radius: 12px; }
        .otp-display { font-family: monospace; font-size: 1.6rem; letter-spacing: 4px; }
        .muted { color: rgba(255,255,255,0.6); }
    </style>
</head>
<body class="py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card p-4">
                <h4 class="mb-2 maya-accent"><i class="fas fa-wallet"></i> Authorize with Maya</h4>
                <p class="muted mb-1"><strong>Merchant:</strong> RideReviveDetailing</p>
                <p class="muted mb-2">Amount: <strong>₱<?= $amount ?></strong></p>

                <div class="alert alert-warning">
                    <strong>Security:</strong> Do not share your OTP with anyone. If you didn't request this, cancel immediately.
                </div>

                <div id="otpArea">
                    <button id="generateOtp" class="btn btn-maya w-100 mb-3">Generate OTP</button>
                    <div id="otpShown" style="display:none;" class="text-center mb-3">
                        <div class="small muted">One-time OTP (shown only once)</div>
                        <div id="otpValue" class="otp-display mt-2"></div>
                    </div>
                </div>

                <form method="post" action="payment_confirm.php">
                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                    <input type="hidden" name="method" value="maya">
                    <div class="mb-3">
                        <label class="form-label">Enter OTP</label>
                        <input type="text" name="otp_input" id="otpInput" class="form-control" pattern="[0-9]{6}" placeholder="6-digit OTP" required>
                    </div>
                    <button type="submit" class="btn btn-maya w-100">Authorize Payment</button>
                </form>
                <div class="mt-3 muted small">Demo only — no real charges.</div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('generateOtp').addEventListener('click', function(){
    var btn = this; btn.disabled = true; btn.innerText = 'Generating...';
    fetch('otp_send.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: <?= $booking_id ?>, method: 'maya' })
    }).then(r=>r.json()).then(function(resp){
        btn.disabled = false; btn.innerText = 'Generate OTP';
        if (resp.success) {
            document.getElementById('otpShown').style.display = 'block';
            document.getElementById('otpValue').innerText = resp.otp;
            setTimeout(function(){ document.getElementById('otpValue').innerText = '••••••'; }, 12000);
        } else {
            alert('Error: ' + resp.message);
        }
    }).catch(function(){ btn.disabled = false; btn.innerText = 'Generate OTP'; alert('Network error'); });
});
</script>
</body>
</html>
