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

		// CakePHP 3 retira App.base do PSR-7 URI antes do middleware: com
		// /portal/portal/produtos/... o getPath() vira /portal/produtos/... e a
		// detecção do duplicado falha. Usar REQUEST_URI bruto do SAPI.
		$reqUri = $request->getServerParam('REQUEST_URI', '');
		if ($reqUri === '' || $reqUri[0] !== '/') {
			return $next($request, $response);
		}

		$parsedPath = parse_url($reqUri, PHP_URL_PATH);
		if (!is_string($parsedPath) || $parsedPath === '') {
			return $next($request, $response);
		}

		$path = rawurldecode($parsedPath);
		$fixed = PortalUrlPath::normalizePath($path);
		if ($fixed === $path) {
			return $next($request, $response);
		}

		$target = $fixed;
		$query = parse_url($reqUri, PHP_URL_QUERY);
		if (is_string($query) && $query !== '') {
			$target .= '?' . $query;
		}

		return $response->withStatus(302)->withHeader('Location', $target);
	}
}
