<?php
// Case file as XML with a DTD and an XSL stylesheet (Task 9)
require __DIR__ . '/includes/auth.php';
require_login();

$id   = (int)($_GET['id'] ?? 0);
$case = get_case($conn, $id);
if (!$case || (!$case['is_published'] && !is_admin())) { flash('Case not found.', 'error'); redirect('dashboard.php'); }

// The file contains the solution, so players get it only after closing the case
if (!is_admin() && !user_accusation($conn, (int)$_SESSION['user_id'], $id)) {
    flash('Close the case with a final accusation to unlock its case file.', 'info');
    redirect('case.php?id=' . $id);
}

$stmt = $conn->prepare("SELECT id, name, occupation, statement, alibi, motive FROM suspects WHERE case_id = ? ORDER BY id");
$stmt->bind_param('i', $id);
$stmt->execute();
$suspects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare("SELECT id, title, description, found_at, cost FROM evidence WHERE case_id = ? ORDER BY id");
$stmt->bind_param('i', $id);
$stmt->execute();
$evidence = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$culpritName = '';
foreach ($suspects as $s) { if ((int)$s['id'] === (int)$case['culprit_id']) $culpritName = $s['name']; }

$w = new XMLWriter();
$w->openMemory();
$w->setIndent(true);
$w->setIndentString('  ');
$w->startDocument('1.0', 'UTF-8');
$w->writePi('xml-stylesheet', 'type="text/xsl" href="xml/case.xsl"');
$w->startDtd('casefile', null, 'xml/case.dtd');
$w->endDtd();

$w->startElement('casefile');
$w->writeAttribute('id', (string)$case['id']);
$w->writeAttribute('difficulty', $case['difficulty']);
$w->writeElement('title', $case['title']);
$w->writeElement('victim', $case['victim']);
$w->writeElement('location', $case['location']);
$w->writeElement('incident_time', $case['incident_time']);
$w->writeElement('summary', $case['summary']);

$w->startElement('suspects');
foreach ($suspects as $s) {
    $w->startElement('suspect');
    $w->writeAttribute('id', (string)$s['id']);
    $w->writeElement('name', $s['name']);
    $w->writeElement('occupation', $s['occupation']);
    $w->writeElement('statement', $s['statement']);
    $w->writeElement('alibi', $s['alibi']);
    $w->writeElement('motive', $s['motive']);
    $w->endElement();
}
$w->endElement();

$w->startElement('evidence_list');
foreach ($evidence as $ev) {
    $w->startElement('evidence');
    $w->writeAttribute('id', (string)$ev['id']);
    $w->writeAttribute('cost', (string)$ev['cost']);
    $w->writeElement('title', $ev['title']);
    $w->writeElement('description', $ev['description']);
    $w->writeElement('found_at', $ev['found_at']);
    $w->endElement();
}
$w->endElement();

$w->startElement('solution');
$w->writeElement('culprit', $culpritName);
$w->writeElement('explanation', $case['solution']);
$w->endElement();

$w->endElement(); // casefile
$w->endDocument();

header('Content-Type: application/xml; charset=UTF-8');
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="case_' . $id . '.xml"');
}
echo $w->outputMemory();
