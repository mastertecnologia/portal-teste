#!/usr/bin/env php
<?php
/**
 * Teste rápido de rede até o SMTP (sem autenticar).
 * Uso no servidor: php scripts/smtp_reachability.php [pgm|master|default]
 *
 * Carrega o mesmo .env da raiz do projeto que o CakePHP.
 */
$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';
if (!is_readable($envFile)) {
	fwrite(STDERR, "Ficheiro .env não encontrado ou ilegível: {$envFile}\n");
	exit(1);
}

$raw = file_get_contents($envFile);
if ($raw !== false && strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
	$raw = substr($raw, 3);
}
$env = [];
foreach (preg_split('/\R/', $raw) ?: [] as $line) {
	$line = trim($line);
	if ($line === '' || strpos($line, '#') === 0) {
		continue;
	}
	if (stripos($line, 'export ') === 0) {
		$line = trim(substr($line, 7));
	}
	if (strpos($line, '=') === false) {
		continue;
	}
	list($k, $v) = explode('=', $line, 2);
	$k = ltrim(trim($k), "\xEF\xBB\xBF");
	$env[$k] = trim($v, " \t\"'");
}

$which = $argv[1] ?? 'pgm';
$prefix = strtoupper($which);
if ($which === 'default') {
	$host = $env['MAIL_DEFAULT_HOST'] ?? 'smtp.gmail.com';
	$port = (int)($env['MAIL_DEFAULT_PORT'] ?? 587);
} elseif ($which === 'master') {
	$host = $env['MAIL_MASTER_HOST'] ?? 'mail.pgm.inf.br';
	$port = (int)($env['MAIL_MASTER_PORT'] ?? 587);
} else {
	$host = $env['MAIL_PGM_HOST'] ?? 'mail.pgm.inf.br';
	$port = (int)($env['MAIL_PGM_PORT'] ?? 587);
}

// Mesma lógica que config/app.php para Skymail (host efetivo)
if ($which !== 'default' && ($env['MAIL_' . $prefix . '_TLS_INSECURE'] ?? '') !== '1'
	&& ($env['MAIL_' . $prefix . '_TLS_INSECURE'] ?? '') !== 'true'
	&& trim($host) === 'mail.pgm.inf.br'
	&& ($env['MAIL_' . $prefix . '_SKIP_SKYMAIL_TLS_PEER'] ?? '') !== '1'
	&& ($env['MAIL_' . $prefix . '_SKIP_SKYMAIL_TLS_PEER'] ?? '') !== 'true') {
	$host = 'mail.skymail.net.br';
	echo "(Skymail) Ajustado host para: {$host}\n";
}

$timeout = 8;
echo "A testar TCP {$host}:{$port} (timeout {$timeout}s)...\n";

$errno = 0;
$errstr = '';
$t0 = microtime(true);
$fp = @stream_socket_client(
	"tcp://{$host}:{$port}",
	$errno,
	$errstr,
	$timeout,
	STREAM_CLIENT_CONNECT
);
$ms = (int)round((microtime(true) - $t0) * 1000);

if (is_resource($fp)) {
	fclose($fp);
	echo "OK — porta aberta em {$ms} ms.\n";
	exit(0);
}

echo "FALHOU em {$ms} ms — errno={$errno} {$errstr}\n";
echo "Sugestão: firewall, DNS, host/porta errados no .env, ou servidor SMTP em baixo.\n";
exit(2);
