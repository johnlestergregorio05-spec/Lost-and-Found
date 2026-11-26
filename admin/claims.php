<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('Admin', 'Staff');
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $claimId = (int)($_POST['claim_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $s = $pdo->prepare('SELECT * FROM claims WHERE claim_id = ?');
    $s->execute([$claimId]);
    $cl = $s->fetch();

    if ($cl && $cl['claim_state'] === 'Pending' && in_array($decision, ['Approved', 'Rejected'], true)) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE claims SET claim_state=?, reviewed_by=?, reviewed_at=NOW() WHERE claim_id=?')
                ->execute([$decision, $me['user_id'], $claimId]);
            if ($decision === 'Approved') {
                $pdo->prepare("UPDATE items SET claim_status='Claimed' WHERE item_id=?")->execute([$cl['item_id']]);
                // Other pending claims on the same item are rejected automatically
                $pdo->prepare("UPDATE claims SET claim_state='Rejected', reviewed_by=?, reviewed_at=NOW()
                               WHERE item_id=? AND claim_id<>? AND claim_state='Pending'")
                    ->execute([$me['user_id'], $cl['item_id'], $claimId]);
            }
            $pdo->commit();
            audit($decision === 'Approved' ? 'CLAIM_APPROVE' : 'CLAIM_REJECT', 'claim', $claimId, $decision . ' claim for item #' . $cl['item_id']);
            flash('success', 'Claim ' . strtolower($decision) . '.');
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('danger', 'Could not save the decision. Try again.');
        }
    }
    redirect('admin/claims.php');
}

$rows = $pdo->query("SELECT cl.*, i.item_name, i.claim_status, u.full_name AS claimant, u.contact_no, u.school_id_no, rv.full_name AS reviewer
    FROM claims cl
    JOIN items i ON i.item_id = cl.item_id
    JOIN users u ON u.user_id = cl.claimant_id
    LEFT JOIN users rv ON rv.user_id = cl.reviewed_by
    ORDER BY (cl.claim_state='Pending') DESC, cl.created_at DESC")->fetchAll();

$pageTitle = 'Claim Requests';
require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Claim Requests</h1>
<div class="table-responsive card card-body shadow-sm">
<table class="table align-middle mb-0">
  <thead><tr><th>Item</th><th>Claimant</th><th>Proof</th><th>Status</th><th class="text-end">Decision</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('item_view.php?id=' . (int)$r['item_id']) ?>"><?= e($r['item_name']) ?></a></td>
      <td><?= e($r['claimant']) ?><div class="small text-muted"><?= e($r['school_id_no']) ?> &middot; <?= e($r['contact_no'] ?? 'no contact') ?></div></td>
      <td class="small" style="max-width:320px"><?= nl2br(e($r['proof_details'])) ?></td>
      <td><?= status_badge($r['claim_state']) ?>
          <?php if ($r['reviewer']): ?><div class="small text-muted">by <?= e($r['reviewer']) ?></div><?php endif; ?></td>
      <td class="text-end">
        <?php if ($r['claim_state'] === 'Pending'): ?>
        <form method="post" class="d-inline-flex gap-1">
          <?= csrf_field() ?><input type="hidden" name="claim_id" value="<?= (int)$r['claim_id'] ?>">
          <button name="decision" value="Approved" class="btn btn-sm btn-success" data-confirm-btn="Approve this claim and mark the item as Claimed?">Approve</button>
          <button name="decision" value="Rejected" class="btn btn-sm btn-outline-danger">Reject</button>
        </form>
        <?php else: ?><span class="text-muted small">Done</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted">No claim requests yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
