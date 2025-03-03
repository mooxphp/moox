const defaultTheme = require("tailwindcss/defaultTheme");

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: "class",
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./custom/**/*.blade.php",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ["sans", ...defaultTheme.fontFamily.sans],
            },
            width: {
                30: "7.5rem", // 120px
                40: "10rem", // 160px
                50: "12.5rem", // 200px
                60: "15rem", // 240px
                70: "17.5rem", // 280px
                80: "20rem", // 320px
                90: "22.5rem", // 360px
                100: "25rem", // 400px
            },
            spacing: {
                10: "2.5rem", // 40px
                20: "5rem", // 80px
                30: "7.5rem", // 120px
                40: "10rem", // 160px
                50: "12.5rem", // 200px
                60: "15rem", // 240px
                70: "17.5rem", // 280px
                80: "20rem", // 320px
                90: "22.5rem", // 360px
                100: "25rem", // 400px
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
