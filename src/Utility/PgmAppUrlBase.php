<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;

/**
 * Prefixo URL da app (ex. /portal) para links e redirects quando APP_BASE não está no .env.
 */
final class PgmAppUrlBase {

	/**
	 * Caminho base sem barra final; vazio se a app estiver na raiz do vhost.
	 *
	 * Em produção com rewrite (SCRIPT_NAME=/index.php), o dirname não inclui /portal;
	 * o Cake expõe o prefixo em {@see ServerRequest::$base} — passar $request quando disponível.
	 */
	public static function path(?ServerRequest $request = null): string {
		$cfg = Configure::read('App.base');
		if ($cfg !== false && $cfg !== null && $cfg !== '') {
			return rtrim((string)$cfg, '/');
		}
		if ($request !== null) {
			$base = (string)($request->getAttribute('base') ?? '');
			if ($base === '' && isset($request->base)) {
				$base = (string)$request->base;
			}
			$base = rtrim($base, '/');
			if ($base !== '' && $base !== '/') {
				return $base;
			}
		}
		$script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
		if ($script === '') {
			return '';
		}
		$dir = str_replace('\\', '/', dirname($script));
		if ($dir === '' || $dir === '/' || $dir === '.') {
			return '';
		}

		return rtrim($dir, '/');
	}
}
