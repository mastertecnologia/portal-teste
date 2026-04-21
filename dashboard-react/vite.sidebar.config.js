import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

/** Build isolado: não toca em `public/tickets-app` (vite.config.js principal). */
export default defineConfig({
  plugins: [react()],
  base: '/js/pgm-sidebar-react/',
  build: {
    // DocumentRoot em produção é `public/` (ver `.htaccess` na raiz + WEBROOT_DIR=public).
    // Saída em `webroot/` fazia o Apache servir 404/Cake ("Controller class Js could not be found").
    outDir: path.resolve(__dirname, '../public/js/pgm-sidebar-react'),
    emptyOutDir: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'sidebar.html'),
      output: {
        entryFileNames: 'sidebar-app.js',
        chunkFileNames: 'sidebar-chunk-[name].js',
        assetFileNames: 'sidebar-assets[extname]',
      },
    },
  },
  server: {
    port: 5174,
    strictPort: false,
    host: true,
  },
});
