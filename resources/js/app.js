document.addEventListener('DOMContentLoaded', function() {
  const toggle = document.getElementById('mobile-menu-toggle');
  const panel = document.getElementById('mobile-menu-panel');
  if (toggle && panel) {
    toggle.addEventListener('click', function() {
      panel.classList.toggle('hidden');
    });
  }
  // Close when clicking on the overlay background (outside inner content)
  if (panel) {
    panel.addEventListener('click', function(e) {
      if (e.target === panel) panel.classList.add('hidden');
    });
  }
  // Theme toggle logic (3-state: system, light, dark)
  const root = document.documentElement;
  const themeToggle = document.getElementById('theme-toggle');
  const iconSystem = document.getElementById('icon-system');
  const iconLight = document.getElementById('icon-light');
  const iconDark = document.getElementById('icon-dark');

  function showIcon(which) {
    if (iconSystem) iconSystem.classList.toggle('hidden', which !== 'system');
    if (iconLight) iconLight.classList.toggle('hidden', which !== 'light');
    if (iconDark) iconDark.classList.toggle('hidden', which !== 'dark');
  }

  function applyTheme(mode) {
    if (mode === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    localStorage.setItem('theme', mode);
    showIcon(mode);
  }

  let saved = localStorage.getItem('theme');
  if (!['system','light','dark'].includes(saved)) {
    saved = 'system';
  }
  applyTheme(saved);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = localStorage.getItem('theme') || 'system';
      const next = current === 'system' ? 'light' : current === 'light' ? 'dark' : 'system';
      applyTheme(next);
    });
  }
});
