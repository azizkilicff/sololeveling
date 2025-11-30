<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();

// Ensure "name" column exists
try {
  $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'name'");
  $stmt->execute();
  if (!$stmt->fetch()) {
    $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255) DEFAULT ''");
  }
} catch (Throwable $e) {
  // ignore
}
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
if (!$username || !$email || !$password || !$name) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }
$hash = password_hash($password, PASSWORD_BCRYPT);
try {
  $stmt = $pdo->prepare('INSERT INTO users (username,name,email,password_hash) VALUES (?,?,?,?)');
  $stmt->execute([$username,$name,$email,$hash]);
  $_SESSION['user_id'] = $pdo->lastInsertId();
  echo json_encode(['ok'=>true]);
} catch (PDOException $e) {
  http_response_code(400);
  echo json_encode(['error'=>'Username or email already exists']);
}
