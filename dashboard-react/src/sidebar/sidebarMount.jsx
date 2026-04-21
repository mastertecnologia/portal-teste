import React from 'react';
import { createRoot } from 'react-dom/client';
import Sidebar from './Sidebar.jsx';

/**
 * Monta a sidebar no primeiro `#sidebar-app` encontrado.
 * Opcional: `window.__PGM_SIDEBAR_PROPS__` (objeto) é passado como props para <Sidebar />.
 */
export function mountPgmSidebar() {
  const container = document.getElementById('sidebar-app');
  if (!container) return null;
  const boot = typeof window !== 'undefined' && window.__PGM_SIDEBAR_PROPS__;
  try {
    const root = createRoot(container);
    root.render(<Sidebar {...(boot && typeof boot === 'object' ? boot : {})} />);
    queueMicrotask(function () {
      if (typeof window.pgmTurboShellMarkNavLinks === 'function') {
        window.pgmTurboShellMarkNavLinks();
      }
    });
    return root;
  } catch (err) {
    const raw = err && err.message ? String(err.message) : String(err);
    const msg = raw.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    container.innerHTML =
      '<div class="pgm-sidebar-react-fail" style="padding:14px 12px;font:13px/1.45 system-ui,sans-serif;color:#f85149;background:#161b22;border-right:1px solid #30363d;max-width:260px;height:100vh;box-sizing:border-box">' +
      '<strong>Menu React</strong><p style="margin:8px 0 0;color:#8b949e">Falha ao iniciar. Veja a consola (F12).</p>' +
      '<pre style="margin:10px 0 0;font-size:11px;color:#c9d1d9;white-space:pre-wrap;word-break:break-all">' +
      msg +
      '</pre></div>';
    if (typeof console !== 'undefined' && console.error) {
      console.error('PGM sidebar React:', err);
    }
    return null;
  }
}

mountPgmSidebar();
