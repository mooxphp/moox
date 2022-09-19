/** @type {import('tailwindcss').Config} */

const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        "./app/**/*.{html,php,blade.php}",
        "./*.{html,php,blade.php}",
        ],
    theme: {
        extend: {
            colors: {
            teal: colors.teal,
            cyan: colors.cyan,
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
    ],
}
