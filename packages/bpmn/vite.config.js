import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Main app
                'resources/css/app.css',
                'resources/js/app.js',

                // BPMN package assets
                'packageslocal/bpmn/resources/css/bpmn.css',
                'packageslocal/bpmn/resources/js/bpmn.js',
            ],
            refresh: true,
        }),

        tailwindcss(),
    ],

    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@bpmn': path.resolve(__dirname, 'packageslocal/bpmn/resources/js'),
        },
        preserveSymlinks: true,
    },
});
