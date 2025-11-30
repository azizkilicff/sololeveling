<?php
// public/api/complete.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();

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

    $pdo->commit();
    echo json_encode(['ok'=>true, 'xp_gained'=>$gain, 'xp'=>$xp, 'level'=>$level]);
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

    $pdo->commit();
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