<?php
// api/complete.php
declare(strict_types=1);

header("Content-Type: application/json");

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/progress.php';
require_login();
ensure_progress_tables($pdo);

$user_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true) ?? [];
$id     = (int)($input['id'] ?? 0);
$action = ($input['action'] ?? 'complete');

if ($id <= 0) {
    json_error("Missing quest ID", 400);
}

try {
    $pdo->beginTransaction();

    // fetch quest
    $stmt = $pdo->prepare("SELECT * FROM quests WHERE id=? AND user_id=? AND status='pending' FOR UPDATE");
    $stmt->execute([$id, $user_id]);
    $q = $stmt->fetch();

    if (!$q) {
        $pdo->rollBack();
        json_error("Quest not found or already resolved", 404);
    }

    $reward = (int)$q['reward_xp'];
    $penalty = (int)$q['penalty_xp'];

    if ($action === "complete") {
        $pdo->prepare("UPDATE quests SET status='completed', completed_at=NOW() WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE users SET xp = xp + ? WHERE id=?")->execute([$reward, $user_id]);

        $xp      = (int)$pdo->query("SELECT xp FROM users WHERE id=$user_id")->fetchColumn();
        $details = level_details($xp);
        $level   = $details['level'];
        $streak  = update_streak_on_completion($pdo, $user_id);

        $earned = [];
        if (award_achievement($pdo, $user_id, 'first_quest')) $earned[] = 'first_quest';
        if ($streak >= 3  && award_achievement($pdo, $user_id, 'streak_3')) $earned[] = 'streak_3';
        if ($streak >= 7  && award_achievement($pdo, $user_id, 'streak_7')) $earned[] = 'streak_7';
        $levelEarned = sync_level_achievements($pdo, $user_id, $level);
        $earned = array_merge($earned, $levelEarned);

        $pdo->commit();

        json_ok([
            "ok"          => true,
            "xp_change"   => $reward,
            "xp"          => $xp,
            "level"       => $level,
            "xp_in_level" => $details['xp_in_level'],
            "xp_cap"      => $details['xp_cap'],
            "streak"      => $streak,
            "earned"      => $earned
        ]);
        exit;
    }

    if ($action === "fail") {
        $pdo->prepare("UPDATE quests SET status='failed', completed_at=NOW() WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE users SET xp = GREATEST(xp - ?,0) WHERE id=?")->execute([$penalty, $user_id]);

        $xp      = (int)$pdo->query("SELECT xp FROM users WHERE id=$user_id")->fetchColumn();
        $details = level_details($xp);
        $level   = $details['level'];
        reset_streak($pdo, $user_id);

        $pdo->commit();

        json_ok([
            "ok"          => true,
            "xp_change"   => -$penalty,
            "xp"          => $xp,
            "level"       => $level,
            "xp_in_level" => $details['xp_in_level'],
            "xp_cap"      => $details['xp_cap']
        ]);
        exit;
    }

    $pdo->rollBack();
    json_error("Invalid action", 400);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error("complete error: ".$e->getMessage(), 500);
}
