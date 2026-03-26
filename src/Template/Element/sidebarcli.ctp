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
<aside class="left-sidebar skin-pgm pgm-sidebar-shell">
	<div class="pgm-sidebar-brand">
		<a href="javascript:void(0)" class="nav-toggler d-block d-md-none waves-effect waves-dark pgm-sidebar-toggler" aria-label="Abrir menu">
			<i class="ti-menu"></i>
		</a>
		<a href="javascript:void(0)" class="sidebartoggler d-none d-md-block waves-effect waves-dark pgm-sidebar-toggler" aria-label="Recolher menu">
			<i class="icon-menu"></i>
		</a>
		<?= $this->Html->link(
			'<div class="pgm-sidebar-mark">PGM</div><div class="pgm-sidebar-titles hide-menu"><strong>PGM Soluções</strong><div class="pgm-sidebar-sub">ERP Enterprise</div></div>',
			['controller' => 'Users', 'action' => 'dashboard'],
			['class' => 'pgm-sidebar-logo-link navbar-brand', 'escape' => false]
		) ?>
	</div>

	<div class="pgm-sidebar-meta">
		<div class="pgm-meta-row">
			<div style="flex:1;min-width:0">
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
					<p class="pgm-meta-date m-0"><?= h(EmpresaNome($empresa)) ?></p>
					<?= $this->Form->hidden('empresaSidebar', ['id' => 'empresaSidebar', 'value' => $empresa]); ?>
				<?php endif; ?>
			</div>
			<div>
				<small>Data</small>
				<p class="pgm-meta-date"><?= h(date('d/m/Y')) ?></p>
			</div>
		</div>
	</div>

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav" class="p-t-30">
				<li class="pgm-nav-section-label" aria-hidden="true"><span>Menu</span></li>
				<?php if (!empty($permissaoacesso)) : ?>
					<li class="<?= $dashboard ?>"><?= $this->Html->link('<i class="fa fa-columns"></i><span class="hide-menu">Dashboard</span>', '/users/dashboard', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
					<li class="<?= $clientesActive ?>"><?= $this->Html->link('<i class="fa fa-building"></i><span class="hide-menu">Empresa</span>', "/clientes/edit/$idcliente", ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
					<li class="<?= $orcamentosActive ?> has-arrow-sub">
						<a href="javascript:void(0)" class="waves-effect waves-dark has-arrow" aria-expanded="<?= !empty($orcamentosActive) ? 'true' : 'false' ?>">
							<i class="fa fa-file-invoice-dollar"></i>
							<span class="hide-menu">Orçamentos</span>
						</a>
						<ul class="collapse <?= !empty($orcamentosActive) ? 'in' : '' ?>">
							<li><?= $this->Html->link('Meus Orçamentos', '/orcamentos/index', ['class' => 'waves-effect waves-dark']) ?></li>
							<li><?= $this->Html->link('<i class="ti-plus" style="font-size:.7rem;margin-right:4px;"></i> Solicitar Orçamento', '/orcamentos/solicitar', ['class' => 'waves-effect waves-dark', 'escape' => false]) ?></li>
						</ul>
					</li>
				<?php endif; ?>
				<li class="<?= $ticketsActive ?>"><?= $this->Html->link('<i class="fa fa-ticket-alt"></i><span class="hide-menu">Tickets</span>', '/tickets/indexcliente', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
				<?php $display = $sidebar != 'mini-sidebar' ? 'none' : ''; ?>
				<li id="mini-logout" style="display:<?= $display ?>;"><?= $this->Html->link('<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>', '/users/logout', ['class' => 'waves-effect waves-dark', 'aria-expanded' => 'false', 'escape' => false]); ?></li>
			</ul>
		</nav>
	</div>

	<div class="pgm-sidebar-footer">
		<div class="user-profile">
			<div class="user-pro-body">
				<div class="dropdown dropup">
					<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link hide-menu text-white d-flex align-items-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<span class="pgm-user-av"><?= h($userInitials ?: '?') ?></span>
						<span class="hide-menu text-truncate" style="max-width:140px"><?= h($name) ?></span>
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
