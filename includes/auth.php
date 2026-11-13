<?php
// Include at the top of every page: starts the session and enforces the inactivity timeout (Task 6a)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (logged_in()) {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        $isAjax = strpos($_SERVER['SCRIPT_NAME'], '/ajax/') !== false;
        session_unset();
        session_destroy();
        if ($isAjax) json_out(['error' => 'Your session expired. Please log in again.'], 401);
        session_start();
        flash('Your session expired due to inactivity. Please log in again.', 'error');
        redirect(BASE_URL . 'login.php');
    }
    $_SESSION['last_activity'] = time();
}
