/**
 * Deep-link por hash (#cliente, #acessos, #acessosCliente, #usuarios, #contratos, #token) sem alterar rotas.
 * Atualiza o hash ao trocar de aba (replaceState) para permitir copiar link.
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

	function paneExists(id) {
		if (!id || id.charAt(0) !== '#') {
			return false;
		}
		var $p = $(id);
		return $p.length && $p.hasClass('tab-pane');
	}

	function activateFromHash() {
		var raw = (window.location.hash || '').replace(/^#/, '');
		if (!raw) {
			return;
		}
		var sel = '#' + raw;
		if (!paneExists(sel)) {
			return;
		}
		var $link = $nav.find('a[href="' + sel + '"]');
		if ($link.length) {
			$link.tab('show');
		}
	}

	$nav.find('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var href = $(e.target).attr('href');
		if (href && href.charAt(0) === '#' && window.history && window.history.replaceState) {
			try {
				window.history.replaceState(null, '', href);
			} catch (err) { /* ignore */ }
		}
		$nav.find('a[data-toggle="tab"]').attr('aria-selected', 'false');
		$(e.target).attr('aria-selected', 'true');
	});

	$(function () {
		activateFromHash();
	});
	$(window).on('hashchange', activateFromHash);
})();
