<?php
$this->Breadcrumbs->add('Configurações', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap', ['fullBase' => true]);
echo $this->Html->css('/dist/css/pages/config-admin-shell.css');
$this->end();

$iconUser = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
$iconBuilding = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>';
$iconHandshake = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M3.9 12c0-1.16.94-2.1 2.1-2.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-2.9H7c-1.16 0-2.1-.94-2.1-2.1zM8 13h8v-2H8v2zm9-6h-4v2.1h4c1.16 0 2.1.94 2.1 2.1s-.94 2.1-2.1 2.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';
$iconFolder = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
$iconWarning = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>';
$iconMarker = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
$iconEnvelope = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
$iconCalendar = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>';
$iconClock = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>';
$iconLayers = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>';
$iconShield = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>';
?>
<div class="col-12 p-0 admin-panel-ambient">
	<div class="admin-panel-wrap">
		<header class="admin-panel-hero">
			<h1>Painel administrativo</h1>
			<p>Hub central do PGM/Master: separação entre <strong class="admin-text-bright">operação interna (empresas &amp; equipe)</strong> e <strong class="admin-text-bright">portal de clientes</strong>, além de parâmetros de sistema, OS e suporte.</p>
			<div class="admin-panel-hero-actions">
				<?= $this->Html->link('<i class="fa fa-users"></i> Usuários da equipe', ['controller' => 'Users', 'action' => 'index'], ['class' => 'admin-panel-btn admin-panel-btn--teal', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-building"></i> Clientes', ['controller' => 'Clientes', 'action' => 'index'], ['class' => 'admin-panel-btn admin-panel-btn--accent', 'escape' => false]) ?>
				<?= $this->Html->link('<i class="fa fa-shield-alt"></i> RBAC / ABAC', ['controller' => 'Permissoes', 'action' => 'adminIndex'], ['class' => 'admin-panel-btn', 'escape' => false]) ?>
			</div>
		</header>

		<div class="admin-pillars">
			<div class="admin-pillar admin-pillar--operator">
				<div class="admin-pillar-head">
					<div class="admin-pillar-kicker">Pilar 1 · Operadora PGM/Master</div>
					<h2 class="admin-pillar-title">Empresas e equipe</h2>
					<p class="admin-pillar-desc">Empresas do grupo, logins internos e quem pode atuar em qual workspace (multi-empresa).</p>
				</div>
				<div class="admin-pillar-grid">
					<div class="admin-pillar-card">
						<span class="admin-pillar-card-label"><?= $iconBuilding ?> Empresas</span>
						<p class="admin-pillar-card-value"><?= h($nroEmpresas) ?></p>
						<a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="admin-pillar-card-link">Visualizar empresas</a>
					</div>
					<div class="admin-pillar-card">
						<span class="admin-pillar-card-label"><?= $iconUser ?> Usuários da equipe</span>
						<p class="admin-pillar-card-value"><?= h($nroUsuariosEquipe) ?></p>
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'index']) ?>" class="admin-pillar-card-link">Visualizar usuários</a>
					</div>
					<div class="admin-pillar-card">
						<span class="admin-pillar-card-label"><?= $iconHandshake ?> Empresa / usuário</span>
						<p class="admin-pillar-card-value"><?= h($nroEmpresasusers) ?></p>
						<a href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>" class="admin-pillar-card-link">Visualizar relações</a>
					</div>
				</div>
			</div>

			<div class="admin-pillar admin-pillar--portal">
				<div class="admin-pillar-head">
					<div class="admin-pillar-kicker">Pilar 2 · Portal do cliente</div>
					<h2 class="admin-pillar-title">Clientes e usuários do portal</h2>
					<p class="admin-pillar-desc">Cadastro de clientes e contas <em>role cliente</em> que acessam o portal (escopo ABAC por cliente/empresa).</p>
				</div>
				<div class="admin-pillar-grid">
					<div class="admin-pillar-card">
						<span class="admin-pillar-card-label"><?= $iconBuilding ?> Clientes</span>
						<p class="admin-pillar-card-value"><?= h($nroClientes) ?></p>
						<a href="<?= $this->Url->build(['controller' => 'Clientes', 'action' => 'index']) ?>" class="admin-pillar-card-link">Visualizar clientes</a>
					</div>
					<div class="admin-pillar-card">
						<span class="admin-pillar-card-label"><?= $iconUser ?> Usuários clientes</span>
						<p class="admin-pillar-card-value"><?= h($nroUsuariosClientes) ?></p>
						<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'indexClientes']) ?>" class="admin-pillar-card-link">Visualizar usuários</a>
					</div>
				</div>
			</div>
		</div>

		<section class="admin-section">
			<h2 class="admin-section-title">Sistema, segurança e acesso</h2>
			<div class="admin-section-grid">
				<div class="admin-section-card admin-section-card--perm">
					<span class="admin-section-card-label"><?= $iconShield ?> Permissionamento</span>
					<p class="admin-section-card-value">RBAC + ABAC</p>
					<p class="admin-section-card-meta">Catálogo de telas/funções, papéis e matriz. Escopos: empresa, cliente, próprio.</p>
					<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminIndex']) ?>" class="admin-section-card-link">Catálogo e matriz</a>
					<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminUsers']) ?>" class="admin-section-card-link admin-section-card-link--stack">Papéis por usuário</a>
					<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminGroups']) ?>" class="admin-section-card-link admin-section-card-link--stack">Grupos RBAC</a>
					<a href="<?= $this->Url->build(['controller' => 'Permissoes', 'action' => 'adminRbacAudit']) ?>" class="admin-section-card-link admin-section-card-link--stack">Auditoria RBAC</a>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconFolder ?> Login externo</span>
					<p class="admin-section-card-value">URL e horários</p>
					<p class="admin-section-card-meta">Acesso público, e-mails de relatório e janela comercial.</p>
					<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'acessos']) ?>" class="admin-section-card-link">Configurações de acesso externo</a>
				</div>
			</div>
		</section>

		<section class="admin-section">
			<h2 class="admin-section-title">Parâmetros — Ordens de serviço</h2>
			<div class="admin-section-grid">
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconWarning ?> Tipos</span>
					<p class="admin-section-card-value">Tipo de OS</p>
					<a href="<?= $this->Url->build(['controller' => 'Problemas', 'action' => 'index']) ?>" class="admin-section-card-link">Configurar tipos</a>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconMarker ?> Status</span>
					<p class="admin-section-card-value">Status de OS</p>
					<a href="<?= $this->Url->build(['controller' => 'Areas', 'action' => 'index']) ?>" class="admin-section-card-link">Configurar status</a>
				</div>
			</div>
		</section>

		<section class="admin-section">
			<h2 class="admin-section-title">Suporte e operação</h2>
			<div class="admin-section-grid">
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconLayers ?> Filas / técnicos</span>
					<p class="admin-section-card-value">Tickets</p>
					<a href="<?= $this->Url->build(['controller' => 'Queues', 'action' => 'adminIndex']) ?>" class="admin-section-card-link">Gerenciar filas, níveis e vínculos</a>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconEnvelope ?> E-mail</span>
					<p class="admin-section-card-value">Suporte</p>
					<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'emailsuporte']) ?>" class="admin-section-card-link">E-mail de destino</a>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconCalendar ?> Visitas</span>
					<p class="admin-section-card-value">Agenda</p>
					<a href="<?= $this->Url->build(['controller' => 'Visitas', 'action' => 'calendario']) ?>" class="admin-section-card-link">Visitas da empresa</a>
				</div>
				<div class="admin-section-card">
					<span class="admin-section-card-label"><?= $iconClock ?> Feriados</span>
					<p class="admin-section-card-value">Horário especial</p>
					<a href="<?= $this->Url->build(['controller' => 'Feriados', 'action' => 'index']) ?>" class="admin-section-card-link">Cadastrar feriados</a>
				</div>
			</div>
		</section>
	</div>
</div>
