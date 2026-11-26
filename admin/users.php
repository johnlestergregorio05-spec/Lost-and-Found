<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('Admin');
$me = current_user();
$errors = [];

$roles = $pdo->query('SELECT * FROM roles ORDER BY role_id')->fetchAll();
$roleIds = array_column($roles, 'role_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($action === 'create') {
        $sid = trim($_POST['school_id_no'] ?? ''); $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? ''); $role = (int)($_POST['role_id'] ?? 0); $pw = $_POST['password'] ?? '';
        if ($sid === '' || strlen($name) < 3) $errors[] = 'School ID and a full name (3+ chars) are required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
        if (!in_array($role, array_map('intval', $roleIds), true)) $errors[] = 'Choose a role.';
        if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!$errors) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email=? OR school_id_no=?');
            $c->execute([$email, $sid]);
            if ($c->fetchColumn() > 0) $errors[] = 'Email or School ID already exists.';
        }
        if (!$errors) {
            $pdo->prepare('INSERT INTO users (role_id, school_id_no, full_name, email, password_hash) VALUES (?,?,?,?,?)')
                ->execute([$role, $sid, $name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            audit('CREATE', 'user', (int)$pdo->lastInsertId(), 'Admin created account: ' . $email);
            flash('success', 'User created.'); redirect('admin/users.php');
        }
    } elseif ($uid && $uid !== (int)$me['user_id']) {   // admins cannot change/delete themselves here
        if ($action === 'set_role') {
            $role = (int)($_POST['role_id'] ?? 0);
            if (in_array($role, array_map('intval', $roleIds), true)) {
                $pdo->prepare('UPDATE users SET role_id=? WHERE user_id=?')->execute([$role, $uid]);
                audit('ROLE_CHANGE', 'user', $uid, 'Role changed to role_id ' . $role); flash('success', 'Role updated.');
            }
        } elseif ($action === 'toggle') {
            $pdo->prepare("UPDATE users SET account_status = IF(account_status='Active','Inactive','Active') WHERE user_id=?")->execute([$uid]);
            audit('STATUS_CHANGE', 'user', $uid, 'Toggled account status'); flash('success', 'Account status updated.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE user_id=?')->execute([$uid]);
            audit('DELETE', 'user', $uid, 'Deleted user account'); flash('success', 'User deleted.');
        }
        redirect('admin/users.php');
    } elseif ($uid === (int)$me['user_id']) {
        flash('warning', 'You cannot modify your own account from this page.'); redirect('admin/users.php');
    }
}

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id=u.role_id';
$params = [];
if ($q !== '') { $sql .= ' WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.school_id_no LIKE ?'; $params = ["%$q%", "%$q%", "%$q%"]; }
$stmt = $pdo->prepare($sql . ' ORDER BY u.role_id, u.full_name');
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">User Management</h1>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>

<div class="card shadow-sm mb-4"><div class="card-body">
  <h2 class="h6">Add user</h2>
  <form method="post" class="row g-2 needs-validation" novalidate>
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="col-md-2"><input class="form-control" name="school_id_no" placeholder="School ID" required></div>
    <div class="col-md-3"><input class="form-control" name="full_name" placeholder="Full name" minlength="3" required></div>
    <div class="col-md-3"><input type="email" class="form-control" name="email" placeholder="Email" required></div>
    <div class="col-md-2"><input type="password" class="form-control" name="password" placeholder="Password (8+)" minlength="8" required></div>
    <div class="col-md-1">
      <select name="role_id" class="form-select" required>
        <?php foreach ($roles as $r): ?><option value="<?= (int)$r['role_id'] ?>" <?= $r['role_name'] === 'User' ? 'selected' : '' ?>><?= e($r['role_name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
  </form>
</div></div>

<form class="row g-2 mb-3" method="get">
  <div class="col-9 col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, email or ID"></div>
  <div class="col-3 col-md-2"><button class="btn btn-outline-primary w-100">Search</button></div>
</form>

<div class="table-responsive card card-body shadow-sm">
<table class="table align-middle mb-0">
  <thead><tr><th>Name</th><th>School ID</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): $self = $u['user_id'] == $me['user_id']; ?>
    <tr>
      <td><?= e($u['full_name']) ?><?= $self ? ' <span class="badge bg-info">you</span>' : '' ?></td>
      <td><?= e($u['school_id_no']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td>
        <?php if ($self): ?><?= e($u['role_name']) ?>
        <?php else: ?>
        <form method="post" class="d-flex gap-1">
          <?= csrf_field() ?><input type="hidden" name="action" value="set_role"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <select name="role_id" class="form-select form-select-sm">
            <?php foreach ($roles as $r): ?><option value="<?= (int)$r['role_id'] ?>" <?= $r['role_id'] == $u['role_id'] ? 'selected' : '' ?>><?= e($r['role_name']) ?></option><?php endforeach; ?>
          </select>
          <button class="btn btn-sm btn-outline-primary">Save</button>
        </form>
        <?php endif; ?>
      </td>
      <td><span class="badge bg-<?= $u['account_status'] === 'Active' ? 'success' : 'secondary' ?>"><?= e($u['account_status']) ?></span></td>
      <td class="text-end">
        <?php if (!$self): ?>
        <form method="post" class="d-inline-flex gap-1">
          <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
          <button name="action" value="toggle" class="btn btn-sm btn-outline-secondary"><?= $u['account_status'] === 'Active' ? 'Deactivate' : 'Activate' ?></button>
          <button name="action" value="delete" class="btn btn-sm btn-outline-danger" data-confirm-btn="Delete this user and all of their reports and claims?">Delete</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
