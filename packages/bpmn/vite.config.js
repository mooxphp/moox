import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/bpmn.css", "resources/js/bpmn.js"],
            refresh: true,
        }),
    ],
});
