<?php
/**
 * Filtros client-side da lista CRM (protótipo pg-clientes).
 */
?>
<?php $this->start('script'); ?>
<script>
(function () {
	var root = document.getElementById('pg-clientes-lista');
	if (!root) return;
	var tbody = root.querySelector('#cli-proto-table tbody');
	if (!tbody) return;

	var statusFilter = '';
	var type = 'pj';
	var typeAll = true;
	var segmentoFilter = '';
	var vendedorFilter = '';
	var activeChip = '';
	var filterPj = true;
	var filterPf = true;
	var filterPapelCliente = true;
	var filterPapelFornecedor = true;
	var searchInput = root.querySelector('#cli-search');
	var searchMode = root.querySelector('#cli-search-mode');

	function normalizeAccent(s) {
		if (!s) return '';
		try {
			return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		} catch (e) {
			return s;
		}
	}

	function detectQueryType(raw) {
		var s = (raw || '').trim();
		if (!s) return { type: 'empty' };
		if (s.indexOf('@') >= 0) {
			return { type: 'email', value: s.toLowerCase().replace(/\s/g, '') };
		}
		var digits = s.replace(/\D/g, '');
		var onlyDocChars = /^[\d\s.\-\/()]+$/u.test(s);
		if (digits.length >= 3 && onlyDocChars) {
			return { type: 'doc', digits: digits };
		}
		var lowered = normalizeAccent(s.toLowerCase());
		var words = lowered.split(/\s+/).filter(function (w) { return w.length > 0; });
		return { type: 'nome', words: words };
	}

	function rowVisible(row) {
		if (statusFilter !== '' && row.getAttribute('data-cli-status') !== statusFilter) {
			return false;
		}
		var rowTipo = row.getAttribute('data-cli-tipo') || '';
		if (rowTipo === 'pj' && !filterPj) return false;
		if (rowTipo === 'pf' && !filterPf) return false;
		if (!filterPj && !filterPf) return false;
		var ehCli = row.getAttribute('data-cli-papel-cliente') === '1';
		var ehForn = row.getAttribute('data-cli-papel-fornecedor') === '1';
		if (!filterPapelCliente && !filterPapelFornecedor) return false;
		if (filterPapelCliente && !filterPapelFornecedor && !ehCli) return false;
		if (!filterPapelCliente && filterPapelFornecedor && !ehForn) return false;
		if (filterPapelCliente && filterPapelFornecedor && !ehCli && !ehForn) return false;
		if (segmentoFilter !== '' && row.getAttribute('data-cli-segmento') !== segmentoFilter) {
			return false;
		}
		if (vendedorFilter !== '' && String(row.getAttribute('data-cli-vendedor') || '') !== String(vendedorFilter)) {
			return false;
		}
		if (activeChip === 'atraso' && row.getAttribute('data-cli-atraso') !== '1') return false;
		if (activeChip === 'vip' && row.getAttribute('data-cli-vip') !== '1') return false;
		if (activeChip === 'novos' && row.getAttribute('data-cli-novo') !== '1') return false;
		if (activeChip === 'aniversariantes' && row.getAttribute('data-cli-aniv') !== '1') return false;
		if (activeChip === 'sem-contato' && row.getAttribute('data-cli-sem-contato') !== '1') return false;
		if (activeChip === 'top-receita' && row.getAttribute('data-cli-top10') !== '1') return false;
		return true;
	}

	function rowMatches(row, q) {
		if (!rowVisible(row)) return false;
		var doc = row.getAttribute('data-cli-doc') || '';
		var email = row.getAttribute('data-cli-email') || '';
		var text = normalizeAccent((row.getAttribute('data-cli-text') || '').toLowerCase());
		if (q.type === 'empty') return true;
		if (q.type === 'email') return email.indexOf(q.value) !== -1;
		if (q.type === 'doc') return doc && doc.indexOf(q.digits) !== -1;
		if (q.type === 'nome') {
			for (var i = 0; i < q.words.length; i++) {
				if (text.indexOf(q.words[i]) === -1) return false;
			}
			return true;
		}
		return true;
	}

	function rowRank(row, q) {
		var ord = parseInt(row.getAttribute('data-cli-ord') || '0', 10);
		if (q.type === 'empty') return ord;
		var doc = row.getAttribute('data-cli-doc') || '';
		var email = row.getAttribute('data-cli-email') || '';
		var text = normalizeAccent((row.getAttribute('data-cli-text') || '').toLowerCase());
		var primary = normalizeAccent((row.getAttribute('data-cli-primary') || '').toLowerCase());
		function pack(tier, lk) {
			return tier * 10000000 + Math.min(lk || 0, 9999) * 1000 + ord;
		}
		if (q.type === 'email') {
			if (email === q.value) return pack(1, text.length);
			if (email.indexOf(q.value) === 0) return pack(2, text.length);
			return pack(3, text.length);
		}
		if (q.type === 'doc') {
			if (!doc) return pack(99, 9999);
			if (doc === q.digits) return pack(1, doc.length);
			if (doc.indexOf(q.digits) === 0) return pack(2, doc.length);
			return pack(3, doc.length);
		}
		if (q.type === 'nome') {
			var phrase = q.words.join(' ');
			if (text === phrase || primary === phrase) return pack(1, text.length);
			if (primary.indexOf(phrase) === 0) return pack(2, text.length);
			return pack(5, text.length);
		}
		return pack(50, text.length);
	}

	function redrawTable() {
		var q = detectQueryType(searchInput ? searchInput.value : '');
		var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-cli-edit-url]'));
		var visible = [];
		rows.forEach(function (row) {
			if (rowMatches(row, q)) {
				visible.push({ row: row, rank: rowRank(row, q) });
			}
		});
		visible.sort(function (a, b) { return a.rank - b.rank; });
		rows.forEach(function (row) { row.style.display = 'none'; });
		visible.forEach(function (item) {
			item.row.style.display = '';
			tbody.appendChild(item.row);
		});
		var empty = root.querySelector('#cli-proto-empty');
		if (empty) {
			empty.style.display = visible.length === 0 ? '' : 'none';
		}
	}

	function updateSearchModeUi() {
		if (!searchInput || !searchMode) return;
		var q = detectQueryType(searchInput.value);
		if (q.type === 'empty') { searchMode.textContent = ''; return; }
		if (q.type === 'email') { searchMode.textContent = 'E-mail'; return; }
		if (q.type === 'doc') {
			searchMode.textContent = q.digits.length === 11 ? 'CPF' : 'CNPJ/CPF';
			return;
		}
		searchMode.textContent = 'Nome';
	}

	root.querySelectorAll('[data-cli-kpi]').forEach(function (el) {
		el.addEventListener('click', function () {
			var k = el.getAttribute('data-cli-kpi');
			if (k === 'ativos' || k === 'bloqueados') {
				statusFilter = k === 'bloqueados' ? 'inativos' : 'ativos';
				var sel = root.querySelector('#cli-filter-status');
				if (sel) sel.value = statusFilter;
				root.querySelectorAll('[data-cli-kpi]').forEach(function (kpi) { kpi.classList.remove('cli-kpi-active'); });
				el.classList.add('cli-kpi-active');
				redrawTable();
			}
			if (k === 'aniversariantes') {
				activeChip = activeChip === 'aniversariantes' ? '' : 'aniversariantes';
				root.querySelectorAll('.cli-proto-chip').forEach(function (c) { c.classList.remove('active'); });
				if (activeChip) {
					var chip = root.querySelector('.cli-proto-chip[data-chip="aniversariantes"]');
					if (chip) chip.classList.add('active');
				}
				redrawTable();
			}
		});
	});

	var statusSel = root.querySelector('#cli-filter-status');
	if (statusSel) {
		statusSel.addEventListener('change', function () {
			statusFilter = statusSel.value;
			redrawTable();
		});
	}
	var segSel = root.querySelector('#cli-filter-segmento');
	if (segSel) {
		segSel.addEventListener('change', function () {
			segmentoFilter = segSel.value;
			redrawTable();
		});
	}
	var tipoPj = root.querySelector('#cli-filter-tipo-pj');
	var tipoPf = root.querySelector('#cli-filter-tipo-pf');
	function syncTipoFromChecks() {
		filterPj = !tipoPj || tipoPj.checked;
		filterPf = !tipoPf || tipoPf.checked;
		redrawTable();
	}
	if (tipoPj) tipoPj.addEventListener('change', syncTipoFromChecks);
	if (tipoPf) tipoPf.addEventListener('change', syncTipoFromChecks);
	var papelCli = root.querySelector('#cli-filter-papel-cliente');
	var papelForn = root.querySelector('#cli-filter-papel-fornecedor');
	function syncPapelFromChecks() {
		filterPapelCliente = !papelCli || papelCli.checked;
		filterPapelFornecedor = !papelForn || papelForn.checked;
		redrawTable();
	}
	if (papelCli) papelCli.addEventListener('change', syncPapelFromChecks);
	if (papelForn) papelForn.addEventListener('change', syncPapelFromChecks);
	var vendSel = root.querySelector('#cli-filter-vendedor');
	if (vendSel) {
		vendSel.addEventListener('change', function () {
			vendedorFilter = vendSel.value;
			redrawTable();
		});
	}
	root.querySelectorAll('.cli-proto-chip').forEach(function (chip) {
		chip.addEventListener('click', function () {
			var id = chip.getAttribute('data-chip');
			if (activeChip === id) {
				activeChip = '';
				root.querySelectorAll('.cli-proto-chip').forEach(function (c) { c.classList.remove('active'); });
			} else {
				activeChip = id;
				root.querySelectorAll('.cli-proto-chip').forEach(function (c) { c.classList.remove('active'); });
				chip.classList.add('active');
			}
			redrawTable();
		});
	});

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			updateSearchModeUi();
			redrawTable();
		});
		updateSearchModeUi();
	}

	tbody.addEventListener('click', function (e) {
		var tr = e.target.closest('tr[data-cli-edit-url]');
		if (!tr) return;
		if (e.target.closest('a, button, input, select, textarea')) return;
		var u = tr.getAttribute('data-cli-edit-url');
		if (u) window.location.href = u;
	});

	redrawTable();
})();
</script>
<?php $this->end(); ?>
