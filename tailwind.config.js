import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Dark mode is switched by a `dark` class on <html> (set by the theme toggle).
    darkMode: 'class',

    theme: {
        extend: {
            // Token values live as CSS variables in resources/css/app.css (light in :root, dark in .dark),
            // so every token-based class switches theme automatically and opacity modifiers (e.g. text-text/70) still work.
            colors: {
                background: 'rgb(var(--color-background) / <alpha-value>)',
                surface: 'rgb(var(--color-surface) / <alpha-value>)',
                text: 'rgb(var(--color-text) / <alpha-value>)',
                primary: 'rgb(var(--color-primary) / <alpha-value>)',
                'primary-dark': 'rgb(var(--color-primary-dark) / <alpha-value>)',
                accent: 'rgb(var(--color-accent) / <alpha-value>)',
            },
            // Focus-ring gaps match the page instead of always being white.
            ringOffsetColor: {
                DEFAULT: 'rgb(var(--color-background))',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
