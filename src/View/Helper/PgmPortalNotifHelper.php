<?php
namespace App\View\Helper;

use App\Utility\PgmAppUrlBase;
use Cake\View\Helper;

/**
 * URLs das rotas /pgm-notifications/* com App.base quando Url->build omite o prefixo.
 */
class PgmPortalNotifHelper extends Helper {

	public $helpers = ['Url'];

	/**
	 * @param array<string, mixed> $route Opções para Url->build
	 */
	public function url(array $route): string {
		$u = $this->Url->build($route);
		if (preg_match('#^https?://[^/]+(/.*)$#i', $u, $m)) {
			$u = $m[1];
		}
		$base = '';
		$req = $this->getView()->getRequest();
		if (method_exists($req, 'getAttribute')) {
			$base = (string)($req->getAttribute('base') ?? '');
		}
		if ($base === '' && isset($req->base)) {
			$base = (string)$req->base;
		}
		if ($base === '') {
			$base = PgmAppUrlBase::path($req);
		}
		$base = rtrim($base, '/');
		if ($base !== '' && $u !== '' && isset($u[0]) && $u[0] === '/' && strpos($u, $base . '/') !== 0) {
			return $base . $u;
		}

		return $u;
	}
}
