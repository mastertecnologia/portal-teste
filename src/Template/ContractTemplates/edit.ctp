<?php
$this->assign('title', $title ?? 'Editar modelo');
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h4 class="card-title"><?= h($title) ?></h4>
			<?= $this->Form->create($template, ['url' => '/contract-templates/edit/' . (int)$template->id, 'class' => 'contract-templates-form']) ?>
			<?= $this->element('ContractTemplates/form_fields') ?>
			<?= $this->Form->button(__('Salvar'), ['class' => 'btn btn-primary']) ?>
			<?= $this->Html->link(__('Cancelar'), '/contract-templates', ['class' => 'btn btn-secondary']) ?>
			<?= $this->Form->end() ?>
			<?= $this->element('ContractTemplates/tinymce_setup', ['editorId' => 'contract-template-conteudo-html']) ?>
		</div>
	</div>
</div>
