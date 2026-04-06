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
		if ($u === '') {
			return ' Configure a URL do ERP em Empresas → Editar empresa (campo URL ERP), ex.: http://IP-DO-WINDOWS:85/WebGridPGM/';
		}
		if (preg_match('#\b(localhost|127\.0\.0\.1)\b#', $u)) {
			return ' A URL ERP usa localhost — no servidor do Portal, localhost é este computador, não o IIS do Grid. Defina em Empresas → URL ERP o IP ou hostname do Windows onde roda o WebGrid (ex.: http://10.0.2.7:85/WebGridPGM/).';
		}

		return '';
	}
}
