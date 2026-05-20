<?php
/** @var \App\View\AppView $this @var array $wizardSteps */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Conclusão')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📝 <?= h(__('Relatório final + faturamento')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Aprovação'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'aprovacao'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Relatório técnico')) ?></div>
	<div class="field"><textarea rows="4" placeholder="<?= h(__('Resumo do que foi feito, lições aprendidas, recomendações...')) ?>"></textarea></div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Faturar?')) ?></div>
	<div class="g2">
		<div class="field"><label><?= h(__('Forma de pagamento')) ?></label><select><option><?= h(__('Boleto')) ?></option><option>PIX</option><option><?= h(__('Cartão')) ?></option></select></div>
		<div class="field"><label><?= h(__('Vencimento')) ?></label><input type="date"></div>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'aprovacao'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<?= $this->Html->link(__('Concluir OS') . ' →', ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'sucesso'], ['class' => 'btn btn-primary btn-sm']) ?>
</div>
