<?php
/**
 * UI premium (mock pgm_erp_completo) — switchover por módulo.
 *
 * Enquanto um módulo não estiver em premium_modules, as rotas legadas
 * (/clientes, /orcamentos, …) permanecem. Rotas *-prototype convivem.
 * Com `clientes` em PORTAL_PREMIUM_MODULES: /clientes → lista protótipo; Visão 360° → /clientes-prototype/visao360/:id.
 *
 * .env (opcional):
 *   PORTAL_UI_MODE=legacy|premium|mixed   (default mixed)
 *   PORTAL_PREMIUM_MODULES=clientes,orcamentos,servicedesk
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

return [
    'PortalUi' => [
        'mode' => $mode,
        'premium_modules' => $modules,
        'reference_html' => [
            'primary' => 'docs/referencias/pgm_erp_completo_2.html',
            'fallback' => 'docs/reference/pgm_erp_completo_2.html',
        ],
    ],
];
