<?php
/**
 * UI premium (mock pgm_erp_completo) — switchover por módulo.
 *
 * Enquanto um módulo não estiver em premium, as rotas legadas
 * (/clientes, /orcamentos, …) permanecem. Rotas *-prototype convivem.
 *
 * .env (opcional):
 *   PORTAL_UI_MODE=legacy|premium|mixed   (default mixed)
 *   PORTAL_PREMIUM_MODULES=clientes,orcamentos,servicedesk
 *
 * premium + PORTAL_PREMIUM_MODULES vazio → todos os módulos em default_premium_modules.
 * mixed + PORTAL_PREMIUM_MODULES vazio → nenhum redirect (só URLs *-prototype).
 *
 * Por empresa (sobrescreve .env quando preenchido): empresas.portal_ui_mode,
 * empresas.portal_ui_premium_modules — edição em Empresas → aba Interface ERP.
 */

$mode = strtolower(trim((string)env('PORTAL_UI_MODE', 'mixed')));
if (!in_array($mode, ['legacy', 'premium', 'mixed'], true)) {
    $mode = 'mixed';
}

$rawModules = trim((string)env('PORTAL_PREMIUM_MODULES', ''));
$modules = [];
if ($rawModules !== '') {
    foreach (preg_split('/\s*,\s*/', $rawModules) as $m) {
        $m = strtolower(trim($m));
        if ($m !== '') {
            $modules[$m] = true;
        }
    }
}

$defaultPremiumModules = [
    'clientes' => true,
    'produtos' => true,
    'orcamentos' => true,
    'ordensservico' => true,
    'servicedesk' => true,
    'financeiro' => true,
    'bancos' => true,
    'fornecedores' => true,
    'empresas' => true,
    'sistema' => true,
];

/**
 * GET legado (equipe) → controller *Prototype quando o módulo está premium.
 * Config/index não entra (SistemaPrototype::config redireciona de volta ao legado).
 */
$legacyActions = [
    'clientes' => [
        'module' => 'clientes',
        'actions' => [
            'index' => ['controller' => 'ClientesPrototype', 'action' => 'lista'],
        ],
    ],
    'produtos' => [
        'module' => 'produtos',
        'actions' => [
            'index' => ['controller' => 'ProdutosPrototype', 'action' => 'lista'],
            'estoque' => ['controller' => 'ProdutosPrototype', 'action' => 'estoque'],
        ],
    ],
    'orcamentos' => [
        'module' => 'orcamentos',
        'actions' => [
            'index' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista'],
        ],
    ],
    'ordensservico' => [
        'module' => 'ordensservico',
        'actions' => [
            'index' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista'],
        ],
    ],
    'servicedesk' => [
        'module' => 'servicedesk',
        'actions' => [
            'index' => ['controller' => 'ServicedeskPrototype', 'action' => 'index'],
            'operacional' => ['controller' => 'ServicedeskPrototype', 'action' => 'index'],
        ],
    ],
    'financeiro' => [
        'module' => 'financeiro',
        'actions' => [
            'index' => ['controller' => 'FinanceiroPrototype', 'action' => 'lista'],
        ],
    ],
    'financeirobancos' => [
        'module' => 'bancos',
        'actions' => [
            'index' => ['controller' => 'BancosPrototype', 'action' => 'lista'],
        ],
    ],
    'empresas' => [
        'module' => 'empresas',
        'actions' => [
            'index' => ['controller' => 'EmpresasPrototype', 'action' => 'lista'],
        ],
    ],
    'users' => [
        'module' => 'sistema',
        'actions' => [
            'index' => ['controller' => 'SistemaPrototype', 'action' => 'usuarios'],
        ],
    ],
    'permissoes' => [
        'module' => 'sistema',
        'actions' => [
            'adminroles' => ['controller' => 'SistemaPrototype', 'action' => 'acessoPapeis'],
        ],
    ],
];

return [
    'PortalUi' => [
        'mode' => $mode,
        'premium_modules' => $modules,
        'default_premium_modules' => $defaultPremiumModules,
        'legacy_actions' => $legacyActions,
        'reference_html' => [
            'primary' => 'docs/referencias/pgm_erp_completo_2.html',
            'fallback' => 'docs/reference/pgm_erp_completo_2.html',
        ],
    ],
];
