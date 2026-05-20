<?php
/**
 * Wizard · 5/5 Sucesso — mockup pg-sucesso.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Concluído')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🎉 <?= h(__('Orçamento enviado!')) ?></h1>
	</div>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card" style="text-align:center;padding:48px 22px;">
	<div style="width:96px;height:96px;background:var(--teal-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:48px;">✅</div>
	<h2 style="font-size:20px;font-weight:600;margin-bottom:8px;color:var(--teal-dark);"><?= h(__('Tudo certo!')) ?></h2>
	<p style="color:var(--text-muted);max-width:520px;margin:0 auto 22px;">
		<?= h(__('O orçamento foi assinado internamente e enviado ao cliente para validação. Você será notificado quando houver aceite ou recusa.')) ?>
	</p>
	<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
		<?= $this->Html->link(__('🗂 Ver lista'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('+ Novo orçamento'), ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
