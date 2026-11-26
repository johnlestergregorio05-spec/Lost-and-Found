// AJAX search + filtering for the Browse page (no page reload)
(function () {
  'use strict';
  var form = document.getElementById('filterForm');
  var box = document.getElementById('results');
  var timer = null, page = 1, controller = null;

  function load() {
    var params = new URLSearchParams(new FormData(form));
    params.set('page', page);
    if (controller) controller.abort();
    controller = new AbortController();

    fetch('ajax_search.php?' + params.toString(), { signal: controller.signal })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
      .then(function (html) { box.innerHTML = html; })
      .catch(function (err) {
        if (err.name === 'AbortError') return;
        box.innerHTML = '<div class="col-12"><div class="alert alert-danger">Could not load items. Please try again.</div></div>';
      });
  }

  form.addEventListener('submit', function (e) { e.preventDefault(); });
  form.addEventListener('input', function (e) {
    page = 1;
    clearTimeout(timer);
    timer = setTimeout(load, e.target.type === 'search' ? 300 : 0);   // debounce typing
  });
  form.addEventListener('reset', function () { page = 1; setTimeout(load, 0); });

  box.addEventListener('click', function (e) {
    var a = e.target.closest('a[data-page]');
    if (!a) return;
    e.preventDefault();
    page = parseInt(a.getAttribute('data-page'), 10) || 1;
    load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  load();
})();
