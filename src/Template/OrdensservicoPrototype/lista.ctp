<?php
/**
 * Lista de Ordens de Serviço — mockup pg-os-lista.
 *
 * @var \App\View\AppView $this
 * @var array{abertas:int,em_execucao:int,aguardando:int,concluidas:int,total:int} $osCounts
 * @var float $osTotalValor
 * @var array<int,array<string,mixed>> $osItems
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Operações')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🛠 <?= h(__('Ordens de Serviço')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d OS · total R$ %s no escopo da empresa ativa')), (int)$osCounts['total'], number_format((float)$osTotalValor, 2, ',', '.')) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('🗂 ' . __('Kanban'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'kanban'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Nova OS'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$osCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Abertas')) ?></div><div class="stat-n"><?= (int)$osCounts['abertas'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Em execução')) ?></div><div class="stat-n"><?= (int)$osCounts['em_execucao'] ?></div></div>
	<div class="stat" style="--sc:var(--purple);"><div class="stat-l"><?= h(__('Aguardando')) ?></div><div class="stat-n"><?= (int)$osCounts['aguardando'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Concluídas')) ?></div><div class="stat-n"><?= (int)$osCounts['concluidas'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<input type="text" placeholder="🔍 <?= h(__('Buscar OS, cliente, descrição...')) ?>" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:13px;"/>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Número')) ?></th>
					<th><?= h(__('Cliente')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Abertura')) ?></th>
					<th><?= h(__('Situação')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($osItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhuma OS no escopo.')) ?></td></tr>
				<?php else : foreach ($osItems as $it) :
					$st = strtolower((string)$it['situacao']);
					$badge = 'b-arq';
					if (strpos($st, 'concl') !== false || strpos($st, 'fech') !== false) {
						$badge = 'b-paga';
					} elseif (strpos($st, 'execu') !== false || strpos($st, 'andam') !== false) {
						$badge = 'b-aprov';
					} elseif (strpos($st, 'aguard') !== false || strpos($st, 'aprov') !== false) {
						$badge = 'b-pendente';
					}
				?>
					<tr>
						<td><strong><?= sprintf('OS-%05d', (int)$it['id']) ?></strong></td>
						<td><?= h((string)$it['cliente']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 80, ['ellipsis' => '…'])) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['valor'])) ?></strong></td>
						<td class="mu"><?= h($H->dt($it['data'])) ?></td>
						<td><?= $H->badge((string)$it['situacao'] !== '' ? (string)$it['situacao'] : '—', str_replace('b-', '', $badge)) ?></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'Ordensservico', 'action' => 'view', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
