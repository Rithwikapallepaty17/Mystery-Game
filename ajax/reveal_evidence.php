<?php
require __DIR__ . '/../includes/auth.php';
require_login(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'POST required.'], 405);

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);

$stmt = $conn->prepare("SELECT ev.id, ev.case_id, ev.title, ev.description, ev.found_at, ev.cost, c.base_score, c.is_published
                        FROM evidence ev JOIN cases c ON c.id = ev.case_id WHERE ev.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$ev = $stmt->get_result()->fetch_assoc();

if (!$ev || (!$ev['is_published'] && !is_admin())) json_out(['error' => 'Evidence not found.'], 404);

if (!is_admin()) {
    if (user_accusation($conn, $uid, (int)$ev['case_id'])) {
        json_out(['error' => 'This case is closed. No more clues can be revealed.'], 409);
    }
    if ((int)$ev['cost'] > 0) {
        // INSERT IGNORE: revealing the same clue twice never charges twice
        $ins = $conn->prepare("INSERT IGNORE INTO evidence_unlocked (user_id, evidence_id) VALUES (?, ?)");
        $ins->bind_param('ii', $uid, $id);
        $ins->execute();
    }
}

$t = player_totals($conn, $uid, (int)$ev['case_id']);
json_out([
    'id'              => (int)$ev['id'],
    'title'           => $ev['title'],
    'description'     => $ev['description'],
    'found_at'        => $ev['found_at'],
    'cost'            => (int)$ev['cost'],
    'clues_used'      => $t['clues'],
    'points_spent'    => $t['spent'],
    'potential_score' => calc_score($ev['base_score'], $t['spent']),
]);
