<?php
// public/api/groups.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';  // $pdo + session + json_ok/json_error
require __DIR__ . '/../lib/auth.php';   // require_login()

// show errors while developing
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_login();
$user_id = (int)($_SESSION['user_id'] ?? 0);

// read action (GET or JSON body)
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

try {
  switch ($action) {

    case 'list': {
        // groups I’m in
        $mine = $pdo->prepare(
          "SELECT g.id, g.name, g.owner_user_id,
                  (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS members
           FROM `group_members` gm
           JOIN `groups` g ON g.id = gm.group_id
           WHERE gm.user_id = ?"
        );
        $mine->execute([$user_id]);
        $mineRows = $mine->fetchAll();
      
        // all/browse (include owner_user_id for completeness)
        $all = $pdo->query(
          "SELECT g.id, g.name, g.owner_user_id,
                  (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS members
           FROM `groups` g
           ORDER BY g.name ASC
           LIMIT 50"
        )->fetchAll();
      
        json_ok(['mine' => $mineRows, 'all' => $all]);
        break;
      }

    case 'create': {
      $name = trim((string)($input['name'] ?? ''));
      if ($name === '') json_error('group name required', 400);

      // unique group name (assumes UNIQUE KEY on groups.name)
      $ins = $pdo->prepare("INSERT INTO `groups` (name, owner_user_id) VALUES (?, ?)");
      try {
        $ins->execute([$name, $user_id]);
      } catch (PDOException $e) {
        // 23000 = integrity constraint violation (duplicate key)
        if ($e->getCode() === '23000') {
          json_error('group name already exists', 409);
        }
        throw $e;
      }
      $group_id = (int)$pdo->lastInsertId();

      // also add owner as member
      $mem = $pdo->prepare(
        "INSERT IGNORE INTO `group_members` (group_id, user_id, role, joined_at)
         VALUES (?, ?, 'owner', NOW())"
      );
      $mem->execute([$group_id, $user_id]);

      json_ok(['ok' => true, 'group_id' => $group_id, 'name' => $name]);
      break;
    }

    case 'join': {
      // join by id or by name
      $group_id = isset($input['group_id']) ? (int)$input['group_id'] : 0;
      if (!$group_id) {
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') json_error('group_id or name required', 400);
        $g = $pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
        $g->execute([$name]);
        $row = $g->fetch();
        if (!$row) json_error('group not found', 404);
        $group_id = (int)$row['id'];
      }

      $join = $pdo->prepare(
        "INSERT IGNORE INTO `group_members` (group_id, user_id, role, joined_at)
         VALUES (?, ?, 'member', NOW())"
      );
      $join->execute([$group_id, $user_id]);

      json_ok(['ok' => true, 'group_id' => $group_id]);
      break;
    }

    case 'leave': {
      $group_id = (int)($input['group_id'] ?? 0);
      if ($group_id <= 0) json_error('group_id required', 400);

      // owner cannot leave without transferring ownership
      $isOwner = $pdo->prepare("SELECT 1 FROM `groups` WHERE id = ? AND owner_user_id = ?");
      $isOwner->execute([$group_id, $user_id]);
      if ($isOwner->fetch()) {
        json_error('owner cannot leave; transfer ownership first', 400);
      }

      $del = $pdo->prepare("DELETE FROM `group_members` WHERE group_id = ? AND user_id = ?");
      $del->execute([$group_id, $user_id]);

      json_ok(['ok' => true]);
      break;
    }

    case 'leaderboard': {
      $group_id = (int)($_GET['group_id'] ?? $input['group_id'] ?? 0);
      if ($group_id <= 0) json_error('group_id required', 400);

      $lb = $pdo->prepare(
        "SELECT u.id AS user_id, u.username, u.xp
         FROM `group_members` gm
         JOIN `users` u ON u.id = gm.user_id
         WHERE gm.group_id = ?
         ORDER BY u.xp DESC
         LIMIT 100"
      );
      $lb->execute([$group_id]);

      json_ok(['group_id' => $group_id, 'leaders' => $lb->fetchAll()]);
      break;
    }

    case 'delete': {
      $group_id = (int)($input['group_id'] ?? 0);
      if ($group_id <= 0) json_error('group_id required', 400);

      // verify ownership
      $stmt = $pdo->prepare('SELECT owner_user_id FROM `groups` WHERE id = ?');
      $stmt->execute([$group_id]);
      $row = $stmt->fetch();
      if (!$row) json_error('group not found', 404);
      if ((int)$row['owner_user_id'] !== $user_id) {
        json_error('only the group owner can delete this group', 403);
      }

      // delete; membership rows will cascade
      $pdo->prepare('DELETE FROM `groups` WHERE id = ?')->execute([$group_id]);
      json_ok(['ok' => true, 'deleted_group_id' => $group_id]);
      break;
    }

    default:
      json_error('Unknown action', 400);
  }

} catch (Throwable $e) {
  json_error('groups error: '.$e->getMessage(), 500);
}