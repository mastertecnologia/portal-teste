/**
 * Centro de Cálculo de Precificação — engine tributária (paridade pgm_erp_completo.html).
 */
(function (global) {
	'use strict';

	var SN_TABELAS = {
		I: [{ ate: 180000, nominal: 0.04, deduzir: 0 }, { ate: 360000, nominal: 0.073, deduzir: 5940 }, { ate: 720000, nominal: 0.095, deduzir: 13860 }, { ate: 1800000, nominal: 0.107, deduzir: 22500 }, { ate: 3600000, nominal: 0.143, deduzir: 87300 }, { ate: 4800000, nominal: 0.19, deduzir: 378000 }],
		II: [{ ate: 180000, nominal: 0.045, deduzir: 0 }, { ate: 360000, nominal: 0.078, deduzir: 5940 }, { ate: 720000, nominal: 0.1, deduzir: 13860 }, { ate: 1800000, nominal: 0.112, deduzir: 22500 }, { ate: 3600000, nominal: 0.147, deduzir: 85500 }, { ate: 4800000, nominal: 0.3, deduzir: 720000 }],
		III: [{ ate: 180000, nominal: 0.06, deduzir: 0 }, { ate: 360000, nominal: 0.112, deduzir: 9360 }, { ate: 720000, nominal: 0.135, deduzir: 17640 }, { ate: 1800000, nominal: 0.16, deduzir: 35640 }, { ate: 3600000, nominal: 0.21, deduzir: 125640 }, { ate: 4800000, nominal: 0.33, deduzir: 648000 }],
		IV: [{ ate: 180000, nominal: 0.045, deduzir: 0 }, { ate: 360000, nominal: 0.09, deduzir: 8100 }, { ate: 720000, nominal: 0.102, deduzir: 12420 }, { ate: 1800000, nominal: 0.14, deduzir: 39780 }, { ate: 3600000, nominal: 0.22, deduzir: 183780 }, { ate: 4800000, nominal: 0.33, deduzir: 828000 }],
		V: [{ ate: 180000, nominal: 0.155, deduzir: 0 }, { ate: 360000, nominal: 0.18, deduzir: 4500 }, { ate: 720000, nominal: 0.195, deduzir: 9900 }, { ate: 1800000, nominal: 0.205, deduzir: 17100 }, { ate: 3600000, nominal: 0.23, deduzir: 62100 }, { ate: 4800000, nominal: 0.305, deduzir: 540000 }]
	};

	var SN_REPARTICAO = {
		I: { IRPJ: 0.055, CSLL: 0.035, COFINS: 0.1282, PIS: 0.0278, CPP: 0.419, ICMS: 0.335 },
		II: { IRPJ: 0.055, CSLL: 0.035, COFINS: 0.115, PIS: 0.025, CPP: 0.375, ICMS: 0.32, IPI: 0.075 },
		III: { IRPJ: 0.04, CSLL: 0.035, COFINS: 0.1282, PIS: 0.0305, CPP: 0.4413, ISS: 0.325 },
		IV: { IRPJ: 0.06, CSLL: 0.15, COFINS: 0.20, PIS: 0.04, ISS: 0.55 },
		V: { IRPJ: 0.25, CSLL: 0.15, COFINS: 0.1428, PIS: 0.0305, CPP: 0.2885, ISS: 0.1382 }
	};

	function p2num(v) {
		if (typeof v === 'number') return v;
		if (!v) return 0;
		var s = String(v).replace(/[R$\s%]/g, '').replace(/\./g, '').replace(',', '.');
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function num2BRL(n) {
		return 'R$ ' + (n || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
	}

	function num2pct(n, dec) {
		return (n || 0).toFixed(dec === undefined ? 2 : dec).replace('.', ',') + '%';
	}

	function calcSimples(rbt12, anexo, receita) {
		var tabela = SN_TABELAS[anexo] || SN_TABELAS.III;
		var faixa = tabela.find(function (f) { return rbt12 <= f.ate; }) || tabela[tabela.length - 1];
		var idx = tabela.indexOf(faixa);
		var efetiva = rbt12 > 0 ? Math.max(0, (rbt12 * faixa.nominal - faixa.deduzir) / rbt12) : faixa.nominal;
		var valor = receita * efetiva;
		var rep = SN_REPARTICAO[anexo] || SN_REPARTICAO.III;
		var tributos = {};
		Object.keys(rep).forEach(function (k) {
			var v = rep[k];
			tributos[k] = { pct: efetiva * v * 100, valor: valor * v, base: 'Receita bruta · ' + (v * 100).toFixed(2).replace('.', ',') + '% do DAS' };
		});
		return { aliquotaEfetiva: efetiva * 100, aliquotaNominal: faixa.nominal * 100, parcelaDeduzir: faixa.deduzir, faixa: idx + 1, valor: valor, tributos: tributos };
	}

	function empresaTax() {
		return global.PGM_PREC_EMPRESA || {};
	}

	function safeReceita(receita) {
		return receita > 0.009 ? receita : 0.01;
	}

	function calcPresumido(rbt12, presIRPJ, presCSLL, receita) {
		receita = safeReceita(receita);
		var tax = empresaTax();
		var pisPct = tax.pis_pct !== undefined ? tax.pis_pct : 0.65;
		var cofinsPct = tax.cofins_pct !== undefined ? tax.cofins_pct : 3;
		var icmsPct = tax.icms_pct !== undefined ? tax.icms_pct : 18;
		var limAnual = 5000000;
		var acimaLim = rbt12 > limAnual;
		var presIRPJReal = acimaLim ? presIRPJ * 1.1 : presIRPJ;
		var presCSLLReal = acimaLim ? presCSLL * 1.1 : presCSLL;
		var baseIRPJ = receita * (presIRPJReal / 100);
		var irpjBase = baseIRPJ * 0.15;
		var lucroPresTrim = (rbt12 * presIRPJReal / 100) / 4;
		var adicAplicavel = lucroPresTrim > 60000;
		var irpjAdic = adicAplicavel ? Math.max(0, baseIRPJ - (60000 / (rbt12 / 4 || 1) * receita)) * 0.10 : 0;
		var irpj = irpjBase + irpjAdic;
		var baseCSLL = receita * (presCSLLReal / 100);
		var csll = baseCSLL * 0.09;
		var pis = receita * (pisPct / 100);
		var cofins = receita * (cofinsPct / 100);
		var ehServico = presIRPJ === 32;
		var icms = ehServico ? 0 : receita * (icmsPct / 100);
		var iss = ehServico ? receita * 0.05 : 0;
		var ibsCbs = receita * 0.01;
		var total = irpj + csll + pis + cofins + icms + iss;
		return {
			aliquotaEfetiva: (total / receita) * 100,
			valor: total,
			avisoLC224: acimaLim ? 'RBT12 acima R$ 5M · presunção majorada' : '',
			tributos: {
				IRPJ: { pct: (irpj / receita) * 100, valor: irpj, base: 'Presunção ' + presIRPJReal.toFixed(1) + '% × 15% (+adic 10%)' },
				CSLL: { pct: (csll / receita) * 100, valor: csll, base: 'Presunção ' + presCSLLReal.toFixed(1) + '% × 9%' },
				PIS: { pct: pisPct, valor: pis, base: 'Receita bruta · ' + pisPct + '% cumulativo' },
				COFINS: { pct: cofinsPct, valor: cofins, base: 'Receita bruta · ' + cofinsPct + '% cumulativo' },
				ICMS: { pct: ehServico ? 0 : icmsPct, valor: icms, base: 'Cadastro fiscal · ' + icmsPct + '%' },
				ISS: { pct: ehServico ? 5 : 0, valor: iss, base: 'Município · 2-5% (BG: 5%)' },
				IBS_CBS: { pct: 1.00, valor: ibsCbs, base: 'Teste 2026 · 100% compensável' }
			}
		};
	}

	function calcReal(margemReal, creditosPct, receita, ehServico) {
		receita = safeReceita(receita);
		var tax = empresaTax();
		var pisPct = tax.pis_pct !== undefined ? tax.pis_pct : 1.65;
		var cofinsPct = tax.cofins_pct !== undefined ? tax.cofins_pct : 7.6;
		var icmsPct = tax.icms_pct !== undefined ? tax.icms_pct : 18;
		var lucroReal = receita * (margemReal / 100);
		var irpjBase = lucroReal * 0.15;
		var irpjAdic = lucroReal > 0 ? lucroReal * 0.10 : 0;
		var irpj = irpjBase + irpjAdic;
		var csll = lucroReal * 0.09;
		var pisBruto = receita * (pisPct / 100);
		var pis = Math.max(0, pisBruto - pisBruto * (creditosPct / 100));
		var cofinsBruto = receita * (cofinsPct / 100);
		var cofins = Math.max(0, cofinsBruto - cofinsBruto * (creditosPct / 100));
		var icms = ehServico ? 0 : receita * (icmsPct / 100);
		var iss = ehServico ? receita * 0.05 : 0;
		var ibsCbs = receita * 0.01;
		var total = irpj + csll + pis + cofins + icms + iss;
		return {
			aliquotaEfetiva: (total / receita) * 100,
			valor: total,
			tributos: {
				IRPJ: { pct: (irpj / receita) * 100, valor: irpj, base: 'Lucro real ' + margemReal + '% · 15% + 10% adic' },
				CSLL: { pct: (csll / receita) * 100, valor: csll, base: 'Lucro real ' + margemReal + '% · 9%' },
				PIS: { pct: (pis / receita) * 100, valor: pis, base: pisPct + '% − ' + creditosPct + '% créditos' },
				COFINS: { pct: (cofins / receita) * 100, valor: cofins, base: cofinsPct + '% − ' + creditosPct + '% créditos' },
				ICMS: { pct: ehServico ? 0 : icmsPct, valor: icms, base: 'Cadastro fiscal · ' + icmsPct + '%' },
				ISS: { pct: ehServico ? 5 : 0, valor: iss, base: 'Município · 2-5% (BG: 5%)' },
				IBS_CBS: { pct: 1.00, valor: ibsCbs, base: 'Teste 2026 · 100% compensável' }
			}
		};
	}

	function setText(id, val) {
		var el = document.getElementById(id);
		if (el) el.textContent = val;
	}

	function recalcularTudo() {
		try {
			recalcularTudoCore();
		} catch (err) {
			if (typeof console !== 'undefined' && console.error) {
				console.error('PgmPrecificacao.recalcularTudo', err);
			}
		}
	}

	function recalcularTudoCore() {
		var regimeEl = document.querySelector('input[name="regime"]:checked');
		var regime = regimeEl ? regimeEl.value : 'simples';
		var opEl = document.getElementById('prec-operacao');
		var op = opEl ? opEl.value : 'servico';
		var ehServico = (op === 'servico' || op === 'locacao');
		var rbt12 = p2num(document.getElementById('prec-rbt12') && document.getElementById('prec-rbt12').value);

		var custoBase = p2num(document.getElementById('prec-custo') && document.getElementById('prec-custo').value);
		var frete = p2num(document.getElementById('prec-frete') && document.getElementById('prec-frete').value);
		var embal = p2num(document.getElementById('prec-embal') && document.getElementById('prec-embal').value);
		var outros = p2num(document.getElementById('prec-outros-custos') && document.getElementById('prec-outros-custos').value);
		var icmsst = p2num(document.getElementById('prec-icmsst') && document.getElementById('prec-icmsst').value);
		var custoTotal = custoBase + frete + embal + outros + icmsst;

		var despAdm = p2num(document.getElementById('prec-desp-adm') && document.getElementById('prec-desp-adm').value);
		var despCom = p2num(document.getElementById('prec-desp-com') && document.getElementById('prec-desp-com').value);
		var comissao = p2num(document.getElementById('prec-comissao') && document.getElementById('prec-comissao').value);
		var taxaPagto = p2num(document.getElementById('prec-taxa-pagto') && document.getElementById('prec-taxa-pagto').value);
		var inadim = p2num(document.getElementById('prec-inadim') && document.getElementById('prec-inadim').value);
		var freteSaida = p2num(document.getElementById('prec-frete-saida') && document.getElementById('prec-frete-saida').value);
		var margem = p2num(document.getElementById('prec-margem') && document.getElementById('prec-margem').value);
		var totalDespPct = despAdm + despCom + comissao + taxaPagto + inadim + freteSaida + margem;

		setText('prec-custo-total', num2BRL(custoTotal));
		setText('prec-desp-total-pct', num2pct(totalDespPct));

		var regimeLabel = '';
		if (regime === 'simples') {
			var anexo = (document.getElementById('prec-anexo') && document.getElementById('prec-anexo').value) || 'III';
			var fatorR = p2num(document.getElementById('prec-fator-r') && document.getElementById('prec-fator-r').value);
			var fatorStatusEl = document.getElementById('prec-fator-status');
			if (fatorStatusEl) {
				if (fatorR >= 28) {
					fatorStatusEl.textContent = '✓ Anexo III ok';
					fatorStatusEl.className = 'badge b-paga';
				} else {
					fatorStatusEl.textContent = '⚠ Migra Anexo V';
					fatorStatusEl.className = 'badge b-vencendo';
				}
				fatorStatusEl.style.fontSize = '10px';
			}
			var tabela = SN_TABELAS[anexo];
			var faixaInfo = tabela.find(function (f) { return rbt12 <= f.ate; }) || tabela[tabela.length - 1];
			var idxFaixa = tabela.indexOf(faixaInfo) + 1;
			var efetivaPreview = rbt12 > 0 ? Math.max(0, (rbt12 * faixaInfo.nominal - faixaInfo.deduzir) / rbt12) : faixaInfo.nominal;
			var elFaixa = document.getElementById('prec-faixa-info');
			if (elFaixa) {
				elFaixa.innerHTML = 'Faixa ' + idxFaixa + ' · alíquota nominal ' + (faixaInfo.nominal * 100).toFixed(2).replace('.', ',') + '% · parcela a deduzir ' + num2BRL(faixaInfo.deduzir) + ' · alíquota efetiva <strong>' + (efetivaPreview * 100).toFixed(2).replace('.', ',') + '%</strong>';
			}
			regimeLabel = 'Simples Nacional · Anexo ' + anexo;
		} else if (regime === 'presumido') {
			regimeLabel = 'Lucro Presumido';
			var elAviso = document.getElementById('prec-aviso-lc224');
			if (elAviso) {
				elAviso.textContent = rbt12 > 5000000 ? ' Esta empresa está acima do limite (RBT12 ' + num2BRL(rbt12) + ').' : '';
			}
		} else {
			regimeLabel = 'Lucro Real';
		}
		setText('prec-regime-label', regimeLabel);

		function calcPreco(reg) {
			var preco = custoTotal / Math.max(0.01, (1 - (totalDespPct + 13) / 100));
			var trib;
			var i;
			for (i = 0; i < 8; i++) {
				if (reg === 'simples') {
					var anexo2 = (document.getElementById('prec-anexo') && document.getElementById('prec-anexo').value) || 'III';
					trib = calcSimples(rbt12, anexo2, preco);
				} else if (reg === 'presumido') {
					var presIRPJ = p2num(document.getElementById('prec-pres-irpj') && document.getElementById('prec-pres-irpj').value);
					var presCSLL = p2num(document.getElementById('prec-pres-csll') && document.getElementById('prec-pres-csll').value);
					trib = calcPresumido(rbt12, presIRPJ, presCSLL, preco);
				} else {
					var margemReal = p2num(document.getElementById('prec-margem-real') && document.getElementById('prec-margem-real').value);
					var creditos = p2num(document.getElementById('prec-creditos') && document.getElementById('prec-creditos').value);
					trib = calcReal(margemReal, creditos, preco, ehServico);
				}
				var cargaPct = trib.aliquotaEfetiva;
				var divisor = 1 - (totalDespPct + cargaPct) / 100;
				if (divisor <= 0) break;
				var novoPreco = custoTotal / divisor;
				if (Math.abs(novoPreco - preco) < 0.01) {
					preco = novoPreco;
					break;
				}
				preco = novoPreco;
			}
			return { preco: preco, trib: trib };
		}

		var resultado = calcPreco(regime);
		var preco = resultado.preco;
		var tribAtual = resultado.trib;
		if (!tribAtual || !tribAtual.tributos) {
			return;
		}

		var cargaFed = 0;
		var cargaEst = 0;
		var cargaIBS = 0;
		Object.keys(tribAtual.tributos).forEach(function (k) {
			var v = tribAtual.tributos[k];
			if (k === 'ICMS' || k === 'ISS') cargaEst += v.pct;
			else if (k === 'IBS_CBS') cargaIBS += v.pct;
			else cargaFed += v.pct;
		});

		setText('prec-carga-fed', num2pct(cargaFed));
		setText('prec-carga-fed-rs', num2BRL(preco * cargaFed / 100));
		setText('prec-carga-est', num2pct(cargaEst));
		setText('prec-carga-est-rs', num2BRL(preco * cargaEst / 100));
		setText('prec-carga-ibs', num2pct(cargaIBS));
		setText('prec-carga-total', num2pct(tribAtual.aliquotaEfetiva));
		setText('prec-carga-total-rs', num2BRL(tribAtual.valor));

		var tbody = document.getElementById('prec-trib-tabela');
		if (tbody) {
			var rows = '';
			var tribLabels = {
				IRPJ: 'IRPJ · Imposto de Renda PJ',
				CSLL: 'CSLL · Contribuição Social',
				PIS: 'PIS · Contribuição',
				COFINS: 'COFINS · Contribuição',
				CPP: 'CPP · Contribuição Previdenciária Patronal',
				ICMS: 'ICMS · Imposto Estadual',
				ISS: 'ISS · Imposto Municipal',
				IPI: 'IPI · Imposto sobre Produtos Industrializados',
				IBS_CBS: 'IBS + CBS · Reforma Tributária'
			};
			var esferaLabel = {
				IRPJ: 'Federal', CSLL: 'Federal', PIS: 'Federal', COFINS: 'Federal', CPP: 'Federal', IPI: 'Federal',
				ICMS: 'Estadual', ISS: 'Municipal', IBS_CBS: 'Esta+Mun+Fed'
			};
			Object.keys(tribAtual.tributos).forEach(function (k) {
				var v = tribAtual.tributos[k];
				if (v.valor === 0 && v.pct === 0) return;
				rows += '<tr style="border-bottom:1px solid var(--border-light);"><td style="padding:8px 10px;font-weight:600;">' + (tribLabels[k] || k) + '</td><td style="padding:8px 10px;font-size:11px;color:var(--text-muted);">' + (esferaLabel[k] || '—') + '</td><td style="padding:8px 10px;font-size:11px;color:var(--text-muted);">' + (v.base || '—') + '</td><td style="padding:8px 10px;text-align:right;font-family:monospace;">' + num2pct(v.pct, 3) + '</td><td style="padding:8px 10px;text-align:right;font-weight:600;">' + num2BRL(v.valor) + '</td><td style="padding:8px 10px;text-align:right;color:var(--teal-dark);"><strong>' + num2pct(v.pct) + '</strong></td></tr>';
			});
			tbody.innerHTML = rows;
		}
		var tfoot = document.getElementById('prec-trib-foot');
		if (tfoot) {
			tfoot.innerHTML = '<tr style="background:var(--teal);color:#fff;"><td colspan="3" style="padding:12px;font-weight:700;">TOTAL DE TRIBUTOS SOBRE A VENDA</td><td style="padding:12px;text-align:right;font-weight:700;font-family:monospace;">' + num2pct(tribAtual.aliquotaEfetiva, 3) + '</td><td style="padding:12px;text-align:right;font-weight:700;font-size:14px;">' + num2BRL(tribAtual.valor) + '</td><td style="padding:12px;text-align:right;font-weight:700;">' + num2pct(tribAtual.aliquotaEfetiva) + '</td></tr>';
		}

		var cargaTotal = tribAtual.aliquotaEfetiva;
		var divisorRes = 1 - (totalDespPct + cargaTotal) / 100;
		var lucroLiq = preco * margem / 100;
		var margemBruta = preco > 0 ? ((preco - custoTotal) / preco) * 100 : 0;
		var markup = custoTotal > 0 ? ((preco - custoTotal) / custoTotal) * 100 : 0;

		setText('prec-res-custo', num2BRL(custoTotal));
		setText('prec-res-divisor', divisorRes.toFixed(4).replace('.', ','));
		setText('prec-res-preco', num2BRL(preco));
		setText('prec-res-markup', num2pct(markup));
		setText('prec-res-margem-bruta', num2pct(margemBruta));
		setText('prec-res-margem', num2pct(margem));
		setText('prec-res-lucro', num2BRL(lucroLiq));

		var dre = document.getElementById('prec-dre-body');
		if (dre) {
			var valDespAdm = preco * despAdm / 100;
			var valDespCom = preco * despCom / 100;
			var valComissao = preco * comissao / 100;
			var valTaxa = preco * taxaPagto / 100;
			var valInadim = preco * inadim / 100;
			var valFreteSaida = preco * freteSaida / 100;
			dre.innerHTML =
				'<tr><td style="padding:8px;font-weight:600;color:var(--teal-dark);">(+) Receita bruta</td><td style="padding:8px;text-align:right;font-weight:600;color:var(--teal-dark);">' + num2BRL(preco) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">100,00%</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;">(−) Tributos sobre venda</td><td style="padding:8px;text-align:right;color:#7A1822;">' + num2BRL(tribAtual.valor) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(cargaTotal) + '</td></tr>' +
				'<tr style="border-top:1px solid var(--border-light);"><td style="padding:8px;font-weight:600;">(=) Receita líquida</td><td style="padding:8px;text-align:right;font-weight:600;">' + num2BRL(preco - tribAtual.valor) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(100 - cargaTotal) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;">(−) Custo do produto</td><td style="padding:8px;text-align:right;color:#7A1822;">' + num2BRL(custoTotal) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(preco > 0 ? custoTotal / preco * 100 : 0) + '</td></tr>' +
				'<tr style="border-top:1px solid var(--border-light);"><td style="padding:8px;font-weight:600;">(=) Lucro bruto</td><td style="padding:8px;text-align:right;font-weight:600;">' + num2BRL(preco - tribAtual.valor - custoTotal) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(preco > 0 ? (preco - tribAtual.valor - custoTotal) / preco * 100 : 0) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Despesas administrativas</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valDespAdm) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(despAdm) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Despesas comerciais</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valDespCom) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(despCom) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Comissão vendedor</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valComissao) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(comissao) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Taxas (cartão / boleto)</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valTaxa) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(taxaPagto) + '</td></tr>' +
				'<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Provisão inadimplência</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valInadim) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(inadim) + '</td></tr>' +
				(freteSaida > 0 ? '<tr><td style="padding:8px;color:#7A1822;font-size:11px;">(−) Frete saída</td><td style="padding:8px;text-align:right;color:#7A1822;font-size:11px;">' + num2BRL(valFreteSaida) + '</td><td style="padding:8px;text-align:right;color:var(--text-muted);font-size:11px;">' + num2pct(freteSaida) + '</td></tr>' : '') +
				'<tr style="background:var(--teal-light);"><td style="padding:10px;font-weight:700;color:var(--teal-dark);">(=) LUCRO LÍQUIDO</td><td style="padding:10px;text-align:right;font-weight:700;color:var(--teal-dark);font-size:15px;">' + num2BRL(lucroLiq) + '</td><td style="padding:10px;text-align:right;color:var(--teal-dark);font-weight:700;">' + num2pct(margem) + '</td></tr>';
		}

		function setReg(prefix, dados) {
			setText(prefix + '-preco', num2BRL(dados.preco));
			setText(prefix + '-carga', num2pct(dados.trib.aliquotaEfetiva));
			setText(prefix + '-lucro', num2BRL(dados.preco * margem / 100));
		}
		var compS = calcPreco('simples');
		var compP = calcPreco('presumido');
		var compR = calcPreco('real');
		setReg('comp-simples', compS);
		setReg('comp-presumido', compP);
		setReg('comp-real', compR);

		var precos = { 'Simples Nacional': compS.preco, 'Lucro Presumido': compP.preco, 'Lucro Real': compR.preco };
		var melhor = Object.keys(precos).sort(function (a, b) { return precos[a] - precos[b]; })[0];
		var economia = Math.max.apply(null, Object.keys(precos).map(function (k) { return precos[k]; })) - precos[melhor];
		var elRecomenda = document.getElementById('prec-comp-recomenda');
		if (elRecomenda) {
			elRecomenda.innerHTML = '💡 <strong>Recomendação:</strong> O <strong>' + melhor + '</strong> oferece o melhor preço final (mais competitivo) — economia de até ' + num2BRL(economia) + ' por unidade comparado ao regime mais oneroso. <em>Avalie sempre com seu contador antes de mudar de regime.</em>';
		}

		['simples', 'presumido', 'real'].forEach(function (r) {
			var card = document.getElementById('reg-card-' + r);
			if (card) {
				if (r === regime) {
					card.style.background = 'var(--teal-light)';
					card.style.borderColor = 'var(--teal)';
				} else {
					card.style.background = 'var(--bg-surface)';
					card.style.borderColor = 'var(--border-light)';
				}
			}
		});
	}

	function trocarRegime(regime) {
		var sc = document.getElementById('prec-simples-config');
		if (sc) sc.style.display = regime === 'simples' ? '' : 'none';
		var pc = document.getElementById('prec-presumido-config');
		if (pc) pc.style.display = regime === 'presumido' ? '' : 'none';
		var rc = document.getElementById('prec-real-config');
		if (rc) rc.style.display = regime === 'real' ? '' : 'none';
		recalcularTudo();
	}

	function alternarTributos() {
		var det = document.getElementById('prec-trib-detalhe');
		var btn = document.getElementById('btn-toggle-trib');
		if (!det) return;
		var aberto = det.style.display !== 'none';
		det.style.display = aberto ? 'none' : '';
		if (btn) btn.textContent = aberto ? '▼ Detalhar' : '▲ Recolher';
	}

	function bindRecalc() {
		if (global.__PGM_PRECIFIC_DELEGATED__) {
			return;
		}
		document.addEventListener('change', function (e) {
			var el = e.target;
			if (!el || !el.id) {
				return;
			}
			if (el.id === 'prec-produto-base') {
				return;
			}
			if (el.name === 'regime') {
				trocarRegime(el.value);
				return;
			}
			if (el.id.indexOf('prec-') === 0 || el.id === 'prec-operacao') {
				recalcularTudo();
			}
		});
		document.addEventListener('input', function (e) {
			var el = e.target;
			if (el && el.id && el.id.indexOf('prec-') === 0 && el.id !== 'prec-produto-base') {
				recalcularTudo();
			}
		});
		global.__PGM_PRECIFIC_DELEGATED__ = true;
	}

	function setVal(id, val) {
		var el = document.getElementById(id);
		if (el && val !== undefined && val !== null) {
			el.value = val;
		}
	}

	function setRadioRegime(regime) {
		var r = document.querySelector('input[name="regime"][value="' + regime + '"]');
		if (r) {
			r.checked = true;
			trocarRegime(regime);
		}
	}

	function carregarProdutoDb(id) {
		var url = global.PGM_PRECIFIC_DADOS_URL;
		if (!url || !id || id === '0') {
			return;
		}
		var info = document.getElementById('prec-produto-info');
		if (info) {
			info.textContent = 'Carregando dados do banco…';
			info.style.display = 'block';
		}
		fetch(url + (url.indexOf('?') >= 0 ? '&' : '?') + 'produto_id=' + encodeURIComponent(id), {
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (!data || !data.ok) {
					if (info) {
						info.textContent = (data && data.error) ? data.error : 'Não foi possível carregar o produto.';
					}
					return;
				}
				if (data.empresa) {
					aplicarDadosEmpresa(data.empresa);
				}
				if (data.produto) {
					global.PGM_PREC_PRODUTOS = global.PGM_PREC_PRODUTOS || {};
					global.PGM_PREC_PRODUTOS[data.produto.id] = data.produto;
					global.PGM_PREC_PRODUTOS[String(data.produto.id)] = data.produto;
					aplicarDadosProduto(data.produto);
					if (parseFloat(data.produto.venda) <= 0 && info) {
						info.textContent += ' · Cadastre o preço em Produtos ou na tabela vigente.';
					}
				}
			})
			.catch(function () {
				if (info) info.textContent = 'Erro de rede ao buscar preço do produto.';
			});
	}

	function aplicarDadosEmpresa(emp) {
		if (!emp) return;
		if (emp.rbt12_fmt) setVal('prec-rbt12', emp.rbt12_fmt);
		var fonteEl = document.getElementById('prec-rbt12-fonte');
		if (fonteEl && emp.fonte_rbt12 === 'faturamento_12m') {
			fonteEl.textContent = 'Fonte: faturamento acumulado dos últimos 12 meses (tabela Faturamento).';
		}
		if (emp.operacao) setVal('prec-operacao', emp.operacao);
		if (emp.anexo) setVal('prec-anexo', emp.anexo);
		if (emp.fator_r !== undefined) setVal('prec-fator-r', String(emp.fator_r));
		if (emp.pres_irpj !== undefined) setVal('prec-pres-irpj', String(emp.pres_irpj));
		if (emp.pres_csll !== undefined) setVal('prec-pres-csll', String(emp.pres_csll));
		if (emp.margem_real !== undefined) setVal('prec-margem-real', String(emp.margem_real).replace('.', ','));
		if (emp.creditos_pct !== undefined) setVal('prec-creditos', String(emp.creditos_pct).replace('.', ','));
		if (emp.regime_ui) setRadioRegime(emp.regime_ui);
	}

	function aplicarDadosProduto(p) {
		if (!p) return;
		if (p.venda_fmt) setVal('prec-venda-atual', p.venda_fmt);
		else if (p.venda > 0) setVal('prec-venda-atual', (p.venda).toFixed(2).replace('.', ','));
		var custoFmt = p.custo_fmt;
		if ((!custoFmt || p2num(custoFmt) <= 0) && p.venda > 0) {
			custoFmt = (p.venda * 0.7).toFixed(2).replace('.', ',');
		}
		if (custoFmt) setVal('prec-custo', custoFmt);
		if (p.frete_fmt) setVal('prec-frete', p.frete_fmt);
		if (p.margem_lucro_pct !== undefined) {
			setVal('prec-margem', String(p.margem_lucro_pct).replace('.', ','));
		}
		if (p.operacao) setVal('prec-operacao', p.operacao);
		if (p.anexo) setVal('prec-anexo', p.anexo);
		if (p.regime) setRadioRegime(p.regime);
		var info = document.getElementById('prec-produto-info');
		if (info) {
			var fonteC = p.fonte_custo === 'erp' ? 'ERP' : (p.fonte_custo === 'cadastro' ? 'cadastro' : (p.fonte_custo === 'estimado_margem' ? 'estimativa (margem)' : 'estimativa'));
			var fonteV = p.fonte_venda === 'erp' ? 'ERP' : (p.fonte_venda === 'tabela' ? 'tabela ativa' : 'cadastro');
			info.textContent = (p.codigo || '') + ' · ' + (p.tipo_label || '') +
				' · Custo (' + fonteC + '): ' + (p.custo_fmt || '—') +
				' · Venda (' + fonteV + '): ' + (p.venda_fmt || '—') +
				' · Margem atual: ' + (p.margem_fmt || '—');
			info.style.display = 'block';
		}
		var hid = document.getElementById('precificacao-produto-id');
		if (hid) hid.value = p.id || '0';
		recalcularTudo();
	}

	function aplicarProdutoBase(custoFmt) {
		setVal('prec-custo', custoFmt);
		recalcularTudo();
	}

	function parsePrecoSugerido() {
		var el = document.getElementById('prec-res-preco');
		if (!el) return 0;
		return p2num(el.textContent);
	}

	function aplicarAoProduto(formId) {
		var form = document.getElementById(formId);
		var pid = document.getElementById('precificacao-produto-id');
		var preco = parsePrecoSugerido();
		if (!form || !pid || parseInt(pid.value, 10) <= 0) {
			alert('Selecione um produto do catálogo antes de aplicar o preço.');
			return;
		}
		var hidden = document.getElementById('precificacao-vl-hidden');
		if (hidden) hidden.value = preco.toFixed(2);
		if (preco <= 0) {
			alert('Preço sugerido inválido. Ajuste os custos e recalcule.');
			return;
		}
		if (confirm('Aplicar preço sugerido de ' + num2BRL(preco) + ' ao produto selecionado?')) {
			form.submit();
		}
	}

	function bootProdutoSelect() {
		var sel = document.getElementById('prec-produto-base');
		if (!sel) {
			return;
		}
		if (sel.__pgmPrecBound) {
			return;
		}
		sel.__pgmPrecBound = true;
		sel.addEventListener('change', function () {
			var id = sel.value;
			if (id === '0') {
				var info = document.getElementById('prec-produto-info');
				if (info) info.style.display = 'none';
				var hid0 = document.getElementById('precificacao-produto-id');
				if (hid0) hid0.value = '0';
				aplicarDadosEmpresa(global.PGM_PREC_EMPRESA || {});
				return;
			}
			carregarProdutoDb(id);
		});
	}

	function bootPrecificacaoPage(empresaCtx) {
		bindRecalc();
		bootProdutoSelect();
		if (empresaCtx) aplicarDadosEmpresa(empresaCtx);
		var sel = document.getElementById('prec-produto-base');
		if (sel && sel.value !== '0') {
			carregarProdutoDb(sel.value);
		} else {
			recalcularTudo();
		}
	}

	global.PgmPrecificacao.carregarProdutoDb = carregarProdutoDb;

	global.PgmPrecificacao = {
		carregarProdutoDb: carregarProdutoDb,
		recalcularTudo: recalcularTudo,
		trocarRegime: trocarRegime,
		alternarTributos: alternarTributos,
		aplicarProdutoBase: aplicarProdutoBase,
		aplicarDadosEmpresa: aplicarDadosEmpresa,
		aplicarDadosProduto: aplicarDadosProduto,
		aplicarAoProduto: aplicarAoProduto,
		num2BRL: num2BRL,
		boot: bootPrecificacaoPage,
		init: bootPrecificacaoPage
	};

	global.pgmPrecificacaoBoot = function () {
		if (!global.PgmPrecificacao) return;
		global.PgmPrecificacao.boot(global.PGM_PREC_EMPRESA || {});
	};
})(typeof window !== 'undefined' ? window : this);
