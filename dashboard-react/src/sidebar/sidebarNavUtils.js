export const PGM_MAIN_FRAME_ID = 'pgm-main-frame';

export function normalizePath(p) {
  if (!p) return '/';
  const base = String(p).split('?')[0];
  if (base.length > 1 && base.endsWith('/')) {
    return base.slice(0, -1);
  }
  return base;
}

export function pathMatches(href, activePath) {
  if (!href || !activePath) return false;
  const h = normalizePath(href);
  const p = normalizePath(activePath);
  if (h === '/') return p === '/' || p === '';
  return p === h || p.startsWith(`${h}/`);
}

/** Espelha `pgmTurboSameOriginHref` + frame em `default.ctp`. */
export function getTurboLinkProps(href, target, skipTurboFrame = false) {
  if (typeof window === 'undefined') return {};
  if (skipTurboFrame) return {};
  if (target === '_blank') return {};
  const raw = String(href || '').trim();
  if (!raw || raw === '#' || raw.indexOf('javascript:') === 0) return {};
  try {
    const u = new URL(raw, window.location.href);
    if (u.origin !== window.location.origin) return {};
    if (/\/users\/logout(\/|$)/i.test(u.pathname)) return {};
    return {
      'data-turbo-frame': PGM_MAIN_FRAME_ID,
      'data-turbo-action': 'advance',
    };
  } catch {
    return {};
  }
}
