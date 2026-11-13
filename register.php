<?php
require __DIR__ . '/includes/auth.php';
if (logged_in()) redirect('dashboard.php');

$errors = [];
$old = array_fill_keys(['full_name', 'email', 'username'], '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '') $errors['full_name'] = 'Full name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Za-z0-9_]{4,20}$/', $old['username'])) $errors['username'] = 'Username must be 4-20 letters, digits or _.';
    if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'player')");
            $stmt->bind_param('ssss', $old['username'], $hash, $old['full_name'], $old['email']);
            $stmt->execute();
            flash('Welcome to the force, detective. Please log in.', 'success');
            redirect('login.php');
        } catch (mysqli_sql_exception $ex) {
            $errors['general'] = ($ex->getCode() == 1062) ? 'That username or email is already registered.' : 'Registration failed. Please try again.';
        }
    }
}

function field_error($errors, $k) { return isset($errors[$k]) ? e($errors[$k]) : ''; }

$pageTitle = 'Join';
include __DIR__ . '/includes/header.php';
?>
<div class="card narrow">
  <h2>Join as Detective</h2>
  <?php if (isset($errors['general'])): ?><div class="alert error"><?= e($errors['general']) ?></div><?php endif; ?>
  <form id="register-form" method="POST" action="register.php" novalidate>
    <div class="field"><label>Full Name</label>
      <input type="text" name="full_name" value="<?= e($old['full_name']) ?>">
      <span class="error" id="err-full_name"><?= field_error($errors, 'full_name') ?></span></div>
    <div class="field"><label>Email</label>
      <input type="email" name="email" value="<?= e($old['email']) ?>">
      <span class="error" id="err-email"><?= field_error($errors, 'email') ?></span></div>
    <div class="field"><label>Username</label>
      <input type="text" name="username" value="<?= e($old['username']) ?>">
      <span class="error" id="err-username"><?= field_error($errors, 'username') ?></span></div>
    <div class="field"><label>Password</label>
      <input type="password" name="password">
      <span class="error" id="err-password"><?= field_error($errors, 'password') ?></span></div>
    <div class="field"><label>Confirm Password</label>
      <input type="password" name="confirm_password">
      <span class="error" id="err-confirm_password"><?= field_error($errors, 'confirm_password') ?></span></div>
    <button type="submit">Register</button>
  </form>
  <p class="muted">Already on the force? <a href="login.php">Log in</a></p>
</div>
<script src="<?= BASE_URL ?>assets/validate.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
