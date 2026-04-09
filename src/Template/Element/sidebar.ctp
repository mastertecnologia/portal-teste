<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	$ctrl = $this->request->getParam('controller');
	$act = $this->request->getParam('action');
	$osOpen = ($ctrl === 'Ordensservico');
	$osIndexActive = ($ctrl === 'Ordensservico' && $act === 'index');
	$osAddActive = ($ctrl === 'Ordensservico' && $act === 'add');
	$ticketsOpen = ($ctrl === 'Tickets' || $ctrl === 'Servicedesk');
	$ticketsServicedeskActive = ($ctrl === 'Servicedesk');
	$ticketsHistoricoActive = ($ctrl === 'Tickets' && $act === 'historico');
	$relatoriosOpen = ($ctrl === 'Relatorios' || $ctrl === 'AdvancedReports');
	$relatoriosPainelActive = ($ctrl === 'Relatorios');
	$relatoriosIndicadoresAdvActive = ($ctrl === 'AdvancedReports');
	$roleNav = (int)($role ?? 1);
	$sg = isset($sidebarMenuGates) && is_array($sidebarMenuGates) ? $sidebarMenuGates : [];
	$advModOpen = in_array($ctrl, ['AdvancedContracts', 'AdvancedInvoices', 'ContractTemplates', 'ContractManagement'], true);
	$advMgmtAct = ($ctrl === 'ContractManagement');
	$advTplAct = ($ctrl === 'ContractTemplates');
	$advInvAct = ($ctrl === 'AdvancedInvoices');
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

	<?php if ($roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true)) : ?>
	<div class="pgm-sidebar-top-tools">
		<?= $this->element('pgm_theme_toggle_icon') ?>
		<?= $this->element('portal_notification_bell') ?>
	</div>
	<?php elseif ($roleNav === 0) : ?>
	<div class="pgm-sidebar-top-tools">
		<?= $this->element('pgm_theme_toggle_icon') ?>
	</div>
	<?php endif; ?>

	<!-- ── Workspace / Empresa ────────────────────────────── -->
	<div class="pgm-sidebar-workspace">
		<div class="pgm-ws-icon hide-menu" aria-hidden="true">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none">
				<path d="M3 21h18M3 7l9-4 9 4M4 7v14M20 7v14M9 21v-5h6v5M8 11h1m-1 4h1m6-4h1m-1 4h1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</div>
		<div class="hide-menu pgm-sidebar-flex-min">
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

	<!-- ── Busca de funções (Fase 6b: pesquisa.sidebar_search) ── -->
	<?php if (($sg['sidebar_functions_search'] ?? true)) : ?>
	<div class="pgm-sb-search-block">
		<div class="pgm-sidebar-functions-search" id="pgm-sidebar-functions-search">
			<div class="pgm-sb-sbox">
				<svg class="pgm-sb-search-svg" width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true">
					<circle cx="6.5" cy="6.5" r="4.5" stroke="currentColor" stroke-width="1.5"/>
					<path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
				<input type="text" id="pesquisa-funcoes" autocomplete="off" placeholder="Buscar funções…" />
			</div>
			<ul class="pgm-sb-typeahead htmlpesquisa list-unstyled m-0 p-0"></ul>
		</div>
		<div id="pgm-sidebar-dt-host" class="pgm-sidebar-dt-host pgm-sidebar-dt-host--pending" aria-label="Busca na listagem"></div>
	</div>
	<?php endif; ?>

	<!-- ── Navegação ──────────────────────────────────────── -->
	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav">

				<li class="pgm-nav-section-label" aria-hidden="true"><span>Menu principal</span></li>

				<?php if (($sg['dashboard'] ?? true)) : ?>
				<li class="<?= $dashboard ?>">
					<?= $this->Html->link(
						'<i class="fa fa-columns"></i><span class="hide-menu"> Dashboard </span>',
						['controller' => 'Users', 'action' => 'dashboard'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Dashboard', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php
				// Indicadores avançados só na equipa (role 0); evita secção vazia no portal.
				$sgRelSec = ($sg['relatorios_painel'] ?? true)
					|| ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true));
				if ($sgRelSec) :
				?>
				<li class="pgm-ng <?= h($relActive) ?> <?= $relatoriosOpen ? 'open' : '' ?>">
					<div class="pgm-np <?= $relatoriosOpen ? 'open-p' : '' ?>" role="button" tabindex="0" aria-expanded="<?= $relatoriosOpen ? 'true' : 'false' ?>">
						<i class="fas fa-chart-pie ni-ico-fa"></i>
						<span class="hide-menu">Relatórios</span>
						<svg class="pgm-chevron hide-menu" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<ul class="pgm-nc list-unstyled">
						<?php if (($sg['relatorios_painel'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Painel e indicadores</span>',
							['controller' => 'Relatorios', 'action' => 'index'],
							['class' => 'pgm-nch ' . ($relatoriosPainelActive ? 'act' : ''), 'escape' => false, 'aria-label' => 'Relatórios e indicadores']
						) ?></li>
						<?php endif; ?>
						<?php if ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Indicadores (módulo avançado)</span>',
							'/modulo-avancado/indicadores',
							['class' => 'pgm-nch ' . ($relatoriosIndicadoresAdvActive ? 'act' : ''), 'escape' => false, 'aria-label' => 'Indicadores módulo avançado']
						) ?></li>
						<?php endif; ?>
					</ul>
				</li>
				<?php endif; ?>
				<?php if (($sg['clientes'] ?? true)) : ?>
				<li class="<?= $clientesActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-building"></i><span class="hide-menu"> Clientes </span>',
						['controller' => 'Clientes', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Clientes', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php if (($sg['produtos'] ?? true)) : ?>
				<li class="<?= $produtosActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-boxes"></i><span class="hide-menu"> Produtos </span>',
						['controller' => 'Produtos', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'aria-label' => 'Produtos', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>

				<!-- Ordens de Serviço (collapsible); Fase 6c: listar vs nova -->
				<?php
				$sgOsSec = ($sg['ordensservico_list'] ?? true)
					|| ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true));
				if ($sgOsSec) :
				?>
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
						<?php if (($sg['ordensservico_list'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Listar Ordens</span>',
							['controller' => 'Ordensservico', 'action' => 'index'],
							['class' => 'pgm-nch ' . ($osIndexActive ? 'act' : ''), 'escape' => false]
						) ?></li>
						<?php endif; ?>
						<?php if ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Nova ordem</span>',
							['controller' => 'Ordensservico', 'action' => 'add'],
							['class' => 'pgm-nch pgm-nch-nova ' . ($osAddActive ? 'act' : ''), 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer']
						) ?></li>
						<?php endif; ?>
					</ul>
				</li>
				<?php endif; ?>

				<?php
				$sgTkSec = ($sg['tickets_servicedesk'] ?? true) || ($sg['tickets_historico'] ?? true);
				if ($sgTkSec) :
				?>
				<li class="pgm-ng <?= h($ticketsActive) ?> <?= $ticketsOpen ? 'open' : '' ?>">
					<div class="pgm-np <?= $ticketsOpen ? 'open-p' : '' ?>" role="button" tabindex="0" aria-expanded="<?= $ticketsOpen ? 'true' : 'false' ?>">
						<i class="fas fa-ticket-alt ni-ico-fa"></i>
						<span class="hide-menu">Tickets</span>
						<svg class="pgm-chevron hide-menu" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<ul class="pgm-nc list-unstyled">
						<?php if (($sg['tickets_servicedesk'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Service Desk</span>',
							['controller' => 'Servicedesk', 'action' => 'index'],
							['class' => 'pgm-nch ' . ($ticketsServicedeskActive ? 'act' : ''), 'escape' => false, 'target' => '_blank', 'rel' => 'noopener noreferrer', 'aria-label' => 'Service Desk']
						) ?></li>
						<?php endif; ?>
						<?php if (($sg['tickets_historico'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Histórico de atendimentos</span>',
							['controller' => 'Tickets', 'action' => 'historico'],
							['class' => 'pgm-nch ' . ($ticketsHistoricoActive ? 'act' : ''), 'escape' => false, 'aria-label' => 'Histórico de atendimentos']
						) ?></li>
						<?php endif; ?>
					</ul>
				</li>
				<?php endif; ?>

				<li class="pgm-nav-section-label" aria-hidden="true"><span>Operações</span></li>

				<?php if (!empty($admin) && ($sg['queues'] ?? true)) : ?>
				<li class="<?= $queuesAtendimentoActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-layer-group"></i><span class="hide-menu"> Filas / técnicos </span>',
						['controller' => 'Queues', 'action' => 'adminIndex'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>

				<?php if (($sg['orcamentos'] ?? true)) : ?>
				<li class="<?= $orcamentosActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-file-invoice-dollar"></i><span class="hide-menu"> Orçamentos </span>',
						['controller' => 'Orcamentos', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php
				// 6e: conferência é POST na mesma página (index); menu = fila; ver coluna conferências em index.ctp.
				$sgPrefSec = ($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true);
				if ($sgPrefSec) :
				?>
				<li class="<?= $prefaturamentoActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-clipboard-check"></i><span class="hide-menu"> Pré-faturamento </span>',
						['controller' => 'Prefaturamento', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false, 'aria-label' => 'Pré-faturamento']
					) ?>
				</li>
				<?php endif; ?>
				<?php if (($sg['faturamento'] ?? true)) : ?>
				<li class="<?= $faturamentoActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-file-alt"></i><span class="hide-menu"> Faturamento </span>',
						['controller' => 'Faturamento', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php if (($sg['financeiro'] ?? true)) : ?>
				<li class="<?= $financeiroActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-chart-line"></i><span class="hide-menu"> Financeiro </span>',
						['controller' => 'Financeiro', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php
				$sgAdvSec = ($sg['advanced_module_gestao'] ?? true)
					|| ($sg['advanced_module_modelos'] ?? true)
					|| ($sg['advanced_module_faturas'] ?? true);
				if ($roleNav === 0 && $sgAdvSec) :
				?>
				<li class="pgm-ng <?= h($advancedModuleActive) ?> <?= $advModOpen ? 'open' : '' ?>">
					<div class="pgm-np <?= $advModOpen ? 'open-p' : '' ?>" role="button" tabindex="0" aria-expanded="<?= $advModOpen ? 'true' : 'false' ?>">
						<i class="fas fa-cubes ni-ico-fa"></i>
						<span class="hide-menu">Módulo avançado</span>
						<svg class="pgm-chevron hide-menu" width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true">
							<path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</div>
					<ul class="pgm-nc list-unstyled">
						<?php if (($sg['advanced_module_gestao'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Gestão de contratos</span>',
							'/modulo-contratos',
							['class' => 'pgm-nch ' . ($advMgmtAct ? 'act' : ''), 'escape' => false]
						) ?></li>
						<?php endif; ?>
						<?php if (($sg['advanced_module_modelos'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Modelos de contrato</span>',
							'/contract-templates',
							['class' => 'pgm-nch ' . ($advTplAct ? 'act' : ''), 'escape' => false]
						) ?></li>
						<?php endif; ?>
						<?php if (($sg['advanced_module_faturas'] ?? true)) : ?>
						<li><?= $this->Html->link(
							'<span class="pgm-ndot"></span><span>Faturas</span>',
							'/modulo-avancado/faturas',
							['class' => 'pgm-nch ' . ($advInvAct ? 'act' : ''), 'escape' => false]
						) ?></li>
						<?php endif; ?>
					</ul>
				</li>
				<?php endif; ?>
				<?php if (($sg['faturas_locacao'] ?? true)) : ?>
				<li class="<?= $faturasActive ?>">
					<?= $this->Html->link(
						'<i class="fas fa-file-invoice"></i><span class="hide-menu"> Locação </span>',
						['controller' => 'Faturas', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php if (($sg['visitas_agenda'] ?? true)) : ?>
				<li class="<?= $visitasActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-calendar"></i><span class="hide-menu"> Agenda </span>',
						['controller' => 'Visitas', 'action' => 'calendario'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>
				<?php if (($sg['bancosenhas'] ?? true)) : ?>
				<li class="<?= $senhasActive ?>">
					<?= $this->Html->link(
						'<i class="fa fa-lock"></i><span class="hide-menu"> Banco de Senhas </span>',
						['controller' => 'Bancosenhas', 'action' => 'index'],
						['class' => 'waves-effect waves-dark', 'escape' => false]
					) ?>
				</li>
				<?php endif; ?>

				<li id="mini-logout" class="<?= $sidebar != 'mini-sidebar' ? 'd-none' : '' ?>">
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
			<?php if (($sg['footer_perfil_senha'] ?? true)) : ?>
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
			<?php endif; ?>
			<?php if (!empty($showConfigAdminHub)) : ?>
			<?= $this->Html->link(
				'<i class="ti-settings"></i>',
				['controller' => 'config', 'action' => 'index'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Painel Administrativo']
			) ?>
			<?php elseif (!empty($showPermissoesRbacShortcut)) : ?>
			<?= $this->Html->link(
				'<i class="ti-settings"></i>',
				['controller' => 'config', 'action' => 'index'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Permissões RBAC / catálogo']
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
						<span class="caret hide-menu pgm-sidebar-caret-push"></span>
					</a>
					<div class="dropdown-menu animated flipInY">
						<?php if (($sg['footer_perfil_senha'] ?? true)) : ?>
						<?= $this->Html->link('<i class="fas fa-user"></i> Alterar Perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?= $this->Html->link('<i class="fa fa-lock"></i> Alterar Senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?php endif; ?>
						<?php if (($sg['footer_acesso_remoto'] ?? true)) : ?>
						<?= $this->Html->link('<i class="ti-rss-alt"></i> Acesso Remoto', ['controller' => 'normasempresa', 'action' => 'acessoremoto'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?php endif; ?>
						<?php if (($sg['footer_twofactor_menu'] ?? true)) : ?>
						<?= $this->Html->link('<i class="ti-lock"></i> Verificação login', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item', 'escape' => false]) ?>
						<?php endif; ?>
						<?php if (!empty($showConfigAdminHub)) {
							echo $this->Html->link('<i class="ti-settings"></i> Painel Administrativo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item', 'escape' => false]);
						} elseif (!empty($showPermissoesRbacShortcut)) {
							echo $this->Html->link('<i class="ti-settings"></i> Permissões RBAC / catálogo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item', 'escape' => false]);
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

	if (window.PgmPortalTheme) {
		PgmPortalTheme.initSidebarToggle(<?= json_encode(Router::url(['controller' => 'Users', 'action' => 'selectTheme'])) ?>);
	}
</script>
