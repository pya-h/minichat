<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$me = $_SESSION['user_id'];
$myUsername = $_SESSION['username'];
$target = $_GET['target'] ?? '';

if (!$target) {
    echo json_encode([]);
    exit;
}

// Find target user id
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$target]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    echo json_encode([]);
    exit;
}
$targetId = $targetUser['id'];

// Fetch all messages between me and target, ordered asc by time
$stmt = $pdo->prepare("
    SELECT m.sender_id, m.message, u.username AS sender_username
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.sent_at ASC
");
$stmt->execute([$me, $targetId, $targetId, $me]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for JSON
$out = array_map(fn($m) => [
    'sender' => $m['sender_username'],
    'message' => $m['message']
], $messages);

header('Content-Type: application/json');
echo json_encode($out);
