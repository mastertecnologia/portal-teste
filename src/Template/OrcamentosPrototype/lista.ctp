<?php
/**
 * Lista de orçamentos — mockup pg-lista.
 *
 * @var \App\View\AppView $this
 * @var array{pendente:int,enviado:int,aprovado:int,recusado:int,total:int} $orcCounts
 * @var float $orcTotalValor
 * @var array<int,array<string,mixed>> $orcItems
 * @var array<int,string> $orcStatusLabels
 * @var array<int,string> $orcClientesOptions
 * @var array{status:string,cliente:string,de:string,ate:string} $orcFiltros
 */
$H = $this->ErpPrototype;
$f = (array)($orcFiltros ?? ['status' => '', 'cliente' => '', 'de' => '', 'ate' => '']);
$clientesOptions = (array)($orcClientesOptions ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">💼 <?= h(__('Orçamentos')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);">
			<?= sprintf(h(__('%d orçamentos · total R$ %s no escopo da empresa ativa')), (int)$orcCounts['total'], number_format((float)$orcTotalValor, 2, ',', '.')) ?>
		</div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link('📥 ' . __('Exportar CSV'), ['controller' => 'OrcamentosPrototype', 'action' => 'exportCsv', '?' => array_filter($f)], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📊 ' . __('Relatórios'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'cobranca'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('+ ' . __('Novo orçamento'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
	<div class="stat" style="--sc:var(--teal);"><div class="stat-l"><?= h(__('Total')) ?></div><div class="stat-n"><?= (int)$orcCounts['total'] ?></div></div>
	<div class="stat" style="--sc:var(--amber);"><div class="stat-l"><?= h(__('Pendentes')) ?></div><div class="stat-n"><?= (int)$orcCounts['pendente'] ?></div></div>
	<div class="stat" style="--sc:var(--blue);"><div class="stat-l"><?= h(__('Enviados')) ?></div><div class="stat-n"><?= (int)$orcCounts['enviado'] ?></div></div>
	<div class="stat" style="--sc:var(--teal-dark);"><div class="stat-l"><?= h(__('Aprovados')) ?></div><div class="stat-n"><?= (int)$orcCounts['aprovado'] ?></div></div>
	<div class="stat" style="--sc:var(--red);"><div class="stat-l"><?= h(__('Recusados')) ?></div><div class="stat-n"><?= (int)$orcCounts['recusado'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);">
		<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
			<div class="field" style="flex:1;min-width:180px;">
				<label><?= h(__('Cliente')) ?></label>
				<select name="cliente">
					<option value=""><?= h(__('Todos')) ?></option>
					<?php foreach ($clientesOptions as $cid => $cnome) : ?>
						<option value="<?= (int)$cid ?>"<?= (string)$f['cliente'] === (string)$cid ? ' selected' : '' ?>><?= h(\Cake\Utility\Text::truncate((string)$cnome, 40, ['ellipsis' => '…'])) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 160px;">
				<label><?= h(__('Status')) ?></label>
				<select name="status">
					<option value=""><?= h(__('Todos')) ?></option>
					<?php foreach ($orcStatusLabels as $st => $lbl) : ?>
						<option value="<?= (int)$st ?>"<?= (string)$f['status'] === (string)$st ? ' selected' : '' ?>><?= h($lbl) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field" style="flex:0 0 140px;"><label><?= h(__('Criado de')) ?></label><input type="date" name="de" value="<?= h((string)$f['de']) ?>"></div>
			<div class="field" style="flex:0 0 140px;"><label><?= h(__('Até')) ?></label><input type="date" name="ate" value="<?= h((string)$f['ate']) ?>"></div>
			<button type="submit" class="btn btn-primary btn-sm">🔍 <?= h(__('Filtrar')) ?></button>
			<?= $this->Html->link(__('Limpar'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		</div>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Número')) ?></th>
					<th><?= h(__('Cliente')) ?></th>
					<th><?= h(__('Autor')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Data')) ?></th>
					<th><?= h(__('Situação')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($orcItems === []) : ?>
					<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum orçamento no escopo.')) ?></td></tr>
				<?php else : foreach ($orcItems as $it) :
					$st = (int)$it['status'];
					$lbl = (string)($orcStatusLabels[$st] ?? '—');
					$badge = 'b-arq';
					if ($lbl === __('Pendente')) {
						$badge = 'b-pendente';
					} elseif ($lbl === __('Enviado')) {
						$badge = 'b-env';
					} elseif ($lbl === __('Aprovado')) {
						$badge = 'b-aprov';
					} elseif ($lbl === __('Recusado')) {
						$badge = 'b-recus';
					}
				?>
					<?php $orcHref = $this->Url->build(['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', (int)$it['id']]); ?>
					<tr data-pgm-row-href="<?= h($orcHref) ?>" tabindex="0">
						<td><strong><?= sprintf('ORC-%04d', (int)$it['id']) ?></strong></td>
						<td><?= h((string)$it['cliente']) ?></td>
						<td><?= h((string)$it['autor']) ?></td>
						<td class="r"><strong><?= h($H->brl((float)$it['valor'])) ?></strong></td>
						<td class="mu"><?= h($H->dt($it['modified'])) ?></td>
						<td><?= $H->badge($lbl, str_replace('b-', '', $badge)) ?></td>
						<td class="r"><?= $this->Html->link(__('Abrir'), ['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', (int)$it['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>

<div class="alert-box alert-teal" style="margin-top:14px;">
	💡 <?= h(__('Use os filtros e abra o detalhe para editar itens, mudar status ou exportar CSV. O módulo Orçamentos clássico continua disponível em paralelo.')) ?>
	<span style="color:var(--text-muted);"> · ⌨️ <code>j</code>/<code>k</code> <?= h(__('navega linhas')) ?></span>
</div>
