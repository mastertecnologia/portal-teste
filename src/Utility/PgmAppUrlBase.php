<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Core\Configure;

/**
 * Prefixo URL da app (ex. /portal) para links e redirects quando APP_BASE não está no .env.
 */
final class PgmAppUrlBase {

	/**
	 * Caminho base sem barra final; vazio se a app estiver na raiz do vhost.
	 */
	public static function path(): string {
		$cfg = Configure::read('App.base');
		if ($cfg !== false && $cfg !== null && $cfg !== '') {
			return rtrim((string)$cfg, '/');
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
