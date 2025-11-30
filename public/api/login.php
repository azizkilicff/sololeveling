<?php
// public/api/login.php
require __DIR__ . '/../config/db.php'; // this already starts the session

$input    = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($username === '' || $password === '') {
  json_error('username and password are required', 400);
}

try {
  $stmt = $pdo->prepare('SELECT id, username, email, password_hash, xp FROM users WHERE username = ?');
  $stmt->execute([$username]);
  $u = $stmt->fetch();

  $valid = false;
  if ($u) {
    try {
      $valid = password_verify($password, $u['password_hash'] ?? '');
    } catch (ValueError $e) {
      // Happens if password_hash column contains plaintext or a malformed value
      $valid = false;
    }
  }

  if (!$u || !$valid) {
    json_error('invalid credentials', 401);
  }

  $_SESSION['user_id'] = (int)$u['id'];
  unset($u['password_hash']);        // never send hash back
  json_ok(['user' => $u]);           // consistent JSON payload

} catch (Throwable $e) {
  json_error('login failed: '.$e->getMessage(), 500);
}