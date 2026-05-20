<?php
/**
 * Wizard · 1/5 Novo orçamento — mockup pg-novo.
 *
 * @var \App\View\AppView $this
 * @var array<int,array{label:string,state:string}> $wizardSteps
 */
$H = $this->ErpPrototype;
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Comercial · Novo orçamento')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">📝 <?= h(__('Cabeçalho e cliente')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<div class="card">
	<div class="sec-title"><?= h(__('Dados do cliente')) ?></div>
	<div class="g2">
		<div class="field">
			<label><?= h(__('Cliente')) ?></label>
			<input type="text" placeholder="<?= h(__('Buscar cliente cadastrado...')) ?>" autocomplete="off">
		</div>
		<div class="field">
			<label><?= h(__('Vendedor')) ?></label>
			<select><option><?= h(__('— Selecione —')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('Centro de custo')) ?></label>
			<select><option><?= h(__('Comercial')) ?></option></select>
		</div>
		<div class="field">
			<label><?= h(__('Validade (dias)')) ?></label>
			<input type="number" value="30" min="1" max="180">
		</div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Observações iniciais')) ?></div>
	<div class="field">
		<textarea rows="3" placeholder="<?= h(__('Ex.: condições comerciais, prazo de entrega, garantia...')) ?>"></textarea>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar à lista'), ['controller' => 'OrcamentosPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<div style="display:flex;gap:8px;">
		<?= $this->Html->link(__('Salvar rascunho'), ['controller' => 'Orcamentos', 'action' => 'add'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link(__('Avançar para itens') . ' →', ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'revisao'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
</div>

<div class="alert-box alert-blue" style="margin-top:14px;">
	<?= h(__('Wizard em modo demonstração: a gravação real continua no fluxo clássico até a integração final.')) ?>
</div>
