import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  base: '/tickets-app/',
  build: {
    outDir: path.resolve(__dirname, '../public/tickets-app'),
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
