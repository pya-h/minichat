<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = trim($_GET['username'] ?? '');

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username parameter']);
    exit;
}

// Validate username format
if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{2,}$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid username format']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'exists' => $user !== false,
        'username' => $username
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 