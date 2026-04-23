<?php
/**
 * Navegação parcial: marca links da sidebar para alvo turbo-frame id pgm-main-frame.
 * Espera <turbo-frame id="pgm-main-frame"> no layout. Opcional: pgmLayoutNoTopbarMinHeight (default.ctp).
 */
?>
		// Navegação parcial: Turbo Frame troca só a coluna principal; sidebar permanece no DOM.
		(function pgmTurboShellInit() {
			var FRAME_ID = 'pgm-main-frame';

			function pgmNormalizePath(p) {
				if (!p) {
					return '/';
				}
				p = String(p).split('?')[0].replace(/\/+/g, '/');
				if (p.length > 1 && p.slice(-1) === '/') {
					p = p.slice(0, -1);
				}
				return p === '' ? '/' : p;
			}

			function pgmTurboSameOriginHref(a) {
				var raw = (a.getAttribute('href') || '').trim();
				if (!raw || raw === '#' || raw.indexOf('javascript:') === 0) {
					return null;
				}
				try {
					var u = new URL(raw, window.location.href);
					if (u.origin !== window.location.origin) {
						return null;
					}
					if (/\/users\/logout(\/|$)/.test(u.pathname)) {
						return null;
					}
					return u;
				} catch (e1) {
					return null;
				}
			}

			function pgmTurboMarkNavLinks() {
				document.querySelectorAll(
					'aside.pgm-sidebar-shell .pgm-sidebar-brand a[href], aside.pgm-sidebar-shell .scroll-sidebar a[href], aside.pgm-sidebar-shell .pgm-sidebar-footer a.preview-dd-item[href], aside.pgm-sidebar-shell .pgm-sidebar-footer a.dropdown-item[href]'
				).forEach(function (a) {
					if (a.getAttribute('data-turbo') === 'false') {
						return;
					}
					if (a.getAttribute('target') === '_blank') {
						return;
					}
					if (a.closest('.dropdown-menu')) {
						return;
					}
					if (!pgmTurboSameOriginHref(a)) {
						return;
					}
					a.setAttribute('data-turbo-frame', FRAME_ID);
					a.setAttribute('data-turbo-action', 'advance');
				});
			}

			function pgmTurboSyncSidebarActive() {
				var side = document.querySelector('aside.pgm-sidebar-shell');
				if (!side) {
					return;
				}
				if (window.__PGM_REACT_SIDEBAR__) {
					pgmTurboRebindDynamicUi();
					return;
				}
				var path = pgmNormalizePath(window.location.pathname);
				var links = [];
				side.querySelectorAll('a[href]').forEach(function (a) {
					if (a.closest('.dropdown-menu')) {
						return;
					}
					var u = pgmTurboSameOriginHref(a);
					if (!u) {
						return;
					}
					try {
						var p = pgmNormalizePath(u.pathname);
						if (path === p || (p !== '/' && path.indexOf(p + '/') === 0)) {
							links.push({ a: a, plen: p.length, nav: a.classList.contains('pgm-nav-link') ? 1 : 0 });
						}
					} catch (err) {
					}
				});
				links.sort(function (x, y) {
					if (y.plen !== x.plen) {
						return y.plen - x.plen;
					}
					return y.nav - x.nav;
				});
				var best = links.length ? links[0].a : null;

				side.querySelectorAll('a.pgm-nav-link').forEach(function (a) {
					a.classList.toggle('active', !!best && a === best);
				});

				var nav = document.getElementById('sidebarnav');
				if (nav && !side.querySelector('a.pgm-nav-link')) {
					nav.querySelectorAll('li.active').forEach(function (li) {
						li.classList.remove('active');
					});
					nav.querySelectorAll('li.selected').forEach(function (li) {
						li.classList.remove('selected');
					});
					nav.querySelectorAll('ul.collapse.in').forEach(function (ul) {
						ul.classList.remove('in');
					});
					nav.querySelectorAll('a.has-arrow').forEach(function (t) {
						t.setAttribute('aria-expanded', 'false');
					});
					if (best) {
						var collapse = best.closest('ul.collapse');
						if (collapse) {
							collapse.classList.add('in');
							var innerLi = best.closest('li');
							if (innerLi) {
								innerLi.classList.add('active');
							}
							var outer = collapse.closest('li.has-arrow-sub');
							if (outer) {
								outer.classList.add('active', 'selected');
								var toggler = outer.querySelector('a.has-arrow');
								if (toggler) {
									toggler.setAttribute('aria-expanded', 'true');
								}
							}
						} else {
							var topLi = best.closest('#sidebarnav > li');
							if (topLi && !topLi.classList.contains('has-arrow-sub')) {
								topLi.classList.add('active');
							}
						}
					}
				}

				if (typeof pgmSidebarApplyNavSectionStates === 'function') {
					pgmSidebarApplyNavSectionStates();
				}
				if (typeof pgmSidebarLucideRefresh === 'function') {
					pgmSidebarLucideRefresh();
				}
			}

			function pgmTurboRebindDynamicUi() {
				if (typeof pgmLayoutNoTopbarMinHeight === 'function') {
					pgmLayoutNoTopbarMinHeight();
				}
			}

			if (window.Turbo) {
				try {
					Turbo.session.drive = false;
				} catch (e2) {
				}
				document.addEventListener('turbo:frame-load', function (e) {
					if (!e.target || e.target.id !== FRAME_ID) {
						return;
					}
					pgmTurboSyncSidebarActive();
					pgmTurboRebindDynamicUi();
				});
			}

			function pgmTurboBoot() {
				pgmTurboMarkNavLinks();
				pgmTurboSyncSidebarActive();
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', pgmTurboBoot);
			} else {
				pgmTurboBoot();
			}

			window.pgmTurboShellMarkNavLinks = pgmTurboMarkNavLinks;
		})();
