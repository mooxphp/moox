import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";

export default defineConfig({
    root: resolve(__dirname),
    base: "./",
    plugins: [
        laravel({
            input: [
                resolve(__dirname, "resources/src/app.css"),
                resolve(__dirname, "resources/src/app.js"),
            ],
            refresh: true,
            publicDirectory: "../../public",
            buildDirectory: "build/featherlight",
        }),
    ],
    css: {
        postcss: {
            plugins: [
                tailwindcss({
                    config: resolve(__dirname, "tailwind.config.cjs"),
                }),
                autoprefixer(),
            ],
        },
    },
});
