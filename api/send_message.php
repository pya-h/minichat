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
$target = $_POST['target'] ?? '';
$messageEncryptedForRecipient = $_POST['message'] ?? '';
$messageEncryptedForSender = $_POST['message_for_sender'] ?? '';

if (!$target || !$messageEncryptedForRecipient || !$messageEncryptedForSender) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Find target user
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$target]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['error' => 'Target user not found']);
    exit;
}

// Save encrypted text message
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, message_for_sender, message_type) VALUES (?, ?, ?, ?, 'text')");
$stmt->execute([
    $userId,
    $targetUser['id'],
    $messageEncryptedForRecipient,
    $messageEncryptedForSender,
]);

$messageId = $pdo->lastInsertId();

echo json_encode(['status' => 'ok', 'message_id' => $messageId]); 