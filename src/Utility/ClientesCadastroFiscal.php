<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\Table;

/**
 * Regime tributário, CNAE e data de abertura no cadastro mestre (clientes).
 */
class ClientesCadastroFiscal {

	public const REGIME_SIMPLES = 'simples_nacional';
	public const REGIME_PRESUMIDO = 'lucro_presumido';
	public const REGIME_REAL = 'lucro_real';
	public const REGIME_MEI = 'mei';
	public const REGIME_IMUNE = 'imune_isento';
	public const REGIME_PF = 'pessoa_fisica';

	/**
	 * @return array<string,string>
	 */
	public static function regimeOptions(): array {
		return [
			self::REGIME_SIMPLES => __('Simples Nacional'),
			self::REGIME_PRESUMIDO => __('Lucro Presumido'),
			self::REGIME_REAL => __('Lucro Real'),
			self::REGIME_MEI => __('MEI · Microempreendedor Individual'),
			self::REGIME_IMUNE => __('Imune / Isento'),
			self::REGIME_PF => __('Pessoa Física'),
		];
	}

	/**
	 * @return array<string,string>
	 */
	public static function tipoEnderecoOptions(): array {
		return [
			'comercial_sede' => __('Comercial · Sede'),
			'comercial_filial' => __('Comercial · Filial'),
			'entrega' => __('Entrega'),
			'cobranca' => __('Cobrança'),
			'residencial' => __('Residencial'),
		];
	}

	public static function columnsAvailable($clientesTable): bool {
		if ($clientesTable === null) {
			return false;
		}
		try {
			if (!$clientesTable->hasField('regime_tributario')) {
				return false;
			}
			$schema = $clientesTable->getConnection()->getSchemaCollection()->describe('clientes');

			return $schema->hasColumn('regime_tributario');
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param array<string,mixed> $data
	 * @return array{ok:bool,errors?:array<string,string>,data?:array<string,mixed>}
	 */
	public static function normalizeFromRequest(array $data, bool $columnsAvailable): array {
		if (!$columnsAvailable) {
			unset($data['regime_tributario'], $data['cnae_principal'], $data['data_abertura'], $data['tipo_endereco']);

			return ['ok' => true, 'data' => $data];
		}
		$opts = array_keys(self::regimeOptions());
		$reg = trim((string)($data['regime_tributario'] ?? ''));
		if ($reg !== '' && !in_array($reg, $opts, true)) {
			return ['ok' => false, 'errors' => ['regime_tributario' => __('Regime tributário inválido.')]];
		}
		$data['regime_tributario'] = $reg !== '' ? $reg : null;

		$cnae = preg_replace('/\D+/', '', (string)($data['cnae_principal'] ?? ''));
		$data['cnae_principal'] = strlen($cnae) >= 4 ? substr($cnae, 0, 7) : null;

		$ab = trim((string)($data['data_abertura'] ?? ''));
		$data['data_abertura'] = $ab !== '' ? $ab : null;

		$te = trim((string)($data['tipo_endereco'] ?? ''));
		$teOpts = array_keys(self::tipoEnderecoOptions());
		$data['tipo_endereco'] = ($te !== '' && in_array($te, $teOpts, true)) ? $te : null;

		return ['ok' => true, 'data' => $data];
	}

	/**
	 * Inferência a partir da Receita (CNPJ) — ex.: NFS-e "Simples Nacional: Não" → Lucro Presumido.
	 *
	 * @param array<string,mixed> $raw
	 */
	public static function inferirRegimeFromReceitaRaw(array $raw): string {
		$porte = strtoupper((string)($raw['porte'] ?? ''));
		if (strpos($porte, 'MEI') !== false) {
			return self::REGIME_MEI;
		}
		if (!empty($raw['opcao_pelo_mei']) || !empty($raw['mei'])) {
			return self::REGIME_MEI;
		}
		if (isset($raw['simples']) && is_array($raw['simples'])) {
			$opt = $raw['simples']['optante'] ?? null;
			if ($opt === true || $opt === 1) {
				return self::REGIME_SIMPLES;
			}
			if ($opt === false || $opt === 0) {
				return self::REGIME_PRESUMIDO;
			}
		}
		$simples = $raw['opcao_pelo_simples'] ?? $raw['optante_simples'] ?? $raw['simples_nacional'] ?? null;
		if ($simples === true || $simples === 1) {
			return self::REGIME_SIMPLES;
		}
		$s = strtoupper(trim((string)$simples));
		if ($s === 'SIM' || $s === 'S' || $s === 'TRUE' || $s === '1') {
			return self::REGIME_SIMPLES;
		}
		if ($s === 'NAO' || $s === 'NÃO' || $s === 'N' || $s === 'FALSE' || $s === '0') {
			return self::REGIME_PRESUMIDO;
		}

		return self::REGIME_PRESUMIDO;
	}

	/**
	 * Enriquece payload da consulta CNPJ para o front (CNAE formatado, validação, rótulos).
	 *
	 * @param array<string,mixed> $dados
	 * @return array<string,mixed>
	 */
	public static function enriquecerDadosConsulta(array $dados): array {
		$cnae = $dados['cnae_principal'] ?? null;
		$codigo = null;
		$descricao = null;
		if (is_array($cnae)) {
			$codigo = isset($cnae['codigo']) ? preg_replace('/\D+/', '', (string)$cnae['codigo']) : null;
			$descricao = $cnae['descricao'] ?? null;
		} elseif (is_string($cnae) && $cnae !== '') {
			$codigo = preg_replace('/\D+/', '', $cnae);
		}
		if ($codigo !== null && strlen($codigo) > 7) {
			$codigo = substr($codigo, 0, 7);
		}
		$formatado = $codigo !== null && strlen($codigo) >= 4
			? self::formatCnaeInput(['codigo' => $codigo])
			: null;
		$dados['cnae_principal'] = $codigo !== null ? [
			'codigo' => $codigo,
			'descricao' => $descricao,
		] : null;
		$dados['cnae_principal_formatado'] = $formatado;
		$dados['cnae_principal_descricao'] = $descricao;
		$dados['cnae_principal_valido'] = $codigo !== null && strlen($codigo) === 7;

		$regime = (string)($dados['regime_tributario'] ?? '');
		if ($regime !== '') {
			$opts = self::regimeOptions();
			$dados['regime_tributario_rotulo'] = $opts[$regime] ?? $regime;
		}

		return $dados;
	}

	/**
	 * @param array<string,mixed>|null $cnae
	 */
	public static function formatCnaeStored(?string $codigo): ?string {
		if ($codigo === null || trim($codigo) === '') {
			return null;
		}

		return self::formatCnaeInput(['codigo' => $codigo]);
	}

	public static function formatCnaeInput(?array $cnae): ?string {
		if ($cnae === null || empty($cnae['codigo'])) {
			return null;
		}
		$d = preg_replace('/\D+/', '', (string)$cnae['codigo']);
		if (strlen($d) > 7) {
			$d = substr($d, 0, 7);
		}
		if (strlen($d) < 4) {
			return $d !== '' ? $d : null;
		}
		if (strlen($d) >= 7) {
			return substr($d, 0, 4) . '-' . substr($d, 4, 1) . '/' . substr($d, 5, 2);
		}

		return $d;
	}
}
