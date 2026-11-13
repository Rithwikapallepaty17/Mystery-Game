<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

// Delete a case: suspects, evidence and accusations are removed by ON DELETE CASCADE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM cases WHERE id = ?");
    $stmt->bind_param('i', $del);
    $stmt->execute();
    flash($stmt->affected_rows > 0 ? 'Case deleted.' : 'Case not found.', $stmt->affected_rows > 0 ? 'success' : 'error');
    redirect('cases.php');
}

$cases = $conn->query("SELECT c.id, c.title, c.difficulty, c.is_published, c.culprit_id,
                              (SELECT COUNT(*) FROM suspects s WHERE s.case_id = c.id) AS n_suspects,
                              (SELECT COUNT(*) FROM evidence e WHERE e.case_id = c.id) AS n_evidence,
                              (SELECT COUNT(*) FROM accusations a WHERE a.case_id = c.id) AS n_played
                       FROM cases c ORDER BY c.id")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Cases';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div class="row-inline" style="justify-content:space-between">
    <h2 style="margin:0">Cases (<?= count($cases) ?>)</h2>
    <a class="btn" href="case_edit.php">+ New case</a>
  </div>
  <br>
  <?php if (!$cases): ?><p class="muted">No cases yet.</p><?php else: ?>
  <table>
    <tr><th>Title</th><th class="num">Level</th><th class="num">Suspects</th><th class="num">Clues</th><th class="num">Played</th><th class="num">Status</th><th>Actions</th></tr>
    <?php foreach ($cases as $c): ?>
      <tr>
        <td><?= e($c['title']) ?></td>
        <td class="num"><span class="badge <?= e($c['difficulty']) ?>"><?= e($c['difficulty']) ?></span></td>
        <td class="num"><?= (int)$c['n_suspects'] ?></td>
        <td class="num"><?= (int)$c['n_evidence'] ?></td>
        <td class="num"><?= (int)$c['n_played'] ?></td>
        <td class="num"><?= $c['is_published'] ? 'Published' : 'Draft' ?></td>
        <td>
          <a class="btn small" href="case_edit.php?id=<?= $c['id'] ?>">Edit</a>
          <a class="btn small ghost" href="suspects.php?case_id=<?= $c['id'] ?>">Suspects</a>
          <a class="btn small ghost" href="evidence.php?case_id=<?= $c['id'] ?>">Evidence</a>
          <a class="btn small ghost" href="../case.php?id=<?= $c['id'] ?>">Preview</a>
          <form method="POST" class="inline-form" onsubmit="return confirm('Delete this case and all its suspects, evidence and results?');">
            <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
            <button type="submit" class="small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>
<p class="muted">Workflow: create the case, add suspects and evidence, then edit the case again to choose the culprit and publish it.</p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
