<?php
require __DIR__ . '/includes/auth.php';
require_login();

$id   = (int)($_GET['id'] ?? 0);
$case = get_case($conn, $id);
if (!$case || (!$case['is_published'] && !is_admin())) {
    flash('Case not found.', 'error');
    redirect('dashboard.php');
}

// Cookie (Task 6b): remember the last case this detective opened (must be set before any output)
setcookie('last_case', (string)$id, time() + 86400 * 30, '/');

$uid   = (int)$_SESSION['user_id'];
$admin = is_admin();
$acc   = $admin ? null : user_accusation($conn, $uid, $id);
$closed = (bool)$acc;

$stmt = $conn->prepare("SELECT id, name, occupation FROM suspects WHERE case_id = ? ORDER BY name");
$stmt->bind_param('i', $id);
$stmt->execute();
$suspects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT ev.id, ev.title, ev.description, ev.found_at, ev.cost, eu.user_id AS unlocked_by
                        FROM evidence ev
                        LEFT JOIN evidence_unlocked eu ON eu.evidence_id = ev.id AND eu.user_id = ?
                        WHERE ev.case_id = ? ORDER BY ev.cost, ev.id");
$stmt->bind_param('ii', $uid, $id);
$stmt->execute();
$evidence = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totals    = player_totals($conn, $uid, $id);
$potential = calc_score($case['base_score'], $totals['spent']);

$pageTitle = $case['title'];
$useJquery = true;
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <span class="badge <?= e($case['difficulty']) ?>"><?= e($case['difficulty']) ?></span>
  <h1 style="margin-top:8px"><?= e($case['title']) ?></h1>
  <table style="margin:12px 0">
    <tr><th style="width:140px">Victim</th><td><?= e($case['victim']) ?></td></tr>
    <tr><th>Location</th><td><?= e($case['location']) ?></td></tr>
    <tr><th>Time of incident</th><td><?= e($case['incident_time']) ?></td></tr>
  </table>
  <p class="pre"><?= e($case['summary']) ?></p>
  <p><a class="btn ghost small" href="export_case.php?id=<?= $id ?>">Case file (XML)</a>
     <?php if (!$admin && !$closed): ?><span class="muted"> unlocks after your final accusation</span><?php endif; ?></p>
</div>

<?php if ($closed): ?>
  <div class="alert info">You have already made your final accusation in this case. <a href="result.php?case_id=<?= $id ?>">See the result</a></div>
<?php endif; ?>

<div class="card">
  <h2>Suspects</h2>
  <?php if (!$suspects): ?>
    <p class="muted">No suspects have been added to this case yet.</p>
  <?php else: ?>
  <div class="field" style="max-width:420px">
    <label for="suspect-select">Select a suspect to read their statement</label>
    <select id="suspect-select">
      <option value="">-- Choose a suspect --</option>
      <?php foreach ($suspects as $s): ?>
        <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['occupation']) ?>)</option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="panel" id="suspect-panel">
    <h3 id="s-name"></h3>
    <div class="muted" id="s-occ"></div>
    <dl>
      <dt>Statement</dt><dd id="s-statement"></dd>
      <dt>Alibi</dt><dd id="s-alibi"></dd>
      <dt>Possible motive</dt><dd id="s-motive"></dd>
    </dl>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Evidence Board</h2>
  <?php if (!$admin): ?>
  <div class="scorebar">
    <div>Paid clues used: <b id="clues-used"><?= $totals['clues'] ?></b></div>
    <div>Points spent: <b id="points-spent"><?= $totals['spent'] ?></b></div>
    <div>Score if you solve it now: <b id="potential"><?= $potential ?></b> / <?= (int)$case['base_score'] ?></div>
  </div>
  <?php endif; ?>
  <table id="evidence-table">
    <tr><th>Evidence</th><th>Details</th><th>Found at</th><th class="num">Cost</th><th class="num">Action</th></tr>
    <?php foreach ($evidence as $ev):
        $open = ((int)$ev['cost'] === 0) || $ev['unlocked_by'] || $admin; ?>
      <tr id="ev-<?= (int)$ev['id'] ?>" class="<?= $open ? '' : 'locked' ?>">
        <td><strong><?= e($ev['title']) ?></strong></td>
        <td class="desc pre"><?= $open ? e($ev['description']) : 'Locked. Reveal this clue to read the details.' ?></td>
        <td class="found"><?= $open ? e($ev['found_at']) : '?' ?></td>
        <td class="num"><?= (int)$ev['cost'] === 0 ? 'Free' : '-' . (int)$ev['cost'] ?></td>
        <td class="num action">
          <?php if ($open): ?>
            <?= (int)$ev['cost'] === 0 ? '&mdash;' : 'Revealed' ?>
          <?php elseif ($closed): ?>
            Case closed
          <?php else: ?>
            <button type="button" class="small reveal" data-id="<?= (int)$ev['id'] ?>" data-cost="<?= (int)$ev['cost'] ?>">Reveal</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$evidence): ?><tr><td colspan="5" class="muted">No evidence has been added yet.</td></tr><?php endif; ?>
  </table>
</div>

<?php if (!$admin): ?>
<div class="card" id="notes" >
  <h2>Detective Notebook</h2>
  <form id="note-form" class="note-form" autocomplete="off">
    <div class="grow">
      <input type="text" id="note-input" placeholder="Write a theory or a question to check..." maxlength="200">
      <span class="error" id="note-error"></span>
    </div>
    <button type="submit">Add note</button>
  </form>
  <ul id="notes-list"></ul>
  <p class="muted">Tick a note once you have checked it. Notes are saved in this browser only.</p>
</div>

<div class="card" style="text-align:center">
  <?php if ($closed): ?>
    <a class="btn" href="result.php?case_id=<?= $id ?>">View result</a>
  <?php elseif ($suspects): ?>
    <h2>Ready to name the culprit?</h2>
    <p class="muted">You only get one accusation, and a wrong one scores zero.</p>
    <a class="btn" href="accuse.php?case_id=<?= $id ?>">Make final accusation</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<script>
window.CASE = <?= json_encode([
    'base' => BASE_URL, 'caseId' => $id, 'uid' => $uid,
    'baseScore' => (int)$case['base_score'], 'minScore' => MIN_CORRECT_SCORE,
], JSON_HEX_TAG) ?>;
</script>
<script src="<?= BASE_URL ?>assets/case.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
