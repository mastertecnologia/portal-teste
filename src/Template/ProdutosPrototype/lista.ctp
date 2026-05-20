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
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Cadastros')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📦 <?= h(__('Produtos')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d itens · catálogo total %s')), (int)$prodCounts['total'], $H->brl((float)$prodValorTotal)) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📦 ' . __('Estoque'), ['controller' => 'ProdutosPrototype', 'action' => 'estoque'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('💲 ' . __('Tabela de preços'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo produto'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$prodCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Ativos')) ?></div><div class="stat-n"><?= (int)$prodCounts['ativos'] ?></div></div>
	<div class="stat" style="--sc:var(--gray-400);"><div class="stat-l"><?= h(__('Inativos')) ?></div><div class="stat-n"><?= (int)$prodCounts['inativos'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Sem estoque')) ?></div><div class="stat-n"><?= (int)$prodCounts['sem_estoque'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<input type="text" placeholder="🔍 <?= h(__('Buscar código, descrição, NCM...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
	</div>
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
					<tr>
						<td style="font-family:monospace;font-size:11px;font-weight:600;"><?= h((string)$it['codigo']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 90, ['ellipsis' => '…'])) ?></td>
						<td><span class="badge <?= h($badge) ?>"><?= h($tipoLbl) ?></span></td>
						<td class="mu"><?= h((string)$it['unidade']) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['preco'])) ?></strong></td>
						<td class="r" style="color:<?= h($estCol) ?>;font-weight:600;"><?= number_format($est, 2, ',', '.') ?></td>
						<td><?= $H->badge($it['ativo'] ? __('Ativo') : __('Inativo'), $it['ativo'] ? 'paga' : 'arq') ?></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'Produtos', 'action' => 'view', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
