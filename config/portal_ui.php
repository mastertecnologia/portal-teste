<?php
/**
 * UI premium (mock pgm_erp_completo) — switchover por módulo.
 *
 * Enquanto um módulo não estiver em premium_modules, as rotas legadas
 * (/clientes, /orcamentos, …) permanecem. Rotas *-prototype convivem.
 * Com módulos em PORTAL_PREMIUM_MODULES (ex.: clientes, orcamentos, produtos):
 *   clientes — /clientes → lista protótipo; Visão 360° → /clientes-prototype/visao360/:id
 *   orcamentos — /orcamentos → lista protótipo (equipe); view → detalhe protótipo
 *   produtos — /produtos → lista protótipo; /produtos/estoque → estoque protótipo
 *   servicedesk — /servicedesk (equipe) → dashboard protótipo; edit/view ticket → ticket protótipo
 *   orcamentos add/edit (equipe) — add → wizard novo; edit → detalhe protótipo
 *
 * .env (opcional):
 *   PORTAL_UI_MODE=legacy|premium|mixed   (default mixed)
 *   PORTAL_PREMIUM_MODULES=clientes,orcamentos,produtos,servicedesk,ordens,financeiro,bancos,fornecedores,home
 *   PORTAL_ERP_PREMIUM_NAV=1   (menu “Interface ERP” quando há módulos premium ou mode=premium)
 *
 * Ativação no servidor: bash scripts/enable-portal-premium-ui.sh [/caminho/.env]
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
            'primary' => 'docs/reference/pgm_erp_completo.html',
            'fallback' => 'docs/referencias/pgm_erp_completo.html',
        ],
    ],
];
