/** @type {import('tailwindcss').Config} */
export default {
    // In Tailwind v4, most configuration is done in CSS
    // This file is kept for DaisyUI-specific options if needed
    daisyui: {
        themes: ['light', 'dark'],
        darkTheme: 'dark',
        base: true,
        styled: true,
        utils: true,
        prefix: '',
        logs: true,
        themeRoot: ':root',
    },
};

