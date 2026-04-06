<?php
namespace App\Utility;

/**
 * Dicas de configuração para chamadas SOAP ao ERP (WebGrid / IIS).
 */
class ErpSoapUrl {

	/**
	 * Texto extra para exibir ao falhar SOAP quando urlerp aponta para esta máquina.
	 */
	public static function hintIfLocalhostUrlErp(string $urlerp): string {
		$u = strtolower(trim($urlerp));
		$ex = ErpGridUrl::defaultBaseUrl();
		if ($u === '') {
			return ' Configure a URL do ERP em Empresas → Editar empresa (campo URL ERP), ex.: ' . $ex;
		}
		if (preg_match('#\b(localhost|127\.0\.0\.1)\b#', $u)) {
			return ' A URL ERP usa localhost — no servidor do Portal, localhost é este computador, não o IIS do Grid. Defina em Empresas → URL ERP o host acessível ao servidor (ex.: ' . $ex . ').';
		}

		return '';
	}
}
