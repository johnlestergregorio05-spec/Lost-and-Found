<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT i.*, c.category_name, l.location_name, u.full_name AS reporter_name, u.contact_no
    FROM items i
    JOIN categories c ON c.category_id = i.category_id
    JOIN locations  l ON l.location_id = i.location_id
    JOIN users u ON u.user_id = i.reporter_id
    WHERE i.item_id = ?");
$s->execute([$id]);
$it = $s->fetch();

$me = current_user();
$canManage = $me && (has_role('Admin', 'Staff') || $me['user_id'] == ($it['reporter_id'] ?? 0));
if (!$it || ($it['approval_status'] !== 'Approved' && !$canManage)) {
    http_response_code(404);
    flash('danger', 'Item not found or not yet approved.');
    redirect('browse.php');
}

$errors = [];
$myClaim = null;
if ($me) {
    $c = $pdo->prepare('SELECT * FROM claims WHERE item_id = ? AND claimant_id = ?');
    $c->execute([$id, $me['user_id']]);
    $myClaim = $c->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim') {
    verify_csrf();
    require_login();
    $proof = trim($_POST['proof_details'] ?? '');
    if ($it['claim_status'] === 'Claimed') $errors[] = 'This item has already been claimed.';
    elseif ($it['reporter_id'] == $me['user_id']) $errors[] = 'You cannot claim your own report.';
    elseif ($myClaim) $errors[] = 'You already submitted a claim for this item.';
    elseif (strlen($proof) < 15) $errors[] = 'Provide at least 15 characters of proof (marks, contents, purchase details...).';

    if (!$errors) {
        $ins = $pdo->prepare('INSERT INTO claims (item_id, claimant_id, proof_details) VALUES (?,?,?)');
        $ins->execute([$id, $me['user_id'], $proof]);
        audit('CLAIM_REQUEST', 'claim', (int)$pdo->lastInsertId(), 'Claim requested for item #' . $id);
        flash('success', 'Claim submitted. Staff will review your proof.');
        redirect('item_view.php?id=' . $id);
    }
}

$pageTitle = $it['item_name'];
require_once __DIR__ . '/includes/header.php';
?>
<a href="<?= url('browse.php') ?>" class="small">&larr; Back to browse</a>
<div class="row g-4 mt-1">
  <div class="col-md-5">
    <img src="<?= e(photo_url($it['photo_path'])) ?>" class="img-fluid rounded shadow-sm w-100" alt="<?= e($it['item_name']) ?>">
  </div>
  <div class="col-md-7">
    <div class="mb-2"><?= status_badge($it['report_type']) ?> <?= status_badge($it['claim_status']) ?>
      <?php if ($it['approval_status'] !== 'Approved'): ?><?= status_badge($it['approval_status']) ?><?php endif; ?></div>
    <h1 class="h3"><?= e($it['item_name']) ?></h1>
    <dl class="row small">
      <dt class="col-4">Category</dt><dd class="col-8"><?= e($it['category_name']) ?></dd>
      <dt class="col-4">Location</dt><dd class="col-8"><?= e($it['location_name']) ?></dd>
      <dt class="col-4">Date</dt><dd class="col-8"><?= e(date('F d, Y', strtotime($it['date_happened']))) ?></dd>
      <dt class="col-4">Posted by</dt><dd class="col-8"><?= e($it['reporter_name']) ?></dd>
    </dl>
    <p><?= nl2br(e($it['description'])) ?></p>

    <?php if ($canManage): ?>
      <a class="btn btn-sm btn-outline-primary" href="<?= url('item_form.php?id=' . (int)$it['item_id']) ?>">Edit report</a>
    <?php endif; ?>

    <hr>
    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>

    <?php if ($it['claim_status'] === 'Claimed'): ?>
        <div class="alert alert-success">This item has been claimed.</div>
    <?php elseif (!$me): ?>
        <div class="alert alert-info">Is this yours? <a href="<?= url('login.php') ?>">Log in</a> to submit a claim.</div>
    <?php elseif ($it['reporter_id'] == $me['user_id']): ?>
        <div class="alert alert-secondary">This is your own report.</div>
    <?php elseif ($myClaim): ?>
        <div class="alert alert-info">Your claim is <strong><?= e($myClaim['claim_state']) ?></strong>.</div>
    <?php elseif ($it['approval_status'] === 'Approved'): ?>
        <h2 class="h6">Claim this item</h2>
        <form method="post" class="needs-validation" novalidate>
          <?= csrf_field() ?><input type="hidden" name="action" value="claim">
          <textarea class="form-control mb-2" name="proof_details" rows="3" minlength="15" required
              placeholder="Describe proof of ownership (unique marks, contents, serial number...)"></textarea>
          <div class="invalid-feedback">At least 15 characters.</div>
          <button class="btn btn-warning">Submit claim</button>
        </form>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
