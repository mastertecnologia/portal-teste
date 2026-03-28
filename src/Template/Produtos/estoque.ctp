<?php
use Cake\Routing\Router;

$this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Estoque', [], ['class' => 'breadcrumb-item active']);

$queryAtual = [
	'sCodProduto' => $sCodProduto,
	'sDescricao' => $sDescricao,
	'apenasComSaldo' => $bApenasComSaldo ? 1 : 0,
];
$toggleTarget = $bApenasComSaldo ? 'todos' : 'estoque';
$toggleLabel = $bApenasComSaldo ? 'Exibir todos' : 'Apenas com estoque';
$toggleClass = $bApenasComSaldo ? 'est-btn' : 'est-btn primary';
?>
<style>
.est-root{
	--est-bg:#f9f9f8; --est-surface:#ffffff; --est-surface2:#f9f9f8; --est-border:#e5e4e0;
	--est-text:#1a1a18; --est-muted:#6b6a65; --est-teal:#00c08b; --est-teal-l:#008f68;
	--est-yellow:#d29922;
	background:var(--est-surface); color:var(--est-text); border:1px solid var(--est-border); border-radius:12px;
	padding:18px; display:flex; flex-direction:column; gap:14px; min-height:calc(100vh - 170px);
	box-shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
}
.est-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-end;}
.est-title h1{font-size:1.4rem;margin:0;color:var(--est-teal);font-weight:700;}
.est-title p{margin:2px 0 0;color:var(--est-muted);font-size:.82rem;}
.est-actions{display:flex;gap:8px;flex-wrap:wrap;}
.est-btn{
	border-radius:8px; border:1px solid var(--est-border); background:transparent; color:var(--est-text);
	padding:8px 12px; font-size:.8rem; font-weight:600; text-decoration:none !important; display:inline-flex; align-items:center; gap:6px;
	cursor:pointer;
}
.est-btn:hover{background:var(--est-surface2);}
.est-btn.primary{background:var(--est-teal);border-color:var(--est-teal);color:#fff;}
.est-btn.warn{border-color:var(--est-yellow);color:var(--est-yellow);}
.est-filters{display:grid;grid-template-columns:minmax(220px,280px) minmax(0,1fr) auto;gap:12px;align-items:end;}
.est-field label{display:block;font-size:.72rem;color:var(--est-muted);margin-bottom:4px;}
.est-label-spacer{display:block;font-size:.72rem;margin-bottom:4px;visibility:hidden;line-height:1;}
.est-input{width:100%;background:var(--est-surface2);border:1px solid var(--est-border);border-radius:8px;color:var(--est-text);padding:8px 10px;}
.est-input:focus{outline:none;border-color:var(--est-teal);}
.est-field--codigo .bootstrap-select{width:100% !important;}
.est-field--codigo .bootstrap-select > .dropdown-toggle,
.est-field--codigo .bootstrap-select > .dropdown-toggle.btn-light,
.est-field--codigo .bootstrap-select > .dropdown-toggle.btn-default,
.est-field--codigo .bootstrap-select > .dropdown-toggle:hover,
.est-field--codigo .bootstrap-select > .dropdown-toggle:focus,
.est-field--codigo .bootstrap-select.show > .dropdown-toggle,
.est-field--codigo .bootstrap-select > .dropdown-toggle:active{
	background:var(--est-surface2) !important;
	border:1px solid var(--est-border) !important;
	color:var(--est-text) !important;
	box-shadow:none !important;
}
.est-field--codigo .bootstrap-select > .dropdown-toggle.bs-placeholder{color:var(--est-muted) !important;}
.est-field--codigo .bootstrap-select .filter-option-inner-inner{color:var(--est-text) !important;}
.est-field--codigo .bootstrap-select .dropdown-menu{
	background:#fff !important;
	border:1px solid var(--est-border) !important;
}
.est-field--codigo .bootstrap-select .dropdown-menu .dropdown-item{color:var(--est-text) !important;}
.est-field--codigo .bootstrap-select .dropdown-menu .dropdown-item:hover,
.est-field--codigo .bootstrap-select .dropdown-menu .dropdown-item.active{
	background:rgba(0,192,139,.12) !important;
	color:var(--est-text) !important;
}
.est-actions-secondary{display:flex;gap:8px;flex-wrap:wrap;}
.est-results{display:flex;flex:1;flex-direction:column;gap:12px;min-height:0;}
.est-table-wrap{flex:1;min-height:0;overflow:auto;border:1px solid var(--est-border);border-radius:10px;}
.est-table-wrap::-webkit-scrollbar{width:8px;height:8px;}
.est-table-wrap::-webkit-scrollbar-thumb{background:rgba(26,26,24,.2);border-radius:8px;}
.est-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.est-table th{position:sticky;top:0;background:var(--est-surface2);padding:10px;text-transform:uppercase;font-size:.66rem;letter-spacing:.06em;color:var(--est-muted);border-bottom:1px solid var(--est-border);}
.est-table td{padding:9px 10px;border-bottom:1px solid var(--est-border);}
.est-table tr:hover td{background:rgba(249,249,248,.9);}
.est-table tr.est-row-checked td{background:rgba(0,192,139,.1);}
.est-table .est-col-check{width:36px;text-align:center;}
.est-num{text-align:right;font-family:monospace;}
.est-empty{padding:30px;text-align:center;color:var(--est-muted);}
.est-footer{display:flex;justify-content:space-between;align-items:center;color:var(--est-muted);font-size:.78rem;}
input.est-check{accent-color:var(--est-teal);cursor:pointer;}
body.est-print-selected .est-row-not-selected{display:none;}
@media (max-width: 960px){ .est-filters{grid-template-columns:1fr;} }
@media print{
	body *{visibility:hidden;}
	#estoque-printable,#estoque-printable *{visibility:visible;}
	#estoque-printable{position:absolute;left:0;top:0;width:100%;background:#fff;color:#111;}
	.est-actions,.est-actions-secondary,.est-filters,.page-titles,.left-sidebar,.pgm-sidebar-footer,.pgm-sidebar-brand,.pgm-sidebar-workspace,.pgm-sb-search-block{display:none!important;}
	.est-table .est-col-check{display:none;}
}
</style>

<div class="est-root" id="estoque-printable">
	<div class="est-top">
		<div class="est-title">
			<h1>Produtos em Estoque</h1>
		</div>
		<div class="est-actions">
			<a href="#" id="btn-imprimir" class="est-btn warn">Imprimir</a>
			<a href="#" id="btn-pdf" class="est-btn">PDF</a>
			<a
				href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $toggleTarget === 'estoque' ? 't' : 'f', '?' => $queryAtual]) ?>"
				class="<?= $toggleClass ?>"
				id="btn-toggle-estoque"
				data-target="<?= h($toggleTarget) ?>"
			><?= h($toggleLabel) ?></a>
		</div>
	</div>

	<?= $this->Form->create(null, [
		'type' => 'get',
		'id' => 'estoque-filter-form',
		'url' => ['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f'],
	]) ?>
	<div class="est-filters">
		<div class="est-field est-field--codigo">
			<label for="sCodProduto">Filtro por código</label>
			<?= $this->Form->control('sCodProduto', [
				'id' => 'sCodProduto',
				'empty' => 'Todos',
				'class' => 'form-control selectpicker est-input',
				'data-live-search' => true,
				'options' => $produtosOpt,
				'value' => $sCodProduto,
				'label' => false
			]) ?>
		</div>
		<div class="est-field">
			<label for="sDescricao">Filtro por descrição</label>
			<?= $this->Form->control('sDescricao', ['id' => 'sDescricao', 'class' => 'est-input', 'value' => $sDescricao, 'label' => false, 'autocomplete' => 'off']) ?>
		</div>
		<div class="est-field est-field--actions">
			<label class="est-label-spacer" aria-hidden="true">&nbsp;</label>
			<div class="est-actions">
			<?= $this->Form->button('Buscar', ['class' => 'est-btn primary', 'type' => 'button', 'id' => 'btn-buscar']) ?>
			<a class="est-btn" href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f']) ?>" id="btn-limpar">Limpar</a>
			</div>
		</div>
	</div>
	<?= $this->Form->end() ?>

	<div class="est-results" id="est-results">
		<?= $this->element('Produtos/estoque_lista', ['produtos' => $produtos, 'bApenasComSaldo' => $bApenasComSaldo]) ?>
	</div>
</div>

<script>
(function() {
	var estoqueUrlComSaldo = "<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', 't']) ?>";
	var estoqueUrlTodos = "<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', 'f']) ?>";
	var pdfUrlComSaldo = "<?= Router::url(['controller' => 'Produtos', 'action' => 'estoquePdf', 't']) ?>";
	var pdfUrlTodos = "<?= Router::url(['controller' => 'Produtos', 'action' => 'estoquePdf', 'f']) ?>";
	var apenasComSaldoAtual = <?= $bApenasComSaldo ? 'true' : 'false' ?>;
	var basePdf = apenasComSaldoAtual ? pdfUrlComSaldo : pdfUrlTodos;
	var $form = $('#estoque-filter-form');
	var $results = $('#est-results');
	var descTimer;
	var activeRequest = null;

	function getFilterQueryString() {
		if (!$form.length) {
			return '';
		}
		return $form.serialize();
	}

	function selectedCodes() {
		var codes = [];
		$('.est-check-item:checked').each(function() {
			var c = $(this).data('codigo');
			if (c !== undefined && c !== null && String(c).trim() !== '') {
				codes.push(String(c).trim());
			}
		});
		return codes;
	}

	function refreshSelectedState() {
		var count = selectedCodes().length;
		$('#est-selected-count').text(count);
		$('.est-row').each(function() {
			var code = String($(this).data('codigo'));
			var checked = $('.est-check-item[data-codigo="' + code.replace(/"/g, '\\"') + '"]').is(':checked');
			$(this).toggleClass('est-row-checked', checked);
			$(this).toggleClass('est-row-not-selected', !checked);
		});
	}

	function openPdfSelected(codes) {
		var queryAtual = getFilterQueryString();
		var qs = queryAtual ? (queryAtual + '&') : '';
		qs += 'escopo=selecionados&codigos=' + encodeURIComponent(codes.join(','));
		window.open(basePdf + '?' + qs, '_blank');
	}

	function submitFiltersAjax() {
		if (!$form.length || !$results.length) {
			return;
		}

		var query = getFilterQueryString();
		var url = $form.attr('action');
		if (!url) {
			return;
		}

		if (activeRequest && activeRequest.readyState !== 4) {
			activeRequest.abort();
		}

		activeRequest = $.get(url, query + (query ? '&' : '') + 'ajax=1')
			.done(function(html) {
				$results.html(html);
				$('#est-check-all').prop('checked', false);
				refreshSelectedState();
				var newUrl = url + (query ? ('?' + query) : '');
				window.history.replaceState({}, '', newUrl);
			});
	}

	function atualizarBotaoModo() {
		var $btn = $('#btn-toggle-estoque');
		if (!$btn.length) {
			return;
		}

		if (apenasComSaldoAtual) {
			$btn.text('Exibir todos');
			$btn.removeClass('primary');
			$btn.attr('data-target', 'todos');
			$btn.attr('href', estoqueUrlTodos);
			return;
		}

		$btn.text('Apenas com estoque');
		$btn.addClass('primary');
		$btn.attr('data-target', 'estoque');
		$btn.attr('href', estoqueUrlComSaldo);
	}

	$(document).on('change', '#est-check-all', function() {
		$('.est-check-item').prop('checked', $(this).is(':checked'));
		refreshSelectedState();
	});

	$(document).on('change', '.est-check-item', function() {
		var total = $('.est-check-item').length;
		var checked = $('.est-check-item:checked').length;
		$('#est-check-all').prop('checked', total > 0 && total === checked);
		refreshSelectedState();
	});

	$(document).on('click', '#btn-imprimir', function(e) {
		e.preventDefault();
		var codes = selectedCodes();
		if (!codes.length) {
			alert('Selecione ao menos um produto para imprimir.');
			return;
		}
		$('body').addClass('est-print-selected');
		window.print();
		setTimeout(function() { $('body').removeClass('est-print-selected'); }, 200);
	});

	$(document).on('click', '#btn-pdf', function(e) {
		e.preventDefault();
		var codes = selectedCodes();
		if (!codes.length) {
			alert('Selecione ao menos um produto para gerar PDF.');
			return;
		}
		openPdfSelected(codes);
	});

	refreshSelectedState();

	$(document).on('submit', '#estoque-filter-form', function(e) {
		e.preventDefault();
		submitFiltersAjax();
	});

	$(document).on('click', '#btn-buscar', function(e) {
		e.preventDefault();
		submitFiltersAjax();
	});

	$(document).on('keydown', '#sDescricao', function(e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			submitFiltersAjax();
		}
	});

	$(document).on('click', '#btn-limpar', function(e) {
		e.preventDefault();
		$('#sDescricao').val('');
		$('#sCodProduto').val('');
		if ($.fn.selectpicker) {
			$('#sCodProduto').selectpicker('refresh');
		}
		submitFiltersAjax();
	});

	$(document).on('click', '#btn-toggle-estoque', function(e) {
		e.preventDefault();
		var target = String($(this).attr('data-target') || '');
		apenasComSaldoAtual = (target === 'estoque');
		$form.attr('action', apenasComSaldoAtual ? estoqueUrlComSaldo : estoqueUrlTodos);
		basePdf = apenasComSaldoAtual ? pdfUrlComSaldo : pdfUrlTodos;
		atualizarBotaoModo();
		submitFiltersAjax();
	});

	$(document).on('change changed.bs.select', '#sCodProduto', function() {
		submitFiltersAjax();
	});

	$(document).on('input', '#sDescricao', function() {
		clearTimeout(descTimer);
		descTimer = setTimeout(function() {
			submitFiltersAjax();
		}, 400);
	});

	atualizarBotaoModo();
})();
</script>