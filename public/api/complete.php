<?php
// public/api/complete.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();
ensure_progress_tables($pdo);

$user_id = (int)$_SESSION['user_id'];
$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$id      = (int)($input['id'] ?? 0);
$action  = strtolower((string)($input['action'] ?? 'complete'));

if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'missing id']); exit; }

try {
  // verify quest belongs to user and is pending
  $stmt = $pdo->prepare('SELECT id, user_id, status, reward_xp, penalty_xp FROM quests WHERE id = ? AND user_id = ?');
  $stmt->execute([$id, $user_id]);
  $q = $stmt->fetch();
  if (!$q) { http_response_code(404); echo json_encode(['error' => 'quest not found']); exit; }
  if ($q['status'] !== 'pending') { http_response_code(400); echo json_encode(['error' => 'already resolved']); exit; }

  if ($action === 'complete') {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE quests SET status='completed', completed_at=NOW() WHERE id=?")->execute([$id]);

    $gain = (int)$q['reward_xp'];
    $pdo->prepare('UPDATE users SET xp = xp + ? WHERE id = ?')->execute([$gain, $user_id]);

    // log event (quest_id + user_id exist now)
    $pdo->prepare('INSERT INTO quest_events (quest_id, user_id, event_type, delta_xp) VALUES (?,?,?,?)')
        ->execute([$id, $user_id, 'completed', $gain]);

    $xp = (int)$pdo->query("SELECT xp FROM users WHERE id = $user_id")->fetchColumn();
    $level = level_from_xp($xp);

    $streak = update_streak_on_completion($pdo, $user_id);

    $earned = [];
    if (award_achievement($pdo, $user_id, 'first_quest')) $earned[] = 'first_quest';
    if ($streak >= 3 && award_achievement($pdo, $user_id, 'streak_3')) $earned[] = 'streak_3';
    if ($streak >= 7 && award_achievement($pdo, $user_id, 'streak_7')) $earned[] = 'streak_7';
    if ($level >= 5 && award_achievement($pdo, $user_id, 'level_5')) $earned[] = 'level_5';
    if ($level >= 10 && award_achievement($pdo, $user_id, 'level_10')) $earned[] = 'level_10';

    if ($pdo->inTransaction()) { $pdo->commit(); }
    echo json_encode(['ok'=>true, 'xp_gained'=>$gain, 'xp'=>$xp, 'level'=>$level, 'streak'=>$streak, 'earned'=>$earned]);
    exit;
  }

  if ($action === 'fail') {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE quests SET status='failed' WHERE id=?")->execute([$id]);

    $loss = (int)$q['penalty_xp'];
    $pdo->prepare('UPDATE users SET xp = GREATEST(0, xp - ?) WHERE id = ?')->execute([$loss, $user_id]);

    $pdo->prepare('INSERT INTO quest_events (quest_id, user_id, event_type, delta_xp) VALUES (?,?,?,?)')
        ->execute([$id, $user_id, 'failed', -$loss]);

    $xp = (int)$pdo->query("SELECT xp FROM users WHERE id = $user_id")->fetchColumn();
    $level = level_from_xp($xp);
    reset_streak($pdo, $user_id);

    if ($pdo->inTransaction()) { $pdo->commit(); }
    echo json_encode(['ok'=>true, 'xp_lost'=>$loss, 'xp'=>$xp, 'level'=>$level]);
    exit;
  }

  http_response_code(400);
  echo json_encode(['error'=>'invalid action']);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['error'=>'complete error: '.$e->getMessage()]);
}
