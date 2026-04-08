<?php
namespace App\Middleware;

use App\Utility\PortalUrlPath;

/**
 * CakePHP 3: invocável ($request, $response, $next).
 *
 * Só redireciona GET/HEAD quando o path real do browser começa com /portal/portal/
 * (link relativo mal resolvido). Não usar getServerParam() — não existe no
 * ServerRequest do Cake 3; quebrava todo o site com fatal error.
 */
class CollapseDuplicatePortalPathMiddleware {

	/**
	 * REQUEST_URI compatível com CakePHP 3 / PSR-7.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return string
	 */
	private static function requestUriRaw($request) {
		if (method_exists($request, 'getEnv')) {
			$v = $request->getEnv('REQUEST_URI');
			if ($v !== null && $v !== false && $v !== '') {
				return (string)$v;
			}
		}
		$params = $request->getServerParams();

		return isset($params['REQUEST_URI']) ? (string)$params['REQUEST_URI'] : '';
	}

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

		$reqUri = self::requestUriRaw($request);
		if ($reqUri === '' || !isset($reqUri[0]) || $reqUri[0] !== '/') {
			return $next($request, $response);
		}

		$parsedPath = parse_url($reqUri, PHP_URL_PATH);
		if (!is_string($parsedPath) || $parsedPath === '') {
			return $next($request, $response);
		}

		$path = rawurldecode($parsedPath);
		if ($path !== '' && $path[0] !== '/') {
			$path = '/' . ltrim($path, '/');
		}

		// Só age no caso conhecido do bug; não reescreve outras URLs.
		if (strpos($path, '/portal/portal') !== 0) {
			return $next($request, $response);
		}

		$fixed = PortalUrlPath::normalizePath($path);
		if ($fixed === $path) {
			return $next($request, $response);
		}

		$target = $fixed;
		$query = parse_url($reqUri, PHP_URL_QUERY);
		if (is_string($query) && $query !== '') {
			$target .= '?' . $query;
		}

		if (method_exists($response, 'withRedirect')) {
			return $response->withRedirect($target, 302);
		}

		return $response->withStatus(302)->withHeader('Location', $target);
	}
}
