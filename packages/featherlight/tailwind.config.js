const path = require("path");

module.exports = {
    content: [
        path.resolve(__dirname, "resources/views/**/*.blade.php"),
        "../../packages/components/resources/views/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                primary: "rgb(var(--color-primary) / <alpha-value>)",
                secondary: "rgb(var(--color-secondary) / <alpha-value>)",
            },
            fontFamily: {
                sans: "var(--font-base)",
            },
        },
    },
    plugins: [require("daisyui")],
};
