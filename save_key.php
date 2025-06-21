<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$publicKey = json_encode($data['publicKey'] ?? null);

if (!$publicKey) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET public_key = ? WHERE id = ?");
$stmt->execute([$publicKey, $_SESSION['user_id']]);
http_response_code(200);
