<?php
// config.php
$host = '100.90.113.36';
$port = '5432';
$db   = 'postgres';
$user = 'postgres';
$pass = 'Raki_727';

// Charset removed from DSN to fix the crash
$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
}
?>