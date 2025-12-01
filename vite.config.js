import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-ui': ['sweetalert2', '@chenfengyuan/vue-countdown'],
                    'vendor-charts': ['chart.js', 'vue-chartjs'],
                    'vendor-editor': ['@tinymce/tinymce-vue'],
                },
            },
        },
    },
});
