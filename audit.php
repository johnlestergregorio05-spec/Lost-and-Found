<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('Admin');

$action = trim($_GET['action'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;

$where = []; $params = [];
if ($action !== '') { $where[] = 'a.action = ?'; $params[] = $action; }
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'a.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $where[] = 'a.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a $w");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT a.*, u.full_name, u.email FROM audit_logs a
    LEFT JOIN users u ON u.user_id = a.user_id $w ORDER BY a.created_at DESC, a.log_id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();
$actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
$qs = fn(int $p) => http_build_query(array_filter(['action' => $action, 'from' => $from, 'to' => $to, 'page' => $p]));

$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Audit Logs</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-12 col-md-3">
    <select name="action" class="form-select"><option value="">All actions</option>
      <?php foreach ($actions as $a): ?><option <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
  <div class="col-6 col-md-3"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
  <div class="col-12 col-md-3"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="<?= url('admin/audit.php') ?>">Reset</a></div>
</form>

<div class="table-responsive card card-body shadow-sm">
<table class="table table-sm align-middle mb-0">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th></tr></thead>
  <tbody>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td class="text-nowrap small"><?= e($l['created_at']) ?></td>
      <td class="small"><?= e($l['full_name'] ?? 'System / guest') ?></td>
      <td><span class="badge bg-dark"><?= e($l['action']) ?></span></td>
      <td class="small"><?= e($l['entity']) ?><?= $l['entity_id'] ? ' #' . (int)$l['entity_id'] : '' ?></td>
      <td class="small"><?= e($l['details']) ?></td>
      <td class="small"><?= e($l['ip_address']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted">No log entries.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php if ($pages > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
  <?php for ($p = 1; $p <= $pages; $p++): ?>
    <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="?<?= e($qs($p)) ?>"><?= $p ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
