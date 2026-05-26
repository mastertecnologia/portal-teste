<?php
namespace App\Utility;

/**
 * Cálculo de desconto (% ou valor fixo) em orçamentos — cabeçalho e linhas.
 */
class OrcamentoDescontoUtil
{
	public static function descontoAbsoluto(float $base, float $valor, string $tipo): float
	{
		if ($valor <= 0 || $base <= 0) {
			return 0.0;
		}
		if ($tipo === 'fix') {
			return min($base, $valor);
		}

		return min($base, max(0.0, $base * ($valor / 100)));
	}

	/**
	 * @param object|array<string,mixed> $reg Linha Orcamentosservicos
	 */
	public static function linhaBruto($reg): float
	{
		$vm = (float)(is_array($reg) ? ($reg['valormensal'] ?? 0) : ($reg->valormensal ?? 0));
		if ($vm > 0.0001) {
			return $vm;
		}

		return (float)(is_array($reg) ? ($reg['valordoservico'] ?? 0) : ($reg->valordoservico ?? 0));
	}

	/**
	 * @param object|array<string,mixed> $reg
	 */
	public static function linhaDescontoAbsoluto($reg, bool $temColunasDesconto = true): float
	{
		$bruto = self::linhaBruto($reg);
		if (!$temColunasDesconto || $bruto <= 0) {
			return 0.0;
		}
		$dv = (float)(is_array($reg) ? ($reg['desconto_valor'] ?? 0) : ($reg->desconto_valor ?? 0));
		$tipo = (string)(is_array($reg) ? ($reg['desconto_tipo'] ?? 'pct') : ($reg->desconto_tipo ?? 'pct'));
		if (!in_array($tipo, ['pct', 'fix'], true)) {
			$tipo = 'pct';
		}

		return self::descontoAbsoluto($bruto, $dv, $tipo);
	}

	/**
	 * @param object|array<string,mixed> $reg
	 */
	public static function linhaLiquido($reg, bool $temColunasDesconto = true): float
	{
		$bruto = self::linhaBruto($reg);

		return max(0.0, $bruto - self::linhaDescontoAbsoluto($reg, $temColunasDesconto));
	}

	public static function rotuloDesconto(float $valor, string $tipo): string
	{
		if ($valor <= 0) {
			return '—';
		}
		if ($tipo === 'fix') {
			return 'R$ ' . number_format($valor, 2, ',', '.');
		}

		return rtrim(rtrim(number_format($valor, 2, ',', '.'), '0'), ',') . '%';
	}
}
