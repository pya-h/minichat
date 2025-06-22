<?php
session_start();

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(400);
    $post_max_size = ini_get('post_max_size');
    echo json_encode([
        'status' => 'error', 
        'error' => "The uploaded data exceeds the server's configured limit (post_max_size is {$post_max_size}). Please upload a smaller file."
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'User not logged in']);
    exit;
}


$sender_id = $_SESSION['user_id'];
$target_username = $_POST['target'] ?? null;
$message_for_recipient = $_POST['message'] ?? null;
$message_for_sender = $_POST['message_for_sender'] ?? null;
$image_file = $_FILES['image_file'] ?? null;


if (!$target_username) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'A required field was missing from the request.']);
    exit;
}

if (!isset($image_file) || $image_file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    
    $error_message = 'An unknown file upload error occurred.';
    if (isset($image_file['error'])) {
        switch ($image_file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "The uploaded file exceeds the server's maximum file size limit.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Server configuration error: Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Server error: Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'A server extension stopped the file upload.';
                break;
        }
    } else {
        $error_message = 'No file was sent with the request or the file was too large.';
    }
    
    echo json_encode(['status' => 'error', 'error' => $error_message]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$target_username]);
$receiver = $stmt->fetch();

if (!$receiver) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'error' => 'Target user not found']);
    exit;
}
$receiver_id = $receiver['id'];

$upload_dir = '../uploads/images/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($image_file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid image type. Only JPG, PNG, GIF, and WEBP are allowed.']);
    exit;
}

if ($image_file['size'] > 5 * 1024 * 1024) { // 5 MB limit
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Image file is too large. Max 5MB allowed.']);
    exit;
}

$file_extension = pathinfo($image_file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid('img_', true) . '.' . $file_extension;
$upload_path = $upload_dir . $unique_filename;

if (move_uploaded_file($image_file['tmp_name'], $upload_path)) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO messages (sender_id, receiver_id, message, message_for_sender, message_type, image_file_path) 
             VALUES (?, ?, ?, ?, 'image', ?)"
        );
        $stmt->execute([
            $sender_id, 
            $receiver_id,
            $message_for_recipient,
            $message_for_sender,
            'uploads/images/' . $unique_filename
        ]);

        echo json_encode(['status' => 'ok', 'message' => 'Image sent successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        unlink($upload_path); 
        echo json_encode(['status' => 'error', 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Failed to move uploaded file.']);
}