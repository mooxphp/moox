const path = require("path");

module.exports = {
    content: [
        path.resolve(__dirname, "resources/mail/templates/**/*.html"),
        path.resolve(__dirname, "resources/mail/layouts/**/*.html"),
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
    corePlugins: {
        preflight: false,
    },
};
