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
$target = $_POST['target'] ?? '';
$messageEncryptedForRecipient = $_POST['message'] ?? null;
$messageEncryptedForSender = $_POST['message_for_sender'] ?? null;

if (!$target) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

// Validate file upload
if (!isset($_FILES['voice_file']) || $_FILES['voice_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Voice file upload failed']);
    exit;
}

$voiceFile = $_FILES['voice_file'];
$allowedTypes = ['audio/wav', 'audio/mp3', 'audio/ogg', 'audio/webm'];
$allowedExtensions = ['wav', 'mp3', 'ogg', 'webm'];
$maxSize = 10 * 1024 * 1024; // 10MB

// Validate file type and extension
$fileExtension = strtolower(pathinfo($voiceFile['name'], PATHINFO_EXTENSION));
if (!in_array($voiceFile['type'], $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only WAV, MP3, OGG, and WebM are allowed']);
    exit;
}

if ($voiceFile['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 10MB']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$target]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    http_response_code(404);
    echo json_encode(['error' => 'Target user not found']);
    exit;
}

$uploadsDir = '../uploads';
$voiceMessagesDir = '../uploads/voice_messages';

if (!is_dir($uploadsDir)) {
    if (!mkdir($uploadsDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create uploads directory']);
        exit;
    }
}

if (!is_dir($voiceMessagesDir)) {
    if (!mkdir($voiceMessagesDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create voice messages directory']);
        exit;
    }
}

if (!is_writable($voiceMessagesDir)) {
    http_response_code(500);
    echo json_encode(['error' => 'Voice messages directory is not writable']);
    exit;
}

$uniqueFilename = uniqid('voice_', true) . '.' . $fileExtension;
$uploadPath = $voiceMessagesDir . '/' . $uniqueFilename;

if (!move_uploaded_file($voiceFile['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save voice file']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, message_for_sender, message_type, voice_file_path) VALUES (?, ?, ?, ?, 'voice', ?)");
$stmt->execute([
    $userId,
    $targetUser['id'],
    $messageEncryptedForRecipient,
    $messageEncryptedForSender,
    $uniqueFilename,
]);

$messageId = $pdo->lastInsertId();

echo json_encode(['status' => 'ok', 'message_id' => $messageId, 'file_path' => $uniqueFilename]); 