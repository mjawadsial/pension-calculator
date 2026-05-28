<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}"
    data-urdu-font="jameel-noori"
>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pension Calculator - AGPR</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script>
        (function () {
            if (sessionStorage.getItem('pension-lang-transition') !== '1') {
                return;
            }

            var mode = 'light';

            try {
                var stored = JSON.parse(localStorage.getItem('pension-theme-selection') || '{}');
                mode = stored.mode === 'dark' ? 'dark' : 'light';
            } catch (error) {
                mode = 'light';
            }

            document.documentElement.setAttribute('data-theme-mode', mode);
            document.documentElement.classList.add('lang-transition-pending');
        })();
    </script>
</head>
<body
    class="min-h-screen antialiased"
    x-data="{
        settingsOpen: false,
        langTransitionVisible: false,
        langTransitionFading: false,
        themes: @js($themes ?? []),
        activeThemeId: null,
        activeMode: 'light',
        urduFont: 'jameel-noori',
        applyThemeSelection(themeId = this.activeThemeId, mode = this.activeMode) {
            if (!themeId || !Array.isArray(this.themes) || this.themes.length === 0) {
                return;
            }

            const theme = this.themes.find((item) => item.id === themeId) || this.themes[0];
            if (!theme || !theme.modes) {
                return;
            }

            const normalizedMode = mode === 'dark' ? 'dark' : 'light';
            const palette = theme.modes[normalizedMode] || theme.modes.light || theme.modes.dark;
            if (!palette) {
                return;
            }

            const root = document.documentElement;
            const isUrdu = root.lang === 'ur';
            const urduText = normalizedMode === 'dark' ? '#ffffff' : '#000000';

            root.style.setProperty('--theme-bg', palette.bg);
            root.style.setProperty('--theme-surface', palette.surface);
            root.style.setProperty('--theme-text', isUrdu ? urduText : palette.text);
            root.style.setProperty('--theme-muted', isUrdu ? urduText : (palette.muted || palette.text));
            root.style.setProperty('--theme-accent', palette.accent);
            root.style.setProperty('--theme-accent-text', isUrdu ? '#000000' : (palette.accent_text || '#ffffff'));
            root.style.setProperty('--theme-border', palette.border);
            root.style.setProperty('--theme-input-bg', palette.input_bg || palette.surface);
            root.dataset.themeMode = normalizedMode;

            this.activeThemeId = theme.id;
            this.activeMode = normalizedMode;
            localStorage.setItem('pension-theme-selection', JSON.stringify({
                themeId: theme.id,
                mode: normalizedMode
            }));
        },
        applyUrduFont(fontKey = this.urduFont) {
            this.urduFont = fontKey === 'jameel-noori-kasheeda' ? 'jameel-noori-kasheeda' : 'jameel-noori';
            document.documentElement.setAttribute('data-urdu-font', this.urduFont);
            localStorage.setItem('pension-urdu-font', this.urduFont);
        },
        switchLanguage(url) {
            sessionStorage.setItem('pension-lang-transition', '1');
            window.location.href = url;
        },
        runLangTransition() {
            if (sessionStorage.getItem('pension-lang-transition') !== '1') {
                return;
            }

            sessionStorage.removeItem('pension-lang-transition');
            this.langTransitionVisible = true;
            document.documentElement.classList.add('lang-transition-pending');

            this.$nextTick(() => {
                setTimeout(() => {
                    this.langTransitionFading = true;

                    setTimeout(() => {
                        this.langTransitionVisible = false;
                        this.langTransitionFading = false;
                        document.documentElement.classList.remove('lang-transition-pending');
                    }, 450);
                }, 1500);
            });
        },
        init() {
            const stored = localStorage.getItem('pension-theme-selection');
            if (stored) {
                try {
                    const parsed = JSON.parse(stored);
                    this.activeThemeId = parsed.themeId || null;
                    this.activeMode = parsed.mode === 'dark' ? 'dark' : 'light';
                } catch (error) {
                    this.activeThemeId = null;
                    this.activeMode = 'light';
                }
            }

            if (!this.activeThemeId && this.themes[0]) {
                this.activeThemeId = this.themes[0].id;
            }

            this.applyThemeSelection(this.activeThemeId, this.activeMode);

            const storedUrduFont = localStorage.getItem('pension-urdu-font');
            this.urduFont = storedUrduFont === 'jameel-noori-kasheeda' ? 'jameel-noori-kasheeda' : 'jameel-noori';
            this.applyUrduFont(this.urduFont);
            this.runLangTransition();
        }
    }"
>
    <x-language-transition />

    <header class="no-print border-b" style="border-color: var(--theme-border);">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <h1 class="text-lg font-semibold">{{ __('app.title') }}</h1>
            <div class="flex items-center gap-3">
                <div class="toggle-row {{ app()->getLocale() === 'ur' ? 'row-ur' : 'row-en' }}">
                    <span class="lang-label label-left">EN</span>
                    <a
                        class="switch-track"
                        href="{{ route('language.switch', app()->getLocale() === 'en' ? 'ur' : 'en') }}"
                        aria-label="Switch language"
                        @click.prevent="switchLanguage('{{ route('language.switch', app()->getLocale() === 'en' ? 'ur' : 'en') }}')"
                    >
                        <span class="flag-knob">
                            @if(app()->getLocale() === 'ur')
                                <svg class="flag-svg" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <clipPath id="circleClipPkUi"><circle cx="15" cy="15" r="15"/></clipPath>
                                    <g clip-path="url(#circleClipPkUi)">
                                        <rect width="30" height="30" fill="#01411C"/>
                                        <rect width="7.5" height="30" fill="#FFFFFF"/>
                                        <circle cx="17.5" cy="15" r="6.5" fill="#FFFFFF"/>
                                        <circle cx="19.2" cy="13.8" r="5.8" fill="#01411C"/>
                                        <polygon points="19.5,11.5 20.2,13 21.8,13 20.5,14 21,15.5 19.5,14.5 18,15.5 18.5,14 17.2,13 18.8,13" fill="#FFFFFF"/>
                                    </g>
                                </svg>
                            @else
                                <svg class="flag-svg" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <clipPath id="circleClipEnUi"><circle cx="30" cy="15" r="15"/></clipPath>
                                    <g clip-path="url(#circleClipEnUi)">
                                        <path d="M0 0h60v30H0z" fill="#012169"/>
                                        <path d="M0 0l60 30M60 0L0 30" stroke="#fff" stroke-width="6"/>
                                        <path d="M0 0l60 30M60 0L0 30" stroke="#C8102E" stroke-width="4"/>
                                        <path d="M30 0v30M0 15h60" stroke="#fff" stroke-width="10"/>
                                        <path d="M30 0v30M0 15h60" stroke="#C8102E" stroke-width="6"/>
                                    </g>
                                </svg>
                            @endif
                        </span>
                    </a>
                    <span class="lang-label label-right">UR</span>
                </div>

                <button
                    type="button"
                    class="btn-ghost inline-flex h-10 w-10 items-center justify-center rounded-full"
                    @click="settingsOpen = true"
                    aria-label="Open theme settings"
                    title="{{ __('app.theme_settings') }}"
                >
                    <x-app-icon name="adjustments-horizontal" class="icon-fixed-white h-6 w-6" />
                </button>
            </div>
        </div>
    </header>

    <div
        x-show="settingsOpen"
        class="fixed inset-0 z-40 flex items-center justify-center p-4 no-print"
        @click.self="settingsOpen = false"
        @keydown.escape.window="settingsOpen = false"
        style="display: none;"
    >
        <div
            x-show="settingsOpen"
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-black/40 backdrop-blur-sm"
        ></div>

        <div
            x-show="settingsOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="panel app-modal relative w-full max-w-2xl rounded-xl p-5"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold">{{ __('app.theme_settings') }}</h2>
                <button type="button" class="btn-secondary inline-flex h-8 w-8 items-center justify-center rounded-md p-0" @click="settingsOpen = false" aria-label="{{ __('app.close') }}">
                    <x-app-icon name="x-mark" class="icon-fixed-white h-4 w-4" />
                </button>
            </div>

            <div class="mb-4 flex items-center gap-2">
                <span class="text-sm opacity-80">{{ __('app.mode') }}:</span>
                <button
                    type="button"
                    class="btn-secondary rounded-md px-3 py-1 text-sm"
                    :class="{ 'btn-active-shimmer': activeMode === 'light' }"
                    @click="applyThemeSelection(activeThemeId || (themes[0] ? themes[0].id : null), 'light')"
                >
                    {{ __('app.light') }}
                </button>
                <button
                    type="button"
                    class="btn-secondary rounded-md px-3 py-1 text-sm"
                    :class="{ 'btn-active-shimmer': activeMode === 'dark' }"
                    @click="applyThemeSelection(activeThemeId || (themes[0] ? themes[0].id : null), 'dark')"
                >
                    {{ __('app.dark') }}
                </button>
            </div>

            <p class="mb-3 text-sm opacity-70">{{ __('app.pick_theme') }}</p>
            <div class="grid grid-cols-3 gap-4 sm:grid-cols-4">
                <template x-for="theme in themes" :key="theme.id">
                    <button
                        type="button"
                        class="group flex flex-col items-center gap-2"
                        @click="applyThemeSelection(theme.id, activeMode || 'light')"
                        :title="theme.name"
                    >
                        <span
                            class="theme-swatch inline-flex h-10 w-10 items-center justify-center rounded-full border-2"
                            :style="'background: ' + (theme.modes?.[activeMode]?.accent || '#777777') + '; border-color: ' + (activeThemeId === theme.id ? 'var(--theme-text)' : 'var(--theme-border)')"
                        ></span>
                        <span class="text-center text-[11px] leading-tight opacity-80" x-text="theme.name"></span>
                    </button>
                </template>
            </div>

            <div class="urdu-font-row mt-5 flex items-center gap-2" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
                <span class="text-sm opacity-80">{{ app()->getLocale() === 'ur' ? 'اردو فونٹ:' : 'Urdu Font:' }}</span>
                <button
                    type="button"
                    class="btn-secondary rounded-md px-2.5 py-1 text-sm"
                    style="font-family: 'Jameel Noori Nastaleeq', serif;"
                    :class="{ 'btn-active-shimmer': urduFont === 'jameel-noori' }"
                    @click="applyUrduFont('jameel-noori')"
                >
                    {{ app()->getLocale() === 'ur' ? 'جمیل نوری نستعلیق' : 'Jameel Noori Nastaleeq' }}
                </button>
                <button
                    type="button"
                    class="btn-secondary rounded-md px-2.5 py-1 text-sm"
                    style="font-family: 'Pencal Jameel Kasheeda', 'Jameel Noori Nastaleeq', serif;"
                    :class="{ 'btn-active-shimmer': urduFont === 'jameel-noori-kasheeda' }"
                    @click="applyUrduFont('jameel-noori-kasheeda')"
                >
                    {{ app()->getLocale() === 'ur' ? 'جمیل نوری نستعلیق کشیدہ' : 'Jameel Noori Nastaleeq Kasheeda' }}
                </button>
            </div>
        </div>
    </div>

    <main class="mx-auto max-w-6xl px-4 py-6">
        @yield('content')
    </main>

    <footer class="site-footer mx-auto max-w-6xl border-t px-4 py-4 text-center text-xs opacity-75" style="border-color: var(--theme-border);">
        <p>{{ __('app.footer_attribution') }}</p>
    </footer>
</body>
</html>
