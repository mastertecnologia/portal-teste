<?php
use Cake\Routing\Router;
$this->Breadcrumbs->add('Configurações', [], ['class' => 'breadcrumb-item active']);

$this->start('css');
echo $this->Html->css('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', ['fullBase' => true]);
echo $this->Html->css('dist/css/config-erp');
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
?>
<div class="col-12">
<div class="config-erp-wrap">
	<header class="config-erp-header">
		<h1 class="config-erp-title">Configurações</h1>
		<p class="config-erp-subtitle">Gestão PGM/Master, clientes, sistema e parâmetros.</p>
	</header>

	<!-- PGM / Master: empresas operadoras e equipe -->
	<section class="config-erp-section">
		<h2 class="config-erp-section-title">PGM / Master — Empresas e equipe</h2>
		<div class="config-erp-grid">
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconBuilding ?></span> Empresas</span>
				<p class="config-erp-card-value"><?= h($nroEmpresas) ?></p>
				<a href="<?= $this->Url->build(['controller' => 'Empresas', 'action' => 'index']) ?>" class="config-erp-card-link">Visualizar empresas</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconUser ?></span> Usuários da equipe</span>
				<p class="config-erp-card-value"><?= h($nroUsuariosEquipe) ?></p>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'index']) ?>" class="config-erp-card-link">Visualizar usuários</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconHandshake ?></span> Empresa / Usuário</span>
				<p class="config-erp-card-value"><?= h($nroEmpresasusers) ?></p>
				<a href="<?= $this->Url->build(['controller' => 'Empresasusers', 'action' => 'index']) ?>" class="config-erp-card-link">Visualizar relações</a>
			</div>
		</div>
	</section>

	<!-- Clientes e usuários clientes -->
	<section class="config-erp-section">
		<h2 class="config-erp-section-title">Clientes e usuários do portal</h2>
		<div class="config-erp-grid">
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconBuilding ?></span> Clientes</span>
				<p class="config-erp-card-value"><?= h($nroClientes) ?></p>
				<a href="<?= $this->Url->build(['controller' => 'Clientes', 'action' => 'index']) ?>" class="config-erp-card-link">Visualizar clientes</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconUser ?></span> Usuários clientes</span>
				<p class="config-erp-card-value"><?= h($nroUsuariosClientes) ?></p>
				<a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'indexClientes']) ?>" class="config-erp-card-link">Visualizar usuários</a>
			</div>
		</div>
	</section>

	<!-- Sistema e acesso -->
	<section class="config-erp-section">
		<h2 class="config-erp-section-title">Sistema e acesso</h2>
		<div class="config-erp-grid">
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconFolder ?></span> Login externo</span>
				<p class="config-erp-card-value">Acesso</p>
				<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'acessos']) ?>" class="config-erp-card-link">Configurações de acesso externo</a>
			</div>
		</div>
	</section>

	<!-- Parâmetros de OS -->
	<section class="config-erp-section">
		<h2 class="config-erp-section-title">Parâmetros — Ordens de serviço</h2>
		<div class="config-erp-grid">
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconWarning ?></span> Tipos</span>
				<p class="config-erp-card-value">Tipo de OS</p>
				<a href="<?= $this->Url->build(['controller' => 'Problemas', 'action' => 'index']) ?>" class="config-erp-card-link">Configurar tipos</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconMarker ?></span> Status</span>
				<p class="config-erp-card-value">Status de OS</p>
				<a href="<?= $this->Url->build(['controller' => 'Areas', 'action' => 'index']) ?>" class="config-erp-card-link">Configurar status</a>
			</div>
		</div>
	</section>

	<!-- Suporte e operação -->
	<section class="config-erp-section">
		<h2 class="config-erp-section-title">Suporte e operação</h2>
		<div class="config-erp-grid">
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconLayers ?></span> Filas / técnicos</span>
				<p class="config-erp-card-value">Tickets</p>
				<a href="<?= $this->Url->build(['controller' => 'Queues', 'action' => 'adminIndex']) ?>" class="config-erp-card-link">Gerenciar filas, níveis e vínculos</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconEnvelope ?></span> E-mail</span>
				<p class="config-erp-card-value">Suporte</p>
				<a href="<?= $this->Url->build(['controller' => 'Config', 'action' => 'emailsuporte']) ?>" class="config-erp-card-link">E-mail de destino</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconCalendar ?></span> Visitas</span>
				<p class="config-erp-card-value">Visitas</p>
				<a href="<?= $this->Url->build(['controller' => 'Visitas', 'action' => 'calendario']) ?>" class="config-erp-card-link">Visitas da empresa</a>
			</div>
			<div class="config-erp-card">
				<span class="config-erp-card-category"><span class="config-erp-icon"><?= $iconClock ?></span> Feriados</span>
				<p class="config-erp-card-value">Horário especial</p>
				<a href="<?= $this->Url->build(['controller' => 'Feriados', 'action' => 'index']) ?>" class="config-erp-card-link">Cadastrar feriados</a>
			</div>
		</div>
	</section>
</div>
</div>
