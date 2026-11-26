<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= url('assets/img/favicon.png') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&family=Bricolage+Grotesque:wght@600;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

  <header class="site-header">
    <a class="logo" href="<?= url('index.php') ?>"><img src="<?= url('assets/img/logo.png') ?>" alt="" width="33" height="40">TraceIT</a>
    <nav class="nav" aria-label="Main">
      <a href="<?= url('browse.php') ?>">Browse items</a>
      <?php if ($user): ?>
        <a href="<?= url('dashboard.php') ?>">Dashboard</a>
        <a href="<?= url('item_form.php') ?>">Report an item</a>
        <a href="<?= url('my_items.php') ?>">My reports</a>
        <a href="<?= url('my_claims.php') ?>">My claims</a>
      <?php else: ?>
        <a href="<?= url('item_form.php') ?>">Report an item</a>
        <a href="<?= url('index.php#how') ?>">How it works</a>
      <?php endif; ?>
      <?php if (has_role('Admin', 'Staff')): ?>
        <a href="<?= url('admin/items.php') ?>">Manage reports</a>
        <a href="<?= url('admin/claims.php') ?>">Claim requests</a>
      <?php endif; ?>
      <?php if (has_role('Admin')): ?>
        <a href="<?= url('admin/users.php') ?>">Users</a>
        <a href="<?= url('admin/audit.php') ?>">Audit logs</a>
      <?php endif; ?>
    </nav>
    <div class="auth">
      <?php if ($user): ?>
        <span class="auth-user"><?= e($user['full_name']) ?><small><?= e($user['role_name']) ?></small></span>
        <a class="btn btn-ghost btn-small" href="<?= url('profile.php') ?>">Profile</a>
        <a class="btn btn-primary btn-small" href="<?= url('logout.php') ?>">Log out</a>
      <?php else: ?>
        <a class="btn btn-ghost" href="<?= url('login.php') ?>">Log in</a>
        <a class="btn btn-primary" href="<?= url('register.php') ?>">Register</a>
      <?php endif; ?>
    </div>
  </header>

  <main>
<?php
$flashes = get_flashes();
if (empty($fullWidth)) echo '<div class="container py-4">';
elseif ($flashes) echo '<div class="flash-wrap">';
foreach ($flashes as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
      <?= e($f['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach;
if (!empty($fullWidth) && $flashes) echo '</div>';
