<?php
require __DIR__ . '/includes/auth.php';
require_login();
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT c.id, c.title, c.victim, c.difficulty, c.base_score,
                               a.id AS acc_id, a.is_correct, a.score,
                               (SELECT COUNT(*) FROM evidence_unlocked eu JOIN evidence ev ON ev.id = eu.evidence_id
                                 WHERE eu.user_id = ? AND ev.case_id = c.id) AS unlocked_count
                        FROM cases c
                        LEFT JOIN accusations a ON a.case_id = c.id AND a.user_id = ?
                        WHERE c.is_published = 1 ORDER BY c.id");
$stmt->bind_param('ii', $uid, $uid);
$stmt->execute();
$cases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cookie (Task 6b): the case this detective opened last
$lastCase = null;
$lastId = (int)($_COOKIE['last_case'] ?? 0);
foreach ($cases as $c) { if ((int)$c['id'] === $lastId && !$c['acc_id']) $lastCase = $c; }

$pageTitle = 'Cases';
include __DIR__ . '/includes/header.php';
?>
<h1>Welcome, Detective <?= e($_SESSION['full_name']) ?></h1>
<p class="muted">Study the case file, question the suspects, buy extra clues if you must, then make your one and only accusation.
Fewer clues means a higher score.</p>

<?php if ($lastCase): ?>
  <div class="alert info">You were last investigating <strong><?= e($lastCase['title']) ?></strong>.
    <a href="case.php?id=<?= (int)$lastCase['id'] ?>">Continue the investigation &rarr;</a></div>
<?php endif; ?>

<?php if (!$cases): ?>
  <div class="card">No cases are open right now.<?= is_admin() ? ' <a href="admin/case_edit.php">Create one</a>.' : '' ?></div>
<?php else: ?>
<div class="grid">
  <?php foreach ($cases as $c): ?>
    <div class="card case-card">
      <div>
        <span class="badge <?= e($c['difficulty']) ?>"><?= e($c['difficulty']) ?></span>
        <h2 style="margin-top:8px"><?= e($c['title']) ?></h2>
        <p class="muted">Victim: <?= e($c['victim']) ?></p>
        <?php if ($c['acc_id']): ?>
          <?php if ($c['is_correct']): ?>
            <div class="status solved">&#10004; Solved &middot; Score <?= (int)$c['score'] ?></div>
          <?php else: ?>
            <div class="status failed">&#10008; Wrong accusation &middot; Case closed</div>
          <?php endif; ?>
        <?php elseif ((int)$c['unlocked_count'] > 0): ?>
          <div class="status progress">&#9203; Investigation in progress</div>
        <?php else: ?>
          <div class="status">New case &middot; up to <?= (int)$c['base_score'] ?> points</div>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($c['acc_id']): ?>
          <a class="btn ghost" href="result.php?case_id=<?= (int)$c['id'] ?>">View result</a>
        <?php else: ?>
          <a class="btn" href="case.php?id=<?= (int)$c['id'] ?>">Open case file</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
