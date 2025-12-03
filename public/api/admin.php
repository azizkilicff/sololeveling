<?php
// public/api/admin.php
declare(strict_types=1);

header('Content-Type: application/json');

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/auth.php';
require_login();

$userId = (int)($_SESSION['user_id'] ?? 0);

// Verify admin role
$roleStmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$roleStmt->execute([$userId]);
$role = $roleStmt->fetchColumn();
if ($role !== 'admin') {
    json_error("Admin access required", 403);
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? ($input['action'] ?? '');

try {
    if ($action === 'users') {
        $stmt = $pdo->query("SELECT id, username, email, role FROM users ORDER BY id DESC");
        json_ok($stmt->fetchAll());
    }

    if ($action === 'groups') {
        $stmt = $pdo->query("
            SELECT g.id, g.name, g.owner_user_id,
                   (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members
            FROM `groups` g
            ORDER BY g.id DESC
        ");
        json_ok($stmt->fetchAll());
    }

    if ($action === 'delete_user') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) json_error("Missing id", 400);

        // prevent self-delete
        if ($id === $userId) json_error("Cannot delete current admin", 400);

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM group_members WHERE user_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM group_join_requests WHERE user_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        $pdo->commit();
        json_ok(['ok' => true, 'deleted_user_id' => $id]);
    }

    if ($action === 'delete_group') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) json_error("Missing id", 400);

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM group_members WHERE group_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM group_join_requests WHERE group_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM `groups` WHERE id=?")->execute([$id]);
        $pdo->commit();
        json_ok(['ok' => true, 'deleted_group_id' => $id]);
    }

    json_error("Invalid action", 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error("admin error: ".$e->getMessage(), 500);
}
