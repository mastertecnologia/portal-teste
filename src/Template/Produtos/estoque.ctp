<?php
use Cake\Routing\Router;

$this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Estoque', [], ['class' => 'breadcrumb-item active']);

$queryAtual = [
	'sCodProduto' => $sCodProduto,
	'sDescricao' => $sDescricao,
	'apenasComSaldo' => $bApenasComSaldo ? 1 : 0,
];
$queryStringAtual = http_build_query($queryAtual);
?>
<style>
.est-root{
	--est-bg:#0d1117; --est-surface:#161b22; --est-surface2:#1c2230; --est-border:#21262d;
	--est-text:#e6edf3; --est-muted:#8b949e; --est-teal:#1d9e75; --est-teal-l:#5cdbc0;
	--est-yellow:#d29922;
	background:var(--est-bg); color:var(--est-text); border:1px solid var(--est-border); border-radius:12px;
	padding:18px; display:flex; flex-direction:column; gap:14px; min-height:calc(100vh - 170px);
}
.est-top{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-end;}
.est-title h1{font-size:1.4rem;margin:0;color:var(--est-teal-l);font-weight:700;}
.est-title p{margin:2px 0 0;color:var(--est-muted);font-size:.82rem;}
.est-actions{display:flex;gap:8px;flex-wrap:wrap;}
.est-btn{
	border-radius:8px; border:1px solid var(--est-border); background:transparent; color:var(--est-text);
	padding:8px 12px; font-size:.8rem; font-weight:600; text-decoration:none !important; display:inline-flex; align-items:center; gap:6px;
	cursor:pointer;
}
.est-btn:hover{background:rgba(255,255,255,.05);}
.est-btn.primary{background:var(--est-teal);border-color:var(--est-teal);color:#fff;}
.est-btn.warn{border-color:var(--est-yellow);color:var(--est-yellow);}
.est-filters{display:grid;grid-template-columns:280px 1fr auto;gap:10px;align-items:end;}
.est-field label{display:block;font-size:.72rem;color:var(--est-muted);margin-bottom:4px;}
.est-input{width:100%;background:#0b1220;border:1px solid var(--est-border);border-radius:8px;color:var(--est-text);padding:8px 10px;}
.est-input:focus{outline:none;border-color:var(--est-teal);}
.est-hint{font-size:.74rem;color:var(--est-muted);margin-top:2px;}
.est-actions-secondary{display:flex;gap:8px;flex-wrap:wrap;}
.est-table-wrap{flex:1;min-height:0;overflow:auto;border:1px solid var(--est-border);border-radius:10px;}
.est-table-wrap::-webkit-scrollbar{width:8px;height:8px;}
.est-table-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:8px;}
.est-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.est-table th{position:sticky;top:0;background:var(--est-surface2);padding:10px;text-transform:uppercase;font-size:.66rem;letter-spacing:.06em;color:var(--est-muted);border-bottom:1px solid var(--est-border);}
.est-table td{padding:9px 10px;border-bottom:1px solid var(--est-border);}
.est-table tr:hover td{background:rgba(255,255,255,.03);}
.est-table tr.est-row-checked td{background:rgba(29,158,117,.12);}
.est-table .est-col-check{width:36px;text-align:center;}
.est-table .est-col-actions{width:130px;text-align:right;}
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
	.est-table .est-col-actions,.est-table .est-col-check{display:none;}
}
</style>

<div class="est-root" id="estoque-printable">
	<div class="est-top">
		<div class="est-title">
			<h1>Produtos em Estoque</h1>
			<p>Subtelas: visualização, impressão e geração de PDF (parcial, total e por item).</p>
		</div>
		<div class="est-actions">
			<a href="#" id="btn-imprimir-todos" class="est-btn warn">Imprimir listados</a>
			<a href="#" id="btn-imprimir-selecionados" class="est-btn">Imprimir selecionados</a>
			<?= $this->Html->link('PDF atual', ['controller' => 'Produtos', 'action' => 'estoquePdf', $bApenasComSaldo ? 't' : 'f', '?' => $queryAtual], ['class' => 'est-btn']) ?>
			<a href="#" id="btn-pdf-selecionados" class="est-btn">PDF selecionados</a>
			<?= $this->Html->link('PDF completo', ['controller' => 'Produtos', 'action' => 'estoquePdf', 'f', '?' => ['sCodProduto' => null, 'sDescricao' => null, 'apenasComSaldo' => 0]], ['class' => 'est-btn']) ?>
			<?php if ($bApenasComSaldo) : ?>
				<?= $this->Html->link('Exibir todos', ['controller' => 'Produtos', 'action' => 'estoque', 'f', '?' => $queryAtual], ['class' => 'est-btn']) ?>
			<?php else : ?>
				<?= $this->Html->link('Apenas com estoque', ['controller' => 'Produtos', 'action' => 'estoque', 't', '?' => $queryAtual], ['class' => 'est-btn primary']) ?>
			<?php endif; ?>
		</div>
	</div>

	<?= $this->Form->create(null, ['type' => 'get']) ?>
	<div class="est-filters">
		<div class="est-field">
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
			<?= $this->Form->control('sDescricao', ['id' => 'sDescricao', 'class' => 'est-input', 'value' => $sDescricao, 'label' => false]) ?>
			<div class="est-hint"><?= h($regraBuscaDescricao ?? '') ?></div>
		</div>
		<div class="est-actions">
			<?= $this->Form->button('Buscar', ['class' => 'est-btn primary']) ?>
			<a class="est-btn" href="<?= Router::url(['controller' => 'Produtos', 'action' => 'estoque', $bApenasComSaldo ? 't' : 'f']) ?>">Limpar</a>
		</div>
	</div>
	<?= $this->Form->end() ?>

	<div class="est-table-wrap">
		<?php if (!empty($produtos)) : ?>
		<table class="est-table">
			<thead>
				<tr>
					<th class="est-col-check"><input type="checkbox" id="est-check-all" class="est-check" /></th>
					<th>Código</th>
					<th>Descrição</th>
					<th class="est-num">Quantidade Atual</th>
					<th class="est-num">Preço Custo</th>
					<th class="est-num">Preço Venda</th>
					<th class="est-col-actions">Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($produtos as $reg) : ?>
				<tr class="est-row" data-codigo="<?= h($reg->sCodProduto) ?>">
					<td class="est-col-check"><input type="checkbox" class="est-check est-check-item" data-codigo="<?= h($reg->sCodProduto) ?>" /></td>
					<td><?= h($reg->sCodProduto) ?></td>
					<td><?= h($reg->sDescProduto) ?></td>
					<td class="est-num"><?= h($reg->nQtdeAtual) ?></td>
					<td class="est-num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></td>
					<td class="est-num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></td>
					<td class="est-col-actions">
						<?= $this->Html->link(
							'PDF item',
							[
								'controller' => 'Produtos',
								'action' => 'estoquePdf',
								$bApenasComSaldo ? 't' : 'f',
								'?' => array_merge($queryAtual, ['escopo' => 'item', 'codigos' => (string)$reg->sCodProduto])
							],
							['class' => 'est-btn', 'style' => 'padding:5px 9px;font-size:.72rem;']
						) ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<div class="est-empty">Nenhum produto encontrado com os filtros atuais.</div>
		<?php endif; ?>
	</div>

	<div class="est-footer">
		<div><?= $bApenasComSaldo ? 'Modo: apenas produtos com estoque.' : 'Modo: todos os produtos.' ?></div>
		<div class="est-actions-secondary">
			<span>Total listado: <strong><?= (int)count($produtos ?? []) ?></strong></span>
			<span>Selecionados: <strong id="est-selected-count">0</strong></span>
		</div>
	</div>
</div>

<script>
(function() {
	var basePdf = "<?= Router::url(['controller' => 'Produtos', 'action' => 'estoquePdf', $bApenasComSaldo ? 't' : 'f']) ?>";
	var queryAtual = "<?= h($queryStringAtual) ?>";

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
		var qs = queryAtual ? (queryAtual + '&') : '';
		qs += 'escopo=selecionados&codigos=' + encodeURIComponent(codes.join(','));
		window.open(basePdf + '?' + qs, '_blank');
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

	$(document).on('click', '#btn-imprimir-todos', function(e) {
		e.preventDefault();
		$('body').removeClass('est-print-selected');
		window.print();
	});

	$(document).on('click', '#btn-imprimir-selecionados', function(e) {
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

	$(document).on('click', '#btn-pdf-selecionados', function(e) {
		e.preventDefault();
		var codes = selectedCodes();
		if (!codes.length) {
			alert('Selecione ao menos um produto para gerar PDF.');
			return;
		}
		openPdfSelected(codes);
	});

	refreshSelectedState();
})();
</script>