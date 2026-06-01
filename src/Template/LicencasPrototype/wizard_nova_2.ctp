<?php
/**
 * Wizard 2/4 — vigência e valores.
 *
 * @var array<string,mixed>|null $lic
 * @var int $licId
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
$ini = $row['inicio'] ?? '';
$fim = $row['fim'] ?? '';
if (is_object($ini) && method_exists($ini, 'format')) {
	$ini = $ini->format('Y-m-d');
}
if (is_object($fim) && method_exists($fim, 'format')) {
	$fim = $fim->format('Y-m-d');
}
$valor = $row['valor_anual'] ?? '';
?>
<div class="pg-page-head" style="margin-bottom:14px;">
	<div>
		<h1 class="pg-page-title" style="font-size:20px;">+ <?= h(__('Nova licença')) ?></h1>
		<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Passo 2 de 4')) ?> · <?= h($row['codigo'] ?? '') ?></p>
	</div>
</div>

<?= $this->ErpPrototype->stepper($wizardSteps) ?>

<form method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="2">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="card">
	<div class="g2">
		<div class="field">
			<label><?= h(__('Assentos')) ?></label>
			<input type="number" name="assentos" value="<?= (int)($row['assentos'] ?? 1) ?>" min="1">
		</div>
		<div class="field">
			<label><?= h(__('Modelo')) ?></label>
			<select name="modelo">
				<option value="assinatura"<?= ($row['modelo'] ?? '') === 'assinatura' ? ' selected' : '' ?>><?= h(__('Assinatura')) ?></option>
				<option value="perpetua"<?= ($row['modelo'] ?? '') === 'perpetua' ? ' selected' : '' ?>><?= h(__('Perpétua')) ?></option>
			</select>
		</div>
		<div class="field">
			<label><?= h(__('Início')) ?></label>
			<input type="date" name="inicio" value="<?= h((string)$ini) ?>">
		</div>
		<div class="field">
			<label><?= h(__('Fim')) ?></label>
			<input type="date" name="fim" value="<?= h((string)$fim) ?>">
		</div>
		<div class="field">
			<label><?= h(__('Valor anual (R$)')) ?></label>
			<input type="text" name="valor_anual" value="<?= h((string)$valor) ?>" placeholder="0,00">
		</div>
	</div>
</div>

<div style="display:flex;justify-content:space-between;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar'), ['action' => 'view', 'nova', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost']) ?>
	<button type="submit" class="btn btn-primary"><?= h(__('Salvar e continuar')) ?> →</button>
</div>
</form>
