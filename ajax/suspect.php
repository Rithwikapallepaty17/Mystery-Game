<?php
require __DIR__ . '/../includes/auth.php';
require_login(true);

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT s.name, s.occupation, s.statement, s.alibi, s.motive, c.is_published
                        FROM suspects s JOIN cases c ON c.id = s.case_id WHERE s.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();

if (!$s || (!$s['is_published'] && !is_admin())) json_out(['error' => 'Suspect not found.'], 404);

// Note: the culprit is never included in this response.
json_out([
    'name'       => $s['name'],
    'occupation' => $s['occupation'],
    'statement'  => $s['statement'],
    'alibi'      => $s['alibi'],
    'motive'     => $s['motive'],
]);
