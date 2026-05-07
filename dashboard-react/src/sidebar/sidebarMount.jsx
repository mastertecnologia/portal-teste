import React from 'react';
import { createRoot } from 'react-dom/client';
import Sidebar from './Sidebar.jsx';
import { pgmSidebarRawTargetIsBlank, pgmSidebarSameTabStaffHref } from './sidebarNavUtils.js';

/**
 * Última linha de defesa: algum script externo ou payload estranho pode repor `target="_blank"` no DOM.
 * Captura no host da sidebar antes de outros listeners.
 */
function pgmSidebarInstallWorkflowSlaSameTabCapture() {
  if (typeof document === 'undefined' || window.__PGM_SIDEBAR_SLA_SAME_TAB__) return;
  const host = document.getElementById('sidebar-app');
  if (!host) return;
  window.__PGM_SIDEBAR_SLA_SAME_TAB__ = true;
  host.addEventListener(
    'click',
    function (e) {
      if (e.defaultPrevented) return;
      if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      const el = e.target;
      if (!el || typeof el.closest !== 'function') return;
      const a = el.closest('a[href]');
      if (!a || !host.contains(a)) return;
      const hrefAttr = a.getAttribute('href') || '';
      if (!pgmSidebarSameTabStaffHref(hrefAttr)) return;
      if (!pgmSidebarRawTargetIsBlank(a.getAttribute('target'))) return;
      e.preventDefault();
      e.stopPropagation();
      window.location.assign(a.href);
    },
    true
  );
}

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
    pgmSidebarInstallWorkflowSlaSameTabCapture();
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
