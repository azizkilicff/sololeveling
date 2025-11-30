<?php
// public/api/me.php
declare(strict_types=1);

header('Content-Type: application/json');
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();

$stmt = $pdo->prepare('SELECT id, username, email, xp, created_at FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
  http_response_code(404);
  echo json_encode(['error' => 'User not found']);
  exit;
}

// compute, don’t persist
$user['level'] = level_from_xp((int)$user['xp']);

echo json_encode(['user' => $user]);