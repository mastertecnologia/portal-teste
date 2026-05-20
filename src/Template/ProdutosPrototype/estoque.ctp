<?php
/**
 * Estoque — mockup pg-estoque.
 *
 * @var \App\View\AppView $this
 * @var array{baixo:int,zero:int,ok:int} $estKpi
 * @var array<int,array<string,mixed>> $estItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Produtos · Estoque')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📦 <?= h(__('Controle de Estoque')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Saldos atuais por produto · alertas para itens em baixo ou esgotados')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🗒 ' . __('Log de auditoria'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'estoque-log'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📋 ' . __('Inventário'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'inventario'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Pedido de compra'), ['controller' => 'ProdutosPrototype', 'action' => 'view', 'pc-novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('OK em estoque')) ?></div><div class="stat-n"><?= (int)$estKpi['ok'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Estoque baixo')) ?></div><div class="stat-n"><?= (int)$estKpi['baixo'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Sem estoque')) ?></div><div class="stat-n"><?= (int)$estKpi['zero'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Unidade')) ?></th>
					<th class="r"><?= h(__('Saldo')) ?></th>
					<th class="r"><?= h(__('Preço unit.')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($estItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum produto físico no escopo.')) ?></td></tr>
				<?php else : foreach ($estItems as $it) :
					$st = (string)$it['status'];
					$statusLbl = $st === 'no' ? __('Sem estoque') : ($st === 'low' ? __('Baixo') : __('OK'));
					$statusBadge = $st === 'no' ? 'b-vencida' : ($st === 'low' ? 'b-vencendo' : 'b-paga');
					$colorCss = $st === 'no' ? '#7A1822' : ($st === 'low' ? '#8A4D02' : 'var(--teal-dark)');
				?>
					<tr>
						<td style="font-family:monospace;font-size:11px;font-weight:600;"><?= h((string)$it['codigo']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 80, ['ellipsis' => '…'])) ?></td>
						<td class="mu"><?= h((string)$it['unidade']) ?></td>
						<td class="r" style="color:<?= h($colorCss) ?>;font-weight:700;"><?= number_format((float)$it['estoque'], 2, ',', '.') ?></td>
						<td class="r"><?= h($H->brl((float)$it['preco'])) ?></td>
						<td><span class="badge <?= h($statusBadge) ?>"><?= h($statusLbl) ?></span></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'Produtos', 'action' => 'view', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
