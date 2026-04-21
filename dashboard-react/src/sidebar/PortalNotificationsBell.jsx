import React, { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import './portal-notif-bell.css';

function csrfToken() {
  if (typeof document === 'undefined') return '';
  const m = document.querySelector('meta[name="csrfToken"]');
  if (m && m.getAttribute('content')) return m.getAttribute('content');
  const inp = document.querySelector('input[name="_csrfToken"]');
  return inp ? inp.value : '';
}

function iconForType(t) {
  if (t === 'error') return 'fa-exclamation-circle text-danger';
  if (t === 'warning') return 'fa-exclamation-triangle text-warning';
  if (t === 'success') return 'fa-check-circle text-success';
  return 'fa-info-circle text-info';
}

/**
 * Paridade com `portal_notification_bell.ctp` (fetch, sem jQuery).
 */
export default function PortalNotificationsBell({ api, open, onBellClick, bellRef, panelRef }) {
  const fallbackPanelRef = useRef(null);
  const resolvedPanelRef = panelRef ?? fallbackPanelRef;
  const [count, setCount] = useState(null);
  const [countErr, setCountErr] = useState(false);
  const [items, setItems] = useState([]);
  const [listState, setListState] = useState('idle');

  const refreshCount = useCallback(() => {
    if (!api?.urlCount) return;
    fetch(api.urlCount, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      cache: 'no-store',
    })
      .then((r) => (r.ok ? r.json() : Promise.reject(r)))
      .then((d) => {
        const n = d && typeof d.count !== 'undefined' ? parseInt(String(d.count), 10) : 0;
        setCount(Number.isFinite(n) ? n : 0);
        setCountErr(false);
      })
      .catch(() => {
        setCountErr(true);
      });
  }, [api]);

  useEffect(() => {
    if (!api?.urlCount) return undefined;
    refreshCount();
    const t = setInterval(refreshCount, 60000);
    return () => clearInterval(t);
  }, [api, refreshCount]);

  const loadList = useCallback(() => {
    if (!api?.urlList) return;
    setItems([]);
    setListState('loading');
    fetch(api.urlList, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      cache: 'no-store',
    })
      .then((r) => (r.ok ? r.json() : Promise.reject(r)))
      .then((d) => {
        setItems(Array.isArray(d?.items) ? d.items : []);
        setListState('ok');
      })
      .catch(() => {
        setItems([]);
        setListState('error');
      });
  }, [api]);

  useEffect(() => {
    if (open) {
      loadList();
    }
  }, [open, loadList]);

  useLayoutEffect(() => {
    if (!open) return;
    const btn = bellRef?.current;
    const panel = resolvedPanelRef.current;
    if (!btn || !panel || typeof window === 'undefined') return;
    const rect = btn.getBoundingClientRect();
    const winH = window.innerHeight;
    const winW = window.innerWidth;
    const maxH = winH - 40;
    panel.style.maxHeight = `${maxH}px`;
    let left = rect.left + rect.width + 8;
    if (left + 340 > winW) {
      left = rect.left - 340 - 8;
    }
    /* position: fixed — coordenadas em relação à viewport (não somar scrollTop). */
    const estH = Math.min(420, maxH);
    let top = rect.bottom + 10 - estH;
    if (top < 12) top = 12;
    if (top + estH > winH - 12) top = Math.max(12, winH - estH - 12);
    panel.style.top = `${top}px`;
    panel.style.left = `${left}px`;
    panel.style.right = 'auto';
  }, [open, bellRef, resolvedPanelRef]);

  const markAllRead = (e) => {
    e.preventDefault();
    const tok = csrfToken();
    if (!tok || !api?.urlMarkAll) return;
    const body = new FormData();
    body.append('_csrfToken', tok);
    fetch(api.urlMarkAll, { method: 'POST', body, credentials: 'same-origin' }).finally(() => {
      refreshCount();
      loadList();
    });
  };

  const onItemClick = (e, it) => {
    const href = it.action_url || '#';
    const mid = it.id && !it.is_read ? String(it.id) : '';
    const tok = csrfToken();
    if (!mid || !tok || !api?.urlMarkReadBase) {
      return;
    }
    e.preventDefault();
    const body = new FormData();
    body.append('_csrfToken', tok);
    fetch(`${api.urlMarkReadBase}/${encodeURIComponent(mid)}`, {
      method: 'POST',
      body,
      credentials: 'same-origin',
    })
      .then(() => {
        refreshCount();
        if (href !== '#') window.location.href = href;
      })
      .catch(() => {
        if (href !== '#') window.location.href = href;
      });
  };

  if (!api?.urlCount) {
    return null;
  }

  const showBadge = count !== null && count > 0;
  const bellWrapClass = `pgm-portal-notif-bell${countErr ? ' pgm-notif-api-error' : ''}`;

  return (
    <>
      <div className="pgm-sidebar-notif-host" id="pgmSidebarNotifHost" aria-hidden="true">
        <div className={bellWrapClass} id="pgmPortalNotifBell" ref={bellRef}>
          <a
            href="#"
            className="pgm-bell-btn"
            title="Notificações"
            id="pgmBellToggle"
            onClick={(e) => {
              e.preventDefault();
              onBellClick();
            }}
          >
            <i className="fas fa-bell" />
            <span className="pgm-bell-badge" id="pgmBellBadge" style={{ display: showBadge ? 'inline-block' : 'none' }}>
              {showBadge ? (count > 99 ? '99+' : count) : '0'}
            </span>
          </a>
        </div>
      </div>
      <div className={`pgm-notif-panel-fixed${open ? ' is-open' : ''}`} id="pgmNotifPanel" aria-hidden={!open} ref={resolvedPanelRef}>
        <div className="pgm-notif-panel-header">
          <span>Notificações</span>
          <button type="button" id="pgmMarkAllRead" className="pgm-portal-notif-mark-all" onClick={markAllRead}>
            Marcar todas
          </button>
        </div>
        <div id="pgmNotifListBody" className="pgm-notif-list-body" style={{ minHeight: 48 }}>
          {listState === 'loading' ? (
            <div className="text-muted text-center py-3 pgm-notif-list-placeholder">Carregando…</div>
          ) : null}
          {listState === 'error' ? (
            <div className="text-muted text-center py-3 pgm-notif-list-placeholder">Indisponível</div>
          ) : null}
          {listState === 'ok' && items.length === 0 ? (
            <div className="text-muted text-center py-3 pgm-notif-list-placeholder">Nenhuma notificação</div>
          ) : null}
          {listState === 'ok' && items.length > 0
            ? items.map((it) => {
                const cls = `pgm-portal-notif-item${it.is_read ? '' : ' unread'}`;
                return (
                  <a
                    key={it.id || it.title}
                    className={cls}
                    href={it.action_url || '#'}
                    onClick={(e) => onItemClick(e, it)}
                  >
                    <div>
                      <i className={`fas ${iconForType(it.type)} mr-1`} />
                      <span className="pgm-nt-title">{it.title || ''}</span>
                    </div>
                    {it.message ? <div className="pgm-nt-msg">{it.message}</div> : null}
                    <div className="pgm-nt-meta">{it.created_human || ''}</div>
                  </a>
                );
              })
            : null}
        </div>
        <div className="pgm-portal-notif-footer">
          <small className="text-muted">Eventos do módulo de clientes e integrações</small>
          <br />
          <a href={api.urlPrefs} className="pgm-portal-notif-prefs-link">
            Preferências de alertas
          </a>
        </div>
      </div>
    </>
  );
}
