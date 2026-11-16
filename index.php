<?php
$pageTitle = 'Home';
$fullWidth = true;
require_once __DIR__ . '/includes/header.php';

$openCount = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE approval_status='Approved' AND claim_status='Unclaimed'")->fetchColumn();
$recent = $pdo->query("SELECT i.*, c.category_name, l.location_name
    FROM items i
    JOIN categories c ON c.category_id = i.category_id
    JOIN locations  l ON l.location_id = i.location_id
    WHERE i.approval_status = 'Approved' AND i.claim_status = 'Unclaimed'
    ORDER BY i.created_at DESC LIMIT 6")->fetchAll();
$cats = get_categories();
$locs = get_locations();
?>
    <section class="hero">
      <div class="wrap">
        <h1>Lost something on campus? Check here first.</h1>
        <p class="lead">Every item reported on campus is listed here. Search by what it is, where it was left, or what kind of item it is.</p>

        <form class="search" action="<?= url('browse.php') ?>" method="get">
          <div class="field">
            <label for="type">Status</label>
            <select id="type" name="type">
              <option value="">Lost and found</option>
              <option value="Lost">Lost</option>
              <option value="Found">Found</option>
            </select>
          </div>
          <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category">
              <option value="">All categories</option>
              <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>"><?= e($c['category_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="location">Location</label>
            <select id="location" name="location">
              <option value="">Anywhere on campus</option>
              <?php foreach ($locs as $l): ?><option value="<?= (int)$l['location_id'] ?>"><?= e($l['location_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field field-wide">
            <label for="q">Keyword</label>
            <input id="q" name="q" type="search" placeholder="Black backpack, calculator, ID">
          </div>
          <button class="btn btn-primary" type="submit">Search items</button>
        </form>
      </div>
    </section>

    <section id="browse" class="section wrap">
      <div class="section-head">
        <h2>Recently reported</h2>
        <p>Showing <?= count($recent) ?> of <?= $openCount ?> open items</p>
      </div>
      <div class="grid">
        <?php foreach ($recent as $it) echo render_item_card($it); ?>
      </div>
      <?php if (!$recent): ?><p class="empty-box">No open items yet. Be the first to report one.</p><?php endif; ?>
    </section>

    <section id="report" class="report">
      <div class="wrap report-inner">
        <div>
          <h2>Found or lost an item?</h2>
          <p>Log in and fill out a short report. Staff will check it before it appears in the public list.</p>
        </div>
        <a class="btn btn-primary" href="<?= url('item_form.php') ?>">Report an item</a>
      </div>
    </section>

    <section id="how" class="section wrap">
      <h2>How it works</h2>
      <ol class="steps">
        <li><strong>Report the item.</strong> Add a photo, where it was, and when.</li>
        <li><strong>Staff review the report.</strong> Approved reports go on the public list.</li>
        <li><strong>Claim with proof.</strong> Describe something only the owner would know.</li>
        <li><strong>Pick it up.</strong> Show your school ID at the office to get your item back.</li>
      </ol>
    </section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
