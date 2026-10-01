import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173',
        cors: {
            origin: process.env.APP_URL || 'http://localhost:8005',
        },
        hmr: {
            host: 'localhost',
            clientPort: 5173,
        },
        watch: {
            usePolling: process.env.DOCKER_ENV === 'true',
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
