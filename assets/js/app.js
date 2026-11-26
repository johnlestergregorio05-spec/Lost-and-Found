// Client-side form validation (Bootstrap), confirm dialogs, photo preview
(function () {
  'use strict';

  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var pw = form.querySelector('#password'), pw2 = form.querySelector('#password2');
      if (pw && pw2) {
        pw2.setCustomValidity(pw.value !== pw2.value ? 'Passwords must match.' : '');
      }
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });

  // <form data-confirm="..."> asks before submitting
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
  // <button data-confirm-btn="..."> asks before submitting its form
  document.querySelectorAll('button[data-confirm-btn]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!window.confirm(btn.getAttribute('data-confirm-btn'))) e.preventDefault();
    });
  });

  // Photo preview + size/type check
  var photo = document.getElementById('photo'), preview = document.getElementById('preview');
  if (photo && preview) {
    photo.addEventListener('change', function () {
      var f = photo.files[0];
      if (!f) return;
      if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) {
        alert('Only JPG, PNG or WEBP images are allowed.'); photo.value = ''; return;
      }
      if (f.size > 2 * 1024 * 1024) {
        alert('Photo must be 2 MB or smaller.'); photo.value = ''; return;
      }
      preview.src = URL.createObjectURL(f);
      preview.classList.remove('d-none');
    });
  }
})();
