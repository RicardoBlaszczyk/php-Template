/**
 * Initialisiert den Theme-Toggle der Seite.
 * Lädt das gespeicherte Theme aus dem localStorage,
 * berücksichtigt die Systemeinstellung und aktualisiert
 * die Theme-Buttons bei Änderungen.
 * Das gewählte Theme wird im localStorage gespeichert und
 * auf das `data-bs-theme`-Attribut des HTML-Dokuments angewendet.
 */
(function () {
    const storageKey = 'bs-theme'; // key im localStorage
    const btn = document.getElementById('btnTheme');
    const floating = document.getElementById('floatingToggle');

    // Hilfsfunktion: setze data-bs-theme auf <html> oder <body>.
    function applyTheme(theme) {
        // theme: 'light' | 'dark' | 'system'
        if (theme === 'system') {
            // nutze prefers-color-scheme
            const sys = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', sys);
            updateButtons(sys);
        } else {
            document.documentElement.setAttribute('data-bs-theme', theme);
            updateButtons(theme);
        }
    }

    function updateButtons(activeTheme) {
        // Beschriftung / Stil des Buttons anpassen
        btn.textContent = activeTheme === 'dark' ? 'Light' : 'Dark';
        floating.textContent = activeTheme === 'dark' ? '☀️' : '🌙';
    }

    // Lade Einstellung aus localStorage oder verwende 'system'
    let saved = localStorage.getItem(storageKey) || 'system';
    applyTheme(saved);

    // Reagiere auf System-Änderung, wenn user Setting 'system' ist
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem(storageKey) === 'system') {
            applyTheme('system');
        }
    });

    // Button klick: zyklisch wechseln: system -> dark -> light -> system ...
    btn.addEventListener('click', () => {
        const current = localStorage.getItem(storageKey) || 'system';
        let next;
        if (current === 'system') next = 'dark';
        else if (current === 'dark') next = 'light';
        else next = 'system';
        localStorage.setItem(storageKey, next);
        applyTheme(next);
    });

    // Floating toggle macht nur direct toggle zwischen dark/light (ohne system)
    floating.addEventListener('click', () => {
        const currentApplied = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const next = currentApplied === 'dark' ? 'light' : 'dark';
        localStorage.setItem(storageKey, next);
        applyTheme(next);
    });
})();

/**
 * Zeigt eine Toast-Benachrichtigung an.
 *
 * @param {string} message - Der anzuzeigende Nachrichtentext.
 * @param {string} [type='info'] - Typ der Meldung, z. B. info, success, warning oder error.
 * @param {string} [title='Hinweis'] - Überschrift des Toasts.
 */
function showToast(message, type = 'info', title = 'Hinweis') {
    const t = String(type || 'info').toLowerCase();
    const safeType = (t === 'error') ? 'danger' : t;

    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1080';
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = 'toast border-0';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');

    // Header-Farbe + Textfarbe robust setzen (Bootstrap 5.0+)
    const headerBgMap = {
        success: 'bg-success text-white',
        danger: 'bg-danger text-white',
        warning: 'bg-warning text-dark',
        info: 'bg-info text-dark'
    };

    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const bodyClassMap = {
        success: currentTheme === 'dark' ? 'text-white bg-success-subtle' : 'text-white bg-success',
        danger: currentTheme === 'dark' ? 'text-white bg-danger-subtle' : 'text-white bg-danger',
        warning: currentTheme === 'dark' ? 'text-light bg-warning-subtle' : 'text-dark bg-warning-subtle',
        info: currentTheme === 'dark' ? 'text-light bg-info-subtle' : 'text-dark bg-info-subtle'
    };

    toastEl.innerHTML = `
        <div class="toast-header ${(headerBgMap[safeType] || 'bg-secondary text-white')}">
            <strong class="me-auto">${String(title)}</strong>
            <small class="opacity-75">jetzt</small>
            <button type="button"
                    class="btn-close ${safeType === 'warning' || safeType === 'info' ? '' : 'btn-close-white'} ms-2 mb-1"
                    data-bs-dismiss="toast"
                    aria-label="Close"></button>
        </div>
        <div class="toast-body ${(bodyClassMap[safeType] || 'text-white bg-secondary')}">
            ${String(message)}
        </div>
    `;

    container.appendChild(toastEl);

    const inst = new bootstrap.Toast(toastEl, { delay: 5000 });
    inst.show();

    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}