<?php
/**
 * Lista de produtos — mockup pg-produtos.
 *
 * @var \App\View\AppView $this
 * @var array{total:int,ativos:int,inativos:int,sem_estoque:int} $prodCounts
 * @var float $prodValorTotal
 * @var array<int,array<string,mixed>> $prodItems
 */
$H = $this->ErpPrototype;
$tipoLabels = [
	'prod' => __('Produto'),
	'serv' => __('Serviço'),
	'lic' => __('Licença'),
	'loc' => __('Locação'),
];
$f = (array)($prodFiltros ?? ['q' => '', 'tipo' => '', 'ativo' => '']);
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Cadastros'),
	'title' => __('Produtos'),
	'subtitle' => sprintf(__('%d itens · catálogo total %s'), (int)$prodCounts['total'], $H->brl((float)$prodValorTotal)),
	'actions' => [
		['label' => __('Módulo clássico'), 'url' => ['controller' => 'Produtos', 'action' => 'index'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Exportar CSV'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'exportCsv'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Estoque ERP'), 'url' => ['controller' => 'Produtos', 'action' => 'estoque'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Estoque'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Tabela de preços'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Novo produto'), 'url' => ['controller' => 'Produtos', 'action' => 'add'], 'class' => 'btn btn-primary'],
	],
]) ?>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$prodCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativos')) ?></div><div class="stat-n"><?= (int)$prodCounts['ativos'] ?></div></div>
	<div class="stat" style="--sc:var(--gray-400);"><div class="stat-l"><?= h(__('Inativos')) ?></div><div class="stat-n"><?= (int)$prodCounts['inativos'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Sem estoque')) ?></div><div class="stat-n"><?= (int)$prodCounts['sem_estoque'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:240px;">
				<label><?= h(__('Buscar')) ?></label>
				<input type="search" name="q" value="<?= h((string)$f['q']) ?>" placeholder="🔍 <?= h(__('Código, descrição, NCM...')) ?>">
			</div>
			<div class="field" style="flex:0 0 140px;">
				<label><?= h(__('Tipo')) ?></label>
				<select name="tipo">
					<option value=""><?= h(__('Todos')) ?></option>
					<?php foreach ($tipoLabels as $k => $l) : ?>
						<option value="<?= h($k) ?>"<?= (string)$f['tipo'] === $k ? ' selected' : '' ?>><?= h($l) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 130px;">
				<label><?= h(__('Status')) ?></label>
				<select name="ativo">
					<option value=""><?= h(__('Todos')) ?></option>
					<option value="1"<?= (string)$f['ativo'] === '1' ? ' selected' : '' ?>>Ativo</option>
					<option value="0"<?= (string)$f['ativo'] === '0' ? ' selected' : '' ?>>Inativo</option>
				</select>
			</div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'ProdutosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th><?= h(__('Unidade')) ?></th>
					<th class="r"><?= h(__('Preço')) ?></th>
					<th class="r"><?= h(__('Estoque')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($prodItems === []) : ?>
					<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum produto no escopo.')) ?></td></tr>
				<?php else : foreach ($prodItems as $it) :
					$tipo = (string)$it['tipo'];
					$tipoLbl = (string)($tipoLabels[$tipo] ?? ($tipo !== '' ? $tipo : '—'));
					$badge = 'b-arq';
					if ($tipo === 'prod') {
						$badge = 'b-prod';
					} elseif ($tipo === 'serv') {
						$badge = 'b-serv';
					} elseif ($tipo === 'lic') {
						$badge = 'b-lic';
					} elseif ($tipo === 'loc') {
						$badge = 'b-loc';
					}
					$est = (float)$it['estoque'];
					$estCol = $est <= 0 ? '#7A1822' : ($est < 5 ? '#8A4D02' : 'var(--teal-dark)');
				?>
					<?php
					$detUrl = $this->Url->build(['controller' => 'Produtos', 'action' => 'edit', (int)$it['id']]);
					?>
					<tr data-pgm-row-href="<?= h($detUrl) ?>" tabindex="0">
						<td style="font-family:monospace;font-size:11px;font-weight:600;"><?= h((string)$it['codigo']) ?></td>
						<td>
							<input type="text" data-prod-edit="descricao" data-prod-id="<?= (int)$it['id'] ?>" value="<?= h((string)$it['descricao']) ?>" style="border:1px dashed transparent;background:transparent;font-size:12px;padding:2px 4px;border-radius:3px;width:100%;min-width:160px;">
						</td>
						<td><span class="badge <?= h($badge) ?>"><?= h($tipoLbl) ?></span></td>
						<td class="mu"><?= h((string)$it['unidade']) ?></td>
						<td class="r">
							<input type="text" data-prod-edit="vlunitario" data-prod-id="<?= (int)$it['id'] ?>" value="<?= h(number_format((float)$it['preco'], 2, ',', '.')) ?>" style="border:1px dashed transparent;background:transparent;font-size:11px;padding:2px 4px;border-radius:3px;width:88px;text-align:right;">
						</td>
						<td class="r" style="color:<?= h($estCol) ?>;font-weight:600;"><?= number_format($est, 2, ',', '.') ?></td>
						<td><?= $H->badge($it['ativo'] ? __('Ativo') : __('Inativo'), $it['ativo'] ? 'paga' : 'arq') ?></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'Produtos', 'action' => 'edit', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	💡 <?= h(__('Edite descrição e preço na tabela — clique no campo, altere e sair (Tab ou clique fora).')) ?>
</div>

<?php $this->start('script'); ?>
<script>
(function () {
	var csrf = <?= json_encode((string)$this->request->getAttribute('csrfToken')) ?>;
	var url = <?= json_encode($this->Url->build(['controller' => 'ProdutosPrototype', 'action' => 'apiAtualizarCampo'])) ?>;
	document.querySelectorAll('[data-prod-edit]').forEach(function (el) {
		var orig = el.value;
		el.addEventListener('focus', function () {
			el.style.borderColor = '#1D9E75';
			el.style.background = '#fff';
		});
		el.addEventListener('blur', function () {
			el.style.borderColor = 'transparent';
			el.style.background = 'transparent';
			var v = el.value.trim();
			if (v === orig) return;
			var fd = new FormData();
			fd.append('_csrfToken', csrf);
			fd.append('produto_id', el.getAttribute('data-prod-id'));
			fd.append('campo', el.getAttribute('data-prod-edit'));
			fd.append('valor', v);
			fetch(url, {method: 'POST', body: fd, credentials: 'same-origin', headers: {'X-CSRF-Token': csrf}})
				.then(function (r) { return r.json(); })
				.then(function (data) {
					if (data.ok) {
						el.value = data.valor || v;
						orig = el.value;
						el.style.background = '#E1F5EE';
						setTimeout(function () { el.style.background = 'transparent'; }, 800);
					} else {
						el.value = orig;
						alert(data.error || 'Falha ao salvar');
					}
				});
		});
	});
})();
</script>
<?php $this->end(); ?>
