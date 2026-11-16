<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$me = current_user();

$s = $pdo->prepare("SELECT cl.*, i.item_name, i.report_type FROM claims cl
    JOIN items i ON i.item_id = cl.item_id
    WHERE cl.claimant_id = ? ORDER BY cl.created_at DESC");
$s->execute([$me['user_id']]);
$rows = $s->fetchAll();

$pageTitle = 'My Claims';
require_once __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">My Claims</h1>
<div class="table-responsive card card-body shadow-sm">
<table class="table align-middle mb-0">
  <thead><tr><th>Item</th><th>Proof submitted</th><th>Status</th><th>Date</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('item_view.php?id=' . (int)$r['item_id']) ?>"><?= e($r['item_name']) ?></a></td>
      <td class="small"><?= e(mb_strimwidth($r['proof_details'], 0, 90, '...')) ?></td>
      <td><?= status_badge($r['claim_state']) ?></td>
      <td class="small"><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted">No claims yet. Open an item and submit proof of ownership.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
