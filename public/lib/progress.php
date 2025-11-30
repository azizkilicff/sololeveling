<?php
declare(strict_types=1);

/**
 * FINAL progress.php — clean, correct, NO internal transactions,
 * NO DDL inside helper functions, safe to use inside complete.php transaction.
 */

function level_from_xp(int $xp): int {
    return max(1, (int)floor(sqrt(max(0, $xp) / 50)) + 1);
}

function ensure_progress_tables(PDO $pdo): void {
    // user_progress table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_progress (
            user_id INT PRIMARY KEY,
            streak_count INT NOT NULL DEFAULT 0,
            last_completed_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // user_achievements table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_achievements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(64) NOT NULL,
            earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_code (user_id, code),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

function fetch_progress(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT streak_count, last_completed_date
        FROM user_progress
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC)
        ?: ['streak_count'=>0, 'last_completed_date'=>null];
}

function update_streak_on_completion(PDO $pdo, int $userId): int {
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    // assumes outer transaction already exists
    $stmt = $pdo->prepare('SELECT streak_count, last_completed_date 
                           FROM user_progress 
                           WHERE user_id = ? 
                           FOR UPDATE');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if ($row) {
        $last = $row['last_completed_date'];
        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

        if ($last === $today) {
            return (int)$row['streak_count']; // already counted today
        }

        $newStreak = ($last === $yesterday)
            ? ((int)$row['streak_count'] + 1)
            : 1;

        $pdo->prepare('UPDATE user_progress SET streak_count=?, last_completed_date=? WHERE user_id=?')
            ->execute([$newStreak, $today, $userId]);

        return $newStreak;
    }

    // no row → insert fresh streak
    $pdo->prepare('INSERT INTO user_progress (user_id, streak_count, last_completed_date)
                   VALUES (?, 1, ?)')
        ->execute([$userId, $today]);

    return 1;
}

function reset_streak(PDO $pdo, int $userId): void {
    $pdo->prepare('INSERT INTO user_progress (user_id, streak_count, last_completed_date)
                   VALUES (?, 0, NULL)
                   ON DUPLICATE KEY UPDATE streak_count=0, last_completed_date=NULL')
        ->execute([$userId]);
}

function award_achievement(PDO $pdo, int $userId, string $code): bool {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO user_achievements (user_id, code)
        VALUES (?, ?)
    ");
    $stmt->execute([$userId, $code]);
    return $stmt->rowCount() > 0; // true if newly earned
}

function fetch_achievements(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT code, earned_at 
        FROM user_achievements 
        WHERE user_id = ?
        ORDER BY earned_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}