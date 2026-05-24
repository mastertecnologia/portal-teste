#!/usr/bin/env php
<?php
/**
 * Inventaria telas pg-* no HTML de referência e compara com mapeamento/rotas do portal.
 *
 * Uso:
 *   php bin/audit_pgm_erp_mock.php
 *   php bin/audit_pgm_erp_mock.php /caminho/pgm_erp_completo_2.html
 *   php bin/audit_pgm_erp_mock.php --md
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$writeMd = in_array('--md', $argv ?? [], true);
$pathArg = null;
foreach (array_slice($argv ?? [], 1) as $arg) {
    if ($arg !== '--md' && strpos($arg, '-') !== 0) {
        $pathArg = $arg;
        break;
    }
}

$candidates = [
    $pathArg,
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

$screenConfig = [];
$switchover = [];
$configFile = $root . '/config/portal_ui_screens.php';
if (is_file($configFile)) {
    $loaded = require $configFile;
    if (is_array($loaded['PortalUiScreens']['screens'] ?? null)) {
        $screenConfig = $loaded['PortalUiScreens']['screens'];
    }
    if (is_array($loaded['PortalUiScreens']['switchover'] ?? null)) {
        $switchover = $loaded['PortalUiScreens']['switchover'];
    }
}

$mappedInRef = 0;
$withProto = 0;
$unmapped = [];
foreach ($mockScreens as $id) {
    if (isset($screenConfig[$id])) {
        $mappedInRef++;
        if (!empty($screenConfig[$id]['prototype'])) {
            $withProto++;
        }
    } else {
        $unmapped[] = $id;
    }
}

$lines = [];
$lines[] = '=== Auditoria mock PGM ERP ===';
$lines[] = "Referência: {$htmlPath}";
$lines[] = 'Telas pg-* no HTML: ' . count($mockScreens);
$lines[] = 'Mapeadas em config/portal_ui_screens.php: ' . count($screenConfig);
$lines[] = 'Cobertura (ref ∩ config): ' . $mappedInRef . ' · com protótipo: ' . $withProto;
$lines[] = 'Sem entrada no config: ' . count($unmapped);
$lines[] = 'Módulos *-prototype: ' . implode(', ', $prototypeModules);
$lines[] = '';
$lines[] = '--- Switchover (PORTAL_PREMIUM_MODULES) ---';
foreach ($switchover as $key => $row) {
    $proto = $row['prototype'] ?? [];
    $lines[] = sprintf(
        '  %-18s → %s::%s',
        $key,
        $proto['controller'] ?? '?',
        $proto['action'] ?? '?'
    );
}
$lines[] = '';
$lines[] = '--- Telas no config ---';
foreach ($screenConfig as $pgId => $row) {
    $leg = $row['legacy'] ?? [];
    $pro = $row['prototype'] ?? null;
    $legStr = isset($leg['controller']) ? $leg['controller'] . '::' . ($leg['action'] ?? '') : '—';
    $proStr = is_array($pro) && isset($pro['controller'])
        ? $pro['controller'] . '::' . ($pro['action'] ?? '')
        : '—';
    $lines[] = sprintf('  %-22s %-10s legado=%-24s proto=%s', $pgId, $row['parity'] ?? '?', $legStr, $proStr);
}
if ($unmapped !== []) {
    $lines[] = '';
    $lines[] = '--- Sem entrada no config (' . count($unmapped) . ') ---';
    foreach (array_slice($unmapped, 0, 30) as $id) {
        $lines[] = '  ' . $id;
    }
}
$lines[] = '';
$lines[] = 'Ativar: PORTAL_PREMIUM_MODULES=clientes,produtos,... · Legado: ?legacy_ui=1';

if ($writeMd) {
    $md = ["# Mapeamento telas pg-* ↔ Portal Cake\n\nGerado por audit --md.\n"];
    $md[] = '| pg-* | Título | Paridade | Legado | Protótipo |';
    $md[] = '|------|--------|----------|--------|-----------|';
    foreach ($screenConfig as $pgId => $row) {
        $leg = $row['legacy'] ?? [];
        $pro = $row['prototype'] ?? null;
        $legStr = isset($leg['controller']) ? $leg['controller'] . '/' . ($leg['action'] ?? '') : '—';
        $proStr = is_array($pro) && isset($pro['controller']) ? $pro['controller'] . '/' . ($pro['action'] ?? '') : '—';
        $md[] = sprintf('| `%s` | %s | %s | %s | %s |', $pgId, $row['title'] ?? '', $row['parity'] ?? '', $legStr, $proStr);
    }
    $out = $root . '/docs/MAPEAMENTO_TELAS_PG.md';
    file_put_contents($out, implode("\n", $md) . "\n");
    fwrite(STDERR, "Escrito: {$out}\n");
}

echo implode("\n", $lines) . "\n";
exit(0);
