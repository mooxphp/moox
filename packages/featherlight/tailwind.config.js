/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/views/**/*.blade.php",
        "../../packages/components/resources/views/**/*.blade.php",
        "../../app/**/*.blade.php",
    ],
    theme: {
        extend: {},
    },
    plugins: [require("daisyui")],
};
