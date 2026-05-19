import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'public',
        emptyOutDir: false,
        manifest: false,
        rollupOptions: {
            output: {
                entryFileNames: 'js/app.js',
                chunkFileNames: 'js/[name].js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.name || '';
                    if (name.endsWith('.css')) return 'css/app.css';
                    return 'assets/[name][extname]';
                },
            },
        },
    },
});
