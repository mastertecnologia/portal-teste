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
  const root = createRoot(container);
  root.render(<Sidebar {...(boot && typeof boot === 'object' ? boot : {})} />);
  queueMicrotask(function () {
    if (typeof window.pgmTurboShellMarkNavLinks === 'function') {
      window.pgmTurboShellMarkNavLinks();
    }
  });
  return root;
}

mountPgmSidebar();
