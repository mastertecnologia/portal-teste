<?php
/**
 * Entrada alternativa do webhook Autentique quando o URL canónico
 * /modulo-contratos/webhook/autentique devolve 404 no vhost (ex.: pgm.inf.br sem rewrite).
 *
 * Configure na Autentique: https://SEU_DOMINIO/webhook-autentique.php
 * (o POST é tratado como /modulo-contratos/webhook/autentique no Cake).
 */
$rootDir = dirname(__DIR__);
$base = getenv('APP_BASE');
if ($base === false || $base === '' || $base === 'false') {
	$base = '';
} else {
	$base = rtrim((string)$base, '/');
}
$path = ($base !== '' ? $base : '') . '/modulo-contratos/webhook/autentique';
if (!empty($_SERVER['QUERY_STRING'])) {
	$path .= '?' . $_SERVER['QUERY_STRING'];
}
$_SERVER['SCRIPT_FILENAME'] = $rootDir . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = ($base !== '' ? $base : '') . '/index.php';
$_SERVER['REQUEST_URI'] = $path;

require $rootDir . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application($rootDir . '/config'));
$server->emit($server->run());
