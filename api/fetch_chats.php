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

$sql = "
  SELECT u.username
  FROM users u
  INNER JOIN (
    SELECT sender_id AS uid FROM messages WHERE receiver_id = :uid
    UNION
    SELECT receiver_id AS uid FROM messages WHERE sender_id = :uid
  ) m ON u.id = m.uid
  WHERE u.id != :uid
  GROUP BY u.username
  ORDER BY MAX(m.uid) DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['uid' => $userId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($users);
