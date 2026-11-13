<?php
// Database + app configuration
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('BASE_URL', '/detective/');   // folder name inside htdocs
define('SESSION_TIMEOUT', 1800);     // seconds of inactivity (30 min)
define('MIN_CORRECT_SCORE', 10);     // a correct answer never scores below this

try {
    $conn = new mysqli('127.0.0.1', 'root', '', 'detective_db');   // add MySQL password if you set one
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    die('Database connection failed: ' . htmlspecialchars($ex->getMessage()));
}
