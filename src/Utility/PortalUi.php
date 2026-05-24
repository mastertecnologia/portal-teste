<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;

/**
 * Switchover UI legado ↔ premium (mock pgm_erp_completo).
 */
class PortalUi {

    /** Módulos com shell *-prototype pronto (Fases 0–5; PCP fora). */
    public const PREMIUM_MODULE_KEYS = [
        'clientes',
        'produtos',
        'orcamentos',
        'ordensservico',
        'servicedesk',
        'financeiro',
        'bancos',
        'fornecedores',
        'empresas',
        'sistema',
    ];

    public static function mode(): string {
        $mode = strtolower(trim((string)Configure::read('PortalUi.mode', 'mixed')));
        if (!in_array($mode, ['legacy', 'premium', 'mixed'], true)) {
            return 'mixed';
        }

        return $mode;
    }

    public static function isPremiumModule(string $module): bool {
        if (self::mode() === 'legacy') {
            return false;
        }
        $module = strtolower(trim($module));
        if ($module === '') {
            return false;
        }
        $enabled = self::enabledPremiumModules();
        if ($enabled === []) {
            return false;
        }

        return !empty($enabled[$module]);
    }

    /**
     * @return array<string, true> módulos com UI premium ativa
     */
    public static function enabledPremiumModules(): array {
        $explicit = Configure::read('PortalUi.premium_modules');
        if (!is_array($explicit)) {
            $explicit = [];
        }
        if (self::mode() === 'premium') {
            if ($explicit !== []) {
                return $explicit;
            }
            $defaults = Configure::read('PortalUi.default_premium_modules');
            if (is_array($defaults) && $defaults !== []) {
                return $defaults;
            }

            return array_fill_keys(self::PREMIUM_MODULE_KEYS, true);
        }
        if (self::mode() === 'mixed') {
            return $explicit;
        }

        return [];
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
     * GET legado da equipe (role=0) → *-prototype quando o módulo está premium.
     *
     * @param array<string,mixed>|null $user Auth user
     * @return array<string,mixed>|null rota Cake para redirect
     */
    public static function legacyRedirectRoute(ServerRequest $request, ?array $user): ?array {
        if ($user === null || (int)($user['id'] ?? 0) <= 0) {
            return null;
        }
        if ((int)($user['role'] ?? -1) !== 0) {
            return null;
        }
        if (!$request->is('get')) {
            return null;
        }
        if ((string)$request->getQuery('legacy') === '1') {
            return null;
        }

        $path = (string)$request->getPath();
        if (
            strpos($path, '-prototype') !== false
            || strpos($path, '/prototype-history') === 0
            || strpos($path, '/v2/') !== false
            || strpos($path, '/portal/v2/') !== false
        ) {
            return null;
        }

        $controller = strtolower((string)$request->getParam('controller'));
        $action = strtolower((string)$request->getParam('action'));
        if ($controller === '' || $action === '') {
            return null;
        }
        if (substr($controller, -9) === 'prototype' || $controller === 'prototypehistory') {
            return null;
        }

        $map = Configure::read('PortalUi.legacy_actions');
        if (!is_array($map)) {
            return null;
        }
        $entry = $map[$controller] ?? null;
        if (!is_array($entry)) {
            return null;
        }
        $actions = $entry['actions'] ?? null;
        if (!is_array($actions)) {
            return null;
        }
        $target = $actions[$action] ?? null;
        if (!is_array($target) || empty($target['controller']) || empty($target['action'])) {
            return null;
        }

        $module = (string)($entry['module'] ?? $controller);
        if (!self::isPremiumModule($module)) {
            return null;
        }

        $route = [
            'controller' => (string)$target['controller'],
            'action' => (string)$target['action'],
            'prefix' => false,
        ];
        if (!empty($target['?']) && is_array($target['?'])) {
            $route['?'] = $target['?'];
        }
        if (!empty($target['pass']) && is_array($target['pass'])) {
            foreach ($target['pass'] as $i => $paramName) {
                $val = $request->getParam($paramName);
                if ($val !== null && $val !== '') {
                    $route[$paramName] = $val;
                }
            }
        }

        return $route;
    }
}
