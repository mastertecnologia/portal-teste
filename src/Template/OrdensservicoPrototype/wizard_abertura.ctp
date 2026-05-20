<?php
/** @var \App\View\AppView $this @var array $wizardSteps @var array $osClientesOptions */
$H = $this->ErpPrototype;
$clientes = (array)($osClientesOptions ?? []);
$csrf = (string)$this->request->getAttribute('csrfToken');
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('OS · Abertura')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🛠 <?= h(__('Nova ordem de serviço')) ?></h1>
	</div>
	<?= $this->Html->link('← ' . __('Cancelar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $H->stepper($wizardSteps) ?>

<form method="post" action="<?= h($this->Url->build(['controller' => 'OrdensservicoPrototype', 'action' => 'salvarRascunho'])) ?>" style="margin:0;">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<div class="card">
	<div class="sec-title"><?= h(__('Cabeçalho da OS')) ?></div>
	<div class="g3">
		<div class="field">
			<label><?= h(__('Cliente')) ?> *</label>
			<select name="idcliente" required>
				<option value=""><?= h(__('— Selecione um cliente —')) ?></option>
				<?php foreach ($clientes as $id => $nome) : ?>
					<option value="<?= (int)$id ?>"><?= h((string)$nome) ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field">
			<label><?= h(__('Técnico responsável')) ?></label>
			<input type="text" value="<?= h(trim((string)$this->getRequest()->getSession()->read('Auth.User.name'))) ?>" disabled>
		</div>
		<div class="field">
			<label><?= h(__('Prioridade')) ?></label>
			<select name="prioridade">
				<option value="2" selected>P3 · <?= h(__('Normal')) ?></option>
				<option value="1">P2 · <?= h(__('Alta')) ?></option>
				<option value="0">P1 · <?= h(__('Crítica')) ?></option>
				<option value="3">P4 · <?= h(__('Baixa')) ?></option>
			</select>
		</div>
	</div>
	<div class="field" style="margin-top:12px;">
		<label><?= h(__('Descrição da demanda')) ?></label>
		<textarea rows="4" name="relato" placeholder="<?= h(__('Descreva o serviço solicitado...')) ?>"></textarea>
	</div>
</div>

<div class="footer-bar">
	<?= $this->Html->link('← ' . __('Voltar'), ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm">💾 <?= h(__('Abrir OS e ir para execução')) ?></button>
</div>
</form>
