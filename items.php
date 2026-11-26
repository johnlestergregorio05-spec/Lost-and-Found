<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('Admin', 'Staff');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $s = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
    $s->execute([$id]);
    $it = $s->fetch();

    if ($it) {
        switch ($action) {
            case 'approve':
                $pdo->prepare("UPDATE items SET approval_status='Approved' WHERE item_id=?")->execute([$id]);
                audit('APPROVE', 'item', $id, 'Approved: ' . $it['item_name']); flash('success', 'Report approved.'); break;
            case 'reject':
                $pdo->prepare("UPDATE items SET approval_status='Rejected' WHERE item_id=?")->execute([$id]);
                audit('REJECT', 'item', $id, 'Rejected: ' . $it['item_name']); flash('warning', 'Report rejected.'); break;
            case 'set_claimed':
                $pdo->prepare("UPDATE items SET claim_status='Claimed' WHERE item_id=?")->execute([$id]);
                audit('STATUS_CHANGE', 'item', $id, 'Marked Claimed: ' . $it['item_name']); flash('success', 'Marked as Claimed.'); break;
            case 'set_unclaimed':
                $pdo->prepare("UPDATE items SET claim_status='Unclaimed' WHERE item_id=?")->execute([$id]);
                audit('STATUS_CHANGE', 'item', $id, 'Marked Unclaimed: ' . $it['item_name']); flash('success', 'Marked as Unclaimed.'); break;
            case 'delete':
                $pdo->prepare('DELETE FROM items WHERE item_id=?')->execute([$id]);
                delete_photo($it['photo_path']);
                audit('DELETE', 'item', $id, 'Deleted: ' . $it['item_name']); flash('success', 'Report deleted.'); break;
        }
    }
    redirect('admin/items.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}

$approval = $_GET['approval'] ?? '';
$q = trim($_GET['q'] ?? '');
$where = []; $params = [];
if (in_array($approval, ['Pending', 'Approved', 'Rejected'], true)) { $where[] = 'i.approval_status = ?'; $params[] = $approval; }
if ($q !== '') { $where[] = '(i.item_name LIKE ? OR u.full_name LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT i.*, c.category_name, l.location_name, u.full_name AS reporter
    FROM items i JOIN categories c ON c.category_id=i.category_id
    JOIN locations l ON l.location_id=i.location_id JOIN users u ON u.user_id=i.reporter_id
    $w ORDER BY (i.approval_status='Pending') DESC, i.created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();
$back = http_build_query(array_filter(['approval' => $approval, 'q' => $q]));

$pageTitle = 'All Item Reports';
require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">All Item Reports</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search item or reporter"></div>
  <div class="col-8 col-md-3">
    <select name="approval" class="form-select">
      <option value="">All approval states</option>
      <?php foreach (['Pending', 'Approved', 'Rejected'] as $a): ?><option <?= $approval === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-4 col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
</form>

<div class="table-responsive card card-body shadow-sm">
<table class="table align-middle mb-0">
  <thead><tr><th>Item</th><th>Reporter</th><th>Type</th><th>Approval</th><th>Claim</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><a href="<?= url('item_view.php?id=' . (int)$r['item_id']) ?>"><?= e($r['item_name']) ?></a>
          <div class="small text-muted"><?= e($r['category_name']) ?> &middot; <?= e($r['location_name']) ?></div></td>
      <td><?= e($r['reporter']) ?></td>
      <td><?= status_badge($r['report_type']) ?></td>
      <td><?= status_badge($r['approval_status']) ?></td>
      <td><?= status_badge($r['claim_status']) ?></td>
      <td class="text-end">
        <form method="post" class="d-inline-flex flex-wrap gap-1 justify-content-end" <?= '' ?>>
          <?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int)$r['item_id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
          <?php if ($r['approval_status'] !== 'Approved'): ?><button name="action" value="approve" class="btn btn-sm btn-success">Approve</button><?php endif; ?>
          <?php if ($r['approval_status'] !== 'Rejected'): ?><button name="action" value="reject" class="btn btn-sm btn-outline-warning">Reject</button><?php endif; ?>
          <?php if ($r['claim_status'] === 'Unclaimed'): ?><button name="action" value="set_claimed" class="btn btn-sm btn-outline-success">Mark Claimed</button>
          <?php else: ?><button name="action" value="set_unclaimed" class="btn btn-sm btn-outline-secondary">Mark Unclaimed</button><?php endif; ?>
          <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm-btn="Delete this report permanently?">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">No reports found.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
