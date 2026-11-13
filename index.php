<?php
require __DIR__ . '/includes/auth.php';
redirect(logged_in() ? 'dashboard.php' : 'login.php');
