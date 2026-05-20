<?php
/**
 * Wizard · 4/5 Assinatura digital — mockup pg-esign.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Assinatura')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">✍ <?= h(__('Coletar assinatura digital')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Preview'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'print'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Workflow de assinaturas')) ?></div>
	<div style="padding:8px 0;">
		<div class="tl-item">
			<div class="tl-dot" style="background:var(--teal-light);color:var(--teal-dark);">✓</div>
			<div class="tl-body">
				<div class="tl-title"><?= h(__('PGM Soluções (interna)')) ?></div>
				<div class="tl-sub"><?= h(__('Aprovação comercial · Concluída')) ?></div>
			</div>
		</div>
		<div class="tl-item">
			<div class="tl-dot" style="background:#FAEEDA;color:#633806;">⌛</div>
			<div class="tl-body">
				<div class="tl-title"><?= h(__('Cliente final')) ?></div>
				<div class="tl-sub"><?= h(__('Aguardando assinatura por e-mail (Autentique)')) ?></div>
			</div>
		</div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Opções de envio')) ?></div>
	<div class="g2">
		<div class="field">
			<label><?= h(__('Provedor')) ?></label>
			<select><option>Autentique (ICP-Brasil)</option><option><?= h(__('Assinatura manuscrita (PDF)')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('E-mail do signatário')) ?></label>
			<input type="email" placeholder="cliente@dominio.com">
		</div>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'print'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Enviar e concluir') . ' →', ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'sucesso'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
