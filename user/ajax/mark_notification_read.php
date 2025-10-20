<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing notification ID']);
    exit();
}

$notification_id = (int)$_POST['notification_id'];
$user_id = $_SESSION['user_id'];

try {
    // Update notification as read
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $result = $stmt->execute([$notification_id, $user_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>