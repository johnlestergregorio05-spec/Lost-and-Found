<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($password === '') $errors[] = 'Password is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id WHERE u.email = ?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            if ($u['account_status'] !== 'Active') {
                $errors[] = 'This account is inactive. Contact the administrator.';
                audit('LOGIN_BLOCKED', 'user', (int)$u['user_id'], 'Inactive account', (int)$u['user_id']);
            } else {
                session_regenerate_id(true);
                unset($u['password_hash']);
                $_SESSION['user'] = $u;
                audit('LOGIN', 'user', (int)$u['user_id'], 'User logged in');
                flash('success', 'Welcome back, ' . $u['full_name'] . '!');
                redirect('dashboard.php');
            }
        } else {
            $errors[] = 'Incorrect email or password.';
            audit('LOGIN_FAILED', 'user', $u ? (int)$u['user_id'] : null, 'Failed login for ' . $email, $u ? (int)$u['user_id'] : null);
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card shadow-sm"><div class="card-body p-4">
      <h1 class="h4 mb-3">Login</h1>
      <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required>
            <div class="invalid-feedback">Enter a valid email.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback">Password is required.</div>
        </div>
        <button class="btn btn-primary w-100">Login</button>
      </form>
      <p class="mt-3 mb-0 small">No account? <a href="<?= url('register.php') ?>">Register here</a>.</p>
    </div></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
