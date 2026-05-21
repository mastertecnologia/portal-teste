<?php
/**
 * Dashboard Financeiro — mockup pg-financeiro.
 *
 * @var \App\View\AppView $this
 * @var array<string,float|int> $finKpis
 */
$H = $this->ErpPrototype;
$saldo = (float)($finKpis['saldo_mes'] ?? 0);
?>
<?= $this->element('ErpPrototype/page_header', [
	'eyebrow' => __('Financeiro'),
	'title' => __('Dashboard Financeiro'),
	'subtitle' => __('Visão consolidada do mês corrente · CR, CP e saldo operacional'),
	'actions' => [
		['label' => __('Contas a Receber'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Contas a Pagar'), 'url' => ['controller' => 'FinanceiroPrototype', 'action' => 'contasPagar'], 'class' => 'btn btn-ghost btn-sm'],
		['label' => __('Bancos'), 'url' => ['controller' => 'BancosPrototype', 'action' => 'lista'], 'class' => 'btn btn-primary'],
	],
]) ?>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('A receber (aberto)')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$finKpis['cr_receber'])) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Recebido no mês')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= h($H->brl((float)$finKpis['cr_recebido_mes'])) ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Vencendo 30d')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= h($H->brl((float)$finKpis['cr_vencendo_30d'])) ?></div>
	</div>
	<div class="summary-card" style="background:#F8D8DA;border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('Faturas atrasadas')) ?></div>
		<div class="val" style="color:#7A1822;"><?= (int)$finKpis['cr_atrasadas'] ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('A pagar (aberto)')) ?></div>
		<div class="val" style="color:#7A1822;"><?= h($H->brl((float)$finKpis['cp_pagar'])) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--red);">
		<div class="lbl"><?= h(__('Pago no mês')) ?></div>
		<div class="val" style="color:#7A1822;"><?= h($H->brl((float)$finKpis['cp_pago_mes'])) ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid <?= $saldo >= 0 ? 'var(--teal)' : 'var(--red)' ?>;background:<?= $saldo >= 0 ? '#E1F5EE' : '#F8D8DA' ?>;">
		<div class="lbl"><?= h(__('Saldo operacional do mês')) ?></div>
		<div class="val" style="color:<?= $saldo >= 0 ? 'var(--teal-dark)' : '#7A1822' ?>;"><?= h($H->brl($saldo)) ?></div>
		<div style="font-size:11px;color:var(--text-muted);"><?= h(__('Recebido − Pago')) ?></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Atalhos')) ?></div>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
		<?= $this->Html->link(__('📈 Fluxo de Caixa'), ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('🧾 DRE Gerencial'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('🧾 NF-e / NFS-e'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'nfe'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('📊 Relatórios financeiros'), ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'relatorios-fin'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
