<?php
/**
 * Contas a Pagar — mockup pg-contas-pagar.
 *
 * @var \App\View\AppView $this
 * @var array{pend:int,paga:int,vencida:int,total_valor:float} $cpKpi
 * @var array<int,array<string,mixed>> $cpItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Financeiro')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💳 <?= h(__('Contas a Pagar')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d lançamentos · total %s no escopo')), count($cpItems), $H->brl((float)$cpKpi['total_valor'])) ?>
		</div>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'FinanceiroPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Pendentes')) ?></div><div class="stat-n"><?= (int)$cpKpi['pend'] ?></div></div>
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Pagas')) ?></div><div class="stat-n"><?= (int)$cpKpi['paga'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Vencidas')) ?></div><div class="stat-n"><?= (int)$cpKpi['vencida'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Tipo')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Vencimento')) ?></th>
					<th><?= h(__('Status')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($cpItems === []) : ?>
					<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum lançamento no escopo.')) ?></td></tr>
				<?php else : foreach ($cpItems as $it) :
					$state = (string)$it['status'];
					$badge = $state === 'paga' ? 'paga' : ($state === 'vencida' ? 'vencida' : 'pendente');
					$lbl = $state === 'paga' ? __('Paga') : ($state === 'vencida' ? __('Vencida') : __('Pendente'));
				?>
					<tr>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 90, ['ellipsis' => '…'])) ?></td>
						<td class="mu"><?= h((string)$it['tipo']) ?></td>
						<td class="r" style="color:<?= !empty($it['is_despesa']) ? '#7A1822' : 'var(--teal-dark)' ?>;"><strong><?= h($H->brl((float)$it['valor'])) ?></strong></td>
						<td class="mu"><?= h($H->dt($it['vencimento'])) ?></td>
						<td><?= $H->badge($lbl, $badge) ?></td>
						<td class="r"><?= $this->Html->link(__('Ver'), ['controller' => 'Financeiro', 'action' => 'index'], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
