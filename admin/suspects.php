<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

$cid  = (int)($_GET['case_id'] ?? $_POST['case_id'] ?? 0);
$case = get_case($conn, $cid);
if (!$case) { flash('Case not found.', 'error'); redirect('cases.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete
    if (isset($_POST['delete_id'])) {
        $sid = (int)$_POST['delete_id'];
        try {
            $conn->begin_transaction();
            // If the deleted suspect was the culprit, the case goes back to draft
            $u = $conn->prepare("UPDATE cases SET culprit_id = NULL, is_published = 0 WHERE id = ? AND culprit_id = ?");
            $u->bind_param('ii', $cid, $sid);
            $u->execute();
            $d = $conn->prepare("DELETE FROM suspects WHERE id = ? AND case_id = ?");
            $d->bind_param('ii', $sid, $cid);
            $d->execute();
            $conn->commit();
            flash('Suspect deleted.', 'success');
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            flash('Could not delete the suspect.', 'error');
        }
        redirect('suspects.php?case_id=' . $cid);
    }

    // Add or update
    $sid = (int)($_POST['id'] ?? 0);
    $v = [];
    foreach (['name', 'occupation', 'statement', 'alibi', 'motive'] as $k) $v[$k] = trim($_POST[$k] ?? '');
    if ($v['name'] === '' || $v['occupation'] === '' || $v['statement'] === '' || $v['alibi'] === '' || $v['motive'] === ''
        || mb_strlen($v['name']) > 100 || mb_strlen($v['occupation']) > 100) {
        flash('Fill in every field (name and occupation up to 100 characters).', 'error');
        redirect('suspects.php?case_id=' . $cid . ($sid ? '&edit=' . $sid : ''));
    }
    if ($sid) {
        $stmt = $conn->prepare("UPDATE suspects SET name=?, occupation=?, statement=?, alibi=?, motive=? WHERE id=? AND case_id=?");
        $stmt->bind_param('sssssii', $v['name'], $v['occupation'], $v['statement'], $v['alibi'], $v['motive'], $sid, $cid);
    } else {
        $stmt = $conn->prepare("INSERT INTO suspects (case_id, name, occupation, statement, alibi, motive) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssss', $cid, $v['name'], $v['occupation'], $v['statement'], $v['alibi'], $v['motive']);
    }
    $stmt->execute();
    flash($sid ? 'Suspect updated.' : 'Suspect added.', 'success');
    redirect('suspects.php?case_id=' . $cid);
}

$stmt = $conn->prepare("SELECT * FROM suspects WHERE case_id = ? ORDER BY id");
$stmt->bind_param('i', $cid);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$edit = ['id' => 0, 'name' => '', 'occupation' => '', 'statement' => '', 'alibi' => '', 'motive' => ''];
$editId = (int)($_GET['edit'] ?? 0);
foreach ($list as $s) { if ((int)$s['id'] === $editId) $edit = $s; }

$pageTitle = 'Suspects';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="cases.php">&larr; All cases</a></p>
<div class="card">
  <h2>Suspects: <?= e($case['title']) ?></h2>
  <?php if (!$list): ?><p class="muted">No suspects yet.</p><?php else: ?>
  <table>
    <tr><th>Name</th><th>Occupation</th><th>Role</th><th>Actions</th></tr>
    <?php foreach ($list as $s): ?>
      <tr>
        <td><?= e($s['name']) ?></td><td><?= e($s['occupation']) ?></td>
        <td><?= (int)$case['culprit_id'] === (int)$s['id'] ? '<strong>Culprit</strong>' : 'Suspect' ?></td>
        <td>
          <a class="btn small" href="suspects.php?case_id=<?= $cid ?>&edit=<?= $s['id'] ?>">Edit</a>
          <form method="POST" class="inline-form" onsubmit="return confirm('Delete this suspect?');">
            <input type="hidden" name="case_id" value="<?= $cid ?>">
            <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
            <button type="submit" class="small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2><?= $edit['id'] ? 'Edit suspect' : 'Add suspect' ?></h2>
  <form method="POST">
    <input type="hidden" name="case_id" value="<?= $cid ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="row-inline">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($edit['name']) ?>" required></div>
      <div class="field"><label>Occupation</label><input type="text" name="occupation" value="<?= e($edit['occupation']) ?>" required></div>
    </div><br>
    <div class="field"><label>Statement (what they say happened)</label><textarea name="statement" required><?= e($edit['statement']) ?></textarea></div>
    <div class="field"><label>Alibi</label><textarea name="alibi" required><?= e($edit['alibi']) ?></textarea></div>
    <div class="field"><label>Possible motive</label><textarea name="motive" required><?= e($edit['motive']) ?></textarea></div>
    <button type="submit"><?= $edit['id'] ? 'Update suspect' : 'Add suspect' ?></button>
    <?php if ($edit['id']): ?><a class="btn ghost" href="suspects.php?case_id=<?= $cid ?>">Cancel edit</a><?php endif; ?>
  </form>
</div>
<p><a class="btn ghost" href="evidence.php?case_id=<?= $cid ?>">Next: evidence &rarr;</a>
   <a class="btn ghost" href="case_edit.php?id=<?= $cid ?>">Choose culprit / publish</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
