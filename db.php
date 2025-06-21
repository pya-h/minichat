<?php
// Load .env file manually (simple)
$env = parse_ini_file(__DIR__ . '/.env');
$host = $env['DB_HOST'] ?? 'localhost';
$db = $env['DB_NAME'] ?? 'minichat';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo "DB Connection failed: " . $e->getMessage();
    exit;
}
