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

	public function __invoke($request, $response, $next) {
		$method = $request->getMethod();
		if ($method !== 'GET' && $method !== 'HEAD') {
			return $next($request, $response);
		}
		$base = PgmAppUrlBase::path();
		if ($base === '') {
			return $next($request, $response);
		}
		$path = $request->getUri()->getPath();
		if ($path === '' || (isset($path[0]) && $path[0] !== '/')) {
			return $next($request, $response);
		}
		if (strpos($path, $base . '/') === 0) {
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
