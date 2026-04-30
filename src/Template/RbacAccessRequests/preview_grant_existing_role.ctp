<?php
$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pedidos de acesso', ['action' => 'pedidosAcesso'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pré-visualizar liberação', [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-header"><strong><?= h($title) ?></strong></div>
		<div class="card-body">
			<p><strong>Pedido:</strong> #<?= (int)$request->id ?> · <strong>Status:</strong> <?= h((string)$request->status) ?></p>
			<?php
			$roleOptions = [];
			foreach (($roles ?? []) as $r) {
				$roleOptions[(int)$r->id] = (string)$r->name;
			}
			?>
			<?= $this->Form->create(null, ['type' => 'get']) ?>
			<?= $this->Form->hidden('id', ['value' => (int)$request->id]) ?>
			<?= $this->Form->control('role_id', ['type' => 'select', 'label' => 'Papel para grant', 'options' => $roleOptions, 'empty' => 'Selecione', 'value' => (int)$roleId, 'class' => 'form-control']) ?>
			<?= $this->Form->button('Pré-visualizar', ['class' => 'btn btn-primary']) ?>
			<?= $this->Form->end() ?>

			<?php if (!empty($preview)) : ?>
				<hr>
				<p><strong>Impacto:</strong> <?= h((string)$preview['impact']) ?></p>
				<p><strong>Permissões exigidas:</strong> <code><?= h(implode(', ', (array)$preview['required_codes'])) ?></code></p>
				<p><strong>Permissões do papel:</strong> <code><?= h(implode(', ', (array)$preview['role_permission_codes'])) ?></code></p>
				<?php if (!empty($preview['missing_in_role'])) : ?>
					<p class="text-danger"><strong>Faltando no papel:</strong> <code><?= h(implode(', ', (array)$preview['missing_in_role'])) ?></code></p>
				<?php endif; ?>
				<?= $this->Form->create(null, ['url' => ['action' => 'executeGrantExistingRole', (int)$request->id]]) ?>
				<?= $this->Form->hidden('role_id', ['value' => (int)$roleId]) ?>
				<?= $this->Form->control('justification', ['type' => 'textarea', 'label' => 'Justificativa obrigatória', 'required' => true, 'class' => 'form-control']) ?>
				<?= $this->Form->button('Confirmar liberação', ['class' => 'btn btn-success', 'disabled' => empty($preview['ok'])]) ?>
				<?= $this->Form->end() ?>
			<?php endif; ?>
		</div>
	</div>
</div>

