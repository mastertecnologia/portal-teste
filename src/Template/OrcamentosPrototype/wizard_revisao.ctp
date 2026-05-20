<?php
/**
 * Wizard · 2/5 Revisão (itens + totais) — mockup pg-revisao.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Revisão')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📋 <?= h(__('Itens e totais')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card" style="padding:0;overflow:hidden;">
	<div style="padding:12px 14px;background:var(--bg-surface);border-bottom:1px solid var(--border-light);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
		<strong style="font-size:13px;"><?= h(__('Itens do orçamento')) ?></strong>
		<button class="btn btn-ghost btn-xs" type="button" disabled>+ <?= h(__('Adicionar item')) ?></button>
	</div>
	<div style="overflow-x:auto;">
		<table class="tbl" style="margin:0;">
			<thead>
				<tr>
					<th><?= h(__('Código')) ?></th>
					<th><?= h(__('Descrição')) ?></th>
					<th class="r"><?= h(__('Qtd')) ?></th>
					<th class="r"><?= h(__('Vlr unit.')) ?></th>
					<th class="r"><?= h(__('Desc.')) ?></th>
					<th class="r"><?= h(__('Subtotal')) ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<tr><td colspan="7" style="padding:24px;text-align:center;color:var(--text-muted);"><?= h(__('Nenhum item adicionado. Use o catálogo no fluxo clássico para preencher os itens.')) ?></td></tr>
			</tbody>
		</table>
	</div>
	<div class="tot-wrap" style="padding:14px;">
		<div class="tot-inner">
			<div class="tot-l"><span><?= h(__('Subtotal')) ?></span><span><?= h($H->brl(0)) ?></span></div>
			<div class="tot-l"><span><?= h(__('Descontos')) ?></span><span class="rd">−<?= h($H->brl(0)) ?></span></div>
			<div class="tot-l"><span><?= h(__('Total')) ?></span><span class="g"><?= h($H->brl(0)) ?></span></div>
		</div>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Cabeçalho'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Gerar PDF') . ' →', ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'print'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
