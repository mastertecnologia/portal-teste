<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	$nameTrim = trim((string)($name ?? ''));
	$partsName = $nameTrim !== '' ? preg_split('/\s+/', $nameTrim, -1, PREG_SPLIT_NO_EMPTY) : [];
	$u0 = $partsName[0] ?? '';
	$u1 = $partsName[1] ?? '';
	$userInitials = '';
	if ($u0 !== '') {
		$userInitials .= strtoupper($u0[0]);
	}
	if ($u1 !== '') {
		$userInitials .= strtoupper($u1[0]);
	} elseif (strlen($u0) > 1) {
		$userInitials = strtoupper(substr($u0, 0, 2));
	}
?>
<style>
/* ── Sidebar submenu orcamentos ──────────────────────────────── */
.pgm-sidebar-shell .has-arrow-sub > ul.collapse,
.pgm-sidebar-shell .has-arrow-sub > ul.collapsing{
	padding:4px 0 6px 0;
	margin:0;
	list-style:none;
}
.pgm-sidebar-shell .has-arrow-sub > ul > li > a{
	display:flex;
	align-items:center;
	gap:8px;
	padding:7px 14px 7px 42px;
	font-size:.78rem;
	font-weight:500;
	color:var(--pgm-sb-muted, #9a9890);
	text-decoration:none;
	border-radius:6px;
	margin:1px 8px;
	transition:background .15s,color .15s;
	white-space:nowrap;
	overflow:hidden;
	text-overflow:ellipsis;
}
.pgm-sidebar-shell .has-arrow-sub > ul > li > a:before{
	content:'';
	width:5px;height:5px;
	border-radius:50%;
	background:rgba(255,255,255,.12);
	flex-shrink:0;
	transition:background .15s;
}
.pgm-sidebar-shell .has-arrow-sub > ul > li > a:hover{
	color:var(--pgm-sb-text, #f5f5f3);
	background:rgba(255,255,255,.06);
}
.pgm-sidebar-shell .has-arrow-sub > ul > li > a:hover:before{
	background:var(--pgm-teal, #00c08b);
}
.pgm-sidebar-shell .has-arrow-sub > ul > li.active > a,
.pgm-sidebar-shell .has-arrow-sub > ul > li > a.active{
	color:var(--pgm-teal-light, #5cdbc0);
	background:var(--pgm-teal-dim, rgba(0,192,139,.14));
}
.pgm-sidebar-shell .has-arrow-sub > ul > li.active > a:before{
	background:var(--pgm-teal, #00c08b);
}
/* Linha separadora acima do submenu */
.pgm-sidebar-shell .has-arrow-sub.active > a,
.pgm-sidebar-shell .has-arrow-sub.selected > a{
	color:var(--pgm-sb-text, #f5f5f3);
}
/* mini-sidebar: esconder texto do submenu mas manter ícone */
.mini-sidebar .pgm-sidebar-shell .has-arrow-sub > ul{ display:none!important; }
</style>
<aside class="left-sidebar skin-pgm pgm-sidebar-shell">
	<div class="pgm-sidebar-brand">
		<?= $this->Html->link(
			'<div class="pgm-sidebar-mark">PGM</div><div class="pgm-sidebar-titles hide-menu"><strong>PGM Soluções em TI</strong><div class="pgm-sidebar-sub">ERP Enterprise</div></div>',
			['controller' => 'Users', 'action' => 'dashboard'],
			['class' => 'pgm-sidebar-logo-link navbar-brand', 'escape' => false]
		) ?>
	</div>

	<div class="pgm-sidebar-top-tools">
		<?= $this->element('pgm_theme_toggle_icon') ?>
	</div>

	<div class="pgm-sidebar-meta">
		<div class="pgm-meta-row">
			<div class="pgm-sidebar-flex-min">
				<label>Empresa</label>
				<?php if (count($empresasOptSidebar) > 1) : ?>
					<?= $this->Form->control('empresaSidebar', [
						'id' => 'empresaSidebar',
						'class' => 'form-control pgm-empresa-select',
						'label' => false,
						'value' => $empresa,
						'options' => $empresasOptSidebar,
					]) ?>
				<?php else : ?>
					<p class="pgm-meta-date m-0"><?= h($empresasOptSidebar[$empresa] ?? $nomeempresa ?? '') ?></p>
					<?= $this->Form->hidden('empresaSidebar', ['id' => 'empresaSidebar', 'value' => $empresa]); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav" class="p-t-30">
				<li class="pgm-nav-section-label" aria-hidden="true"><span>Menu</span></li>
				<?php if (!empty($permissaoacesso)) : ?>
					<?php
						$controllerCli = strtolower((string)$this->request->getParam('controller'));
						$actionCli = strtolower((string)$this->request->getParam('action'));
						$relatoriosCliActive = ($controllerCli === 'portalrelatorios' && $actionCli === 'index') ? 'active' : '';
					?>
					<li class="<?= $dashboard ?>"><?= $this->Html->link('<i class="fa fa-columns"></i><span class="hide-menu">Dashboard</span>', '/users/dashboard', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
					<li class="<?= $clientesActive ?>"><?= $this->Html->link('<i class="fa fa-building"></i><span class="hide-menu">Empresa</span>', "/clientes/edit/$idcliente", ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
					<li class="<?= $orcamentosActive ?> has-arrow-sub">
						<a href="javascript:void(0)" class="waves-effect waves-dark has-arrow" aria-expanded="<?= !empty($orcamentosActive) ? 'true' : 'false' ?>">
							<i class="fa fa-file-invoice-dollar"></i>
							<span class="hide-menu">Orçamentos</span>
						</a>
						<ul class="collapse <?= !empty($orcamentosActive) ? 'in' : '' ?>">
							<li><?= $this->Html->link('Meus Orçamentos', '/orcamentos/index', ['class' => 'waves-effect waves-dark']) ?></li>
							<?php if (!empty($canClienteSolicitarOrcamento)) : ?>
							<li><?= $this->Html->link('Solicitar Orçamento', '/orcamentos/solicitar', ['class' => 'waves-effect waves-dark']) ?></li>
							<?php endif; ?>
						</ul>
					</li>
					<li class="<?= h($relatoriosCliActive) ?>"><?= $this->Html->link('<i class="fa fa-chart-bar"></i><span class="hide-menu">Relatórios</span>', '/cliente/relatorios', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
					<?php
						$advCtrls = ['portalcontratos', 'portaladvancedcontracts', 'portaladvancedinvoices'];
						$portalAdvMenuActive = in_array($controllerCli, $advCtrls, true) ? 'active' : '';
						$advContrLi = (in_array($controllerCli, ['portalcontratos', 'portaladvancedcontracts'], true) && $actionCli !== 'franquia') ? 'active' : '';
						$advFranqLi = (in_array($controllerCli, ['portalcontratos', 'portaladvancedcontracts'], true) && $actionCli === 'franquia') ? 'active' : '';
						$advInvLi = ($controllerCli === 'portaladvancedinvoices') ? 'active' : '';
					?>
					<li class="<?= h($portalAdvMenuActive) ?> has-arrow-sub <?= !empty($portalAdvMenuActive) ? 'selected' : '' ?>">
						<a href="javascript:void(0)" class="waves-effect waves-dark has-arrow" aria-expanded="<?= !empty($portalAdvMenuActive) ? 'true' : 'false' ?>">
							<i class="fa fa-layer-group"></i>
							<span class="hide-menu">Contratos &amp; faturas</span>
						</a>
						<ul class="collapse <?= !empty($portalAdvMenuActive) ? 'in' : '' ?>">
							<li class="<?= h($advContrLi) ?>"><?= $this->Html->link('Contratos', '/cliente/contratos', ['class' => 'waves-effect waves-dark']) ?></li>
							<li class="<?= h($advFranqLi) ?>"><?= $this->Html->link('Franquia de horas', '/cliente/contratos/franquia', ['class' => 'waves-effect waves-dark']) ?></li>
							<li class="<?= h($advInvLi) ?>"><?= $this->Html->link('Faturas', '/cliente/faturas-avancadas', ['class' => 'waves-effect waves-dark']) ?></li>
						</ul>
					</li>
				<?php endif; ?>
					<?php
						$ticketsCliAction = $this->request->getParam('action');
						$ticketsCtrlLower = strtolower((string)$this->request->getParam('controller'));
						$ticketsSubIndexActive = ($ticketsCliAction === 'indexcliente') ? 'active' : '';
						$ticketsSubAddActive = ($ticketsCliAction === 'add') ? 'active' : '';
						$ticketsSubHistAdvActive = ($ticketsCtrlLower === 'portaladvancedattendance') ? 'active' : '';
					?>
					<li class="<?= $ticketsActive ?> has-arrow-sub <?= !empty($ticketsActive) ? 'selected' : '' ?>">
						<a href="javascript:void(0)" class="waves-effect waves-dark has-arrow" aria-expanded="<?= !empty($ticketsActive) ? 'true' : 'false' ?>">
							<i class="fa fa-ticket-alt"></i>
							<span class="hide-menu">Tickets</span>
						</a>
						<ul class="collapse <?= !empty($ticketsActive) ? 'in' : '' ?>">
							<li class="<?= h($ticketsSubIndexActive) ?>"><?= $this->Html->link('Meus tickets', '/tickets/indexcliente', ['class' => 'waves-effect waves-dark']) ?></li>
							<li class="<?= h($ticketsSubAddActive) ?>"><?= $this->Html->link('Abrir chamado', '/tickets/add', ['class' => 'waves-effect waves-dark']) ?></li>
							<li class="<?= h($ticketsSubHistAdvActive) ?>"><?= $this->Html->link('Histórico de atendimento', '/cliente/historico-atendimento-avancado', ['class' => 'waves-effect waves-dark']) ?></li>
						</ul>
					</li>
				<li id="mini-logout" class="<?= $sidebar != 'mini-sidebar' ? 'd-none' : '' ?>"><?= $this->Html->link('<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>', '/users/logout', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
			</ul>
		</nav>
	</div>

	<div class="pgm-sidebar-footer">
		<div class="pgm-sidebar-collapse-row">
			<a href="javascript:void(0)" class="sidebartoggler pgm-sidebar-collapse-btn" title="Recolher menu" aria-label="Recolher menu lateral"><i class="ti-angle-double-left"></i></a>
		</div>
		<div class="user-profile">
			<div class="user-pro-body">
				<div class="dropdown dropup">
					<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link hide-menu text-white d-flex align-items-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<span class="pgm-user-av"><?= h($userInitials ?: '?') ?></span>
						<span class="hide-menu text-truncate pgm-cli-name-truncate"><?= h($name) ?></span>
						<span class="caret hide-menu"></span>
					</a>
					<div class="dropdown-menu animated flipInY">
						<?= $this->Html->link('<i class="fas fa-user"></i> Meu Perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?= $this->Html->link('<i class="fa fa-lock"></i> Alterar Senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<?= $this->Html->link('<i class="ti-lock"></i> Verificação em 2 etapas', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item', 'escape' => false]); ?>
						<div class="dropdown-divider"></div>
						<?= $this->Html->link('<i class="fa fa-power-off text-danger"></i> Sair', ['controller' => 'Users', 'action' => 'logout'], ['class' => 'dropdown-item', 'escape' => false]); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</aside>
<script>
	if (window.PgmPortalTheme) {
		PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
	}
</script>
