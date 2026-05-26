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
    public static function listRoute(string $module): ?array {
        $module = strtolower(trim($module));
        $legacy = [
            'clientes' => ['controller' => 'Clientes', 'action' => 'index', 'prefix' => false],
            'orcamentos' => ['controller' => 'Orcamentos', 'action' => 'index', 'prefix' => false],
            'produtos' => ['controller' => 'Produtos', 'action' => 'index', 'prefix' => false],
            'servicedesk' => ['controller' => 'Servicedesk', 'action' => 'index', 'prefix' => false],
        ];
        $prototype = [
            'clientes' => ['controller' => 'ClientesPrototype', 'action' => 'lista', 'prefix' => false],
            'orcamentos' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista', 'prefix' => false],
            'produtos' => ['controller' => 'ProdutosPrototype', 'action' => 'lista', 'prefix' => false],
            'servicedesk' => ['controller' => 'ServicedeskPrototype', 'action' => 'index', 'prefix' => false],
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
     * Rota do detalhe/revisão de orçamento (legado view ou protótipo detalhe).
     */
    public static function orcamentosDetalheRoute(int $orcamentoId): ?array {
        if ($orcamentoId <= 0) {
            return null;
        }
        if (self::isPremiumModule('orcamentos')) {
            return ['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $orcamentoId, 'prefix' => false];
        }

        return ['controller' => 'Orcamentos', 'action' => 'view', $orcamentoId, 'prefix' => false];
    }

    /**
     * Wizard “novo orçamento” (protótipo pg-novo ou legado add).
     *
     * @return array<string, mixed>
     */
    public static function orcamentosNovoRoute(): array {
        if (self::isPremiumModule('orcamentos')) {
            return ['controller' => 'OrcamentosPrototype', 'action' => 'view', 'novo', 'prefix' => false];
        }

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
            return in_array($action, ['lista', 'estoque', 'view', 'exportcsv', 'precosave'], true);
        }

        return $controller === 'Produtos';
    }

    /**
     * Item principal Service Desk na sidebar (protótipo só com premium).
     */
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
