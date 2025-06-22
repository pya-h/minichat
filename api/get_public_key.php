<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

$username = $_GET['username'] ?? '';
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username']);
    exit;
}

$stmt = $pdo->prepare('SELECT public_key FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

echo json_encode(['publicKey' => $user['public_key']]);
