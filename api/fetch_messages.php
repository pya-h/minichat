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
$otherUsername = $_GET['with'] ?? '';

if (!$otherUsername) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing target username']);
  exit;
}

// Get target user ID
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$otherUsername]);
$otherUser = $stmt->fetch();

if (!$otherUser) {
  http_response_code(404);
  echo json_encode(['error' => 'Target user not found']);
  exit;
}
$otherUserId = $otherUser['id'];

// Fetch messages between these two users ordered by created_at ASC
$stmt = $pdo->prepare("
    SELECT id, sender_id, receiver_id, message, message_for_sender, message_type, voice_file_path, image_file_path, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['messages' => $messages]);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
