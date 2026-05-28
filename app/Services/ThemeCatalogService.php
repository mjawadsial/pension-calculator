<?php

namespace App\Services;

class ThemeCatalogService
{
    public function all(): array
    {
        $themeDir = base_path('../theme');
        $paths = glob($themeDir . '/*.md') ?: [];
        $themes = [];

        foreach ($paths as $path) {
            $themes[] = $this->parseThemeFile($path);
        }

        $themes = array_values(array_filter($themes));
        usort($themes, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $themes;
    }

    private function parseThemeFile(string $path): ?array
    {
        $contents = file_get_contents($path);
        if (!$contents || !preg_match('/^---\R(.*?)\R---/s', $contents, $matches)) {
            return null;
        }

        $frontmatter = $matches[1];
        preg_match('/^name:\s*(.+)$/m', $frontmatter, $nameMatch);
        $name = trim($nameMatch[1] ?? pathinfo($path, PATHINFO_FILENAME));
        $id = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));

        $parsed = $this->parseThemeYamlLike($frontmatter);

        $topColors = $parsed['colors'] ?? [];
        $topCinder = $parsed['cinder'] ?? [];
        $variantDark = $parsed['variants']['dark']['colors'] ?? [];
        $variantDarkCinder = $parsed['variants']['dark']['cinder'] ?? [];
        $variantLight = $parsed['variants']['light']['colors'] ?? [];
        $variantLightCinder = $parsed['variants']['light']['cinder'] ?? [];

        $darkSource = !empty($variantDark) || !empty($variantDarkCinder)
            ? array_merge($variantDark, $variantDarkCinder)
            : array_merge($topColors, $topCinder);

        $lightSource = !empty($variantLight) || !empty($variantLightCinder)
            ? array_merge($variantLight, $variantLightCinder)
            : array_merge($topColors, $topCinder);

        return [
            'id' => $id,
            'name' => $name,
            'modes' => [
                'light' => $this->normalizeModeColors($lightSource, false),
                'dark' => $this->normalizeModeColors($darkSource, true),
            ],
        ];
    }

    private function parseThemeYamlLike(string $frontmatter): array
    {
        $lines = preg_split('/\R/', $frontmatter) ?: [];
        $result = [
            'colors' => [],
            'cinder' => [],
            'variants' => [
                'light' => ['colors' => [], 'cinder' => []],
                'dark' => ['colors' => [], 'cinder' => []],
            ],
        ];

        $mode = null;
        $section = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $trimmed = trim($line);

            if ($indent === 0 && str_starts_with($trimmed, 'colors:')) {
                $mode = null;
                $section = 'colors';
                continue;
            }

            if ($indent === 0 && str_starts_with($trimmed, 'variants:')) {
                $mode = null;
                $section = null;
                continue;
            }

            if ($indent === 2 && preg_match('/^(light|dark):$/', $trimmed, $match)) {
                $mode = $match[1];
                $section = null;
                continue;
            }

            if ($indent === 4 && in_array($trimmed, ['colors:', 'cinder:'], true)) {
                $section = rtrim($trimmed, ':');
                continue;
            }

            if ($indent === 2 && $mode === null && in_array($trimmed, ['cinder:'], true)) {
                $section = rtrim($trimmed, ':');
                continue;
            }

            if (!preg_match('/^([a-zA-Z0-9\-_]+):\s*(.+)$/', $trimmed, $kv)) {
                continue;
            }

            $key = $kv[1];
            $value = trim($kv[2], "\"'");

            if ($mode !== null && in_array($section, ['colors', 'cinder'], true)) {
                $result['variants'][$mode][$section][$key] = $value;
                continue;
            }

            if ($mode === null && in_array($section, ['colors', 'cinder'], true)) {
                $result[$section][$key] = $value;
            }
        }

        return $result;
    }

    private function normalizeModeColors(array $source, bool $isDark): array
    {
        $defaults = $isDark
            ? [
                'bg' => '#0F172A',
                'surface' => '#111827',
                'text' => '#F9FAFB',
                'muted' => '#94A3B8',
                'accent' => '#60A5FA',
                'accent_text' => '#0B1020',
                'border' => '#334155',
                'input_bg' => '#0B1220',
            ]
            : [
                'bg' => '#FFFFFF',
                'surface' => '#F8FAFC',
                'text' => '#0F172A',
                'muted' => '#475569',
                'accent' => '#2563EB',
                'accent_text' => '#FFFFFF',
                'border' => '#CBD5E1',
                'input_bg' => '#FFFFFF',
            ];

        return [
            'bg' => $this->pickColor($source, ['background', 'bg', 'surface-dim'], $defaults['bg']),
            'surface' => $this->pickColor($source, ['surface', 'surface-container-low', 'surface-solid'], $defaults['surface']),
            'text' => $this->pickColor($source, ['on-background', 'on-surface', 'text'], $defaults['text']),
            'muted' => $this->pickColor($source, ['on-surface-variant', 'subtext', 'text-secondary'], $defaults['muted']),
            'accent' => $this->pickColor($source, ['primary', 'accent', 'surface-tint'], $defaults['accent']),
            'accent_text' => $this->pickColor($source, ['on-primary', 'accent-text', 'on-secondary'], $defaults['accent_text']),
            'border' => $this->pickColor($source, ['outline', 'border', 'outline-variant'], $defaults['border']),
            'input_bg' => $this->pickColor($source, ['surface-container-high', 'surface-container', 'surface'], $defaults['input_bg']),
        ];
    }

    private function pickColor(array $source, array $keys, string $fallback): string
    {
        foreach ($keys as $key) {
            if (isset($source[$key]) && preg_match('/^#([A-Fa-f0-9]{6})$/', $source[$key])) {
                return $source[$key];
            }
        }

        return $fallback;
    }
}
