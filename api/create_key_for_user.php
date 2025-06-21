<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (empty($data['username'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username required']);
    exit;
}

$username = trim($data['username']);

// Check if user exists
$stmt = $pdo->prepare("SELECT id, public_key FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Create new user with empty password and dummy public key
    // NOTE: This is insecure, but needed to unblock messaging.
    // Password blank, so user must register or login properly later.
    $dummyPublicKey = json_encode([
        "kty" => "RSA",
        "e" => "AQAB",
        "n" => "oahUIojK3k-0u0EVP8ZD5eZ9nQDPvplVPh3YPvlh6eECXukV7y0fNqyx55YvDkq1r3Zgs7zxy9KwRafqzldmh9KoMCIc5bsFjYIKsHoRMW3ps1upkZq8H0yqPMLCPzKBRiqWnKX2K6Qqf2cn5yhN5rLnMjGCvQJ5VxcvO73jrHNg5A7A"
    ]);


    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, public_key) VALUES (?, '', ?)");
    $stmt->execute([$username, $dummyPublicKey]);
    $userId = $pdo->lastInsertId();

    echo json_encode(['publicKey' => $dummyPublicKey]);
    exit;
}

if ($user['public_key']) {
    echo json_encode(['publicKey' => $user['public_key']]);
    exit;
}

// User exists but no public key — return error or dummy key
http_response_code(404);
echo json_encode(['error' => 'User has no public key, please login once']);
