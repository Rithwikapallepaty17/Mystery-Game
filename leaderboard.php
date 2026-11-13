<?php
require __DIR__ . '/includes/auth.php';
require_login();

$rows = $conn->query("SELECT u.id, u.full_name, u.username,
                             COUNT(*) AS played, SUM(a.is_correct) AS solved,
                             SUM(a.score) AS total_score, SUM(a.clues_used) AS clues
                      FROM accusations a JOIN users u ON u.id = a.user_id
                      WHERE u.role = 'player'
                      GROUP BY u.id, u.full_name, u.username
                      ORDER BY total_score DESC, solved DESC, clues ASC
                      LIMIT 20")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Leaderboard';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Top Detectives</h2>
  <?php if (!$rows): ?><p class="muted">No cases have been closed yet. Be the first!</p><?php else: ?>
  <table>
    <tr><th class="num">Rank</th><th>Detective</th><th class="num">Cases closed</th><th class="num">Solved</th><th class="num">Paid clues</th><th class="num">Total score</th></tr>
    <?php foreach ($rows as $i => $r): ?>
      <tr class="<?= (int)$r['id'] === (int)$_SESSION['user_id'] ? 'me' : '' ?>">
        <td class="num"><?= $i + 1 ?></td>
        <td><?= e($r['full_name']) ?> <span class="muted">@<?= e($r['username']) ?></span></td>
        <td class="num"><?= (int)$r['played'] ?></td>
        <td class="num"><?= (int)$r['solved'] ?></td>
        <td class="num"><?= (int)$r['clues'] ?></td>
        <td class="num"><strong><?= (int)$r['total_score'] ?></strong></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
