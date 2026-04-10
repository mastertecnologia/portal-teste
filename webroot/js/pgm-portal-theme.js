/**
 * Tema global PGM: apenas escuro (data-pgm-theme="dark", sem body.pgm-theme-light).
 * Mantém API mínima para scripts legados; alternância foi removida.
 */
(function ($) {
	'use strict';

	var STORAGE_KEY = 'pgmPortalTheme';

	function applyDark() {
		document.documentElement.setAttribute('data-pgm-theme', 'dark');
		if (document.body) {
			document.body.classList.remove('pgm-theme-light');
		}
		try {
			localStorage.setItem(STORAGE_KEY, 'dark');
		} catch (e) { /* ignore */ }
	}

	window.PgmPortalTheme = {
		STORAGE_KEY: STORAGE_KEY,
		apply: function () {
			applyDark();
		},
		initGuest: function () {
			$(function () {
				applyDark();
			});
		},
		initSidebarToggle: function () {
			/* sem alternância */
		}
	};
})(window.jQuery);
