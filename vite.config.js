import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    build: {
        minify: 'esbuild',
        target: 'es2020',
        // ApexCharts is intentionally lazy-loaded and lands in a dedicated vendor chunk.
        chunkSizeWarningLimit: 700,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('node_modules/apexcharts/')) {
                        return 'vendor-apexcharts';
                    }

                    if (id.includes('node_modules/leaflet/')) {
                        return 'vendor-leaflet';
                    }

                    if (id.includes('node_modules/@fullcalendar/')) {
                        return 'vendor-fullcalendar';
                    }
                },
            },
        },
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, '.'),
        },
    },
    plugins: [
        react(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
