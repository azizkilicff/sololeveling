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

    if ($title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Title required']);
        exit;
    }

    // --- 1) Check if template already exists ---
    $stmt = $pdo->prepare("SELECT * FROM quest_templates WHERE title = ?");
    $stmt->execute([$title]);
    $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- 2) If template missing → auto-create it ---
    if (!$tmpl) {
        $insert = $pdo->prepare("
            INSERT INTO quest_templates (title, description, default_reward_xp, default_penalty_xp)
            VALUES (?, ?, 10, 5)
        ");
        $insert->execute([$title, $desc]);
        $template_id = (int)$pdo->lastInsertId();
    } else {
        $template_id = (int)$tmpl['id'];
    }

    // --- 3) Load template XP for quest creation ---
    $stmt2 = $pdo->prepare("SELECT * FROM quest_templates WHERE id = ?");
    $stmt2->execute([$template_id]);
    $t = $stmt2->fetch(PDO::FETCH_ASSOC);

    $reward = (int)$t['default_reward_xp'];
    $penalty = (int)$t['default_penalty_xp'];

    // Server override: if difficulty is selected manually
    $rewardMap  = ['easy'=>10, 'medium'=>20, 'hard'=>35, 'epic'=>60];
    if (isset($rewardMap[$difficulty])) {
        $reward  = $rewardMap[$difficulty];
        $penalty = (int)round($reward * 0.5);
    }

    // --- 4) Insert quest tied to template ---
    $stmt3 = $pdo->prepare("
        INSERT INTO quests (user_id, template_id, title, description, due_date, reward_xp, penalty_xp, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt3->execute([
        $user_id,
        $template_id,
        $title,
        $desc,
        $due,
        $reward,
        $penalty
    ]);

    echo json_encode(['ok' => true]);
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