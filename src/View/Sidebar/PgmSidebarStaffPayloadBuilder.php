<?php
declare(strict_types=1);

namespace App\View\Sidebar;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;

/**
 * JSON para window.__PGM_SIDEBAR_PROPS__ (sidebar React staff).
 * Regras de visibilidade e rotas alinhadas a src/Template/Element/sidebar.ctp.
 */
final class PgmSidebarStaffPayloadBuilder
{
    /**
     * @param array<string, mixed> $ctx Output de PgmSidebarStaffContext::computeFromArray
     * @param array<string, mixed> $v viewVars do controlador
     * @return array<string, mixed>
     */
    public static function build(array $ctx, array $v, ServerRequest $request): array
    {
        $sg = $ctx['sg'];
        $roleNav = (int)$ctx['roleNav'];
        $admin = !empty($v['admin']);

        $activePath = self::requestActivePath($request);

        $sections = [];
        $dashboardItem = null;

        if (($sg['dashboard'] ?? true)) {
            $dashboardItem = [
                'icon' => 'layout-dashboard',
                'label' => ' Dashboard',
                'href' => self::u(['controller' => 'Users', 'action' => 'dashboard']),
                'dataLabel' => 'Dashboard',
                'active' => (bool)($v['dashboard'] ?? ''),
                'badgeHtml' => '',
                'target' => null,
                'rel' => null,
            ];
        }

        $sgCadGrp = ($sg['clientes'] ?? true) || ($sg['produtos'] ?? true) || ($sg['ativos'] ?? true);
        if ($sgCadGrp) {
            $items = [];
            if (($sg['clientes'] ?? true)) {
                $items[] = self::item('users', ' Clientes', ['controller' => 'Clientes', 'action' => 'index'], [], $ctx['clientesListNavActive'], '', 'Clientes');
            }
            if (($sg['clientes'] ?? true)) {
                $items[] = self::item('user-plus', ' Cadastrar clientes', ['controller' => 'Clientes', 'action' => 'add'], [], $ctx['clientesAddActive'], '', 'Cadastrar clientes');
            }
            if (($sg['produtos'] ?? true)) {
                $items[] = self::item('package', ' Produtos', ['controller' => 'Produtos', 'action' => 'index'], [], (bool)($v['produtosActive'] ?? ''), '', 'Produtos');
            }
            if (($sg['ativos'] ?? true)) {
                $items[] = self::item('cpu', ' Ativos', ['controller' => 'Ativos', 'action' => 'index'], ['data-turbo' => 'false'], (bool)($ctx['ativosActive'] ?? false), '', 'Ativos de TI');
            }
            $sections[] = [
                'id' => 'cadastros',
                'title' => 'Cadastros',
                'defaultOpen' => (bool)$ctx['pgmSbOpenCadastros'],
                'items' => array_values(array_filter($items)),
            ];
        }

        $sgIncGrp = ($sg['tickets_servicedesk'] ?? true) || ($sg['tickets_historico'] ?? true);
        if ($sgIncGrp) {
            $items = [];
            if (($sg['tickets_servicedesk'] ?? true)) {
                $items[] = self::item('headphones', ' Service Desk', ['controller' => 'Servicedesk', 'action' => 'index'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $ctx['ticketsServicedeskActive'], '<span class="badge badge-danger hide-menu">12</span>', 'Service Desk');
            }
            if ($roleNav === 0 && ($sg['tickets_servicedesk'] ?? true)) {
                $items[] = self::item('gauge', ' Dashboard operacional', ['controller' => 'Servicedesk', 'action' => 'operacional'], [], (bool)($ctx['ticketsOperacionalActive'] ?? false), '', 'Dashboard operacional');
            }
            if (($sg['tickets_historico'] ?? true)) {
                $items[] = self::item('history', ' Histórico', ['controller' => 'Tickets', 'action' => 'historico'], ['data-turbo' => 'false'], $ctx['ticketsHistoricoActive'], '', 'Histórico');
            }
            $sections[] = [
                'id' => 'gestao-incidentes',
                'title' => 'Gestão de Incidentes',
                'defaultOpen' => (bool)$ctx['pgmSbOpenIncidentes'],
                'items' => array_values(array_filter($items)),
            ];
        }

        $sgOsGrp = ($sg['ordensservico_list'] ?? true) || ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true))
            || ($admin && ($sg['queues'] ?? true));
        if ($sgOsGrp) {
            $items = [];
            if ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true)) {
                $items[] = self::item('file-plus', ' Nova ordem', ['controller' => 'Ordensservico', 'action' => 'add'], ['target' => '_blank', 'rel' => 'noopener noreferrer'], $ctx['osAddActive'], '', 'Nova ordem');
            }
            if (($sg['ordensservico_list'] ?? true)) {
                $badgeOs = $ctx['osIndexActive'] ? '<span class="pgm-os-badge badge badge-warning hide-menu" id="badge-exec-os">—</span>' : '';
                $items[] = self::item('clipboard-list', ' Ordens de Serviço', ['controller' => 'Ordensservico', 'action' => 'index'], [], $ctx['osIndexActive'], $badgeOs, 'Ordens de Serviço');
            }
            if ($admin && ($sg['queues'] ?? true)) {
                $items[] = self::item('layers', ' Filas / técnicos', ['controller' => 'Queues', 'action' => 'adminIndex'], [], (bool)($v['queuesAtendimentoActive'] ?? ''), '<span class="badge badge-warning hide-menu">7</span>', 'Filas / técnicos');
            }
            if (($sg['ordensservico_list'] ?? true)) {
                $items[] = self::item('bar-chart-2', ' Relatórios', ['controller' => 'Ordensservico', 'action' => 'relatorios'], [], $ctx['relatoriosOsActive'], '', 'Relatórios');
            }
            $sections[] = [
                'id' => 'ordens-servico',
                'title' => 'Ordens de Serviço',
                'defaultOpen' => (bool)$ctx['pgmSbOpenOrdens'],
                'items' => array_values(array_filter($items)),
            ];
        }

        $sgAdvContr = $roleNav === 0 && (
            ($sg['advanced_module_modelos'] ?? true)
            || ($sg['advanced_module_faturas'] ?? true)
            || ($sg['advanced_module_gestao'] ?? true)
        );
        $sgContrGrp = $sgAdvContr || ($sg['faturas_locacao'] ?? true);
        if ($sgContrGrp) {
            $items = [];
            if ($roleNav === 0 && ($sg['advanced_module_gestao'] ?? true)) {
                $items[] = self::item('handshake', ' Gestão de contratos', '/modulo-contratos', [], $ctx['advMgmtAct'], '', 'Gestão de contratos');
            }
            if (($sg['advanced_module_modelos'] ?? true)) {
                $items[] = self::item('file-code', ' Modelos', '/contract-templates', [], $ctx['advTplAct'], '', 'Modelos');
            }
            if (($sg['advanced_module_faturas'] ?? true)) {
                $items[] = self::item('receipt', ' Faturas', '/modulo-avancado/faturas', [], $ctx['advInvAct'], '<span class="badge badge-accent hide-menu">3</span>', 'Faturas');
            }
            if (($sg['faturas_locacao'] ?? true)) {
                $items[] = self::item('truck', ' Locação', ['controller' => 'Faturas', 'action' => 'index'], [], (bool)($v['faturasActive'] ?? ''), '', 'Locação');
            }
            $sections[] = [
                'id' => 'contratos',
                'title' => 'Contratos',
                'defaultOpen' => (bool)$ctx['pgmSbOpenContratos'],
                'items' => array_values(array_filter($items)),
            ];
        }

        if (($sg['orcamentos'] ?? true)) {
            $sections[] = [
                'id' => 'comercial',
                'title' => 'Comercial',
                'defaultOpen' => (bool)$ctx['pgmSbOpenComercial'],
                'items' => [
                    self::item('file-text', ' Orçamentos', ['controller' => 'Orcamentos', 'action' => 'index'], [], (bool)($v['orcamentosActive'] ?? ''), '', 'Orçamentos'),
                ],
            ];
        }

        $sgFatGrp = (($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true)) || ($sg['faturamento'] ?? true);
        if ($sgFatGrp) {
            $items = [];
            $sgPrefSec = ($sg['prefaturamento_fila'] ?? true) || ($sg['prefaturamento_conferencia'] ?? true);
            if ($sgPrefSec) {
                $items[] = self::item('clipboard-check', ' Pré-faturamento', ['controller' => 'Prefaturamento', 'action' => 'index'], [], (bool)($v['prefaturamentoActive'] ?? ''), '', 'Pré-faturamento');
            }
            if (($sg['faturamento'] ?? true)) {
                $items[] = self::item('file-check', ' Faturamento', ['controller' => 'Faturamento', 'action' => 'index'], [], (bool)($v['faturamentoActive'] ?? ''), '', 'Faturamento');
            }
            $sections[] = [
                'id' => 'faturamento',
                'title' => 'Faturamento',
                'defaultOpen' => (bool)$ctx['pgmSbOpenFaturamento'],
                'items' => array_values(array_filter($items)),
            ];
        }

        if (($sg['financeiro'] ?? true)) {
            $sections[] = [
                'id' => 'financeiro',
                'title' => 'Financeiro',
                'defaultOpen' => (bool)$ctx['pgmSbOpenFinanceiro'],
                'items' => [
                    self::item('pie-chart', ' Painel', ['controller' => 'Financeiro', 'action' => 'index'], [], $ctx['finDashAct'], '', 'Painel financeiro'),
                    self::item('arrow-down-circle', ' Contas a receber', ['controller' => 'Financeiro', 'action' => 'contasReceber'], [], $ctx['finRecAct'], '', 'Contas a receber'),
                    self::item('arrow-up-circle', ' Contas a pagar', ['controller' => 'Financeiro', 'action' => 'contasPagar'], [], $ctx['finPagAct'], '', 'Contas a pagar'),
                    self::item('activity', ' Fluxo de caixa', ['controller' => 'Financeiro', 'action' => 'fluxoCaixa'], [], $ctx['finFluxoAct'], '', 'Fluxo de caixa'),
                    self::item('repeat', ' Recorrentes', ['controller' => 'Financeiro', 'action' => 'recorrentes'], [], $ctx['finRecorAct'], '', 'Recorrentes'),
                    self::item('shuffle', ' Conciliação', ['controller' => 'Financeiro', 'action' => 'conciliacao'], [], $ctx['finConcAct'], '', 'Conciliação bancária'),
                    self::item('line-chart', ' DRE', ['controller' => 'Financeiro', 'action' => 'dre'], [], $ctx['finDreAct'], '', 'DRE'),
                    self::item('bar-chart-2', ' Relatórios financeiros', ['controller' => 'FinanceiroRelatorios', 'action' => 'index'], [], $ctx['finRelAct'], '', 'Relatórios financeiros'),
                    self::item('book-open', ' Plano de contas', ['controller' => 'FinanceiroConfig', 'action' => 'planoContas'], [], $ctx['finPlanoAct'], '', 'Plano de contas'),
                    self::item('folder-tree', ' Centros de custo', ['controller' => 'FinanceiroConfig', 'action' => 'centrosCusto'], [], $ctx['finCcAct'], '', 'Centros de custo'),
                ],
            ];
        }

        if (($sg['financeiro'] ?? true)) {
            $sections[] = [
                'id' => 'bancos',
                'title' => 'Bancos',
                'defaultOpen' => (bool)$ctx['pgmSbOpenBancos'],
                'items' => [
                    self::item('landmark', ' Cadastro', ['controller' => 'FinanceiroBancos', 'action' => 'index'], [], $ctx['finBancosAct'], '', 'Cadastro de bancos'),
                    self::item('send', ' Remessa', ['controller' => 'FinanceiroBancos', 'action' => 'remessa'], [], $ctx['finRemessaAct'], '', 'Remessa'),
                    self::item('inbox', ' Retorno', ['controller' => 'FinanceiroBancos', 'action' => 'retorno'], [], $ctx['finRetornoAct'], '', 'Retorno'),
                    self::item('table-2', ' Relatórios bancos', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'], [], $ctx['finRelBancosAct'], '', 'Relatórios bancos'),
                ],
            ];
        }

        $sgFiscalSec = ($sg['fiscal_modulo'] ?? true);
        if ($roleNav === 0 && $sgFiscalSec) {
            $items = [];
            if ($sg['fiscal_menu_dashboard'] ?? true) {
                $items[] = self::item('layout-grid', ' Painel', ['controller' => 'Fiscal', 'action' => 'index'], [], $ctx['fiscalDashAct'], '', 'Painel fiscal');
            }
            if ($sg['fiscal_menu_dfe_recebidos'] ?? true) {
                $items[] = self::item('mail', ' DF-e recebidos', ['controller' => 'Fiscal', 'action' => 'dfeRecebidos'], [], $ctx['fiscalDfeRecAct'], '<span class="badge badge-accent hide-menu">24</span>', 'DF-e recebidos');
            }
            if ($sg['fiscal_menu_notas'] ?? true) {
                $items[] = self::item('cloud-upload', ' Notas de saída', ['controller' => 'FiscalNotas', 'action' => 'index'], [], $ctx['fiscalNotasAct'], '', 'Notas de saída');
            }
            if ($sg['fiscal_menu_notas_entrada'] ?? true) {
                $items[] = self::item('cloud-download', ' Notas de entrada', ['controller' => 'FiscalNotasEntrada', 'action' => 'index'], [], $ctx['fiscalEntradaAct'], '', 'Notas de entrada');
            }
            if ($sg['fiscal_menu_notas'] ?? true) {
                $items[] = self::item('ban', ' Inutilizar (saída)', ['controller' => 'FiscalNotas', 'action' => 'inutilizarNumeracao'], [], $ctx['fiscalInutActSaida'], '', 'Inutilizar num. (saída)');
            }
            if ($sg['fiscal_menu_notas_entrada'] ?? true) {
                $items[] = self::item('ban', ' Inutilizar (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'inutilizarNumeracao'], [], $ctx['fiscalInutActEntrada'], '', 'Inutilizar num. (entrada)');
            }
            if ($sg['fiscal_menu_series_saida'] ?? true) {
                $items[] = self::item('hash', ' Séries (saída)', ['controller' => 'FiscalNotas', 'action' => 'controleSeries'], [], $ctx['fiscalSeriesSaidaAct'], '', 'Séries (saída)');
            }
            if ($sg['fiscal_menu_series_entrada'] ?? true) {
                $items[] = self::item('hash', ' Séries (entrada)', ['controller' => 'FiscalNotasEntrada', 'action' => 'controleSeries'], [], $ctx['fiscalSeriesEntradaAct'], '', 'Séries (entrada)');
            }
            if ($sg['fiscal_menu_consulta_chave'] ?? true) {
                $items[] = self::item('key', ' Consultar chave', ['controller' => 'FiscalNotas', 'action' => 'consultarChave'], [], $ctx['fiscalConsultaChaveAct'], '', 'Consultar chave');
            }
            if ($sg['fiscal_menu_consulta_cadastro'] ?? true) {
                $items[] = self::item('search', ' Consulta cadastral', ['controller' => 'FiscalNotas', 'action' => 'consultarCadastro'], [], $ctx['fiscalConsultaCadastroAct'], '', 'Consulta cadastral');
            }
            if ($sg['fiscal_menu_contingencia'] ?? true) {
                $items[] = self::item('cloud-off', ' Contingência', ['controller' => 'Fiscal', 'action' => 'contingencia'], [], $ctx['fiscalContingenciaAct'], '', 'Contingência');
            }
            if ($sg['fiscal_menu_importar_xml'] ?? true) {
                $items[] = self::item('folder-up', ' Importar XMLs', ['controller' => 'Fiscal', 'action' => 'importarXmlLote'], [], $ctx['fiscalImportarXmlAct'], '', 'Importar XMLs');
            }
            if ($sg['fiscal_menu_certificados'] ?? true) {
                $items[] = self::item('badge-check', ' Certificados', ['controller' => 'FiscalCertificados', 'action' => 'index'], [], $ctx['fiscalCertAct'], '', 'Certificados');
            }
            if ($sg['fiscal_menu_config'] ?? true) {
                $items[] = self::item('sliders', ' Configuração fiscal', ['controller' => 'FiscalConfig', 'action' => 'index'], [], $ctx['fiscalCfgAct'], '', 'Configuração fiscal');
            }
            if ($sg['fiscal_menu_relatorios'] ?? true) {
                $items[] = self::item('newspaper', ' Relatórios fiscais', ['controller' => 'FiscalRelatorios', 'action' => 'index'], [], $ctx['fiscalRelAct'], '', 'Relatórios fiscais');
            }

            $sections[] = [
                'id' => 'fiscal',
                'title' => 'Fiscal',
                'defaultOpen' => (bool)$ctx['pgmSbOpenFiscal'],
                'items' => array_values(array_filter($items)),
            ];
        }

        $sgRelSec = ($sg['relatorios_painel'] ?? true) || ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true));
        if ($sgRelSec) {
            $items = [];
            if (($sg['relatorios_painel'] ?? true)) {
                $items[] = self::item('pie-chart', ' Painel e indicadores', ['controller' => 'Relatorios', 'action' => 'index'], [], $ctx['relatoriosPainelActive'], '', 'Painel e indicadores');
            }
            if ($roleNav === 0 && ($sg['relatorios_indicadores_adv'] ?? true)) {
                $items[] = self::item('trending-up', ' Indicadores avançados', '/modulo-avancado/indicadores', [], $ctx['relatoriosIndicadoresAdvActive'], '', 'Indicadores avançados');
            }
            $sections[] = [
                'id' => 'indicadores',
                'title' => 'Indicadores',
                'defaultOpen' => (bool)$ctx['pgmSbOpenIndicadores'],
                'items' => array_values(array_filter($items)),
            ];
        }

        if (($sg['visitas_agenda'] ?? true)) {
            $sections[] = [
                'id' => 'planner',
                'title' => 'Planner',
                'defaultOpen' => (bool)$ctx['pgmSbOpenPlanner'],
                'items' => [
                    self::item('calendar', ' Agenda', ['controller' => 'Visitas', 'action' => 'calendario'], [], (bool)($v['visitasActive'] ?? ''), '', 'Agenda'),
                ],
            ];
        }

        if (($sg['bancosenhas'] ?? true)) {
            $sections[] = [
                'id' => 'cofre-senhas',
                'title' => 'Cofre de Senhas',
                'defaultOpen' => (bool)$ctx['pgmSbOpenCofre'],
                'items' => [
                    self::item('lock', ' Banco de Senhas', ['controller' => 'Bancosenhas', 'action' => 'index'], [], (bool)($v['senhasActive'] ?? ''), '', 'Banco de Senhas'),
                ],
            ];
        }

        $sections = array_values(array_filter($sections, static function ($sec) {
            return !empty($sec['items']);
        }));

        $empresasOpt = $ctx['empresasOptSidebar'];
        $companies = self::buildWorkspaceCompanies($empresasOpt);
        $currentEmpresaId = (string)($ctx['empresa'] ?? '');
        $current = self::findCompanyById($companies, $currentEmpresaId) ?? ($companies[0] ?? ['id' => '', 'name' => 'PGM', 'initials' => 'PG']);

        $footerLinks = [];
        if (($sg['footer_perfil_senha'] ?? true)) {
            $footerLinks[] = ['label' => 'Alterar perfil', 'href' => self::u(['controller' => 'Users', 'action' => 'change_profile'])];
            $footerLinks[] = ['label' => 'Alterar senha', 'href' => self::u(['controller' => 'Users', 'action' => 'change_password'])];
        }
        if ($roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true)) {
            $footerLinks[] = ['label' => 'Notificações', 'href' => '#', 'id' => 'pgmSidebarMenuOpenNotif'];
        }
        if (($sg['footer_acesso_remoto'] ?? true)) {
            $footerLinks[] = ['label' => 'Acesso remoto', 'href' => self::u(['controller' => 'normasempresa', 'action' => 'acessoremoto'])];
        }
        if (($sg['footer_twofactor_menu'] ?? true)) {
            $footerLinks[] = ['label' => 'Verificação de login', 'href' => self::u(['controller' => 'users', 'action' => 'loginduasetapas'])];
        }
        if (!empty($v['showConfigAdminHub'])) {
            $footerLinks[] = ['label' => 'Painel administrativo', 'href' => self::u(['controller' => 'config', 'action' => 'index'])];
        } elseif (!empty($v['showPermissoesRbacShortcut'])) {
            $footerLinks[] = ['label' => 'Permissões RBAC / catálogo', 'href' => self::u(['controller' => 'config', 'action' => 'index'])];
        }
        $footerLinks[] = ['label' => 'Sair', 'href' => self::u(['controller' => 'Users', 'action' => 'logout']), 'danger' => true];

        return [
            'variant' => 'staff',
            'activePath' => $activePath,
            'dashboardItem' => $dashboardItem,
            'sections' => $sections,
            'workspace' => [
                'sub' => 'Matriz',
                'currentId' => $currentEmpresaId,
                'currentName' => $current['name'],
                'currentInitials' => $current['initials'],
                'companies' => $companies,
                'empresaSelectOptions' => $empresasOpt,
                'empresaSelectValue' => $ctx['empresa'],
                'multiEmpresa' => (bool)$ctx['multiEmpresa'],
            ],
            'user' => [
                'name' => (string)($v['name'] ?? ''),
                'initials' => (string)($ctx['userInitials'] ?: '?'),
                'roleLabel' => $admin ? 'Administrador' : 'Usuário',
            ],
            'footerLinks' => $footerLinks,
            'notificationsBell' => $roleNav === 0 && ($sg['sidebar_notifications_bell'] ?? true),
        ];
    }

    private static function requestActivePath(ServerRequest $request): string
    {
        if (method_exists($request, 'getUri') && $request->getUri()) {
            $uri = $request->getUri();
            $path = $uri->getPath();
            $q = $uri->getQuery();
            if ($q !== '') {
                $path .= '?' . $q;
            }

            return $path;
        }
        $t = $request->getRequestTarget();
        if (is_string($t) && $t !== '') {
            return $t;
        }

        return '/';
    }

    /**
     * @param array|string $url
     */
    private static function u($url): string
    {
        return Router::url($url, false);
    }

    /**
     * @param array<string, mixed> $linkOpts
     * @return array<string, mixed>|null
     */
    private static function item(
        string $icon,
        string $label,
        $url,
        array $linkOpts,
        bool $active,
        string $badgeHtml,
        string $dataLabel
    ): ?array {
        $href = is_string($url) ? self::u($url) : self::u($url);
        $target = $linkOpts['target'] ?? null;
        $rel = $linkOpts['rel'] ?? null;
        $skipTurboFrame = isset($linkOpts['data-turbo']) && $linkOpts['data-turbo'] === 'false';

        return [
            'icon' => $icon,
            'label' => $label,
            'href' => $href,
            'dataLabel' => $dataLabel !== '' ? $dataLabel : strip_tags($label),
            'active' => $active,
            'badgeHtml' => $badgeHtml,
            'target' => $target,
            'rel' => $rel,
            'skipTurboFrame' => $skipTurboFrame,
        ];
    }

    /**
     * @param array<string|int, string> $empresasOpt
     * @return list<array{id: string, name: string, initials: string}>
     */
    private static function buildWorkspaceCompanies(array $empresasOpt): array
    {
        $out = [];
        foreach ($empresasOpt as $empresaId => $empresaNome) {
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
            $out[] = [
                'id' => (string)$empresaId,
                'name' => $nomeAtualDisplay,
                'initials' => $ini !== '' ? $ini : 'PG',
            ];
        }

        return $out;
    }

    /**
     * @param list<array{id: string, name: string, initials: string}> $companies
     * @return array{id: string, name: string, initials: string}|null
     */
    private static function findCompanyById(array $companies, string $id): ?array
    {
        foreach ($companies as $c) {
            if ($c['id'] === $id) {
                return $c;
            }
        }

        return null;
    }
}
