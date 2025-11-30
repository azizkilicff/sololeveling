<?php
// public/api/groups.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_login();
$user_id = (int)($_SESSION['user_id'] ?? 0);

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? 'list');

try {

    // =====================================================================================
    // LIST GROUPS
    // =====================================================================================
    if ($action === 'list') {

        $mine = $pdo->prepare("
            SELECT g.id, g.name, g.owner_user_id, g.join_mode,
                   (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS members
            FROM `group_members` gm
            JOIN `groups` g ON g.id = gm.group_id
            WHERE gm.user_id = ?
        ");
        $mine->execute([$user_id]);
        $mineRows = $mine->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mineRows as &$g) {
            if ((int)$g['owner_user_id'] === $user_id && $g['join_mode'] === 'request') {
                $req = $pdo->prepare("
                    SELECT r.user_id, u.username, r.requested_at
                    FROM `group_join_requests` r
                    JOIN `users` u ON u.id = r.user_id
                    WHERE r.group_id = ?
                ");
                $req->execute([$g['id']]);
                $g['requests'] = $req->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $g['requests'] = [];
            }
            $g['requested_by_me'] = 0;
        }

        $all = $pdo->query("
            SELECT g.id, g.name, g.owner_user_id, g.join_mode,
                   (SELECT COUNT(*) FROM `group_members` gm2 WHERE gm2.group_id = g.id) AS members
            FROM `groups` g
            ORDER BY g.name ASC
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($all as &$g) {
            $stmt = $pdo->prepare("
                SELECT 1 FROM `group_join_requests`
                WHERE group_id = ? AND user_id = ?
            ");
            $stmt->execute([$g['id'], $user_id]);
            $g['requested_by_me'] = $stmt->fetch() ? 1 : 0;
            $g['requests'] = [];
        }

        json_ok(['mine' => $mineRows, 'all' => $all]);
        exit;
    }

    // =====================================================================================
    // CREATE GROUP
    // =====================================================================================
    if ($action === 'create') {
        $name = trim((string)$input['name'] ?? '');
        if ($name === "") json_error("Group name required", 400);

        $join_mode = ($input['join_mode'] ?? 'open') === 'request' ? 'request' : 'open';

        $stmt = $pdo->prepare("
            INSERT INTO `groups` (name, owner_user_id, join_mode)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $user_id, $join_mode]);
        $gid = (int)$pdo->lastInsertId();

        $pdo->prepare("
            INSERT IGNORE INTO `group_members` (group_id, user_id, role, joined_at)
            VALUES (?, ?, 'owner', NOW())
        ")->execute([$gid, $user_id]);

        json_ok(['ok' => true, 'group_id' => $gid]);
        exit;
    }

    // =====================================================================================
    // JOIN GROUP
    // =====================================================================================
    if ($action === 'join') {

        $gid = (int)($input['group_id'] ?? 0);

        if (!$gid) {
            $name = trim((string)$input['name'] ?? '');
            if ($name === '') json_error('group_id or name required', 400);

            $g = $pdo->prepare("SELECT id, join_mode FROM `groups` WHERE name = ?");
            $g->execute([$name]);
            $row = $g->fetch(PDO::FETCH_ASSOC);
            if (!$row) json_error("Group not found", 404);

            $gid = (int)$row['id'];
            $join_mode = $row['join_mode'];

        } else {
            $g = $pdo->prepare("SELECT join_mode FROM `groups` WHERE id = ?");
            $g->execute([$gid]);
            $join_mode = $g->fetchColumn();
        }

        if (!$join_mode) json_error("Group not found", 404);

        if ($join_mode === 'open') {
            $pdo->prepare("
                INSERT IGNORE INTO `group_members` (group_id, user_id, role, joined_at)
                VALUES (?, ?, 'member', NOW())
            ")->execute([$gid, $user_id]);

            json_ok(['ok' => true, 'joined' => true]);
            exit;
        }

        if ($join_mode === 'request') {
            $pdo->prepare("
                INSERT IGNORE INTO `group_join_requests` (group_id, user_id)
                VALUES (?, ?)
            ")->execute([$gid, $user_id]);

            json_ok(['ok' => true, 'requested' => true]);
            exit;
        }
    }

    // =====================================================================================
    // APPROVE REQUEST
    // =====================================================================================
    if ($action === 'approve_request') {
        $gid = (int)$input['group_id'];
        $uid = (int)$input['user_id'];

        $stmt = $pdo->prepare("SELECT owner_user_id FROM `groups` WHERE id=?");
        $stmt->execute([$gid]);
        if ((int)$stmt->fetchColumn() !== $user_id) json_error("Not owner", 403);

        $pdo->prepare("DELETE FROM `group_join_requests` WHERE group_id=? AND user_id=?")
            ->execute([$gid, $uid]);

        $pdo->prepare("
            INSERT IGNORE INTO `group_members` (group_id, user_id, role, joined_at)
            VALUES (?, ?, 'member', NOW())
        ")->execute([$gid, $uid]);

        json_ok(['ok'=>true]);
        exit;
    }

    // =====================================================================================
    // REJECT REQUEST
    // =====================================================================================
    if ($action === 'reject_request') {
        $gid = (int)$input['group_id'];
        $uid = (int)$input['user_id'];

        $stmt = $pdo->prepare("SELECT owner_user_id FROM `groups` WHERE id=?");
        $stmt->execute([$gid]);
        if ((int)$stmt->fetchColumn() !== $user_id) json_error("Not owner", 403);

        $pdo->prepare("DELETE FROM `group_join_requests` WHERE group_id=? AND user_id=?")
            ->execute([$gid, $uid]);

        json_ok(['ok'=>true]);
        exit;
    }

    // =====================================================================================
    // LEAVE GROUP
    // =====================================================================================
    if ($action === 'leave') {
        $gid = (int)$input['group_id'];

        $check = $pdo->prepare("SELECT 1 FROM `groups` WHERE id=? AND owner_user_id=?");
        $check->execute([$gid, $user_id]);
        if ($check->fetch()) json_error("Owner cannot leave their own group", 400);

        $pdo->prepare("DELETE FROM `group_members` WHERE group_id=? AND user_id=?")
            ->execute([$gid, $user_id]);

        json_ok(['ok'=>true]);
        exit;
    }

    // =====================================================================================
    // KICK MEMBER (owner only)
    // =====================================================================================
    if ($action === 'kick') {
        $gid = (int)$input['group_id'];
        $uid = (int)$input['user_id'];

        $stmt = $pdo->prepare("SELECT owner_user_id FROM `groups` WHERE id=?");
        $stmt->execute([$gid]);
        if ((int)$stmt->fetchColumn() !== $user_id)
            json_error("Only owner can kick members", 403);

        // Do not allow kicking the owner
        $pdo->prepare("DELETE FROM `group_members` WHERE group_id=? AND user_id=? AND user_id <> ?")
            ->execute([$gid, $uid, $user_id]);

        json_ok(['ok'=>true]);
        exit;
    }

    // =====================================================================================
    // LEADERBOARD
    // =====================================================================================
    if ($action === 'leaderboard') {
        $gid = (int)($_GET['group_id'] ?? $input['group_id'] ?? 0);

        // owner
        $ownerStmt = $pdo->prepare("SELECT owner_user_id FROM `groups` WHERE id=?");
        $ownerStmt->execute([$gid]);
        $ownerId = (int)$ownerStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT u.id AS user_id, u.username, u.xp
            FROM `group_members` gm
            JOIN `users` u ON u.id = gm.user_id
            WHERE gm.group_id = ?
            ORDER BY u.xp DESC
        ");
        $stmt->execute([$gid]);

        json_ok(['leaders'=>$stmt->fetchAll(), 'owner_user_id' => $ownerId]);
        exit;
    }

    // =====================================================================================
    // DELETE GROUP
    // =====================================================================================
    if ($action === 'delete') {
        $gid = (int)$input['group_id'];

        $stmt = $pdo->prepare("SELECT owner_user_id FROM `groups` WHERE id=?");
        $stmt->execute([$gid]);
        if ((int)$stmt->fetchColumn() !== $user_id)
            json_error("Only owner can delete group", 403);

        $pdo->prepare("DELETE FROM `groups` WHERE id=?")->execute([$gid]);

        json_ok(['ok'=>true]);
        exit;
    }

    json_error("Unknown action: $action", 400);

} catch (Throwable $e) {
    json_error("groups error: ".$e->getMessage(), 500);
}
