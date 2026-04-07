<?php
use Cake\Routing\Router;

$this->append('css_late', $this->element('pgm_premium_css', ['name' => 'pgm-estoque']));

$this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Estoque', [], ['class' => 'breadcrumb-item active']);

$queryAtual = [
	'sCodProduto' => $sCodProduto,
	'sDescricao' => $sDescricao,
	'apenasComSaldo' => $bApenasComSaldo ? 1 : 0,
];
$toggleTarget = $bApenasComSaldo ? 'todos' : 'estoque';
$toggleLabel = $bApenasComSaldo ? 'Exibir todos' : 'Apenas com estoque';
$toggleClass = $bApenasComSaldo ? 'est-btn est-btn--outline' : 'est-btn est-btn--primary';
?>
<div class="est-root" id="estoque-printable">
	<div class="est-topbar">
		<a href="<?= Router::url(['controller' => 'Produtos', 'action' => 'index']) ?>" class="est-back-link est-btn est-btn--outline">
			<i class="fas fa-arrow-left" aria-hidden="true"></i> Voltar aos produtos
		</a>
		<div class="est-view-toggle" role="group" aria-label="Modo de visualização">
			<button type="button" class="est-btn est-btn--primary est-view-btn" data-est-view="list" id="btn-est-view-list">Lista</button>
			<button type="button" class="est-btn est-btn--outline est-view-btn" data-est-view="catalog" id="btn-est-view-catalog">Catálogo</button>
		</div>
	</div>
	<header class="est-header">
		<div class="est-header__title">
			<h1>Produtos em Estoque</h1>
			<p>Consulta ao ERP — selecione linhas para imprimir ou gerar PDF.</p>
		</div>
		<div class="est-header__actions" role="toolbar" aria-label="Impressão e escopo da listagem">
			<div class="est-header__actions-inner">
				<a href="#" id="btn-imprimir" class="est-btn est-btn--outline">Imprimir</a>
				<a href="#" id="btn-pdf" class="est-btn est-btn--secondary">PDF</a>
				<a
					href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $toggleTarget === 'estoque' ? 't' : 'f', '?' => $queryAtual]) ?>"
					class="<?= h($toggleClass) ?>"
					id="btn-toggle-estoque"
					data-target="<?= h($toggleTarget) ?>"
				><?= h($toggleLabel) ?></a>
			</div>
		</div>
	</header>

	<?= $this->Form->create(null, [
		'type' => 'get',
		'id' => 'estoque-filter-form',
		'url' => ['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f'],
	]) ?>
	<div class="est-filters-panel">
		<div class="est-filters-grid">
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
			<div class="est-field est-field--descricao">
				<label for="sDescricao">Filtro por descrição</label>
				<?= $this->Form->control('sDescricao', ['id' => 'sDescricao', 'class' => 'est-input', 'value' => $sDescricao, 'label' => false, 'autocomplete' => 'off', 'placeholder' => 'Buscar por texto na descrição']) ?>
			</div>
			<div class="est-field est-field--actions">
				<span class="est-field__actions-heading">Ações</span>
				<div class="est-filter-actions">
					<?= $this->Form->button('Buscar', ['class' => 'est-btn est-btn--primary', 'type' => 'button', 'id' => 'btn-buscar']) ?>
					<a class="est-btn est-btn--outline" href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f']) ?>" id="btn-limpar">Limpar</a>
				</div>
			</div>
		</div>
	</div>
	<?= $this->Form->end() ?>

	<div class="est-results est-mode-list" id="est-results">
		<?= $this->element('Produtos/estoque_lista', [
			'produtos' => $produtos,
			'bApenasComSaldo' => $bApenasComSaldo,
			'mapCodigoId' => $mapCodigoId ?? [],
			'estoqueReturnUrl' => $estoqueReturnUrl ?? '',
		]) ?>
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
	var VIEW_KEY = 'pgmEstoqueView';

	function applyEstoqueViewMode() {
		if (!$results.length) {
			return;
		}
		var mode = localStorage.getItem(VIEW_KEY) || 'list';
		$results.toggleClass('est-mode-catalog', mode === 'catalog').toggleClass('est-mode-list', mode !== 'catalog');
		$('.est-view-btn').each(function() {
			var v = String($(this).data('est-view') || '');
			var on = v === mode;
			$(this).toggleClass('est-btn--primary', on).toggleClass('est-btn--outline', !on);
		});
		var $cat = $results.find('.est-catalog-view');
		var $tbl = $results.find('.est-table-view');
		if (mode === 'catalog') {
			$cat.removeAttr('hidden').attr('aria-hidden', 'false');
			$tbl.attr('hidden', 'hidden').attr('aria-hidden', 'true');
		} else {
			$tbl.removeAttr('hidden').attr('aria-hidden', 'false');
			$cat.attr('hidden', 'hidden').attr('aria-hidden', 'true');
		}
	}

	function getFilterQueryString() {
		if (!$form.length) {
			return '';
		}
		return $form.serialize();
	}

	function selectedCodes() {
		var codes = [];
		var seen = {};
		$results.find('.est-check-item:checked').each(function() {
			var c = $(this).data('codigo');
			if (c !== undefined && c !== null && String(c).trim() !== '') {
				var k = String(c).trim();
				if (!seen[k]) {
					seen[k] = true;
					codes.push(k);
				}
			}
		});
		return codes;
	}

	function refreshSelectedState() {
		var count = selectedCodes().length;
		$('#est-selected-count').text(count);
		$('.est-row, .est-card').each(function() {
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
				applyEstoqueViewMode();
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
			$btn.removeClass('est-btn--primary').addClass('est-btn--outline');
			$btn.attr('data-target', 'todos');
			$btn.attr('href', estoqueUrlTodos);
			return;
		}

		$btn.text('Apenas com estoque');
		$btn.removeClass('est-btn--outline').addClass('est-btn--primary');
		$btn.attr('data-target', 'estoque');
		$btn.attr('href', estoqueUrlComSaldo);
	}

	$(document).on('change', '#est-check-all', function() {
		$results.find('.est-check-item').prop('checked', $(this).is(':checked'));
		refreshSelectedState();
	});

	$(document).on('change', '.est-check-item', function() {
		var n = $results.find('.est-row').length;
		var checkedU = selectedCodes().length;
		$('#est-check-all').prop('checked', n > 0 && checkedU === n);
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

	$(document).on('click', '.est-view-btn', function() {
		var v = String($(this).data('est-view') || '');
		if (v !== 'list' && v !== 'catalog') {
			return;
		}
		localStorage.setItem(VIEW_KEY, v);
		applyEstoqueViewMode();
	});

	applyEstoqueViewMode();

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
