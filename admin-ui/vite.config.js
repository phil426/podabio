import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
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
        manifest: true
    }
});
