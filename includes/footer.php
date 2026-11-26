<?php if (empty($fullWidth)) echo '</div>'; ?>
  </main>

  <footer class="site-footer">
    <div class="wrap">
      <p><strong>About TraceIT</strong>, Open Monday to Friday, 8:00 AM to 5:00 PM</p>
      <p><strong>Designed by:</strong> John Lester Gregorio, Rommel Barammeda, Josiah Eros Sison.</p>
    </div>
  </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $s): ?>
<script src="<?= url($s) ?>"></script>
<?php endforeach; ?>
</body>
</html>
