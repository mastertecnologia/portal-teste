<?php
declare(strict_types=1);

namespace App\View\Sidebar;

use App\Utility\ErpPrototypeAccess;
use App\Utility\PortalUi;

/**
 * Menu lateral staff unificado (sidebar legado/React + itens do mock ErpPrototype).
 */
final class PgmSidebarStaffNavRegistry
{
    /**
     * @param callable(string,string,array|string,array,bool,string,string): array<string,mixed>|null $mkItem
     * @param callable(string,string,string,array,bool): array<string,mixed> $mkGroup
     * @return list<array<string,mixed>>
     */
    public static function buildMergedSections(
        array $ctx,
        array $sg,
        int $roleNav,
        bool $admin,
        int $userId,
        string $ctrl,
        callable $mkItem,
        callable $mkGroup
    ): array {
        if ($roleNav !== 0) {
            return [];
        }

        $sections = [];
        $principal = self::sectionPrincipal($ctx, $sg, $mkItem);
        if ($principal !== null) {
            $sections[] = $principal;
        }
        $operacoes = self::sectionOperacoes($ctx, $sg, $roleNav, $admin, $userId, $ctrl, $mkItem, $mkGroup);
        if ($operacoes !== null) {
            $sections[] = $operacoes;
        }
        $pcp = self::sectionPcp($ctx, $sg, $admin, $userId, $ctrl, $mkItem);
        if ($pcp !== null) {
            $sections[] = $pcp;
        }
        $financeiro = self::sectionFinanceiro($ctx, $sg, $mkItem);
        if ($financeiro !== null) {
            $sections[] = $financeiro;
        }
        $bancos = self::sectionBancos($ctx, $sg, $mkItem);
        if ($bancos !== null) {
            $sections[] = $bancos;
        }
        $licenciamento = self::sectionLicenciamento($ctx, $admin, $userId, $ctrl, $mkItem);
        if ($licenciamento !== null) {
            $sections[] = $licenciamento;
        }
        $sistema = self::sectionSistema($ctx, $sg, $admin, $userId, $ctrl, $mkItem);
        if ($sistema !== null) {
            $sections[] = $sistema;
        }

        return $sections;
    }

    /**
     * @param callable $mkItem
     * @return array<string,mixed>|null
     */
    private static function sectionPrincipal(array $ctx, array $sg, callable $mkItem): ?array
    {
        $items = [];
        if ($sg['clientes'] ?? true) {
            $items[] = $mkItem(
                'users',
                ' Clientes',
                PortalUi::listRoute('clientes') ?? ['controller' => 'Clientes', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['clientesListNavActive'] ?? false),
                '',
                'Clientes'
            );
        }
        if ($sg['clientes'] ?? true) {
            $items[] = $mkItem(
                'user-plus',
                ' Cadastrar clientes',
                ['controller' => 'Clientes', 'action' => 'add'],
                [],
                (bool)($ctx['clientesAddActive'] ?? false),
                '',
                'Cadastrar clientes'
            );
        }
        if ($sg['produtos'] ?? true) {
            $items[] = $mkItem(
                'package',
                ' Produtos',
                PortalUi::listRoute('produtos') ?? ['controller' => 'Produtos', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['produtosListNavActive'] ?? false),
                '',
                'Produtos'
            );
            $items[] = $mkItem(
                'boxes',
                ' Estoque',
                PortalUi::produtosEstoqueRoute(),
                ['data-turbo' => 'false'],
                (bool)($ctx['produtosEstoqueNavActive'] ?? false),
                '',
                'Estoque'
            );
            $items[] = $mkItem(
                'tags',
                ' Tabela de preços',
                ['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos'],
                ['data-turbo' => 'false'],
                self::ctrlActive($ctx, 'ProdutosPrototype', ['view'], 'precos'),
                '',
                'Tabela de preços'
            );
            $items[] = $mkItem(
                'history',
                ' Histórico preços',
                ['controller' => 'ProdutosPrototype', 'action' => 'view', 'historico-precos'],
                ['data-turbo' => 'false'],
                self::ctrlActive($ctx, 'ProdutosPrototype', ['view'], 'historico-precos'),
                '',
                'Histórico preços'
            );
        }
        if ($sg['clientes'] ?? true) {
            $forn = PortalUi::listRoute('fornecedores');
            if ($forn !== null) {
                $items[] = $mkItem(
                    'building-2',
                    ' Fornecedores',
                    $forn,
                    ['data-turbo' => 'false'],
                    (bool)($ctx['fornecedoresNavActive'] ?? false),
                    '',
                    'Fornecedores'
                );
            }
        }
        if ($sg['ativos'] ?? true) {
            $items[] = $mkItem(
                'cpu',
                ' Ativos',
                ['controller' => 'Ativos', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['ativosActive'] ?? false),
                '',
                'Ativos de TI'
            );
        }
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'principal',
            'title' => 'Principal',
            'defaultOpen' => (bool)($ctx['pgmSbOpenPrincipal'] ?? $ctx['pgmSbOpenCadastros'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function sectionOperacoes(
        array $ctx,
        array $sg,
        int $roleNav,
        bool $admin,
        int $userId,
        string $ctrl,
        callable $mkItem,
        callable $mkGroup
    ): ?array {
        $items = [];
        if ($sg['orcamentos'] ?? true) {
            $items[] = $mkItem(
                'file-text',
                ' Orçamentos',
                PortalUi::listRoute('orcamentos') ?? ['controller' => 'Orcamentos', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['orcamentosListNavActive'] ?? false),
                '',
                'Orçamentos'
            );
            $items[] = $mkItem(
                'award',
                ' Vendedores · Ranking',
                ['controller' => 'Relatorios', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['relatoriosPainelActive'] ?? false),
                '',
                'Vendedores'
            );
            $items[] = $mkItem(
                'bar-chart-2',
                ' Relatórios vendas',
                ['controller' => 'Relatorios', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['relatoriosPainelActive'] ?? false),
                '',
                'Relatórios vendas'
            );
        }

        $osChildren = [];
        if ($roleNav === 0 && ($sg['ordensservico_nova'] ?? true)) {
            $osChildren[] = $mkItem(
                'file-plus',
                ' Nova ordem',
                ['controller' => 'Ordensservico', 'action' => 'add'],
                ['target' => '_blank', 'rel' => 'noopener noreferrer'],
                (bool)($ctx['osAddActive'] ?? false),
                '',
                'Nova ordem'
            );
        }
        if ($sg['ordensservico_list'] ?? true) {
            $badgeOs = !empty($ctx['osIndexActive']) ? '<span class="pgm-os-badge badge badge-warning hide-menu" id="badge-exec-os">—</span>' : '';
            $osChildren[] = $mkItem(
                'clipboard-list',
                ' Ordens de Serviço',
                PortalUi::listRoute('ordens') ?? ['controller' => 'Ordensservico', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['osIndexActive'] ?? false),
                $badgeOs,
                'Ordens de Serviço'
            );
            $osChildren[] = $mkItem(
                'columns-3',
                ' OS · Kanban',
                ['controller' => 'OrdensservicoPrototype', 'action' => 'view', 'kanban'],
                ['data-turbo' => 'false'],
                self::ctrlActive($ctx, 'OrdensservicoPrototype', ['view'], 'kanban'),
                '',
                'OS Kanban'
            );
            $osChildren[] = $mkItem(
                'bar-chart-2',
                ' Relatórios OS',
                ['controller' => 'Ordensservico', 'action' => 'relatorios'],
                [],
                (bool)($ctx['relatoriosOsActive'] ?? false),
                '',
                'Relatórios OS'
            );
        }
        if ($admin && ($sg['queues'] ?? true)) {
            $osChildren[] = $mkItem(
                'layers',
                ' Filas / técnicos',
                ['controller' => 'Queues', 'action' => 'adminIndex'],
                [],
                false,
                '<span class="badge badge-warning hide-menu">7</span>',
                'Filas / técnicos'
            );
        }
        $osChildren = array_values(array_filter($osChildren));
        if ($osChildren !== []) {
            $items[] = $mkGroup('Ordens de Serviço', 'operacoes-os', $osChildren, (bool)($ctx['pgmSbOpenOrdens'] ?? false));
        }

        $sdItems = self::serviceDeskItems($ctx, $sg, $roleNav, $admin, $userId, $ctrl, $mkItem, $mkGroup);
        $items = array_merge($items, $sdItems);

        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'operacoes',
            'title' => 'Operações',
            'defaultOpen' => (bool)($ctx['pgmSbOpenOperacoes'] ?? $ctx['pgmSbOpenComercial'] ?? $ctx['pgmSbOpenIncidentes'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private static function serviceDeskItems(
        array $ctx,
        array $sg,
        int $roleNav,
        bool $admin,
        int $userId,
        string $ctrl,
        callable $mkItem,
        callable $mkGroup
    ): array {
        if (!($sg['tickets_servicedesk'] ?? true) && !($sg['tickets_historico'] ?? true)) {
            return [];
        }

        $adminFlag = $admin ? 1 : 0;
        $gate = static function (string $key) use ($adminFlag, $roleNav, $userId): bool {
            return ErpPrototypeAccess::sidebarKeyVisible($adminFlag, 0, $userId, $key);
        };

        $out = [];
        if ($sg['tickets_servicedesk'] ?? true) {
            $out[] = $mkItem(
                'headphones',
                ' Service Desk',
                PortalUi::servicedeskHomeRoute(),
                ['data-turbo' => 'false'],
                (bool)($ctx['ticketsServicedeskHomeActive'] ?? false),
                '',
                'Service Desk'
            );
        }

        $sdChildren = [];
        $sdDefs = [
            ['sd-dashboard', 'layout-dashboard', ' Dashboard', ['controller' => 'ServicedeskPrototype', 'action' => 'index']],
            ['sd-fila', 'list', ' Fila técnica', ['controller' => 'ServicedeskPrototype', 'action' => 'fila']],
            ['sd-meus', 'inbox', ' Meus tickets', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'meus']],
            ['sd-grupo', 'users', ' Meu grupo', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'grupo']],
            ['sd-kanban', 'columns-3', ' Kanban', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kanban']],
            ['sd-aprovacoes', 'check-circle', ' Aprovações', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'aprovacoes']],
            ['sd-cmdb', 'server', ' CMDB Ativos', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'cmdb']],
            ['sd-problemas', 'alert-triangle', ' Problemas', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'problemas']],
            ['sd-mudancas', 'shuffle', ' Mudanças', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'mudancas']],
            ['sd-contratos', 'file-signature', ' Contratos SLA', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'contratos']],
            ['sd-fat', 'receipt', ' Faturamento', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'fat']],
            ['sd-kb', 'book-open', ' Base conhecimento', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'kb']],
            ['sd-portal', 'globe', ' Portal cliente', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'portal']],
            ['sd-calendar', 'calendar', ' Plantões', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'calendar']],
            ['sd-csat', 'smile', ' CSAT & NPS', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'csat']],
            ['sd-relatorios', 'bar-chart-3', ' Relatórios', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'relatorios']],
            ['sd-config', 'sliders', ' SLA & Config', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'config']],
            ['sd-perm', 'shield', ' Permissões', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'perm']],
            ['sd-integracoes', 'plug', ' Integrações', ['controller' => 'ServicedeskPrototype', 'action' => 'view', 'integracoes']],
        ];
        foreach ($sdDefs as $def) {
            if (!$gate($def[0])) {
                continue;
            }
            $route = $def[3];
            $active = self::sdItemActive($ctx, $ctrl, $def[0], $route);
            $sdChildren[] = $mkItem($def[1], $def[2], $route, ['data-turbo' => 'false'], $active, '', trim($def[2]));
        }
        $sdChildren = array_values(array_filter($sdChildren));
        if ($sdChildren !== []) {
            $out[] = $mkGroup('Service Desk · módulos', 'operacoes-sd-modulos', $sdChildren, (bool)($ctx['pgmSbOpenSdModulos'] ?? $ctx['pgmSbOpenIncidentes'] ?? false));
        }

        if ($roleNav === 0 && ($sg['tickets_servicedesk'] ?? true)) {
            $out[] = $mkItem('gauge', ' Dashboard operacional', ['controller' => 'Servicedesk', 'action' => 'operacional'], ['data-turbo' => 'false'], (bool)($ctx['ticketsOperacionalActive'] ?? false), '', 'Dashboard operacional');
            $out[] = $mkItem('bar-chart-3', ' Relatório SLA', '/servicedesk/sla-relatorio', ['data-turbo' => 'false'], (bool)($ctx['ticketsSlaRelatorioActive'] ?? false), '', 'Relatório SLA');
        }

        $configChildren = [];
        if ($roleNav === 0 && (($sg['tickets_servicedesk'] ?? true) || ($sg['tickets_historico'] ?? true))) {
            $configChildren[] = $mkItem('git-branch', ' Workflow & SLA', '/servicedesk/workflow-sla-admin', ['data-turbo' => 'false'], (bool)($ctx['ticketsWorkflowSlaActive'] ?? false), '', 'Workflow e SLA');
        }
        if ($roleNav === 0 && ($sg['tickets_historico'] ?? true)) {
            $configChildren[] = $mkItem('history', ' Histórico', ['controller' => 'Tickets', 'action' => 'historico'], ['data-turbo' => 'false'], (bool)($ctx['ticketsHistoricoActive'] ?? false), '', 'Histórico');
        }
        $configChildren = array_values(array_filter($configChildren));
        if ($configChildren !== []) {
            $out[] = $mkGroup('Configurações SD', 'operacoes-sd-config', $configChildren, (bool)($ctx['ticketsIncidentesConfigOpen'] ?? false));
        }

        return array_values(array_filter($out));
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function sectionPcp(array $ctx, array $sg, bool $admin, int $userId, string $ctrl, callable $mkItem): ?array
    {
        if (!PortalUi::isPremiumModule('pcp') && PortalUi::mode() !== 'premium') {
            return null;
        }
        $adminFlag = $admin ? 1 : 0;
        $items = [];
        $defs = [
            ['pcp', 'factory', ' PCP · Visão geral', ['controller' => 'PcpPrototype', 'action' => 'lista']],
            ['pcp-dashboard', 'layout-dashboard', ' Dashboard', ['controller' => 'PcpPrototype', 'action' => 'view', 'dashboard']],
            ['engenharia', 'blueprint', ' Engenharia', ['controller' => 'PcpPrototype', 'action' => 'view', 'engenharia']],
            ['bom', 'git-branch', ' Estrutura BOM', ['controller' => 'PcpPrototype', 'action' => 'view', 'bom']],
            ['op-lista', 'list-ordered', ' Ordens de Produção', ['controller' => 'PcpPrototype', 'action' => 'view', 'op-lista']],
        ];
        foreach ($defs as $def) {
            if (!ErpPrototypeAccess::sidebarKeyVisible($adminFlag, 0, $userId, $def[0])) {
                continue;
            }
            $items[] = $mkItem($def[1], $def[2], $def[3], ['data-turbo' => 'false'], $ctrl === 'PcpPrototype', '', trim($def[2]));
        }
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'pcp',
            'title' => 'Indústria · PCP',
            'defaultOpen' => (bool)($ctx['pgmSbOpenPcp'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function sectionFinanceiro(array $ctx, array $sg, callable $mkItem): ?array
    {
        if (!($sg['financeiro'] ?? true) && !($sg['faturamento'] ?? true)) {
            return null;
        }
        $items = [];
        if ($sg['financeiro'] ?? true) {
            $items[] = $mkItem(
                'pie-chart',
                ' Financeiro',
                PortalUi::listRoute('financeiro') ?? ['controller' => 'Financeiro', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['finDashAct'] ?? false),
                '',
                'Financeiro'
            );
            $items[] = $mkItem('arrow-down-circle', ' Contas a receber', ['controller' => 'FinanceiroPrototype', 'action' => 'titulos'], ['data-turbo' => 'false'], (bool)($ctx['finRecAct'] ?? false), '', 'Contas a receber');
            $items[] = $mkItem('arrow-up-circle', ' Contas a pagar', ['controller' => 'FinanceiroPrototype', 'action' => 'contasPagar'], ['data-turbo' => 'false'], (bool)($ctx['finPagAct'] ?? false), '', 'Contas a pagar');
            $items[] = $mkItem('file-check', ' Faturamento', ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'orc-faturamento'], ['data-turbo' => 'false'], false, '', 'Faturamento');
            $items[] = $mkItem('credit-card', ' Cobrança', ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'cobranca'], ['data-turbo' => 'false'], false, '', 'Cobrança');
            $items[] = $mkItem('file-text', ' NF-e / NFS-e', ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'nfe'], ['data-turbo' => 'false'], false, '', 'NF-e');
            $items[] = $mkItem('line-chart', ' DRE', ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'dre'], ['data-turbo' => 'false'], (bool)($ctx['finDreAct'] ?? false), '', 'DRE');
            $items[] = $mkItem('activity', ' Fluxo de caixa', ['controller' => 'Financeiro', 'action' => 'fluxoCaixa'], ['data-turbo' => 'false'], (bool)($ctx['finFluxoAct'] ?? false), '', 'Fluxo de caixa');
            $items[] = $mkItem('repeat', ' Recorrentes', ['controller' => 'Financeiro', 'action' => 'recorrentes'], ['data-turbo' => 'false'], (bool)($ctx['finRecorAct'] ?? false), '', 'Recorrentes');
            $items[] = $mkItem('shuffle', ' Conciliação', ['controller' => 'Financeiro', 'action' => 'conciliacao'], ['data-turbo' => 'false'], (bool)($ctx['finConcAct'] ?? false), '', 'Conciliação');
            $items[] = $mkItem('bar-chart-2', ' Relatórios financ.', ['controller' => 'FinanceiroPrototype', 'action' => 'view', 'relatorios-fin'], ['data-turbo' => 'false'], (bool)($ctx['finRelAct'] ?? false), '', 'Relatórios financ.');
            $items[] = $mkItem('book-open', ' Plano de contas', ['controller' => 'FinanceiroConfig', 'action' => 'planoContas'], ['data-turbo' => 'false'], (bool)($ctx['finPlanoAct'] ?? false), '', 'Plano de contas');
            $items[] = $mkItem('folder-tree', ' Centros de custo', ['controller' => 'FinanceiroConfig', 'action' => 'centrosCusto'], ['data-turbo' => 'false'], (bool)($ctx['finCcAct'] ?? false), '', 'Centros de custo');
        }
        if ($sg['prefaturamento_fila'] ?? true) {
            $items[] = $mkItem('clipboard-check', ' Pré-faturamento', ['controller' => 'Prefaturamento', 'action' => 'index'], [], false, '', 'Pré-faturamento');
        }
        if ($sg['faturamento'] ?? true) {
            $items[] = $mkItem('file-check', ' Faturamento (legado)', ['controller' => 'Faturamento', 'action' => 'index'], [], false, '', 'Faturamento legado');
        }
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'financeiro',
            'title' => 'Financeiro',
            'defaultOpen' => (bool)($ctx['pgmSbOpenFinanceiro'] ?? $ctx['pgmSbOpenFaturamento'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function sectionBancos(array $ctx, array $sg, callable $mkItem): ?array
    {
        if (!($sg['financeiro'] ?? true)) {
            return null;
        }
        $items = [
            $mkItem(
                'landmark',
                ' Bancos',
                PortalUi::listRoute('bancos') ?? ['controller' => 'FinanceiroBancos', 'action' => 'index'],
                ['data-turbo' => 'false'],
                (bool)($ctx['finBancosAct'] ?? false),
                '',
                'Bancos'
            ),
            $mkItem('wallet', ' Contas bancárias', ['controller' => 'BancosPrototype', 'action' => 'view', 'contas'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'BancosPrototype', ['view'], 'contas'), '', 'Contas'),
            $mkItem('scroll-text', ' Extrato', ['controller' => 'BancosPrototype', 'action' => 'view', 'extrato'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'BancosPrototype', ['view'], 'extrato'), '', 'Extrato'),
            $mkItem('git-compare', ' Conciliação', ['controller' => 'BancosPrototype', 'action' => 'view', 'conciliacao'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'BancosPrototype', ['view'], 'conciliacao'), '', 'Conciliação bancária'),
            $mkItem('arrow-left-right', ' Transferências / PIX', ['controller' => 'BancosPrototype', 'action' => 'view', 'transferencias'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'BancosPrototype', ['view'], 'transferencias'), '', 'Transferências'),
            $mkItem('trending-up', ' Fluxo de caixa (bancos)', ['controller' => 'BancosPrototype', 'action' => 'view', 'fluxo-caixa'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'BancosPrototype', ['view'], 'fluxo-caixa'), '', 'Fluxo bancos'),
            $mkItem('send', ' Remessa', ['controller' => 'BancosPrototype', 'action' => 'view', 'remessa'], ['data-turbo' => 'false'], (bool)($ctx['finRemessaAct'] ?? false), '', 'Remessa'),
            $mkItem('inbox', ' Retorno', ['controller' => 'BancosPrototype', 'action' => 'view', 'retorno'], ['data-turbo' => 'false'], (bool)($ctx['finRetornoAct'] ?? false), '', 'Retorno'),
            $mkItem('table-2', ' Relatórios bancos', ['controller' => 'FinanceiroBancos', 'action' => 'relatorios'], ['data-turbo' => 'false'], (bool)($ctx['finRelBancosAct'] ?? false), '', 'Relatórios bancos'),
        ];
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'bancos',
            'title' => 'Bancos',
            'defaultOpen' => (bool)($ctx['pgmSbOpenBancos'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * Licenciamento (pg-lic-* do mock pgm_erp_completo.html).
     *
     * @return array<string,mixed>|null
     */
    private static function sectionLicenciamento(array $ctx, bool $admin, int $userId, string $ctrl, callable $mkItem): ?array
    {
        $adminFlag = $admin ? 1 : 0;
        $gate = static function (string $key) use ($adminFlag, $userId): bool {
            try {
                return ErpPrototypeAccess::sidebarKeyVisible($adminFlag, 0, $userId, $key);
            } catch (\Throwable $e) {
                return false;
            }
        };

        $items = [];
        $defs = [
            ['lic-dashboard', 'key-round', ' Painel · Licenças', ['controller' => 'LicencasPrototype', 'action' => 'dashboard']],
            ['lic-empresas', 'building-2', ' Empresas-cliente', ['controller' => 'LicencasPrototype', 'action' => 'view', 'empresas']],
            ['lic-licencas', 'package', ' Licenças', ['controller' => 'LicencasPrototype', 'action' => 'licencas']],
            ['lic-renovacoes', 'refresh-cw', ' Renovações', ['controller' => 'LicencasPrototype', 'action' => 'view', 'renovacoes']],
            ['lic-calendario', 'calendar', ' Calendário', ['controller' => 'LicencasPrototype', 'action' => 'view', 'calendario']],
            ['lic-cofre', 'lock', ' Cofre Credenciais', ['controller' => 'LicencasPrototype', 'action' => 'view', 'cofre']],
            ['lic-dispositivos', 'monitor', ' Dispositivos', ['controller' => 'LicencasPrototype', 'action' => 'view', 'dispositivos']],
            ['lic-catalogo', 'book-open', ' Catálogo & Fornecedores', ['controller' => 'LicencasPrototype', 'action' => 'view', 'catalogo']],
            ['lic-inteligencia', 'brain', ' Inteligência', ['controller' => 'LicencasPrototype', 'action' => 'view', 'inteligencia']],
            ['lic-auditoria', 'file-search', ' Auditoria', ['controller' => 'LicencasPrototype', 'action' => 'view', 'auditoria']],
        ];
        foreach ($defs as $def) {
            if (!$gate($def[0])) {
                continue;
            }
            $route = $def[3];
            $badge = '';
            if ($def[0] === 'lic-renovacoes' && !empty($ctx['licRenovacoesBadge'])) {
                $badge = '<span class="badge badge-warning hide-menu">' . (int)$ctx['licRenovacoesBadge'] . '</span>';
            }
            $items[] = $mkItem(
                $def[1],
                $def[2],
                $route,
                ['data-turbo' => 'false'],
                self::licItemActive($ctx, $ctrl, $def[0], $route),
                $badge,
                trim($def[2])
            );
        }
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'licenciamento',
            'title' => 'Licenciamento',
            'titleBadgeHtml' => '<span class="badge badge-success hide-menu" style="font-size:9px;font-weight:600;margin-left:6px;">NOVO</span>',
            'defaultOpen' => (bool)($ctx['pgmSbOpenLicenciamento'] ?? false),
            'items' => $items,
        ];
    }

    /**
     * @param array<string,mixed> $route
     */
    private static function licItemActive(array $ctx, string $ctrl, string $key, array $route): bool
    {
        $erpNav = (string)($ctx['erpNavActive'] ?? '');
        if ($erpNav !== '' && $erpNav === $key) {
            return true;
        }
        if ($ctrl !== 'LicencasPrototype') {
            return false;
        }
        $act = (string)($ctx['act'] ?? '');
        $targetAct = (string)($route['action'] ?? '');
        if ($targetAct === 'view' && isset($route[0])) {
            return $act === 'view' && (string)($ctx['erpViewPage'] ?? '') === (string)$route[0];
        }

        return $act === $targetAct;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function sectionSistema(array $ctx, array $sg, bool $admin, int $userId, string $ctrl, callable $mkItem): ?array
    {
        if (!$admin) {
            return null;
        }
        $items = [
            $mkItem('building', ' Empresas', ['controller' => 'EmpresasPrototype', 'action' => 'lista'], ['data-turbo' => 'false'], $ctrl === 'EmpresasPrototype', '', 'Empresas'),
            $mkItem('settings', ' Configurações', ['controller' => 'SistemaPrototype', 'action' => 'config'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'SistemaPrototype', ['config'], null), '', 'Configurações'),
            $mkItem('users', ' Usuários ERP', ['controller' => 'SistemaPrototype', 'action' => 'usuarios'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'SistemaPrototype', ['usuarios'], null), '', 'Usuários'),
            $mkItem('shield', ' Controle de acesso', ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'SistemaPrototype', ['acessoCentral', 'acessoPapeis'], null), '', 'Acesso'),
            $mkItem('file-search', ' Auditoria LGPD', ['controller' => 'SistemaPrototype', 'action' => 'auditoria'], ['data-turbo' => 'false'], self::ctrlActive($ctx, 'SistemaPrototype', ['auditoria'], null), '', 'Auditoria'),
        ];
        $items = array_values(array_filter($items));
        if ($items === []) {
            return null;
        }

        return [
            'id' => 'sistema',
            'title' => 'Sistema',
            'defaultOpen' => (bool)($ctx['pgmSbOpenSistema'] ?? false),
            'items' => $items,
        ];
    }

    private static function ctrlActive(array $ctx, string $controller, array $actions, ?string $page): bool
    {
        $ctrl = (string)($ctx['ctrl'] ?? '');
        $act = (string)($ctx['act'] ?? '');
        if ($ctrl !== $controller) {
            return false;
        }
        if ($page !== null && $act === 'view') {
            return (string)($ctx['erpViewPage'] ?? '') === $page;
        }

        return in_array($act, $actions, true);
    }

    /**
     * @param array<string,mixed> $route
     */
    private static function sdItemActive(array $ctx, string $ctrl, string $key, array $route): bool
    {
        $erpNav = (string)($ctx['erpNavActive'] ?? '');
        if ($erpNav !== '' && $erpNav === $key) {
            return true;
        }
        $targetCtrl = (string)($route['controller'] ?? '');
        $targetAct = (string)($route['action'] ?? '');
        if ($ctrl !== $targetCtrl) {
            return false;
        }
        if ($targetAct === 'view' && isset($route[0])) {
            return (string)($ctx['act'] ?? '') === 'view' && (string)($ctx['erpViewPage'] ?? '') === (string)$route[0];
        }

        return (string)($ctx['act'] ?? '') === $targetAct;
    }
}
