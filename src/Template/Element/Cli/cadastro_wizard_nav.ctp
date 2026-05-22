<?php
/**
 * Navegação inferior do wizard (dentro do form).
 *
 * @var bool|null $wizardShowSave Mostrar botão salvar no último passo
 */
$wizardShowSave = !empty($wizardShowSave);
?>
<div class="cli-wizard-nav">
	<button type="button" class="btn-cli-secondary cli-wizard-prev" data-cli-wizard-prev disabled>
		<i class="fas fa-arrow-left" aria-hidden="true"></i> <?= h(__('Anterior')) ?>
	</button>
	<span class="cli-wizard-step-label" data-cli-wizard-label></span>
	<button type="button" class="btn-cli-primary cli-wizard-next" data-cli-wizard-next>
		<?= h(__('Próximo')) ?> <i class="fas fa-arrow-right" aria-hidden="true"></i>
	</button>
	<?php if ($wizardShowSave) : ?>
	<button type="submit" class="btn-cli-primary cli-wizard-save d-none" data-cli-wizard-save>
		<i class="fas fa-check" aria-hidden="true"></i> <?= h(__('Salvar cliente')) ?>
	</button>
	<?php endif; ?>
</div>
