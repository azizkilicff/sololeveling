<?php
// public/api/quests.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];


// =====================================================================================
// FUNCTION: Auto regenerate daily/weekly quests when user opens quest list
// =====================================================================================
function regenerate_repeatable_quests(PDO $pdo, int $user_id)
{
    // -------- DAILY quests --------
    $daily = $pdo->prepare("
        SELECT id FROM quests
        WHERE user_id = ?
          AND repeat_mode = 'daily'
          AND (last_generated IS NULL OR last_generated < CURDATE())
    ");
    $daily->execute([$user_id]);

    foreach ($daily->fetchAll() as $q) {
        $pdo->prepare("
            UPDATE quests
            SET status='pending',
                last_generated=CURDATE()
            WHERE id=?
        ")->execute([$q['id']]);
    }

    // -------- WEEKLY quests --------
    // reset every Monday (WEEKDAY() == 0 means Monday)
    $weekly = $pdo->prepare("
        SELECT id FROM quests
        WHERE user_id = ?
          AND repeat_mode = 'weekly'
          AND (
                last_generated IS NULL OR 
                last_generated < DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
              )
    ");
    $weekly->execute([$user_id]);

    foreach ($weekly->fetchAll() as $q) {
        $pdo->prepare("
            UPDATE quests
            SET status='pending',
                last_generated=CURDATE()
            WHERE id=?
        ")->execute([$q['id']]);
    }
}


try {

    // =====================================================================================
    // GET — list quests (with auto-reset daily/weekly)
    // =====================================================================================
    if ($method === 'GET') {

        regenerate_repeatable_quests($pdo, $user_id);

        $stmt = $pdo->prepare('
            SELECT * FROM quests
            WHERE user_id = ?
            ORDER BY 
              CASE 
                WHEN status = "pending" THEN 0
                ELSE 1
              END,
              CASE 
                WHEN status = "pending" THEN due_date 
              END ASC,
              CASE 
                WHEN status <> "pending" THEN IFNULL(completed_at, created_at)
              END DESC,
              created_at DESC
        ');
        $stmt->execute([$user_id]);

        echo json_encode(['quests'=>$stmt->fetchAll()]);
        exit;
    }


    // Decode JSON for POST/PUT
    $input = json_decode(file_get_contents('php://input'), true) ?? [];


    // =====================================================================================
    // POST — create quest (supports repeat_mode)
    // =====================================================================================
    if ($method === 'POST') {

        $title = trim((string)$input['title'] ?? '');
        $desc  = trim((string)$input['description'] ?? '');
        $due   = (string)($input['due_date'] ?? date('Y-m-d'));
        $difficulty = strtolower(trim((string)($input['difficulty'] ?? 'medium')));

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Title required']);
            exit;
        }

        // NEW: repeat mode (none/daily/weekly)
        $repeat_mode = strtolower(trim((string)($input['repeat_mode'] ?? 'none')));
        if (!in_array($repeat_mode, ['none','daily','weekly'])) {
            $repeat_mode = 'none';
        }

        // ---- Check if template exists ----
        $stmt = $pdo->prepare("SELECT * FROM quest_templates WHERE title = ?");
        $stmt->execute([$title]);
        $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);

        // ---- Create template if missing ----
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

        // ---- Load XP from template ----
        $stmt2 = $pdo->prepare("SELECT * FROM quest_templates WHERE id = ?");
        $stmt2->execute([$template_id]);
        $t = $stmt2->fetch(PDO::FETCH_ASSOC);

        $reward  = (int)$t['default_reward_xp'];
        $penalty = (int)$t['default_penalty_xp'];

        // ---- Override XP if difficulty chosen ----
        $rewardMap = ['easy'=>10, 'medium'=>20, 'hard'=>35, 'epic'=>60];
        if (isset($rewardMap[$difficulty])) {
            $reward  = $rewardMap[$difficulty];
            $penalty = (int)round($reward * 0.5);
        }

        // ---- Insert quest (now includes repeat_mode + last_generated) ----
        $stmt3 = $pdo->prepare("
            INSERT INTO quests 
              (user_id, template_id, title, description, due_date, reward_xp, penalty_xp, status, repeat_mode, last_generated)
            VALUES
              (?, ?, ?, ?, ?, ?, ?, 'pending', ?, CURDATE())
        ");
        $stmt3->execute([
            $user_id,
            $template_id,
            $title,
            $desc,
            $due,
            $reward,
            $penalty,
            $repeat_mode
        ]);

        echo json_encode(['ok' => true]);
        exit;
    }


    // =====================================================================================
    // PUT — update quest
    // =====================================================================================
    if ($method === 'PUT') {

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error'=>'Missing id']);
            exit;
        }

        $title = trim((string)($input['title'] ?? ''));
        $desc  = trim((string)($input['description'] ?? ''));
        $due   = (string)($input['due_date'] ?? date('Y-m-d'));
        $difficulty = strtolower(trim((string)($input['difficulty'] ?? '')));
        $repeat_mode = strtolower(trim((string)($input['repeat_mode'] ?? 'none')));
        if (!in_array($repeat_mode, ['none','daily','weekly'])) {
            $repeat_mode = 'none';
        }

        // Update with difficulty (XP recalculation)
        if ($difficulty !== '') {
            $rewardMap  = ['easy'=>10, 'medium'=>20, 'hard'=>35, 'epic'=>60];
            $reward     = $rewardMap[$difficulty] ?? 20;
            $penalty    = (int)round($reward * 0.5);

            $stmt = $pdo->prepare('
                UPDATE quests 
                SET title=?, description=?, due_date=?, reward_xp=?, penalty_xp=?, repeat_mode=? 
                WHERE id=? AND user_id=?
            ');
            $stmt->execute([$title, $desc, $due, $reward, $penalty, $repeat_mode, $id, $user_id]);

        } else {
            $stmt = $pdo->prepare('
                UPDATE quests 
                SET title=?, description=?, due_date=?, repeat_mode=? 
                WHERE id=? AND user_id=?
            ');
            $stmt->execute([$title, $desc, $due, $repeat_mode, $id, $user_id]);
        }

        echo json_encode(['ok'=>true]);
        exit;
    }


    // =====================================================================================
    // DELETE — delete quest
    // =====================================================================================
    if ($method === 'DELETE') {
        parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
        $id = (int)($qs['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error'=>'Missing id']);
            exit;
        }

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
