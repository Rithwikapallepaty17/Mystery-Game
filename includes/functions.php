<?php
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nl($s) { return nl2br(e($s)); }

function flash($msg, $type = 'info') { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; }

function show_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class='alert " . e($f['type']) . "'>" . e($f['msg']) . "</div>";
    }
}

function redirect($url) { header('Location: ' . $url); exit; }

function json_out($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data);
    exit;
}

function logged_in() { return isset($_SESSION['user_id']); }
function is_admin()  { return logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }

function require_login($ajax = false) {
    if (!logged_in()) {
        if ($ajax) json_out(['error' => 'Please log in again.'], 401);
        redirect(BASE_URL . 'login.php');
    }
}

function require_admin($ajax = false) {
    if (!is_admin()) {
        if ($ajax) json_out(['error' => 'Forbidden'], 403);
        redirect(BASE_URL . 'login.php');
    }
}

function get_case($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function user_accusation($conn, $uid, $case_id) {
    $stmt = $conn->prepare("SELECT * FROM accusations WHERE user_id = ? AND case_id = ?");
    $stmt->bind_param('ii', $uid, $case_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Number of paid clues the player revealed in this case, and the points they cost
function player_totals($conn, $uid, $case_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS clues, COALESCE(SUM(ev.cost), 0) AS spent
                            FROM evidence_unlocked eu JOIN evidence ev ON ev.id = eu.evidence_id
                            WHERE eu.user_id = ? AND ev.case_id = ? AND ev.cost > 0");
    $stmt->bind_param('ii', $uid, $case_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return ['clues' => (int)$r['clues'], 'spent' => (int)$r['spent']];
}

function calc_score($base, $spent) {
    return max(MIN_CORRECT_SCORE, (int)$base - (int)$spent);
}
