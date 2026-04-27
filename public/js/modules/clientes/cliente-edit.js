/**
 * Módulo da ficha Clientes/edit (ETAPAS 9–11): CSRF para AJAX legado + helpers de e-mail nos modais.
 * Inclusão na view: $this->Html->script('/js/modules/clientes/cliente-edit.js') antes do bloco inline.
 */
(function (w) {
	'use strict';

	function pgmCsrfToken() {
		var m = document.querySelector('meta[name="csrfToken"]');
		if (m && m.getAttribute('content')) {
			return m.getAttribute('content');
		}
		var inp = document.querySelector('input[name="_csrfToken"]');
		return inp ? inp.value : '';
	}

	function normalizaEmails(texto) {
		if (!texto) {
			return '';
		}
		var partes = texto
			.replace(/[\r\n]+/g, ';')
			.split(';')
			.map(function (p) {
				return p.trim();
			})
			.filter(function (p) {
				return p.length > 0;
			});

		return partes.join('; ');
	}

	function formataEmailsParaEdicao(texto) {
		if (!texto) {
			return '';
		}

		return texto
			.split(';')
			.map(function (p) {
				return p.trim();
			})
			.filter(function (p) {
				return p.length > 0;
			})
			.join('\n');
	}

	function atualizaDisplayEmails(texto) {
		if (!texto) {
			$('#emailresponsavel_display').val('');
			$('#emailresponsavel_display').attr('placeholder', 'Nenhum e-mail de contato cadastrado');

			return;
		}
		$('#emailresponsavel_display').val(texto.replace(/;/g, '; '));
	}

	function atualizaDisplayEmailsFaturamento(texto) {
		if (!texto) {
			$('#email_faturamento_display').val('');
			$('#email_faturamento_display').attr('placeholder', 'Nenhum e-mail de faturamento cadastrado');

			return;
		}
		$('#email_faturamento_display').val(texto.replace(/;/g, '; '));
	}

	w.PgmClienteEditUtils = {
		pgmCsrfToken: pgmCsrfToken,
		normalizaEmails: normalizaEmails,
		formataEmailsParaEdicao: formataEmailsParaEdicao,
		atualizaDisplayEmails: atualizaDisplayEmails,
		atualizaDisplayEmailsFaturamento: atualizaDisplayEmailsFaturamento,
	};
})(window);
