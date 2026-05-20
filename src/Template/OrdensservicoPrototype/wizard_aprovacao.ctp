<?php
/** @var \App\View\AppView $this @var array $wizardSteps */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Aprovação')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">✓ <?= h(__('Aprovação do cliente')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Execução'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'execucao'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="alert-box alert-blue">
	<?= h(__('O cliente recebe a OS por e-mail ou portal para aceitar antes de fechar.')) ?>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Resumo enviado ao cliente')) ?></div>
	<div class="g2">
		<div class="summary-card" style="background:var(--bg-surface);"><div class="lbl"><?= h(__('Horas apontadas')) ?></div><div class="val">—</div></div>
		<div class="summary-card" style="background:var(--bg-surface);"><div class="lbl"><?= h(__('Total da OS')) ?></div><div class="val"><?= h($H->brl(0)) ?></div></div>
	</div>
	<div class="footer-bar" style="margin-top:14px;border:none;padding:0;">
		<?= $this->Html->link(__('Reabrir execução'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'execucao'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Marcar como aprovada') . ' →', ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'conclusao'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
