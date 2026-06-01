<?php
/**
 * Wizard 3/4 — assentos por e-mail.
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
$emails = [];
foreach ((array)($row['assentos_rows'] ?? []) as $a) {
	if (!empty($a['email'])) {
		$emails[] = $a['email'];
	}
}
?>
<div class="pg-page-head" style="margin-bottom:14px;">
	<div>
		<h1 class="pg-page-title" style="font-size:20px;">+ <?= h(__('Nova licença')) ?></h1>
		<p style="font-size:12px;color:var(--text-muted);"><?= h(__('Passo 3 de 4')) ?></p>
	</div>
</div>

<?= $this->ErpPrototype->stepper($wizardSteps) ?>

<form method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="3">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="card">
	<div class="sec-title"><?= h(__('Atribuir assentos (e-mails)')) ?></div>
	<p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= h(__('Um e-mail por linha ou separados por vírgula.')) ?></p>
	<div class="field">
		<textarea name="emails" rows="8" placeholder="usuario@empresa.com.br"><?= h(implode("\n", $emails)) ?></textarea>
	</div>
</div>

<div style="display:flex;justify-content:space-between;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar'), ['action' => 'view', 'nova-2', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost']) ?>
	<button type="submit" class="btn btn-primary"><?= h(__('Salvar e continuar')) ?> →</button>
</div>
</form>
