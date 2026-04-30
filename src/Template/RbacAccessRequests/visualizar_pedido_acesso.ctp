<?php
$privilegedRequestView = !empty($privilegedRequestView);
$canActAsManager = !empty($canActAsManager);
$canActAsAdmin = !empty($canActAsAdmin);
$canPreviewGrant = !empty($canPreviewGrant);
$breadcrumbParent = !$privilegedRequestView ? 'meusPedidosAcesso' : 'pedidosAcesso';

$this->Breadcrumbs->add('Configurações', ['controller' => 'Config', 'action' => 'index'], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pedidos de acesso', ['action' => $breadcrumbParent], ['class' => 'breadcrumb-item']);
$this->Breadcrumbs->add('Pedido #' . (int)$row->id, [], ['class' => 'breadcrumb-item active']);
?>
<div class="col-md-12">
	<div class="card">
		<div class="card-header">
			<strong><?= h($title) ?></strong>
		</div>
		<div class="card-body">
			<p><strong>Usuário:</strong> <?= (int)$row->user_id ?> · <strong>Suporte:</strong> <code><?= h($row->support_code) ?></code></p>
			<p><strong>Rota:</strong> <code><?= h($row->controller) ?>#<?= h($row->action) ?></code> · <strong>Motivo:</strong> <?= h((string)$row->reason) ?></p>
			<p><strong>Status:</strong> <?= h((string)$row->status) ?></p>
			<?php if (!empty($row->requester_message)) : ?>
				<p><strong>Mensagem do solicitante:</strong> <?= h($row->requester_message) ?></p>
			<?php endif; ?>

			<?php if ($privilegedRequestView && $diag !== null && is_array($diag)) : ?>
				<hr>
				<h4>Diagnóstico para revisão admin</h4>
				<p><strong>Permissões possíveis:</strong> <code><?= h((string)($diag['required_permissions_or_label'] ?? '')) ?></code></p>
				<p><strong>Papéis atuais:</strong>
					<?php foreach (($diag['user_roles'] ?? []) as $r) : ?>
						<code><?= h($r['name']) ?></code>
					<?php endforeach; ?>
				</p>
				<p><strong>Sugestões:</strong></p>
				<ul>
					<?php foreach (($diag['suggestions'] ?? []) as $s) : ?>
						<li><?= h($s) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if (!$privilegedRequestView) : ?>
				<hr>
				<p class="small text-muted">Acompanhe o status do seu pedido. Detalhes técnicos são visíveis apenas para administradores autorizados.</p>
			<?php endif; ?>

			<?php if ($canActAsManager && (string)$row->status === 'pending_manager') : ?>
				<hr>
				<div class="row">
					<div class="col-md-6">
						<?= $this->Form->create(null, ['url' => ['action' => 'aprovarManagerPedidoAcesso', $row->id]]) ?>
						<?= $this->Form->control('manager_response', ['label' => 'Resposta manager (aprovação)', 'type' => 'textarea', 'class' => 'form-control']) ?>
						<?= $this->Form->button('Aprovar (manager → admin)', ['class' => 'btn btn-success']) ?>
						<?= $this->Form->end() ?>
					</div>
					<div class="col-md-6">
						<?= $this->Form->create(null, ['url' => ['action' => 'rejeitarManagerPedidoAcesso', $row->id]]) ?>
						<?= $this->Form->control('manager_response', ['label' => 'Resposta manager (rejeição)', 'type' => 'textarea', 'class' => 'form-control']) ?>
						<?= $this->Form->button('Rejeitar (manager)', ['class' => 'btn btn-danger']) ?>
						<?= $this->Form->end() ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ($canActAsAdmin && in_array((string)$row->status, ['pending_admin', 'manager_approved'], true)) : ?>
				<hr>
				<div class="row">
					<div class="col-md-6">
						<?= $this->Form->create(null, ['url' => ['action' => 'aprovarAdminPedidoAcesso', $row->id]]) ?>
						<?= $this->Form->control('admin_response', ['label' => 'Resposta admin (aprovação)', 'type' => 'textarea', 'class' => 'form-control']) ?>
						<?= $this->Form->button('Aprovar (admin)', ['class' => 'btn btn-success']) ?>
						<?= $this->Form->end() ?>
					</div>
					<div class="col-md-6">
						<?= $this->Form->create(null, ['url' => ['action' => 'rejeitarAdminPedidoAcesso', $row->id]]) ?>
						<?= $this->Form->control('admin_response', ['label' => 'Resposta admin (rejeição)', 'type' => 'textarea', 'class' => 'form-control']) ?>
						<?= $this->Form->button('Rejeitar (admin)', ['class' => 'btn btn-danger']) ?>
						<?= $this->Form->end() ?>
					</div>
				</div>
			<?php endif; ?>
			<?php if ($canPreviewGrant && (string)$row->status === 'admin_approved') : ?>
				<hr>
				<p><?= $this->Html->link('Pré-visualizar liberação', ['action' => 'previewGrantExistingRole', $row->id], ['class' => 'btn btn-warning']) ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

