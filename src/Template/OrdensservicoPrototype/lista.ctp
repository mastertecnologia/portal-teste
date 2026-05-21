<?php
/**
 * Lista de Ordens de Serviço — mockup pg-os-lista.
 *
 * @var \App\View\AppView $this
 * @var array{abertas:int,em_execucao:int,aguardando:int,concluidas:int,total:int} $osCounts
 * @var float $osTotalValor
 * @var array<int,array<string,mixed>> $osItems
 * @var array<int,string> $osClientesOptions
 * @var array{situacao:string,cliente:string,de:string,ate:string} $osFiltros
 */
$H = $this->ErpPrototype;
$f = (array)($osFiltros ?? ['situacao' => '', 'cliente' => '', 'de' => '', 'ate' => '']);
$clientesOpt = (array)($osClientesOptions ?? []);
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Operações'),
	'title' => __('Ordens de Serviço'),
	'subtitle' => sprintf(__('%d OS · total R$ %s no escopo da empresa ativa'), (int)$osCounts['total'], number_format((float)$osTotalValor, 2, ',', '.')),
	'actions' => [
		['label' => __('Exportar CSV'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'exportCsv', '?' => array_filter($f)], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Kanban'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'kanban'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => '+ ' . __('Nova OS'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura'], 'class' => 'btn btn-primary'],
	],
]) ?>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$osCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Abertas')) ?></div><div class="stat-n"><?= (int)$osCounts['abertas'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Em execução')) ?></div><div class="stat-n"><?= (int)$osCounts['em_execucao'] ?></div></div>
	<div class="stat" style="--sc:var(--purple);"><div class="stat-l"><?= h(__('Aguardando')) ?></div><div class="stat-n"><?= (int)$osCounts['aguardando'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Concluídas')) ?></div><div class="stat-n"><?= (int)$osCounts['concluidas'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:180px;">
				<label><?= h(__('Cliente')) ?></label>
				<select name="cliente">
					<option value=""><?= h(__('Todos')) ?></option>
					<?php foreach ($clientesOpt as $cid => $cnome) : ?>
						<option value="<?= (int)$cid ?>"<?= (string)$f['cliente'] === (string)$cid ? ' selected' : '' ?>><?= h(\Cake\Utility\Text::truncate((string)$cnome, 40, ['ellipsis' => '…'])) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 160px;">
				<label><?= h(__('Situação')) ?></label>
				<select name="situacao">
					<option value=""><?= h(__('Todas')) ?></option>
					<option value="0"<?= (string)$f['situacao'] === '0' ? ' selected' : '' ?>>0 - <?= h(__('Aberta')) ?></option>
					<option value="1"<?= (string)$f['situacao'] === '1' ? ' selected' : '' ?>>1 - <?= h(__('Em execução')) ?></option>
					<option value="2"<?= (string)$f['situacao'] === '2' ? ' selected' : '' ?>>2 - <?= h(__('Aguardando')) ?></option>
					<option value="3"<?= (string)$f['situacao'] === '3' ? ' selected' : '' ?>>3 - <?= h(__('Concluída')) ?></option>
				</select>
			</div>
			<div class="field" style="flex:0 0 140px;"><label><?= h(__('Abertura de')) ?></label><input type="date" name="de" value="<?= h((string)$f['de']) ?>"></div>
			<div class="field" style="flex:0 0 140px;"><label><?= h(__('Até')) ?></label><input type="date" name="ate" value="<?= h((string)$f['ate']) ?>"></div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
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
					<?php $osHref = $this->Url->build(['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', (int)$it['id']]); ?>
					<tr data-pgm-row-href="<?= h($osHref) ?>" tabindex="0">
						<td><strong><?= sprintf('OS-%05d', (int)$it['id']) ?></strong></td>
						<td><?= h((string)$it['cliente']) ?></td>
						<td><?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 80, ['ellipsis' => '…'])) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['valor'])) ?></strong></td>
						<td class="mu"><?= h($H->dt($it['data'])) ?></td>
						<td><?= $H->badge((string)$it['situacao'] !== '' ? (string)$it['situacao'] : '—', str_replace('b-', '', $badge)) ?></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
