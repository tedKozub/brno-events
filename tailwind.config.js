import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class', '[data-theme="events-dark"]'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        require('daisyui'),
    ],
    daisyui: {
        themes: [
            {
                "events-light": {
                    "primary": "#c2410c",         // Deep Energetic Orange (WCAG AA compliant 5.18:1)
                    "primary-content": "#ffffff",
                    "secondary": "#1e293b",       // Deep Slate
                    "secondary-content": "#ffffff",
                    "accent": "#f59e0b",          // Amber / Gold
                    "accent-content": "#1c1917",
                    "neutral": "#334155",         // Dark Slate
                    "neutral-content": "#ffffff",
                    "base-100": "#ffffff",        // Crisp white cards
                    "base-200": "#f8fafc",        // Slate-50 page background
                    "base-300": "#e2e8f0",        // Slate-200 borders
                    "base-content": "#0f172a",    // Slate-900 high contrast text
                    "info": "#1d4ed8",            // Deep Blue (WCAG AA compliant 6.70:1)
                    "info-content": "#ffffff",
                    "success": "#15803d",         // Deep Emerald green (WCAG AA compliant 5.02:1)
                    "success-content": "#ffffff",
                    "warning": "#b45309",         // Deep Amber (WCAG AA compliant 5.02:1)
                    "warning-content": "#ffffff",
                    "error": "#b91c1c",           // Deep Red (WCAG AA compliant 6.47:1)
                    "error-content": "#ffffff",
                },
                "events-dark": {
                    "primary": "#f6732c",         // Obsidian Warm Orange
                    "primary-content": "#ffffff",
                    "secondary": "#a1a1aa",       // Zinc-400
                    "secondary-content": "#050505",
                    "accent": "#ffffff",          // Pure White
                    "accent-content": "#050505",
                    "neutral": "#1c1c20",         // Deep Zinc-900
                    "neutral-content": "#ffffff",
                    "base-100": "#121214",        // Matte Elevated Dark Surface
                    "base-200": "#050505",        // Pitch Obsidian Background
                    "base-300": "#222226",        // Subtle Dark Border
                    "base-content": "#ffffff",    // Pure White High Contrast Text
                    "info": "#38bdf8",
                    "info-content": "#050505",
                    "success": "#22c55e",
                    "success-content": "#050505",
                    "warning": "#f59e0b",
                    "warning-content": "#050505",
                    "error": "#ef4444",
                    "error-content": "#ffffff",
                    "--rounded-box": "1rem",
                    "--rounded-btn": "0.75rem",
                    "--rounded-badge": "9999px",
                    "--border-btn": "1px",
                },
                "events-dark-legacy": {
                    "primary": "#f97316",         // Vivid Sunset Orange
                    "primary-content": "#0b0f19",
                    "secondary": "#38bdf8",       // Sky Blue accent
                    "secondary-content": "#0b0f19",
                    "accent": "#fbbf24",          // Bright Amber
                    "accent-content": "#0b0f19",
                    "neutral": "#1e293b",         // Slate-800
                    "neutral-content": "#f8fafc",
                    "base-100": "#131b2e",        // Elevated Slate Navy Card surfaces
                    "base-200": "#0b0f19",        // Deep Navy-Black background
                    "base-300": "#1e293b",        // Subtle card border
                    "base-content": "#f8fafc",    // Slate-50 crisp high contrast text
                    "info": "#38bdf8",
                    "info-content": "#0b0f19",
                    "success": "#22c55e",
                    "success-content": "#0b0f19",
                    "warning": "#f59e0b",
                    "warning-content": "#0b0f19",
                    "error": "#ef4444",
                    "error-content": "#ffffff",
                    "--rounded-box": "1rem",
                    "--rounded-btn": "0.75rem",
                    "--rounded-badge": "9999px",
                    "--border-btn": "1px",
                },
            },
        ],
        darkTheme: "events-dark",
        base: true,
        styled: true,
        utils: true,
        prefix: "",
        logs: false,
        themeRoot: ":root",
    },
};
