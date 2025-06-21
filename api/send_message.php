<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$senderUsername = $_SESSION['username'];

$target = $_POST['target'] ?? '';
$message = $_POST['message'] ?? '';
$messageForSender = $_POST['message_for_sender'] ?? '';

if (!$target || !$message || !$messageForSender) {
    http_response_code(400);
    echo json_encode(['error' => 'Target and both encrypted messages are required']);
    exit;
}

// Find or create target user
$stmt = $pdo->prepare("SELECT id, public_key FROM users WHERE username = ?");
$stmt->execute([$target]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, '')");
    $stmt->execute([$target]);
    $targetUserId = $pdo->lastInsertId();
} else {
    $targetUserId = $targetUser['id'];
    if (!$targetUser['public_key']) {
        http_response_code(400);
        echo json_encode(['error' => 'Target user has no public key; cannot send encrypted message']);
        exit;
    }
}

// Save message with both encrypted versions
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, message_for_sender) VALUES (?, ?, ?, ?)");
$stmt->execute([$userId, $targetUserId, $message, $messageForSender]);

echo json_encode(['status' => 'ok']);
