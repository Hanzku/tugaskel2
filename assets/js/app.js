'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('appSidebar');
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebarClose = document.getElementById('sidebarClose');
  const backdrop = document.getElementById('sidebarBackdrop');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('is-open');
    if (backdrop) backdrop.classList.add('is-active');
    if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'true');
    document.body.classList.add('drawer-open');
    if (sidebarClose) {
      setTimeout(() => sidebarClose.focus(), 50);
    }
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('is-open');
    if (backdrop) backdrop.classList.remove('is-active');
    if (sidebarToggle) {
      sidebarToggle.setAttribute('aria-expanded', 'false');
      sidebarToggle.focus();
    }
    document.body.classList.remove('drawer-open');
  }

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      if (sidebar && sidebar.classList.contains('is-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });
  }

  if (sidebarClose) {
    sidebarClose.addEventListener('click', closeSidebar);
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
      closeSidebar();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900 && sidebar && sidebar.classList.contains('is-open')) {
      sidebar.classList.remove('is-open');
      if (backdrop) backdrop.classList.remove('is-active');
      if (sidebarToggle) sidebarToggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('drawer-open');
    }
  });
});

document.addEventListener('submit', (event) => {
  const button = event.submitter;
  if (button instanceof HTMLButtonElement && button.dataset.confirm) {
    if (!window.confirm(button.dataset.confirm)) {
      event.preventDefault();
    }
  }
});
