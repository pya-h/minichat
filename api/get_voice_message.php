<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Not authorized');
}

$userId = $_SESSION['user_id'];
$messageId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$messageId) {
    http_response_code(400);
    exit('Missing message id');
}

$stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND message_type = 'voice'");
$stmt->execute([$messageId]);
$message = $stmt->fetch();

if (!$message || ($message['sender_id'] != $userId && $message['receiver_id'] != $userId)) {
    http_response_code(403);
    exit('Forbidden');
}

$voiceFile = $message['voice_file_path'];
$fullPath = realpath(__DIR__ . '/../uploads/voice_messages/' . $voiceFile);
$uploadsDir = realpath(__DIR__ . '/../uploads/voice_messages/');

if (!$fullPath || strpos($fullPath, $uploadsDir) !== 0 || !file_exists($fullPath)) {
    http_response_code(404);
    exit('File not found');
}

$mimeType = mime_content_type($fullPath);
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Content-Disposition: inline; filename="' . basename($voiceFile) . '"');
readfile($fullPath);
exit; 