/* ============================================================
   THEME MANAGER — Dark Mode + Custom Colors
   Saves to localStorage
   ============================================================ */

const THEME_KEY = 'rs_theme';
const DARK_KEY = 'rs_dark_mode';

const DEFAULT_THEME = {
    primary: '#e63a1a',
    primaryDark: '#c22f13',
    primaryLight: '#ffd9cd',
    accent: '#ff7849'
};

/* ---------- Helpers ---------- */
function hexToRgb(hex) {
    const h = hex.replace('#', '');
    const bigint = parseInt(h.length === 3
        ? h.split('').map(c => c + c).join('')
        : h, 16);
    return {
        r: (bigint >> 16) & 255,
        g: (bigint >> 8) & 255,
        b: bigint & 255
    };
}

function lighten(hex, amount = 0.7) {
    const { r, g, b } = hexToRgb(hex);
    const nr = Math.round(r + (255 - r) * amount);
    const ng = Math.round(g + (255 - g) * amount);
    const nb = Math.round(b + (255 - b) * amount);
    return '#' + [nr, ng, nb].map(x => x.toString(16).padStart(2, '0')).join('');
}

function darken(hex, amount = 0.2) {
    const { r, g, b } = hexToRgb(hex);
    const nr = Math.round(r * (1 - amount));
    const ng = Math.round(g * (1 - amount));
    const nb = Math.round(b * (1 - amount));
    return '#' + [nr, ng, nb].map(x => x.toString(16).padStart(2, '0')).join('');
}

/* ---------- Get / Save theme ---------- */
function getTheme() {
    try {
        const saved = JSON.parse(localStorage.getItem(THEME_KEY));
        return saved || { ...DEFAULT_THEME };
    } catch { return { ...DEFAULT_THEME }; }
}

function saveTheme(theme) {
    localStorage.setItem(THEME_KEY, JSON.stringify(theme));
}

/* ---------- Apply theme to CSS variables ---------- */
function applyTheme(theme) {
    const root = document.documentElement;
    root.style.setProperty('--primary', theme.primary);
    root.style.setProperty('--primary-dark', theme.primaryDark || darken(theme.primary, 0.2));
    root.style.setProperty('--primary-light', theme.primaryLight || lighten(theme.primary, 0.75));
    root.style.setProperty('--accent', theme.accent || theme.primary);
}

/* ---------- Dark mode ---------- */
function isDarkMode() {
    return localStorage.getItem(DARK_KEY) === '1';
}

function setDarkMode(on) {
    if (on) {
        document.body.classList.add('dark-mode');
        localStorage.setItem(DARK_KEY, '1');
    } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem(DARK_KEY, '0');
    }
    updateToggleIcon();
}

function toggleDarkMode() {
    setDarkMode(!isDarkMode());
}

function updateToggleIcon() {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.textContent = isDarkMode() ? '☀' : '☾';
    btn.title = isDarkMode() ? 'Switch to light mode' : 'Switch to dark mode';
}

/* ---------- Init ---------- */
function initTheme() {
    applyTheme(getTheme());
    setDarkMode(isDarkMode());

    const btn = document.getElementById('themeToggle');
    if (btn) {
        btn.onclick = toggleDarkMode;
        updateToggleIcon();
    }
}

document.addEventListener('DOMContentLoaded', initTheme);

// Apply theme immediately (before DOMContentLoaded) to prevent flash
(function () {
    try {
        const theme = JSON.parse(localStorage.getItem(THEME_KEY));
        if (theme) {
            const root = document.documentElement;
            root.style.setProperty('--primary', theme.primary);
            root.style.setProperty('--primary-dark', theme.primaryDark || darken(theme.primary, 0.2));
            root.style.setProperty('--primary-light', theme.primaryLight || lighten(theme.primary, 0.75));
            root.style.setProperty('--accent', theme.accent || theme.primary);
        }
    } catch {}
    if (localStorage.getItem(DARK_KEY) === '1') {
        document.documentElement.classList.add('preload-dark');
        document.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('dark-mode');
            document.documentElement.classList.remove('preload-dark');
        });
    }
})();