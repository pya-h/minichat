<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Not logged in']);
  exit;
}

$target = $_GET['target'] ?? '';
if (!$target) {
  http_response_code(400);
  echo json_encode(['error' => 'Target username is required']);
  exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$target]);
$targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$targetUser) {
  http_response_code(404);
  echo json_encode(['error' => 'Target user not found']);
  exit;
}

$targetId = $targetUser['id'];

// Fetch messages where current user is sender or receiver
$stmt = $pdo->prepare("
  SELECT 
    m.sender_id,
    u.username AS sender,
    m.receiver_id,
    m.message,
    m.message_for_sender,
    m.created_at
  FROM messages m
  JOIN users u ON u.id = m.sender_id
  WHERE 
    (sender_id = :user AND receiver_id = :target) OR
    (sender_id = :target AND receiver_id = :user)
  ORDER BY m.created_at ASC
");
$stmt->execute([
  'user' => $userId,
  'target' => $targetId,
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return message encrypted for *this* user
$messages = [];
foreach ($rows as $row) {
  $messages[] = [
    'sender' => $row['sender'],
    'encrypted_message' => ($row['sender_id'] == $userId) ? $row['message_for_sender'] : $row['message'],
    'created_at' => $row['created_at'],
  ];
}

echo json_encode($messages);
