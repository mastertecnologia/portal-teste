<?php
/** @var \App\View\AppView $this @var array $wizardSteps */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Abertura')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🛠 <?= h(__('Nova ordem de serviço')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Cabeçalho da OS')) ?></div>
	<div class="g3">
		<div class="field"><label><?= h(__('Cliente')) ?></label><input type="text" placeholder="<?= h(__('Buscar cliente...')) ?>"></div>
		<div class="field"><label><?= h(__('Técnico responsável')) ?></label><select><option><?= h(__('— Selecione —')) ?></option></select></div>
		<div class="field"><label><?= h(__('Prioridade')) ?></label><select><option>P3 · Normal</option><option>P2 · Alta</option><option>P1 · Crítica</option></select></div>
	</div>
	<div class="field" style="margin-top:12px;">
		<label><?= h(__('Descrição da demanda')) ?></label>
		<textarea rows="4" placeholder="<?= h(__('Descreva o serviço solicitado...')) ?>"></textarea>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Iniciar execução') . ' →', ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'execucao'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
