<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$me = $_SESSION['user_id'];

// Get unique users who have chatted with me (either sent or received messages)
$stmt = $pdo->prepare("
    SELECT u.id, u.username FROM users u
    WHERE u.id IN (
        SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ?
        UNION
        SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?
    )
    ORDER BY u.username ASC
");
$stmt->execute([$me, $me]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($users);
