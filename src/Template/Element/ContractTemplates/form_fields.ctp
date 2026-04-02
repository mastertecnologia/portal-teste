<?php
/** @var \App\Model\Entity\ContractTemplate $template */
$cp = $template->clausulas_padrao;
$clausulasJson = json_encode(is_array($cp) ? $cp : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$va = $template->variaveis;
$variaveisJson = json_encode(is_array($va) ? $va : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
<?= $this->Form->control('nome', ['label' => 'Nome', 'class' => 'form-control', 'required' => true]) ?>
<?= $this->Form->control('tipo_contrato', [
	'label' => 'Tipo',
	'class' => 'form-control',
	'options' => [
		'servico' => 'Serviço',
		'licenca' => 'Licença',
		'misto' => 'Misto',
	],
	'empty' => false,
]) ?>
<?= $this->Form->control('descricao', ['label' => 'Descrição', 'type' => 'textarea', 'class' => 'form-control', 'rows' => 3]) ?>
<?= $this->Form->control('conteudo_html', ['label' => 'Conteúdo HTML', 'type' => 'textarea', 'class' => 'form-control', 'rows' => 12]) ?>
<div class="form-group">
	<label>Cláusulas padrão (JSON array)</label>
	<?= $this->Form->textarea('clausulas_padrao', ['class' => 'form-control small', 'rows' => 6, 'value' => $clausulasJson, 'escape' => false, 'style' => 'font-family:monospace']) ?>
</div>
<div class="form-group">
	<label>Variáveis (JSON array)</label>
	<?= $this->Form->textarea('variaveis', ['class' => 'form-control small', 'rows' => 6, 'value' => $variaveisJson, 'escape' => false, 'style' => 'font-family:monospace']) ?>
</div>
<?= $this->Form->control('versao', ['label' => 'Versão', 'type' => 'number', 'class' => 'form-control', 'min' => 1]) ?>
<?= $this->Form->control('ativo', [
	'label' => 'Ativo',
	'type' => 'checkbox',
	'checked' => (bool)$template->ativo,
	'value' => 1,
	'hiddenField' => false,
]) ?>
