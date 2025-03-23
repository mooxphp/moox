import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";
import tailwindcss from "@tailwindcss/postcss";
import autoprefixer from "autoprefixer";

export default defineConfig({
    root: resolve(__dirname),
    base: "./",
    build: {
        outDir: resolve(__dirname, "resources/dist"),
        manifest: true,
    },
    plugins: [
        laravel({
            input: [
                resolve(__dirname, "resources/src/app.css"),
                resolve(__dirname, "resources/src/app.js"),
            ],
            refresh: true,
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
