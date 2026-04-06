<?php
/**
 * Bloco reutilizável: label + botão modal + hidden + textarea somente leitura + ajuda.
 * IDs/classes dos botões preservam integração com JS da ficha (gerenciar e-mails).
 */
$labelTitle = $labelTitle ?? '';
$modalTarget = $modalTarget ?? '#';
$fieldName = $fieldName ?? 'email';
$hiddenId = $hiddenId ?? 'email';
$textareaId = $textareaId ?? '';
$placeholder = $placeholder ?? '';
$helpText = $helpText ?? '';
$gerenciarClass = $gerenciarClass ?? 'btn btn-sm btn-outline-info';
$colClass = $colClass ?? 'col-lg-6 col-md-6 col-sm-12';
?>
<div class="<?= h($colClass) ?>">
	<div class="form-group cli-cmp-field">
		<label class="cli-cmp-label cli-cmp-label--row d-flex justify-content-between align-items-center flex-wrap pgm-gap-8">
			<span><?= h($labelTitle) ?></span>
			<button type="button" class="<?= h($gerenciarClass) ?>" data-toggle="modal" data-target="<?= h($modalTarget) ?>">Adicionar e-mails</button>
		</label>
		<?= $this->Form->hidden($fieldName, ['id' => $hiddenId]) ?>
		<textarea id="<?= h($textareaId) ?>" class="form-control cli-cmp-input" rows="2" readonly placeholder="<?= h($placeholder) ?>"></textarea>
		<?php if ($helpText !== ''): ?>
		<small class="form-text text-muted cli-cmp-help"><?= h($helpText) ?></small>
		<?php endif; ?>
	</div>
</div>
