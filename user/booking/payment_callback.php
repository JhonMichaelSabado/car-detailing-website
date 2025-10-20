<?php
// payment_callback.php (mock)
session_start();
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$method = isset($_POST['method']) ? $_POST['method'] : 'unknown';
// Here you would update DB: mark payment as completed for this booking
// For demo, just redirect to confirmation with a success message
header("Location: booking_confirmation.php?booking_id=$booking_id&paid=1&method=$method");
exit;
