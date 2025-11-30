<?php
// No session_start() here — db.php already started and configured sessions.

function read_json() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function require_login() {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
  }
}

/* If you still need a “display level” from XP in some responses */
function level_from_xp($xp) {
  return max(1, (int)floor(sqrt(max(0, (int)$xp) / 50)) + 1);
}