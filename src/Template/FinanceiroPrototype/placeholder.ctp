<?php
/**
 * Placeholder das telas financeiras não migradas (DRE, NF-e, relatórios).
 *
 * @var \App\View\AppView $this
 * @var string $page
 */
$labels = [
	'nfe' => __('NF-e / NFS-e'),
	'dre' => __('DRE Gerencial'),
	'relatorios-fin' => __('Relatórios Financeiros'),
	'orc-faturamento' => __('Faturamento de orçamento'),
	'orc-cobranca' => __('Cobrança · orçamentos'),
	'cobranca' => __('Cobrança'),
];
$label = (string)($labels[$page] ?? ucfirst((string)$page));

$legacyMap = [
	'nfe' => ['FiscalNotas', 'index'],
	'dre' => ['FinanceiroRelatorios', 'index'],
	'relatorios-fin' => ['FinanceiroRelatorios', 'index'],
	'orc-faturamento' => ['Faturamento', 'index'],
	'orc-cobranca' => ['Faturamento', 'index'],
	'cobranca' => ['Faturamento', 'index'],
];
[$ctrlLegacy, $actLegacy] = $legacyMap[$page] ?? ['Financeiro', 'index'];
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Financeiro · Protótipo')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;"><?= h($label) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'FinanceiroPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<div class="card" style="text-align:center;padding:48px 22px;">
	<div style="font-size:48px;margin-bottom:14px;">🚧</div>
	<h2 style="font-size:18px;margin-bottom:8px;"><?= h(__('Tela em construção')) ?></h2>
	<p style="color:var(--text-muted);margin-bottom:18px;">
		<?= h(__('A interface premium para esta etapa será migrada do mockup nas próximas sub-fases.')) ?><br>
		<?= h(__('Por enquanto, o fluxo continua disponível no módulo clássico.')) ?>
	</p>
	<?= $this->Html->link(__('Ir ao módulo clássico'), ['controller' => $ctrlLegacy, 'action' => $actLegacy], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
