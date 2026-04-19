<?php
namespace App\Utility;

/**
 * Rotas JSON públicas da integração ERP (auth por token de empresa).
 * Uma única fonte para CORS, isAuthorized e Security::unlockedActions.
 */
final class ErpApiRoutes
{
    /** @var array<string, list<string>> controller em minúsculas => actions em minúsculas */
    public const CONTROLLER_ACTIONS_LOWER = [
        'ordensservico' => ['listapi', 'refreshapi'],
        'clientes' => ['addapi', 'listapi'],
        'produtos' => ['addapi', 'listapi'],
        'clicontratos' => ['addapi', 'listapi'],
    ];

    /**
     * Nomes reais dos métodos PHP nas actions de API (CakePHP 3).
     * Usar em Security::unlockedActions (global no AppController).
     *
     * @return list<string>
     */
    public static function securityUnlockedActionNames(): array
    {
        return ['addAPI', 'listAPI', 'refreshAPI'];
    }

    public static function matches(string $controllerLower, string $actionLower): bool
    {
        if (!isset(self::CONTROLLER_ACTIONS_LOWER[$controllerLower])) {
            return false;
        }

        return in_array($actionLower, self::CONTROLLER_ACTIONS_LOWER[$controllerLower], true);
    }
}
