<?php
declare(strict_types=1);

namespace App\Utility;

/**
 * Visual e rótulos do protótipo Bancos (pg-bancos / pg-contas).
 */
class FinanceiroBancosPrototypeUi {

	/**
	 * @return array{header:string,logo_bg:string,logo_fg:string,sigla:string,bar:string}
	 */
	public static function branding(string $codigoBanco, string $nomeBanco = ''): array {
		$key = ltrim(trim($codigoBanco), '0');
		if ($key === '') {
			$key = '0';
		}
		$map = [
			'1' => ['header' => '#003DA5', 'logo_bg' => '#FAE128', 'logo_fg' => '#003DA5', 'sigla' => 'BB', 'bar' => '#003DA5'],
			'748' => ['header' => '#1F7F1F', 'logo_bg' => '#3FA535', 'logo_fg' => '#fff', 'sigla' => 'SI', 'bar' => '#3FA535'],
			'237' => ['header' => '#7A0019', 'logo_bg' => '#CC092F', 'logo_fg' => '#fff', 'sigla' => 'BR', 'bar' => '#CC092F'],
			'104' => ['header' => '#003978', 'logo_bg' => '#0070AF', 'logo_fg' => '#fff', 'sigla' => 'CA', 'bar' => '#0070AF'],
			'341' => ['header' => '#A85100', 'logo_bg' => '#EC7000', 'logo_fg' => '#fff', 'sigla' => 'IT', 'bar' => '#EC7000'],
			'756' => ['header' => '#003641', 'logo_bg' => '#00AE9D', 'logo_fg' => '#fff', 'sigla' => 'SC', 'bar' => '#00AE9D'],
			'33' => ['header' => '#CC0000', 'logo_bg' => '#EC0000', 'logo_fg' => '#fff', 'sigla' => 'ST', 'bar' => '#EC0000'],
			'77' => ['header' => '#FF7A00', 'logo_bg' => '#fff', 'logo_fg' => '#FF7A00', 'sigla' => 'IN', 'bar' => '#FF7A00'],
			'260' => ['header' => '#820AD1', 'logo_bg' => '#fff', 'logo_fg' => '#820AD1', 'sigla' => 'NU', 'bar' => '#820AD1'],
		];
		if (isset($map[$key])) {
			return $map[$key];
		}
		$sigla = self::siglaFromNome($nomeBanco);

		return [
			'header' => '#1D9E75',
			'logo_bg' => 'rgba(255,255,255,.25)',
			'logo_fg' => '#fff',
			'sigla' => $sigla,
			'bar' => '#1D9E75',
		];
	}

	public static function siglaFromNome(string $nome): string {
		$nome = trim($nome);
		if ($nome === '') {
			return '??';
		}
		$parts = preg_split('/\s+/', $nome) ?: [];
		if (count($parts) >= 2) {
			return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
		}

		return mb_strtoupper(mb_substr($nome, 0, 2));
	}

	public static function tipoContaLabel(?string $observacoes): string {
		$obs = (string)$observacoes;
		if (preg_match('/tipo_conta:([^\n|]+)/i', $obs, $m)) {
			return trim($m[1]);
		}

		return __('Corrente PJ');
	}

	/**
	 * @return array{0:string,1:string}
	 */
	public static function splitNumeroDigito(string $raw): array {
		$raw = trim($raw);
		if ($raw === '') {
			return ['', ''];
		}
		if (strpos($raw, '-') !== false) {
			$parts = explode('-', $raw, 2);

			return [
				preg_replace('/\D/', '', $parts[0]),
				preg_replace('/\D/', '', $parts[1] ?? ''),
			];
		}

		return [preg_replace('/\D/', '', $raw), ''];
	}

	/**
	 * @param object $banco
	 */
	public static function formatAgenciaConta($banco): array {
		$ag = trim((string)$banco->get('numero_agencia'));
		$dgAg = trim((string)$banco->get('digito_agencia'));
		$cc = trim((string)$banco->get('numero_conta'));
		$dgCc = trim((string)$banco->get('digito_conta'));
		$agFmt = $ag !== '' ? $ag . ($dgAg !== '' ? '-' . $dgAg : '') : '—';
		$ccFmt = $cc !== '' ? $cc . ($dgCc !== '' ? '-' . $dgCc : '') : '—';

		return [$agFmt, $ccFmt];
	}
}
