<?php
$this->assign('title', $title ?? 'Pré-visualização');
/** @var \App\Model\Entity\ContractTemplate $template */
$cl = $template->clausulas_padrao;
$va = $template->variaveis;
$clJson = json_encode(is_array($cl) ? $cl : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$vaJson = json_encode(is_array($va) ? $va : [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
<div class="col-12 pgm-adv-page">
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
				<h4 class="card-title mb-0"><?= h($title) ?></h4>
				<div>
					<?= $this->Html->link(__('Editar'), '/contract-templates/edit/' . (int)$template->id, ['class' => 'btn btn-sm btn-primary']) ?>
					<?= $this->Html->link(__('Clonar'), '/contract-templates/clonar/' . (int)$template->id, ['class' => 'btn btn-sm btn-info', 'confirm' => __('Criar uma cópia deste modelo?')]) ?>
					<?= $this->Html->link(__('Lista'), '/contract-templates', ['class' => 'btn btn-sm btn-default']) ?>
				</div>
			</div>
			<p class="text-muted small mb-2">
				<?= h($template->tipo_contrato) ?> · v<?= (int)$template->versao ?>
				<?= !empty($template->ativo) ? ' · <span class="label label-success">ativo</span>' : ' · <span class="label label-default">inativo</span>' ?>
			</p>
			<?php if (trim((string)$template->descricao) !== ''): ?>
			<p class="small"><?= nl2br(h($template->descricao)) ?></p>
			<?php endif; ?>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title"><?= __('Conteúdo HTML') ?></h5>
			<p class="text-muted small"><?= __('Pré-visualização interna (equipe). HTML gravado no modelo.') ?></p>
			<div class="pgm-template-preview well well-sm" style="max-height:480px;overflow:auto;background:#fff;">
				<?= $template->conteudo_html ?>
			</div>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title"><?= __('Variáveis (JSON)') ?></h5>
			<pre class="small mb-0" style="max-height:200px;overflow:auto;"><?= h($vaJson) ?></pre>
		</div>
	</div>
	<div class="pgm-adv-panel card mb-3">
		<div class="card-body">
			<h5 class="card-title"><?= __('Cláusulas padrão (JSON)') ?></h5>
			<pre class="small mb-0" style="max-height:200px;overflow:auto;"><?= h($clJson) ?></pre>
		</div>
	</div>
</div>
