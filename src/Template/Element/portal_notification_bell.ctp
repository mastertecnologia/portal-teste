<?php
/**
 * Sino de notificações internas (equipe role 0). Sem migration: badge 0 e lista vazia.
 * @var \App\View\AppView $this
 */
$urlCount = $this->Url->build(['controller' => 'PortalNotifications', 'action' => 'unreadCount']);
$urlList = $this->Url->build(['controller' => 'PortalNotifications', 'action' => 'listJson']);
$urlMarkAll = $this->Url->build(['controller' => 'PortalNotifications', 'action' => 'markAllRead']);
$urlMarkReadBase = rtrim($this->Url->build(['controller' => 'PortalNotifications', 'action' => 'markRead']), '/');
$urlPrefs = $this->Url->build(['controller' => 'PortalNotifications', 'action' => 'preferences']);
?>
<style>
.pgm-portal-notif-bell { position: relative; }
.pgm-portal-notif-bell .pgm-bell-btn {
	display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;
	border:1px solid #30363d;background:#161b22;color:#8b949e;text-decoration:none!important;
	transition:color .15s,border-color .15s,background .15s;
}
.pgm-portal-notif-bell .pgm-bell-btn:hover { color:#5cdbc0;border-color:#1d9e75;background:#1c2230; }
.pgm-portal-notif-bell .pgm-bell-badge {
	position:absolute;top:-4px;right:-4px;min-width:18px;height:18px;padding:0 5px;border-radius:9px;
	font-size:10px;font-weight:700;line-height:18px;text-align:center;background:#f85149;color:#fff;
	display:none;
}
.pgm-portal-notif-dropdown {
	background:#161b22!important;border:1px solid #30363d!important;box-shadow:0 8px 24px rgba(0,0,0,.35);
	padding:0!important;
}
.pgm-portal-notif-dropdown .dropdown-header { color:#8b949e;font-size:11px;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #21262d;padding:10px 14px; }
.pgm-portal-notif-item {
	display:block;padding:10px 14px;border-bottom:1px solid #21262d;color:#c9d1d9!important;font-size:12px;text-decoration:none!important;
}
.pgm-portal-notif-item:hover { background:#1c2230;color:#e6edf3!important; }
.pgm-portal-notif-item.unread { border-left:3px solid #1d9e75;padding-left:11px; }
.pgm-portal-notif-item .pgm-nt-title { font-weight:600;color:#e6edf3;margin-bottom:2px; }
.pgm-portal-notif-item .pgm-nt-meta { font-size:10px;color:#6e7681;margin-top:4px; }
.pgm-portal-notif-footer { padding:8px 14px;border-top:1px solid #21262d;font-size:11px; }
.pgm-portal-notif-footer a { color:#5cdbc0!important; }
</style>
<div class="dropdown pgm-portal-notif-bell" id="pgmPortalNotifBell">
	<a href="#" class="pgm-bell-btn dropdown-toggle" data-toggle="dropdown" data-display="static" aria-expanded="false" title="Notificações" id="pgmBellToggle">
		<i class="fas fa-bell"></i>
		<span class="pgm-bell-badge" id="pgmBellBadge">0</span>
	</a>
	<div class="dropdown-menu dropdown-menu-right pgm-portal-notif-dropdown" aria-labelledby="pgmBellToggle">
		<div class="dropdown-header d-flex justify-content-between align-items-center">
			<span>Notificações</span>
			<a href="#" id="pgmMarkAllRead" style="font-size:11px;color:#5cdbc0!important;">Marcar todas</a>
		</div>
		<div id="pgmNotifListBody" style="min-height:48px;">
			<div class="text-muted text-center py-3" style="font-size:12px;">Carregando…</div>
		</div>
		<div class="pgm-portal-notif-footer text-center">
			<small class="text-muted">Eventos do módulo de clientes e integrações</small><br>
			<a href="<?= h($urlPrefs) ?>" style="font-size:11px;">Preferências de alertas</a>
		</div>
	</div>
</div>
<script>
(function() {
	var urlCount = <?= json_encode($urlCount) ?>;
	var urlList = <?= json_encode($urlList) ?>;
	var urlMarkAll = <?= json_encode($urlMarkAll) ?>;
	var urlMarkReadBase = <?= json_encode($urlMarkReadBase) ?>;

	function csrfToken() {
		var m = document.querySelector('meta[name="csrfToken"]');
		if (m && m.getAttribute('content')) return m.getAttribute('content');
		var inp = document.querySelector('input[name="_csrfToken"]');
		return inp ? inp.value : '';
	}

	function refreshCount() {
		$.getJSON(urlCount).done(function(d) {
			var n = (d && typeof d.count !== 'undefined') ? parseInt(d.count, 10) : 0;
			var $b = $('#pgmBellBadge');
			if (n > 0) { $b.text(n > 99 ? '99+' : n).show(); } else { $b.hide(); }
		});
	}

	function iconForType(t) {
		if (t === 'error') return 'fa-exclamation-circle text-danger';
		if (t === 'warning') return 'fa-exclamation-triangle text-warning';
		if (t === 'success') return 'fa-check-circle text-success';
		return 'fa-info-circle text-info';
	}

	function loadList() {
		var $body = $('#pgmNotifListBody');
		$body.html('<div class="text-muted text-center py-3" style="font-size:12px;">Carregando…</div>');
		$.getJSON(urlList).done(function(d) {
			var items = (d && d.items) ? d.items : [];
			if (!items.length) {
				$body.html('<div class="text-muted text-center py-3" style="font-size:12px;">Nenhuma notificação</div>');
				return;
			}
			var h = '';
			items.forEach(function(it) {
				var cls = 'pgm-portal-notif-item' + (it.is_read ? '' : ' unread');
				var markId = (it.id && !it.is_read) ? it.id : '';
				h += '<a class="' + cls + '" href="' + (it.action_url || '#') + '" data-mark-id="' + markId + '">';
				h += '<div><i class="fas ' + iconForType(it.type) + ' mr-1"></i><span class="pgm-nt-title">' + $('<div>').text(it.title || '').html() + '</span></div>';
				if (it.message) h += '<div style="opacity:.9;">' + $('<div>').text(it.message).html() + '</div>';
				h += '<div class="pgm-nt-meta">' + (it.created_human || '') + '</div></a>';
			});
			$body.html(h);
			$body.find('a.pgm-portal-notif-item').on('click', function(e) {
				var $a = $(this);
				var mid = $a.attr('data-mark-id');
				var href = $a.attr('href') || '#';
				var tok = csrfToken();
				if (mid && tok) {
					e.preventDefault();
					$.post(urlMarkReadBase + '/' + encodeURIComponent(mid), { _csrfToken: tok })
						.done(function() { refreshCount(); if (href !== '#') { window.location.href = href; } })
						.fail(function() { if (href !== '#') { window.location.href = href; } });
				} else if (mid && !tok) {
					if (href !== '#') { return; }
				}
			});
		}).fail(function() {
			$body.html('<div class="text-muted text-center py-3" style="font-size:12px;">Indisponível</div>');
		});
	}

	$(function() {
		refreshCount();
		setInterval(refreshCount, 60000);
		$('#pgmPortalNotifBell').on('show.bs.dropdown', function() { loadList(); });
		$('#pgmMarkAllRead').on('click', function(e) {
			e.preventDefault();
			var tok = csrfToken();
			if (!tok) { return; }
			$.post(urlMarkAll, { _csrfToken: tok }).always(function() { refreshCount(); loadList(); });
		});
	});
})();
</script>
