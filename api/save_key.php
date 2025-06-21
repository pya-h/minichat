<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['publicKey'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No public key provided']);
    exit;
}

$userId = $_SESSION['user_id'];
$publicKeyJson = json_encode($data['publicKey']); // Double-encode for storage as JSON string

try {
    $stmt = $pdo->prepare("UPDATE users SET public_key = ? WHERE id = ?");
    $stmt->execute([$publicKeyJson, $userId]);
    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: ' . $e->getMessage()]);
}
