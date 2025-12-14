import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: process.env.NODE_ENV === 'production' ? '/admin-ui/dist/' : '/',
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: [],
    include: ['src/**/*.{test,spec}.{ts,tsx}']
  },
  server: {
    port: 5174,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/uploads': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      // Proxy PHP files
      '/*.php': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      // Proxy asset directories
      '/js': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/css': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/assets': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      },
      '/icons': {
        target: 'http://localhost:8080',
        changeOrigin: true,
        secure: false,
      }
    }
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
        marketingIcons: resolve(__dirname, 'src/marketing-icons.tsx'),
        smoothScroll: resolve(__dirname, 'src/smooth-scroll.tsx'),
      },
      output: {
        entryFileNames: (chunkInfo) => {
          if (chunkInfo.name === 'marketingNav') return 'marketing-nav.js';
          if (chunkInfo.name === 'marketingIcons') return 'marketing-icons.js';
          if (chunkInfo.name === 'smoothScroll') return 'smooth-scroll.js';
          return '[name].js';
        },
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
} as any);

