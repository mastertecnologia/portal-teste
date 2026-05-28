#!/usr/bin/env php
<?php
/**
 * Inventaria telas pg-* no HTML de referência e compara com registry + rotas *-prototype.
 *
 * Uso:
 *   php bin/audit_pgm_erp_mock.php
 *   php bin/audit_pgm_erp_mock.php /caminho/pgm_erp_completo.html
 *
 * Documentação: python3 bin/generate_pgm_erp_coverage.py
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$candidates = [
    $argv[1] ?? null,
    $root . '/docs/referencias/pgm_erp_completo_2.html',
    $root . '/docs/reference/pgm_erp_completo_2.html',
    $root . '/docs/reference/pgm_erp_completo.html',
    $root . '/docs/referencias/pgm_erp_completo.html',
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

$registry = [];
$registryFile = $root . '/config/pgm_erp_screens.json';
if (is_file($registryFile)) {
    $decoded = json_decode((string)file_get_contents($registryFile), true);
    if (is_array($decoded)) {
        foreach ($decoded['screens'] ?? [] as $row) {
            if (!empty($row['id'])) {
                $registry[(string)$row['id']] = $row;
            }
        }
    }
}

$byStatus = ['implemented' => 0, 'bridge' => 0, 'placeholder' => 0, 'planned' => 0];
foreach ($registry as $row) {
    $st = (string)($row['status'] ?? 'planned');
    if (!isset($byStatus[$st])) {
        $byStatus[$st] = 0;
    }
    $byStatus[$st]++;
}

$missing = array_values(array_diff($mockScreens, array_keys($registry)));

$lines = [];
$lines[] = '=== Auditoria mock PGM ERP ===';
$lines[] = "Referência: {$htmlPath}";
$lines[] = 'Telas pg-* no HTML: ' . count($mockScreens);
$lines[] = 'Registry: ' . count($registry);
$lines[] = 'Módulos *-prototype: ' . implode(', ', $prototypeModules);
$lines[] = '';
$lines[] = 'Status (registry):';
foreach ($byStatus as $st => $n) {
    if ($n > 0) {
        $lines[] = "  {$st}: {$n}";
    }
}
if ($missing !== []) {
    $lines[] = '';
    $lines[] = 'FALTA no registry (' . count($missing) . '):';
    foreach ($missing as $id) {
        $lines[] = "  {$id}";
    }
}
$lines[] = '';
$lines[] = 'Docs: docs/PGM_ERP_COBERTURA_TELAS.md';
$lines[] = 'Gerar: python3 bin/generate_pgm_erp_coverage.py';

$out = implode("\n", $lines) . "\n";
echo $out;

$genDir = $root . '/docs/generated';
if (!is_dir($genDir)) {
    @mkdir($genDir, 0755, true);
}
file_put_contents($genDir . '/pgm_erp_audit_cli.txt', $out);

exit($missing !== [] ? 1 : 0);
