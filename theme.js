/**
 * theme.js — 3-way theme toggle: Dark / Light / System
 * Reads/writes to localStorage('theme'): 'dark' | 'light' | 'system'
 * Must be included in <head> (or at top of body) to prevent FOUC.
 */

(function () {
    const STORAGE_KEY = 'theme';
    const MODES = ['dark', 'light', 'system'];

    /* ── Apply theme to <html> ──────────────────────────── */
    function applyTheme(mode) {
        const root = document.documentElement;
        let effective = mode;
        if (mode === 'system') {
            effective = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }
        root.setAttribute('data-theme', effective);
        root.setAttribute('data-theme-mode', mode); // keep track of the chosen mode
    }

    /* ── Persist & apply ────────────────────────────────── */
    function setTheme(mode) {
        localStorage.setItem(STORAGE_KEY, mode);
        applyTheme(mode);
        updateToggleUI(mode);
    }

    /* ── Read saved preference (default: system) ────────── */
    function getSavedTheme() {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    }

    /* ── Cycle: dark → light → system → dark … ─────────── */
    function cycleTheme() {
        const current = getSavedTheme();
        const next = MODES[(MODES.indexOf(current) + 1) % MODES.length];
        setTheme(next);
    }

    /* ── Update button icon & tooltip ───────────────────── */
    function updateToggleUI(mode) {
        const btn = document.getElementById('theme-toggle-btn');
        if (!btn) return;

        const icons = {
            dark:   `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`,
            light:  `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`,
            system: `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>`
        };
        const labels = { dark: 'Dark', light: 'Light', system: 'System' };

        btn.innerHTML = icons[mode] + `<span class="theme-label">${labels[mode]}</span>`;
        btn.title = `Theme: ${labels[mode]} — click to cycle`;
        btn.setAttribute('data-mode', mode);
    }

    /* ── Listen for OS preference change (system mode) ──── */
    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', () => {
        if (getSavedTheme() === 'system') applyTheme('system');
    });

    /* ── Expose globally ─────────────────────────────────── */
    window.ThemeManager = { setTheme, cycleTheme, getSavedTheme, updateToggleUI };

    /* ── Apply immediately to prevent flash ─────────────── */
    applyTheme(getSavedTheme());
})();

/* Called after DOM is ready to wire up the button */
document.addEventListener('DOMContentLoaded', function () {
    const saved = window.ThemeManager.getSavedTheme();
    window.ThemeManager.updateToggleUI(saved);

    const btn = document.getElementById('theme-toggle-btn');
    if (btn) {
        btn.addEventListener('click', function () {
            window.ThemeManager.cycleTheme();
        });
    }
});
