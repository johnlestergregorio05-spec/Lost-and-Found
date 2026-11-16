<?php
$pageTitle = 'Browse Items';
$fullWidth = true;
$extraScripts = ['assets/js/browse.js'];
require_once __DIR__ . '/includes/header.php';
$cats = get_categories();
$locs = get_locations();
$g = fn(string $k): string => (string)($_GET[$k] ?? '');
?>
    <section class="section wrap">
      <div class="section-head">
        <h2>Browse items</h2>
        <p>Results update as you type or change a filter.</p>
      </div>

      <form id="filterForm" class="search search-6" autocomplete="off">
        <div class="field">
          <label for="type">Type</label>
          <select id="type" name="type">
            <option value="">Lost and found</option>
            <option value="Lost" <?= $g('type') === 'Lost' ? 'selected' : '' ?>>Lost</option>
            <option value="Found" <?= $g('type') === 'Found' ? 'selected' : '' ?>>Found</option>
          </select>
        </div>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">Any status</option>
            <option value="Unclaimed" <?= $g('status') === 'Unclaimed' ? 'selected' : '' ?>>Unclaimed</option>
            <option value="Claimed" <?= $g('status') === 'Claimed' ? 'selected' : '' ?>>Claimed</option>
          </select>
        </div>
        <div class="field">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int)$c['category_id'] ?>" <?= $g('category') == $c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="location">Location</label>
          <select id="location" name="location">
            <option value="">Anywhere on campus</option>
            <?php foreach ($locs as $l): ?><option value="<?= (int)$l['location_id'] ?>" <?= $g('location') == $l['location_id'] ? 'selected' : '' ?>><?= e($l['location_name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field field-wide">
          <label for="q">Keyword</label>
          <input id="q" name="q" type="search" value="<?= e($g('q')) ?>" placeholder="Black backpack, calculator, ID">
        </div>
        <button class="btn btn-ghost" type="reset">Clear</button>
      </form>

      <div id="results"><p class="results-count">Loading...</p></div>
    </section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
