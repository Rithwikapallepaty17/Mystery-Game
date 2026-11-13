<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

$cid  = (int)($_GET['case_id'] ?? $_POST['case_id'] ?? 0);
$case = get_case($conn, $cid);
if (!$case) { flash('Case not found.', 'error'); redirect('cases.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $eid = (int)$_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM evidence WHERE id = ? AND case_id = ?");
        $stmt->bind_param('ii', $eid, $cid);
        $stmt->execute();
        flash('Evidence deleted.', 'success');
        redirect('evidence.php?case_id=' . $cid);
    }

    $eid   = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $found = trim($_POST['found_at'] ?? '');
    $cost  = (int)($_POST['cost'] ?? 0);

    if ($title === '' || $desc === '' || $found === '' || mb_strlen($title) > 150 || mb_strlen($found) > 150 || $cost < 0 || $cost > 50) {
        flash('Fill in every field. Cost must be between 0 (free clue) and 50.', 'error');
        redirect('evidence.php?case_id=' . $cid . ($eid ? '&edit=' . $eid : ''));
    }
    if ($eid) {
        $stmt = $conn->prepare("UPDATE evidence SET title=?, description=?, found_at=?, cost=? WHERE id=? AND case_id=?");
        $stmt->bind_param('sssiii', $title, $desc, $found, $cost, $eid, $cid);
    } else {
        $stmt = $conn->prepare("INSERT INTO evidence (case_id, title, description, found_at, cost) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('isssi', $cid, $title, $desc, $found, $cost);
    }
    $stmt->execute();
    flash($eid ? 'Evidence updated.' : 'Evidence added.', 'success');
    redirect('evidence.php?case_id=' . $cid);
}

$stmt = $conn->prepare("SELECT * FROM evidence WHERE case_id = ? ORDER BY cost, id");
$stmt->bind_param('i', $cid);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$edit = ['id' => 0, 'title' => '', 'description' => '', 'found_at' => '', 'cost' => 10];
$editId = (int)($_GET['edit'] ?? 0);
foreach ($list as $ev) { if ((int)$ev['id'] === $editId) $edit = $ev; }

$pageTitle = 'Evidence';
include __DIR__ . '/../includes/header.php';
?>
<p><a href="cases.php">&larr; All cases</a></p>
<div class="card">
  <h2>Evidence: <?= e($case['title']) ?></h2>
  <?php if (!$list): ?><p class="muted">No evidence yet.</p><?php else: ?>
  <table>
    <tr><th>Title</th><th>Found at</th><th class="num">Cost</th><th>Actions</th></tr>
    <?php foreach ($list as $ev): ?>
      <tr>
        <td><?= e($ev['title']) ?></td><td><?= e($ev['found_at']) ?></td>
        <td class="num"><?= (int)$ev['cost'] === 0 ? 'Free' : (int)$ev['cost'] ?></td>
        <td>
          <a class="btn small" href="evidence.php?case_id=<?= $cid ?>&edit=<?= $ev['id'] ?>">Edit</a>
          <form method="POST" class="inline-form" onsubmit="return confirm('Delete this evidence?');">
            <input type="hidden" name="case_id" value="<?= $cid ?>">
            <input type="hidden" name="delete_id" value="<?= $ev['id'] ?>">
            <button type="submit" class="small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2><?= $edit['id'] ? 'Edit evidence' : 'Add evidence' ?></h2>
  <form method="POST">
    <input type="hidden" name="case_id" value="<?= $cid ?>">
    <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="row-inline">
      <div class="field"><label>Title</label><input type="text" name="title" value="<?= e($edit['title']) ?>" required></div>
      <div class="field"><label>Found at</label><input type="text" name="found_at" value="<?= e($edit['found_at']) ?>" required></div>
      <div class="field" style="max-width:150px"><label>Cost (0 = free)</label><input type="number" name="cost" min="0" max="50" value="<?= (int)$edit['cost'] ?>"></div>
    </div><br>
    <div class="field"><label>Details</label><textarea name="description" required><?= e($edit['description']) ?></textarea></div>
    <button type="submit"><?= $edit['id'] ? 'Update evidence' : 'Add evidence' ?></button>
    <?php if ($edit['id']): ?><a class="btn ghost" href="evidence.php?case_id=<?= $cid ?>">Cancel edit</a><?php endif; ?>
  </form>
  <p class="muted">Tip: keep one free clue so players have a starting point, and price the decisive clues higher.</p>
</div>
<p><a class="btn ghost" href="case_edit.php?id=<?= $cid ?>">Choose culprit / publish</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
