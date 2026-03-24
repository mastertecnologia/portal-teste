#!/usr/bin/env php
<?php
/**
 * Gera scripts/postgres/pgm_sit_config_generated.sql com os IDs de situação do ticket,
 * lendo vendor/PGMPackages/TicketConstants.php (mesmos valores do PHP).
 *
 * Uso (na raiz do repositório portal-teste):
 *   php scripts/generate_pgm_sit_config.php
 *
 * Se não houver vendor (sem composer install), defina no ambiente:
 *   Windows PowerShell:
 *     $env:PGM_SIT_RESOLVIDO="3"; $env:PGM_SIT_FECHADO="4"; php scripts/generate_pgm_sit_config.php
 *   Linux/macOS:
 *     export PGM_SIT_RESOLVIDO=3 PGM_SIT_FECHADO=4 && php scripts/generate_pgm_sit_config.php
 *
 * Depois, no PostgreSQL (antes do patch grande), execute o .sql gerado, ou rode:
 *   php scripts/build_pgm_postgres_full_sql.php
 */
$root = dirname(__DIR__);
$outDir = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'postgres';
$outFile = $outDir . DIRECTORY_SEPARATOR . 'pgm_sit_config_generated.sql';
$constantsFile = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'PGMPackages' . DIRECTORY_SEPARATOR . 'TicketConstants.php';

$fromEnv = getenv('PGM_SIT_RESOLVIDO');
$fromEnvF = getenv('PGM_SIT_FECHADO');

if ($fromEnv !== false && $fromEnv !== '' && $fromEnvF !== false && $fromEnvF !== '') {
	$r = (int) $fromEnv;
	$f = (int) $fromEnvF;
} elseif (is_file($constantsFile)) {
	require_once $constantsFile;
	if (!defined('C_TicketSituacaoResolvido') || !defined('C_TicketSituacaoFechado')) {
		fwrite(STDERR, "Erro: {$constantsFile} não define C_TicketSituacaoResolvido / C_TicketSituacaoFechado.\n");
		exit(1);
	}
	$r = (int) C_TicketSituacaoResolvido;
	$f = (int) C_TicketSituacaoFechado;
} else {
	fwrite(STDERR, "Não encontrado: {$constantsFile}\n");
	fwrite(STDERR, "Execute `composer install` na raiz do projeto ou defina PGM_SIT_RESOLVIDO e PGM_SIT_FECHADO.\n");
	exit(1);
}

if ($r < 0 || $f < 0) {
	fwrite(STDERR, "Valores inválidos: resolvido={$r}, fechado={$f}\n");
	exit(1);
}

if (!is_dir($outDir)) {
	mkdir($outDir, 0755, true);
}

$srcComment = ($fromEnv !== false && $fromEnv !== '' && $fromEnvF !== false && $fromEnvF !== '')
	? 'PGM_SIT_RESOLVIDO/PGM_SIT_FECHADO'
	: basename($constantsFile);
$srcComment = str_replace(['--', "\n", "\r"], ' ', $srcComment);

$sql = <<<SQL
-- Gerado por scripts/generate_pgm_sit_config.php (fonte: {$srcComment})
-- C_TicketSituacaoResolvido = {$r}, C_TicketSituacaoFechado = {$f}
DO \$cfg\$
BEGIN
  PERFORM set_config('pgm.sit_resolvido', '{$r}', false);
  PERFORM set_config('pgm.sit_fechado', '{$f}', false);
END \$cfg\$;

SQL;

file_put_contents($outFile, $sql);

fwrite(STDOUT, "OK: {$outFile}\n");
fwrite(STDOUT, "  pgm.sit_resolvido = {$r}  (C_TicketSituacaoResolvido)\n");
fwrite(STDOUT, "  pgm.sit_fechado   = {$f}  (C_TicketSituacaoFechado)\n");
fwrite(STDOUT, "\nPróximo: php scripts/build_pgm_postgres_full_sql.php\n");
fwrite(STDOUT, "   ou: psql ... -f scripts/postgres/pgm_sit_config_generated.sql -f scripts/postgres/pgm_dashboard_verify_and_patch.sql\n");

exit(0);
