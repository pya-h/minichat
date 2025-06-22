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

$stmt = $pdo->prepare('
  SELECT DISTINCT u.username
  FROM users u
  JOIN (
    SELECT sender_id as user_id FROM messages WHERE receiver_id = ?
    UNION
    SELECT receiver_id as user_id FROM messages WHERE sender_id = ?
  ) m ON u.id = m.user_id
  WHERE u.id != ?
');
$stmt->execute([$userId, $userId, $userId]);
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['chatUsers' => $users]);
