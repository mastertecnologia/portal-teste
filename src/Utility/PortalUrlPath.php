<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Normaliza paths do app em subpasta /portal (evita /portal/portal/... por URL relativa mal resolvida).
 */
class PortalUrlPath {

	/**
	 * Path da URI (ex.: /portal/produtos/estoque/t).
	 */
	public static function normalizePath(string $path): string {
		$path = trim($path);
		if ($path === '') {
			return $path;
		}
		if ($path[0] !== '/') {
			$path = '/' . ltrim($path, '/');
		}
		$dup = '/portal/portal';
		while (strlen($path) >= strlen($dup) && strpos($path, $dup) === 0) {
			$path = '/portal' . substr($path, strlen($dup));
		}

		return $path;
	}

	/**
	 * Saída de Router::url ou query return (sem esquema http).
	 */
	public static function normalizeRelativeUrl(string $url): string {
		$url = trim($url);
		if ($url === '') {
			return $url;
		}
		if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
			return $url;
		}

		return self::normalizePath($url);
	}

	/**
	 * Redirect interno seguro (sem open redirect / protocol-relative).
	 *
	 * @param mixed $url
	 */
	public static function sanitizeInternalRedirect($url): ?string {
		if (!is_string($url)) {
			return null;
		}
		$url = trim(rawurldecode($url));
		if ($url === '') {
			return null;
		}
		if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
			return null;
		}
		if (strpos($url, '//') === 0) {
			return null;
		}
		if ($url[0] !== '/') {
			return null;
		}

		return self::normalizeRelativeUrl($url);
	}
}
