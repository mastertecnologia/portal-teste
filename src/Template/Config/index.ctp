<?php
$this->Breadcrumbs->add('Configurações', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/dist/css/pages/config-admin-shell.css');
$this->end();
?>
<div class="col-12 p-0 admin-panel-ambient">
	<div class="admin-panel-wrap">
		<?php if (!empty($configHubPermissoesDelegate)) : ?>
		<!-- ═══ HUB DELEGADO (sem users.admin) ═══ -->
		<header class="admin-panel-hero">
			<h1>Permissões e acesso</h1>
			<p>Hub reduzido: atalhos para <strong class="admin-text-bright">RBAC / ABAC</strong> conforme as permissões do seu papel. O restante do painel administrativo continua reservado a administradores.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('<i class="fa fa-shield-alt"></i> Catálogo RBAC', ['controller' => 'Permissoes', 'action' => 'adminIndex'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
			</div>
		</header>
		<section class="admin-section">
			<h2 class="admin-section-title">Permissionamento</h2>
			<div class="admin-section-grid">
				<div class="admin-section-card admin-section-card--perm ap-card-span2">
					<span class="admin-section-card-label"><i class="fas fa-shield-alt"></i> RBAC + ABAC</span>
					<p class="admin-section-card-value">Papéis e políticas</p>
					<p class="admin-section-card-meta">Use apenas os itens para os quais foi delegado; outras rotas podem recusar acesso.</p>
					<div class="ap-link-group">
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminIndex']) ?>" class="admin-section-card-link">Catálogo e matriz</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminRoles']) ?>" class="admin-section-card-link">Papéis RBAC</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminUsers']) ?>" class="admin-section-card-link">Papéis por usuário</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminGroups']) ?>" class="admin-section-card-link">Grupos RBAC</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminPermissionPolicies']) ?>" class="admin-section-card-link">Políticas por permissão</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminFieldPermissions']) ?>" class="admin-section-card-link">Campos por permissão</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminRbacAudit']) ?>" class="admin-section-card-link">Auditoria RBAC</a>
					</div>
				</div>
			</div>
		</section>
		<?php else : ?>
		<!-- ═══ PAINEL COMPLETO (admin) ═══ -->
		<header class="admin-panel-hero">
			<div class="ap-hero-top">
				<div>
					<h1><i class="fas fa-cogs ap-hero-ico"></i> Painel administrativo</h1>
					<p>Gerencie usuários, empresas, permissões e parâmetros do sistema em um só lugar.</p>
				</div>
				<div class="ap-hero-stats">
					<div class="ap-stat">
						<span class="ap-stat-num"><?= h($nroUsuariosEquipe) ?></span>
						<span class="ap-stat-label">Equipe</span>
					</div>
					<div class="ap-stat">
						<span class="ap-stat-num"><?= h($nroUsuariosClientes) ?></span>
						<span class="ap-stat-label">Clientes</span>
					</div>
					<div class="ap-stat">
						<span class="ap-stat-num"><?= h($nroEmpresas) ?></span>
						<span class="ap-stat-label">Empresas</span>
					</div>
				</div>
			</div>
		</header>

		<!-- ── PESSOAS E ACESSOS ── -->
		<section class="admin-section">
			<h2 class="admin-section-title"><i class="fas fa-users ap-sec-ico"></i> Pessoas e acessos</h2>
			<div class="admin-section-grid ap-grid-4">
				<div class="admin-section-card ap-card-accent--teal">
					<span class="admin-section-card-label"><i class="fas fa-user-tie"></i> Equipe</span>
					<p class="admin-section-card-value"><?= h($nroUsuariosEquipe) ?> <small>usuários</small></p>
					<p class="admin-section-card-meta">Logins internos (role 0) — técnicos, gestores e admin.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'index']) ?>" class="ap-card-link-primary">Gerenciar</a>
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'add']) ?>" class="ap-card-link-add"><i class="fas fa-plus"></i> Novo</a>
					</div>
				</div>
				<div class="admin-section-card ap-card-accent--indigo">
					<span class="admin-section-card-label"><i class="fas fa-user"></i> Usuários do portal</span>
					<p class="admin-section-card-value"><?= h($nroUsuariosClientes) ?> <small>usuários</small></p>
					<p class="admin-section-card-meta">Contas de clientes (role 1) que acessam o portal.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'indexClientes']) ?>" class="ap-card-link-primary">Gerenciar</a>
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'addcliente']) ?>" class="ap-card-link-add"><i class="fas fa-plus"></i> Novo</a>
					</div>
				</div>
				<div class="admin-section-card ap-card-accent--indigo">
					<span class="admin-section-card-label"><i class="fas fa-shield-alt"></i> Permissões</span>
					<p class="admin-section-card-value">RBAC + ABAC</p>
					<p class="admin-section-card-meta">Papéis, matriz, políticas e auditoria de acesso.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminIndex']) ?>" class="ap-card-link-primary">Catálogo</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminMatrix']) ?>" class="ap-card-link-secondary">Matriz</a>
					</div>
					<div class="ap-link-group">
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminRoles']) ?>">Papéis</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminUsers']) ?>">Por usuário</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminGroups']) ?>">Grupos</a>
						<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminRbacAudit']) ?>">Auditoria</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-user-clock"></i> Requisições de acesso</span>
					<p class="admin-section-card-value">Aprovações</p>
					<p class="admin-section-card-meta">Pedidos de acesso pendentes de usuários novos.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'requisicoesAcesso']) ?>" class="ap-card-link-primary">Ver pendentes</a>
					</div>
				</div>
			</div>
		</section>

		<!-- ── ESTRUTURA ── -->
		<section class="admin-section">
			<h2 class="admin-section-title"><i class="fas fa-building ap-sec-ico"></i> Estrutura</h2>
			<div class="admin-section-grid ap-grid-3">
				<div class="admin-section-card ap-card-accent--teal">
					<span class="admin-section-card-label"><i class="fas fa-city"></i> Empresas</span>
					<p class="admin-section-card-value"><?= h($nroEmpresas) ?> <small>cadastradas</small></p>
					<p class="admin-section-card-meta">Empresas do grupo com workspaces separados.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="ap-card-link-primary">Gerenciar</a>
						<a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'add']) ?>" class="ap-card-link-add"><i class="fas fa-plus"></i> Nova</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-link"></i> Vínculos empresa / usuário</span>
					<p class="admin-section-card-value"><?= h($nroEmpresasusers) ?> <small>vínculos</small></p>
					<p class="admin-section-card-meta">Quem pode atuar em qual empresa (multi-empresa).</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>" class="ap-card-link-primary">Gerenciar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-address-book"></i> Clientes</span>
					<p class="admin-section-card-value"><?= h($nroClientes) ?> <small>cadastrados</small></p>
					<p class="admin-section-card-meta">Cadastro de clientes PF/PJ da empresa.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Clientes', 'action' => 'index']) ?>" class="ap-card-link-primary">Gerenciar</a>
					</div>
				</div>
			</div>
		</section>

		<!-- ── OPERAÇÃO ── -->
		<section class="admin-section">
			<h2 class="admin-section-title"><i class="fas fa-headset ap-sec-ico"></i> Operação e suporte</h2>
			<div class="admin-section-grid ap-grid-4">
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-layer-group"></i> Filas / técnicos</span>
					<p class="admin-section-card-value">Tickets</p>
					<p class="admin-section-card-meta">Filas de atendimento, níveis e vínculos de técnicos.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Queues', 'action' => 'adminIndex']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-exclamation-triangle"></i> Tipos de OS</span>
					<p class="admin-section-card-value">Categorias</p>
					<p class="admin-section-card-meta">Tipos de problema para ordens de serviço.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Problemas', 'action' => 'index']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-map-marker-alt"></i> Status de OS</span>
					<p class="admin-section-card-value">Etapas</p>
					<p class="admin-section-card-meta">Status e fluxo das ordens de serviço.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Areas', 'action' => 'index']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-calendar-alt"></i> Feriados</span>
					<p class="admin-section-card-value">Calendário</p>
					<p class="admin-section-card-meta">Feriados nacionais, estaduais e da empresa.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Feriados', 'action' => 'index']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
			</div>
		</section>

		<!-- ── SISTEMA ── -->
		<section class="admin-section">
			<h2 class="admin-section-title"><i class="fas fa-server ap-sec-ico"></i> Sistema</h2>
			<div class="admin-section-grid ap-grid-3">
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-sign-in-alt"></i> Login externo</span>
					<p class="admin-section-card-value">Acesso e horários</p>
					<p class="admin-section-card-meta">URL pública, e-mails de relatório e janela comercial.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'acessos']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-envelope"></i> E-mail suporte</span>
					<p class="admin-section-card-value">Destino</p>
					<p class="admin-section-card-meta">E-mail de destino para notificações de suporte.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'emailsuporte']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><i class="fas fa-folder-open"></i> Diretórios</span>
					<p class="admin-section-card-value">Paths do sistema</p>
					<p class="admin-section-card-meta">Caminhos de diretórios utilizados pelo sistema.</p>
					<div class="ap-card-actions">
						<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'pastas']) ?>" class="ap-card-link-primary">Configurar</a>
					</div>
				</div>
			</div>
		</section>
		<?php endif; ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.admin-panel-wrap a[href]').forEach(function (link) {
		link.setAttribute('data-turbo', 'false');
	});
});
</script>
