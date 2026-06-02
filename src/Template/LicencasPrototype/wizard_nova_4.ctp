<?php
/**
 * Wizard 4/4 — Cofre & finalizar.
 */
$csrf = (string)$this->request->getAttribute('csrfToken');
$row = (array)($lic ?? []);
?>
<?= $this->element('LicencasPrototype/wizard_header', ['wizardStepNum' => 4, 'wizardSteps' => $wizardSteps ?? [], 'licId' => (int)$licId]) ?>
<p style="font-size:12px;color:var(--text-muted);margin:-8px 0 14px;"><?= h(__('Passo 4 de 4 · Revisão e ativação')) ?> · <strong><?= h($row['codigo'] ?? '') ?></strong></p>

<form id="lic-wizard-form" method="post" action="<?= h($this->Url->build(['action' => 'salvarWizard'])) ?>">
<input type="hidden" name="_csrfToken" value="<?= h($csrf) ?>">
<input type="hidden" name="wizard_step" value="4">
<input type="hidden" name="id" value="<?= (int)$licId ?>">

<div class="g2" style="gap:14px;align-items:start;">
	<div class="card">
		<div class="sec-title">📋 <?= h(__('Revisão final')) ?></div>
		<ul style="font-size:13px;line-height:1.8;margin:0 0 12px 18px;">
			<li><strong><?= h(__('Código')) ?>:</strong> <?= h($row['codigo'] ?? '') ?></li>
			<li><strong><?= h(__('Cliente')) ?>:</strong> <?= h($row['cliente'] ?? '') ?></li>
			<li><strong><?= h(__('Produto')) ?>:</strong> <?= h($row['produto'] ?? '') ?></li>
			<li><strong><?= h(__('Assentos')) ?>:</strong> <?= (int)($row['assentos'] ?? 0) ?></li>
			<li><strong><?= h(__('Vigência')) ?>:</strong> <?= h($this->ErpPrototype->dt($row['inicio'] ?? '')) ?> → <?= h($this->ErpPrototype->dt($row['fim'] ?? '')) ?></li>
			<li><strong><?= h(__('Valor anual')) ?>:</strong> <?= $this->ErpPrototype->brl($row['valor_anual'] ?? 0) ?></li>
		</ul>
		<div class="field">
			<label><?= h(__('Status ao concluir')) ?></label>
			<select name="status_final">
				<option value="ativa"><?= h(__('Ativa — publicar licença')) ?></option>
				<option value="rascunho"><?= h(__('Manter como rascunho')) ?></option>
			</select>
		</div>
		<p style="font-size:11px;color:var(--text-muted);margin-top:10px;"><?= h(__('Credenciais no cofre podem ser cadastradas depois em Licenciamento › Cofre.')) ?></p>
	</div>
	<div>
		<div class="card" style="position:sticky;top:14px;background:var(--teal-light);">
			<div class="sec-title" style="color:var(--teal-dark);">✓ <?= h(__('Pronto para concluir')) ?></div>
			<p style="font-size:12px;margin:0;"><?= h(__('Ao concluir, a licença ficará disponível no painel, calendário de renovações e relatórios.')) ?></p>
		</div>
	</div>
</div>

<div class="card" style="margin-top:14px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
	<?= $this->Html->link('← ' . __('Voltar (Atribuir)'), ['action' => 'view', 'nova-3', '?' => ['id' => (int)$licId]], ['class' => 'btn btn-ghost btn-sm']) ?>
	<button type="submit" class="btn btn-primary btn-sm"><?= h(__('Concluir licença')) ?></button>
</div>
</form>
