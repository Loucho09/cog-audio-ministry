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
                // Deeper/lighter red for small text (eyebrows) so it passes AA on both backgrounds.
                'accent-text': 'rgb(var(--color-accent-text) / <alpha-value>)',
            },
            // Decorative motion only. Every use is prefixed with motion-safe: so reduced-motion users get the static design.
            keyframes: {
                equalizer: {
                    '0%, 100%': { transform: 'scaleY(0.35)' },
                    '50%': { transform: 'scaleY(1)' },
                },
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(12px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                nudge: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(6px)' },
                },
            },
            animation: {
                equalizer: 'equalizer 1.1s ease-in-out infinite',
                'fade-up': 'fade-up 0.8s ease-out both',
                nudge: 'nudge 1.8s ease-in-out infinite',
            },
            // Focus-ring gaps match the page instead of always being white.
            ringOffsetColor: {
                DEFAULT: 'rgb(var(--color-background))',
            },
            fontFamily: {
                // Matches the logo: "COG" is a heavy geometric sans (Montserrat), "Audio" a signature script.
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
                // Decorative accent only (e.g. "Audio" in the hero title). Never for body text.
                script: ['"Mrs Saint Delafield"', 'cursive'],
            },
        },
    },

    plugins: [forms],
};
