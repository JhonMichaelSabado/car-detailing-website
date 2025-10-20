<?php
session_start();
require_once '../../includes/config.php';

// Simple auth check - ensure user owns the booking or session booking_id exists
if (!isset($_SESSION['user_id']) || !isset($_SESSION['booking_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized or no booking in session']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_SESSION['booking_id'];

// Allow only POST with file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Ensure upload directory exists
$uploadBase = __DIR__ . '/../../uploads/payments';
if (!is_dir($uploadBase)) {
    mkdir($uploadBase, 0755, true);
}

// Validate file
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['payment_proof'];
$allowedExt = ['jpg','jpeg','png','pdf'];
$maxSize = 5 * 1024 * 1024; // 5 MB

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit();
}

// Sanitize filename and create target path
$filename = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
$targetDir = $uploadBase . '/booking_' . intval($booking_id);
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
$targetPath = $targetDir . '/' . time() . '_' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit();
}

// Optional: create a DB table payment_proofs if not exists (safe to try)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_proofs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(1024) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending','verified','rejected') DEFAULT 'pending'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare("INSERT INTO payment_proofs (booking_id, user_id, filename, filepath) VALUES (?, ?, ?, ?)");
    $stmt->execute([$booking_id, $user_id, basename($targetPath), $targetPath]);

} catch (PDOException $e) {
    // Log error but proceed
    error_log('DB error saving payment proof: ' . $e->getMessage());
}

// Return JSON success
echo json_encode(['success' => true, 'message' => 'Payment proof uploaded', 'file' => basename($targetPath)]);
