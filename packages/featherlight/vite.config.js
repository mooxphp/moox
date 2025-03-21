import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import { resolve } from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/src/app.css", "resources/src/app.js"],
            refresh: true,
            publicDirectory: "../../public",
            buildDirectory: "build/featherlight",
        }),
    ],
    build: {
        outDir: "../../public/build/featherlight",
        emptyOutDir: true,
    },
});
