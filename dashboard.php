<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$me = current_user();

$q = $pdo->prepare("SELECT COUNT(*) AS total,
    SUM(approval_status='Pending') AS pending,
    SUM(claim_status='Claimed') AS claimed
    FROM items WHERE reporter_id = ?");
$q->execute([$me['user_id']]);
$mine = $q->fetch();

$pendingItems = $pendingClaims = $userCount = $logCount = null;
if (has_role('Admin', 'Staff')) {
    $pendingItems  = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE approval_status='Pending'")->fetchColumn();
    $pendingClaims = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE claim_state='Pending'")->fetchColumn();
}
if (has_role('Admin')) {
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $logCount  = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Dashboard</h1>
<p class="text-muted">Signed in as <strong><?= e($me['role_name']) ?></strong></p>

<h2 class="h6 text-uppercase text-muted">My activity</h2>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= (int)$mine['total'] ?></div>My reports</div></div>
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= (int)$mine['pending'] ?></div>Awaiting approval</div></div>
  <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= (int)$mine['claimed'] ?></div>Claimed</div></div>
</div>

<?php if (has_role('Admin', 'Staff')): ?>
<h2 class="h6 text-uppercase text-muted">Staff queue</h2>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><a class="text-decoration-none" href="<?= url('admin/items.php?approval=Pending') ?>"><div class="stat-card"><div class="stat-num"><?= $pendingItems ?></div>Reports to approve</div></a></div>
  <div class="col-6 col-md-3"><a class="text-decoration-none" href="<?= url('admin/claims.php') ?>"><div class="stat-card"><div class="stat-num"><?= $pendingClaims ?></div>Claims to review</div></a></div>
  <?php if (has_role('Admin')): ?>
  <div class="col-6 col-md-3"><a class="text-decoration-none" href="<?= url('admin/users.php') ?>"><div class="stat-card"><div class="stat-num"><?= $userCount ?></div>Users</div></a></div>
  <div class="col-6 col-md-3"><a class="text-decoration-none" href="<?= url('admin/audit.php') ?>"><div class="stat-card"><div class="stat-num"><?= $logCount ?></div>Audit log entries</div></a></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2">
  <a class="btn btn-primary" href="<?= url('item_form.php') ?>">Report an item</a>
  <a class="btn btn-outline-primary" href="<?= url('browse.php') ?>">Browse items</a>
  <a class="btn btn-outline-secondary" href="<?= url('my_claims.php') ?>">My claims</a>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
