<?php
namespace App\Middleware;

use Cake\Core\Configure;

/**
 * Redireciona GET/HEAD de /portal-notifications/* para /{App.base}/portal-notifications/*
 * quando a app está em subpasta (ex.: /portal) e o browser pediu sem o prefixo (404 / "Não encontrado").
 */
class PortalNotificationsBasePathMiddleware {

	public function __invoke($request, $response, $next) {
		$method = $request->getMethod();
		if ($method !== 'GET' && $method !== 'HEAD') {
			return $next($request, $response);
		}
		$base = rtrim((string)Configure::read('App.base'), '/');
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
		if (strpos($path, '/portal-notifications') !== 0) {
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
