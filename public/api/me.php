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
$user['level'] = level_from_xp((int)$user['xp']);
$user['streak'] = (int)$progress['streak_count'];
$user['achievements'] = $achievements;

echo json_encode(['user' => $user]);
