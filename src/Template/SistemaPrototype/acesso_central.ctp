<?php
/**
 * Controle de Acesso · Central — mockup pg-acesso-central.
 *
 * @var \App\View\AppView $this
 * @var array{roles:int,users_with_roles:int,access_pending:int} $rbacKpi
 */
?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
	<div>
		<div style="font-size:11px;color:var(--teal);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px;"><?= h(__('Sistema · RBAC')) ?></div>
		<h1 style="font-size:22px;font-weight:600;margin:0;">🔐 <?= h(__('Controle de Acesso')) ?></h1>
		<div style="font-size:12px;color:var(--text-muted);"><?= h(__('Papéis, atribuições e pedidos de acesso')) ?></div>
	</div>
	<div style="display:flex;gap:8px;flex-wrap:wrap;">
		<?= $this->Html->link(__('Painel IAM'), ['controller' => 'Permissoes', 'action' => 'adminIndex'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('👥 ' . __('Usuários'), ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📋 ' . __('Auditoria'), ['controller' => 'SistemaPrototype', 'action' => 'auditoria'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>

<div class="summary-grid" style="margin-bottom:14px;">
	<div class="summary-card" style="border-left:3px solid var(--teal);">
		<div class="lbl"><?= h(__('Papéis ativos')) ?></div>
		<div class="val" style="color:var(--teal-dark);"><?= (int)$rbacKpi['roles'] ?></div>
	</div>
	<div class="summary-card" style="border-left:3px solid var(--blue);">
		<div class="lbl"><?= h(__('Usuários com papel atribuído')) ?></div>
		<div class="val" style="color:#0C447C;"><?= (int)$rbacKpi['users_with_roles'] ?></div>
	</div>
	<div class="summary-card" style="background:#FAEEDA;border-left:3px solid var(--amber);">
		<div class="lbl"><?= h(__('Pedidos de acesso pendentes')) ?></div>
		<div class="val" style="color:#8A4D02;"><?= (int)$rbacKpi['access_pending'] ?></div>
	</div>
</div>

<div class="card">
	<div class="sec-title"><?= h(__('Atalhos')) ?></div>
	<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
		<?= $this->Html->link('🛡 ' . __('Papéis RBAC'), ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('⚙ ' . __('Gerenciar papéis'), ['controller' => 'Permissoes', 'action' => 'adminRoles'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🎭 ' . __('Simular acesso'), ['controller' => 'SistemaPrototype', 'action' => 'viewAs'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📨 ' . __('Pedidos de acesso'), ['controller' => 'RbacAccessRequests', 'action' => 'pedidosAcessoManager'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('📑 ' . __('Auditoria de mudanças'), ['controller' => 'SistemaPrototype', 'action' => 'auditoria'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🔍 ' . __('Auditoria RBAC'), ['controller' => 'RbacAccessRequests', 'action' => 'auditLogs'], ['class' => 'btn btn-ghost btn-sm']) ?>
		<?= $this->Html->link('🏢 ' . __('Empresas & Filiais'), ['controller' => 'EmpresasPrototype', 'action' => 'lista'], ['class' => 'btn btn-ghost btn-sm']) ?>
	</div>
</div>
