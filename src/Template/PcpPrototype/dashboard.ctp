<?php
/**
 * Dashboard PCP — KPIs reais (tabelas pcp_*).
 *
 * @var \App\View\AppView $this
 * @var array<string,int> $pcpKpi
 * @var array<int,array<string,mixed>> $pcpOrdensRecentes
 * @var bool $pcpMigrationHint
 */
$k = (array)($pcpKpi ?? []);
$rows = (array)($pcpOrdensRecentes ?? []);
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Indústria · PCP')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h(__('Dashboard PCP')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Indicadores a partir das ordens de produção e cadastros PCP')) ?></div>
	</div>
	<?= $this->Html->link('← ' . __('Visão geral'), ['controller' => 'PcpPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?php if (!empty($pcpMigrationHint)) : ?>
<div class="alert-box alert-amber" style="margin-bottom:14px;">
	<strong><?= h(__('Migração pendente:')) ?></strong>
	<?= h(__('execute bin/cake migrations migrate para criar as tabelas pcp_* antes de cadastrar OPs.')) ?>
</div>
<?php endif; ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--blue);"><div class="lbl"><?= h(__('OPs planejadas/abertas')) ?></div><div class="val" style="color:#0C447C;"><?= (int)($k['ops_abertas'] ?? 0) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--amber);"><div class="lbl"><?= h(__('Em execução')) ?></div><div class="val" style="color:#8A4D02;"><?= (int)($k['ops_execucao'] ?? 0) ?></div></div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);"><div class="lbl"><?= h(__('Aguardando')) ?></div><div class="val" style="color:#7A1822;"><?= (int)($k['ops_aguardando'] ?? 0) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal-dark);"><div class="lbl"><?= h(__('Concluídas')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)($k['ops_concluidas'] ?? 0) ?></div></div>
	<div class="summary-card" style="border-left:3px solid var(--teal);"><div class="lbl"><?= h(__('Centros de trabalho')) ?></div><div class="val" style="color:var(--teal-dark);"><?= (int)($k['centros'] ?? 0) ?></div></div>
	<div class="summary-card"><div class="lbl"><?= h(__('Fichas engenharia')) ?></div><div class="val"><?= (int)($k['fichas'] ?? 0) ?></div></div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Ordens recentes')) ?></div>
	<?php if ($rows === []) : ?>
		<p style="font-size:13px;color:var(--text-muted);padding:12px 0;"><?= h(__('Nenhuma ordem de produção cadastrada.')) ?></p>
	<?php else : ?>
	<div class="tbl-wrap">
		<table class="tbl">
			<thead><tr><th><?= h(__('Nº OP')) ?></th><th><?= h(__('Produto')) ?></th><th class="r"><?= h(__('Qtd')) ?></th><th><?= h(__('Status')) ?></th><th></th></tr></thead>
			<tbody>
			<?php foreach ($rows as $r) : ?>
				<tr>
					<td><span class="titulo-num"><?= h($r['numero']) ?></span></td>
					<td><?= h($r['produto']) ?></td>
					<td class="r"><?= h(number_format((float)$r['quantidade'], 2, ',', '.')) ?></td>
					<td><span class="badge b-pend"><?= h($r['status']) ?></span></td>
					<td><?= $this->Html->link(__('Abrir'), ['controller' => 'PcpPrototype', 'action' => 'opDetalhe', $r['id']], ['class' => 'btn btn-ghost btn-xs']) ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	<div style="margin-top:12px;">
		<?= $this->Html->link(__('Ver todas as OPs →'), ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
