<?php
// public/api/user.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/progress.php';

require_login();
ensure_progress_tables($pdo);

$uid = (int)($_GET['id'] ?? 0);
if ($uid <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, name, email, xp FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$progress = fetch_progress($pdo, $uid);
$details = level_details((int)$user['xp']);
$user['level'] = $details['level'];
$user['xp_in_level'] = $details['xp_in_level'];
$user['xp_cap'] = $details['xp_cap'];
$user['streak'] = (int)$progress['streak_count'];
sync_level_achievements($pdo, $uid, $user['level']);
sync_streak_achievements($pdo, $uid, $user['streak']);

echo json_encode(['user' => $user]);
