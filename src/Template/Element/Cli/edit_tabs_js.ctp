/**
 * Deep-link por hash (#cliente, #acessos, #usuarios, #contratos, #ativos, #token)
 * ou query ?tab= (mesmos ids, sem #) — rotas e painéis legados inalterados.
 */
(function () {
	if (typeof jQuery === 'undefined') {
		return;
	}
	var $ = jQuery;
	var $nav = $('#cli-edit-tabs-nav');
	if (!$nav.length) {
		return;
	}

	var TAB_IDS = ['cliente', 'acessos', 'acessosCliente', 'usuarios', 'contratos', 'ativos', 'token'];

	function paneExists(id) {
		if (!id || id.charAt(0) !== '#') {
			return false;
		}
		var $p = $(id);
		return $p.length && $p.hasClass('tab-pane');
	}

	function normalizeTabId(raw) {
		if (!raw) {
			return '';
		}
		var id = String(raw).replace(/^#/, '').trim();
		return TAB_IDS.indexOf(id) >= 0 ? id : '';
	}

	function tabHashFromLocation() {
		var fromHash = normalizeTabId((window.location.hash || '').replace(/^#/, ''));
		if (fromHash) {
			return fromHash;
		}
		try {
			var params = new URLSearchParams(window.location.search || '');
			return normalizeTabId(params.get('tab') || '');
		} catch (e) {
			return '';
		}
	}

	function activateTab(id) {
		if (!id) {
			return;
		}
		var sel = '#' + id;
		if (!paneExists(sel)) {
			return;
		}
		var $link = $nav.find('a[href="' + sel + '"]');
		if ($link.length) {
			$link.tab('show');
		}
	}

	function syncUrlHash(href) {
		if (href && href.charAt(0) === '#' && window.history && window.history.replaceState) {
			try {
				var path = window.location.pathname + window.location.search;
				window.history.replaceState(null, '', path + href);
			} catch (err) { /* ignore */ }
		}
	}

	$nav.find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var href = $(e.target).attr('href');
		syncUrlHash(href);
		$nav.find('a[data-toggle="tab"]').attr('aria-selected', 'false');
		$(e.target).attr('aria-selected', 'true');
	});

	$(document).on('click', 'a.cli-sf-tab-jump[href*="#"]', function (e) {
		var href = $(this).attr('href') || '';
		var hashIdx = href.indexOf('#');
		if (hashIdx < 0) {
			return;
		}
		var tabId = normalizeTabId(href.slice(hashIdx + 1));
		if (!tabId) {
			return;
		}
		e.preventDefault();
		activateTab(tabId);
		syncUrlHash('#' + tabId);
	});

	$(function () {
		activateTab(tabHashFromLocation());
	});
	$(window).on('hashchange', function () {
		activateTab(normalizeTabId((window.location.hash || '').replace(/^#/, '')));
	});
})();
