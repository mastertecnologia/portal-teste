<?php
/**
 * Sino de notificações internas (equipe role 0). Sem migration: badge 0 e lista vazia.
 * @var \App\View\AppView $this
 */
$urlCount = $this->PgmPortalNotif->url(['controller' => 'PortalNotifications', 'action' => 'unreadCount']);
$urlList = $this->PgmPortalNotif->url(['controller' => 'PortalNotifications', 'action' => 'listJson']);
$urlMarkAll = $this->PgmPortalNotif->url(['controller' => 'PortalNotifications', 'action' => 'markAllRead']);
$urlMarkReadBase = rtrim($this->PgmPortalNotif->url(['controller' => 'PortalNotifications', 'action' => 'markRead']), '/');
$urlPrefs = $this->PgmPortalNotif->url(['controller' => 'PortalNotifications', 'action' => 'preferences']);
?>
<style>
.pgm-portal-notif-bell { position:relative;display:inline-block; }
.pgm-sf-actions .pgm-portal-notif-bell .pgm-bell-btn {
	width:28px;height:28px;border-radius:6px;border:none;background:transparent;font-size:15px;padding:0;
}
.pgm-portal-notif-bell .pgm-bell-btn {
	display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;
	border:1px solid #30363d;background:#161b22;color:#8b949e;text-decoration:none!important;
	transition:color .15s,border-color .15s,background .15s;font-size:14px;
}
.pgm-portal-notif-bell .pgm-bell-btn:hover { color:#5cdbc0;border-color:#1d9e75;background:#1c2230; }
.pgm-portal-notif-bell.pgm-notif-api-error .pgm-bell-btn { border-color:#d29922!important; }
.pgm-portal-notif-bell .pgm-bell-badge {
	position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 5px;border-radius:9px;
	font-size:10px;font-weight:700;line-height:18px;text-align:center;background:#f85149;color:#fff;
	display:none;
}
/* Painel flutuante fixo — escapa overflow da sidebar */
.pgm-notif-panel-fixed {
	display:none;position:fixed;z-index:9999;
	width:340px;max-height:420px;overflow-y:auto;
	background:#161b22;border:1px solid #30363d;border-radius:10px;
	box-shadow:0 8px 24px rgba(0,0,0,.45);
	font-family:'DM Sans',sans-serif;
}
.pgm-notif-panel-fixed.is-open { display:block; }
.pgm-notif-panel-header {
	display:flex;justify-content:space-between;align-items:center;
	padding:10px 14px;border-bottom:1px solid #21262d;
	color:#8b949e;font-size:11px;text-transform:uppercase;letter-spacing:.06em;
}
.pgm-portal-notif-mark-all { font-size:11px;color:#5cdbc0!important;cursor:pointer;text-decoration:none; }
.pgm-portal-notif-item {
	display:block;padding:10px 14px;border-bottom:1px solid #21262d;color:#c9d1d9!important;font-size:12px;text-decoration:none!important;
}
.pgm-portal-notif-item:hover { background:#1c2230;color:#e6edf3!important; }
.pgm-portal-notif-item.unread { border-left:3px solid #1d9e75;padding-left:11px; }
.pgm-portal-notif-item .pgm-nt-title { font-weight:600;color:#e6edf3;margin-bottom:2px; }
.pgm-portal-notif-item .pgm-nt-meta { font-size:10px;color:#6e7681;margin-top:4px; }
.pgm-portal-notif-footer { padding:8px 14px;border-top:1px solid #21262d;font-size:11px;text-align:center; }
.pgm-portal-notif-footer a { color:#5cdbc0!important; }
.pgm-notif-list-placeholder { font-size:12px; }
.pgm-portal-notif-prefs-link { font-size:11px; }
.pgm-nt-msg { opacity:.9; }
</style>
<div class="pgm-portal-notif-bell" id="pgmPortalNotifBell">
	<a href="#" class="pgm-bell-btn" title="Notificações" id="pgmBellToggle">
		<i class="fas fa-bell"></i>
		<span class="pgm-bell-badge" id="pgmBellBadge">0</span>
	</a>
</div>
<!-- Painel fixo (fora do sidebar overflow) -->
<div class="pgm-notif-panel-fixed" id="pgmNotifPanel">
	<div class="pgm-notif-panel-header">
		<span>Notificações</span>
		<a href="#" id="pgmMarkAllRead" class="pgm-portal-notif-mark-all">Marcar todas</a>
	</div>
	<div id="pgmNotifListBody" class="pgm-notif-list-body" style="min-height:48px;">
		<div class="text-muted text-center py-3 pgm-notif-list-placeholder">Carregando…</div>
	</div>
	<div class="pgm-portal-notif-footer">
		<small class="text-muted">Eventos do módulo de clientes e integrações</small><br>
		<a href="<?= h($urlPrefs) ?>" class="pgm-portal-notif-prefs-link" data-turbo="false">Preferências de alertas</a>
	</div>
</div>
<script>
(function() {
	if (window.__pgmPortalNotifBellInit) return;
	window.__pgmPortalNotifBellInit = true;

	var urlCount = <?= json_encode($urlCount) ?>;
	var urlList = <?= json_encode($urlList) ?>;
	var urlMarkAll = <?= json_encode($urlMarkAll) ?>;
	var urlMarkReadBase = <?= json_encode($urlMarkReadBase) ?>;
	var pollTimer = null;

	function escHtml(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;');
	}

	function qs(id) {
		return document.getElementById(id);
	}

	function refreshCount() {
		var bell = qs('pgmPortalNotifBell');
		var badge = qs('pgmBellBadge');
		if (!bell || !badge) return;

		window.PGMHttp.httpGetJson(urlCount)
			.then(function(d) {
				var n = (d && typeof d.count !== 'undefined') ? parseInt(d.count, 10) : 0;
				bell.classList.remove('pgm-notif-api-error');
				if (n > 0) {
					badge.textContent = n > 99 ? '99+' : String(n);
					badge.style.display = 'inline-block';
				} else {
					badge.style.display = 'none';
				}
			})
			.catch(function(err) {
				if (window.console && console.warn) {
					console.warn('PGM: falha ao obter contagem de notificações', err && err.message);
				}
				bell.classList.add('pgm-notif-api-error');
			});
	}

	function iconForType(t) {
		if (t === 'error') return 'fa-exclamation-circle text-danger';
		if (t === 'warning') return 'fa-exclamation-triangle text-warning';
		if (t === 'success') return 'fa-check-circle text-success';
		return 'fa-info-circle text-info';
	}

	function loadList() {
		var body = qs('pgmNotifListBody');
		if (!body) return;
		body.innerHTML = '<div class="text-muted text-center py-3 pgm-notif-list-placeholder">Carregando…</div>';

		window.PGMHttp.httpGetJson(urlList).then(function(d) {
			var items = (d && d.items) ? d.items : [];
			if (!items.length) {
				body.innerHTML = '<div class="text-muted text-center py-3 pgm-notif-list-placeholder">Nenhuma notificação</div>';
				return;
			}
			var h = '';
			items.forEach(function(it) {
				var cls = 'pgm-portal-notif-item' + (it.is_read ? '' : ' unread');
				var markId = (it.id && !it.is_read) ? it.id : '';
				h += '<a class="' + cls + '" href="' + escHtml(it.action_url || '#') + '" data-mark-id="' + escHtml(markId) + '">';
				h += '<div><i class="fas ' + iconForType(it.type) + ' mr-1"></i><span class="pgm-nt-title">' + escHtml(it.title || '') + '</span></div>';
				if (it.message) h += '<div class="pgm-nt-msg">' + escHtml(it.message) + '</div>';
				h += '<div class="pgm-nt-meta">' + escHtml(it.created_human || '') + '</div></a>';
			});
			body.innerHTML = h;
		}).catch(function() {
			body.innerHTML = '<div class="text-muted text-center py-3 pgm-notif-list-placeholder">Indisponível</div>';
		});
	}

	function positionPanel() {
		var btn = qs('pgmBellToggle');
		var panel = qs('pgmNotifPanel');
		if (!btn || !panel) return;
		var winH = window.innerHeight || document.documentElement.clientHeight || 0;
		var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
		var maxH = winH - 40;
		panel.style.maxHeight = maxH + 'px';

		var rect = btn.getBoundingClientRect();
		if (!rect || isNaN(rect.left)) {
			panel.style.top = (scrollTop + 20) + 'px';
			panel.style.left = 'auto';
			panel.style.right = '20px';
			return;
		}
		var left = rect.left + window.pageXOffset + rect.width + 8;
		var top = scrollTop + 20;
		var winW = window.innerWidth || document.documentElement.clientWidth || 0;
		if (left + 340 > winW) {
			left = rect.left + window.pageXOffset - 340 - 8;
		}
		panel.style.top = top + 'px';
		panel.style.left = left + 'px';
		panel.style.right = 'auto';
	}

	function handleListClick(event) {
		var anchor = event.target.closest('a.pgm-portal-notif-item');
		var body = qs('pgmNotifListBody');
		if (!anchor || !body || !body.contains(anchor)) return;
		var mid = anchor.getAttribute('data-mark-id');
		var href = anchor.getAttribute('href') || '#';
		if (mid) {
			event.preventDefault();
			window.PGMHttp.httpPost(urlMarkReadBase + '/' + encodeURIComponent(mid), {})
				.then(function() {
					refreshCount();
					if (href !== '#') window.location.href = href;
				})
				.catch(function() {
					if (href !== '#') window.location.href = href;
				});
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		if (!window.PGMHttp || typeof window.PGMHttp.httpGetJson !== 'function' || typeof window.PGMHttp.httpPost !== 'function') {
			if (window.console && console.warn) console.warn('PGM: PGMHttp indisponível');
			return;
		}
		var bellToggle = qs('pgmBellToggle');
		var panel = qs('pgmNotifPanel');
		var markAll = qs('pgmMarkAllRead');
		var listBody = qs('pgmNotifListBody');
		if (!bellToggle || !panel || !markAll || !listBody) return;

		refreshCount();
		if (!pollTimer) {
			pollTimer = window.setInterval(refreshCount, 60000);
		}

		bellToggle.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var wasOpen = panel.classList.contains('is-open');
			if (wasOpen) {
				panel.classList.remove('is-open');
			} else {
				loadList();
				panel.classList.add('is-open');
				positionPanel();
			}
		});

		document.addEventListener('click', function(e) {
			if (!e.target.closest('#pgmNotifPanel, #pgmBellToggle, #pgmSidebarMenuOpenNotif')) {
				panel.classList.remove('is-open');
			}
		});

		markAll.addEventListener('click', function(e) {
			e.preventDefault();
			if (!window.PGMHttp.getCsrfToken()) { return; }
			window.PGMHttp.httpPost(urlMarkAll, {})
				.then(function() {
					refreshCount();
					loadList();
				})
				.catch(function() {
					refreshCount();
					loadList();
				});
		});

		listBody.addEventListener('click', handleListClick);
		window.addEventListener('resize', function() {
			if (panel.classList.contains('is-open')) positionPanel();
		});
	});
})();
</script>
