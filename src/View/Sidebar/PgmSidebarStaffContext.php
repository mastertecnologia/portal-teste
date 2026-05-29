<?php
declare(strict_types=1);

namespace App\View\Sidebar;

use App\Utility\PortalUi;
use Cake\Http\ServerRequest;

/**
 * Estado derivado do menu lateral staff (antes só em sidebar.ctp).
 * Usado pelo elemento Cake e por PgmSidebarStaffPayloadBuilder (React).
 */
final class PgmSidebarStaffContext
{
    /**
     * @param array<string, mixed> $v View variables (ex.: $this->viewVars no element).
     * @return array<string, mixed>
     */
    public static function computeFromArray(array $v, ServerRequest $request): array
    {
        $ctrl = (string)$request->getParam('controller');
        $act = (string)$request->getParam('action');
        $pass = $request->getParam('pass');
        $erpViewPage = is_array($pass) && isset($pass[0]) ? (string)$pass[0] : '';

        $roleNav = (int)($v['role'] ?? 1);
        $sg = isset($v['sidebarMenuGates']) && is_array($v['sidebarMenuGates']) ? $v['sidebarMenuGates'] : [];

        $osIndexActive = PortalUi::isOrdensNavActive($ctrl, $act);
        $osAddActive = ($ctrl === 'Ordensservico' && $act === 'add');
        $clientesAddActive = ($ctrl === 'Clientes' && $act === 'add');
        $clientesListNavActive = PortalUi::isClientesListNavActive($ctrl, $act);
        $produtosListNavActive = PortalUi::isProdutosNavActive($ctrl, $act);
        $produtosEstoqueNavActive = $ctrl === 'ProdutosPrototype' && $act === 'estoque';
        $fornecedoresNavActive = $ctrl === 'FornecedoresPrototype';
        $orcamentosListNavActive = PortalUi::isOrcamentosNavActive($ctrl, $act);
        $ativosActive = ($ctrl === 'Ativos');
        $ticketsOperacionalActive = ($ctrl === 'Servicedesk' && $act === 'operacional');
        $ticketsSlaRelatorioActive = ($ctrl === 'Servicedesk' && $act === 'slaRelatorio');
        $ticketsWorkflowSlaActive = ($ctrl === 'Servicedesk' && $act === 'workflowSlaAdmin');
		$ticketsSdPrototypeActive = PortalUi::isServicedeskHomeNavActive($ctrl, $act)
			&& $ctrl === 'ServicedeskPrototype';
		$ticketsServicedeskHomeActive = PortalUi::isServicedeskHomeNavActive($ctrl, $act);
		$ticketsServicedeskActive = $ticketsServicedeskHomeActive
			|| ($ctrl === 'Servicedesk' && !in_array($act, ['operacional', 'workflowSlaAdmin', 'slaRelatorio', 'index'], true));
        $ticketsHistoricoActive = ($ctrl === 'Tickets' && $act === 'historico');

        $advMgmtAct = ($ctrl === 'ContractManagement');
        $advTplAct = ($ctrl === 'ContractTemplates');
        $advInvAct = ($ctrl === 'AdvancedInvoices');

        $relatoriosPainelActive = ($ctrl === 'Relatorios');
        $relatoriosIndicadoresAdvActive = ($ctrl === 'AdvancedReports');

        $finDashAct = PortalUi::isFinanceiroNavActive($ctrl, $act);
        $finRecAct = ($ctrl === 'Financeiro' && in_array($act, ['contasReceber', 'addReceita', 'editReceita'], true))
            || ($ctrl === 'FinanceiroPrototype' && $act === 'titulos');
        $finPagAct = ($ctrl === 'Financeiro' && in_array($act, ['contasPagar', 'addDespesa', 'editDespesa'], true))
            || ($ctrl === 'FinanceiroPrototype' && $act === 'contasPagar');
        $finFluxoAct = ($ctrl === 'Financeiro' && $act === 'fluxoCaixa');
        $finRecorAct = ($ctrl === 'Financeiro' && in_array($act, ['recorrentes', 'addRecorrente', 'editRecorrente'], true));
        $finConcAct = ($ctrl === 'Financeiro' && $act === 'conciliacao');
        $finDreAct = ($ctrl === 'Financeiro' && $act === 'dre');
        $finRelAct = ($ctrl === 'FinanceiroRelatorios');
        $finPlanoAct = ($ctrl === 'FinanceiroConfig' && in_array($act, ['planoContas', 'planoContasAdd', 'planoContasEdit'], true));
        $finCcAct = ($ctrl === 'FinanceiroConfig' && in_array($act, ['centrosCusto', 'centroCustoAdd', 'centroCustoEdit'], true));
        $finBancosAct = PortalUi::isBancosNavActive($ctrl, $act)
            || ($ctrl === 'FinanceiroBancos' && in_array($act, ['cadastrar', 'add', 'edit', 'delete', 'buscarCatalogo', 'bootstrapBancoPorCodigo'], true));
        $finRemessaAct = ($ctrl === 'FinanceiroBancos' && in_array($act, ['remessa', 'remessaMultiempresas'], true))
            || ($ctrl === 'BancosPrototype' && $act === 'view' && $erpViewPage === 'remessa');
        $finRetornoAct = ($ctrl === 'FinanceiroBancos' && $act === 'retorno')
            || ($ctrl === 'BancosPrototype' && $act === 'view' && $erpViewPage === 'retorno');
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

        $queuesAtendimentoActive = $v['queuesAtendimentoActive'] ?? null;
        $faturasActive = $v['faturasActive'] ?? null;
        $visitasActive = $v['visitasActive'] ?? null;
        $senhasActive = $v['senhasActive'] ?? null;
        $clientesActive = $v['clientesActive'] ?? null;
        $produtosActive = $v['produtosActive'] ?? null;
        $orcamentosActive = $v['orcamentosActive'] ?? null;
        $prefaturamentoActive = $v['prefaturamentoActive'] ?? null;
        $faturamentoActive = $v['faturamentoActive'] ?? null;

        $pgmSbOpenOrdens = $osIndexActive || $osAddActive || (bool)$queuesAtendimentoActive || $relatoriosOsActive;
        $pgmSbOpenContratos = $advMgmtAct || $advTplAct || $advInvAct || (bool)$faturasActive;
        $pgmSbOpenIndicadores = $relatoriosPainelActive || $relatoriosIndicadoresAdvActive;
        $pgmSbOpenPlanner = (bool)$visitasActive;
        $pgmSbOpenCofre = (bool)$senhasActive;
        $erpHomeActive = PortalUi::isErpHomeNavActive($ctrl, $act);
        $pgmSbOpenCadastros = (bool)$clientesActive || $clientesAddActive || (bool)$produtosActive
            || $produtosListNavActive || $produtosEstoqueNavActive || $ativosActive || $fornecedoresNavActive;
        $pgmSbOpenPrincipal = $pgmSbOpenCadastros || $erpHomeActive;
        $ticketsIncidentesConfigOpen = $ticketsWorkflowSlaActive || ($roleNav === 0 && $ticketsHistoricoActive);
        $pgmSbOpenIncidentes = $ticketsServicedeskActive || $ticketsSdPrototypeActive || $ticketsHistoricoActive || $ticketsOperacionalActive || $ticketsWorkflowSlaActive || $ticketsSlaRelatorioActive;
        $pgmSbOpenOperacoes = (bool)$orcamentosListNavActive || $pgmSbOpenOrdens || $pgmSbOpenIncidentes
            || (bool)$orcamentosActive;
        $pgmSbOpenSdModulos = $ctrl === 'ServicedeskPrototype';
        $pgmSbOpenPcp = $ctrl === 'PcpPrototype';
        $pgmSbOpenSistema = in_array($ctrl, ['SistemaPrototype', 'EmpresasPrototype', 'PrototypeHistory'], true);
        $pgmSbOpenComercial = (bool)$orcamentosActive || $orcamentosListNavActive;
        $pgmSbOpenFaturamento = (bool)$prefaturamentoActive || (bool)$faturamentoActive;
        $pgmSbOpenFinanceiro = $finDashAct || $finRecAct || $finPagAct || $finFluxoAct || $finRecorAct || $finConcAct || $finDreAct || $finRelAct || $finPlanoAct || $finCcAct;
        $pgmSbOpenBancos = $finBancosAct || $finRemessaAct || $finRetornoAct || $finRelBancosAct;
        $erpPremiumNavOpen = PortalUi::showPremiumNav() && (
            $erpHomeActive
            || in_array($ctrl, [
                'FornecedoresPrototype',
                'FinanceiroPrototype',
                'BancosPrototype',
                'PcpPrototype',
                'EmpresasPrototype',
                'SistemaPrototype',
            ], true)
        );

        $pgmSbOpenFiscal = $fiscalDashAct || $fiscalDfeRecAct || $fiscalNotasAct || $fiscalEntradaAct
            || $fiscalInutActSaida || $fiscalInutActEntrada || $fiscalSeriesSaidaAct || $fiscalSeriesEntradaAct
            || $fiscalCertAct || $fiscalCfgAct || $fiscalRelAct || $fiscalConsultaChaveAct || $fiscalConsultaCadastroAct
            || $fiscalContingenciaAct || $fiscalImportarXmlAct;

        $nameTrim = trim((string)($v['name'] ?? ''));
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

        $empresasOptSidebar = $v['empresasOptSidebar'] ?? [];
        if (!is_array($empresasOptSidebar)) {
            $empresasOptSidebar = [];
        }
        $multiEmpresa = count($empresasOptSidebar) > 1;

        return [
            'ctrl' => $ctrl,
            'act' => $act,
            'roleNav' => $roleNav,
            'sg' => $sg,
            'osIndexActive' => $osIndexActive,
            'osAddActive' => $osAddActive,
            'clientesAddActive' => $clientesAddActive,
            'clientesListNavActive' => $clientesListNavActive,
            'produtosListNavActive' => $produtosListNavActive,
            'produtosEstoqueNavActive' => $produtosEstoqueNavActive,
            'fornecedoresNavActive' => $fornecedoresNavActive,
            'erpNavActive' => (string)($v['erpNavActive'] ?? ''),
            'erpViewPage' => $erpViewPage,
            'orcamentosListNavActive' => $orcamentosListNavActive,
            'ativosActive' => $ativosActive,
            'ticketsServicedeskActive' => $ticketsServicedeskActive,
            'ticketsSdPrototypeActive' => $ticketsSdPrototypeActive,
            'ticketsServicedeskHomeActive' => $ticketsServicedeskHomeActive,
            'ticketsOperacionalActive' => $ticketsOperacionalActive,
            'ticketsSlaRelatorioActive' => $ticketsSlaRelatorioActive,
            'ticketsWorkflowSlaActive' => $ticketsWorkflowSlaActive,
            'ticketsHistoricoActive' => $ticketsHistoricoActive,
            'ticketsIncidentesConfigOpen' => $ticketsIncidentesConfigOpen,
            'advMgmtAct' => $advMgmtAct,
            'advTplAct' => $advTplAct,
            'advInvAct' => $advInvAct,
            'relatoriosPainelActive' => $relatoriosPainelActive,
            'relatoriosIndicadoresAdvActive' => $relatoriosIndicadoresAdvActive,
            'finDashAct' => $finDashAct,
            'finRecAct' => $finRecAct,
            'finPagAct' => $finPagAct,
            'finFluxoAct' => $finFluxoAct,
            'finRecorAct' => $finRecorAct,
            'finConcAct' => $finConcAct,
            'finDreAct' => $finDreAct,
            'finRelAct' => $finRelAct,
            'finPlanoAct' => $finPlanoAct,
            'finCcAct' => $finCcAct,
            'finBancosAct' => $finBancosAct,
            'finRemessaAct' => $finRemessaAct,
            'finRetornoAct' => $finRetornoAct,
            'finRelBancosAct' => $finRelBancosAct,
            'fiscalDashAct' => $fiscalDashAct,
            'fiscalDfeRecAct' => $fiscalDfeRecAct,
            'fiscalNotasAct' => $fiscalNotasAct,
            'fiscalEntradaAct' => $fiscalEntradaAct,
            'fiscalInutActSaida' => $fiscalInutActSaida,
            'fiscalInutActEntrada' => $fiscalInutActEntrada,
            'fiscalSeriesSaidaAct' => $fiscalSeriesSaidaAct,
            'fiscalSeriesEntradaAct' => $fiscalSeriesEntradaAct,
            'fiscalCertAct' => $fiscalCertAct,
            'fiscalCfgAct' => $fiscalCfgAct,
            'fiscalRelAct' => $fiscalRelAct,
            'fiscalConsultaChaveAct' => $fiscalConsultaChaveAct,
            'fiscalConsultaCadastroAct' => $fiscalConsultaCadastroAct,
            'fiscalContingenciaAct' => $fiscalContingenciaAct,
            'fiscalImportarXmlAct' => $fiscalImportarXmlAct,
            'relatoriosOsActive' => $relatoriosOsActive,
            'pgmSbOpenOrdens' => $pgmSbOpenOrdens,
            'pgmSbOpenContratos' => $pgmSbOpenContratos,
            'pgmSbOpenIndicadores' => $pgmSbOpenIndicadores,
            'pgmSbOpenPlanner' => $pgmSbOpenPlanner,
            'pgmSbOpenCofre' => $pgmSbOpenCofre,
            'pgmSbOpenCadastros' => $pgmSbOpenCadastros,
            'pgmSbOpenPrincipal' => $pgmSbOpenPrincipal,
            'pgmSbOpenOperacoes' => $pgmSbOpenOperacoes,
            'pgmSbOpenSdModulos' => $pgmSbOpenSdModulos,
            'pgmSbOpenPcp' => $pgmSbOpenPcp,
            'pgmSbOpenSistema' => $pgmSbOpenSistema,
            'pgmSbOpenIncidentes' => $pgmSbOpenIncidentes,
            'pgmSbOpenComercial' => $pgmSbOpenComercial,
            'pgmSbOpenFaturamento' => $pgmSbOpenFaturamento,
            'pgmSbOpenFinanceiro' => $pgmSbOpenFinanceiro,
            'pgmSbOpenBancos' => $pgmSbOpenBancos,
            'pgmSbOpenFiscal' => $pgmSbOpenFiscal,
            'erpHomeActive' => $erpHomeActive,
            'erpPremiumNavOpen' => $erpPremiumNavOpen,
            'userInitials' => $userInitials,
            'multiEmpresa' => $multiEmpresa,
            'empresasOptSidebar' => $empresasOptSidebar,
            'empresa' => $v['empresa'] ?? null,
            'nomeempresa' => $v['nomeempresa'] ?? null,
        ];
    }
}
