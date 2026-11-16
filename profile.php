<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$me = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact_no'] ?? '');
    $cur = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';

    if (strlen($name) < 3) $errors[] = 'Full name must be at least 3 characters.';
    if ($contact !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $contact)) $errors[] = 'Contact number format is invalid.';

    $changePw = $new !== '';
    if ($changePw) {
        $h = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $h->execute([$me['user_id']]);
        if (!password_verify($cur, (string)$h->fetchColumn())) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
    }

    if (!$errors) {
        $pdo->prepare('UPDATE users SET full_name = ?, contact_no = ? WHERE user_id = ?')
            ->execute([$name, $contact ?: null, $me['user_id']]);
        if ($changePw) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $me['user_id']]);
        }
        $_SESSION['user']['full_name'] = $name;
        $_SESSION['user']['contact_no'] = $contact ?: null;
        audit('UPDATE', 'user', (int)$me['user_id'], $changePw ? 'Profile and password updated' : 'Profile updated');
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}

$me = current_user();
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-8 col-lg-6">
<div class="card shadow-sm"><div class="card-body p-4">
  <h1 class="h4 mb-3">My Profile</h1>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="<?= e($me['email']) ?>" disabled></div>
    <div class="mb-3"><label class="form-label">School ID</label><input class="form-control" value="<?= e($me['school_id_no']) ?>" disabled></div>
    <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" name="full_name" minlength="3" value="<?= e($me['full_name']) ?>" required></div>
    <div class="mb-3"><label class="form-label">Contact No.</label><input class="form-control" name="contact_no" pattern="[0-9+\-\s]{7,20}" value="<?= e($me['contact_no'] ?? '') ?>"></div>
    <hr><p class="small text-muted">Leave blank to keep your current password.</p>
    <div class="mb-3"><label class="form-label">Current password</label><input type="password" class="form-control" name="current_password"></div>
    <div class="mb-3"><label class="form-label">New password</label><input type="password" class="form-control" name="new_password" minlength="8"></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div></div>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
