import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.jsx';
import './index.css';

/** Mantido entre navegações Turbo no mesmo documento (só o frame troca). */
let ticketsReactRoot = null;

function mountTicketsReact() {
  const el = document.getElementById('tickets-react-root') || document.getElementById('root');
  // #region agent log
  try {
    const payload =
      JSON.stringify({
        sessionId: '369061',
        location: 'main.jsx:mountTicketsReact',
        message: 'mount',
        data: {
          hasEl: !!el,
          screen: window.__TICKETS_BOOT__?.screen ?? null,
          path: window.location?.pathname ?? null,
        },
        timestamp: Date.now(),
        hypothesisId: 'H2-mount-dom',
      }) + '\n';
    const k = 'pgm_debug_369061_log';
    const prev = sessionStorage.getItem(k) || '';
    sessionStorage.setItem(k, (prev + payload).slice(-12000));
    fetch('http://127.0.0.1:7753/ingest/17010d6d-b722-4a03-aba9-a1bdf34f817d', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '369061' },
      body: JSON.stringify({
        sessionId: '369061',
        location: 'main.jsx:mountTicketsReact',
        message: 'mount',
        data: { hasEl: !!el, screen: window.__TICKETS_BOOT__?.screen ?? null },
        timestamp: Date.now(),
        hypothesisId: 'H2-mount-dom',
      }),
    }).catch(() => {});
  } catch {
    /* ignore */
  }
  // #endregion
  if (!el) {
    return;
  }
  if (ticketsReactRoot) {
    try {
      ticketsReactRoot.unmount();
    } catch {
      /* ignore */
    }
    ticketsReactRoot = null;
  }
  ticketsReactRoot = ReactDOM.createRoot(el);
  ticketsReactRoot.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}

if (typeof window !== 'undefined') {
  window.__pgmTicketsReactMount = mountTicketsReact;
}

mountTicketsReact();

/**
 * Turbo ativa scripts do fragmento antes de `turbo:frame-load`. Se o mount inline falhar
 * (ex.: ordem de execução / CSP), o root fica vazio — remonta uma vez no próximo frame.
 */
if (typeof document !== 'undefined') {
  document.addEventListener('turbo:frame-load', (e) => {
    if (!e.target || e.target.id !== 'pgm-main-frame') {
      return;
    }
    const boot = window.__TICKETS_BOOT__;
    const screen = boot && typeof boot.screen === 'string' ? boot.screen : '';
    if (!screen || typeof window.__pgmTicketsReactMount !== 'function') {
      return;
    }
    const isTicketsScreen =
      screen.startsWith('tech_') || screen.startsWith('client_') || screen.startsWith('finance_');
    if (!isTicketsScreen) {
      return;
    }
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const el = document.getElementById('tickets-react-root');
        if (!el || typeof window.__pgmTicketsReactMount !== 'function') {
          return;
        }
        const empty =
          el.childElementCount === 0 && String(el.textContent || '').trim() === '';
        if (empty) {
          window.__pgmTicketsReactMount();
        }
      });
    });
  });
}
