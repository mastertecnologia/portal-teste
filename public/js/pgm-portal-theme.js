/**
 * Tema global PGM: alternância sem reload, espelho em localStorage (chave pgmPortalTheme).
 * Requer jQuery. Botões logados: classe .pgm-js-theme-toggle (persiste via selectTheme).
 */
(function ($) {
	'use strict';

	var STORAGE_KEY = 'pgmPortalTheme';
	var LOGGED_TOGGLE_SEL = '.pgm-js-theme-toggle';

	/** Token CSRF CakePHP (Security): meta[name="csrfToken"] ou input hidden do formulário. */
	function readCsrfToken() {
		var m = document.querySelector('meta[name="csrfToken"]');
		if (m && m.getAttribute('content')) {
			return m.getAttribute('content');
		}
		var inp = document.querySelector('input[name="_csrfToken"]');
		if (inp && inp.value) {
			return inp.value;
		}
		return '';
	}

	function applyTheme(mode) {
		if (mode !== 'light' && mode !== 'dark') {
			return;
		}
		document.documentElement.setAttribute('data-pgm-theme', mode);
		if (!document.body) {
			return;
		}
		if (mode === 'light') {
			document.body.classList.add('pgm-theme-light');
		} else {
			document.body.classList.remove('pgm-theme-light');
		}
	}

	function updateToggleButton($btn, mode) {
		if (!$btn || !$btn.length) {
			return;
		}
		var isLight = mode === 'light';
		$btn.data('current', mode);
		$btn.attr('title', isLight ? 'Mudar para tema escuro' : 'Mudar para tema claro');
		$btn.attr('aria-pressed', isLight ? 'true' : 'false');
		$btn.attr('aria-label', isLight ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro');
		var $icon = $btn.find('.pgm-tt-icon');
		if ($icon.length) {
			if ($btn.hasClass('pgm-theme-toggle-btn--icon')) {
				$icon.html(isLight ? '<i class="fas fa-sun" aria-hidden="true"></i>' : '<i class="fas fa-moon" aria-hidden="true"></i>');
			} else {
				$icon.text(isLight ? '☀️' : '🌙');
			}
		}
		var $lbl = $btn.find('.pgm-tt-label');
		if ($lbl.length) {
			$lbl.text(isLight ? 'Claro' : 'Escuro');
		}
	}

	function persistLocal(mode) {
		try {
			localStorage.setItem(STORAGE_KEY, mode);
		} catch (e) { /* ignore */ }
	}

	window.PgmPortalTheme = {
		STORAGE_KEY: STORAGE_KEY,
		apply: applyTheme,
		/**
		 * Telas sem sessão ou preferência só local.
		 * @param {string} defaultMode 'light'|'dark'
		 * @param {string|null} serverMode se 'light'|'dark', ignora localStorage no primeiro paint
		 */
		initGuest: function (defaultMode, serverMode) {
			var mode;
			if (serverMode === 'light' || serverMode === 'dark') {
				mode = serverMode;
			} else {
				var v;
				try {
					v = localStorage.getItem(STORAGE_KEY);
				} catch (e) {
					v = null;
				}
				mode = v === 'dark' || v === 'light' ? v : defaultMode || 'light';
			}
			$(function () {
				applyTheme(mode);
				var $t = $('#pgmAuthThemeToggle');
				if ($t.length) {
					var isLight = mode === 'light';
					$t.attr('aria-label', isLight ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro');
					$t.find('.pgm-auth-tt-ico').text(isLight ? '☀️' : '🌙');
					$t.find('.pgm-auth-tt-txt').text(isLight ? 'Claro' : 'Escuro');
				}
			});
			$(document).on('click', '#pgmAuthThemeToggle', function () {
				var cur = document.documentElement.getAttribute('data-pgm-theme') || 'light';
				var next = cur === 'dark' ? 'light' : 'dark';
				applyTheme(next);
				persistLocal(next);
				var $t = $(this);
				var isLight = next === 'light';
				$t.attr('aria-label', isLight ? 'Tema claro ativo. Ativar escuro' : 'Tema escuro ativo. Ativar claro');
				$t.find('.pgm-auth-tt-ico').text(isLight ? '☀️' : '🌙');
				$t.find('.pgm-auth-tt-txt').text(isLight ? 'Claro' : 'Escuro');
			});
		},
		/** Persiste no usuário (POST selectTheme). Atualiza todos os .pgm-js-theme-toggle */
		initSidebarToggle: function (postUrl) {
			$(document).off('click.pgmPortalTheme', LOGGED_TOGGLE_SEL).on('click.pgmPortalTheme', LOGGED_TOGGLE_SEL, function (e) {
				e.preventDefault();
				var $btn = $(this);
				var current = $btn.data('current');
				if (current !== 'dark' && current !== 'light') {
					current = document.documentElement.getAttribute('data-pgm-theme') || 'dark';
				}
				var next = current === 'dark' ? 'light' : 'dark';
				var prev = current;
				applyTheme(next);
				$(LOGGED_TOGGLE_SEL).each(function () {
					updateToggleButton($(this), next);
				});
				persistLocal(next);
				var payload = { theme: next };
				var tok = readCsrfToken();
				if (tok) {
					payload._csrfToken = tok;
				}
				$.ajax({
					type: 'POST',
					url: postUrl,
					data: payload,
					dataType: 'json',
					success: function (data) {
						if (!data || data.ok !== true) {
							applyTheme(prev);
							$(LOGGED_TOGGLE_SEL).each(function () {
								updateToggleButton($(this), prev);
							});
							persistLocal(prev);
							if (window.bootbox) {
								bootbox.alert('Erro ao trocar o tema. Tente novamente.');
							}
						}
					},
					error: function () {
						applyTheme(prev);
						$(LOGGED_TOGGLE_SEL).each(function () {
							updateToggleButton($(this), prev);
						});
						persistLocal(prev);
						if (window.bootbox) {
							bootbox.alert('Erro ao trocar o tema. Tente novamente.');
						}
					}
				});
			});
		}
	};
})(window.jQuery);
