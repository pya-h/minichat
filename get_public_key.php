<?php
require 'db.php';
$username = $_GET['username'] ?? '';
$stmt = $pdo->prepare("SELECT public_key FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['public_key']) {
    echo json_encode(['publicKey' => $user['public_key']]);
} else {
    http_response_code(404);
}
