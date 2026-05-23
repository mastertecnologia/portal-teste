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
}
