<?php
namespace App\Middleware;

use App\Utility\PgmAppUrlBase;

/**
 * Redireciona GET/HEAD de /pgm-notifications/* ou /portal-notifications/* para /{App.base}/...
 * quando a app está em subpasta (ex.: /portal) e o browser pediu sem o prefixo (404 / "Não encontrado").
 *
 * Base: APP_BASE no .env; se vazio, dirname(SCRIPT_NAME) (ex.: /portal/index.php → /portal).
 */
class PortalNotificationsBasePathMiddleware {

	/**
	 * Path público do browser (inclui /portal/...). getUri()->getPath() na subpasta costuma vir
	 * sem o prefixo App.base — usar só ele provoca 302 para a mesma URL e loop infinito.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return string
	 */
	private static function browserRequestPath($request) {
		if (method_exists($request, 'getEnv')) {
			$v = $request->getEnv('REQUEST_URI');
			if ($v !== null && $v !== false && $v !== '') {
				$reqUri = (string)$v;
			} else {
				$reqUri = '';
			}
		} else {
			$params = $request->getServerParams();
			$reqUri = isset($params['REQUEST_URI']) ? (string)$params['REQUEST_URI'] : '';
		}
		if ($reqUri === '') {
			return '';
		}
		$parsedPath = parse_url($reqUri, PHP_URL_PATH);
		if (!is_string($parsedPath) || $parsedPath === '') {
			return '';
		}
		$path = rawurldecode($parsedPath);
		if ($path !== '' && (!isset($path[0]) || $path[0] !== '/')) {
			$path = '/' . ltrim($path, '/');
		}

		return $path;
	}

	public function __invoke($request, $response, $next) {
		$method = $request->getMethod();
		if ($method !== 'GET' && $method !== 'HEAD') {
			return $next($request, $response);
		}
		$base = PgmAppUrlBase::path();
		if ($base === '') {
			return $next($request, $response);
		}
		$browserPath = self::browserRequestPath($request);
		$path = $browserPath !== '' ? $browserPath : $request->getUri()->getPath();
		if ($path === '' || (isset($path[0]) && $path[0] !== '/')) {
			return $next($request, $response);
		}
		if (strpos($path, $base . '/') === 0 || $path === $base) {
			return $next($request, $response);
		}
		$needsBase = (strpos($path, '/pgm-notifications') === 0 || strpos($path, '/portal-notifications') === 0);
		if (!$needsBase) {
			return $next($request, $response);
		}
		$query = $request->getUri()->getQuery();
		$target = $base . $path;
		if ($query !== '') {
			$target .= '?' . $query;
		}
		if (method_exists($response, 'withRedirect')) {
			return $response->withRedirect($target, 302);
		}

		return $response->withStatus(302)->withHeader('Location', $target);
	}
}
