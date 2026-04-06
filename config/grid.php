<?php
/**
 * Integração ERP / WebGrid (SOAP .wso no IIS).
 *
 * URL padrão quando empresas.urlerp estiver vazio ou apontar para loopback (localhost/127.0.0.1).
 * Sobrescreva por ambiente: GRID_ERP_BASE_URL=http://host:85/WebGridPGM/
 */
$default = 'http://10.0.2.7:85/WebGridPGM/';
if (function_exists('env')) {
	$e = env('GRID_ERP_BASE_URL');
	if ($e !== null && trim((string)$e) !== '') {
		$default = rtrim(trim((string)$e), '/') . '/';
	}
}

return [
	'Grid' => [
		'defaultErpBaseUrl' => $default,
	],
];
