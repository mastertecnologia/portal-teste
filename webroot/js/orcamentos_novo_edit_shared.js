/**
 * Fluxo compartilhado: orçamento novo (add) e edição (edit).
 * Depende de window.orcOrcamentoFormConfig (definido em Element/orcamentos_form_shared_js.ctp).
 */
(function ($) {
	'use strict';

	var cfg = window.orcOrcamentoFormConfig;
	if (!cfg || !cfg.mode) {
		return;
	}

	function numberToReal(n) {
		if (isNaN(n)) {
			return '0,00';
		}
		var x = Number(n).toFixed(2).split('.');
		x[0] = x[0].split(/(?=(?:...)*$)/).join('.');
		return x.join(',');
	}

	function orcEscapeHtmlAttr(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#39;')
			.replace(/</g, '&lt;');
	}

	function parseBrFloat(txt) {
		if (!txt) {
			return 0;
		}
		return parseFloat(String(txt).split('R$').join('').replace(/\./g, '').replace(',', '.').trim()) || 0;
	}

	function orcInsertDescPreviewSync() {
		var el = document.getElementById('orc-insert-desc-preview');
		if (!el) {
			return;
		}
		var t = ($('#observacao').val() || '').toString().trim();
		el.textContent = t ? t : '—';
	}

	function orcTipBadgeFromCatalogCodigo(codigo) {
		var found = null;
		(window.orcProdutosCatalogo || []).some(function (p) {
			if (String(p.codigo) === String(codigo) || String(p.id) === String(codigo)) {
				found = p;
				return true;
			}
			return false;
		});
		if (found && found.badge) {
			return String(found.badge);
		}
		return null;
	}

	function orcTipBadgeFromData(data, codigoFallback) {
		if (data && data.badge) {
			return String(data.badge);
		}
		var fromCat = codigoFallback ? orcTipBadgeFromCatalogCodigo(codigoFallback) : null;
		if (fromCat) {
			return fromCat;
		}
		if (data && data.tipoLabel) {
			var tl = String(data.tipoLabel).toLowerCase();
			if (tl.indexOf('licen') >= 0) {
				return 'lic';
			}
			if (tl.indexOf('loca') >= 0) {
				return 'loc';
			}
			if (tl.indexOf('prod') >= 0) {
				return 'prod';
			}
			if (tl.indexOf('serv') >= 0) {
				return 'serv';
			}
		}
		var t = data && data.tipo !== undefined && data.tipo !== null ? parseInt(data.tipo, 10) : NaN;
		if (!isNaN(t)) {
			if (typeof cfg.tipoProdutoLegacy !== 'undefined' && t === cfg.tipoProdutoLegacy) {
				return 'prod';
			}
			if (typeof cfg.tipoServicoLegacy !== 'undefined' && t === cfg.tipoServicoLegacy) {
				return 'serv';
			}
			if (typeof cfg.tipoLicencaLegacy !== 'undefined' && t === cfg.tipoLicencaLegacy) {
				return 'lic';
			}
			if (typeof cfg.tipoLocacaoLegacy !== 'undefined' && t === cfg.tipoLocacaoLegacy) {
				return 'loc';
			}
			if (t === cfg.tipoProduto) {
				return 'prod';
			}
			if (t === cfg.tipoServico) {
				return 'serv';
			}
			if (typeof cfg.tipoLicenca !== 'undefined' && t === cfg.tipoLicenca) {
				return 'lic';
			}
			if (typeof cfg.tipoLocacao !== 'undefined' && t === cfg.tipoLocacao) {
				return 'loc';
			}
		}
		return 'serv';
	}

	function orcTipDisplayFromProduto(data, codigoFallback) {
		var $tip = $('#orc-f-tip');
		if (!$tip.length) {
			return;
		}
		var badge = orcTipBadgeFromData(data, codigoFallback || (data && data.codigo));
		if (badge === 'srv') {
			badge = 'serv';
		}
		if (['prod', 'serv', 'lic', 'loc'].indexOf(badge) < 0) {
			badge = 'serv';
		}
		$tip.val(badge);
	}

	function orcCustoUnitFromCatalog(codigo) {
		var cu = $('#orc-custo-unit');
		if (!cu.length || !window.orcProdutosCatalogo) {
			return;
		}
		var found = null;
		(window.orcProdutosCatalogo || []).some(function (p) {
			if (String(p.codigo) === String(codigo) || String(p.id) === String(codigo)) {
				found = p;
				return true;
			}
			return false;
		});
		if (found && found.custoUnit != null && parseFloat(found.custoUnit) > 0) {
			cu.val(numberToReal(parseFloat(found.custoUnit)));
		} else {
			cu.val('');
		}
	}

	function orcObsSolicitacaoPreviewSync() {
		var $ta = $('#observacoes');
		var $iframe = $('#orc-obs-solicitacao-preview');
		if (!$ta.length || !$iframe.length) {
			return;
		}
		var raw = String($ta.val() == null ? '' : $ta.val());
		raw = raw.replace(/<\/script/gi, '<\\/script').replace(/<\/iframe/gi, '<\\/iframe');
		var doc = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;font-size:13px;line-height:1.45;color:#1a1a18;margin:12px;}p{margin:.5em 0}ul,ol{padding-left:1.25em}</style></head><body>' + raw + '</body></html>';
		$iframe.attr('srcdoc', doc);
	}

	function orcClienteMetaFill() {
		var id = $('#idcliente').val();
		var m = window.orcClientesMeta && window.orcClientesMeta[id];
		if (!m) {
			$('#orc-cli-doc, #orc-cli-email, #orc-cli-contato').val('');
			$('#orc-cli-doc-lbl').text('CNPJ / CPF');
			return;
		}
		var jur = cfg.juridicaTipo;
		if (parseInt(m.tipo, 10) === jur) {
			$('#orc-cli-doc-lbl').text('CNPJ');
			$('#orc-cli-doc').val(m.cnpj || '');
		} else {
			$('#orc-cli-doc-lbl').text('CPF');
			$('#orc-cli-doc').val(m.cpf || '');
		}
		$('#orc-cli-email').val(m.email || '');
		var cont = (m.nome || '').trim();
		if (!cont && (m.razaosocial || '').trim()) {
			cont = (m.razaosocial || '').trim();
		}
		$('#orc-cli-contato').val(cont);
	}

	function orcApplyDiscountRow(subVenda, subCusto) {
		if (!$('#disc-val').length) {
			return;
		}
		var dv = parseFloat($('#disc-val').val());
		if (isNaN(dv)) {
			dv = 0;
		}
		var tipo = $('#disc-tipo').val();
		var discAbs = tipo === 'pct' ? subVenda * (dv / 100) : dv;
		if (discAbs < 0) {
			discAbs = 0;
		}
		if (discAbs > subVenda) {
			discAbs = subVenda;
		}
		var afterDisc = Math.max(0, subVenda - discAbs);
		var lucro = afterDisc - subCusto;
		var margem = afterDisc > 0.01 ? Math.round((lucro / afterDisc) * 100) : 0;
		$('#disc-show').text('R$ ' + numberToReal(discAbs));
		$('#t-sub').text('R$ ' + numberToReal(subVenda));
		$('#t-cus').text('R$ ' + numberToReal(subCusto));
		$('#t-disc').text('— R$ ' + numberToReal(discAbs));
		var $tm = $('#t-marg');
		if ($tm.length) {
			$tm.text(margem + '%');
			$tm.toggleClass('orc-tot-val--teal', margem >= 15);
			$tm.toggleClass('orc-marg-pct--warn', margem < 15);
		}
		$('#t-tot').text('R$ ' + numberToReal(afterDisc));
	}

	window.orcNovoAfterCarrinhoTotals = function () {
		if (!$('#ms-subtotal').length) {
			return;
		}
		var vu = parseBrFloat($('.valortotal').first().text());
		var vm = parseBrFloat($('.valormensaltotal').first().text());
		var subVenda = vu + vm;
		var subCusto = 0;
		$('#tableCarrinho tbody tr').each(function () {
			var $td = $(this).find('.orc-line-custo');
			if ($td.length) {
				var c = parseFloat($td.data('custo'));
				if (!isNaN(c)) {
					subCusto += c;
				}
			}
		});
		var lucro = subVenda - subCusto;
		var margem = subVenda > 0.01 ? Math.round((lucro / subVenda) * 100) : 0;
		var pctCusto = subVenda > 0.01 ? Math.min(100, Math.round((subCusto / subVenda) * 100)) : 0;
		var pctLucro = subVenda > 0.01 ? Math.min(100, Math.max(0, margem)) : 0;
		$('#ms-subtotal').text('R$ ' + numberToReal(subVenda));
		$('#ms-custo').text('R$ ' + numberToReal(subCusto));
		$('#ms-lucro').text('R$ ' + numberToReal(lucro));
		$('#ms-margem').text(margem + '%');
		var w = Math.min(100, Math.max(0, margem));
		var msBar = document.getElementById('ms-bar');
		if (msBar) {
			msBar.style.setProperty('--orc-margin-pct', w + '%');
		}
		var msBarCusto = document.getElementById('ms-bar-custo');
		if (msBarCusto) {
			msBarCusto.style.setProperty('--orc-margin-pct', pctCusto + '%');
		}
		var msBarLucro = document.getElementById('ms-bar-lucro');
		if (msBarLucro) {
			msBarLucro.style.setProperty('--orc-margin-pct', pctLucro + '%');
		}
		orcApplyDiscountRow(subVenda, subCusto);
	};

	window.carrinho = function carrinho() {
		$.ajax({
			url: cfg.carrinhoUrl,
			type: cfg.carrinhoMethod || 'GET',
			dataType: 'html',
			success: function (data) {
				$('#carrinho').html(data);
				$('#carrinho').fadeIn();
				orcBindLineDiscountInputs();
			},
			error: function (xhr) {
				var msg = 'Não foi possível carregar os itens.';
				if (xhr.responseJSON && xhr.responseJSON.mensagem) {
					msg = xhr.responseJSON.mensagem;
				} else if (xhr.responseText) {
					var t = xhr.responseText.trim();
					if (t.length && t.charAt(0) === '{') {
						try {
							var j = JSON.parse(t);
							if (j && j.mensagem) {
								msg = j.mensagem;
							}
						} catch (e) {
							/* ignore */
						}
					} else if (t.length < 200) {
						msg = t;
					}
				}
				alert(msg);
			}
		});
	};

	window.carrinho();

	$('#observacao').on('input change', function () {
		orcInsertDescPreviewSync();
	});
	$('#observacoes').on('input change', function () {
		orcObsSolicitacaoPreviewSync();
	});
	$(function () {
		orcInsertDescPreviewSync();
		orcObsSolicitacaoPreviewSync();
	});

	function orcOnIdprodutoChange() {
		var codigoSel = $('#idproduto').val();
		if (codigoSel != 0 && codigoSel !== '0') {
			$('#valoruni').attr('disabled', true);
			$('.mensal').attr('disabled', true);
			$.ajax({
				type: 'post',
				url: cfg.produtoUrlBase + '/' + codigoSel,
				dataType: 'json',
				success: function (data) {
					if (data.mensagem) {
						$('#servico').val('');
						$('#valoruni').val('');
						$('.qtdEstoque').text(data.mensagem).show();
						$('#valoruni').prop('disabled', false);
						$('.mensal').prop('disabled', false);
						orcInsertDescPreviewSync();
						return;
					}
					var descLinha = (data.descricao || '').toString().trim();
					$('#servico').val(descLinha);
					if (!($('#observacao').val() || '').toString().trim()) {
						$('#observacao').val(descLinha);
					}
					orcInsertDescPreviewSync();
					orcTipDisplayFromProduto(data, codigoSel);
					orcCustoUnitFromCatalog(codigoSel);
					$('#quantidade').val('');
					$('#valordoservico').val('');
					if (data.tipo == cfg.tipoServico) {
						$('#valormensal').prop('disabled', false);
						$('#valoruni').prop('disabled', false);
						$('#valormensal').val('');
						$('#valoruni').val(numberToReal(data.vlunitario));
						$('#tipo').val(1);
						$('#quantidade').mask('99:99');
						$('.qtdEstoque').hide();
					} else if (data.tipo == cfg.tipoProduto) {
						$('#valormensal').prop('disabled', 'disabled');
						$('#valoruni').prop('disabled', false);
						$('#valoruni').val(numberToReal(data.vlunitario));
						$('#valormensal').val('');
						$('#tipo').val(0);
						$('#quantidade').mask('0000000');
						$.ajax({
							type: 'post',
							url: cfg.qtdestoqueUrlBase + '/' + data.codigo,
							dataType: 'json',
							success: function (qtdestoque) {
								var msg = (qtdestoque === -999 || qtdestoque === null || (typeof qtdestoque === 'number' && qtdestoque < 0))
									? 'Estoque: indisponível (consulte o ERP)'
									: ('Qtd. em estoque: ' + qtdestoque);
								$('.qtdEstoque').text(msg).show();
							},
							error: function () {
								$('.qtdEstoque').text('Estoque: indisponível').show();
							}
						});
					} else {
						$('#valormensal').prop('disabled', false);
						$('#valoruni').prop('disabled', false);
						$('#valormensal').val(numberToReal(data.vlunitario));
						$('#valoruni').val(numberToReal(data.vlunitario));
						$('#tipo').val(0);
						$('#quantidade').mask('0000000');
						$('.qtdEstoque').hide();
					}
				},
				error: function (xhr) {
					var msg = 'Produto/serviço não encontrado.';
					if (xhr.responseJSON && xhr.responseJSON.mensagem) {
						msg = xhr.responseJSON.mensagem;
					}
					$('.qtdEstoque').text(msg).show();
					$('#valoruni').val('').prop('disabled', false);
					$('.mensal').prop('disabled', false);
					orcInsertDescPreviewSync();
				}
			});
		} else {
			$('#servico').val('');
			$('#valoruni').val('');
			$('#valoruni').attr('disabled', false);
			$('.mensal').attr('disabled', false);
			orcInsertDescPreviewSync();
			$('#orc-f-tip').val('prod');
			$('#orc-custo-unit').val('');
		}
	}

	$('#idproduto').on('change changed.bs.select', function () {
		orcOnIdprodutoChange();
	});

	$('#tipo').change(function () {
		if ($(this).val() == 1) {
			$('#quantidade').mask('99:99');
		} else {
			$('#quantidade').mask('0000000');
		}
	});

	$('#valoruni').keydown(function () {
		var valor = $(this).val().replaceAll('.', '').replaceAll(',', '.');
		if (valor > 0) {
			$('#valormensal').val('');
		}
	});

	$('#valormensal').keydown(function () {
		var valor = $(this).val().replaceAll('.', '').replaceAll(',', '.');
		if (valor > 0) {
			$('#valoruni').val('');
		}
	});

	$('#btn-addservico').click(function (e) {
		e.preventDefault();
		var servico = $('#servico').val();
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valordoservico = $('#valordoservico').val();
		var observacao = $('#observacao').val();
		var valormensal = $('#valormensal').val();
		var idproduto = $('#idproduto').val();
		var tipo = $('#tipo').val();

		if (servico === '') {
			bootbox.alert('Preencha o campo "Descrição".');
			return false;
		}

		if (quantidade === '' || (valoruni === '' && valormensal === '')) {
			bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
			return false;
		}

		if (valoruni === '') {
			valoruni = 0;
		}
		if (valormensal === '') {
			valormensal = 0;
		}

		var postData = {
			servico: servico,
			quantidade: quantidade,
			valoruni: valoruni,
			valordoservico: valordoservico,
			observacao: observacao,
			valormensal: valormensal,
			idproduto: idproduto,
			tipo: tipo,
			desconto_valor: $('#orc-item-disc-val').val() || 0,
			desconto_tipo: $('#orc-item-disc-tipo').val() || 'pct'
		};
		if (cfg.mode === 'edit' && cfg.orcamentoId) {
			postData.id_orcamento = cfg.orcamentoId;
		}
		$.ajax({
			url: cfg.addservicoUrl,
			dataType: 'html',
			type: 'POST',
			data: postData,
			success: function (data) {
				if (data === 'nao pode') {
					bootbox.alert('O serviço já está no carrinho');
					return false;
				}
				window.carrinho();
				$('#servico').val('');
				$('#quantidade').val('');
				$('#valoruni').val('');
				$('#valordoservico').val('');
				$('#observacao').val('');
				$('#valormensal').val('');
				$('#idproduto').val(0);
				$('#tipo').val(0);
				$('#orc-item-disc-val').val(0);
				$('#orc-item-disc-tipo').val('pct');
				$('#idproduto').selectpicker('refresh');
				$('.qtdEstoque').text('').hide();
				$('#valormensal').attr('disabled', false);
				$('#valoruni').attr('disabled', false);
				orcInsertDescPreviewSync();
				$('#servico').focus();
			},
			error: function (xhr) {
				var msg = 'Erro ao adicionar item. Tente novamente.';
				if (xhr.responseJSON && xhr.responseJSON.mensagem) {
					msg = xhr.responseJSON.mensagem;
				} else if (xhr.responseText) {
					var t = xhr.responseText.trim();
					if (t.length && t.charAt(0) === '{') {
						try {
							var j = JSON.parse(t);
							if (j && j.mensagem) {
								msg = j.mensagem;
							}
						} catch (e) {
							/* ignore */
						}
					} else if (t.length < 200) {
						msg = t;
					}
				}
				if (typeof bootbox !== 'undefined') {
					bootbox.alert(msg);
				} else {
					alert(msg);
				}
			}
		});
	});

	jQuery.fn.preventDoubleSubmission = function () {
		$(this).on('submit', function (e) {
			var $form = $(this);
			if ($form.data('submitted') === true) {
				e.preventDefault();
			} else {
				$form.data('submitted', true);
			}
		});
		return this;
	};

	$('form').preventDoubleSubmission();

	var editando = false;

	function orcDiscValFromRaw(raw) {
		if (raw === undefined || raw === null || raw === '') {
			return '0';
		}
		if (typeof raw === 'number' && !isNaN(raw)) {
			return String(raw);
		}
		var s = String(raw).trim().replace(/\s/g, '');
		if (s.indexOf(',') >= 0) {
			s = s.replace(/\./g, '').replace(',', '.');
		}
		var n = parseFloat(s);
		return isNaN(n) ? '0' : String(n);
	}

	function preencherFormularioEdicao(dados) {
		$('#servico').val(dados.servico);
		$('#quantidade').val(dados.quantidade);
		$('#valoruni').val(numberToReal(dados.valoruni));
		$('#observacao').val(dados.observacao);
		$('#valormensal').val(numberToReal(dados.valormensal));
		$('#idproduto').val(dados.idproduto);
		$('#tipo').val(dados.tipo);
		$('#item_edit_id').val(dados.id);
		$('#orc-item-disc-val').val(orcDiscValFromRaw(dados.descontoValor));
		$('#orc-item-disc-tipo').val(dados.descontoTipo || 'pct');
		if (dados.tipo == 1) {
			$('#quantidade').mask('99:99');
		} else {
			$('#quantidade').mask('0000000');
		}
		calcularValorTotal();
		$('#idproduto').selectpicker('refresh');
		orcInsertDescPreviewSync();
	}

	function limparFormularioEdicao() {
		$('#servico').val('');
		$('#quantidade').val('');
		$('#valoruni').val('');
		$('#observacao').val('');
		$('#valormensal').val('');
		$('#idproduto').val(0);
		$('#tipo').val(0);
		$('#valordoservico').val('');
		$('#item_edit_id').val('');
		$('#orc-item-disc-val').val(0);
		$('#orc-item-disc-tipo').val('pct');
		$('#idproduto').selectpicker('refresh');
		$('#valoruni').prop('disabled', false);
		$('#valormensal').prop('disabled', false);
		$('.qtdEstoque').hide();
		$('#quantidade').mask('0000000');
		orcInsertDescPreviewSync();
	}

	function toggleModoEdicao(modo) {
		editando = modo;
		if (modo) {
			$('#btn-addservico').hide();
			$('#orc-item-edit-actions').removeClass('orc-is-hidden');
			if ($('#orc-novo-proposta-title').length) {
				$('#orc-novo-proposta-title').text(cfg.toggleTitleEdit);
			}
		} else {
			$('#btn-addservico').show();
			$('#orc-item-edit-actions').addClass('orc-is-hidden');
			if ($('#orc-novo-proposta-title').length) {
				$('#orc-novo-proposta-title').text(cfg.toggleTitleNew);
			}
			limparFormularioEdicao();
		}
	}

	function calcularValorTotal() {
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valormensal = $('#valormensal').val();

		if (quantidade.indexOf(':') > -1) {
			var quantidadeArray = quantidade.split(':');
			quantidade = (parseFloat(quantidadeArray[0]) + (parseFloat(quantidadeArray[1]) / 6 / 10)).toFixed(2);
		} else {
			quantidade = quantidade.replace(/\./g, '').replace(',', '.');
		}

		valoruni = valoruni.replace(/\./g, '').replace(',', '.');
		valormensal = valormensal.replace(/\./g, '').replace(',', '.');

		var valor = 0;
		if (valoruni !== '' && parseFloat(valoruni) > 0) {
			valor = parseFloat(valoruni);
		} else if (valormensal !== '' && parseFloat(valormensal) > 0) {
			valor = parseFloat(valormensal);
		}

		if (quantidade > 0 && valor > 0) {
			var valortotal = parseFloat(quantidade) * valor;
			$('#valordoservico').val(numberToReal(valortotal));
		} else {
			$('#valordoservico').val('');
		}
	}

	$(document).on('click', '.editaitemcarrinho', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $row = $btn.closest('tr');
		var discVal = $btn.attr('data-orc-disc-v');
		if (discVal === undefined || discVal === null || discVal === '') {
			discVal = $btn.attr('data-desconto-valor');
		}
		var discTipo = $btn.attr('data-orc-disc-t') || $btn.attr('data-desconto-tipo') || 'pct';
		if ($row.find('.orc-line-disc-val').length) {
			discVal = $row.find('.orc-line-disc-val').val();
			discTipo = $row.find('.orc-line-disc-tipo').val() || discTipo;
		}
		var dados = {
			id: $btn.data('id'),
			servico: $btn.data('servico'),
			quantidade: $btn.data('quantidade'),
			valoruni: $btn.data('valoruni'),
			observacao: $btn.data('observacao'),
			valormensal: $btn.data('valormensal'),
			idproduto: $btn.data('idproduto'),
			tipo: $btn.data('tipo'),
			descontoValor: discVal,
			descontoTipo: discTipo
		};
		preencherFormularioEdicao(dados);
		toggleModoEdicao(true);
		var $card = $('.card.orc-premium-card-inner').first();
		if ($card.length) {
			$('html, body').animate({ scrollTop: $card.offset().top }, 500);
		}
	});

	$('#btn-editarservico').click(function (e) {
		e.preventDefault();
		var id = $('#item_edit_id').val();
		var servico = $('#servico').val();
		var quantidade = $('#quantidade').val();
		var valoruni = $('#valoruni').val();
		var valordoservico = $('#valordoservico').val();
		var observacao = $('#observacao').val();
		var valormensal = $('#valormensal').val();
		var idproduto = $('#idproduto').val();
		var tipo = $('#tipo').val();

		if (servico === '') {
			bootbox.alert('Preencha o campo "Descrição".');
			return false;
		}

		if (quantidade === '' || (valoruni === '' && valormensal === '')) {
			bootbox.alert('Preencha o campo "Quantidade" e o campo de valor respectivo.');
			return false;
		}

		$.ajax({
			url: cfg.editaitemcarrinhoUrl,
			dataType: 'html',
			type: 'POST',
			data: {
				id: id,
				servico: servico,
				quantidade: quantidade,
				valoruni: valoruni,
				valordoservico: valordoservico,
				observacao: observacao,
				valormensal: valormensal,
				idproduto: idproduto,
				tipo: tipo,
				desconto_valor: $('#orc-item-disc-val').val() || 0,
				desconto_tipo: $('#orc-item-disc-tipo').val() || 'pct'
			},
			success: function (data) {
				var resp = typeof data === 'string' ? data.trim() : '';
				if (resp === 'success') {
					window.carrinho();
					toggleModoEdicao(false);
					bootbox.alert('Item atualizado com sucesso!');
				} else if (resp === 'error:migration') {
					bootbox.alert('Desconto por item indisponível: execute bin/cake migrations migrate e bin/cake cache clear_all no servidor.');
				} else {
					bootbox.alert('Erro ao atualizar item.');
				}
			},
			error: function (xhr) {
				var msg = 'Erro ao atualizar item. Tente novamente.';
				if (xhr.responseJSON && xhr.responseJSON.mensagem) {
					msg = xhr.responseJSON.mensagem;
				} else if (xhr.responseText) {
					var t = xhr.responseText.trim();
					if (t.length && t.charAt(0) === '{') {
						try {
							var j = JSON.parse(t);
							if (j && j.mensagem) {
								msg = j.mensagem;
							}
						} catch (e) {
							/* ignore */
						}
					} else if (t.length < 200) {
						msg = t;
					}
				}
				bootbox.alert(msg);
			}
		});
	});

	$('#btn-cancelaredicao').click(function (e) {
		e.preventDefault();
		toggleModoEdicao(false);
	});

	$('#quantidade, #valoruni, #valormensal').on('keyup change', function () {
		calcularValorTotal();
	});

	if ($('#idcliente').length) {
		$('#idcliente').on('changed.bs.select', function () {
			orcClienteMetaFill();
		});
		orcClienteMetaFill();
	}

	$('#disc-val, #disc-tipo').on('change input', function () {
		if (typeof window.orcNovoAfterCarrinhoTotals === 'function') {
			window.orcNovoAfterCarrinhoTotals();
		}
	});

	var orcLineDiscSaveTimer = null;
	function orcSalvarDescontoLinha($row) {
		if (!cfg.salvarDescontoItemUrl || cfg.itemDescontoEnabled === false) {
			return;
		}
		var id = $row.find('.orc-line-disc-val').data('id') || $row.attr('data-item-id');
		if (!id) {
			return;
		}
		$.ajax({
			type: 'POST',
			url: cfg.salvarDescontoItemUrl,
			dataType: 'json',
			data: {
				id: id,
				desconto_valor: $row.find('.orc-line-disc-val').val() || 0,
				desconto_tipo: $row.find('.orc-line-disc-tipo').val() || 'pct'
			},
			success: function (res) {
				if (!res || !res.ok) {
					return;
				}
				if (res.vlLiquido != null) {
					$row.find('.valordoservico').text('R$ ' + numberToReal(parseFloat(res.vlLiquido)));
				}
				if (typeof window.valortotal === 'function') {
					window.valortotal();
				} else if (typeof window.orcNovoAfterCarrinhoTotals === 'function') {
					window.orcNovoAfterCarrinhoTotals();
				}
			}
		});
	}

	function orcBindLineDiscountInputs() {
		if (!cfg.salvarDescontoItemUrl || cfg.itemDescontoEnabled === false) {
			return;
		}
		$('#carrinho .orc-line-disc-val, #carrinho .orc-line-disc-tipo').off('change.orcDisc input.orcDisc').on('change.orcDisc input.orcDisc', function () {
			var $row = $(this).closest('tr');
			clearTimeout(orcLineDiscSaveTimer);
			orcLineDiscSaveTimer = setTimeout(function () {
				orcSalvarDescontoLinha($row);
			}, 400);
		});
	}
	orcBindLineDiscountInputs();

	$('#btn-orc-limpar-novo').on('click', function (e) {
		e.preventDefault();
		$('#idcliente').val('').selectpicker('refresh');
		var $fp = $('#formapagamento');
		$fp.val('À vista');
		if ($fp.data('selectpicker')) {
			$fp.selectpicker('refresh');
		}
		orcClienteMetaFill();
		$('#disc-val').val(0);
		$('#disc-tipo').val('pct');
		$('#observacoes').val('');
		orcObsSolicitacaoPreviewSync();
		$.ajax({
			type: 'POST',
			url: cfg.limpacarrinhoUrl,
			dataType: 'html',
			complete: function () {
				window.carrinho();
			}
		});
	});

	$(function () {
		if (!$('#ms-subtotal').length) {
			return;
		}
		$('#ms-subtotal').text('R$ ' + numberToReal(0));
		$('#ms-custo').text('R$ ' + numberToReal(0));
		$('#ms-lucro').text('R$ ' + numberToReal(0));
		$('#ms-margem').text('0%');
		['ms-bar', 'ms-bar-custo', 'ms-bar-lucro'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) {
				el.style.setProperty('--orc-margin-pct', '0%');
			}
		});
		orcApplyDiscountRow(0, 0);
	});

	// Catálogo
	var orcCatalogData = [];
	var orcCatalogRenderedItems = [];

	function orcCatalogEnsureLoaded() {
		var src = window.orcProdutosCatalogo || [];
		if (!src.length) {
			$('#orc-catalog-body').html('<div class="orc-catalog-empty">Nenhum produto ou serviço ativo cadastrado.</div>');
			return;
		}
		orcCatalogData = src.slice();
		orcCatalogFilter('');
	}

	function orcCatalogBadgeClass(badge) {
		var b = (badge || 'outro').toLowerCase();
		if (b === 'prod') {
			return 'orc-cat-badge orc-cat-badge--prod';
		}
		if (b === 'srv') {
			return 'orc-cat-badge orc-cat-badge--srv';
		}
		if (b === 'lic') {
			return 'orc-cat-badge orc-cat-badge--lic';
		}
		if (b === 'loc') {
			return 'orc-cat-badge orc-cat-badge--loc';
		}
		return 'orc-cat-badge orc-cat-badge--outro';
	}

	function orcCatalogUnidadeHint(p) {
		if ((p.badge || '') === 'srv') {
			return 'usr/mês';
		}
		return (p.unidade || 'un').toString();
	}

	function orcCatalogBadgeConsultaEstoque(badge) {
		var b = (badge || '').toLowerCase();
		return b === 'prod' || b === 'lic' || b === 'loc';
	}

	function orcCatalogClearStockLoadingAll(msg) {
		var t = msg || 'Estoque indisponível';
		$('#orc-catalog-body .orc-catalog-stock-line.orc-catalog-stock-line--loading').each(function () {
			$(this).removeClass('orc-catalog-stock-line--loading').addClass('orc-catalog-stock-line--err');
			$(this).text(t);
			$(this).closest('.orc-catalog-item').find('.btn-orc-catalog-add').prop('disabled', true).attr('aria-disabled', 'true');
		});
	}

	function orcCatalogFetchEstoques(items) {
		var url = window.orcEstoquesLoteUrl;
		if (!items || !items.length) {
			return;
		}
		if (!url) {
			orcCatalogClearStockLoadingAll('Estoque indisponível');
			return;
		}
		var cods = [];
		items.forEach(function (p) {
			if (!orcCatalogBadgeConsultaEstoque(p.badge)) {
				return;
			}
			var c = (p.codigo != null && p.codigo !== '') ? String(p.codigo).trim() : '';
			if (c && cods.indexOf(c) === -1) {
				cods.push(c);
			}
		});
		if (!cods.length) {
			return;
		}
		if (cods.length > 150) {
			cods = cods.slice(0, 150);
		}
		$.ajax({
			type: 'POST',
			url: url,
			data: { codigos: cods.join(',') },
			dataType: 'json',
			success: function (map) {
				if (!map || typeof map !== 'object' || map.erro) {
					orcCatalogClearStockLoadingAll();
					return;
				}
				$('#orc-catalog-body .orc-catalog-item').each(function () {
					var cod = ($(this).attr('data-codigo') || '').trim();
					if (!cod) {
						return;
					}
					var $row = $(this);
					var $el = $row.find('.orc-catalog-stock-line');
					if (!$el.length) {
						return;
					}
					$row.removeClass('orc-catalog-item--sem-estoque');
					$el.removeClass('orc-catalog-stock-line--zero orc-catalog-stock-line--ok orc-catalog-stock-line--err');
					if (map[cod] === undefined || map[cod] === null) {
						$el.removeClass('orc-catalog-stock-line--loading').addClass('orc-catalog-stock-line--err');
						$el.text('Estoque indisponível');
						$row.find('.btn-orc-catalog-add').prop('disabled', true).attr('aria-disabled', 'true');
						return;
					}
					var q = map[cod];
					$el.removeClass('orc-catalog-stock-line--loading');
					if (q === -999 || (typeof q === 'number' && q < 0)) {
						$el.addClass('orc-catalog-stock-line--err');
						$el.text('Estoque indisponível');
						$row.find('.btn-orc-catalog-add').prop('disabled', true).attr('aria-disabled', 'true');
					} else if (q === 0) {
						$row.addClass('orc-catalog-item--sem-estoque');
						$el.addClass('orc-catalog-stock-line--zero').text('Estoque zerado');
						$row.find('.btn-orc-catalog-add').prop('disabled', true).attr('aria-disabled', 'true');
					} else {
						$el.addClass('orc-catalog-stock-line--ok').text('Em estoque (' + q + ')');
						$row.find('.btn-orc-catalog-add').prop('disabled', false).attr('aria-disabled', 'false');
					}
				});
			},
			error: function () {
				orcCatalogClearStockLoadingAll();
			}
		});
	}

	function orcRenderCatalog(items) {
		var $body = $('#orc-catalog-body');
		orcCatalogRenderedItems = items;
		if (!items.length) {
			$body.html('<div class="orc-catalog-empty">Nenhum resultado para a busca.</div>');
			return;
		}
		var html = '';
		items.forEach(function (p, idx) {
			var nome = $('<div>').text(p.descricao || p.nome || '').html();
			var cod = $('<div>').text(p.codigo || '').html();
			var codRaw = (p.codigo != null && p.codigo !== '') ? String(p.codigo) : '';
			var tipoLb = $('<div>').text(p.tipoLabel || 'Item').html();
			var spec = 'Cód. ' + cod + ' · ' + $('<div>').text(orcCatalogUnidadeHint(p)).html();
			var preco = 'R$ ' + numberToReal(parseFloat(p.vlunitario) || 0);
			var badgeClass = orcCatalogBadgeClass(p.badge);
			var margemTxt = (p.margemPct !== null && p.margemPct !== undefined) ? ('Margem: ' + p.margemPct + '%') : 'Margem: —';
			var margemHtml = '<span class="orc-catalog-margem-line">' + $('<div>').text(margemTxt).html() + '</span>';
			var stockHtml = '';
			if (orcCatalogBadgeConsultaEstoque(p.badge)) {
				stockHtml = '<span class="orc-catalog-stock-line orc-catalog-stock-line--loading">Carregando estoque…</span><span class="orc-catalog-meta-sep">·</span>';
			}
			var metaRow = '<div class="orc-catalog-item-meta orc-catalog-stock-margem">' + stockHtml + margemHtml + '</div>';
			var custoNum = parseFloat(p.custoUnit);
			var custoBlock = (!isNaN(custoNum) && custoNum > 0)
				? ('<div class="orc-catalog-item-cost">Custo: R$ ' + numberToReal(custoNum) + '</div>')
				: '';
			html += '<div class="orc-catalog-item" data-idx="' + idx + '" data-codigo="' + orcEscapeHtmlAttr(codRaw.trim()) + '" role="button" tabindex="0">' +
				'<div class="orc-catalog-item-main">' +
					'<div class="orc-catalog-item-title-row">' +
						'<span class="orc-catalog-item-name">' + nome + '</span>' +
						'<span class="' + badgeClass + '">' + tipoLb + '</span>' +
					'</div>' +
					'<div class="orc-catalog-item-spec">' + spec + '</div>' +
					metaRow +
				'</div>' +
				'<div class="orc-catalog-item-prices">' +
					'<div class="orc-catalog-item-price">' + preco + '</div>' +
					custoBlock +
					'<div class="orc-catalog-item-unit">' + $('<div>').text(orcCatalogUnidadeHint(p)).html() + '</div>' +
				'</div>' +
				'<div class="orc-catalog-item-actions">' +
					'<button type="button" class="btn-orc-catalog-add" data-idx="' + idx + '" title="Adicionar ao orçamento">' +
						'<i class="fa fa-plus" aria-hidden="true"></i> Adicionar' +
					'</button>' +
				'</div>' +
			'</div>';
		});
		$body.html(html);
		orcCatalogFetchEstoques(items);
	}

	function orcCatalogFilter(q) {
		q = (q || '').toLowerCase().trim();
		var filtered = !q ? orcCatalogData.slice() : orcCatalogData.filter(function (p) {
			var d = ((p.descricao || p.nome || '') + ' ' + (p.codigo || '') + ' ' + (p.tipoLabel || '')).toLowerCase();
			return d.indexOf(q) > -1;
		});
		orcRenderCatalog(filtered);
	}

	window.orcCatalogOpen = function () {
		$('#orc-catalog-search-input').val('');
		$('#orc-catalog-overlay').addClass('open');
		orcCatalogEnsureLoaded();
	};

	window.orcCatalogFilter = orcCatalogFilter;

	function orcCatalogApplySelection(idxStr) {
		if (idxStr === undefined || idxStr === '') {
			return;
		}
		var idx = parseInt(idxStr, 10);
		if (isNaN(idx)) {
			return;
		}
		var p = orcCatalogRenderedItems[idx];
		if (p == null) {
			return;
		}
		var $row = $('#orc-catalog-body .orc-catalog-item').filter(function () {
			return parseInt($(this).attr('data-idx'), 10) === idx;
		});
		if (orcCatalogBadgeConsultaEstoque(p.badge)) {
			if ($row.find('.orc-catalog-stock-line--loading').length) {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Aguarde a consulta de estoque antes de adicionar o item.');
				} else {
					alert('Aguarde a consulta de estoque antes de adicionar o item.');
				}
				return;
			}
			if ($row.hasClass('orc-catalog-item--sem-estoque')) {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Este item está com estoque zerado no ERP e não pode ser incluído no orçamento.');
				} else {
					alert('Este item está com estoque zerado no ERP e não pode ser incluído no orçamento.');
				}
				return;
			}
			if ($row.find('.orc-catalog-stock-line--err').length) {
				if (typeof bootbox !== 'undefined') {
					bootbox.alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
				} else {
					alert('Não foi possível confirmar o estoque deste item. A inclusão não é permitida.');
				}
				return;
			}
		}
		var rawId = p.id != null ? String(p.id) : '';
		var $sel = $('#idproduto');
		if ($sel.length === 0) {
			return;
		}
		if ($sel.data('selectpicker')) {
			$sel.selectpicker('val', rawId);
		} else {
			$sel.val(rawId);
		}
		$sel.trigger('change');
		setTimeout(function () {
			if ($('#servico').val() === '' && (p.descricao || p.nome)) {
				$('#servico').val(p.descricao || p.nome);
			}
			if (!$('#valoruni').val() && parseFloat(p.vlunitario) > 0) {
				$('#valoruni').val(numberToReal(parseFloat(p.vlunitario)));
			}
			orcTipDisplayFromProduto(p, rawId);
			if (p.custoUnit != null && parseFloat(p.custoUnit) > 0) {
				$('#orc-custo-unit').val(numberToReal(parseFloat(p.custoUnit)));
			}
		}, 450);
		$('#orc-catalog-overlay').removeClass('open');
	}

	// Delegar no .orc-catalog-modal: o modal tem onclick stopPropagation, então o clique
	// não sobe até #orc-catalog-overlay — handlers no overlay nunca disparavam.
	function orcCatalogBindModalClicks() {
		var $modal = $('.orc-catalog-modal');
		if (!$modal.length) {
			return;
		}
		$modal.off('click.orcCat').on('click.orcCat', '.btn-orc-catalog-add', function (e) {
			e.preventDefault();
			e.stopPropagation();
			orcCatalogApplySelection($(this).attr('data-idx'));
		});
		$modal.on('click.orcCat', '.orc-catalog-item', function (e) {
			e.preventDefault();
			if ($(e.target).closest('.btn-orc-catalog-add').length) {
				return;
			}
			orcCatalogApplySelection($(this).attr('data-idx'));
		});
	}
	orcCatalogBindModalClicks();

	$('#orc-catalog-search-input').on('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
		}
	});

	function orcSyncDescontoHidden() {
		if (!$('#disc-val').length) {
			return;
		}
		var $hv = $('#orc-desconto-valor-hidden');
		var $ht = $('#orc-desconto-tipo-hidden');
		if ($hv.length) {
			$hv.val($('#disc-val').val() || 0);
		}
		if ($ht.length) {
			$ht.val($('#disc-tipo').val() || 'pct');
		}
	}

	$('#form-orc-add, #form-orc-edit').on('submit', function () {
		orcSyncDescontoHidden();
	});

})(jQuery);
