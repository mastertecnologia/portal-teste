<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	$ctrl = $this->request->getParam('controller');
	$act = $this->request->getParam('action');
	$osOpen = ($ctrl === 'Ordensservico');
	$osIndexActive = ($ctrl === 'Ordensservico' && $act === 'index');
	$osAddActive = ($ctrl === 'Ordensservico' && $act === 'add');
	$roleNav = (int)($role ?? 1);

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

	$multiEmpresa = count($empresasOptSidebar ?? []) > 1;
?>
<aside class="left-sidebar skin-pgm pgm-sidebar-shell">

	<!-- ── Brand ──────────────────────────────────────────── -->
	<div class="pgm-sidebar-brand">
		<?= $this->Html->link(
			'<div class="pgm-sidebar-mark">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
			<div class="pgm-sidebar-titles hide-menu">
				<strong>PGM Soluções em TI</strong>
				<div class="pgm-sidebar-sub">ERP Enterprise</div>
			</div>',
			['controller' => 'Users', 'action' => 'dashboard'],
			['class' => 'pgm-sidebar-logo-link navbar-brand', 'escape' => false]
		) ?>
	</div>

	<!-- ── Workspace / Empresa ────────────────────────────── -->
	<div class="pgm-sidebar-workspace">
		<div class="pgm-ws-icon hide-menu" aria-hidden="true">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
				<path d="M3 21h18M3 7l9-4 9 4M4 7v14M20 7v14M9 21v-5h6v5M8 11h1m-1 4h1m6-4h1m-1 4h1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</div>
		<div class="hide-menu" style="flex:1;min-width:0;">
			<div class="pgm-ws-label">Empresa</div>
			<?= $this->Form->control('empresaSidebar', [
				'id' => 'empresaSidebar',
				'class' => 'form-control pgm-empresa-select' . (!$multiEmpresa ? ' pgm-empresa-single' : ''),
				'label' => false,
				'value' => $empresa,
				'options' => $empresasOptSidebar,
				'readonly' => !$multiEmpresa,
			]) ?>
		</div>
	</div>

	<!-- ── Busca de funções ───────────────────────────────── -->
	<div class="pgm-sb-search-block">
		<div class="pgm-sidebar-functions-search" id="pgm-sidebar-functions-search">
			<div class="pgm-sb-sbox">
				<svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true" style="color:#555e78;flex-shrink:0">
					<circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
					<path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
				<input type="text" id="pesquisa-funcoes" autocomplete="off" placeholder="Buscar funções…" />
			</div>
			<ul class="pgm-sb-typeahead htmlpesquisa list-unstyled m-0 p-0"></ul>
		</div>
		<div id="pgm-sidebar-dt-host" class="pgm-sidebar-dt-host" style="display:none" aria-label="Busca na listagem"></div>
	</div>

	<!-- ── Navegação ──────────────────────────────────────── -->
	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav">

				<li class="pgm-nav-section-label" aria-hidden="true"><span>Menu principal</span></li>

				<li class="<?= $dashboard ?>">
					<?= $this->Html->link(
						'<i class="fa fa-columns"></i><span class="hide-menu"> Dashboard </span>',
						['controller' => 'Users', 'action' => 'dashboard'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Dashboard', 'escape' => false]
					) ?>
				</li>
				<li class="<?= $clientesActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-building"></i><span class="hide-menu"> Clientes </span>',
						['controller' => 'Clientes', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Clientes', 'escape' => false]
					) ?>
				</li>
				<li class="<?= $produtosActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-boxes"></i><span class="hide-menu"> Produtos </span>',
						['controller' => 'Produtos', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Produtos', 'escape' => false]
					) ?>
				</li>

				<!-- Ordens de Serviço (collapsible) -->
				<li class="pgm-ng <?= h($ordensActive) ?> <?= $osOpen ? 'open' : '' ?>">
					<div class="pgm-np <?= $osOpen ? 'open-p' : '' ?>" role="button" tabindex="0" aria-expanded="<?= $osOpen ? 'true' : 'false' ?>">
						<i class="fas fa-file-signature ni-ico-fa"></i>
						<span class="hide-menu">Ordens de Serviço</span>
						<?php if ($osIndexActive) : ?>
							<span class="pgm-os-badge hide-menu" id="badge-exec-os">—</span>
						<?php endif; ?>
						<svg class="pgm-chevron hide-menu" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<ul class="pgm-nc list-unstyled">
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Listar Ordens</span>',
							['controller' => 'Ordensservico', 'action' => 'index'],
							['class' => 'pgm-nch ' . ($osIndexActive ? 'act' : ''), 'escape' => false]
						) ?></li>
						<?php if ($roleNav === 0) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Nova ordem</span>',
							['controller' => 'Ordensservico', 'action' => 'add'],
							['class' => 'pgm-nch pgm-nch-nova ' . ($osAddActive ? 'act' : ''), 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']
						) ?></li>
						<?php endif; ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Relatórios</span>',
							['controller' => 'Ordensservico', 'action' => 'imprimirordens'],
							['class' => 'pgm-nch', 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']
						) ?></li>
					</ul>
				</li>

				<li class="<?= $ticketsActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-ticket-alt"></i><span class="hide-menu"> Tickets </span>',
						['controller' => 'Servicedesk', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Tickets', 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']
					) ?>
				</li>

				<li class="pgm-nav-section-label" aria-hidden="true"><span>Operações</span></li>

				<?php if (!empty($admin)) : ?>
				<li class="<?= $queuesAtendimentoActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-layer-group"></i><span class="hide-menu"> Filas / técnicos </span>',
						['controller' => 'Queues', 'action' => 'adminIndex'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>

				<li class="<?= $orcamentosActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-file-invoice-dollar"></i><span class="hide-menu"> Orçamentos </span>',
						['controller' => 'Orcamentos', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<li class="<?= $faturasActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-file-invoice"></i><span class="hide-menu"> Locação </span>',
						['controller' => 'Faturas', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<li class="<?= $visitasActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-calendar"></i><span class="hide-menu"> Agenda </span>',
						['controller' => 'Agenda', 'action' => 'calendario'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<li class="<?= $senhasActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-lock"></i><span class="hide-menu"> Banco de Senhas </span>',
						['controller' => 'Bancosenhas', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>

				<?php $display = $sidebar != 'mini-sidebar' ? 'none' : ''; ?>
				<li id="mini-logout" style="display:<?= $display ?>;">
					<?= $this->Html->link(
						'<i class="far fa-circle text-danger"></i><span class="hide-menu">Sair</span>',
						'/users/logout',
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
			</ul>
		</nav>
	</div>

	<!-- ── Footer usuário ─────────────────────────────────── -->
	<div class="pgm-sidebar-footer">
		<div class="pgm-sidebar-collapse-row">
			<a href="javascript:void(0)" class="sidebartoggler pgm-sidebar-collapse-btn" title="Recolher menu" aria-label="Recolher menu lateral"><i class="ti-angle-double-left"></i></a>
		</div>
		<!-- Ações rápidas (visíveis apenas sidebar expandida) -->
		<div class="pgm-sf-actions hide-menu">
			<?= $this->Html->link(
				'<i class="fas fa-user-cog"></i>',
				['controller' => 'Users', 'action' => 'change_profile'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Meu perfil']
			) ?>
			<?= $this->Html->link(
				'<i class="fa fa-lock"></i>',
				['controller' => 'Users', 'action' => 'change_password'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Alterar senha']
			) ?>
			<?php if (!empty($admin)) : ?>
			<?= $this->Html->link(
				'<i class="ti-settings"></i>',
				['controller' => 'config', 'action' => 'index'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Painel Administrativo']
			) ?>
			<?php endif; ?>
			<?= $this->Html->link(
				'<i class="fa fa-power-off"></i>',
				['controller' => 'Users', 'action' => 'logout'],
				['class' => 'pgm-sf-act pgm-sf-act-danger', 'escape' => false, 'title' => 'Sair']
			) ?>
		</div>

		<!-- Avatar + nome + dropdown -->
		<div class="user-profile">
			<div class="user-pro-body">
				<div class="dropdown dropup">
					<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link hide-menu text-white d-flex align-items-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<span class="pgm-user-av"><?= h($userInitials ?: '?') ?></span>
						<div class="pgm-sf-user-info hide-menu">
							<span class="pgm-sf-user-name"><?= h($name) ?></span>
							<span class="pgm-sf-user-role"><?= !empty($admin) ? 'Administrador' : 'Usuário' ?></span>
						</div>
						<span class="caret hide-menu" style="margin-left:auto;"></span>
					</a>
					<div class="dropdown-menu animated flipInY">
						<?= $this->Html->link('<i class="fas fa-user"></i> Alterar Perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?= $this->Html->link('<i class="fa fa-lock"></i> Alterar Senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?= $this->Html->link('<i class="ti-rss-alt"></i> Acesso Remoto', ['controller' => 'normasempresa', 'action' => 'acessoremoto'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?= $this->Html->link('<i class="ti-lock"></i> Verificação login', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?php if (!empty($admin)) {
							echo $this->Html->link('<i class="ti-settings"></i> Painel Administrativo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item', 'escape' => false]);
						} ?>
						<?= $this->Html->link('<i class="fa fa-power-off"></i> Logout', ['controller' => 'Users', 'action' => 'logout'], ['class' => 'dropdown-item', 'escape' => false]) ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</aside>
<script>
	document.onkeydown = function(e) {
		if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 117)) {
			return false;
		}
		return true;
	};
	$(document).on('click', '.pgm-np', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $g = $(this).closest('.pgm-ng');
		var on = !$g.hasClass('open');
		$g.toggleClass('open', on);
		$(this).toggleClass('open-p', on).attr('aria-expanded', on ? 'true' : 'false');
	});
	$('.pgm-np').on('keydown', function(e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	});
	$('#pesquisa-funcoes').on('keyup', function(e) {
		e.preventDefault();
		$.ajax({
			url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'pesquisa']);?>/" + $('#pesquisa-funcoes').val(),
			dataType: "json",
			success: function(data) {
				$('.htmlpesquisa').html('');
				$.each(data, function(key, array) {
					$('.htmlpesquisa').append('<li><a class="link link-btn" data-controller="'+array.Controller+'" data-action="'+array.Action+'">'+array.ControllerQueAparece+ ' > ' +array.ActionQueAparece+'</a></li>');
				});
			},
		});
	});
	$(document).on("click", ".link-btn", function(e) {
		var controller = $(this).attr('data-controller');
		var action = $(this).attr('data-action');
		$.ajax({
			url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'link']);?>/" + controller + '/' + action,
			success: function(data) { window.location = data; },
		});
	});
	$(document).on('click', 'a.pgm-nch', function(e) {
		e.stopPropagation();
	});
	setTimeout(function() {
		$('.pgm-nc').removeClass('in');
	}, 0);
</script>
