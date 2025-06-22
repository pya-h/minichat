<?php
session_start();
require_once '../includes/db.php';

function isValidUsername($username)
{
    return preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{2,}$/', $username);
}

function isValidPassword($password)
{
    return strlen($password) >= 8 &&
        preg_match('/[0-9]/', $password) &&
        preg_match('/[a-zA-Z]/', $password) &&
        preg_match('/[^a-zA-Z0-9]/', $password);
}

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

if (!isValidUsername($username)) {
    $_SESSION['login_error'] = 'Username must be at least 3 characters, starting a letter & contains only letters, numbers, hyphens, and underscores';
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    if (!isValidPassword($password)) {
        $_SESSION['login_error'] = 'Password must be at least 8 characters and contain at least one digit, one letter, and one special character';
        header('Location: ../index.php');
        exit;
    }

    $config = [
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'private_key_bits' => 2048,
    ];

    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privatePem);
    $details = openssl_pkey_get_details($res);
    $publicPem = $details['key'];

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, public_key, private_key) VALUES (?, ?, ?, ?)');
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
