<?php
function loadEnv(string $path)
{
    if (!file_exists($path))
        return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if (!isset($_ENV[$name])) {
            $value = trim($value, '"\'');
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

$host = $_ENV['DB_HOST'] ?? 'localhost';
$db = $_ENV['DB_NAME'] ?? 'minichat';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB connection failed: ' . $e->getMessage());
}
