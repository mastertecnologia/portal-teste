# Padrao interno para novos scripts JS de tela

Objetivo: evitar retorno de jQuery em codigo novo e manter comportamento consistente entre telas.

## Alerta importante (plugins e legado)

Nao migrar automaticamente blocos que dependem de plugins/terceiros sem analise tecnica previa, por exemplo:

- `select2`, `datatables`, `bootstrap-select` (`changed.bs.select`)
- `maskMoney`, `jquery.mask`, `bootstrapMaterialDatePicker`
- componentes antigos que exigem jQuery para lifecycle/eventos

Regra: codigo novo segue este padrao; codigo legado com plugin so deve ser migrado em tarefa dedicada com validacao manual do fluxo.

## Template padrao (copiar e adaptar)

```html
<script>
(function() {
	'use strict';

	function initScreenModule() {
		var container = document.getElementById('screenContainerId');
		if (!container) return;
		if (container.__initialized) return;
		container.__initialized = true;

		var moduleRoot = container.closest('.module-root') || document;
		var searchInput = moduleRoot.querySelector('#searchInputId');

		function getRows(scopeContainer) {
			if (!scopeContainer || typeof scopeContainer.querySelectorAll !== 'function') return [];
			return Array.prototype.slice.call(scopeContainer.querySelectorAll('.row-selector'));
		}

		function debounce(fn, delay) {
			var timer = null;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(fn, delay);
			};
		}

		function applyFilters() {
			var rows = getRows(container);
			rows.forEach(function(row) {
				var isVisible = true; // substituir pela regra da tela
				row.style.display = isVisible ? '' : 'none';
			});
		}

		container.addEventListener('click', function(event) {
			var target = event.target;
			if (!target || !container.contains(target)) return;
			if (target.closest('a, button, input, select, textarea, label')) return;

			var row = target.closest('.row-selector');
			if (!row || !container.contains(row)) return;
			if (row.style.display === 'none') return;

			row.classList.toggle('is-open');
		});

		if (searchInput) {
			searchInput.addEventListener('input', debounce(applyFilters, 250));
		}

		// Uso de HTTP com PGMHttp (opcional, somente quando houver request na tela)
		if (window.PGMHttp && typeof window.PGMHttp.httpPost === 'function') {
			window.PGMHttp.httpPost('/alguma-rota', { _csrfToken: window.PGMHttp.getCsrfToken() })
				.then(function(res) { return res.text(); })
				.catch(function(err) {
					if (window.console && console.warn) console.warn('Falha na requisicao', err && err.message);
				});
		}

		applyFilters();
	}

	document.addEventListener('DOMContentLoaded', initScreenModule);
})();
</script>
```

## Exemplo real adaptado (index_clientes)

Referencia: `src/Template/Users/index_clientes.ctp`.

- IIFE + init por `DOMContentLoaded`
- escopo por container (`#tableEmpresas`) e raiz do modulo (`.cli-root`)
- protecao contra reinit (`container.__initialized`)
- delegacao de clique no container
- `getRows(container)` usando `container.querySelectorAll`
- debounce de busca e filtros sem jQuery

```javascript
(function() {
	function initClientesTableModule() {
		var container = document.getElementById('tableEmpresas');
		if (!container) return;
		if (container.__initialized) return;
		container.__initialized = true;
		var moduleRoot = container.closest('.cli-root') || document;

		var searchInput = moduleRoot.querySelector('#uc-search');

		function getRows(scopeContainer) {
			if (!scopeContainer || typeof scopeContainer.querySelectorAll !== 'function') return [];
			return Array.prototype.slice.call(scopeContainer.querySelectorAll('.cli-usr-row'));
		}

		function debounce(fn, delay) {
			var timer = null;
			return function() {
				clearTimeout(timer);
				timer = setTimeout(fn, delay);
			};
		}

		function applyFilters() {
			var rows = getRows(container);
			rows.forEach(function(row) {
				// logica real da tela
				row.style.display = '';
			});
		}

		container.addEventListener('click', function(event) {
			var target = event.target;
			if (!target || !container.contains(target)) return;
			if (target.closest('a, button, input, select, textarea, label')) return;

			var row = target.closest('.cli-usr-row');
			if (!row || !container.contains(row)) return;
			if (row.style.display === 'none') return;

			var detailId = row.getAttribute('data-detail');
			var detail = detailId ? document.getElementById(detailId) : null;
			if (!detail) return;

			row.classList.toggle('cli-row-open');
			detail.classList.toggle('cli-usr-open');
			detail.style.display = row.classList.contains('cli-row-open') ? '' : 'none';
		});

		if (searchInput) {
			searchInput.addEventListener('input', debounce(applyFilters, 250));
		}

		applyFilters();
	}

	document.addEventListener('DOMContentLoaded', initClientesTableModule);
})();
```

## Regras obrigatorias para codigo novo

1. Nao usar jQuery em scripts novos (`$`, `jQuery`, `$.ajax`, `$.post`, `$(...)`).
2. Encapsular sempre em IIFE.
3. Inicializar em `DOMContentLoaded`.
4. Escopar seletores ao container da tela/modulo.
5. Proteger inicializacao com `container.__initialized`.
6. Usar delegacao de eventos para elementos dinamicos.
7. Ignorar cliques em `a, button, input, select, textarea, label` quando houver row toggle.
8. Usar `window.PGMHttp` para HTTP; nao usar `fetch` direto em tela comum.
9. Sempre tratar erros com `.catch(...)` em promises de rede.
10. Evitar variaveis globais; tudo no escopo do modulo.
11. Manter null checks/fallback defensivo para elementos DOM.

## Checklist rapido para devs

- [ ] Sem jQuery no codigo novo?
- [ ] Script esta em IIFE?
- [ ] Usa `DOMContentLoaded`?
- [ ] Tem `container` e `container.__initialized`?
- [ ] Seletores estao escopados ao `container`/`moduleRoot`?
- [ ] Eventos em `addEventListener` (sem jQuery)?
- [ ] Delegacao aplicada onde ha conteudo dinamico?
- [ ] HTTP usa `window.PGMHttp`?
- [ ] `catch` presente nas promises de rede?
- [ ] Null checks presentes antes de acessar elementos/metodos DOM?
- [ ] Sem variavel global nova em `window`?
- [ ] Sem mudanca visual involuntaria?
