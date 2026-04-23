<?php
	use Cake\Routing\Router;
	require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');

	use App\View\Sidebar\PgmSidebarStaffContext;

	$__pgmSbPass = get_defined_vars();
	unset($__pgmSbPass['this'], $__pgmSbPass['__pgmSbPass']);
	extract(PgmSidebarStaffContext::computeFromArray($__pgmSbPass, $this->request), EXTR_OVERWRITE);

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
	$pgmWsNomeDisplay = function_exists('mb_strtoupper') ? mb_strtoupper($currentEmpresaNome, 'UTF-8') : strtoupper($currentEmpresaNome);
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
<aside class="left-sidebar skin-pgm pgm-sidebar-shell" id="sidebar" data-turbo="true">

	<div class="workspace">
		<button class="workspace-btn" id="wsBtn" aria-expanded="false" type="button">
			<div class="workspace-avatar" id="wsAvatar"><?= h($empresaInitials ?: 'PG') ?></div>
			<div class="workspace-info">
				<div class="workspace-name" id="wsName"><?= h($pgmWsNomeDisplay) ?></div>
				<div class="workspace-sub" id="wsSub">Matriz</div>
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
					$nomeAtualDisplay = function_exists('mb_strtoupper') ? mb_strtoupper($nomeAtual, 'UTF-8') : strtoupper($nomeAtual);
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
				<button class="workspace-item<?= $isEmpresaAtiva ? ' active' : '' ?>" data-id="<?= h((string)$empresaId) ?>" data-name="<?= h($nomeAtualDisplay) ?>" data-initials="<?= h($ini ?: 'PG') ?>" type="button">
					<div class="workspace-item-avatar"><?= h($ini ?: 'PG') ?></div>
					<span class="workspace-item-name"><?= h($nomeAtualDisplay) ?></span>
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

	<div class="pgm-sidebar-hidden-dt-host" aria-hidden="true" style="display:none;">
		<div id="pgm-sidebar-functions-search"></div>
		<div id="pgm-sidebar-dt-host" class="pgm-sidebar-dt-host pgm-sidebar-dt-host--pending"></div>
	</div>

	<div class="scroll-sidebar ps ps--theme_default ps--active-y" data-ps-id="5c23612c-2012-1d1a-2b77-a7091df065d9">
		<nav class="sidebar-nav">
			<ul id="sidebarnav" class="pgm-sidebar-nav-flat nav">

				<li class="nav-section-flat">
					<div class="nav-section-items" style="padding: 2px 0;">
						<?php if (($sg['dashboard'] ?? true)) : ?>
						<?= $pgmSbLink('layout-dashboard', ' Dashboard', ['controller' => 'Users', 'action' => 'dashboard'], ['aria-label' => 'Dashboard'], (bool)($dashboard ?? ''), '', 'Dashboard') ?>
						<?php endif; ?>
					</div>
				</li>

				<?php
				$sgCadGrp = ($sg['clientes'] ?? true) || ($sg['produtos'] ?? true);
				if ($sgCadGrp) :
				?>
				<li class="nav-section<?= $pgmSbOpenCadastros ? '' : ' collapsed' ?>" data-pgm-nav-section="cadastros">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenCadastros ? 'true' : 'false' ?>">
						<span>Cadastros</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if (($sg['clientes'] ?? true)) : ?>
						<?= $pgmSbLink('users', ' Clientes', ['controller' => 'Clientes', 'action' => 'index'], [], $clientesListNavActive, '', 'Clientes') ?>
						<?= $pgmSbLink('user-plus', ' Cadastrar clientes', ['controller' => 'Clientes', 'action' => 'add'], [], $clientesAddActive, '', 'Cadastrar clientes') ?>
						<?php endif; ?>
						<?php if (($sg['produtos'] ?? true)) : ?>
						<?= $pgmSbLink('package', ' Produtos', ['controller' => 'Produtos', 'action' => 'index'], [], (bool)($produtosActive ?? ''), '', 'Produtos') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php
				$sgIncGrp = ($sg['tickets_servicedesk'] ?? true) || ($sg['tickets_historico'] ?? true);
				if ($sgIncGrp) :
				?>
				<li class="nav-section<?= $pgmSbOpenIncidentes ? '' : ' collapsed' ?>" data-pgm-nav-section="gestao-incidentes">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenIncidentes ? 'true' : 'false' ?>">
						<span>Gestão de Incidentes</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if (($sg['tickets_servicedesk'] ?? true)) : ?>
						<?= $pgmSbLink('headphones', ' Service Desk', ['controller' => 'Servicedesk', 'action' => 'index'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $ticketsServicedeskActive, '<span class="badge badge-danger hide-menu">12</span>', 'Service Desk') ?>
						<?php endif; ?>
						<?php if ($roleNav === 0 && ($sg['tickets_servicedesk'] ?? true)) : ?>
						<?= $pgmSbLink('gauge', ' Dashboard operacional', ['controller' => 'Servicedesk', 'action' => 'operacional'], [], $ticketsOperacionalActive, '', 'Dashboard operacional') ?>
						<?php endif; ?>
						<?php if (($sg['tickets_historico'] ?? true)) : ?>
						<?= $pgmSbLink('history', ' Histórico', ['controller' => 'Tickets', 'action' => 'historico'], ['data-turbo' => 'false'], $ticketsHistoricoActive, '', 'Histórico') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php
				$sgOsGrp = ($sg['ordensservico_list'] ?? true) || ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true))
					|| (!empty($admin) && ($sg['queues'] ?? true));
				if ($sgOsGrp) :
				?>
				<li class="nav-section<?= $pgmSbOpenOrdens ? '' : ' collapsed' ?>" data-pgm-nav-section="ordens-servico">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenOrdens ? 'true' : 'false' ?>">
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
				$sgAdvContr = $roleNav === 0 && (
					($sg['advanced_module_modelos'] ?? true)
					|| ($sg['advanced_module_faturas'] ?? true)
					|| ($sg['advanced_module_gestao'] ?? true)
				);
				$sgContrGrp = $sgAdvContr || ($sg['faturas_locacao'] ?? true);
				if ($sgContrGrp) :
				?>
				<li class="nav-section<?= $pgmSbOpenContratos ? '' : ' collapsed' ?>" data-pgm-nav-section="contratos">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenContratos ? 'true' : 'false' ?>">
						<span>Contratos</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if ($roleNav === 0 && ($sg['advanced_module_gestao'] ?? true)) : ?>
						<?= $pgmSbLink('handshake', ' Gestão de contratos', '/modulo-contratos', [], $advMgmtAct, '', 'Gestão de contratos') ?>
						<?php endif; ?>
						<?php if (($sg['advanced_module_modelos'] ?? true)) : ?>
						<?= $pgmSbLink('file-code', ' Modelos', '/contract-templates', [], $advTplAct, '', 'Modelos') ?>
						<?php endif; ?>
						<?php if (($sg['advanced_module_faturas'] ?? true)) : ?>
						<?= $pgmSbLink('receipt', ' Faturas', '/modulo-avancado/faturas', [], $advInvAct, '<span class="badge badge-accent hide-menu">3</span>', 'Faturas') ?>
						<?php endif; ?>
						<?php if (($sg['faturas_locacao'] ?? true)) : ?>
						<?= $pgmSbLink('truck', ' Locação', ['controller' => 'Faturas', 'action' => 'index'], [], (bool)($faturasActive ?? ''), '', 'Locação') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['orcamentos'] ?? true)) : ?>
				<li class="nav-section<?= $pgmSbOpenComercial ? '' : ' collapsed' ?>" data-pgm-nav-section="comercial">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenComercial ? 'true' : 'false' ?>">
						<span>Comercial</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?= $pgmSbLink('file-text', ' Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], [], (bool)($orcamentosActive ?? ''), '', 'Orçamentos') ?>
					</div>
				</li>
				<?php endif; ?>

				<?php
				$sgFatGrp = (($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true)) || ($sg['faturamento'] ?? true);
				if ($sgFatGrp) :
				?>
				<li class="nav-section<?= $pgmSbOpenFaturamento ? '' : ' collapsed' ?>" data-pgm-nav-section="faturamento">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenFaturamento ? 'true' : 'false' ?>">
						<span>Faturamento</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php
						$sgPrefSec = ($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true);
						if ($sgPrefSec) :
						?>
						<?= $pgmSbLink('clipboard-check', ' Pré-faturamento', ['controller' => 'Prefaturamento', 'action' => 'index'], [], (bool)($prefaturamentoActive ?? ''), '', 'Pré-faturamento') ?>
						<?php endif; ?>
						<?php if (($sg['faturamento'] ?? true)) : ?>
						<?= $pgmSbLink('file-check', ' Faturamento', ['controller' => 'Faturamento', 'action' => 'index'], [], (bool)($faturamentoActive ?? ''), '', 'Faturamento') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['financeiro'] ?? true)) : ?>
				<li class="nav-section<?= $pgmSbOpenFinanceiro ? '' : ' collapsed' ?>" data-pgm-nav-section="financeiro">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenFinanceiro ? 'true' : 'false' ?>">
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
				<li class="nav-section<?= $pgmSbOpenBancos ? '' : ' collapsed' ?>" data-pgm-nav-section="bancos">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenBancos ? 'true' : 'false' ?>">
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
				<li class="nav-section<?= $pgmSbOpenFiscal ? '' : ' collapsed' ?>" data-pgm-nav-section="fiscal">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenFiscal ? 'true' : 'false' ?>">
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

				<?php
				$sgRelSec = ($sg['relatorios_painel'] ?? true) || ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true));
				if ($sgRelSec) :
				?>
				<li class="nav-section<?= $pgmSbOpenIndicadores ? '' : ' collapsed' ?>" data-pgm-nav-section="indicadores">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenIndicadores ? 'true' : 'false' ?>">
						<span>Indicadores</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?php if (($sg['relatorios_painel'] ?? true)) : ?>
						<?= $pgmSbLink('pie-chart', ' Painel e indicadores', ['controller' => 'Relatorios', 'action' => 'index'], [], $relatoriosPainelActive, '', 'Painel e indicadores') ?>
						<?php endif; ?>
						<?php if ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true)) : ?>
						<?= $pgmSbLink('trending-up', ' Indicadores avançados', '/modulo-avancado/indicadores', [], $relatoriosIndicadoresAdvActive, '', 'Indicadores avançados') ?>
						<?php endif; ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['visitas_agenda'] ?? true)) : ?>
				<li class="nav-section<?= $pgmSbOpenPlanner ? '' : ' collapsed' ?>" data-pgm-nav-section="planner">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenPlanner ? 'true' : 'false' ?>">
						<span>Planner</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?= $pgmSbLink('calendar', ' Agenda', ['controller' => 'Visitas', 'action' => 'calendario'], [], (bool)($visitasActive ?? ''), '', 'Agenda') ?>
					</div>
				</li>
				<?php endif; ?>

				<?php if (($sg['bancosenhas'] ?? true)) : ?>
				<li class="nav-section<?= $pgmSbOpenCofre ? '' : ' collapsed' ?>" data-pgm-nav-section="cofre-senhas">
					<div class="nav-section-label" role="button" tabindex="0" aria-expanded="<?= $pgmSbOpenCofre ? 'true' : 'false' ?>">
						<span>Cofre de Senhas</span>
						<svg class="chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
					</div>
					<div class="nav-section-items">
						<?= $pgmSbLink('lock', ' Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], [], (bool)($senhasActive ?? ''), '', 'Banco de Senhas') ?>
					</div>
				</li>
				<?php endif; ?>

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
	setTimeout(function() {
		$('.pgm-nc').removeClass('in');
	}, 0);

	var PGM_SB_NAV_KEY = 'pgmSidebarSectionExpanded';

	function pgmSidebarGetNavSectionStates() {
		try {
			var raw = localStorage.getItem(PGM_SB_NAV_KEY);
			return raw ? JSON.parse(raw) : {};
		} catch (e) {
			return {};
		}
	}

	function pgmSidebarSetNavSectionState(id, expanded) {
		if (!id) {
			return;
		}
		var o = pgmSidebarGetNavSectionStates();
		o[id] = expanded;
		localStorage.setItem(PGM_SB_NAV_KEY, JSON.stringify(o));
	}

	function pgmSidebarSyncNavSectionDom(sec, expanded) {
		var label = sec.querySelector('.nav-section-label');
		if (expanded) {
			sec.classList.remove('collapsed');
			if (label) {
				label.setAttribute('aria-expanded', 'true');
			}
		} else {
			sec.classList.add('collapsed');
			if (label) {
				label.setAttribute('aria-expanded', 'false');
			}
		}
	}

	function pgmSidebarApplyNavSectionStates() {
		var states = pgmSidebarGetNavSectionStates();
		document.querySelectorAll('.pgm-sidebar-shell #sidebarnav li.nav-section[data-pgm-nav-section]').forEach(function(sec) {
			var id = sec.getAttribute('data-pgm-nav-section');
			var hasActive = !!sec.querySelector('a.pgm-nav-link.active');
			var expanded;
			if (hasActive) {
				expanded = true;
				states[id] = true;
			} else if (Object.prototype.hasOwnProperty.call(states, id)) {
				expanded = !!states[id];
			} else {
				expanded = false;
			}
			pgmSidebarSyncNavSectionDom(sec, expanded);
		});
		localStorage.setItem(PGM_SB_NAV_KEY, JSON.stringify(states));
	}

	function pgmSidebarToggleNavSection(sec) {
		if (!sec || !sec.getAttribute('data-pgm-nav-section')) {
			return;
		}
		var expanded = sec.classList.contains('collapsed');
		pgmSidebarSyncNavSectionDom(sec, expanded);
		pgmSidebarSetNavSectionState(sec.getAttribute('data-pgm-nav-section'), expanded);
	}

	document.querySelectorAll('.nav-section-label').forEach(function(label) {
		label.addEventListener('click', function() {
			var sec = label.closest('.nav-section');
			if (sec && sec.getAttribute('data-pgm-nav-section')) {
				pgmSidebarToggleNavSection(sec);
			}
		});
		label.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				var secK = label.closest('.nav-section');
				if (secK && secK.getAttribute('data-pgm-nav-section')) {
					pgmSidebarToggleNavSection(secK);
				}
			}
		});
	});

	function pgmSidebarExpandAllSections() {
		document.querySelectorAll('.pgm-sidebar-shell .nav-section.collapsed').forEach(function(sec) {
			sec.classList.remove('collapsed');
		});
		document.querySelectorAll('.pgm-sidebar-shell #sidebarnav .nav-section-label').forEach(function(lab) {
			lab.setAttribute('aria-expanded', 'true');
		});
	}
	document.querySelectorAll('.sidebartoggler').forEach(function(btn) {
		btn.addEventListener('click', function() {
			setTimeout(function() {
				if (document.body.classList.contains('mini-sidebar')) {
					pgmSidebarExpandAllSections();
				} else {
					pgmSidebarApplyNavSectionStates();
				}
				pgmSidebarLucideRefresh();
			}, 0);
		});
	});
	pgmSidebarApplyNavSectionStates();
</script>
