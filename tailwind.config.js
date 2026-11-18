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
                    "primary": "#ea580c",         // Energetic Orange
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
                    "info": "#2563eb",
                    "info-content": "#ffffff",
                    "success": "#16a34a",         // Emerald green for free/confirmed
                    "success-content": "#ffffff",
                    "warning": "#d97706",
                    "warning-content": "#ffffff",
                    "error": "#dc2626",
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
                    "--rounded-box": "12px",
                    "--rounded-btn": "4px",
                    "--rounded-badge": "4px",
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
                    "--rounded-box": "16px",
                    "--rounded-btn": "4px",
                    "--rounded-badge": "4px",
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
