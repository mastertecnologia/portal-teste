#!/usr/bin/env php
<?php
/**
 * Gera um único SQL pronto para pgAdmin/psql: substitui o bloco >> BEGIN_PGM_SIT_CONFIG
 * pelo conteúdo de pgm_sit_config_generated.sql (rode antes generate_pgm_sit_config.php).
 *
 * Saída: scripts/postgres/pgm_dashboard_verify_and_patch_FULL.sql
 *
 * Uso:
 *   php scripts/generate_pgm_sit_config.php
 *   php scripts/build_pgm_postgres_full_sql.php
 *   psql -h 10.0.2.23 -U usuario -d banco -v ON_ERROR_STOP=1 -f scripts/postgres/pgm_dashboard_verify_and_patch_FULL.sql
 */
$root = dirname(__DIR__);
$genFile = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'postgres' . DIRECTORY_SEPARATOR . 'pgm_sit_config_generated.sql';
$mainFile = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'postgres' . DIRECTORY_SEPARATOR . 'pgm_dashboard_verify_and_patch.sql';
$outFile = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'postgres' . DIRECTORY_SEPARATOR . 'pgm_dashboard_verify_and_patch_FULL.sql';

if (!is_file($genFile)) {
	fwrite(STDERR, "Falta: {$genFile}\nRode antes: php scripts/generate_pgm_sit_config.php\n");
	exit(1);
}
if (!is_file($mainFile)) {
	fwrite(STDERR, "Falta: {$mainFile}\n");
	exit(1);
}

$generated = trim(file_get_contents($genFile));
$main = file_get_contents($mainFile);

$pattern = '/-- >> BEGIN_PGM_SIT_CONFIG\r?\n.*?\r?\n-- << END_PGM_SIT_CONFIG\r?\n/s';
$replaced = preg_replace($pattern, $generated . "\n", $main, 1, $count);

if ($count !== 1) {
	fwrite(STDERR, "Erro: não encontrei o bloco >> BEGIN_PGM_SIT_CONFIG ... << END_PGM_SIT_CONFIG no patch principal.\n");
	exit(1);
}

$banner = "-- =============================================================================\n"
	. "-- ARQUIVO GERADO — não edite à mão\n"
	. "-- Montado por: php scripts/build_pgm_postgres_full_sql.php\n"
	. "-- =============================================================================\n\n";

file_put_contents($outFile, $banner . $replaced);

fwrite(STDOUT, "OK: {$outFile}\n");
fwrite(STDOUT, "Execute no PostgreSQL (pgAdmin ou psql) este arquivo único.\n");

exit(0);
