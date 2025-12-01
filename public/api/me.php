<?php
declare(strict_types=1);

header("Content-Type: application/json");

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/progress.php';   // <-- REQUIRED (was missing)

require_login();
ensure_progress_tables($pdo);

// Ensure "name" column exists on users
try {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'name'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255) DEFAULT ''");
    }
} catch (Throwable $e) {
    // fallback silent
}

$user_id = (int)$_SESSION['user_id'];

// fetch base user row
$stmt = $pdo->prepare("SELECT id, username, name, email, xp FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'user not found']);
    exit;
}

// Fetch streak data
$progress = fetch_progress($pdo, $user_id);

// Fetch achievements
$achievements = fetch_achievements($pdo, $user_id);

// Compute level
$details = level_details((int)$user['xp']);
$user['level'] = $details['level'];
$user['xp_in_level'] = $details['xp_in_level'];
$user['xp_cap'] = $details['xp_cap'];
$user['streak'] = (int)$progress['streak_count'];

$earnedLevel = sync_level_achievements($pdo, $user_id, $user['level']);
$achievements = fetch_achievements($pdo, $user_id);

// Fallback: if insert failed silently, still reflect by augmenting response
$codes = array_column($achievements, 'code');
if ($user['level'] >= 5 && !in_array('level_5', $codes, true)) {
    $achievements[] = ['code' => 'level_5', 'earned_at' => date('Y-m-d H:i:s')];
}
if ($user['level'] >= 10 && !in_array('level_10', $codes, true)) {
    $achievements[] = ['code' => 'level_10', 'earned_at' => date('Y-m-d H:i:s')];
}

$user['achievements'] = $achievements;
$user['achievement_catalog'] = achievement_catalog();

echo json_encode(['user' => $user]);
