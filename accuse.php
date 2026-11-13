<?php
require __DIR__ . '/includes/auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['case_id'] ?? $_POST['case_id'] ?? 0);
$case = get_case($conn, $id);
if (!$case || !$case['is_published']) { flash('Case not found.', 'error'); redirect('dashboard.php'); }
if (is_admin()) { flash('Admins cannot make accusations.', 'error'); redirect('case.php?id=' . $id); }
if (user_accusation($conn, $uid, $id)) redirect('result.php?case_id=' . $id);

$stmt = $conn->prepare("SELECT id, name, occupation FROM suspects WHERE case_id = ? ORDER BY name");
$stmt->bind_param('i', $id);
$stmt->execute();
$suspects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ids = array_map('intval', array_column($suspects, 'id'));

$errors = [];
$old = ['suspect_id' => '', 'reasoning' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['suspect_id'] = (int)($_POST['suspect_id'] ?? 0);
    $old['reasoning']  = trim($_POST['reasoning'] ?? '');
    $confirm           = isset($_POST['confirm']);

    if (!in_array($old['suspect_id'], $ids, true)) $errors['suspect_id'] = 'Choose the suspect you are accusing.';
    $len = mb_strlen($old['reasoning']);
    if ($len < 20)   $errors['reasoning'] = 'Explain your reasoning in at least 20 characters.';
    if ($len > 1000) $errors['reasoning'] = 'Keep your reasoning under 1000 characters.';
    if (!$confirm)   $errors['confirm'] = 'You must confirm that this accusation is final.';

    if (!$errors) {
        $t       = player_totals($conn, $uid, $id);
        $correct = ($old['suspect_id'] === (int)$case['culprit_id']) ? 1 : 0;
        $score   = $correct ? calc_score($case['base_score'], $t['spent']) : 0;
        try {
            $ins = $conn->prepare("INSERT INTO accusations (user_id, case_id, suspect_id, reasoning, is_correct, clues_used, points_spent, score)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param('iiisiiii', $uid, $id, $old['suspect_id'], $old['reasoning'], $correct, $t['clues'], $t['spent'], $score);
            $ins->execute();
        } catch (mysqli_sql_exception $ex) {
            // Duplicate = already accused (double submit); just show the result
            if ($ex->getCode() != 1062) { $errors['general'] = 'Could not record your accusation. Please try again.'; }
        }
        if (!$errors) redirect('result.php?case_id=' . $id);
    }
}

function field_error($errors, $k) { return isset($errors[$k]) ? e($errors[$k]) : ''; }

$pageTitle = 'Final Accusation';
include __DIR__ . '/includes/header.php';
?>
<div class="card narrow" style="max-width:620px">
  <h2>Final Accusation</h2>
  <p class="muted">Case: <?= e($case['title']) ?>. This is your only attempt. A wrong accusation scores zero.</p>
  <?php if (isset($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>

  <form id="accuse-form" method="POST" action="accuse.php" novalidate>
    <input type="hidden" name="case_id" value="<?= $id ?>">
    <div class="field"><label>Who did it?</label>
      <select name="suspect_id">
        <option value="">-- Choose a suspect --</option>
        <?php foreach ($suspects as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $old['suspect_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?> (<?= e($s['occupation']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <span class="error" id="err-suspect_id"><?= field_error($errors, 'suspect_id') ?></span></div>

    <div class="field"><label>Your reasoning (motive, means, and which clue gave them away)</label>
      <textarea name="reasoning"><?= e($old['reasoning']) ?></textarea>
      <span class="error" id="err-reasoning"><?= field_error($errors, 'reasoning') ?></span></div>

    <div class="field">
      <label><input type="checkbox" name="confirm" value="1"> I understand this accusation is final.</label>
      <span class="error" id="err-confirm"><?= field_error($errors, 'confirm') ?></span></div>

    <button type="submit">Submit accusation</button>
    <a class="btn ghost" href="case.php?id=<?= $id ?>">Back to the case</a>
  </form>
</div>
<script src="<?= BASE_URL ?>assets/validate.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
