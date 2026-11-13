<?php
require __DIR__ . '/includes/auth.php';
if (logged_in()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = 'User not found.';
    } elseif (!password_verify($password, $user['password'])) {
        $error = 'Invalid password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['full_name']     = $user['full_name'];
        $_SESSION['role']          = $user['role'];
        $_SESSION['last_activity'] = time();
        redirect('dashboard.php');
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
  <h2>Detective Login</h2>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <form method="POST" action="login.php">
    <div class="field"><label>Username</label><input type="text" name="username" required></div>
    <div class="field"><label>Password</label><input type="password" name="password" required></div>
    <button type="submit">Login</button>
  </form>
  <p class="muted">New here? <a href="register.php">Join as Detective</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
