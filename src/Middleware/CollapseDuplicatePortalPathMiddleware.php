<?php
namespace App\Middleware;

use App\Utility\PortalUrlPath;

/**
 * CakePHP 3: invocável ($request, $response, $next) — não usar PSR-15 (inexistente no stack 3.10).
 * Redireciona GET/HEAD com /portal/portal/... para /portal/...
 */
class CollapseDuplicatePortalPathMiddleware {

	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param \Psr\Http\Message\ResponseInterface      $response
	 * @param callable                                 $next
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function __invoke($request, $response, $next) {
		$method = $request->getMethod();
		if ($method !== 'GET' && $method !== 'HEAD') {
			return $next($request, $response);
		}

		$uri = $request->getUri();
		$path = $uri->getPath();
		$fixed = PortalUrlPath::normalizePath($path);
		if ($fixed === $path) {
			return $next($request, $response);
		}

		$target = $fixed;
		$query = $uri->getQuery();
		if ($query !== '') {
			$target .= '?' . $query;
		}

		return $response->withStatus(302)->withHeader('Location', $target);
	}
}
