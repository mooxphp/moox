const path = require("path");

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        path.resolve(__dirname, "./resources/**/*.blade.php"),
        path.resolve(__dirname, "./resources/**/*.js"),
        path.resolve(__dirname, "../../resources/**/*.blade.php"),
        path.resolve(__dirname, "../../packages/**/*.blade.php"),
    ],
    safelist: ["text-white"],
    theme: {
        extend: {},
    },
    plugins: [require("daisyui")],
};
