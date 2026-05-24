<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;

/**
 * Switchover UI legado ↔ premium (mock pgm_erp_completo).
 *
 * Prioridade: colunas em empresas (portal_ui_*) → .env / config/portal_ui.php.
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

    /** @var array<int, array<string, mixed>|null> */
    private static $empresaRowCache = [];

    public static function mode(?int $idempresa = null): string {
        return self::resolveSettings(self::empresaRow($idempresa))['mode'];
    }

    public static function isPremiumModule(string $module, ?int $idempresa = null): bool {
        if (self::mode($idempresa) === 'legacy') {
            return false;
        }
        $module = strtolower(trim($module));
        if ($module === '') {
            return false;
        }
        $enabled = self::enabledPremiumModules($idempresa);
        if ($enabled === []) {
            return false;
        }

        return !empty($enabled[$module]);
    }

    /**
     * @return array<string, true> módulos com UI premium ativa
     */
    public static function enabledPremiumModules(?int $idempresa = null): array {
        $settings = self::resolveSettings(self::empresaRow($idempresa));
        $explicit = $settings['premium_modules'];
        if (self::mode($idempresa) === 'premium') {
            if ($explicit !== []) {
                return $explicit;
            }
            $defaults = Configure::read('PortalUi.default_premium_modules');
            if (is_array($defaults) && $defaults !== []) {
                return $defaults;
            }

            return array_fill_keys(self::PREMIUM_MODULE_KEYS, true);
        }
        if (self::mode($idempresa) === 'mixed') {
            return $explicit;
        }

        return [];
    }

    /**
     * Mescla overrides da empresa sobre config global (.env).
     *
     * @param array{portal_ui_mode?: string|null, portal_ui_premium_modules?: string|null}|null $empresaRow
     * @return array{mode: string, premium_modules: array<string, true>}
     */
    public static function resolveSettings(?array $empresaRow = null): array {
        $mode = self::normalizeMode((string)Configure::read('PortalUi.mode', 'mixed'));
        $premiumModules = self::modulesFromConfigure(
            Configure::read('PortalUi.premium_modules'),
        );

        if ($empresaRow !== null) {
            $empresaMode = $empresaRow['portal_ui_mode'] ?? null;
            if ($empresaMode !== null && $empresaMode !== '') {
                $mode = self::normalizeMode((string)$empresaMode);
            }
            if (array_key_exists('portal_ui_premium_modules', $empresaRow)
                && $empresaRow['portal_ui_premium_modules'] !== null
            ) {
                $premiumModules = self::parseModulesCsv(
                    (string)$empresaRow['portal_ui_premium_modules'],
                );
            }
        }

        return [
            'mode' => $mode,
            'premium_modules' => $premiumModules,
        ];
    }

    /**
     * @param array<string, true>|array<mixed>|null $fromConfigure
     * @return array<string, true>
     */
    public static function modulesFromConfigure($fromConfigure): array {
        if (!is_array($fromConfigure) || $fromConfigure === []) {
            return [];
        }
        $out = [];
        foreach ($fromConfigure as $key => $val) {
            if (is_int($key)) {
                $key = $val;
            }
            $m = strtolower(trim((string)$key));
            if ($m !== '' && $val) {
                $out[$m] = true;
            }
        }

        return $out;
    }

    /**
     * @return array<string, true>
     */
    public static function parseModulesCsv(string $raw): array {
        $modules = [];
        if (trim($raw) === '') {
            return $modules;
        }
        foreach (preg_split('/\s*,\s*/', $raw) as $m) {
            $m = strtolower(trim($m));
            if ($m !== '') {
                $modules[$m] = true;
            }
        }

        return $modules;
    }

    public static function normalizeMode(string $mode): string {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['legacy', 'premium', 'mixed'], true)) {
            return 'mixed';
        }

        return $mode;
    }

    /**
     * Redireciona action legada para rota *-prototype quando o módulo está em premium.
     *
     * @param array<string,mixed> $params parâmetros passados ao redirect Cake
     * @return array<string,mixed>|null null = continuar legado
     */
    public static function redirectToPrototypeIfEnabled(
        string $module,
        string $prototypeController,
        string $prototypeAction,
        array $params = [],
        ?int $idempresa = null
    ): ?array {
        if (!self::isPremiumModule($module, $idempresa)) {
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

        $idempresa = (int)($user['idempresa'] ?? 0);

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
        if (!self::isPremiumModule($module, $idempresa > 0 ? $idempresa : null)) {
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
            foreach ($target['pass'] as $paramName) {
                $val = $request->getParam($paramName);
                if ($val !== null && $val !== '') {
                    $route[$paramName] = $val;
                }
            }
        }

        return $route;
    }

    /**
     * @return array{portal_ui_mode: string|null, portal_ui_premium_modules: string|null}|null
     */
    private static function empresaRow(?int $idempresa): ?array {
        if ($idempresa === null || $idempresa <= 0) {
            return null;
        }
        if (!array_key_exists($idempresa, self::$empresaRowCache)) {
            self::$empresaRowCache[$idempresa] = self::loadEmpresaRowFromDb($idempresa);
        }

        return self::$empresaRowCache[$idempresa];
    }

    /**
     * @return array{portal_ui_mode: string|null, portal_ui_premium_modules: string|null}|null
     */
    private static function loadEmpresaRowFromDb(int $idempresa): ?array {
        try {
            $empresas = TableRegistry::getTableLocator()->get('Empresas');
            $schema = $empresas->getSchema();
            if (!$schema->hasColumn('portal_ui_mode')) {
                return null;
            }
            $empresa = $empresas->get($idempresa, [
                'fields' => [
                    'Empresas.id',
                    'Empresas.portal_ui_mode',
                    'Empresas.portal_ui_premium_modules',
                ],
            ]);

            return [
                'portal_ui_mode' => $empresa->get('portal_ui_mode'),
                'portal_ui_premium_modules' => $empresa->get('portal_ui_premium_modules'),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Limpa cache (testes). */
    public static function clearEmpresaCache(): void {
        self::$empresaRowCache = [];
    }
}
