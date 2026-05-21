<?php
/**
 * Front Controller quando a pasta pública é "public" (estrutura Linux segura).
 * ROOT = dirname(__DIR__) = pasta pai (config, logs, src ficam fora do DocumentRoot).
 */
// for built-in server
if (php_sapi_name() === 'cli-server') {
	$_SERVER['PHP_SELF'] = '/' . basename(__FILE__);
	$url = parse_url(urldecode($_SERVER['REQUEST_URI']));
	$path = isset($url['path']) ? $url['path'] : '/';
	/*
	 * Document root é `public/`, mas os links usam App.base `/portal/...`.
	 * O SAPI procura `public/portal/dist/...` (inexistente). Se o ficheiro real está em
	 * `public/dist/...`, reescrever REQUEST_URI só para esta entrega estática (return false).
	 */
	if (strpos($path, '/portal/') === 0 && preg_match('#^/portal/(dist|css|js|assets|plugins|img|fonts|font)(/|$)#', $path)) {
		$diskRel = substr($path, strlen('/portal'));
		if ($diskRel === '' || $diskRel[0] !== '/') {
			$diskRel = '/' . ltrim($diskRel, '/');
		}
		$file = __DIR__ . $diskRel;
		if (strpos($diskRel, '..') === false && is_file($file)) {
			$ext = strtolower((string)pathinfo($file, PATHINFO_EXTENSION));
			$mimes = [
				'css' => 'text/css; charset=UTF-8',
				'js' => 'application/javascript; charset=UTF-8',
				'map' => 'application/json',
				'json' => 'application/json',
				'ico' => 'image/x-icon',
				'png' => 'image/png',
				'jpg' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'gif' => 'image/gif',
				'svg' => 'image/svg+xml',
				'woff' => 'font/woff',
				'woff2' => 'font/woff2',
				'ttf' => 'font/ttf',
				'eot' => 'application/vnd.ms-fontobject',
			];
			$mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
			header('Content-Type: ' . $mime);
			header('Content-Length: ' . (string)filesize($file));
			readfile($file);
			exit(0);
		}
	}
	$file = __DIR__ . $path;
	if (strpos($path, '..') === false && strpos($path, '.') !== false && is_file($file)) {
		return false;
	}
}
/*
 * PHP built-in server (SAPI cli-server): se PATH_INFO vier preenchido, o CakePHP 3
 * (ServerRequestFactory::marshalUriFromServer) substitui o path do Uri e **não** chama
 * updatePath() — com App.base = /portal o pedido /portal/users/... fica com path literal
 * /portal/... e o router trata "portal" como controller (MissingController PortalController).
 */
if (PHP_SAPI === 'cli-server' && isset($_SERVER['PATH_INFO'])) {
    unset($_SERVER['PATH_INFO']);
}
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

$server = new Server(new Application(dirname(__DIR__) . '/config'));
$server->emit($server->run());
