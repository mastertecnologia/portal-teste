<?php
namespace App\Utility;

use Cake\Core\Configure;

/**
 * Monta URLs WSDL/base do ERP a partir de empresas.urlerp e do padrão em config/grid.php.
 */
class ErpGridUrl {

	public const WSDL_PRODUTOS = 'WsProdutos.wso?wsdl';
	public const WSDL_PESSOAS = 'WSPGMPessoas.wso?wsdl';
	public const WSDL_CONTRATOS = 'WSPGMContratos.wso?wsdl';

	public static function defaultBaseUrl(): string {
		$u = Configure::read('Grid.defaultErpBaseUrl');
		if (!is_string($u) || trim($u) === '') {
			return 'http://10.0.2.7:85/WebGridPGM/';
		}

		return rtrim($u, '/') . '/';
	}

	public static function isLoopbackOrEmpty(?string $urlerp): bool {
		$u = $urlerp !== null ? trim($urlerp) : '';

		return $u === '' || (bool)preg_match('#\b(localhost|127\.0\.0\.1)\b#i', $u);
	}

	/**
	 * Base HTTP do ERP (barra final garantida). Usa urlerp do banco se válido para rede; senão o padrão.
	 */
	public static function resolveBaseUrl(?string $urlerp): string {
		if (self::isLoopbackOrEmpty($urlerp)) {
			return self::defaultBaseUrl();
		}

		return rtrim(trim((string)$urlerp), '/') . '/';
	}

	public static function wsdl(?string $urlerp, string $service = self::WSDL_PRODUTOS): string {
		return self::resolveBaseUrl($urlerp) . ltrim($service, '/');
	}
}
