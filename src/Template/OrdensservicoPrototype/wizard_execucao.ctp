<?php
/** @var \App\View\AppView $this @var array $wizardSteps */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Execução')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">⚙ <?= h(__('Execução em andamento')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cabeçalho'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Apontamento de horas')) ?></div>
	<div class="g3">
		<div class="field"><label><?= h(__('Início')) ?></label><input type="datetime-local"></div>
		<div class="field"><label><?= h(__('Fim')) ?></label><input type="datetime-local"></div>
		<div class="field"><label><?= h(__('Horas')) ?></label><input type="number" step="0.25" placeholder="0,00"></div>
	</div>
	<div class="field" style="margin-top:12px;">
		<label><?= h(__('Atividades realizadas')) ?></label>
		<textarea rows="3" placeholder="<?= h(__('O que foi executado...')) ?>"></textarea>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Materiais utilizados')) ?></div>
	<table class="tbl" style="margin:0;">
		<thead><tr><th><?= h(__('Item')) ?></th><th class="r"><?= h(__('Qtd')) ?></th><th class="r"><?= h(__('Unit.')) ?></th><th class="r"><?= h(__('Subtotal')) ?></th></tr></thead>
		<tbody><tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:18px;"><?= h(__('Sem materiais. Adicione no fluxo clássico.')) ?></td></tr></tbody>
	</table>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'abertura'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Enviar para aprovação') . ' →', ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'aprovacao'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
