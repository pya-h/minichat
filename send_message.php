<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$me = $_SESSION['user_id'];
$target = trim($_POST['target'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$target || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing target or message']);
    exit;
}

try {
    // Find or create target user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$target]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        $password_hash = password_hash(random_bytes(8), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$target, $password_hash]);
        $targetUserId = $pdo->lastInsertId();
    } else {
        $targetUserId = $targetUser['id'];
    }

    // Insert message
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$me, $targetUserId, $message]);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
