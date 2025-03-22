import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";
import tailwindcssPostcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                resolve(__dirname, "resources/src/app.css"),
                resolve(__dirname, "resources/src/app.js"),
            ],
            refresh: true,
        }),
    ],
    root: resolve(__dirname),
    base: "./",
    css: {
        postcss: {
            plugins: [
                tailwindcssPostcss({
                    config: resolve(__dirname, "tailwind.config.cjs"),
                }),
                autoprefixer,
            ],
        },
    },
});
