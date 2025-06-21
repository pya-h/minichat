<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

$username = trim($_GET['username']);

$stmt = $pdo->prepare("SELECT public_key FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['public_key']) {
    http_response_code(404);
    echo json_encode(['error' => 'Public key not found']);
    exit;
}

// public_key is stored as JSON string, decode once before returning
echo json_encode(['publicKey' => $user['public_key']]);
