#!/usr/bin/env php
<?php
/**
 * Inventaria telas pg-* no HTML de referência e compara com rotas *-prototype do portal.
 *
 * Uso:
 *   php bin/audit_pgm_erp_mock.php
 *   php bin/audit_pgm_erp_mock.php /caminho/pgm_erp_completo_2.html
 *
 * Coloque o mock em docs/reference/pgm_erp_completo_2.html (cópia do Downloads).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$candidates = [
    $argv[1] ?? null,
    $root . '/docs/referencias/pgm_erp_completo_2.html',
    $root . '/docs/reference/pgm_erp_completo_2.html',
    $root . '/docs/reference/pgm_erp_completo.html',
];

$htmlPath = null;
foreach ($candidates as $c) {
    if ($c !== null && is_file($c) && is_readable($c)) {
        $htmlPath = $c;
        break;
    }
}

if ($htmlPath === null) {
    fwrite(STDERR, "Arquivo de referência não encontrado.\n");
    fwrite(STDERR, "Copie pgm_erp_completo_2.html para docs/referencias/ (ou docs/reference/) ou passe o caminho.\n");
    exit(2);
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    fwrite(STDERR, "Não foi possível ler: {$htmlPath}\n");
    exit(1);
}

preg_match_all('/\bid=["\']?(pg-[a-z0-9-]+)["\']?/i', $html, $m);
$mockScreens = array_values(array_unique($m[1] ?? []));
sort($mockScreens);

$routesFile = $root . '/config/routes.php';
$routesContent = is_file($routesFile) ? (string)file_get_contents($routesFile) : '';
preg_match_all('#/([a-z0-9-]+)-prototype#', $routesContent, $rm);
$prototypeModules = array_values(array_unique($rm[1] ?? []));
sort($prototypeModules);

$implemented = [
    'servicedesk' => 18,
    'orcamentos' => 2,
    'ordens' => 2,
    'clientes' => 4,
    'produtos' => 3,
    'fornecedores' => 1,
    'financeiro' => 4,
    'bancos' => 2,
    'empresas' => 2,
    'sistema' => 5,
    'pcp' => 1,
];

echo "=== Auditoria mock PGM ERP ===\n";
echo "Referência: {$htmlPath}\n";
echo "Telas pg-* no HTML: " . count($mockScreens) . "\n";
echo "Módulos com rota *-prototype: " . implode(', ', $prototypeModules) . "\n\n";

echo "--- Primeiras 30 telas do mock (amostra) ---\n";
foreach (array_slice($mockScreens, 0, 30) as $id) {
    echo "  {$id}\n";
}
if (count($mockScreens) > 30) {
    echo "  … +" . (count($mockScreens) - 30) . " telas\n";
}

echo "\n--- Módulos protótipo (rotas Cake) ---\n";
foreach ($prototypeModules as $mod) {
    $n = $implemented[$mod] ?? 0;
    echo sprintf("  %-16s rotas OK · ~%d telas entregues (parcial)\n", $mod, $n);
}

echo "\nPróximo passo: docs/MIGRACAO_PGM_ERP_COMPLETO.md (fases por módulo).\n";
echo "Switchover: PORTAL_PREMIUM_MODULES=clientes no .env após validar o módulo.\n";

exit(0);
