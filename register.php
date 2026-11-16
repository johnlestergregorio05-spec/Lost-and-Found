<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
$old = ['school_id_no' => '', 'full_name' => '', 'email' => '', 'contact_no' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if ($old['school_id_no'] === '' || strlen($old['school_id_no']) > 30) $errors[] = 'School ID number is required.';
    if (strlen($old['full_name']) < 3) $errors[] = 'Full name must be at least 3 characters.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($old['contact_no'] !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $old['contact_no'])) $errors[] = 'Contact number format is invalid.';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR school_id_no = ?');
        $chk->execute([$old['email'], $old['school_id_no']]);
        if ($chk->fetchColumn() > 0) $errors[] = 'Email or School ID is already registered.';
    }

    if (!$errors) {
        $roleId = (int)$pdo->query("SELECT role_id FROM roles WHERE role_name = 'User'")->fetchColumn();
        $ins = $pdo->prepare('INSERT INTO users (role_id, school_id_no, full_name, email, contact_no, password_hash) VALUES (?,?,?,?,?,?)');
        $ins->execute([$roleId, $old['school_id_no'], $old['full_name'], $old['email'],
                       $old['contact_no'] ?: null, password_hash($pw, PASSWORD_DEFAULT)]);
        $newId = (int)$pdo->lastInsertId();
        audit('REGISTER', 'user', $newId, 'New account registered', $newId);
        flash('success', 'Account created. You can now log in.');
        redirect('login.php');
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm"><div class="card-body p-4">
      <h1 class="h4 mb-3">Create an account</h1>
      <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="school_id_no">School ID No.</label>
            <input class="form-control" id="school_id_no" name="school_id_no" maxlength="30" value="<?= e($old['school_id_no']) ?>" required>
            <div class="invalid-feedback">Required.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="contact_no">Contact No. (optional)</label>
            <input class="form-control" id="contact_no" name="contact_no" pattern="[0-9+\-\s]{7,20}" value="<?= e($old['contact_no']) ?>">
            <div class="invalid-feedback">Use digits, +, - or spaces.</div>
          </div>
          <div class="col-12">
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" minlength="3" maxlength="100" value="<?= e($old['full_name']) ?>" required>
            <div class="invalid-feedback">At least 3 characters.</div>
          </div>
          <div class="col-12">
            <label class="form-label" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" required>
            <div class="invalid-feedback">Enter a valid email.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
            <div class="invalid-feedback">At least 8 characters.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="password2">Confirm password</label>
            <input type="password" class="form-control" id="password2" name="password2" minlength="8" required>
            <div class="invalid-feedback" id="pw2fb">Passwords must match.</div>
          </div>
        </div>
        <button class="btn btn-primary w-100 mt-4">Register</button>
      </form>
      <p class="mt-3 mb-0 small">Already registered? <a href="<?= url('login.php') ?>">Login</a>.</p>
    </div></div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
