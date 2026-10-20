// assets/js/main.js — Poultry Haven shared front-end behaviour
document.addEventListener('DOMContentLoaded', () => {
  /* ---------- Confirm destructive actions ---------- */
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm)) {
        event.preventDefault();
      }
    });
  });

  /* ---------- Public mobile navigation ---------- */
  const menuBtn = document.getElementById('menuBtn');
  const mobileMenu = document.getElementById('mobileMenu');
  if (menuBtn && mobileMenu) {
    menuBtn.addEventListener('click', () => {
      const isHidden = mobileMenu.classList.toggle('hidden');
      menuBtn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
    });
  }

  /* ---------- Image preview before upload ---------- */
  document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const preview = document.getElementById(input.dataset.preview);
      if (!preview) return;
      const file = input.files && input.files[0];
      if (!file) {
        preview.classList.add('hidden');
        preview.removeAttribute('src');
        return;
      }
      preview.src = URL.createObjectURL(file);
      preview.classList.remove('hidden');
    });
  });

  /* ---------- Fallback when a picture file is missing ---------- */
  document.querySelectorAll('img').forEach((img) => {
    img.addEventListener('error', () => {
      if (img.dataset.fallbackApplied === '1') return;
      img.dataset.fallbackApplied = '1';
      img.src = img.getAttribute('data-fallback') || '';
    });
  });

  /* ---------- Submit the filter form when a dropdown changes ---------- */
  document.querySelectorAll('form[data-autosubmit] select').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });

  /* ---------- Fade-in on scroll for sections ---------- */
  const revealTargets = document.querySelectorAll('[data-reveal]');
  if (revealTargets.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('fade-in');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    revealTargets.forEach((el) => observer.observe(el));
  }
});

/* ---------- Small notification helper ---------- */
function showNotice(msg, type = 'success') {
  const n = document.createElement('div');
  n.className = 'fixed top-20 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white ' +
    (type === 'error' ? 'bg-red-600' : 'bg-green-700');
  n.textContent = msg;
  document.body.appendChild(n);
  setTimeout(() => n.remove(), 3000);
}
