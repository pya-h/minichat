<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access Denied');
}

require_once '../includes/db.php';

$message_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$message_id) {
    http_response_code(400);
    exit('Bad Request: Missing message ID');
}

$stmt = $pdo->prepare("SELECT sender_id, receiver_id, image_file_path FROM messages WHERE id = ? AND message_type = 'image'");
$stmt->execute([$message_id]);
$message = $stmt->fetch();

if (!$message) {
    http_response_code(404);
    exit('Not Found: Image message not found.');
}

if ($message['sender_id'] != $user_id && $message['receiver_id'] != $user_id) {
    http_response_code(403);
    exit('Access Denied: You do not have permission to view this image.');
}

$file_path = '../' . $message['image_file_path'];

if (!file_exists($file_path)) {
    http_response_code(404);
    exit('Not Found: The image file is missing from the server.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit; 