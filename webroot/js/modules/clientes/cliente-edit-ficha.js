/**
 * Comportamento principal da ficha Clientes/edit (datepicker, modo leitura/edição, máscaras, DataTables, e-mails, IE, PF/PJ).
 * Token/senhas de acessos: ver cliente-edit-ficha-acessos.js (carregado a seguir na view).
 * Depende de: jQuery, PgmClienteEditUtils (cliente-edit.js), window.PgmClienteEditConfig (definido na view).
 */
(function ($) {
	'use strict';

	function conf() {
		return window.PgmClienteEditConfig || {};
	}

	window.SomenteNumero = function (e) {
		var tecla = window.event ? event.keyCode : e.which;
		if (tecla > 47 && tecla < 58) {
			return true;
		}
		if (tecla === 8 || tecla === 0) {
			return true;
		}

		return false;
	};

	window.cliFichaEditing = false;

	function cliFichaPrepareSubmit() {
		$('#form-edit-cliente select').prop('disabled', false);
		try {
			$('.selectpicker').selectpicker('refresh');
		} catch (e) { /* ignore */ }
	}

	function cliFichaInitSelectpickers() {
		if (typeof $.fn.selectpicker !== 'function') {
			return;
		}
		$('.selectpicker').each(function () {
			var $el = $(this);
			if ($el.data('selectpicker')) {
				return;
			}
			$el.selectpicker({
				container: 'body',
			});
		});
	}

	function cliFichaSyncFooterOffset() {
		var $layout = $('.cli-ficha-layout-unificado');
		var $footer = $('#cli-ficha-footer-bar');
		if (!$layout.length || !$footer.length) {
			return;
		}
		var footerHeight = Math.ceil($footer.outerHeight() || 0);
		if (footerHeight < 80) {
			footerHeight = 100;
		}
		$layout.css('--cli-footer-offset', footerHeight + 'px');
	}

	function tipo(tipoVal) {
		if (String(tipoVal) === '2') {
			$('#razaosocial, #nomefantasia, #cnpj').prop('disabled', false);
			$('#nome, #cpffisica').prop('disabled', true);
			$('.pessoaFisica').hide();
			$('.pessoaJuridica').fadeIn();
		} else {
			$('#nome, #cpffisica').prop('disabled', false);
			$('#razaosocial, #nomefantasia, #cnpj').prop('disabled', true);
			$('.pessoaJuridica').hide();
			$('.pessoaFisica').fadeIn();
		}
	}
	window.tipo = tipo;

	function cliFichaSetViewMode() {
		var C = conf();
		window.cliFichaEditing = false;
		$('#form-edit-cliente').addClass('cli-ficha--readonly');
		$('#cli-ficha-mode-label').text('Modo leitura');
		$('#btn-cli-ficha-edit').removeClass('d-none');
		$('#btn-cli-ficha-cancel').addClass('d-none');
		$('#btn-cli-ficha-save').addClass('d-none');
		$('#form-edit-cliente select').prop('disabled', true);
		try {
			$('.selectpicker').selectpicker('refresh');
		} catch (e) { /* ignore */ }
		$('.btn-gerenciar-emails-faturamento, .btn-gerenciar-emails, #btn-buscar-ie-edit').prop('disabled', true).addClass('disabled');
		if (C.isEquipe) {
			$('#btn-buscar-ie-edit').addClass('d-none');
		}
		tipo($('#tipo').val());
		$('#form-edit-cliente').find('input.form-control, textarea.form-control').each(function () {
			var $el = $(this);
			if ($el.is('[type=hidden]')) {
				return;
			}
			if ($el.prop('disabled')) {
				return;
			}
			$el.prop('readonly', true);
		});
		cliFichaSyncFooterOffset();
	}
	window.cliFichaSetViewMode = cliFichaSetViewMode;

	function cliFichaSetEditMode() {
		var C = conf();
		window.cliFichaEditing = true;
		$('#form-edit-cliente').removeClass('cli-ficha--readonly');
		$('#cli-ficha-mode-label').text('Modo edição');
		$('#btn-cli-ficha-edit').addClass('d-none');
		$('#btn-cli-ficha-cancel').removeClass('d-none');
		$('#btn-cli-ficha-save').removeClass('d-none');
		$('#form-edit-cliente select').prop('disabled', false);
		try {
			$('.selectpicker').selectpicker('refresh');
		} catch (e) { /* ignore */ }
		$('.btn-gerenciar-emails-faturamento, .btn-gerenciar-emails').prop('disabled', false).removeClass('disabled');
		if (C.isEquipe) {
			$('#btn-buscar-ie-edit').prop('disabled', false).removeClass('disabled').removeClass('d-none');
		}
		tipo($('#tipo').val());
		$('#form-edit-cliente').find('input.form-control, textarea.form-control').each(function () {
			var $el = $(this);
			if ($el.is('[type=hidden]')) {
				return;
			}
			if ($el.prop('disabled')) {
				return;
			}
			$el.prop('readonly', false);
		});
		cliFichaSyncFooterOffset();
	}
	window.cliFichaSetEditMode = cliFichaSetEditMode;

	$(function () {
		var C = conf();
		if (!C.clienteId) {
			return;
		}
		var urls = C.urls || {};
		var U = window.PgmClienteEditUtils || {};

		$('.datepicker').bootstrapMaterialDatePicker({
			format: 'DD/MM/YYYY',
			lang: 'pt-br',
			time: false,
			switchOnClick: true,
			nowButton: true,
			cancelText: 'Cancelar',
			setDate: 'currentDate',
			nowText: 'Hoje',
		});

		$('#btn-cli-ficha-edit').on('click', function () {
			cliFichaSetEditMode();
		});
		$('#btn-cli-ficha-cancel').on('click', function () {
			if (confirm('Descartar alterações não salvas nesta página?')) {
				document.getElementById('form-edit-cliente').reset();
				try {
					$('.selectpicker').selectpicker('refresh');
				} catch (e) { /* ignore */ }
				var emailsFaturamentoRaw = $('#email').val() || '';
				if (U.atualizaDisplayEmailsFaturamento) {
					U.atualizaDisplayEmailsFaturamento(emailsFaturamentoRaw);
				}
				var emailsRaw = $('#emailresponsavel').val() || '';
				if (U.atualizaDisplayEmails) {
					U.atualizaDisplayEmails(emailsRaw);
				}
				tipo($('#tipo').val());
				if ($('#cli-ff-switch-inativo').length && $('#inativo').length) {
					$('#cli-ff-switch-inativo').prop('checked', $('#inativo').is(':checked'));
				}
				cliFichaSetViewMode();
			}
		});
		$('#btn-cli-ficha-save').on('click', function () {
			cliFichaPrepareSubmit();
			$('#cli-ficha-submit-fallback').trigger('click');
		});
		$('#form-edit-cliente').on('submit', function () {
			cliFichaPrepareSubmit();
			$('#cli-ficha-loading').removeClass('d-none').attr('aria-hidden', 'false');
		});
		window.addEventListener('pageshow', function () {
			$('#cli-ficha-loading').addClass('d-none').attr('aria-hidden', 'true');
		});

		if (C.isEquipe) {
			$('#cli-ff-switch-inativo').on('change', function () {
				if ($('#inativo').length) {
					$('#inativo').prop('checked', $(this).is(':checked'));
				}
				if (!window.cliFichaEditing) {
					cliFichaSetEditMode();
				}
			});
			if ($('#cli-ff-switch-inativo').length && $('#inativo').length) {
				$('#cli-ff-switch-inativo').prop('checked', $('#inativo').is(':checked'));
			}
		}

		$('#cnpj').mask('99.999.999/9999-99');
		$('#fone').mask('(999) 9999-9999');
		$('#fone2').mask('(999) 99999-9999');
		$('#cep').mask('99999-999');
		$('#cpffisica').mask('999.999.999-99');
		$('#cpfresponsavel').mask('999.999.999-99');
		$('#confirmaCpfResponsavel').mask('999.999.999-99');
		$('.telefone').mask('(99) 9999-9999');
		$('.celular').mask('(99) 99999-9999');

		$('#senhaadministrativa').prop('disabled', false);
		$('#exibirsenha').prop('disabled', false);

		cliFichaInitSelectpickers();
		cliFichaSetViewMode();
		cliFichaSyncFooterOffset();
		$(window).on('resize orientationchange', cliFichaSyncFooterOffset);

		var table = $('#tableContatos, #tableContratos');
		table.on('length.dt', function (e, settings, len) {
			if (typeof pagelength === 'function') {
				pagelength(len);
			}
		});
		table.DataTable({
			pageLength: 20,
			lengthChange: false,
			language: {
				sProcessing: 'Procesando...',
				sLengthMenu: 'Mostrar _MENU_ registros',
				sZeroRecords: 'Nenhum registro encontrado',
				sEmptyTable: 'Nenhum dado disponível',
				sInfo: 'Mostrando registros de _START_ até _END_ de um total de _TOTAL_ registros',
				sInfoEmpty: 'Mostrando registros de 0 a 0 de um total de 0 registros',
				sInfoFiltered: '(filtrado de um total de _MAX_ registros)',
				sInfoPostFix: '',
				sSearch: 'Buscar:',
				sUrl: '',
				sInfoThousands: ',',
				sLoadingRecords: 'Carregando...',
				oPaginate: {
					sFirst: '<<',
					sLast: '>>',
					sNext: '>',
					sPrevious: '<',
				},
				oAria: {
					sSortAscending: ': Ordem Ascendente',
					sSortDescending: ': Ordem descendente',
				},
			},
			drawCallback: function () {
				$('td').removeClass('dark-mode');
			},
		});
		if (typeof filters !== 'undefined') {
			table.search(filters).draw();
		}

		var emailsFaturamentoRaw0 = $('#email').val() || '';
		if (U.atualizaDisplayEmailsFaturamento) {
			U.atualizaDisplayEmailsFaturamento(emailsFaturamentoRaw0);
		}

		$('#modal-emails-faturamento').on('show.bs.modal', function () {
			$('#email_faturamento_editor').val(U.formataEmailsParaEdicao ? U.formataEmailsParaEdicao($('#email').val() || '') : '');
		});

		$('.btn-salvar-emails-faturamento').click(function (e) {
			e.preventDefault();
			var texto = $('#email_faturamento_editor').val() || '';
			var normalizado = U.normalizaEmails ? U.normalizaEmails(texto) : texto;
			$('#email').val(normalizado);
			if (U.atualizaDisplayEmailsFaturamento) {
				U.atualizaDisplayEmailsFaturamento(normalizado);
			}
			$('#modal-emails-faturamento').modal('hide');
			if (typeof window.cliUiToast === 'function') {
				window.cliUiToast('E-mails de faturamento atualizados na tela. Use Salvar cliente para gravar no servidor.', 'success');
			}
		});

		var emailsRaw0 = $('#emailresponsavel').val() || '';
		if (U.atualizaDisplayEmails) {
			U.atualizaDisplayEmails(emailsRaw0);
		}

		$('#modal-emails-contato').on('show.bs.modal', function () {
			$('#emailresponsavel_editor').val(U.formataEmailsParaEdicao ? U.formataEmailsParaEdicao($('#emailresponsavel').val() || '') : '');
		});

		$('.btn-salvar-emails-contato').click(function (e) {
			e.preventDefault();
			var texto2 = $('#emailresponsavel_editor').val() || '';
			var normalizado2 = U.normalizaEmails ? U.normalizaEmails(texto2) : texto2;
			$('#emailresponsavel').val(normalizado2);
			if (U.atualizaDisplayEmails) {
				U.atualizaDisplayEmails(normalizado2);
			}
			$('#modal-emails-contato').modal('hide');
			if (typeof window.cliUiToast === 'function') {
				window.cliUiToast('E-mails de contato atualizados na tela. Use Salvar cliente para gravar no servidor.', 'success');
			}
		});

		$('.inativo').hide();
		window.ativo = 'nao';
		$('.btn-inativo').click(function (e) {
			e.preventDefault();
			if ($('.vesetainativo').hasClass('inativo')) {
				if (window.ativo === 'nao') {
					$('.inativo').show();
					window.ativo = 'sim';
				} else {
					$('.inativo').hide();
					window.ativo = 'nao';
				}
			}
		});

		$('.inativoAcessos').hide();
		window.inativoAcessos = 'nao';
		$('.btn-inativoAcessos').click(function (e) {
			e.preventDefault();
			if ($('.vesetainativoAcessos').hasClass('inativoAcessos')) {
				if (window.inativoAcessos === 'nao') {
					$('.inativoAcessos').show();
					window.inativoAcessos = 'sim';
				} else {
					$('.inativoAcessos').hide();
					window.inativoAcessos = 'nao';
				}
			}
		});

		var cidadesBase = urls.cidadesestado || '';
		$('#inscricaoestadual').change(function () {
			$.ajax({
				url: cidadesBase + '/' + $('#idcidade').val(),
				success: function (data) {
					if (typeof checkInscEstadual === 'function') {
						checkInscEstadual($('#inscricaoestadual').val(), data);
					}
				},
				error: function () {
					alert('Inscrição estadual inválida');
				},
			});
		});

		$('#btn-buscar-ie-edit').on('click', function (e) {
			e.preventDefault();
			var cnpj = ($('#cnpj').val() || '').replace(/\D/g, '');
			if (cnpj.length !== 14) {
				alert('Informe um CNPJ válido (14 dígitos).');
				return;
			}
			var uf = ($('#uf_contribuinte_edit').val() || '').trim().toUpperCase();
			var self = $(this);
			var consultaBase = urls.consultaIe || '';

			function doConsultaIe(ufValue) {
				if (!ufValue) {
					alert('Não foi possível obter a UF. Selecione a cidade do cliente.');
					return;
				}
				var urlIe = consultaBase + '/' + encodeURIComponent(cnpj) + '/' + encodeURIComponent(ufValue);
				self.prop('disabled', true).text('Buscando...');
				$.getJSON(urlIe, function (data) {
					self.prop('disabled', false).text('Buscar IE');
					if (data && data.success && data.ie) {
						$('#inscricaoestadual').val(data.ie);
					} else {
						alert(data && data.message ? data.message : 'IE não encontrada ou serviço indisponível.');
					}
				}).fail(function () {
					self.prop('disabled', false).text('Buscar IE');
					alert('Erro ao acessar o serviço de consulta de IE. Verifique se a chave da API está configurada.');
				});
			}

			if (uf) {
				doConsultaIe(uf);
			} else {
				var idcidade = $('#idcidade').val();
				if (!idcidade) {
					alert('Selecione a cidade do cliente para obter a UF.');
					return;
				}
				$.get(cidadesBase + '/' + idcidade, function (sigla) {
					$('#uf_contribuinte_edit').val(sigla);
					doConsultaIe((sigla || '').trim().toUpperCase());
				}).fail(function () {
					alert('Não foi possível obter a UF da cidade.');
				});
			}
		});

		$('#tipo').change(function () {
			tipo($(this).val());
			if (!window.cliFichaEditing) {
				cliFichaSetViewMode();
			} else {
				cliFichaSetEditMode();
			}
		});

		$('#razaosocial').change(function () {
			var v = $(this).val().toUpperCase();
			$(this).val(v);
		});
		$('#nome').change(function () {
			var v2 = $(this).val().toUpperCase();
			$(this).val(v2);
		});
	});
})(jQuery);
