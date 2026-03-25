/**
 * PGM — aviso de expiração de sessão (pt-BR).
 * Incluir no layout após jQuery, Bootstrap JS e /js/jquery.sessionTimeout.min.simple.modal.js
 *
 * Exemplo no layout (.ctp):
 *   $this->Html->script('/js/jquery.sessionTimeout.min.simple.modal');
 *   $this->Html->script('/js/pgm-session-timeout-init');
 */
(function ($) {
	"use strict";

	function initPlugin() {
		if (typeof $.sessionTimeout !== "function") {
			return;
		}
		$.sessionTimeout({
			title: "Sua sessão vai expirar",
			message:
				"Por segurança, sua sessão será encerrada em breve. Deseja permanecer conectado?",
			titleMessage: "Aviso: tempo da sessão",
			stayConnectedBtn: "Permanecer conectado",
			logoutBtn: "Sair",
			keepAliveUrl: "/keep-alive",
			redirUrl: "/users/login",
			logoutUrl: "/users/logout",
			warnAfter: 9e5,
			redirAfter: 12e5,
			ignoreUserActivity: false,
		});
	}

	$(document).ready(initPlugin);
})(jQuery);
