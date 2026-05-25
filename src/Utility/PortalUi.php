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
        ];
        $prototype = [
            'clientes' => ['controller' => 'ClientesPrototype', 'action' => 'lista', 'prefix' => false],
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
}
