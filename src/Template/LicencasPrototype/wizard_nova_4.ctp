<?php
/**
 * Wizard 4/4 — finalizar.
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
?>
<?= $this->element('LicencasPrototype/wizard_header', ['wizardStepNum' => 4, 'wizardSteps' => $wizardSteps ?? [], 'licId' => (int)$licId]) ?>

<form method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="4">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="card">
	<div class="sec-title"><?= h(__('Revisão')) ?></div>
	<ul style="font-size:13px;line-height:1.8;margin:0 0 12px 18px;">
		<li><strong><?= h(__('Código')) ?>:</strong> <?= h($row['codigo'] ?? '') ?></li>
		<li><strong><?= h(__('Cliente')) ?>:</strong> <?= h($row['cliente'] ?? '') ?></li>
		<li><strong><?= h(__('Produto')) ?>:</strong> <?= h($row['produto'] ?? '') ?></li>
		<li><strong><?= h(__('Assentos')) ?>:</strong> <?= (int)($row['assentos'] ?? 0) ?></li>
	</ul>
	<div class="field">
		<label><?= h(__('Status ao concluir')) ?></label>
		<select name="status_final">
			<option value="ativa"><?= h(__('Ativa')) ?></option>
			<option value="rascunho"><?= h(__('Manter rascunho')) ?></option>
		</select>
	</div>
</div>

<div style="display:flex;justify-content:space-between;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar'), ['action' => 'view', 'nova-3', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost']) ?>
	<button type="submit" class="btn btn-primary"><?= h(__('Concluir licença')) ?></button>
</div>
</form>
