<?php
/**
 * pg-home — resumo executivo com dados reais.
 *
 * @var array<string,mixed> $homeKpi
 * @var array<int,array<string,mixed>> $homeRecentOrc
 * @var array<int,array<string,mixed>> $homeRecentOs
 * @var string $homeDataLabel
 */
$k = (array)($homeKpi ?? []);
?>
<div style="margin-bottom:18px;">
	<h1 style="font-size:24px;font-weight:600;margin:0 0 4px;"><?= h(__('Dashboard')) ?></h1>
	<div style="font-size:13px;color:var(--text-muted);"><?= h($homeDataLabel ?? '') ?> · <?= h(__('Resumo do ERP com dados do portal')) ?></div>
</div>

<div class="summary-grid" style="margin-bottom:16px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Orçamentos no mês')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)($k['orcamentos_mes'] ?? 0) ?></div>
		<div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= h(__('R$ {0}', number_format((float)($k['orcamentos_valor'] ?? 0), 2, ',', '.'))) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Ordens de serviço')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)($k['os_abertas'] ?? 0) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--purple);">
		<div class="lbl"><?= h(__('Tickets (escopo)')) ?></div>
		<div class="val" style="color:#3C3489;"><?= (int)($k['tickets_abertos'] ?? 0) ?></div>
	</div>
	<div class="summary-card">
		<div class="lbl"><?= h(__('Clientes ativos')) ?></div>
		<div class="val"><?= (int)($k['clientes_ativos'] ?? 0) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('A receber (faturas)')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= h('R$ ' . number_format((float)($k['cr_receber'] ?? 0), 2, ',', '.')) ?></div>
	</div>
</div>

<div class="g2">
	<div class="card">
		<div class="sec-title"><?= h(__('Orçamentos recentes')) ?></div>
		<?php foreach ((array)($homeRecentOrc ?? []) as $r) : ?>
			<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
				<span><?= h($r['label']) ?> · <?= h($r['cliente']) ?></span>
				<span style="font-weight:600;color:var(--teal-dark);"><?= h('R$ ' . number_format((float)$r['valor'], 2, ',', '.')) ?></span>
			</div>
		<?php endforeach; ?>
		<?php if (empty($homeRecentOrc)) : ?>
			<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Sem orçamentos no período.')) ?></p>
		<?php endif; ?>
		<div style="margin-top:10px;"><?= $this->Html->link(__('Ver orçamentos →'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?></div>
	</div>
	<div class="card">
		<div class="sec-title"><?= h(__('Ordens de serviço recentes')) ?></div>
		<?php foreach ((array)($homeRecentOs ?? []) as $r) : ?>
			<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border-light);font-size:12px;">
				<span><?= h($r['label']) ?> · <?= h($r['cliente']) ?></span>
				<?= $this->Html->link(__('Abrir'), ['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $r['id']], ['class' => 'btn btn-ghost btn-xs']) ?>
			</div>
		<?php endforeach; ?>
		<?php if (empty($homeRecentOs)) : ?>
			<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Sem ordens recentes.')) ?></p>
		<?php endif; ?>
		<div style="margin-top:10px;"><?= $this->Html->link(__('Ver OS →'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?></div>
	</div>
</div>
