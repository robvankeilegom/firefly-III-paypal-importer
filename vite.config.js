import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 8003,
        strictPort: true,
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
            ],
            refresh: true,
            assets: [
                'resources/images/**',
            ],
        }),
    ],
});
