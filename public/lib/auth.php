<?php
// No session_start() here — db.php already started and configured sessions.

function read_json() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_login() {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
  }
}

/* If you still need a “display level” from XP in some responses */
function level_from_xp($xp) {
  return max(1, (int)floor(sqrt(max(0, (int)$xp) / 50)) + 1);
}

// Ensure streak + achievement tables exist (safe to call repeatedly)
function ensure_progress_tables(PDO $pdo): void {
  $pdo->exec('CREATE TABLE IF NOT EXISTS user_progress (
    user_id INT PRIMARY KEY,
    streak_count INT NOT NULL DEFAULT 0,
    last_completed_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

  $pdo->exec('CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code VARCHAR(64) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_code (user_id, code),
    INDEX idx_user (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');

  // Safety: backfill missing columns for older MySQL versions (no IF NOT EXISTS support)
  ensure_column($pdo, 'user_achievements', 'code', "VARCHAR(64) NOT NULL");
  ensure_column($pdo, 'user_achievements', 'earned_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
  ensure_column($pdo, 'user_achievements', 'user_id', "INT NOT NULL");
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
  try {
    $stmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    $exists = (bool)$stmt->fetchColumn();
    if (!$exists) {
      $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
  } catch (Throwable $e) {
    // If information_schema is blocked, try blind alter; ignore duplicate column errors
    try { $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}"); } catch (Throwable $ignored) {}
  }
}

function update_streak_on_completion(PDO $pdo, int $userId): int {
  if (!$pdo->inTransaction()) { ensure_progress_tables($pdo); }
  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  $startedTxn = false;
  if (!$pdo->inTransaction()) { $pdo->beginTransaction(); $startedTxn = true; }
  $stmt = $pdo->prepare('SELECT streak_count, last_completed_date FROM user_progress WHERE user_id = ? FOR UPDATE');
  $stmt->execute([$userId]);
  $row = $stmt->fetch();

  $streak = 1;
  if ($row) {
    $last = $row['last_completed_date'];
    if ($last === $today) {
      $streak = (int)$row['streak_count']; // already counted today
    } else {
      $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');
      $streak = ($last === $yesterday) ? ((int)$row['streak_count'] + 1) : 1;
    }
    $pdo->prepare('UPDATE user_progress SET streak_count=?, last_completed_date=? WHERE user_id=?')
        ->execute([$streak, $today, $userId]);
  } else {
    $pdo->prepare('INSERT INTO user_progress (user_id, streak_count, last_completed_date) VALUES (?,?,?)')
        ->execute([$userId, $streak, $today]);
  }
  if ($startedTxn && $pdo->inTransaction()) { $pdo->commit(); }
  return $streak;
}

function reset_streak(PDO $pdo, int $userId): void {
  if (!$pdo->inTransaction()) { ensure_progress_tables($pdo); }
  $startedTxn = false;
  if (!$pdo->inTransaction()) { $pdo->beginTransaction(); $startedTxn = true; }
  $pdo->prepare('INSERT INTO user_progress (user_id, streak_count, last_completed_date)
                 VALUES (?, 0, NULL)
                 ON DUPLICATE KEY UPDATE streak_count=0, last_completed_date=NULL')->execute([$userId]);
  if ($startedTxn && $pdo->inTransaction()) { $pdo->commit(); }
}

function fetch_progress(PDO $pdo, int $userId): array {
  if (!$pdo->inTransaction()) { ensure_progress_tables($pdo); }
  $stmt = $pdo->prepare('SELECT streak_count, last_completed_date FROM user_progress WHERE user_id = ?');
  $stmt->execute([$userId]);
  return $stmt->fetch() ?: ['streak_count' => 0, 'last_completed_date' => null];
}

function award_achievement(PDO $pdo, int $userId, string $code): bool {
  if (!$pdo->inTransaction()) { ensure_progress_tables($pdo); }
  $stmt = $pdo->prepare('INSERT IGNORE INTO user_achievements (user_id, code) VALUES (?, ?)');
  $stmt->execute([$userId, $code]);
  return $stmt->rowCount() > 0;
}

function fetch_achievements(PDO $pdo, int $userId): array {
  if (!$pdo->inTransaction()) { ensure_progress_tables($pdo); }
  $stmt = $pdo->prepare('SELECT code, earned_at FROM user_achievements WHERE user_id = ? ORDER BY earned_at DESC');
  $stmt->execute([$userId]);
  return $stmt->fetchAll();
}
