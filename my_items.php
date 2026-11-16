<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$me = current_user();

// Delete own report (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $id = (int)($_POST['item_id'] ?? 0);
    $s = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
    $s->execute([$id]);
    $it = $s->fetch();
    if ($it && ($it['reporter_id'] == $me['user_id'] || has_role('Admin', 'Staff'))) {
        $pdo->prepare('DELETE FROM items WHERE item_id = ?')->execute([$id]);
        delete_photo($it['photo_path']);
        audit('DELETE', 'item', $id, 'Deleted item report: ' . $it['item_name']);
        flash('success', 'Report deleted.');
    } else {
        flash('danger', 'You cannot delete that report.');
    }
    redirect('my_items.php');
}

$s = $pdo->prepare("SELECT i.*, c.category_name, l.location_name FROM items i
    JOIN categories c ON c.category_id=i.category_id JOIN locations l ON l.location_id=i.location_id
    WHERE i.reporter_id = ? ORDER BY i.created_at DESC");
$s->execute([$me['user_id']]);
$rows = $s->fetchAll();

$pageTitle = 'My Reports';
require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">My Reports</h1>
  <a href="<?= url('item_form.php') ?>" class="btn btn-primary">+ Report Item</a>
</div>
<div class="table-responsive card card-body shadow-sm">
<table class="table align-middle mb-0">
  <thead><tr><th>Item</th><th>Type</th><th>Category</th><th>Location</th><th>Approval</th><th>Claim</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('item_view.php?id=' . (int)$r['item_id']) ?>"><?= e($r['item_name']) ?></a></td>
      <td><?= status_badge($r['report_type']) ?></td>
      <td><?= e($r['category_name']) ?></td>
      <td><?= e($r['location_name']) ?></td>
      <td><?= status_badge($r['approval_status']) ?></td>
      <td><?= status_badge($r['claim_status']) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="<?= url('item_form.php?id=' . (int)$r['item_id']) ?>">Edit</a>
        <form method="post" class="d-inline" data-confirm="Delete this report permanently?">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="item_id" value="<?= (int)$r['item_id'] ?>">
          <button class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted">You have not posted any reports yet.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
