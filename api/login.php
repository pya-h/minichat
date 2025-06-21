<?php
session_start();
require_once '../includes/db.php';

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    $_SESSION['login_error'] = 'Missing username or password';
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    // Register new user on login attempt with keypair
    $config = [
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "private_key_bits" => 2048,
    ];

    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privatePem);
    $details = openssl_pkey_get_details($res);
    $publicPem = $details["key"];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, public_key, private_key) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $passwordHash, $publicPem, $privatePem]);

    $userId = $pdo->lastInsertId();

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    header('Location: ../dashboard.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid password';
    header('Location: ../index.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

header('Location: ../dashboard.php');
exit;
