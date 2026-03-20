<?php
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Filas e técnicos', ['action' => 'adminIndex'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add($queue->isNew() ? 'Nova fila' : 'Editar fila', [], ['class' => 'breadcrumb-item active']);
$isNew = $queue->isNew();
?>
<div class="col-md-12 col-lg-8">
	<div class="card">
		<div class="card-body">
			<h5 class="card-title m-b-15"><?= $isNew ? 'Nova fila' : 'Editar fila' ?></h5>
			<?= $this->Form->create($queue, ['class' => 'form-material']) ?>
			<?php if (!$isNew) : ?>
				<?= $this->Form->hidden('id') ?>
			<?php endif; ?>

			<div class="form-group">
				<label class="control-label text-muted">Nome da fila</label>
				<?= $this->Form->control('name', ['class' => 'form-control', 'label' => false, 'required' => true, 'placeholder' => 'Ex.: N1 — Suporte inicial / triagem']) ?>
			</div>
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Código interno</label>
						<?= $this->Form->control('codigo', ['class' => 'form-control', 'label' => false, 'placeholder' => 'n1, n2, n3, noc, servico']) ?>
						<small class="text-muted">Opcional; alinha com o legado de filas por código.</small>
					</div>
				</div>
				<div class="col-md-6">
					<div class="form-group">
						<label class="control-label text-muted">Ordem de exibição</label>
						<?= $this->Form->control('sort_order', ['class' => 'form-control', 'label' => false, 'type' => 'number', 'value' => $queue->sort_order ?? 10]) ?>
					</div>
				</div>
			</div>
			<?php if (!empty($supportLevelsOptions)) : ?>
				<div class="form-group">
					<label class="control-label text-muted">Nível principal da fila</label>
					<?= $this->Form->control('support_level_id', [
						'type' => 'select',
						'options' => $supportLevelsOptions,
						'empty' => '— Selecione —',
						'class' => 'form-control',
						'label' => false,
					]) ?>
				</div>
			<?php endif; ?>
			<?php if (!empty($queuesHasDescription)) : ?>
				<div class="form-group">
					<label class="control-label text-muted">Descrição</label>
					<?= $this->Form->control('description', ['type' => 'textarea', 'class' => 'form-control', 'label' => false, 'rows' => 3]) ?>
				</div>
			<?php endif; ?>

			<div class="m-t-20">
				<?= $this->Form->button($isNew ? 'Criar fila' : 'Salvar', ['class' => 'btn btn-success']) ?>
				<?= $this->Html->link('Cancelar', ['action' => 'adminIndex'], ['class' => 'btn btn-secondary m-l-5']) ?>
			</div>
			<?= $this->Form->end() ?>
		</div>
	</div>
</div>
