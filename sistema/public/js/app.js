function initSidebarToggle() {
  const sidebar = document.getElementById('sidebar');
  if (!sidebar) return;

  const overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  document.body.appendChild(overlay);

  const isDesktop = () => window.innerWidth >= 992;
  let wasDesktop = isDesktop();

  if (wasDesktop && document.documentElement.dataset.sidebarDefaultCollapsed === 'true') {
    sidebar.classList.add('sidebar-collapsed');
  }

  const closeMobile = () => {
    sidebar.classList.remove('sidebar-open');
    overlay.classList.remove('show');
  };

  document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (isDesktop()) {
        sidebar.classList.toggle('sidebar-collapsed');
      } else {
        if (sidebar.classList.contains('sidebar-open')) {
          closeMobile();
        } else {
          sidebar.classList.add('sidebar-open');
          overlay.classList.add('show');
        }
      }
    });
  });

  document.getElementById('sidebarClose')?.addEventListener('click', closeMobile);
  overlay.addEventListener('click', closeMobile);

  window.addEventListener('resize', () => {
    const isDesktopNow = isDesktop();

    if (isDesktopNow !== wasDesktop) {
      sidebar.classList.toggle(
        'sidebar-collapsed',
        isDesktopNow && document.documentElement.dataset.sidebarDefaultCollapsed === 'true'
      );
      closeMobile();
      wasDesktop = isDesktopNow;
    }
  });
}

function initAccountPreferences() {
  const root = document.documentElement;
  const theme = root.dataset.userTheme;
  const systemTheme = window.matchMedia?.('(prefers-color-scheme: dark)');
  const applyTheme = () => {
    const useDarkTheme = theme === 'dark' || (theme === 'system' && systemTheme?.matches);
    root.setAttribute('data-bs-theme', useDarkTheme ? 'dark' : 'light');
  };

  applyTheme();
  if (theme === 'system' && systemTheme) {
    systemTheme.addEventListener?.('change', applyTheme);
  }

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.querySelector(button.dataset.target);
      if (!input) return;

      const visible = input.type === 'text';
      input.type = visible ? 'password' : 'text';
      button.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
      const icon = button.querySelector('i');
      if (icon) icon.className = `bi ${visible ? 'bi-eye' : 'bi-eye-slash'} text-secondary`;
    });
  });

  const password = document.querySelector('[data-new-password]');
  const confirmation = document.querySelector('[data-confirm-password]');
  const lengthRequirement = document.querySelector('[data-password-requirement-length]');
  const matchRequirement = document.querySelector('[data-password-requirement-match]');

  if (password && confirmation) {
    const updatePasswordRequirements = () => {
      lengthRequirement?.classList.toggle('is-met', password.value.length >= 8);
      matchRequirement?.classList.toggle(
        'is-met',
        confirmation.value.length > 0 && password.value === confirmation.value
      );
    };

    password.addEventListener('input', updatePasswordRequirements);
    confirmation.addEventListener('input', updatePasswordRequirements);
  }

  const photoInput = document.querySelector('[data-profile-photo-input]');
  const photoPreview = document.querySelector('[data-profile-photo-preview]');
  const photoPlaceholder = document.querySelector('[data-profile-photo-placeholder]');
  const removePhoto = document.querySelector('[data-remove-profile-photo]');
  const photoError = document.querySelector('[data-profile-photo-error]');
  let previewUrl = null;

  if (photoInput && photoPreview && photoPlaceholder) {
    const originalSource = photoPreview.dataset.originalSrc;
    const updatePhotoPreview = () => {
      if (removePhoto?.checked) {
        photoPreview.classList.add('d-none');
        photoPlaceholder.classList.remove('d-none');
        return;
      }

      const file = photoInput.files?.[0];
      if (file && previewUrl) {
        photoPreview.src = previewUrl;
        photoPreview.classList.remove('d-none');
        photoPlaceholder.classList.add('d-none');
      } else if (originalSource) {
        photoPreview.src = originalSource;
        photoPreview.classList.remove('d-none');
        photoPlaceholder.classList.add('d-none');
      } else {
        photoPreview.classList.add('d-none');
        photoPlaceholder.classList.remove('d-none');
      }
    };

    photoInput.addEventListener('change', () => {
      const file = photoInput.files?.[0];
      if (!file) {
        updatePhotoPreview();
        return;
      }

      const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowedTypes.includes(file.type) || file.size > 2 * 1024 * 1024) {
        if (photoError) {
          photoError.textContent = 'Selecciona una imagen JPG, PNG o WebP de máximo 2 MB.';
          photoError.classList.remove('d-none');
        }
        photoInput.value = '';
        updatePhotoPreview();
        return;
      }

      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = URL.createObjectURL(file);
      if (photoError) photoError.classList.add('d-none');
      if (removePhoto) removePhoto.checked = false;
      updatePhotoPreview();
    });

    removePhoto?.addEventListener('change', updatePhotoPreview);
  }

  document.querySelectorAll('[data-confirm-preferences-reset]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm('¿Restaurar las preferencias predeterminadas?')) {
        event.preventDefault();
      }
    });
  });
}

function initPwaExperience() {
  let offlineNotice = null;
  const updateConnectionStatus = () => {
    if (navigator.onLine) {
      offlineNotice?.remove();
      offlineNotice = null;
      return;
    }

    if (!offlineNotice) {
      offlineNotice = document.createElement('div');
      offlineNotice.className = 'pwa-offline-notice';
      offlineNotice.setAttribute('role', 'status');
      offlineNotice.setAttribute('aria-live', 'polite');
      offlineNotice.textContent = 'Sin conexión. Solo están disponibles los recursos básicos; las operaciones requieren Internet.';
      document.body.prepend(offlineNotice);
    }
  };

  window.addEventListener('offline', updateConnectionStatus);
  window.addEventListener('online', updateConnectionStatus);
  updateConnectionStatus();

  if ('serviceWorker' in navigator && window.isSecureContext) {
    navigator.serviceWorker.register('/sw.js').catch((error) => {
      console.error('No se pudo registrar el Service Worker de FARM SJB.', error);
    });
  }

  if (!('serviceWorker' in navigator)) return;

  let deferredInstallPrompt = null;
  let installButton = null;
  const isStandalone = window.matchMedia?.('(display-mode: standalone)').matches
    || navigator.standalone === true;

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    if (isStandalone) return;

    deferredInstallPrompt = event;
    if (installButton) return;

    installButton = document.createElement('button');
    installButton.type = 'button';
    installButton.className = 'btn btn-primary pwa-install-button';
    installButton.textContent = 'Instalar FARM SJB';
    installButton.addEventListener('click', async () => {
      if (!deferredInstallPrompt) return;

      try {
        await deferredInstallPrompt.prompt();
        await deferredInstallPrompt.userChoice;
        deferredInstallPrompt = null;
        installButton.remove();
        installButton = null;
      } catch (error) {
        console.error('No se pudo iniciar la instalación de FARM SJB.', error);
      }
    });
    document.body.appendChild(installButton);
  });

  window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    installButton?.remove();
    installButton = null;
  });
}

function initUserDropdown() {
  const toggle = document.getElementById('userMenuToggle');
  if (!toggle) return;

  new bootstrap.Dropdown(toggle, {
    popperConfig(defaultConfig) {
      return { ...defaultConfig, strategy: 'fixed' };
    }
  });

  const chevron = toggle.querySelector('.user-menu-chevron');
  if (chevron) {
    toggle.addEventListener('show.bs.dropdown', () => chevron.classList.add('rotated'));
    toggle.addEventListener('hide.bs.dropdown', () => chevron.classList.remove('rotated'));
  }
}

function initLogoutConfirm() {
  document.getElementById('confirmLogoutBtn')?.addEventListener('click', () => {
    window.location.href = '#';
  });
}

function initTooltips() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
    new bootstrap.Tooltip(el);
  });
}

function initLoginForm() {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const password = document.getElementById('loginPassword');
  const toggle = document.getElementById('togglePassword');
  toggle?.addEventListener('click', () => {
    const visible = password.type === 'text';
    password.type = visible ? 'password' : 'text';
    toggle.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    toggle.querySelector('i').className = `bi ${visible ? 'bi-eye' : 'bi-eye-slash'} text-secondary`;
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    document.getElementById('loginAlert')?.classList.add('d-none');

    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }

    const submit = document.getElementById('loginSubmit');
    submit.disabled = true;
    submit.querySelector('.spinner-border').classList.remove('d-none');
    submit.querySelector('.btn-label').textContent = 'Ingresando...';

    // Prototipo: simula la autenticación y redirige al dashboard
    setTimeout(() => { window.location.href = 'dashboard.html'; }, 800);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initSidebarToggle();
  initUserDropdown();
  initLogoutConfirm();
  initTooltips();
  initLoginForm();
  initAccountPreferences();
  initPwaExperience();
});
