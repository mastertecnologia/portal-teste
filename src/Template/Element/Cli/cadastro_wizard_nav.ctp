<?php
/**
 * Navegação inferior do wizard (dentro do form) — mock pg-cliente-novo.
 *
 * @var bool|null $wizardShowSave Mostrar botão salvar no último passo
 */
$wizardShowSave = !empty($wizardShowSave);
?>
<div class="cli-wizard-nav" style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:18px 0 8px;padding-top:16px;border-top:1px solid var(--border);">
	<button type="button" class="btn btn-ghost cli-wizard-prev" data-cli-wizard-prev disabled style="display:inline-flex;align-items:center;gap:6px;">
		← <?= h(__('Anterior')) ?>
	</button>
	<span class="cli-wizard-step-label" data-cli-wizard-label style="font-size:12px;color:var(--text-muted);flex:1;text-align:center;"></span>
	<button type="button" class="btn btn-primary cli-wizard-next" data-cli-wizard-next style="display:inline-flex;align-items:center;gap:6px;">
		<?= h(__('Próximo')) ?> →
	</button>
	<?php if ($wizardShowSave) : ?>
	<button type="submit" class="btn btn-primary cli-wizard-save d-none" data-cli-wizard-save style="display:inline-flex;align-items:center;gap:6px;">
		✓ <?= h(__('Salvar cliente')) ?>
	</button>
	<?php endif; ?>
</div>
