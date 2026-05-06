import React, { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import './preview-dd-sidebar.css';
import ClientSidebar from './ClientSidebar.jsx';
import PortalNotificationsBell from './PortalNotificationsBell.jsx';
import {
  getTurboLinkProps,
  pathMatches,
  PGM_MAIN_FRAME_ID,
  pgmSidebarNavAnchorTargetRel,
  pgmSidebarSameTabStaffHref,
} from './sidebarNavUtils.js';

/** Mesma chave que `sidebar-preview.html` e o portal. */
const PGM_SB_NAV_KEY = 'pgmSidebarSectionExpanded';

function readSectionStates() {
  try {
    const raw = localStorage.getItem(PGM_SB_NAV_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function writeSectionStates(states) {
  try {
    localStorage.setItem(PGM_SB_NAV_KEY, JSON.stringify(states));
  } catch {
    /* ignore */
  }
}

const PGM_SB_SUB_KEY = 'pgmSidebarSubExpanded';

function readSubgroupStates() {
  try {
    const raw = localStorage.getItem(PGM_SB_SUB_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function writeSubgroupStates(states) {
  try {
    localStorage.setItem(PGM_SB_SUB_KEY, JSON.stringify(states));
  } catch {
    /* ignore */
  }
}

function buildInitialSubgroupMap(sectionsList, activePath) {
  const stored = readSubgroupStates();
  const next = { ...stored };
  for (const sec of sectionsList) {
    for (const it of sec.items || []) {
      if (it.itemKind !== 'group' || !it.groupId) continue;
      const gid = it.groupId;
      const childActive =
        it.children?.some((c) => c && (c.active === true || pathMatches(c.href, activePath))) ?? false;
      if (childActive) {
        next[gid] = true;
      } else if (it.groupDefaultOpen && next[gid] === undefined) {
        next[gid] = true;
      } else if (next[gid] === undefined) {
        next[gid] = false;
      }
    }
  }
  writeSubgroupStates(next);
  return next;
}

function sectionItemMatchesPath(it, activePath) {
  if (!it || it.itemKind === 'header') return false;
  if (it.itemKind === 'group') {
    return it.children?.some((c) => c && (c.active === true || pathMatches(c.href, activePath))) ?? false;
  }
  return it.active === true || pathMatches(it.href, activePath);
}

function buildInitialExpandedMap(sectionsList, activePath) {
  const stored = readSectionStates();
  const next = { ...stored };
  for (const sec of sectionsList) {
    const hasActive = sec.items?.some((it) => sectionItemMatchesPath(it, activePath));
    if (hasActive) next[sec.id] = true;
    else if (sec.defaultOpen) next[sec.id] = true;
    else if (next[sec.id] === undefined) next[sec.id] = false;
  }
  writeSectionStates(next);
  return next;
}

const DEMO_SECTIONS = [
  {
    id: 'cadastros',
    title: 'Cadastros',
    defaultOpen: false,
    items: [
      { href: '/clientes', label: ' Clientes', icon: 'users', dataLabel: 'Clientes', active: false, badgeHtml: '', target: null, rel: null },
      { href: '/clientes/add', label: ' Cadastrar clientes', icon: 'user-plus', dataLabel: 'Cadastrar clientes', active: false, badgeHtml: '', target: null, rel: null },
      { href: '/produtos', label: ' Produtos', icon: 'package', dataLabel: 'Produtos', active: false, badgeHtml: '', target: null, rel: null },
    ],
  },
];

/**
 * Staff — consome `window.__PGM_SIDEBAR_PROPS__` gerado pelo Cake (Etapa B).
 * Sem props: dados de demonstração mínimos.
 */
function StaffSidebar(props) {
  const serverActivePath =
    props.activePath ??
    (typeof window !== 'undefined' ? window.location.pathname + window.location.search : '');

  const [livePath, setLivePath] = useState(serverActivePath);

  useEffect(() => {
    setLivePath(serverActivePath);
  }, [serverActivePath]);

  useEffect(() => {
    const syncFromWindow = () => {
      setLivePath(`${window.location.pathname}${window.location.search}`);
    };
    const onFrameLoad = (e) => {
      if (!e.target || e.target.id !== PGM_MAIN_FRAME_ID) return;
      syncFromWindow();
      if (typeof window.pgmTurboShellMarkNavLinks === 'function') {
        window.pgmTurboShellMarkNavLinks();
      }
      if (window.lucide?.createIcons) {
        window.lucide.createIcons();
      }
    };
    document.addEventListener('turbo:frame-load', onFrameLoad);
    window.addEventListener('popstate', syncFromWindow);
    return () => {
      document.removeEventListener('turbo:frame-load', onFrameLoad);
      window.removeEventListener('popstate', syncFromWindow);
    };
  }, []);

  const workspace = props.workspace;
  const companies = useMemo(() => {
    if (workspace?.companies?.length) {
      return workspace.companies;
    }
    return [
      { id: '1', name: 'PGM SOLUÇÕES EM TI', initials: 'PG' },
      { id: '2', name: 'PGM FILIAL BENTO', initials: 'PB' },
    ];
  }, [workspace]);

  const currentFromServer = workspace?.currentId
    ? companies.find((c) => String(c.id) === String(workspace.currentId))
    : null;
  const initialCompany = currentFromServer ?? companies[0] ?? { id: '', name: 'PGM', initials: 'PG' };

  const sections = useMemo(() => {
    if (Array.isArray(props.sections) && props.sections.length > 0) {
      return props.sections;
    }
    return DEMO_SECTIONS;
  }, [props.sections]);

  const dashboardItem = props.dashboardItem ?? null;

  const userName = props.user?.name ?? 'Utilizador';
  const userRole = props.user?.roleLabel ?? 'Usuário';
  const userInitials = props.user?.initials ?? '?';
  const workspaceSub = workspace?.sub ?? 'Matriz';
  const footerLinks = Array.isArray(props.footerLinks) ? props.footerLinks : null;
  const notificationsBell = props.notificationsBell !== false;
  const notificationBellApi = props.notificationBellApi && typeof props.notificationBellApi === 'object' ? props.notificationBellApi : null;

  const [workspaceOpen, setWorkspaceOpen] = useState(false);
  const [workspaceQuery, setWorkspaceQuery] = useState('');
  const [selectedCompany, setSelectedCompany] = useState(initialCompany);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [notifOpen, setNotifOpen] = useState(false);
  const [isMiniSidebar, setIsMiniSidebar] = useState(
    () => typeof document !== 'undefined' && document.body.classList.contains('mini-sidebar')
  );
  const userDdRef = useRef(null);
  const notifPanelRef = useRef(null);
  const bellRef = useRef(null);

  useEffect(() => {
    if (currentFromServer) {
      setSelectedCompany(currentFromServer);
    }
  }, [workspace?.currentId, currentFromServer]);

  const isItemActive = useCallback((it) => {
    if (!it || it.itemKind === 'header' || it.itemKind === 'group') return false;
    return it.active === true || pathMatches(it.href, livePath);
  }, [livePath]);

  const itemOrGroupChildActive = useCallback((it) => {
    if (!it || it.itemKind === 'header') return false;
    if (it.itemKind === 'group') {
      return it.children?.some((c) => isItemActive(c)) ?? false;
    }
    return isItemActive(it);
  }, [isItemActive]);

  const [expandedMap, setExpandedMap] = useState(() => buildInitialExpandedMap(sections, serverActivePath));
  const [subExpanded, setSubExpanded] = useState(() => buildInitialSubgroupMap(sections, serverActivePath));

  useEffect(() => {
    const stored = readSectionStates();
    const next = { ...stored };
    let changed = false;
    for (const sec of sections) {
      const hasActive = sec.items?.some((it) => itemOrGroupChildActive(it));
      if (hasActive && !next[sec.id]) {
        next[sec.id] = true;
        changed = true;
      }
    }
    if (changed) {
      writeSectionStates(next);
      setExpandedMap((prev) => ({ ...prev, ...next }));
    }
  }, [livePath, sections, itemOrGroupChildActive]);

  useEffect(() => {
    const stored = readSubgroupStates();
    const next = { ...stored };
    let changed = false;
    for (const sec of sections) {
      for (const it of sec.items || []) {
        if (it.itemKind !== 'group' || !it.groupId) continue;
        const any = it.children?.some((c) => isItemActive(c));
        if (any && !next[it.groupId]) {
          next[it.groupId] = true;
          changed = true;
        }
      }
    }
    if (changed) {
      writeSubgroupStates(next);
      setSubExpanded((prev) => ({ ...prev, ...next }));
    }
  }, [livePath, sections, isItemActive]);

  const toggleSection = (id) => {
    setExpandedMap((prev) => {
      const sec = sections.find((s) => s.id === id);
      const hasActive = sec?.items?.some((it) => itemOrGroupChildActive(it));
      const open = hasActive || !!prev[id];
      const next = { ...readSectionStates(), ...prev, [id]: !open };
      writeSectionStates(next);
      return next;
    });
  };

  const toggleSubgroup = useCallback((groupId) => {
    setSubExpanded((prev) => {
      let groupItem = null;
      for (const sec of sections) {
        const found = sec.items?.find((it) => it.itemKind === 'group' && it.groupId === groupId);
        if (found) {
          groupItem = found;
          break;
        }
      }
      const hasActive = groupItem?.children?.some((c) => isItemActive(c)) ?? false;
      const open = hasActive || !!prev[groupId];
      const nextVal = !open;
      const next = { ...readSubgroupStates(), ...prev, [groupId]: nextVal };
      writeSubgroupStates(next);
      return next;
    });
  }, [sections, isItemActive]);

  const toggleMiniSidebar = () => {
    document.body.classList.toggle('mini-sidebar');
    const nowMini = document.body.classList.contains('mini-sidebar');
    setIsMiniSidebar(nowMini);
    if (nowMini) {
      setExpandedMap((prev) => {
        const allOpen = {};
        for (const sec of sections) allOpen[sec.id] = true;
        writeSectionStates({ ...readSectionStates(), ...allOpen });
        return { ...prev, ...allOpen };
      });
      setSubExpanded((prev) => {
        const allSub = {};
        for (const sec of sections) {
          for (const it of sec.items || []) {
            if (it.itemKind === 'group' && it.groupId) allSub[it.groupId] = true;
          }
        }
        writeSubgroupStates({ ...readSubgroupStates(), ...allSub });
        return { ...prev, ...allSub };
      });
    } else {
      const stored = readSectionStates();
      const next = { ...stored };
      for (const sec of sections) {
        const hasActive = sec.items?.some((it) => itemOrGroupChildActive(it));
        if (hasActive) next[sec.id] = true;
        else if (next[sec.id] === undefined) next[sec.id] = false;
      }
      writeSectionStates(next);
      setExpandedMap(next);
      const subStored = readSubgroupStates();
      const nextSub = { ...subStored };
      for (const sec of sections) {
        for (const it of sec.items || []) {
          if (it.itemKind !== 'group' || !it.groupId) continue;
          const gid = it.groupId;
          const hasA = it.children?.some((c) => isItemActive(c));
          if (hasA) nextSub[gid] = true;
          else if (nextSub[gid] === undefined) nextSub[gid] = false;
        }
      }
      writeSubgroupStates(nextSub);
      setSubExpanded(nextSub);
    }
  };

  const syncEmpresaSidebar = (companyId) => {
    if (typeof window === 'undefined' || typeof window.jQuery !== 'function') return;
    const $s = window.jQuery('#empresaSidebar');
    if ($s.length) {
      $s.val(String(companyId)).trigger('change');
    }
  };

  useLayoutEffect(() => {
    if (typeof window !== 'undefined' && window.lucide?.createIcons) {
      window.lucide.createIcons();
    }
  }, [workspaceOpen, expandedMap, subExpanded, isMiniSidebar, workspaceQuery, userMenuOpen, notifOpen, selectedCompany, sections, dashboardItem, livePath]);

  useEffect(() => {
    const onDoc = (e) => {
      if (userDdRef.current && !userDdRef.current.contains(e.target)) {
        setUserMenuOpen(false);
      }
      if (
        notifOpen &&
        notifPanelRef.current &&
        !notifPanelRef.current.contains(e.target) &&
        bellRef.current &&
        !bellRef.current.contains(e.target) &&
        e.target?.id !== 'pgmSidebarMenuOpenNotif'
      ) {
        setNotifOpen(false);
      }
    };
    document.addEventListener('click', onDoc);
    return () => document.removeEventListener('click', onDoc);
  }, [notifOpen]);

  const filteredCompanies = companies.filter((c) => c.name.toLowerCase().includes(workspaceQuery.toLowerCase()));

  const empresaOptions = workspace?.empresaSelectOptions;
  const hasHiddenEmpresaSelect = empresaOptions && typeof empresaOptions === 'object';

  const dashboardActive =
    !!dashboardItem &&
    (dashboardItem.active === true || pathMatches(dashboardItem.href, livePath));

  useLayoutEffect(() => {
    if (typeof window !== 'undefined' && typeof window.pgmTurboShellMarkNavLinks === 'function') {
      window.pgmTurboShellMarkNavLinks();
    }
  }, [sections, dashboardItem, footerLinks, userMenuOpen, livePath]);

  return (
    <aside className="left-sidebar skin-pgm pgm-sidebar-shell" id="sidebar">
      {hasHiddenEmpresaSelect ? (
        <div className="pgm-react-empresa-select-host" style={{ display: 'none' }} aria-hidden="true">
          <select id="empresaSidebar" className="form-control pgm-empresa-select" defaultValue={workspace.empresaSelectValue ?? ''}>
            {Object.keys(empresaOptions).map((id) => (
              <option key={id} value={id}>
                {empresaOptions[id]}
              </option>
            ))}
          </select>
        </div>
      ) : null}

      <div className="workspace">
        <button
          className="workspace-btn"
          type="button"
          aria-expanded={workspaceOpen}
          onClick={(e) => {
            e.stopPropagation();
            setWorkspaceOpen((o) => !o);
          }}
        >
          <div className="workspace-avatar">{selectedCompany.initials}</div>
          <div className="workspace-info">
            <div className="workspace-name">{selectedCompany.name}</div>
            <div className="workspace-sub">{workspaceSub}</div>
          </div>
          <svg className="workspace-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>
        <div className={`workspace-dropdown${workspaceOpen ? ' open' : ''}`}>
          <div className="workspace-search">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input
              type="search"
              placeholder="Buscar empresa..."
              autoComplete="off"
              value={workspaceQuery}
              onChange={(e) => setWorkspaceQuery(e.target.value)}
            />
          </div>
          <div>
            {filteredCompanies.map((c) => (
              <button
                key={c.id}
                type="button"
                className={`workspace-item${String(c.id) === String(selectedCompany.id) ? ' active' : ''}`}
                onClick={() => {
                  setSelectedCompany(c);
                  setWorkspaceOpen(false);
                  syncEmpresaSidebar(c.id);
                }}
              >
                <div className="workspace-item-avatar">{c.initials}</div>
                <span className="workspace-item-name">{c.name}</span>
                <svg className="workspace-item-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="scroll-sidebar">
        <nav className="sidebar-nav">
          <ul id="sidebarnav" className="pgm-sidebar-nav-flat nav">
            {dashboardItem ? (
              <li className="nav-section-flat">
                <div className="nav-section-items" style={{ padding: '2px 0' }}>
                  <a
                    href={dashboardItem.href}
                    className={`pgm-nav-link nav-item waves-effect waves-dark${dashboardActive ? ' active' : ''}`}
                    data-label={dashboardItem.dataLabel || 'Dashboard'}
                    {...getTurboLinkProps(dashboardItem.href, null)}
                  >
                    <span className="pgm-nav-lucide" data-lucide={dashboardItem.icon} aria-hidden="true" />
                    <span className="nav-item-label hide-menu">{dashboardItem.label}</span>
                    {dashboardItem.badgeHtml ? (
                      <span className="pgm-nav-badge-host" dangerouslySetInnerHTML={{ __html: dashboardItem.badgeHtml }} />
                    ) : null}
                  </a>
                </div>
              </li>
            ) : null}

            {sections.map((sec) => {
              const hasActive = sec.items?.some((it) => itemOrGroupChildActive(it));
              const expanded = hasActive || !!expandedMap[sec.id];
              return (
                <li
                  key={sec.id}
                  className={`nav-section${expanded ? '' : ' collapsed'}`}
                  data-pgm-nav-section={sec.id}
                >
                  <div
                    className="nav-section-label"
                    role="button"
                    tabIndex={0}
                    aria-expanded={expanded ? 'true' : 'false'}
                    onClick={() => toggleSection(sec.id)}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleSection(sec.id);
                      }
                    }}
                  >
                    <span>{sec.title}</span>
                    <svg className="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                      <polyline points="6 9 12 15 18 9" />
                    </svg>
                  </div>
                  <div className="nav-section-items">
                    {sec.items?.map((it, idx) => {
                      if (it.itemKind === 'header') {
                        return (
                          <div
                            key={`${sec.id}-hdr-${idx}`}
                            className="pgm-nav-subsection-label px-3 py-1 small text-white-50 text-uppercase"
                            role="presentation"
                          >
                            {it.label}
                          </div>
                        );
                      }
                      if (it.itemKind === 'group') {
                        const gid = it.groupId || `${sec.id}-g-${idx}`;
                        const hasActiveChild = it.children?.some((c) => isItemActive(c)) ?? false;
                        const subOpen = hasActiveChild || !!subExpanded[gid];
                        return (
                          <div
                            key={`${sec.id}-grp-${gid}`}
                            className={`pgm-nav-subgroup${subOpen ? '' : ' collapsed'}`}
                            data-pgm-nav-subgroup={gid}
                          >
                            <div
                              className="pgm-nav-subgroup-label"
                              role="button"
                              tabIndex={0}
                              aria-expanded={subOpen ? 'true' : 'false'}
                              onClick={(e) => {
                                e.stopPropagation();
                                toggleSubgroup(gid);
                              }}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                  e.preventDefault();
                                  toggleSubgroup(gid);
                                }
                              }}
                            >
                              <span className="pgm-nav-subgroup-label-text">
                                {it.icon ? (
                                  <span className="pgm-nav-lucide" data-lucide={it.icon} aria-hidden="true" />
                                ) : null}
                                <span className="pgm-nav-subgroup-title">{it.label?.trim() || it.dataLabel}</span>
                              </span>
                              <svg
                                className="chevron"
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.5"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                aria-hidden="true"
                              >
                                <polyline points="6 9 12 15 18 9" />
                              </svg>
                            </div>
                            <div className="pgm-nav-subgroup-items">
                              {(it.children || []).map((c, jidx) => {
                                const navTR = pgmSidebarNavAnchorTargetRel(c.href, c.target, c.rel);
                                return (
                                <a
                                  key={`${sec.id}-${gid}-${jidx}-${c.href}`}
                                  href={c.href}
                                  target={navTR.target}
                                  rel={navTR.rel}
                                  className={`pgm-nav-link nav-item waves-effect waves-dark${isItemActive(c) ? ' active' : ''}`}
                                  data-label={c.dataLabel || c.label}
                                  onClick={(e) => {
                                    if (c.target !== '_blank' || !pgmSidebarSameTabStaffHref(c.href)) return;
                                    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                                    e.preventDefault();
                                    window.location.assign(c.href);
                                  }}
                                  {...getTurboLinkProps(c.href, c.target, c.skipTurboFrame)}
                                >
                                  <span className="pgm-nav-lucide" data-lucide={c.icon} aria-hidden="true" />
                                  <span className="nav-item-label hide-menu">{c.label}</span>
                                  {c.badgeHtml ? (
                                    <span className="pgm-nav-badge-host" dangerouslySetInnerHTML={{ __html: c.badgeHtml }} />
                                  ) : null}
                                </a>
                              );
                              })}
                            </div>
                          </div>
                        );
                      }
                      const navTR = pgmSidebarNavAnchorTargetRel(it.href, it.target, it.rel);
                      return (
                        <a
                          key={`${sec.id}-${idx}-${it.href}`}
                          href={it.href}
                          target={navTR.target}
                          rel={navTR.rel}
                          className={`pgm-nav-link nav-item waves-effect waves-dark${isItemActive(it) ? ' active' : ''}`}
                          data-label={it.dataLabel || it.label}
                          onClick={(e) => {
                            if (it.target !== '_blank' || !pgmSidebarSameTabStaffHref(it.href)) return;
                            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                            e.preventDefault();
                            window.location.assign(it.href);
                          }}
                          {...getTurboLinkProps(it.href, it.target, it.skipTurboFrame)}
                        >
                          <span className="pgm-nav-lucide" data-lucide={it.icon} aria-hidden="true" />
                          <span className="nav-item-label hide-menu">{it.label}</span>
                          {it.badgeHtml ? (
                            <span className="pgm-nav-badge-host" dangerouslySetInnerHTML={{ __html: it.badgeHtml }} />
                          ) : null}
                        </a>
                      );
                    })}
                  </div>
                </li>
              );
            })}
          </ul>
        </nav>
      </div>

      <div className="pgm-sidebar-footer sidebar-footer">
        <div className="user-profile user-profile--footer">
          <div className={`user-pro-body preview-dd${userMenuOpen ? ' open' : ''}`} ref={userDdRef}>
            <div className="dropdown dropup">
            <a
              href="#"
              className="dropdown-toggle u-dropdown link user text-white d-flex align-items-center"
              id="userDdToggle"
              role="button"
              aria-expanded={userMenuOpen}
              onClick={(e) => {
                e.preventDefault();
                setUserMenuOpen((o) => !o);
              }}
            >
              <div className="user-avatar pgm-user-av">{userInitials}</div>
              <div className="user-info pgm-sf-user-info hide-menu">
                <div className="user-name pgm-sf-user-name">{userName}</div>
                <div className="user-role pgm-sf-user-role">{userRole}</div>
              </div>
              <span className="caret hide-menu pgm-sidebar-user-caret" aria-hidden="true" />
            </a>
            <div className="preview-dd-menu" id="userDdMenu" role="menu">
              {footerLinks
                ? footerLinks.map((fl, fidx) => {
                    if (fl.id === 'pgmSidebarMenuOpenNotif') {
                      return (
                        <a
                          key="notif"
                          className="preview-dd-item"
                          href={fl.href || '#'}
                          id="pgmSidebarMenuOpenNotif"
                          onClick={(e) => {
                            e.preventDefault();
                            setUserMenuOpen(false);
                            setNotifOpen(true);
                          }}
                        >
                          {fl.label}
                        </a>
                      );
                    }
                    return (
                      <a
                        key={`${fidx}-${fl.label}-${fl.href}`}
                        className={`preview-dd-item${fl.danger ? ' preview-dd-item--danger' : ''}`}
                        href={fl.href}
                        {...getTurboLinkProps(fl.href, null)}
                      >
                        {fl.label}
                      </a>
                    );
                  })
                : [
                    <a key="p" className="preview-dd-item" href="/users/change_profile" {...getTurboLinkProps('/users/change_profile', null)}>
                      Alterar perfil
                    </a>,
                    <a key="s" className="preview-dd-item" href="/users/change_password" {...getTurboLinkProps('/users/change_password', null)}>
                      Alterar senha
                    </a>,
                    <a
                      key="n"
                      className="preview-dd-item"
                      href="#"
                      id="pgmSidebarMenuOpenNotif"
                      onClick={(e) => {
                        e.preventDefault();
                        setUserMenuOpen(false);
                        setNotifOpen(true);
                      }}
                    >
                      Notificações
                    </a>,
                    <a key="o" className="preview-dd-item preview-dd-item--danger" href="/users/logout">
                      Sair
                    </a>,
                  ]}
            </div>
            </div>
          </div>
        </div>

        {notificationsBell && notificationBellApi ? (
          <PortalNotificationsBell
            api={notificationBellApi}
            open={notifOpen}
            onBellClick={() => setNotifOpen((o) => !o)}
            bellRef={bellRef}
            panelRef={notifPanelRef}
          />
        ) : null}

        <button
          type="button"
          className="pgm-sidebar-collapse-btn icon-btn pgm-sidebar-collapse-react"
          title="Colapsar sidebar"
          aria-label="Recolher menu lateral"
          onClick={toggleMiniSidebar}
        >
          <span data-lucide="chevrons-left" className="pgm-nav-lucide" id="collapseIcon" aria-hidden="true" />
        </button>
      </div>
    </aside>
  );
}

export default function Sidebar(props) {
  if (props.variant === 'client') {
    return <ClientSidebar {...props} />;
  }
  return <StaffSidebar {...props} />;
}
