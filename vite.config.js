import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/src/app.ts',
            buildDirectory: 'vendor/module',
            publicDirectory: '.module/public',
            refresh: true,
        }),
        tailwindcss(),
    ],
});
