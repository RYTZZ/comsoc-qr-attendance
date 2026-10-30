import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Servers that lack an .mjs MIME mapping reject module scripts;
                // emit the pdf.js worker with a .js extension instead.
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
                    if (/\.mjs$/.test(name)) {
                        return 'assets/[name]-[hash].js';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});
