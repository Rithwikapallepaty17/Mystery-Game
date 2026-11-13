<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$case = $id ? get_case($conn, $id) : null;
if ($id && !$case) { flash('Case not found.', 'error'); redirect('cases.php'); }

$suspects = [];
if ($id) {
    $stmt = $conn->prepare("SELECT id, name FROM suspects WHERE case_id = ? ORDER BY name");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $suspects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$suspectIds = array_map('intval', array_column($suspects, 'id'));

$f = $case ?: ['title' => '', 'victim' => '', 'location' => '', 'incident_time' => '', 'difficulty' => 'medium',
               'base_score' => 100, 'summary' => '', 'solution' => '', 'culprit_id' => null, 'is_published' => 0];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $f['title']         = trim($_POST['title'] ?? '');
    $f['victim']        = trim($_POST['victim'] ?? '');
    $f['location']      = trim($_POST['location'] ?? '');
    $f['incident_time'] = trim($_POST['incident_time'] ?? '');
    $f['difficulty']    = $_POST['difficulty'] ?? 'medium';
    $f['base_score']    = (int)($_POST['base_score'] ?? 100);
    $f['summary']       = trim($_POST['summary'] ?? '');
    $f['solution']      = trim($_POST['solution'] ?? '');
    $f['culprit_id']    = $id ? ((int)($_POST['culprit_id'] ?? 0) ?: null) : null;
    $f['is_published']  = ($id && isset($_POST['is_published'])) ? 1 : 0;

    if ($f['title'] === '' || mb_strlen($f['title']) > 150) $errors[] = 'Title is required (max 150 characters).';
    if ($f['victim'] === '' || mb_strlen($f['victim']) > 150) $errors[] = 'Victim is required (max 150 characters).';
    if ($f['location'] === '' || mb_strlen($f['location']) > 150) $errors[] = 'Location is required (max 150 characters).';
    if ($f['incident_time'] === '' || mb_strlen($f['incident_time']) > 60) $errors[] = 'Time of incident is required (max 60 characters).';
    if (!in_array($f['difficulty'], ['easy', 'medium', 'hard'], true)) $errors[] = 'Choose a difficulty.';
    if ($f['base_score'] < 50 || $f['base_score'] > 500) $errors[] = 'Base score must be between 50 and 500.';
    if (mb_strlen($f['summary']) < 20) $errors[] = 'Write a summary of at least 20 characters.';
    if (mb_strlen($f['solution']) < 20) $errors[] = 'Write the solution explanation (at least 20 characters).';
    if ($f['culprit_id'] !== null && !in_array($f['culprit_id'], $suspectIds, true)) $errors[] = 'The culprit must be one of this case suspects.';
    if ($f['is_published'] && $f['culprit_id'] === null) $errors[] = 'Choose the culprit before publishing the case.';
    if ($f['is_published'] && count($suspects) < 2) $errors[] = 'Add at least two suspects before publishing.';

    if (!$errors) {
        if ($id) {
            $stmt = $conn->prepare("UPDATE cases SET title=?, victim=?, location=?, incident_time=?, difficulty=?, base_score=?,
                                    summary=?, solution=?, culprit_id=?, is_published=? WHERE id=?");
            $stmt->bind_param('sssssissiii', $f['title'], $f['victim'], $f['location'], $f['incident_time'], $f['difficulty'],
                              $f['base_score'], $f['summary'], $f['solution'], $f['culprit_id'], $f['is_published'], $id);
            $stmt->execute();
            flash('Case updated.', 'success');
            redirect('cases.php');
        } else {
            $stmt = $conn->prepare("INSERT INTO cases (title, victim, location, incident_time, difficulty, base_score, summary, solution)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssiss', $f['title'], $f['victim'], $f['location'], $f['incident_time'], $f['difficulty'],
                              $f['base_score'], $f['summary'], $f['solution']);
            $stmt->execute();
            flash('Case created. Now add suspects and evidence.', 'success');
            redirect('suspects.php?case_id=' . $conn->insert_id);
        }
    }
}

$pageTitle = $id ? 'Edit Case' : 'New Case';
include __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:720px;margin:auto">
  <h2><?= $id ? 'Edit case' : 'New case' ?></h2>
  <?php foreach ($errors as $err): ?><div class="alert error"><?= e($err) ?></div><?php endforeach; ?>
  <form method="POST">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="field"><label>Title</label><input type="text" name="title" value="<?= e($f['title']) ?>" required></div>
    <div class="field"><label>Victim</label><input type="text" name="victim" value="<?= e($f['victim']) ?>" required></div>
    <div class="row-inline">
      <div class="field"><label>Location</label><input type="text" name="location" value="<?= e($f['location']) ?>" required></div>
      <div class="field"><label>Time of incident</label><input type="text" name="incident_time" value="<?= e($f['incident_time']) ?>" required></div>
    </div><br>
    <div class="row-inline">
      <div class="field"><label>Difficulty</label>
        <select name="difficulty">
          <?php foreach (['easy', 'medium', 'hard'] as $d): ?>
            <option value="<?= $d ?>" <?= $f['difficulty'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="field"><label>Base score (50 to 500)</label><input type="number" name="base_score" min="50" max="500" value="<?= (int)$f['base_score'] ?>"></div>
    </div><br>
    <div class="field"><label>Case summary (what the players read first)</label><textarea name="summary"><?= e($f['summary']) ?></textarea></div>
    <div class="field"><label>Solution explanation (shown after the player accuses)</label><textarea name="solution"><?= e($f['solution']) ?></textarea></div>

    <?php if ($id): ?>
      <div class="field"><label>Culprit</label>
        <select name="culprit_id">
          <option value="">-- Not chosen yet --</option>
          <?php foreach ($suspects as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (int)$f['culprit_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="field"><label><input type="checkbox" name="is_published" value="1" <?= $f['is_published'] ? 'checked' : '' ?>> Published (visible to players)</label></div>
    <?php else: ?>
      <p class="muted">You will choose the culprit and publish the case after adding the suspects.</p>
    <?php endif; ?>

    <button type="submit"><?= $id ? 'Save changes' : 'Create case' ?></button>
    <a class="btn ghost" href="cases.php">Cancel</a>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
