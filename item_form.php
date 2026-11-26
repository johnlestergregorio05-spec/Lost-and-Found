<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$me = current_user();
$isStaff = has_role('Admin', 'Staff');
$id = (int)($_GET['id'] ?? 0);
$item = null;

if ($id) {
    $s = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
    $s->execute([$id]);
    $item = $s->fetch();
    if (!$item) { flash('danger', 'Item not found.'); redirect('my_items.php'); }
    if ($item['reporter_id'] != $me['user_id'] && !$isStaff) {
        flash('danger', 'You can only edit your own reports.'); redirect('my_items.php');
    }
}

$errors = [];
$f = [
    'report_type'  => $item['report_type']   ?? 'Lost',
    'item_name'    => $item['item_name']     ?? '',
    'description'  => $item['description']   ?? '',
    'category_id'  => $item['category_id']   ?? '',
    'location_id'  => $item['location_id']   ?? '',
    'date_happened'=> $item['date_happened'] ?? date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');

    if (!in_array($f['report_type'], ['Lost', 'Found'], true)) $errors[] = 'Choose Lost or Found.';
    if (strlen($f['item_name']) < 3 || strlen($f['item_name']) > 120) $errors[] = 'Item name must be 3 to 120 characters.';
    if (strlen($f['description']) < 10) $errors[] = 'Description must be at least 10 characters.';
    if (!(int)$f['category_id']) $errors[] = 'Choose a category.';
    if (!(int)$f['location_id']) $errors[] = 'Choose a location.';
    $d = DateTime::createFromFormat('Y-m-d', $f['date_happened']);
    if (!$d || $d->format('Y-m-d') !== $f['date_happened']) $errors[] = 'Enter a valid date.';
    elseif ($f['date_happened'] > date('Y-m-d')) $errors[] = 'Date cannot be in the future.';

    $newPhoto = null;
    if (!$errors) {
        try { $newPhoto = handle_photo_upload('photo'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }

    if (!$errors) {
        if ($item) {
            $photo = $newPhoto ?? $item['photo_path'];
            
            $approval = $isStaff ? $item['approval_status'] : 'Pending';
            $u = $pdo->prepare('UPDATE items SET report_type=?, item_name=?, description=?, category_id=?, location_id=?, date_happened=?, photo_path=?, approval_status=? WHERE item_id=?');
            $u->execute([$f['report_type'], $f['item_name'], $f['description'], (int)$f['category_id'], (int)$f['location_id'], $f['date_happened'], $photo, $approval, $id]);
            if ($newPhoto) delete_photo($item['photo_path']);
            audit('UPDATE', 'item', $id, 'Edited item report: ' . $f['item_name']);
            flash('success', 'Report updated.');
        } else {
            $approval = $isStaff ? 'Approved' : 'Pending';
            $i = $pdo->prepare('INSERT INTO items (reporter_id, category_id, location_id, report_type, item_name, description, date_happened, photo_path, approval_status) VALUES (?,?,?,?,?,?,?,?,?)');
            $i->execute([$me['user_id'], (int)$f['category_id'], (int)$f['location_id'], $f['report_type'], $f['item_name'], $f['description'], $f['date_happened'], $newPhoto, $approval]);
            $newId = (int)$pdo->lastInsertId();
            audit('CREATE', 'item', $newId, 'Posted ' . $f['report_type'] . ' item: ' . $f['item_name']);
            flash('success', $isStaff ? 'Report posted.' : 'Report submitted. It will appear publicly once staff approves it.');
        }
        redirect('my_items.php');
    }
}

$pageTitle = $item ? 'Edit Report' : 'Report an Item';
require_once __DIR__ . '/includes/header.php';
$cats = get_categories(); $locs = get_locations();
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card shadow-sm"><div class="card-body p-4">
  <h1 class="h4 mb-3"><?= e($pageTitle) ?></h1>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <?= csrf_field() ?>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Report type</label>
        <select name="report_type" class="form-select" required>
          <option value="Lost"  <?= $f['report_type'] === 'Lost'  ? 'selected' : '' ?>>I lost an item</option>
          <option value="Found" <?= $f['report_type'] === 'Found' ? 'selected' : '' ?>>I found an item</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Item name</label>
        <input class="form-control" name="item_name" minlength="3" maxlength="120" value="<?= e($f['item_name']) ?>" required>
        <div class="invalid-feedback">3 to 120 characters.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select" required>
          <option value="">Choose...</option>
          <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $f['category_id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Choose a category.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Location</label>
        <select name="location_id" class="form-select" required>
          <option value="">Choose...</option>
          <?php foreach ($locs as $l): ?><option value="<?= (int)$l['location_id'] ?>" <?= $f['location_id'] == $l['location_id'] ? 'selected' : '' ?>><?= e($l['location_name']) ?></option><?php endforeach; ?>
        </select>
        <div class="invalid-feedback">Choose a location.</div>
      </div>
      <div class="col-md-4">
        <label class="form-label">Date lost / found</label>
        <input type="date" class="form-control" name="date_happened" max="<?= date('Y-m-d') ?>" value="<?= e($f['date_happened']) ?>" required>
        <div class="invalid-feedback">Pick a valid date (not in the future).</div>
      </div>
      <div class="col-12">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="4" minlength="10" required><?= e($f['description']) ?></textarea>
        <div class="form-text">Color, brand, marks, contents. For found items, avoid revealing details only the owner would know.</div>
        <div class="invalid-feedback">At least 10 characters.</div>
      </div>
      <div class="col-12">
        <label class="form-label">Photo (JPG, PNG, WEBP, max 2 MB)</label>
        <input type="file" class="form-control" name="photo" id="photo" accept="image/jpeg,image/png,image/webp">
        <?php if ($item && $item['photo_path']): ?>
          <img src="<?= e(photo_url($item['photo_path'])) ?>" class="mt-2 rounded" style="max-height:120px" alt="Current photo" id="preview">
        <?php else: ?>
          <img src="" class="mt-2 rounded d-none" style="max-height:120px" alt="Preview" id="preview">
        <?php endif; ?>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary"><?= $item ? 'Save changes' : 'Submit report' ?></button>
      <a href="<?= url('my_items.php') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div></div>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
