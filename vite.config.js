import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/errors.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        })
    ],
    build: {
        chunkSizeWarningLimit: 500,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) return 'vendor-vue';
                        if (id.includes('sweetalert2')) return 'vendor-ui';
                        if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'vendor-charts';
                        if (id.includes('@tiptap') || id.includes('prosemirror')) return 'vendor-editor';
                        if (id.includes('face-api')) return 'vendor-face';
                        if (id.includes('@vuepic/vue-datepicker')) return 'vendor-datepicker';
                    }
                },
            },
        },
    },
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'axios'],
    },
});
