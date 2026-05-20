<?php
/**
 * Extrato bancário — mockup pg-extrato com filtros.
 *
 * @var \App\View\AppView $this
 * @var array{entradas:float,saidas:float,pendentes:int,total_mov:int} $extKpi
 * @var array<int,array<string,mixed>> $extItems
 * @var array<int,string> $extContas
 * @var array{tipo:string,conta:string,dias:int} $extFiltros
 */
$H = $this->ErpPrototype;
$saldo = $extKpi['entradas'] - $extKpi['saidas'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Bancos')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📊 <?= h(__('Extrato Bancário')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= sprintf(h(__('Últimos %d dias · %d movimentos')), (int)$extFiltros['dias'], (int)$extKpi['total_mov']) ?></div>
	</div>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link('📥 ' . __('Exportar CSV'), ['controller' => 'BancosPrototype', 'action' => 'exportExtratoCsv', '?' => array_filter($extFiltros)], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'BancosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Entradas')) ?></div><div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$extKpi['entradas'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Saídas')) ?></div><div class="val" style="color:#7A1822;"><?= h($H->brl((float)$extKpi['saidas'])) ?></div></div>
	<div class="summary-card" style="border-left:3px solid <?= $saldo >= 0 ? 'var(--teal)' : 'var(--red)' ?>;"><div class="lbl"><?= h(__('Saldo do período')) ?></div><div class="val" style="color:<?= $saldo >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h($H->brl((float)$saldo)) ?></div></div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Pendentes de conciliação')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)$extKpi['pendentes'] ?></div></div>
</div>

<div class="card" style="padding:0;overflow:hidden;">
	<form method="get" style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
		<div class="field" style="flex:0 0 140px;"><label><?= h(__('Período (dias)')) ?></label>
			<select name="dias" style="width:100%;">
				<?php foreach ([7, 15, 30, 60, 90, 180] as $d) : ?>
					<option value="<?= $d ?>"<?= (int)$extFiltros['dias'] === $d ? ' selected' : '' ?>><?= $d ?> <?= h(__('dias')) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field" style="flex:0 0 180px;"><label><?= h(__('Tipo')) ?></label>
			<select name="tipo" style="width:100%;">
				<option value=""><?= h(__('Todos')) ?></option>
				<option value="c"<?= (string)$extFiltros['tipo'] === 'c' ? ' selected' : '' ?>>📈 <?= h(__('Crédito (entrada)')) ?></option>
				<option value="d"<?= (string)$extFiltros['tipo'] === 'd' ? ' selected' : '' ?>>📉 <?= h(__('Débito (saída)')) ?></option>
			</select>
		</div>
		<?php if ($extContas !== []) : ?>
			<div class="field" style="flex:0 0 220px;"><label><?= h(__('Conta')) ?></label>
				<select name="conta" style="width:100%;">
					<option value=""><?= h(__('Todas')) ?></option>
					<?php foreach ($extContas as $c) : ?>
						<option value="<?= h($c) ?>"<?= (string)$extFiltros['conta'] === (string)$c ? ' selected' : '' ?>><?= h($c) ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>
		<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Aplicar filtros')) ?></button>
		<?= $this->Html->link(__('Limpar'), ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</form>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Data')) ?></th>
					<th></th>
					<th><?= h(__('Descrição')) ?></th>
					<th><?= h(__('Conta')) ?></th>
					<th class="r"><?= h(__('Valor')) ?></th>
					<th><?= h(__('Conciliado?')) ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ($extItems === []) : ?>
					<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum movimento no período/filtros selecionados.')) ?></td></tr>
				<?php else : foreach ($extItems as $it) :
					$cor = !empty($it['is_entrada']) ? 'var(--teal-dark)' : '#7A1822';
					$icon = !empty($it['is_entrada']) ? '📈' : '📉';
				?>
					<tr>
						<td class="mu"><?= h($H->dt($it['data'], 'd/m/Y')) ?></td>
						<td style="font-size:14px;"><?= $icon ?></td>
						<td>
							<?= h(\Cake\Utility\Text::truncate((string)$it['descricao'], 70, ['ellipsis' => '…'])) ?>
							<?php if (!empty($it['fitid'])) : ?>
								<div style="font-size:10px;color:var(--text-muted);font-family:monospace;">FITID: <?= h((string)$it['fitid']) ?></div>
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= h((string)$it['conta']) ?></td>
						<td class="r" style="color:<?= h($cor) ?>;font-weight:700;"><?= h($H->brl((float)$it['valor'])) ?></td>
						<td>
							<?php if (!empty($it['conciliado'])) : ?>
								<?= $H->badge(__('Conciliado'), 'paga') ?>
							<?php else : ?>
								<?= $H->badge(__('Pendente'), 'pendente') ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
</div>
