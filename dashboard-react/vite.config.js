import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'node',
    include: ['src/**/*.test.js'],
  },
  base: '/tickets-app/',
  build: {
    /** Saída em webroot: é aqui que `react_app.ctp` resolve WWW_ROOT (não public/). */
    outDir: path.resolve(__dirname, '../webroot/tickets-app'),
    emptyOutDir: true,
    cssCodeSplit: false,
    rollupOptions: {
      input: path.resolve(__dirname, 'index.html'),
      output: {
        entryFileNames: 'assets/tickets.js',
        chunkFileNames: 'assets/tickets-[name].js',
        assetFileNames: 'assets/tickets[extname]',
      },
    },
  },
  server: {
    port: 5173,
    strictPort: false,
    host: true,
    open: true,
  },
  preview: {
    port: 4173,
    strictPort: false,
    host: true,
    open: true,
  },
});
