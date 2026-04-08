<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Utility\PortalUrlPath;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Redireciona GET/HEAD com /portal/portal/... para /portal/... (404 → rota válida).
 */
class CollapseDuplicatePortalPathMiddleware implements MiddlewareInterface {

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		$method = $request->getMethod();
		if ($method !== 'GET' && $method !== 'HEAD') {
			return $handler->handle($request);
		}

		$uri = $request->getUri();
		$path = $uri->getPath();
		$fixed = PortalUrlPath::normalizePath($path);
		if ($fixed === $path) {
			return $handler->handle($request);
		}

		$target = $fixed;
		$query = $uri->getQuery();
		if ($query !== '') {
			$target .= '?' . $query;
		}

		return (new Response())
			->withStatus(302)
			->withHeader('Location', $target);
	}
}
