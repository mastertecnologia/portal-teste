import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App.jsx';
import './index.css';

/** Mantido entre navegações Turbo no mesmo documento (só o frame troca). */
let ticketsReactRoot = null;

function mountTicketsReact() {
  const el = document.getElementById('tickets-react-root') || document.getElementById('root');
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
