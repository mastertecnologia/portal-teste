<?php
use Cake\Routing\Router;

$this->Breadcrumbs->add('Produtos', ['controller' => 'Produtos', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Estoque', [], ['class' => 'breadcrumb-item active']);

$queryAtual = [
	'sCodProduto' => $sCodProduto,
	'sDescricao' => $sDescricao,
	'apenasComSaldo' => $bApenasComSaldo ? 1 : 0,
];
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
}
.est-btn:hover{background:rgba(255,255,255,.05);}
.est-btn.primary{background:var(--est-teal);border-color:var(--est-teal);color:#fff;}
.est-btn.warn{border-color:var(--est-yellow);color:var(--est-yellow);}
.est-filters{display:grid;grid-template-columns:280px 1fr auto;gap:10px;align-items:end;}
.est-field label{display:block;font-size:.72rem;color:var(--est-muted);margin-bottom:4px;}
.est-input{width:100%;background:#0b1220;border:1px solid var(--est-border);border-radius:8px;color:var(--est-text);padding:8px 10px;}
.est-input:focus{outline:none;border-color:var(--est-teal);}
.est-table-wrap{flex:1;min-height:0;overflow:auto;border:1px solid var(--est-border);border-radius:10px;}
.est-table-wrap::-webkit-scrollbar{width:8px;height:8px;}
.est-table-wrap::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);border-radius:8px;}
.est-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.est-table th{position:sticky;top:0;background:var(--est-surface2);padding:10px;text-transform:uppercase;font-size:.66rem;letter-spacing:.06em;color:var(--est-muted);border-bottom:1px solid var(--est-border);}
.est-table td{padding:9px 10px;border-bottom:1px solid var(--est-border);}
.est-table tr:hover td{background:rgba(255,255,255,.03);}
.est-num{text-align:right;font-family:monospace;}
.est-empty{padding:30px;text-align:center;color:var(--est-muted);}
.est-footer{display:flex;justify-content:space-between;align-items:center;color:var(--est-muted);font-size:.78rem;}
@media (max-width: 960px){ .est-filters{grid-template-columns:1fr;} }
@media print{
	body *{visibility:hidden;}
	#estoque-printable,#estoque-printable *{visibility:visible;}
	#estoque-printable{position:absolute;left:0;top:0;width:100%;background:#fff;color:#111;}
	.est-actions,.est-filters,.page-titles,.left-sidebar,.pgm-sidebar-footer,.pgm-sidebar-brand,.pgm-sidebar-workspace,.pgm-sb-search-block{display:none!important;}
}
</style>

<div class="est-root" id="estoque-printable">
	<div class="est-top">
		<div class="est-title">
			<h1>Produtos em Estoque</h1>
			<p>Subtelas: visualização, impressão e geração de PDF (atual e completo).</p>
		</div>
		<div class="est-actions">
			<a href="#" id="btn-imprimir" class="est-btn warn">Imprimir</a>
			<?= $this->Html->link('PDF atual', ['controller' => 'Produtos', 'action' => 'estoquePdf', $bApenasComSaldo ? 't' : 'f', '?' => $queryAtual], ['class' => 'est-btn']) ?>
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
					<th>Código</th>
					<th>Descrição</th>
					<th class="est-num">Quantidade Atual</th>
					<th class="est-num">Preço Custo</th>
					<th class="est-num">Preço Venda</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($produtos as $reg) : ?>
				<tr>
					<td><?= h($reg->sCodProduto) ?></td>
					<td><?= h($reg->sDescProduto) ?></td>
					<td class="est-num"><?= h($reg->nQtdeAtual) ?></td>
					<td class="est-num"><?= number_format((float)$reg->nPrecoCusto, 2, ',', '.') ?></td>
					<td class="est-num"><?= number_format((float)$reg->nPrecoVenda, 2, ',', '.') ?></td>
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
		<div>Total listado: <strong><?= (int)count($produtos ?? []) ?></strong></div>
	</div>
</div>

<script>
$(document).on('click', '#btn-imprimir', function(e) {
	e.preventDefault();
	window.print();
});
</script>