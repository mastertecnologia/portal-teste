<?php
/**
 * Wizard 3/4 — Atribuir assentos.
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
$emails = [];
foreach ((array)($row['assentos_rows'] ?? []) as $a) {
	if (!empty($a['email'])) {
		$emails[] = $a['email'];
	}
}
$cap = max(1, (int)($row['assentos'] ?? 1));
?>
<?= $this->element('LicencasPrototype/wizard_header', ['wizardStepNum' => 3, 'wizardSteps' => $wizardSteps ?? [], 'licId' => (int)$licId]) ?>
<p style="font-size:12px;color:var(--text-muted);margin:-8px 0 14px;"><?= h(__('Passo 3 de 4 · Atribuir assentos a usuários')) ?> · <strong><?= h($row['codigo'] ?? '') ?></strong></p>

<form id="lic-wizard-form" method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="3">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="g2" style="gap:14px;align-items:start;">
	<div class="card">
		<div class="sec-title">👥 <?= h(__('Atribuir assentos')) ?></div>
		<p style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= h(__('Informe até {0} e-mail(s) (um por linha ou separados por vírgula).', $cap) ?></p>
		<div class="field">
			<label><?= h(__('E-mails dos usuários')) ?></label>
			<textarea name="emails" rows="10" placeholder="usuario@empresa.com.br"><?= h(implode("\n", $emails)) ?></textarea>
		</div>
	</div>
	<div>
		<div class="card" style="position:sticky;top:14px;">
			<div class="sec-title">👁 <?= h(__('Resumo')) ?></div>
			<ul style="font-size:12px;line-height:1.8;margin:0;padding-left:16px;">
				<li><strong><?= h(__('Cliente')) ?>:</strong> <?= h($row['cliente'] ?? '') ?></li>
				<li><strong><?= h(__('Produto')) ?>:</strong> <?= h($row['produto'] ?? '') ?></li>
				<li><strong><?= h(__('Assentos contratados')) ?>:</strong> <?= (int)$cap ?></li>
				<li><strong><?= h(__('E-mails informados')) ?>:</strong> <?= count($emails) ?></li>
			</ul>
		</div>
	</div>
</div>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar (Quantidade & Datas)'), ['action' => 'view', 'nova-2', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Próximo: Cofre & Documentos')) ?> →</button>
</div>
</form>
