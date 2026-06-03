<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Switchover UI legado ↔ premium (mock pgm_erp_completo).
 */
class PortalUi {

    public static function mode(): string {
        return (string)Configure::read('PortalUi.mode', 'mixed');
    }

    public static function hasAnyPremiumModule(): bool {
        $map = Configure::read('PortalUi.premium_modules');
        if (!is_array($map)) {
            return false;
        }

        return $map !== [];
    }

    /**
     * Exibe seção “Interface ERP” na sidebar legado (atalhos *-prototype).
     */
    public static function showPremiumNav(): bool {
        if (self::mode() === 'premium') {
            return true;
        }
        if (self::hasAnyPremiumModule()) {
            return true;
        }

        return filter_var(env('PORTAL_ERP_PREMIUM_NAV', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function isPremiumModule(string $module): bool {
        $module = strtolower(trim($module));
        if ($module === '') {
            return false;
        }
        $map = Configure::read('PortalUi.premium_modules');
        if (!is_array($map) || $map === []) {
            return false;
        }

        return !empty($map[$module]);
    }

    /**
     * Redireciona action legada para rota *-prototype quando o módulo está em premium.
     *
     * @param string $module ex.: clientes, orcamentos, servicedesk
     * @param string $prototypeController ex.: ClientesPrototype
     * @param string $prototypeAction ex.: lista, view
     * @param array<string,mixed> $params parâmetros passados ao redirect Cake
     * @return array<string,mixed>|null null = continuar legado
     */
    public static function redirectToPrototypeIfEnabled(
        string $module,
        string $prototypeController,
        string $prototypeAction,
        array $params = []
    ): ?array {
        if (!self::isPremiumModule($module)) {
            return null;
        }
        $route = [
            'controller' => $prototypeController,
            'action' => $prototypeAction,
            'prefix' => false,
        ];

        return $params === [] ? $route : array_merge($route, $params);
    }

    /**
     * Rota Cake da listagem principal do módulo (legado ou *-prototype).
     *
     * @return array<string, mixed>|null null se o módulo não estiver mapeado
     */
    /**
     * Dashboard equipe: pg-home do mock ou dashboard legado.
     *
     * @return array<string, mixed>
     */
    public static function dashboardRoute(): array {
        if (self::mode() === 'premium' || self::isPremiumModule('home')) {
            return ['controller' => 'ErpHomePrototype', 'action' => 'index', 'prefix' => false];
        }

        return ['controller' => 'Users', 'action' => 'dashboard', 'prefix' => false];
    }

    public static function listRoute(string $module): ?array {
        $module = strtolower(trim($module));
        $legacy = [
            'clientes' => ['controller' => 'Clientes', 'action' => 'index', 'prefix' => false],
            'orcamentos' => ['controller' => 'Orcamentos', 'action' => 'index', 'prefix' => false],
            'produtos' => ['controller' => 'Produtos', 'action' => 'index', 'prefix' => false],
            'servicedesk' => ['controller' => 'Servicedesk', 'action' => 'index', 'prefix' => false],
            'ordens' => ['controller' => 'Ordensservico', 'action' => 'index', 'prefix' => false],
            'financeiro' => ['controller' => 'Financeiro', 'action' => 'index', 'prefix' => false],
            'bancos' => ['controller' => 'FinanceiroBancos', 'action' => 'index', 'prefix' => false],
            'fornecedores' => ['controller' => 'Clientes', 'action' => 'index', 'prefix' => false],
            'home' => ['controller' => 'Users', 'action' => 'dashboard', 'prefix' => false],
        ];
        $prototype = [
            'clientes' => ['controller' => 'ClientesPrototype', 'action' => 'lista', 'prefix' => false],
            'orcamentos' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista', 'prefix' => false],
            'produtos' => ['controller' => 'ProdutosPrototype', 'action' => 'lista', 'prefix' => false],
            'servicedesk' => ['controller' => 'ServicedeskPrototype', 'action' => 'index', 'prefix' => false],
            'ordens' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista', 'prefix' => false],
            'financeiro' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista', 'prefix' => false],
            'bancos' => ['controller' => 'BancosPrototype', 'action' => 'lista', 'prefix' => false],
            'fornecedores' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista', 'prefix' => false],
            'home' => ['controller' => 'ErpHomePrototype', 'action' => 'index', 'prefix' => false],
        ];
        if (!isset($legacy[$module])) {
            return null;
        }

        return self::isPremiumModule($module) ? $prototype[$module] : $legacy[$module];
    }

    /**
     * Item “Clientes” ativo na sidebar (legado ou protótipo premium).
     */
    public static function isClientesListNavActive(string $controller, string $action): bool {
        if ($controller === 'ClientesPrototype') {
            return in_array($action, ['lista', 'visao360'], true);
        }

        return $controller === 'Clientes' && $action !== 'add';
    }

    /**
     * Rota Cake da Visão 360° (legado ou protótipo).
     *
     * @param array<string, string> $query ex.: ['tab' => 'historico']
     * @return array<string, mixed>|null null se $clienteId inválido
     */
    public static function visao360Route(int $clienteId, array $query = []): ?array {
        if ($clienteId <= 0) {
            return null;
        }
        $route = self::isPremiumModule('clientes')
            ? ['controller' => 'ClientesPrototype', 'action' => 'visao360', $clienteId, 'prefix' => false]
            : ['controller' => 'Clientes', 'action' => 'visao360', $clienteId, 'prefix' => false];
        if ($query !== []) {
            $route['?'] = $query;
        }

        return $route;
    }

    /**
     * Rota do detalhe/revisão de orçamento (pg-revisao — Orcamentos::view com stepper).
     */
    public static function orcamentosDetalheRoute(int $orcamentoId): ?array {
        if ($orcamentoId <= 0) {
            return null;
        }

        return ['controller' => 'Orcamentos', 'action' => 'view', $orcamentoId, 'prefix' => false];
    }

    /**
     * Tela “novo orçamento” (pg-novo — Orcamentos::add com dados reais e carrinho).
     *
     * @return array<string, mixed>
     */
    public static function orcamentosNovoRoute(): array {
        return ['controller' => 'Orcamentos', 'action' => 'add', 'prefix' => false];
    }

    /**
     * Home do Service Desk para equipe (dashboard protótipo ou fila React legado).
     *
     * @return array<string, mixed>
     */
    public static function servicedeskHomeRoute(): array {
        if (self::isPremiumModule('servicedesk')) {
            return ['controller' => 'ServicedeskPrototype', 'action' => 'index', 'prefix' => false];
        }

        return ['controller' => 'Servicedesk', 'action' => 'index', 'prefix' => false];
    }

    /**
     * Detalhe de ticket (protótipo ou edição legada).
     */
    public static function servicedeskTicketRoute(int $ticketId): ?array {
        if ($ticketId <= 0) {
            return null;
        }
        if (self::isPremiumModule('servicedesk')) {
            return ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $ticketId, 'prefix' => false];
        }

        return [
            'controller' => 'Servicedesk',
            'action' => 'edit',
            $ticketId,
            'prefix' => false,
            '?' => ['sd' => '1'],
        ];
    }

    /**
     * Rota da tela de estoque de produtos (legado ou protótipo).
     *
     * @return array<string, mixed>
     */
    public static function produtosEstoqueRoute(): array {
        if (self::isPremiumModule('produtos')) {
            return ['controller' => 'ProdutosPrototype', 'action' => 'estoque', 'prefix' => false];
        }

        return ['controller' => 'Produtos', 'action' => 'estoque', 'prefix' => false];
    }

    /**
     * Item Orçamentos ativo na sidebar legado (inclui OrcamentosPrototype).
     */
    public static function isOrcamentosNavActive(string $controller, string $action): bool {
        if ($controller === 'OrcamentosPrototype') {
            return in_array($action, ['lista', 'detalhe', 'view', 'exportcsv'], true);
        }

        return $controller === 'Orcamentos';
    }

    /**
     * Item Produtos ativo na sidebar legado (inclui ProdutosPrototype).
     */
    public static function isProdutosNavActive(string $controller, string $action): bool {
        if ($controller === 'ProdutosPrototype') {
            return in_array($action, ['lista', 'estoque', 'view', 'exportcsv', 'precosave', 'reajustesave'], true);
        }

        return $controller === 'Produtos';
    }

    /**
     * Item principal Service Desk na sidebar (protótipo só com premium).
     */
    public static function isOrdensNavActive(string $controller, string $action): bool {
        if ($controller === 'OrdensservicoPrototype') {
            return in_array($action, ['lista', 'detalhe', 'view', 'exportcsv'], true);
        }

        return $controller === 'Ordensservico' && $action === 'index';
    }

    public static function isFinanceiroNavActive(string $controller, string $action): bool {
        if ($controller === 'FinanceiroPrototype') {
            return in_array($action, ['lista', 'titulos', 'contasPagar', 'view'], true);
        }

        return $controller === 'Financeiro' && $action === 'index';
    }

    public static function isBancosNavActive(string $controller, string $action): bool {
        if ($controller === 'BancosPrototype') {
            return in_array($action, ['lista', 'view'], true);
        }

        return $controller === 'FinanceiroBancos' && $action === 'index';
    }

    public static function isErpHomeNavActive(string $controller, string $action): bool {
        return $controller === 'ErpHomePrototype' && $action === 'index';
    }

    public static function isServicedeskHomeNavActive(string $controller, string $action): bool {
        if ($controller === 'ServicedeskPrototype') {
            return self::isPremiumModule('servicedesk')
                && in_array($action, ['index', 'fila', 'ticket', 'view', 'ci', 'csathistorico', 'csatexportcsv'], true);
        }
        if ($controller === 'Servicedesk' && $action === 'index') {
            return !self::isPremiumModule('servicedesk');
        }

        return false;
    }
}
