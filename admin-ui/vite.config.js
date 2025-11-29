import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';
// https://vitejs.dev/config/
export default defineConfig({
    plugins: [react()],
    base: process.env.NODE_ENV === 'production' ? '/admin-ui/dist/' : '/',
    server: {
        port: 5174,
        host: '0.0.0.0'
    },
    preview: {
        port: 4174,
        host: '0.0.0.0'
    },
    build: {
        outDir: 'dist',
        sourcemap: true,
        manifest: true,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'src/main.tsx'),
                marketingNav: resolve(__dirname, 'src/marketing-nav.tsx'),
            },
            output: {
                entryFileNames: function (chunkInfo) {
                    return chunkInfo.name === 'marketingNav'
                        ? 'marketing-nav.js'
                        : '[name].js';
                },
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
