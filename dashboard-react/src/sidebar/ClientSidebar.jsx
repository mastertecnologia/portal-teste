import React, { useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { getTurboLinkProps, pathMatches } from './sidebarNavUtils.js';

const CLIENT_SUB_KEY = 'pgmClientNavSubs';

function readClientSubStates() {
  try {
    const raw = localStorage.getItem(CLIENT_SUB_KEY);
    return raw ? JSON.parse(raw) : {};
  } catch {
    return {};
  }
}

function writeClientSubStates(states) {
  try {
    localStorage.setItem(CLIENT_SUB_KEY, JSON.stringify(states));
  } catch {
    /* ignore */
  }
}

export default function ClientSidebar(props) {
  const serverActivePath =
    props.activePath ??
    (typeof window !== 'undefined' ? window.location.pathname + window.location.search : '');

  const [livePath, setLivePath] = useState(serverActivePath);
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [isMiniSidebar, setIsMiniSidebar] = useState(
    () => typeof document !== 'undefined' && document.body.classList.contains('mini-sidebar')
  );
  const userDdRef = useRef(null);

  const navBlocks = Array.isArray(props.navBlocks) ? props.navBlocks : [];
  const workspace = props.workspace;
  const companies = useMemo(() => {
    if (workspace?.companies?.length) return workspace.companies;
    return [];
  }, [workspace]);
  const currentFromServer = workspace?.currentId
    ? companies.find((c) => String(c.id) === String(workspace.currentId))
    : null;
  const initialCompany = currentFromServer ?? companies[0] ?? { id: '', name: 'PGM', initials: 'PG' };
  const [workspaceOpen, setWorkspaceOpen] = useState(false);
  const [workspaceQuery, setWorkspaceQuery] = useState('');
  const [selectedCompany, setSelectedCompany] = useState(initialCompany);

  const isItemPathActive = useCallback((href) => pathMatches(href, livePath), [livePath]);

  const buildInitialSubs = useCallback(() => {
    const stored = readClientSubStates();
    const next = { ...stored };
    for (const b of navBlocks) {
      if (b.type !== 'group') continue;
      const has = b.items?.some((it) => it.active || isItemPathActive(it.href));
      if (has || b.defaultOpen) next[b.id] = true;
      else if (next[b.id] === undefined) next[b.id] = false;
    }
    writeClientSubStates(next);
    return next;
  }, [navBlocks, isItemPathActive]);

  const [subOpen, setSubOpen] = useState(() => buildInitialSubs());

  useEffect(() => {
    setLivePath(serverActivePath);
  }, [serverActivePath]);

  useEffect(() => {
    const syncFromWindow = () => {
      setLivePath(`${window.location.pathname}${window.location.search}`);
    };
    const onFrameLoad = (e) => {
      if (!e.target || e.target.id !== 'pgm-main-frame') return;
      syncFromWindow();
      if (typeof window.pgmTurboShellMarkNavLinks === 'function') {
        window.pgmTurboShellMarkNavLinks();
      }
    };
    document.addEventListener('turbo:frame-load', onFrameLoad);
    window.addEventListener('popstate', syncFromWindow);
    return () => {
      document.removeEventListener('turbo:frame-load', onFrameLoad);
      window.removeEventListener('popstate', syncFromWindow);
    };
  }, []);

  useEffect(() => {
    const stored = readClientSubStates();
    const next = { ...stored };
    let changed = false;
    for (const b of navBlocks) {
      if (b.type !== 'group') continue;
      const has = b.items?.some((it) => it.active || isItemPathActive(it.href));
      if (has && !next[b.id]) {
        next[b.id] = true;
        changed = true;
      }
    }
    if (changed) {
      writeClientSubStates(next);
      setSubOpen((prev) => ({ ...prev, ...next }));
    }
  }, [livePath, navBlocks, isItemPathActive]);

  useEffect(() => {
    if (currentFromServer) setSelectedCompany(currentFromServer);
  }, [workspace?.currentId, currentFromServer]);

  useEffect(() => {
    const onDoc = (e) => {
      if (userDdRef.current && !userDdRef.current.contains(e.target)) {
        setUserMenuOpen(false);
      }
    };
    document.addEventListener('click', onDoc);
    return () => document.removeEventListener('click', onDoc);
  }, []);

  const syncEmpresaSidebar = (companyId) => {
    if (typeof window === 'undefined' || typeof window.jQuery !== 'function') return;
    const $s = window.jQuery('#empresaSidebar');
    if ($s.length) {
      $s.val(String(companyId)).trigger('change');
    }
  };

  const toggleSub = (id) => {
    setSubOpen((prev) => {
      const next = { ...prev, [id]: !prev[id] };
      writeClientSubStates(next);
      return next;
    });
  };

  const toggleMiniSidebar = () => {
    document.body.classList.toggle('mini-sidebar');
    setIsMiniSidebar(document.body.classList.contains('mini-sidebar'));
  };

  const userName = props.user?.name ?? '';
  const userInitials = props.user?.initials ?? '?';
  const footerLinks = Array.isArray(props.footerLinks) ? props.footerLinks : [];

  const empresaOptions = workspace?.empresaSelectOptions;
  const hasHiddenEmpresaSelect = empresaOptions && typeof empresaOptions === 'object';

  const filteredCompanies = companies.filter((c) => c.name.toLowerCase().includes(workspaceQuery.toLowerCase()));

  const isGroupOpen = (g) => {
    const has = g.items?.some((it) => it.active || isItemPathActive(it.href));
    return has || !!subOpen[g.id];
  };

  useLayoutEffect(() => {
    if (typeof window !== 'undefined' && typeof window.pgmTurboShellMarkNavLinks === 'function') {
      window.pgmTurboShellMarkNavLinks();
    }
  }, [navBlocks, livePath, userMenuOpen]);

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

      <div className="pgm-sidebar-brand">
        <a className="pgm-sidebar-logo-link navbar-brand" href="/users/dashboard" {...getTurboLinkProps('/users/dashboard', null)}>
          <div className="pgm-sidebar-mark">PGM</div>
          <div className="pgm-sidebar-titles hide-menu">
            <strong>PGM Soluções em TI</strong>
            <div className="pgm-sidebar-sub">ERP Enterprise</div>
          </div>
        </a>
      </div>

      <div className="pgm-sidebar-meta">
        <div className="pgm-meta-row">
          <div className="pgm-sidebar-flex-min">
            <label>Empresa</label>
            {workspace?.multiEmpresa && companies.length > 1 ? (
              <div className="workspace workspace--client-meta">
                <button
                  type="button"
                  className="workspace-btn workspace-btn--full"
                  aria-expanded={workspaceOpen}
                  onClick={(e) => {
                    e.stopPropagation();
                    setWorkspaceOpen((o) => !o);
                  }}
                >
                  <div className="workspace-avatar">{selectedCompany.initials}</div>
                  <div className="workspace-info">
                    <div className="workspace-name">{selectedCompany.name}</div>
                    <div className="workspace-sub">{workspace?.sub ?? ''}</div>
                  </div>
                  <svg className="workspace-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9" />
                  </svg>
                </button>
                <div className={`workspace-dropdown${workspaceOpen ? ' open' : ''}`}>
                  <div className="workspace-search">
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
                      </button>
                    ))}
                  </div>
                </div>
              </div>
            ) : (
              <p className="pgm-meta-date m-0">{workspace?.currentName ?? ''}</p>
            )}
          </div>
        </div>
      </div>

      <div className="scroll-sidebar ps ps--theme_default ps--active-y">
        <nav className="sidebar-nav">
          <ul id="sidebarnav" className="p-t-30">
            <li className="pgm-nav-section-label" aria-hidden="true">
              <span>Menu</span>
            </li>
            {navBlocks.map((b, idx) => {
              if (b.type === 'link') {
                const active = b.active || isItemPathActive(b.href);
                return (
                  <li key={`blk-${idx}`} className={active ? 'active' : ''}>
                    <a
                      href={b.href}
                      className={`waves-effect waves-dark${active ? ' active' : ''}`}
                      aria-expanded="false"
                      {...getTurboLinkProps(b.href, null)}
                    >
                      <i className={`fa ${b.iconFa}`} />
                      <span className="hide-menu">{b.label}</span>
                    </a>
                  </li>
                );
              }
              if (b.type === 'group') {
                const open = isGroupOpen(b);
                const parentActive = b.active || open;
                return (
                  <li key={b.id} className={`${parentActive ? 'active ' : ''}has-arrow-sub${parentActive ? ' selected' : ''}`}>
                    <a
                      href="javascript:void(0)"
                      className="waves-effect waves-dark has-arrow"
                      aria-expanded={open ? 'true' : 'false'}
                      onClick={(e) => {
                        e.preventDefault();
                        toggleSub(b.id);
                      }}
                    >
                      <i className={`fa ${b.iconFa}`} />
                      <span className="hide-menu">{b.label}</span>
                    </a>
                    <ul className={`collapse${open ? ' in' : ''}`}>
                      {b.items?.map((it, j) => {
                        const subAct = it.active || isItemPathActive(it.href);
                        return (
                          <li key={`${b.id}-${j}`} className={subAct ? 'active' : ''}>
                            <a href={it.href} className={`waves-effect waves-dark${subAct ? ' active' : ''}`} {...getTurboLinkProps(it.href, null)}>
                              {it.label}
                            </a>
                          </li>
                        );
                      })}
                    </ul>
                  </li>
                );
              }
              return null;
            })}
            <li id="mini-logout" className={isMiniSidebar ? '' : 'd-none'}>
              <a href="/users/logout" className="waves-effect waves-dark" aria-expanded="false">
                <i className="far fa-circle text-danger" />
                <span className="hide-menu">Sair</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>

      <div className="pgm-sidebar-footer">
        <div className="pgm-sidebar-collapse-row">
          <button
            type="button"
            className="pgm-sidebar-collapse-btn icon-btn pgm-sidebar-collapse-react"
            title="Recolher menu"
            aria-label="Recolher menu lateral"
            onClick={toggleMiniSidebar}
          >
            <i className="ti-angle-double-left" />
          </button>
        </div>
        <div className="user-profile">
          <div className={`user-pro-body preview-dd${userMenuOpen ? ' open' : ''}`} ref={userDdRef}>
            <a
              href="#"
              className="dropdown-toggle u-dropdown link hide-menu text-white d-flex align-items-center"
              role="button"
              aria-expanded={userMenuOpen}
              onClick={(e) => {
                e.preventDefault();
                setUserMenuOpen((o) => !o);
              }}
            >
              <span className="pgm-user-av">{userInitials}</span>
              <span className="hide-menu text-truncate pgm-cli-name-truncate">{userName}</span>
              <span className="caret hide-menu" aria-hidden="true" />
            </a>
            <div className="preview-dd-menu" role="menu">
              {footerLinks.map((fl, fidx) => (
                <a
                  key={`${fidx}-${fl.label}`}
                  className={`preview-dd-item${fl.danger ? ' preview-dd-item--danger' : ''}`}
                  href={fl.href}
                  {...getTurboLinkProps(fl.href, null)}
                >
                  {fl.label}
                </a>
              ))}
            </div>
          </div>
        </div>
      </div>
    </aside>
  );
}
