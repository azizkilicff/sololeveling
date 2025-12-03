<?php
// public/config/db.php
declare(strict_types=1);

// Keep errors in server logs, not in JSON responses
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');

$DB_HOST = '127.0.0.1';
$DB_NAME = 'leveling_tracker';
$DB_USER = 'root';
$DB_PASS = 'StrongPass!2025';  // ✅ your password

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);

/* --- Sessions (needed for login) --- */
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* --- JSON helpers used by API endpoints --- */
function json_ok(array $data = []): void {
  header('Content-Type: application/json');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_error(string $msg, int $code = 400): void {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}