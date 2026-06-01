<?php
/**
 * Wizard 1/4 — cliente e produto.
 *
 * @var array<int,array{label:string,state:string}> $wizardSteps
 * @var array<int,array<string,mixed>> $licCatalogo
 * @var array<int,string> $licClientes
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$catalogo = (array)($licCatalogo ?? []);
$clientes = (array)($licClientes ?? []);
?>
<div class="pg-page-head" style="margin-bottom:14px;">
	<div>
		<h1 class="pg-page-title" style="font-size:20px;">+ <?= h(__('Nova licença')) ?></h1>
		<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Passo 1 de 4')) ?></p>
	</div>
	<?= $this->Html->link(__('Cancelar'), ['action' => 'licencas'], ['class' => 'btn btn-ghost btn-sm']) ?>
</div>

<?= $this->ErpPrototype->stepper($wizardSteps) ?>

<form method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="1">

<div class="card">
	<div class="sec-title"><?= h(__('Cliente')) ?></div>
	<div class="field">
		<label><?= h(__('Empresa-cliente')) ?> *</label>
		<select name="idcliente" required>
			<option value=""><?= h(__('Selecione…')) ?></option>
			<?php foreach ($clientes as $cid => $cnome) : ?>
			<option value="<?= (int)$cid ?>"><?= h($cnome) ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Produto')) ?></div>
	<?php if ($catalogo !== []) : ?>
	<div class="field">
		<label><?= h(__('Do catálogo')) ?></label>
		<select name="idcatalogo">
			<option value=""><?= h(__('Outro / descrever abaixo')) ?></option>
			<?php foreach ($catalogo as $p) : ?>
			<option value="<?= (int)$p['id'] ?>"><?= h($p['nome']) ?><?= $p['sku'] !== '' ? ' (' . h($p['sku']) . ')' : '' ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php endif; ?>
	<div class="field">
		<label><?= h(__('Nome do produto (se não estiver no catálogo)')) ?></label>
		<input type="text" name="produto_label" maxlength="200" placeholder="<?= h(__('Ex.: Microsoft 365 Business Premium')) ?>">
	</div>
	<div class="g2">
		<div class="field">
			<label><?= h(__('Modelo')) ?></label>
			<select name="modelo">
				<option value="assinatura"><?= h(__('Assinatura')) ?></option>
				<option value="perpetua"><?= h(__('Perpétua')) ?></option>
				<option value="trial"><?= h(__('Trial')) ?></option>
			</select>
		</div>
		<div class="field">
			<label><?= h(__('Assentos iniciais')) ?></label>
			<input type="number" name="assentos" value="1" min="1" max="9999">
		</div>
	</div>
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;">
	<button type="submit" class="btn btn-primary"><?= h(__('Salvar e continuar')) ?> →</button>
</div>
</form>
