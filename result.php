<?php
require __DIR__ . '/includes/auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['case_id'] ?? 0);
$case = get_case($conn, $id);
if (!$case) { flash('Case not found.', 'error'); redirect('dashboard.php'); }

$acc = user_accusation($conn, $uid, $id);
if (!$acc) { flash('Make your final accusation to see the result.', 'info'); redirect('case.php?id=' . $id); }

$stmt = $conn->prepare("SELECT id, name FROM suspects WHERE id IN (?, ?)");
$culprit = (int)$case['culprit_id']; $accused = (int)$acc['suspect_id'];
$stmt->bind_param('ii', $culprit, $accused);
$stmt->execute();
$names = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $names[(int)$r['id']] = $r['name'];

// Score breakdown (bill-style table, Task 5)
$stmt = $conn->prepare("SELECT ev.title, ev.cost FROM evidence_unlocked eu JOIN evidence ev ON ev.id = eu.evidence_id
                        WHERE eu.user_id = ? AND ev.case_id = ? AND ev.cost > 0 ORDER BY eu.unlocked_at");
$stmt->bind_param('ii', $uid, $id);
$stmt->execute();
$paid = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$win = (bool)$acc['is_correct'];
$raw = (int)$case['base_score'] - (int)$acc['points_spent'];

$pageTitle = 'Result';
include __DIR__ . '/includes/header.php';
?>
<div class="card verdict <?= $win ? 'win' : 'lose' ?>">
  <?php if ($win): ?>
    <h1>Case solved!</h1>
    <p>You correctly identified <strong><?= e($names[$culprit] ?? 'the culprit') ?></strong>.</p>
  <?php else: ?>
    <h1>Wrong accusation</h1>
    <p>You accused <strong><?= e($names[$accused] ?? 'a suspect') ?></strong>, but the culprit was <strong><?= e($names[$culprit] ?? 'unknown') ?></strong>.</p>
  <?php endif; ?>
  <div class="big-score"><?= (int)$acc['score'] ?> pts</div>
</div>

<div class="card">
  <h2>What really happened</h2>
  <p class="pre"><?= e($case['solution']) ?></p>
  <h3>Your reasoning</h3>
  <p class="pre muted"><?= e($acc['reasoning']) ?></p>
  <a class="btn ghost small" href="export_case.php?id=<?= $id ?>">Download case file (XML)</a>
</div>

<div class="card">
  <h2>Score breakdown</h2>
  <table>
    <tr><th>Item</th><th class="num">Points</th></tr>
    <tr><td>Base score</td><td class="num"><?= (int)$case['base_score'] ?></td></tr>
    <?php foreach ($paid as $p): ?>
      <tr><td>Clue revealed: <?= e($p['title']) ?></td><td class="num">-<?= (int)$p['cost'] ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$paid): ?><tr><td class="muted">No paid clues used</td><td class="num">0</td></tr><?php endif; ?>
    <tr><td><strong>Subtotal (<?= (int)$acc['clues_used'] ?> paid clue<?= (int)$acc['clues_used'] === 1 ? '' : 's' ?>)</strong></td><td class="num"><strong><?= $raw ?></strong></td></tr>
    <?php if ($win && $raw < MIN_CORRECT_SCORE): ?>
      <tr><td>Minimum score for a correct answer applied</td><td class="num"><?= MIN_CORRECT_SCORE ?></td></tr>
    <?php endif; ?>
    <?php if (!$win): ?><tr><td>Wrong accusation: score set to zero</td><td class="num">0</td></tr><?php endif; ?>
    <tr><td><strong>Final score</strong></td><td class="num"><strong><?= (int)$acc['score'] ?></strong></td></tr>
  </table>
</div>
<p><a class="btn" href="dashboard.php">Back to cases</a> <a class="btn ghost" href="leaderboard.php">Leaderboard</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>
