<?php /* expects: $pageTitle (string), optional $useJquery (bool) */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Case Files') ?> | Case Files</title>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/style.css">
<?php if (!empty($useJquery)): ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php endif; ?>
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= BASE_URL ?>dashboard.php">&#128373; Case Files</a>
  <nav>
  <?php if (logged_in()): ?>
    <a href="<?= BASE_URL ?>dashboard.php">Cases</a>
    <a href="<?= BASE_URL ?>leaderboard.php">Leaderboard</a>
    <?php if (is_admin()): ?><a href="<?= BASE_URL ?>admin/cases.php">Manage Cases</a><?php endif; ?>
    <a href="<?= BASE_URL ?>logout.php">Logout (<?= e($_SESSION['username']) ?>)</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>login.php">Login</a>
    <a href="<?= BASE_URL ?>register.php">Join as Detective</a>
  <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php show_flash(); ?>
