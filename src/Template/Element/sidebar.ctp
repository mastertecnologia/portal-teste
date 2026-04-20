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

	$relatoriosOsActive = ($ctrl === 'Ordensservico' && $act === 'relatorios');

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
	/**
	 * @param array|string $url Rota Cake ou URL absoluta/caminho (ex.: '/modulo-contratos')
	 * @param array<string, mixed> $linkOpts
	 */
	/**
	 * Link de menu no estilo “nav-item” da proposta (Lucide + rótulo + badge opcional).
	 *
	 * @param string $dataLabel Texto curto para tooltip no menu recolhido (ex.: Dashboard)
	 */
	$pgmSbLink = function (
		string $iconLucide,
		string $labelPlain,
		$url,
		array $linkOpts,
		bool $isActive,
		string $badgeInnerHtml = '',
		string $dataLabel = ''
	) use ($html) {
		$labelForAttr = $dataLabel !== '' ? $dataLabel : strip_tags($labelPlain);
		$cls = 'pgm-nav-link nav-item waves-effect waves-dark' . ($isActive ? ' active' : '');
		$linkOpts = array_merge($linkOpts, [
			'class' => $cls,
			'escape' => false,
			'data-label' => $labelForAttr,
		]);

		$badge = $badgeInnerHtml !== '' ? $badgeInnerHtml : '';

		return $html->link(
			'<span class="pgm-nav-lucide" data-lucide="' . h($iconLucide) . '" aria-hidden="true"></span>'
			. '<span class="nav-item-label hide-menu">' . $labelPlain . '</span>'
			. $badge,
			$url,
			$linkOpts
		);
	};
?>
<?php
	$currentEmpresaNome = (string)($empresasOptSidebar[$empresa] ?? $nomeempresa ?? 'PGM Soluções em TI');
	$empresaParts = preg_split('/\s+/', trim($currentEmpresaNome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
	$empresaInitials = '';
	if (!empty($empresaParts[0])) {
		$empresaInitials .= strtoupper(substr($empresaParts[0], 0, 1));
	}
	if (!empty($empresaParts[1])) {
		$empresaInitials .= strtoupper(substr($empresaParts[1], 0, 1));
	}
	if ($empresaInitials === '' && $currentEmpresaNome !== '') {
		$empresaInitials = strtoupper(substr($currentEmpresaNome, 0, 2));
	}
?>
<aside class="left-sidebar skin-pgm pgm-sidebar-shell" id="sidebar">

	<div class="workspace">
		<button class="workspace-btn" id="wsBtn" aria-expanded="false" type="button">
			<div class="workspace-avatar" id="wsAvatar"><?= h($empresaInitials ?: 'PG') ?></div>
			<div class="workspace-info">
				<div class="workspace-name" id="wsName"><?= h($currentEmpresaNome) ?></div>
				<div class="workspace-sub" id="wsSub">ERP · Matriz</div>
			</div>
			<svg class="workspace-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
		</button>

		<div class="workspace-dropdown" id="wsDropdown">
			<div class="workspace-search">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				<input type="text" placeholder="Buscar empresa..." id="wsSearchInput">
			</div>
			<div id="wsList">
				<?php foreach (($empresasOptSidebar ?? []) as $empresaId => $empresaNome) :
					$nomeAtual = (string)$empresaNome;
					$parts = preg_split('/\s+/', trim($nomeAtual), -1, PREG_SPLIT_NO_EMPTY) ?: [];
					$ini = '';
					if (!empty($parts[0])) {
						$ini .= strtoupper(substr($parts[0], 0, 1));
					}
					if (!empty($parts[1])) {
						$ini .= strtoupper(substr($parts[1], 0, 1));
					}
					if ($ini === '' && $nomeAtual !== '') {
						$ini = strtoupper(substr($nomeAtual, 0, 2));
					}
					$isEmpresaAtiva = ((string)$empresaId === (string)$empresa);
				?>
				<button class="workspace-item<?= $isEmpresaAtiva ? ' active' : '' ?>" data-id="<?= h((string)$empresaId) ?>" data-name="<?= h($nomeAtual) ?>" data-initials="<?= h($ini ?: 'PG') ?>" type="button">
					<div class="workspace-item-avatar"><?= h($ini ?: 'PG') ?></div>
					<span class="workspace-item-name"><?= h($nomeAtual) ?></span>
					<svg class="workspace-item-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
				</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<div style="display:none;">
		<?= $this->Form->control('empresaSidebar', [
			'id' => 'empresaSidebar',
			'class' => 'form-control pgm-empresa-select',
			'label' => false,
			'value' => $empresa,
			'options' => $empresasOptSidebar,
			'readonly' => !$multiEmpresa,
		]) ?>
	</div>

	<div class="search-wrap">
		<div class="search">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<input id="pesquisa-funcoes" type="text" class="search-input" placeholder="Buscar..." autocomplete="off" />
			<span class="kbd"><kbd>Ctrl</kbd><kbd>K</kbd></span>
		</div>
		<ul class="pgm-sb-typeahead htmlpesquisa list-unstyled m-0 p-0"></ul>
		<div class="pgm-sidebar-hidden-dt-host" aria-hidden="true">
			<div id="pgm-sidebar-functions-search"></div>
			<div id="pgm-sidebar-dt-host" class="pgm-sidebar-dt-host pgm-sidebar-dt-host--pending"></div>
		</div>
	</div>

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav" class="pgm-sidebar-nav-flat nav">

				<li class="nav-section-flat">
					<div class="nav-section-items" style="padding: 2px 0;">
						<?php if (($sg['dashboard'] ?? true)) : ?>
						<?= $pgmSbLink('layout-dashboard', ' Dashboard', ['controller' => 'Users', 'action' => 'dashboard'], ['aria-label' => 'Dashboard'], (bool)($dashboard ?? ''), '', 'Dashboard') ?>
						<?php endif; ?>

						<?php if (($sg['clientes'] ?? true)) : ?>
						<?= $pgmSbLink('users', ' Clientes', ['controller' => 'Clientes', 'action' => 'index'], [], (bool)($clientesActive ?? ''), '', 'Clientes') ?>
						<?php endif; ?>

						<?php if (($sg['produtos'] ?? true)) : ?>
						<?= $pgmSbLink('package', ' Produtos', ['controller' => 'Produtos', 'action' => 'index'], [], (bool)($produtosActive ?? ''), '', 'Produtos') ?>
						<?php endif; ?>

						<?php if (($sg['tickets_servicedesk'] ?? true)) : ?>
						<?= $pgmSbLink('headphones', ' Service Desk', ['controller' => 'Servicedesk', 'action' => 'index'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $ticketsServicedeskActive, '<span class="badge badge-danger hide-menu">12</span>', 'Service Desk') ?>
						<?php endif; ?>

						<?php if (($sg['tickets_historico'] ?? true)) : ?>
						<?= $pgmSbLink('history', ' Histórico', ['controller' => 'Tickets', 'action' => 'historico'], [], $ticketsHistoricoActive, '', 'Histórico') ?>
						<?php endif; ?>
					</div>
				</li>

				<?php
				$sgOsGrp = ($sg['ordensservico_list'] ?? true) || ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true))
					|| (!empty($admin) && ($sg['queues'] ?? true));
				if ($sgOsGrp) :
				?>
				<li class="nav-section">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="true">
						<span>Ordens de Serviço</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true)) : ?>
						<?= $pgmSbLink('file-plus', ' Nova ordem', ['controller' => 'Ordensservico', 'action' => 'add'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $osAddActive, '', 'Nova ordem') ?>
						<?php endif; ?>
						<?php if (($sg['ordensservico_list'] ?? true)) : ?>
						<?= $pgmSbLink(
							'clipboard-list',
							' Ordens de Serviço',
							['controller' => 'Ordensservico', 'action' => 'index'],
							[],
							$osIndexActive,
							($osIndexActive ? '<span class="pgm-os-badge badge badge-warning hide-menu" id="badge-exec-os">—</span>' : ''),
							'Ordens de Serviço'
						) ?>
						<?php endif; ?>
						<?php if (!empty($admin) && ($sg['queues'] ?? true)) : ?>
						<?= $pgmSbLink('layers', ' Filas / técnicos', ['controller' => 'Queues', 'action' => 'adminIndex'], [], (bool)($queuesAtendimentoActive ?? ''), '<span class="badge badge-warning hide-menu">7</span>', 'Filas / técnicos') ?>
						<?php endif; ?>
						<?php if (($sg['ordensservico_list'] ?? true)) : ?>
						<?= $pgmSbLink('bar-chart-2', ' Relatórios', ['controller' => 'Ordensservico', 'action' => 'relatorios'], [], $relatoriosOsActive, '', 'Relatórios') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php
				$sgContrGrp = $roleNav === 0 && (($sg['advanced_module_modelos'] ?? true) || ($sg['advanced_module_faturas'] ?? true));
				if ($sgContrGrp) :
				?>
				<li class="nav-section">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="true">
						<span>Contratos</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if (($sg['advanced_module_modelos'] ?? true)) : ?>
						<?= $pgmSbLink('file-code', ' Modelos', '/contract-templates', [], $advTplAct, '', 'Modelos') ?>
						<?php endif; ?>
						<?php if (($sg['advanced_module_faturas'] ?? true)) : ?>
						<?= $pgmSbLink('receipt', ' Faturas', '/modulo-avancado/faturas', [], $advInvAct, '<span class="badge badge-accent hide-menu">3</span>', 'Faturas') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['financeiro'] ?? true)) : ?>
				<li class="nav-section">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="true">
						<span>Financeiro</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?= $pgmSbLink('pie-chart', ' Painel', ['controller' => 'Financeiro', 'action' => 'index'], [], $finDashAct, '', 'Painel financeiro') ?>
						<?= $pgmSbLink('arrow-down-circle', ' Contas a receber', ['controller' => 'Financeiro', 'action' => 'contasReceber'], [], $finRecAct, '', 'Contas a receber') ?>
						<?= $pgmSbLink('arrow-up-circle', ' Contas a pagar', ['controller' => 'Financeiro', 'action' => 'contasPagar'], [], $finPagAct, '', 'Contas a pagar') ?>
						<?= $pgmSbLink('activity', ' Fluxo de caixa', ['controller' => 'Financeiro', 'action' => 'fluxoCaixa'], [], $finFluxoAct, '', 'Fluxo de caixa') ?>
						<?= $pgmSbLink('repeat', ' Recorrentes', ['controller' => 'Financeiro', 'action' => 'recorrentes'], [], $finRecorAct, '', 'Recorrentes') ?>
						<?= $pgmSbLink('shuffle', ' Conciliação', ['controller' => 'Financeiro', 'action' => 'conciliacao'], [], $finConcAct, '', 'Conciliação bancária') ?>
						<?= $pgmSbLink('line-chart', ' DRE', ['controller' => 'Financeiro', 'action' => 'dre'], [], $finDreAct, '', 'DRE') ?>
						<?= $pgmSbLink('bar-chart-2', ' Relatórios financeiros', ['controller' => 'FinanceiroRelatorios', 'action' => 'index'], [], $finRelAct, '', 'Relatórios financeiros') ?>
						<?= $pgmSbLink('book-open', ' Plano de contas', ['controller' => 'FinanceiroConfig', 'action' => 'planoContas'], [], $finPlanoAct, '', 'Plano de contas') ?>
						<?= $pgmSbLink('folder-tree', ' Centros de custo', ['controller' => 'FinanceiroConfig', 'action' => 'centrosCusto'], [], $finCcAct, '', 'Centros de custo') ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['financeiro'] ?? true)) : ?>
				<li class="nav-section">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="true">
						<span>Bancos</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?= $pgmSbLink('landmark', ' Cadastro', ['controller' => 'FinanceiroBancos', 'action' => 'index'], [], $finBancosAct, '', 'Cadastro de bancos') ?>
						<?= $pgmSbLink('send', ' Remessa', ['controller' => 'FinanceiroBancos', 'action' => 'remessa'], [], $finRemessaAct, '', 'Remessa') ?>
						<?= $pgmSbLink('inbox', ' Retorno', ['controller' => 'FinanceiroBancos', 'action' => 'retorno'], [], $finRetornoAct, '', 'Retorno') ?>
						<?= $pgmSbLink('table-2', ' Relatórios bancos', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'], [], $finRelBancosAct, '', 'Relatórios bancos') ?>
					</div>
				</li>
				<?php endif; ?>

				<?php
				$sgFiscalSec = ($sg['fiscal_modulo'] ?? true);
				if ($roleNav === 0 && $sgFiscalSec) :
				?>
				<li class="nav-section">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="true">
						<span>Fiscal</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if (($sg['fiscal_menu_dashboard'] ?? true)) : ?>
						<?= $pgmSbLink('layout-grid', ' Painel', ['controller' => 'Fiscal', 'action' => 'index'], [], $fiscalDashAct, '', 'Painel fiscal') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_dfe_recebidos'] ?? true)) : ?>
						<?= $pgmSbLink('mail', ' DF-e recebidos', ['controller' => 'Fiscal', 'action' => 'dfeRecebidos'], [], $fiscalDfeRecAct, '<span class="badge badge-accent hide-menu">24</span>', 'DF-e recebidos') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_notas'] ?? true)) : ?>
						<?= $pgmSbLink('cloud-upload', ' Notas de saída', ['controller' => 'FiscalNotas', 'action' => 'index'], [], $fiscalNotasAct, '', 'Notas de saída') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_notas_entrada'] ?? true)) : ?>
						<?= $pgmSbLink('cloud-download', ' Notas de entrada', ['controller' => 'FiscalNotasEntrada', 'action' => 'index'], [], $fiscalEntradaAct, '', 'Notas de entrada') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_notas'] ?? true)) : ?>
						<?= $pgmSbLink('ban', ' Inutilizar (saída)', ['controller' => 'FiscalNotas', 'action' => 'inutilizarNumeracao'], [], $fiscalInutActSaida, '', 'Inutilizar num. (saída)') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_notas_entrada'] ?? true)) : ?>
						<?= $pgmSbLink('ban', ' Inutilizar (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'inutilizarNumeracao'], [], $fiscalInutActEntrada, '', 'Inutilizar num. (entrada)') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_series_saida'] ?? true)) : ?>
						<?= $pgmSbLink('hash', ' Séries (saída)', ['controller' => 'FiscalNotas', 'action' => 'controleSeries'], [], $fiscalSeriesSaidaAct, '', 'Séries (saída)') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_series_entrada'] ?? true)) : ?>
						<?= $pgmSbLink('hash', ' Séries (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'controleSeries'], [], $fiscalSeriesEntradaAct, '', 'Séries (entrada)') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_consulta_chave'] ?? true)) : ?>
						<?= $pgmSbLink('key', ' Consultar chave', ['controller' => 'FiscalNotas', 'action' => 'consultarChave'], [], $fiscalConsultaChaveAct, '', 'Consultar chave') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_consulta_cadastro'] ?? true)) : ?>
						<?= $pgmSbLink('search', ' Consulta cadastral', ['controller' => 'FiscalNotas', 'action' => 'consultarCadastro'], [], $fiscalConsultaCadastroAct, '', 'Consulta cadastral') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_contingencia'] ?? true)) : ?>
						<?= $pgmSbLink('cloud-off', ' Contingência', ['controller' => 'Fiscal', 'action' => 'contingencia'], [], $fiscalContingenciaAct, '', 'Contingência') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_importar_xml'] ?? true)) : ?>
						<?= $pgmSbLink('folder-up', ' Importar XMLs', ['controller' => 'Fiscal', 'action' => 'importarXmlLote'], [], $fiscalImportarXmlAct, '', 'Importar XMLs') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_certificados'] ?? true)) : ?>
						<?= $pgmSbLink('badge-check', ' Certificados', ['controller' => 'FiscalCertificados', 'action' => 'index'], [], $fiscalCertAct, '', 'Certificados') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_config'] ?? true)) : ?>
						<?= $pgmSbLink('sliders', ' Configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], [], $fiscalCfgAct, '', 'Configuração fiscal') ?>
						<?php endif; ?>
						<?php if (($sg['fiscal_menu_relatorios'] ?? true)) : ?>
						<?= $pgmSbLink('newspaper', ' Relatórios fiscais', ['controller' => 'FiscalRelatorios', 'action' => 'index'], [], $fiscalRelAct, '', 'Relatórios fiscais') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<li class="nav-section-flat">
					<div class="nav-section-items" style="padding: 2px 0;">
						<?php if ($roleNav === 0 && ($sg['advanced_module_gestao'] ?? true)) : ?>
						<?= $pgmSbLink('handshake', ' Gestão de contratos', '/modulo-contratos', [], $advMgmtAct, '', 'Gestão de contratos') ?>
						<?php endif; ?>

						<?php if (($sg['visitas_agenda'] ?? true)) : ?>
						<?= $pgmSbLink('calendar', ' Agenda', ['controller' => 'Visitas', 'action' => 'calendario'], [], (bool)($visitasActive ?? ''), '', 'Agenda') ?>
						<?php endif; ?>

						<?php if (($sg['orcamentos'] ?? true)) : ?>
						<?= $pgmSbLink('file-text', ' Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], [], (bool)($orcamentosActive ?? ''), '', 'Orçamentos') ?>
						<?php endif; ?>

						<?php
						$sgPrefSec = ($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true);
						if ($sgPrefSec) :
						?>
						<?= $pgmSbLink('clipboard-check', ' Pré-faturamento', ['controller' => 'Prefaturamento', 'action' => 'index'], [], (bool)($prefaturamentoActive ?? ''), '', 'Pré-faturamento') ?>
						<?php endif; ?>

						<?php if (($sg['faturamento'] ?? true)) : ?>
						<?= $pgmSbLink('file-check', ' Faturamento', ['controller' => 'Faturamento', 'action' => 'index'], [], (bool)($faturamentoActive ?? ''), '', 'Faturamento') ?>
						<?php endif; ?>

						<?php if (($sg['faturas_locacao'] ?? true)) : ?>
						<?= $pgmSbLink('truck', ' Locação', ['controller' => 'Faturas', 'action' => 'index'], [], (bool)($faturasActive ?? ''), '', 'Locação') ?>
						<?php endif; ?>

						<?php if (($sg['bancosenhas'] ?? true)) : ?>
						<?= $pgmSbLink('lock', ' Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], [], (bool)($senhasActive ?? ''), '', 'Banco de Senhas') ?>
						<?php endif; ?>

						<?php
						$sgRelSec = ($sg['relatorios_painel'] ?? true) || ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true));
						if ($sgRelSec) :
						?>
							<?php if (($sg['relatorios_painel'] ?? true)) : ?>
						<?= $pgmSbLink('pie-chart', ' Painel e indicadores', ['controller' => 'Relatorios', 'action' => 'index'], [], $relatoriosPainelActive, '', 'Painel e indicadores') ?>
							<?php endif; ?>
							<?php if ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true)) : ?>
						<?= $pgmSbLink('trending-up', ' Indicadores avançados', '/modulo-avancado/indicadores', [], $relatoriosIndicadoresAdvActive, '', 'Indicadores avançados') ?>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</li>

				<li id="mini-logout" class="<?= $sidebar != 'mini-sidebar' ? 'd-none' : '' ?> nav-section-flat">
					<div class="nav-section-items" style="padding: 2px 0;">
						<?= $this->Html->link(
							'<span class="pgm-nav-lucide" data-lucide="log-out" aria-hidden="true"></span><span class="nav-item-label hide-menu">Sair</span>',
							'/users/logout',
							['class' => 'pgm-nav-link nav-item waves-effect waves-dark pgm-nav-link--danger', 'escape' => false, 'data-label' => 'Sair']
						) ?>
					</div>
				</li>

			</ul>
		</nav>
	</div>

	<div class="pgm-sidebar-footer sidebar-footer">
		<div class="user-profile user-profile--footer">
			<div class="user-pro-body">
				<div class="dropdown dropup">
					<a href="javascript:void(0)" class="dropdown-toggle u-dropdown link user text-white d-flex align-items-center" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<div class="user-avatar pgm-user-av"><?= h($userInitials ?: '?') ?></div>
						<div class="user-info pgm-sf-user-info hide-menu">
							<div class="user-name pgm-sf-user-name"><?= h($name) ?></div>
							<div class="user-role pgm-sf-user-role"><?= !empty($admin) ? 'Administrador' : 'Usuário' ?></div>
						</div>
						<span class="caret hide-menu pgm-sidebar-user-caret" aria-hidden="true"></span>
					</a>
					<div class="dropdown-menu animated flipInY">
						<?php if (($sg['footer_perfil_senha'] ?? true)) : ?>
						<?= $this->Html->link('Alterar perfil', ['controller' => 'Users', 'action' => 'change_profile'], ['class' => 'dropdown-item']) ?>
						<?= $this->Html->link('Alterar senha', ['controller' => 'Users', 'action' => 'change_password'], ['class' => 'dropdown-item']) ?>
						<?php endif; ?>
						<?php if ($roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true)) : ?>
						<?= $this->Html->link('Notificações', '#', ['class' => 'dropdown-item', 'id' => 'pgmSidebarMenuOpenNotif', 'escape' => true]) ?>
						<?php endif; ?>
						<?php if (($sg['footer_acesso_remoto'] ?? true)) : ?>
						<?= $this->Html->link('Acesso remoto', ['controller' => 'normasempresa', 'action' => 'acessoremoto'], ['class' => 'dropdown-item']) ?>
						<?php endif; ?>
						<?php if (($sg['footer_twofactor_menu'] ?? true)) : ?>
						<?= $this->Html->link('Verificação de login', ['controller' => 'users', 'action' => 'loginduasetapas'], ['class' => 'dropdown-item']) ?>
						<?php endif; ?>
						<?php if (!empty($showConfigAdminHub)) : ?>
						<?= $this->Html->link('Painel administrativo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item']) ?>
						<?php elseif (!empty($showPermissoesRbacShortcut)) : ?>
						<?= $this->Html->link('Permissões RBAC / catálogo', ['controller' => 'config', 'action' => 'index'], ['class' => 'dropdown-item']) ?>
						<?php endif; ?>
						<div class="dropdown-divider"></div>
						<?= $this->Html->link('Sair', ['controller' => 'Users', 'action' => 'logout'], ['class' => 'dropdown-item text-danger']) ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ($roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true)) : ?>
		<div class="pgm-sidebar-notif-host" id="pgmSidebarNotifHost" aria-hidden="true"><?= $this->element('portal_notification_bell') ?></div>
		<?php endif; ?>

		<a href="javascript:void(0)" class="sidebartoggler pgm-sidebar-collapse-btn icon-btn" title="Colapsar sidebar" aria-label="Recolher menu lateral"><span data-lucide="chevrons-left" class="pgm-nav-lucide" id="collapseIcon" aria-hidden="true"></span></a>
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
	$(document).on('click', '#pgmSidebarMenuOpenNotif', function(e) {
		e.preventDefault();
		var $dd = $(this).closest('.dropdown');
		$dd.removeClass('show open');
		$dd.find('.dropdown-menu').removeClass('show');
		var $bell = $('#pgmBellToggle');
		if ($bell.length) {
			$bell.trigger('click');
		}
	});
	$(document).on('click', 'a.pgm-nav-link', function() {
		/* no-op: evita interferência com navegação */
	});
	var wsBtn = document.getElementById('wsBtn');
	var wsDropdown = document.getElementById('wsDropdown');
	var wsName = document.getElementById('wsName');
	var wsAvatar = document.getElementById('wsAvatar');
	var wsSearchInput = document.getElementById('wsSearchInput');
	if (wsBtn && wsDropdown) {
		wsBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			var isOpen = wsDropdown.classList.toggle('open');
			wsBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			if (isOpen && wsSearchInput) {
				setTimeout(function(){ wsSearchInput.focus(); }, 30);
			}
		});
		document.addEventListener('click', function(e) {
			if (!wsDropdown.contains(e.target) && !wsBtn.contains(e.target)) {
				wsDropdown.classList.remove('open');
				wsBtn.setAttribute('aria-expanded', 'false');
			}
		});
	}
	document.querySelectorAll('.workspace-item').forEach(function(item) {
		item.addEventListener('click', function() {
			document.querySelectorAll('.workspace-item').forEach(function(i){ i.classList.remove('active'); });
			item.classList.add('active');
			if (wsName) wsName.textContent = item.dataset.name || '';
			if (wsAvatar) wsAvatar.textContent = item.dataset.initials || 'PG';
			var empresaId = item.dataset.id || '';
			if (empresaId !== '') {
				$('#empresaSidebar').val(empresaId).trigger('change');
			}
			wsDropdown.classList.remove('open');
			if (wsBtn) wsBtn.setAttribute('aria-expanded', 'false');
		});
	});
	if (wsSearchInput) {
		wsSearchInput.addEventListener('input', function(e){
			var q = (e.target.value || '').toLowerCase();
			document.querySelectorAll('.workspace-item').forEach(function(item) {
				var txt = (item.dataset.name || '').toLowerCase();
				item.style.display = txt.indexOf(q) !== -1 ? '' : 'none';
			});
		});
	}
	$('#pesquisa-funcoes').on('keyup', function(e) {
		e.preventDefault();
		var q = $('#pesquisa-funcoes').val();
		if (!q || q.length < 2) {
			$('.htmlpesquisa').html('');
			return;
		}
		$.ajax({
			url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'pesquisa']);?>/" + encodeURIComponent(q),
			dataType: "json",
			success: function(data) {
				$('.htmlpesquisa').html('');
				if (!data || !data.length) {
					$('.htmlpesquisa').append('<li class="pgm-search-empty">Nenhum resultado encontrado.</li>');
					return;
				}
				$.each(data, function(key, array) {
					$('.htmlpesquisa').append('<li><a class="link link-btn pgm-search-link" data-controller="'+array.Controller+'" data-action="'+array.Action+'">'+array.ControllerQueAparece+ ' > ' +array.ActionQueAparece+'</a></li>');
				});
			}
		});
	});
	$(document).on("click", ".link-btn", function() {
		var controller = $(this).attr('data-controller');
		var action = $(this).attr('data-action');
		$.ajax({
			url: "<?= Router::url(['controller'=>'Pesquisa','action'=>'link']);?>/" + controller + '/' + action,
			success: function(data) { window.location = data; }
		});
	});
	document.addEventListener('keydown', function(e) {
		if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
			e.preventDefault();
			var searchInput = document.getElementById('pesquisa-funcoes');
			if (searchInput) searchInput.focus();
		}
	});
	setTimeout(function() {
		$('.pgm-nc').removeClass('in');
	}, 0);

	document.querySelectorAll('.nav-section-label').forEach(function(label) {
		label.addEventListener('click', function() {
			var sec = label.closest('.nav-section');
			if (sec) {
				sec.classList.toggle('collapsed');
			}
		});
		label.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				var secK = label.closest('.nav-section');
				if (secK) {
					secK.classList.toggle('collapsed');
				}
			}
		});
	});

	function pgmSidebarExpandAllSections() {
		document.querySelectorAll('.pgm-sidebar-shell .nav-section.collapsed').forEach(function(sec) {
			sec.classList.remove('collapsed');
		});
	}
	document.querySelectorAll('.sidebartoggler').forEach(function(btn) {
		btn.addEventListener('click', function() {
			setTimeout(function() {
				if (document.body.classList.contains('mini-sidebar')) {
					pgmSidebarExpandAllSections();
				}
				pgmSidebarLucideRefresh();
			}, 0);
		});
	});
</script>
