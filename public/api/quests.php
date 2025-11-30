<?php
// public/api/quests.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM quests WHERE user_id = ? ORDER BY status ASC, due_date ASC, created_at DESC');
    $stmt->execute([$user_id]);
    echo json_encode(['quests'=>$stmt->fetchAll()]);
    exit;
  }

  $input = json_decode(file_get_contents('php://input'), true) ?? [];

  if ($method === 'POST') {
    $title = trim((string)($input['title'] ?? ''));
    $desc  = trim((string)($input['description'] ?? ''));
    $due   = (string)($input['due_date'] ?? date('Y-m-d'));
    $difficulty = strtolower(trim((string)($input['difficulty'] ?? 'medium')));

    if ($title === '') { http_response_code(400); echo json_encode(['error'=>'Title required']); exit; }

    // Server-side difficulty mapping (authoritative)
    $rewardMap  = ['easy'=>10, 'medium'=>20, 'hard'=>35, 'epic'=>60];
    $reward     = $rewardMap[$difficulty] ?? 20;
    $penalty    = (int)round($reward * 0.5);

    $stmt = $pdo->prepare('INSERT INTO quests (user_id, title, description, due_date, reward_xp, penalty_xp, status) VALUES (?,?,?,?,?,?, "pending")');
    $stmt->execute([$user_id, $title, $desc, $due, $reward, $penalty]);
    echo json_encode(['ok'=>true]);
    exit;
  }

  if ($method === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

    $title = trim((string)($input['title'] ?? ''));
    $desc  = trim((string)($input['description'] ?? ''));
    $due   = (string)($input['due_date'] ?? date('Y-m-d'));
    $difficulty = strtolower(trim((string)($input['difficulty'] ?? '')));

    if ($difficulty !== '') {
      $rewardMap  = ['easy'=>10, 'medium'=>20, 'hard'=>35, 'epic'=>60];
      $reward     = $rewardMap[$difficulty] ?? 20;
      $penalty    = (int)round($reward * 0.5);

      $stmt = $pdo->prepare('UPDATE quests SET title=?, description=?, due_date=?, reward_xp=?, penalty_xp=? WHERE id=? AND user_id=?');
      $stmt->execute([$title, $desc, $due, $reward, $penalty, $id, $user_id]);
    } else {
      $stmt = $pdo->prepare('UPDATE quests SET title=?, description=?, due_date=? WHERE id=? AND user_id=?');
      $stmt->execute([$title, $desc, $due, $id, $user_id]);
    }

    echo json_encode(['ok'=>true]);
    exit;
  }

  if ($method === 'DELETE') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
    $id = (int)($qs['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

    $stmt = $pdo->prepare('DELETE FROM quests WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user_id]);
    echo json_encode(['ok'=>true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error'=>'method not allowed']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'quests error: '.$e->getMessage()]);
}