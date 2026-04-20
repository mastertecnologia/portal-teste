<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	$ctrl = $this->request->getParam('controller');
	$act = $this->request->getParam('action');

	/* ── Active-state helpers ─────────────────────────────────────────── */
	$roleNav = (int)($role ?? 1);
	$sg = isset($sidebarMenuGates) && is_array($sidebarMenuGates) ? $sidebarMenuGates : [];

	$osIndexActive = ($ctrl === 'Ordensservico' && $act === 'index');
	$osAddActive = ($ctrl === 'Ordensservico' && $act === 'add');
	$ticketsServicedeskActive = ($ctrl === 'Servicedesk');
	$ticketsHistoricoActive = ($ctrl === 'Tickets' && $act === 'historico');

	$advMgmtAct = ($ctrl === 'ContractManagement');
	$advTplAct = ($ctrl === 'ContractTemplates');
	$advInvAct = ($ctrl === 'AdvancedInvoices');

	$relatoriosPainelActive = ($ctrl === 'Relatorios');
	$relatoriosIndicadoresAdvActive = ($ctrl === 'AdvancedReports');

	$finDashAct = ($ctrl === 'Financeiro' && $act === 'index');
	$finRecAct = ($ctrl === 'Financeiro' && in_array($act, ['contasReceber', 'addReceita', 'editReceita'], true));
	$finPagAct = ($ctrl === 'Financeiro' && in_array($act, ['contasPagar', 'addDespesa', 'editDespesa'], true));
	$finFluxoAct = ($ctrl === 'Financeiro' && $act === 'fluxoCaixa');
	$finRecorAct = ($ctrl === 'Financeiro' && in_array($act, ['recorrentes', 'addRecorrente', 'editRecorrente'], true));
	$finConcAct = ($ctrl === 'Financeiro' && $act === 'conciliacao');
	$finDreAct = ($ctrl === 'Financeiro' && $act === 'dre');
	$finRelAct = ($ctrl === 'FinanceiroRelatorios');
	$finPlanoAct = ($ctrl === 'FinanceiroConfig' && in_array($act, ['planoContas', 'planoContasAdd', 'planoContasEdit'], true));
	$finCcAct = ($ctrl === 'FinanceiroConfig' && in_array($act, ['centrosCusto', 'centroCustoAdd', 'centroCustoEdit'], true));
	$finBancosAct = ($ctrl === 'FinanceiroBancos' && in_array($act, ['index', 'cadastrar', 'add', 'edit', 'delete', 'buscarCatalogo', 'bootstrapBancoPorCodigo'], true));
	$finRemessaAct = ($ctrl === 'FinanceiroBancos' && in_array($act, ['remessa', 'remessaMultiempresas'], true));
	$finRetornoAct = ($ctrl === 'FinanceiroBancos' && $act === 'retorno');
	$finRelBancosAct = ($ctrl === 'FinanceiroBancos' && in_array($act, ['relatorios', 'relacaoBancos', 'relacaoRemessas', 'historicoRetorno', 'previsaoRecebimentosPorBanco', 'previsaoPorBancos'], true));

	$fiscalDashAct = ($ctrl === 'Fiscal' && $act === 'index');
	$fiscalDfeRecAct = ($ctrl === 'Fiscal' && $act === 'dfeRecebidos');
	$fiscalNotasAct = ($ctrl === 'FiscalNotas' && !in_array($act, ['controleSeries', 'inutilizarNumeracao', 'consultarChave', 'consultarCadastro'], true));
	$fiscalEntradaAct = ($ctrl === 'FiscalNotasEntrada' && !in_array($act, ['controleSeries', 'inutilizarNumeracao', 'consultarChave', 'consultarCadastro'], true));
	$fiscalInutActSaida = ($ctrl === 'FiscalNotas' && $act === 'inutilizarNumeracao');
	$fiscalInutActEntrada = ($ctrl === 'FiscalNotasEntrada' && $act === 'inutilizarNumeracao');
	$fiscalSeriesSaidaAct = ($ctrl === 'FiscalNotas' && $act === 'controleSeries');
	$fiscalSeriesEntradaAct = ($ctrl === 'FiscalNotasEntrada' && $act === 'controleSeries');
	$fiscalCertAct = ($ctrl === 'FiscalCertificados');
	$fiscalCfgAct = ($ctrl === 'FiscalConfig');
	$fiscalRelAct = ($ctrl === 'FiscalRelatorios');
	$fiscalConsultaChaveAct = (in_array($ctrl, ['FiscalNotas', 'FiscalNotasEntrada'], true) && $act === 'consultarChave');
	$fiscalConsultaCadastroAct = (in_array($ctrl, ['FiscalNotas', 'FiscalNotasEntrada'], true) && $act === 'consultarCadastro');
	$fiscalContingenciaAct = ($ctrl === 'Fiscal' && $act === 'contingencia');
	$fiscalImportarXmlAct = ($ctrl === 'Fiscal' && $act === 'importarXmlLote');

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

	$html = $this->Html;
	/** @param array<string, mixed> $linkOpts */
	$pgmSbLink = function (string $iconLucide, string $labelInnerHtml, array $url, array $linkOpts, bool $isActive) use ($html) {
		$cls = 'pgm-nav-link waves-effect waves-dark' . ($isActive ? ' active' : '');
		$linkOpts = array_merge($linkOpts, ['class' => $cls, 'escape' => false]);

		return $html->link(
			'<span class="pgm-nav-lucide" data-lucide="' . h($iconLucide) . '" aria-hidden="true"></span><span class="hide-menu">' . $labelInnerHtml . '</span>',
			$url,
			$linkOpts
		);
	};
?>
<aside class="left-sidebar skin-pgm pgm-sidebar-shell">

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

	<div class="pgm-sidebar-workspace pgm-sidebar-company-box">
		<div class="pgm-ws-icon hide-menu" aria-hidden="true">
			<span data-lucide="building-2" class="pgm-nav-lucide pgm-nav-lucide--inline"></span>
		</div>
		<div class="hide-menu pgm-sidebar-flex-min">
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

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<div class="pgm-sidebar-hidden-dt-host" aria-hidden="true">
			<div id="pgm-sidebar-functions-search"></div>
			<div id="pgm-sidebar-dt-host" class="pgm-sidebar-dt-host pgm-sidebar-dt-host--pending"></div>
		</div>
		<nav class="sidebar-nav">
			<ul id="sidebarnav" class="pgm-sidebar-nav-flat">

				<?php if (($sg['dashboard'] ?? true)) : ?>
				<li><?= $pgmSbLink('layout-dashboard', ' Dashboard', ['controller' => 'Users', 'action' => 'dashboard'], ['aria-label' => 'Dashboard'], (bool)($dashboard ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['clientes'] ?? true)) : ?>
				<li><?= $pgmSbLink('users-round', ' Clientes', ['controller' => 'Clientes', 'action' => 'index'], [], (bool)($clientesActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['produtos'] ?? true)) : ?>
				<li><?= $pgmSbLink('package', ' Produtos', ['controller' => 'Produtos', 'action' => 'index'], [], (bool)($produtosActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['tickets_servicedesk'] ?? true)) : ?>
				<li><?= $pgmSbLink('headphones', ' Service Desk', ['controller' => 'Servicedesk', 'action' => 'index'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $ticketsServicedeskActive) ?></li>
				<?php endif; ?>

				<?php if (($sg['tickets_historico'] ?? true)) : ?>
				<li><?= $pgmSbLink('history', ' Histórico de atendimentos', ['controller' => 'Tickets', 'action' => 'historico'], [], $ticketsHistoricoActive) ?></li>
				<?php endif; ?>

				<?php if (($sg['ordensservico_list'] ?? true)) : ?>
				<li><?= $pgmSbLink(
					'clipboard-list',
					' Ordens de Serviço' . ($osIndexActive ? ' <span class="pgm-os-badge hide-menu" id="badge-exec-os">—</span>' : ''),
					['controller' => 'Ordensservico', 'action' => 'index'],
					[],
					$osIndexActive
				) ?></li>
				<?php endif; ?>

				<?php if ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true)) : ?>
				<li><?= $pgmSbLink('file-plus', ' Nova ordem', ['controller' => 'Ordensservico', 'action' => 'add'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $osAddActive) ?></li>
				<?php endif; ?>

				<?php if (!empty($admin) && ($sg['queues'] ?? true)) : ?>
				<li><?= $pgmSbLink('layers', ' Filas / técnicos', ['controller' => 'Queues', 'action' => 'adminIndex'], [], (bool)($queuesAtendimentoActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['visitas_agenda'] ?? true)) : ?>
				<li><?= $pgmSbLink('calendar', ' Agenda', ['controller' => 'Visitas', 'action' => 'calendario'], [], (bool)($visitasActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['orcamentos'] ?? true)) : ?>
				<li><?= $pgmSbLink('file-text', ' Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], [], (bool)($orcamentosActive ?? '')) ?></li>
				<?php endif; ?>

				<?php
				$sgPrefSec = ($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true);
				if ($sgPrefSec) :
				?>
				<li><?= $pgmSbLink('clipboard-check', ' Pré-faturamento', ['controller' => 'Prefaturamento', 'action' => 'index'], [], (bool)($prefaturamentoActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['faturamento'] ?? true)) : ?>
				<li><?= $pgmSbLink('file-check', ' Faturamento', ['controller' => 'Faturamento', 'action' => 'index'], [], (bool)($faturamentoActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if (($sg['faturas_locacao'] ?? true)) : ?>
				<li><?= $pgmSbLink('truck', ' Locação', ['controller' => 'Faturas', 'action' => 'index'], [], (bool)($faturasActive ?? '')) ?></li>
				<?php endif; ?>

				<?php if ($roleNav === 0 && (($sg['advanced_module_gestao'] ?? true) || ($sg['advanced_module_modelos'] ?? true) || ($sg['advanced_module_faturas'] ?? true))) : ?>
					<?php if (($sg['advanced_module_gestao'] ?? true)) : ?>
				<li><?= $pgmSbLink('handshake', ' Gestão de contratos', '/modulo-contratos', [], $advMgmtAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['advanced_module_modelos'] ?? true)) : ?>
				<li><?= $pgmSbLink('file-code', ' Modelos de contrato', '/contract-templates', [], $advTplAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['advanced_module_faturas'] ?? true)) : ?>
				<li><?= $pgmSbLink('receipt', ' Faturas (contratos)', '/modulo-avancado/faturas', [], $advInvAct) ?></li>
					<?php endif; ?>
				<?php endif; ?>

				<?php if (($sg['financeiro'] ?? true)) : ?>
				<li><?= $pgmSbLink('pie-chart', ' Financeiro — Painel', ['controller' => 'Financeiro', 'action' => 'index'], [], $finDashAct) ?></li>
				<li><?= $pgmSbLink('arrow-down-circle', ' Contas a receber', ['controller' => 'Financeiro', 'action' => 'contasReceber'], [], $finRecAct) ?></li>
				<li><?= $pgmSbLink('arrow-up-circle', ' Contas a pagar', ['controller' => 'Financeiro', 'action' => 'contasPagar'], [], $finPagAct) ?></li>
				<li><?= $pgmSbLink('activity', ' Fluxo de caixa', ['controller' => 'Financeiro', 'action' => 'fluxoCaixa'], [], $finFluxoAct) ?></li>
				<li><?= $pgmSbLink('repeat', ' Recorrentes', ['controller' => 'Financeiro', 'action' => 'recorrentes'], [], $finRecorAct) ?></li>
				<li><?= $pgmSbLink('shuffle', ' Conciliação bancária', ['controller' => 'Financeiro', 'action' => 'conciliacao'], [], $finConcAct) ?></li>
				<li><?= $pgmSbLink('line-chart', ' DRE', ['controller' => 'Financeiro', 'action' => 'dre'], [], $finDreAct) ?></li>
				<li><?= $pgmSbLink('bar-chart-2', ' Relatórios financeiros', ['controller' => 'FinanceiroRelatorios', 'action' => 'index'], [], $finRelAct) ?></li>
				<li><?= $pgmSbLink('landmark', ' Bancos — Cadastro', ['controller' => 'FinanceiroBancos', 'action' => 'index'], [], $finBancosAct) ?></li>
				<li><?= $pgmSbLink('send', ' Bancos — Remessa', ['controller' => 'FinanceiroBancos', 'action' => 'remessa'], [], $finRemessaAct) ?></li>
				<li><?= $pgmSbLink('inbox', ' Bancos — Retorno', ['controller' => 'FinanceiroBancos', 'action' => 'retorno'], [], $finRetornoAct) ?></li>
				<li><?= $pgmSbLink('table-2', ' Bancos — Relatórios', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'], [], $finRelBancosAct) ?></li>
				<li><?= $pgmSbLink('book-open', ' Plano de contas', ['controller' => 'FinanceiroConfig', 'action' => 'planoContas'], [], $finPlanoAct) ?></li>
				<li><?= $pgmSbLink('folder-tree', ' Centros de custo', ['controller' => 'FinanceiroConfig', 'action' => 'centrosCusto'], [], $finCcAct) ?></li>
				<?php endif; ?>

				<?php
				$sgFiscalSec = ($sg['fiscal_modulo'] ?? true);
				if ($roleNav === 0 && $sgFiscalSec) :
				?>
					<?php if (($sg['fiscal_menu_dashboard'] ?? true)) : ?>
				<li><?= $pgmSbLink('layout-grid', ' Fiscal — Painel', ['controller' => 'Fiscal', 'action' => 'index'], [], $fiscalDashAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_dfe_recebidos'] ?? true)) : ?>
				<li><?= $pgmSbLink('mail', ' DF-e recebidos', ['controller' => 'Fiscal', 'action' => 'dfeRecebidos'], [], $fiscalDfeRecAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_notas'] ?? true)) : ?>
				<li><?= $pgmSbLink('cloud-upload', ' Notas de saída', ['controller' => 'FiscalNotas', 'action' => 'index'], [], $fiscalNotasAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_notas_entrada'] ?? true)) : ?>
				<li><?= $pgmSbLink('cloud-download', ' Notas de entrada', ['controller' => 'FiscalNotasEntrada', 'action' => 'index'], [], $fiscalEntradaAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_notas'] ?? true)) : ?>
				<li><?= $pgmSbLink('ban', ' Inutilizar num. (saída)', ['controller' => 'FiscalNotas', 'action' => 'inutilizarNumeracao'], [], $fiscalInutActSaida) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_notas_entrada'] ?? true)) : ?>
				<li><?= $pgmSbLink('ban', ' Inutilizar num. (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'inutilizarNumeracao'], [], $fiscalInutActEntrada) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_series_saida'] ?? true)) : ?>
				<li><?= $pgmSbLink('hash', ' Séries (saída)', ['controller' => 'FiscalNotas', 'action' => 'controleSeries'], [], $fiscalSeriesSaidaAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_series_entrada'] ?? true)) : ?>
				<li><?= $pgmSbLink('hash', ' Séries (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'controleSeries'], [], $fiscalSeriesEntradaAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_consulta_chave'] ?? true)) : ?>
				<li><?= $pgmSbLink('key', ' Consultar chave', ['controller' => 'FiscalNotas', 'action' => 'consultarChave'], [], $fiscalConsultaChaveAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_consulta_cadastro'] ?? true)) : ?>
				<li><?= $pgmSbLink('search', ' Consulta cadastral', ['controller' => 'FiscalNotas', 'action' => 'consultarCadastro'], [], $fiscalConsultaCadastroAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_contingencia'] ?? true)) : ?>
				<li><?= $pgmSbLink('cloud-off', ' Contingência', ['controller' => 'Fiscal', 'action' => 'contingencia'], [], $fiscalContingenciaAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_importar_xml'] ?? true)) : ?>
				<li><?= $pgmSbLink('folder-up', ' Importar XMLs', ['controller' => 'Fiscal', 'action' => 'importarXmlLote'], [], $fiscalImportarXmlAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_certificados'] ?? true)) : ?>
				<li><?= $pgmSbLink('badge-check', ' Certificados', ['controller' => 'FiscalCertificados', 'action' => 'index'], [], $fiscalCertAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_config'] ?? true)) : ?>
				<li><?= $pgmSbLink('sliders', ' Configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], [], $fiscalCfgAct) ?></li>
					<?php endif; ?>
					<?php if (($sg['fiscal_menu_relatorios'] ?? true)) : ?>
				<li><?= $pgmSbLink('newspaper', ' Relatórios fiscais', ['controller' => 'FiscalRelatorios', 'action' => 'index'], [], $fiscalRelAct) ?></li>
					<?php endif; ?>
				<?php endif; ?>

				<?php if (($sg['bancosenhas'] ?? true)) : ?>
				<li><?= $pgmSbLink('lock', ' Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], [], (bool)($senhasActive ?? '')) ?></li>
				<?php endif; ?>

				<?php
				$sgRelSec = ($sg['relatorios_painel'] ?? true) || ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true));
				if ($sgRelSec) :
				?>
					<?php if (($sg['relatorios_painel'] ?? true)) : ?>
				<li><?= $pgmSbLink('pie-chart', ' Painel e indicadores', ['controller' => 'Relatorios', 'action' => 'index'], [], $relatoriosPainelActive) ?></li>
					<?php endif; ?>
					<?php if ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true)) : ?>
				<li><?= $pgmSbLink('trending-up', ' Indicadores avançados', '/modulo-avancado/indicadores', [], $relatoriosIndicadoresAdvActive) ?></li>
					<?php endif; ?>
				<?php endif; ?>

				<li id="mini-logout" class="<?= $sidebar != 'mini-sidebar' ? 'd-none' : '' ?>">
					<?= $this->Html->link(
						'<span class="pgm-nav-lucide" data-lucide="log-out" aria-hidden="true"></span><span class="hide-menu">Sair</span>',
						'/users/logout',
						['class' => 'pgm-nav-link waves-effect waves-dark pgm-nav-link--danger', 'escape' => false]
					) ?>
				</li>
			</ul>
		</nav>
	</div>

	<div class="pgm-sidebar-footer">
		<div class="pgm-sf-actions hide-menu">
			<?php if (($sg['footer_perfil_senha'] ?? true)) : ?>
			<?= $this->Html->link(
				'<span class="pgm-sf-ico" data-lucide="user-round-cog" aria-hidden="true"></span>',
				['controller' => 'Users', 'action' => 'change_profile'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Meu perfil']
			) ?>
			<?= $this->Html->link(
				'<span class="pgm-sf-ico" data-lucide="key-round" aria-hidden="true"></span>',
				['controller' => 'Users', 'action' => 'change_password'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Alterar senha']
			) ?>
			<?php endif; ?>
			<?php if (!empty($showConfigAdminHub)) : ?>
			<?= $this->Html->link(
				'<span class="pgm-sf-ico" data-lucide="settings" aria-hidden="true"></span>',
				['controller' => 'config', 'action' => 'index'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Painel Administrativo']
			) ?>
			<?php elseif (!empty($showPermissoesRbacShortcut)) : ?>
			<?= $this->Html->link(
				'<span class="pgm-sf-ico" data-lucide="shield-check" aria-hidden="true"></span>',
				['controller' => 'config', 'action' => 'index'],
				['class' => 'pgm-sf-act', 'escape' => false, 'title' => 'Permissões RBAC / catálogo']
			) ?>
			<?php endif; ?>
			<?php if ($roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true)) : ?>
			<?= $this->element('portal_notification_bell') ?>
			<?php endif; ?>
			<?= $this->Html->link(
				'<span class="pgm-sf-ico" data-lucide="log-out" aria-hidden="true"></span>',
				['controller' => 'Users', 'action' => 'logout'],
				['class' => 'pgm-sf-act pgm-sf-act-danger', 'escape' => false, 'title' => 'Sair']
			) ?>
		</div>

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

		<div class="pgm-sidebar-collapse-row">
			<a href="javascript:void(0)" class="sidebartoggler pgm-sidebar-collapse-btn" title="Recolher menu" aria-label="Recolher menu lateral"><span data-lucide="chevrons-left" class="pgm-nav-lucide" aria-hidden="true"></span></a>
		</div>
	</div>
</aside>
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
<script>
	document.onkeydown = function(e) {
		if (e.ctrlKey && (e.keyCode === 85 || e.keyCode === 117)) {
			return false;
		}
		return true;
	};
	function pgmSidebarLucideRefresh() {
		if (window.lucide && typeof lucide.createIcons === 'function') {
			lucide.createIcons();
		}
	}
	$(function() {
		pgmSidebarLucideRefresh();
	});
	$(document).on('click', 'a.pgm-nav-link', function() {
		/* no-op: evita interferência com navegação */
	});
	setTimeout(function() {
		$('.pgm-nc').removeClass('in');
	}, 0);
</script>
