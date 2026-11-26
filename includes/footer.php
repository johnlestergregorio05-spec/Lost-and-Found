<?php if (empty($fullWidth)) echo '</div>'; ?>
  </main>

  <footer class="site-footer">
    <div class="wrap">
      <p><strong>Lost and Found Office</strong>, Student Affairs, Room 101</p>
      <p>Open Monday to Friday, 8:00 AM to 5:00 PM</p>
    </div>
  </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= url($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
