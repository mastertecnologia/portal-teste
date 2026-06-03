export const PGM_MAIN_FRAME_ID = 'pgm-main-frame';

/** `target="_blank"` vindo do JSON (com espaços / casing). */
export function pgmSidebarRawTargetIsBlank(target) {
  return String(target || '').trim().toLowerCase() === '_blank';
}

/**
 * Admin Workflow & SLA — sempre mesma aba.
 * Cobre: URL dashed oficial, slug camelCase legado, e comparação case-insensitive.
 */
export function pgmSidebarSameTabStaffHref(href) {
  const raw = String(href || '').trim();
  if (raw === '') return false;
  const lower = raw.toLowerCase();
  if (lower.includes('workflow-sla-admin')) return true;
  if (/\/servicedesk\/workflowslaadmin(?:\/|$|\?|#)/i.test(lower)) return true;
  return false;
}

/** `target` / `rel` seguros para o `<a>` da sidebar staff (não altera outros itens). */
export function pgmSidebarNavAnchorTargetRel(href, target, rel) {
  if (pgmSidebarSameTabStaffHref(href)) {
    return { target: undefined, rel: undefined };
  }
  return {
    target: target ? target : undefined,
    rel: rel ? rel : undefined,
  };
}

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
  /*
    skipTurboFrame: páginas com layout sem <turbo-frame id="pgm-main-frame"> (ex.: Service Desk).
    Importante: devolver data-turbo="false" — senão `pgmTurboMarkNavLinks()` (Sidebar useLayoutEffect)
    volta a injetar data-turbo-frame em todos os <a> da scroll-sidebar.
  */
  if (skipTurboFrame) return { 'data-turbo': 'false' };
  const effTarget = pgmSidebarSameTabStaffHref(href) ? null : target;
  if (pgmSidebarRawTargetIsBlank(effTarget)) return {};
  const raw = String(href || '').trim();
  if (!raw || raw === '#' || raw.indexOf('javascript:') === 0) return {};
  try {
    const u = new URL(raw, window.location.href);
    if (u.origin !== window.location.origin) return {};
    if (/\/users\/logout(\/|$)/i.test(u.pathname)) return {};
    /* Protótipos ERP: navegação completa (evita "Content missing" no turbo-frame). */
    if (/-prototype(\/|$)/i.test(u.pathname)) {
      return { 'data-turbo': 'false' };
    }
    return {
      'data-turbo-frame': PGM_MAIN_FRAME_ID,
      'data-turbo-action': 'advance',
    };
  } catch {
    return {};
  }
}
