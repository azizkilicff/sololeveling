<?php
// public/api/search_templates.php
declare(strict_types=1);

header('Content-Type: application/json');

// Correct include paths:
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/db.php';

require_login();

$q = $_GET['q'] ?? '';
$q = trim($q);

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, description, default_reward_xp, default_penalty_xp
    FROM quest_templates
    WHERE title LIKE ?
    ORDER BY title ASC
    LIMIT 10
");
$stmt->execute(["%$q%"]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));