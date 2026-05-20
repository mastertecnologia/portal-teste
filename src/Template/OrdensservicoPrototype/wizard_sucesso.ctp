<?php
/** @var \App\View\AppView $this @var array $wizardSteps */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Concluída')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🎉 <?= h(__('Ordem fechada com sucesso')) ?></h1>
	</div>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card" style="text-align:center;padding:48px 22px;">
	<div style="width:96px;height:96px;background:var(--teal-light);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:48px;">✅</div>
	<h2 style="font-size:20px;font-weight:600;margin-bottom:8px;color:var(--teal-dark);"><?= h(__('OS concluída!')) ?></h2>
	<p style="color:var(--text-muted);max-width:520px;margin:0 auto 22px;">
		<?= h(__('Relatório final arquivado e fatura gerada. O cliente recebe automaticamente o comprovante e a próxima ação cabe ao financeiro.')) ?>
	</p>
	<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
		<?= $this->Html->link(__('🗂 Ver OS'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('💵 Contas a Receber'), ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>
