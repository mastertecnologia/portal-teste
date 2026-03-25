/**
 * Utilitários do módulo Orçamentos (referência: prompt_cursor_cakephp.md PROMPT 4).
 * Catálogo: GET /orcamentos/catalogo?q= — ver OrcamentosController::catalogo().
 * Formulário legado (add/edit) mantém cálculos e AJAX em scripts inline nas views.
 */
(function (window) {
	'use strict';

	window.PgmOrcamentos = window.PgmOrcamentos || {};

	/**
	 * @param {string} [query]
	 * @returns {Promise<Array<{id:number,codigo:string,nome:string,tipo:number,preco_venda:number,preco_custo:number,estoque:null|number,margem:number}>>}
	 */
	window.PgmOrcamentos.fetchCatalogo = function (query) {
		var q = encodeURIComponent(query || '');
		return fetch('/orcamentos/catalogo?q=' + q, {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
		}).then(function (r) {
			if (!r.ok) {
				throw new Error('Catálogo: HTTP ' + r.status);
			}
			return r.json();
		});
	};

	/** Alçada do prompt: desconto > 15% ou total > 50000 */
	window.PgmOrcamentos.exigeAprovacaoInterna = function (descontoPercent, totalValor) {
		var d = parseFloat(descontoPercent) || 0;
		var t = parseFloat(totalValor) || 0;
		return d > 15 || t > 50000;
	};
})(window);
