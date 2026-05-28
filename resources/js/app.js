import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('dateInputMask', () => ({
        mask(event) {
            const input = event.target;
            const digits = input.value.replace(/\D/g, '').slice(0, 8);
            let formatted = '';

            if (digits.length <= 2) {
                formatted = digits;
            } else if (digits.length <= 4) {
                formatted = `${digits.slice(0, 2)}/${digits.slice(2)}`;
            } else {
                formatted = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
            }

            input.value = formatted;
        },
        blockKey(event) {
            const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];

            if (allowed.includes(event.key)) {
                return;
            }

            if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) {
                return;
            }

            if (!/^\d$/.test(event.key)) {
                event.preventDefault();
            }
        },
    }));

    Alpine.data('pensionForm', (initialCommutation = '35') => ({
        commutation: String(initialCommutation),
        logSubmit(event) {
            const form = event.target;
            const payload = Object.fromEntries(new FormData(form).entries());

            console.group('[Pension] Calculate Pension — submit');
            console.log('Timestamp:', new Date().toISOString());
            console.log('Payload:', payload);

            const invalidFields = [...form.querySelectorAll(':invalid')].map((field) => ({
                name: field.name || field.id || '(unnamed)',
                value: field.value,
                message: field.validationMessage,
            }));

            if (invalidFields.length > 0) {
                console.warn('Browser blocked submit — fix these fields first:', invalidFields);
            } else {
                console.log('Browser validation passed — posting to server…');
            }

            console.groupEnd();
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();

window.__pensionThemes = [];

window.applyTheme = function applyTheme(themeInput, mode = 'light') {
    if (!themeInput) {
        return;
    }

    const theme = typeof themeInput === 'string' ? JSON.parse(themeInput) : themeInput;
    const activeMode = mode === 'dark' ? 'dark' : 'light';
    const modeColors = theme.modes?.[activeMode] ?? theme.modes?.light ?? theme.modes?.dark;
    if (!modeColors) {
        return;
    }

    const root = document.documentElement;
    root.style.setProperty('--theme-bg', modeColors.bg);
    root.style.setProperty('--theme-surface', modeColors.surface);
    root.style.setProperty('--theme-text', modeColors.text);
    root.style.setProperty('--theme-accent', modeColors.accent);
    root.style.setProperty('--theme-border', modeColors.border);
    localStorage.setItem('pension-theme-selection', JSON.stringify({ themeId: theme.id, mode: activeMode }));
};

window.restoreTheme = function restoreTheme(themeMap) {
    window.__pensionThemes = Array.isArray(themeMap) ? themeMap : [];
    const stored = localStorage.getItem('pension-theme-selection');

    let themeId = null;
    let mode = 'light';

    if (stored) {
        try {
            const parsed = JSON.parse(stored);
            themeId = parsed.themeId ?? null;
            mode = parsed.mode === 'dark' ? 'dark' : 'light';
        } catch (error) {
            themeId = null;
            mode = 'light';
        }
    }

    let match = window.__pensionThemes.find((theme) => theme.id === themeId);
    if (!match && window.__pensionThemes.length > 0) {
        match = window.__pensionThemes[0];
        themeId = match.id;
    }

    if (match) {
        window.applyTheme(match, mode);
    }

    return {
        themeId,
        mode,
    };
};

window.applyThemeById = function applyThemeById(themeId, mode = 'light') {
    const match = window.__pensionThemes.find((theme) => theme.id === themeId);
    if (match) {
        window.applyTheme(match, mode);
    }
};

window.getStoredThemeSelection = function getStoredThemeSelection() {
    const stored = localStorage.getItem('pension-theme-selection');
    if (!stored) {
        return null;
    }

    try {
        return JSON.parse(stored);
    } catch (error) {
        return null;
    }
};
