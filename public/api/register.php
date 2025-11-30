<?php
header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
session_start();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
if (!$username || !$email || !$password) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }
$hash = password_hash($password, PASSWORD_BCRYPT);
try {
  $stmt = $pdo->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)');
  $stmt->execute([$username,$email,$hash]);
  $_SESSION['user_id'] = $pdo->lastInsertId();
  echo json_encode(['ok'=>true]);
} catch (PDOException $e) {
  http_response_code(400);
  echo json_encode(['error'=>'Username or email already exists']);
}
