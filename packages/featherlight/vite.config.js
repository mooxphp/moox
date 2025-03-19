import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/src/app.css", "resources/src/app.js"],
            refresh: true,
        }),
    ],
    build: {
        outDir: "resources/dist",
        manifest: true,
        rollupOptions: {
            input: ["resources/src/app.css", "resources/src/app.js"],
        },
    },
});
