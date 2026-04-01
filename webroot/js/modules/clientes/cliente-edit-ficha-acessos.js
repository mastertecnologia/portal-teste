/**
 * Ficha Clientes/edit — token portal, modal de dados, senha administrativa nos acessos, inativar.
 * Carregar depois de cliente-edit.js, PgmClienteEditConfig e cliente-edit-ficha.js.
 */
(function ($) {
	'use strict';

	function conf() {
		return window.PgmClienteEditConfig || {};
	}

	function bootboxAlert(html) {
		if (window.bootbox && typeof window.bootbox.alert === 'function') {
			window.bootbox.alert(html);
		} else {
			alert($('<div/>').html(html).text());
		}
	}

	$(function () {
		var C = conf();
		if (!C.clienteId) {
			return;
		}
		var urls = C.urls || {};
		var U = window.PgmClienteEditUtils || {};

		$('.btn-atualizaToken').click(function (e) {
			e.preventDefault();
			var urlTok = urls.updateToken || '';
			if (!C.isClientePortal) {
				window.location = urlTok;
			} else {
				$('#modal-confirmaToken').modal('toggle');
				$('#modal-confirmaToken').modal('show');
			}
		});

		$('.btn-atualizaDentroDoModal').click(function (e) {
			e.preventDefault();
			var tok = U.pgmCsrfToken ? U.pgmCsrfToken() : '';
			$.ajax({
				type: 'POST',
				url: urls.verificadadoscliente || '',
				data: {
					idcliente: C.clienteId,
					nomeresponsavel: $('#confirmaNomeResponsavel').val() || '',
					cpf: $('#confirmaCpfResponsavel').val() || '',
					rg: $('#confirmaRgResponsavel').val() || '',
					_csrfToken: tok,
				},
				success: function (data) {
					if (data === 'tudocerto') {
						window.location = urls.updateToken || '';
					} else {
						bootboxAlert('<p class="text-center" style="font-size: 1.2rem">Os dados inseridos não conferem com os cadastrados no bando de dados.</p>');
					}
				},
			});
		});

		$('.senha').click(function () {
			var id = $(this).attr('data-id');
			$('#idsenha').val(id);
			$('#modal-senha').modal('toggle');
			$('#modal-senha').modal('show');
		});

		$('.btn-verificasenha').click(function (e) {
			e.preventDefault();
			var id = $('#idsenha').val();
			var senha = $('#senhaadministrativa').val();
			var idcliente = C.clienteId;
			var csrf = U.pgmCsrfToken ? U.pgmCsrfToken() : '';
			$.ajax({
				type: 'post',
				url: (urls.verificasenha || '') + '/',
				data: { id: id, senhaadm: senha, idcliente: idcliente, _csrfToken: csrf },
				success: function (data) {
					$('#modal-senha').modal('toggle');
					bootboxAlert(data);
				},
				error: function () {
					alert('erro');
				},
			});
		});

		$('#exibirsenha').change(function () {
			if ($(this).is(':checked')) {
				$('#senhaadministrativa').attr('type', 'text');
			} else {
				$('#senhaadministrativa').attr('type', 'password');
			}
		});

		$('#exibirsenhacliente').change(function () {
			if ($(this).is(':checked')) {
				$('#senha').attr('type', 'text');
			} else {
				$('#senha').attr('type', 'password');
			}
		});

		$('.btn-inativar-cliente').click(function (e) {
			e.preventDefault();
			if (confirm('Você confirma a inativação deste cliente no portal e no ERP?')) {
				$('#inativo').prop('checked', true);
				$('#form-edit-cliente').submit();
			}
		});
	});
})(jQuery);
