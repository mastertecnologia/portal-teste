<?php
use Cake\Routing\Router;

$this->Html->css('pgm-estoque', ['block' => 'css_late']);

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
	<header class="est-header">
		<div class="est-header__title">
			<h1>Produtos em Estoque</h1>
			<p>Consulta ao ERP — selecione linhas para imprimir ou gerar PDF.</p>
		</div>
		<div class="est-header__actions">
			<a href="#" id="btn-imprimir" class="est-btn est-btn--outline">Imprimir</a>
			<a href="#" id="btn-pdf" class="est-btn est-btn--secondary">PDF</a>
			<a
				href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $toggleTarget === 'estoque' ? 't' : 'f', '?' => $queryAtual]) ?>"
				class="<?= h($toggleClass) ?>"
				id="btn-toggle-estoque"
				data-target="<?= h($toggleTarget) ?>"
			><?= h($toggleLabel) ?></a>
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
			<div class="est-field">
				<label for="sDescricao">Filtro por descrição</label>
				<?= $this->Form->control('sDescricao', ['id' => 'sDescricao', 'class' => 'est-input', 'value' => $sDescricao, 'label' => false, 'autocomplete' => 'off', 'placeholder' => 'Buscar por texto na descrição']) ?>
			</div>
			<div class="est-field est-field--actions">
				<span class="est-label-spacer" aria-hidden="true">Ações</span>
				<div class="est-filter-actions">
					<?= $this->Form->button('Buscar', ['class' => 'est-btn est-btn--primary', 'type' => 'button', 'id' => 'btn-buscar']) ?>
					<a class="est-btn est-btn--outline" href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f']) ?>" id="btn-limpar">Limpar</a>
				</div>
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
