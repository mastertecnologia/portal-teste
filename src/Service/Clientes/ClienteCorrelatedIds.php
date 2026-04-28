<?php
namespace App\Service\Clientes;

use App\Model\Table\ClientesTable;
use Cake\Log\Log;

/**
 * Correlaciona vários `clientes.id` na mesma empresa que representam o mesmo cliente operacional
 * (CNPJ/CPF só dígitos ou removeCaracteres legado, public_code, nome/razão normalizado).
 *
 * Implementação em PHP sobre os registos da empresa — funciona em PostgreSQL, MySQL/MariaDB, etc.,
 * sem depender de `instanceof` do driver nem de SQL específico.
 */
class ClienteCorrelatedIds {
	// #region agent log
	protected static function _agentDebugLog18a583(string $runId, string $hypothesisId, string $location, string $message, array $data = []): void {
		$line = json_encode([
			'sessionId' => '18a583',
			'runId' => $runId,
			'hypothesisId' => $hypothesisId,
			'location' => $location,
			'message' => $message,
			'data' => $data,
			'timestamp' => (int) round(microtime(true) * 1000),
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
		@file_put_contents(ROOT . DS . 'debug-18a583.log', $line, FILE_APPEND);
	}
	// #endregion

	/**
	 * @param string|null $raw
	 */
	public static function normalizeNomeKey($raw): string {
		$s = trim((string)$raw);
		if ($s === '') {
			return '';
		}
		if (function_exists('mb_strtolower')) {
			$s = mb_strtolower($s, 'UTF-8');
		} else {
			$s = strtolower($s);
		}
		$s = preg_replace('/\s+/u', ' ', $s);

		return trim($s);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowCnpjDigits($row): string {
		$v = is_array($row) ? ($row['cnpj'] ?? '') : ($row->get('cnpj') ?? '');

		return preg_replace('/\D/', '', (string)$v);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowCpfDigits($row): string {
		$v = is_array($row) ? ($row['cpf'] ?? '') : ($row->get('cpf') ?? '');

		return preg_replace('/\D/', '', (string)$v);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowPublicCode($row): string {
		$v = is_array($row) ? ($row['public_code'] ?? '') : ($row->get('public_code') ?? '');

		return trim((string)$v);
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowId($row): int {
		$id = is_array($row) ? ($row['id'] ?? 0) : ($row->get('id') ?? 0);

		return (int)$id;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowTipo($row): int {
		$t = is_array($row) ? ($row['tipo'] ?? 0) : ($row->get('tipo') ?? 0);

		return (int)$t;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowNomeKeys($row): array {
		$rz = is_array($row) ? ($row['razaosocial'] ?? '') : ($row->get('razaosocial') ?? '');
		$nm = is_array($row) ? ($row['nome'] ?? '') : ($row->get('nome') ?? '');
		$nf = is_array($row) ? ($row['nomefantasia'] ?? '') : ($row->get('nomefantasia') ?? '');

		return [self::normalizeNomeKey($rz), self::normalizeNomeKey($nm), self::normalizeNomeKey($nf)];
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $ref
	 * @param \Cake\Datasource\EntityInterface|array<string, mixed> $row
	 */
	protected static function _rowMatchesRef($ref, $row): bool {
		$rid = (int)$ref->get('id');
		if (self::_rowId($row) === $rid) {
			return true;
		}
		$refPub = trim((string)($ref->get('public_code') ?? ''));
		if ($refPub !== '' && self::_rowPublicCode($row) === $refPub) {
			return true;
		}
		$refCnpjD = preg_replace('/\D/', '', (string)($ref->get('cnpj') ?? ''));
		if (strlen($refCnpjD) >= 11 && self::_rowCnpjDigits($row) === $refCnpjD) {
			return true;
		}
		if (function_exists('removeCaracteres')) {
			$rc = removeCaracteres((string)($ref->get('cnpj') ?? ''));
			$rowCnpj = is_array($row) ? ($row['cnpj'] ?? '') : ($row->get('cnpj') ?? '');
			$rcc = removeCaracteres((string)$rowCnpj);
			if ($rc !== '' && strlen($rc) >= 11 && $rcc === $rc) {
				return true;
			}
		}
		$refCpfD = preg_replace('/\D/', '', (string)($ref->get('cpf') ?? ''));
		if (strlen($refCpfD) >= 9 && self::_rowCpfDigits($row) === $refCpfD) {
			return true;
		}
		if (function_exists('removeCaracteres')) {
			$rp = removeCaracteres((string)($ref->get('cpf') ?? ''));
			$rowCpf = is_array($row) ? ($row['cpf'] ?? '') : ($row->get('cpf') ?? '');
			$rcp = removeCaracteres((string)$rowCpf);
			if ($rp !== '' && strlen($rp) >= 9 && $rcp === $rp) {
				return true;
			}
		}
		$tipoPj = defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2;
		$isPj = (int)($ref->get('tipo') ?? 0) === $tipoPj;
		$nomeRaw = $isPj ? (string)($ref->get('razaosocial') ?? '') : (string)($ref->get('nome') ?? '');
		if (trim($nomeRaw) === '') {
			$nomeRaw = (string)($ref->get('nomefantasia') ?? '');
		}
		$nomeKey = self::normalizeNomeKey($nomeRaw);
		$nomeLen = function_exists('mb_strlen') ? mb_strlen($nomeKey, 'UTF-8') : strlen($nomeKey);
		if ($nomeKey !== '' && $nomeLen >= 4) {
			[$krz, $knm, $knf] = self::_rowNomeKeys($row);
			if ($krz === $nomeKey || $knm === $nomeKey || $knf === $nomeKey) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return int[]
	 */
	public static function forEmpresaCliente(ClientesTable $Clientes, int $idempresa, int $idclienteRef): array {
		$idempresa = (int)$idempresa;
		$idclienteRef = (int)$idclienteRef;
		if ($idempresa <= 0 || $idclienteRef <= 0) {
			return $idclienteRef > 0 ? [$idclienteRef] : [];
		}
		$select = ['id', 'cnpj', 'cpf', 'razaosocial', 'nome', 'nomefantasia', 'tipo'];
		if (in_array('public_code', $Clientes->getSchema()->columns(), true)) {
			$select[] = 'public_code';
		}
		$cli = $Clientes->find()
			->select($select)
			->where(['Clientes.id' => $idclienteRef, 'Clientes.idempresa' => $idempresa])
			->first();
		if ($cli === null) {
			return [$idclienteRef];
		}
		$ids = [];
		$scanned = 0;
		$warnThreshold = 12000;
		$refNomeKeys = self::_rowNomeKeys($cli);
		// #region agent log
		self::_agentDebugLog18a583('post-fix', 'H9', 'ClienteCorrelatedIds::forEmpresaCliente:ref', 'reference cliente keys', [
			'idempresa' => $idempresa,
			'ref_idcliente' => (int)$cli->get('id'),
			'ref_public_code' => (string)($cli->get('public_code') ?? ''),
			'ref_cnpj_digits' => preg_replace('/\D/', '', (string)($cli->get('cnpj') ?? '')),
			'ref_cpf_digits' => preg_replace('/\D/', '', (string)($cli->get('cpf') ?? '')),
			'ref_nome_keys_sha1_8' => array_map(static function ($v) { return substr(sha1((string)$v), 0, 8); }, $refNomeKeys),
		]);
		// #endregion
		$debugSample = [];
		foreach (
			$Clientes->find()
				->select($select)
				->where(['Clientes.idempresa' => $idempresa])
				->all() as $row
		) {
			$scanned++;
			if ($scanned === $warnThreshold) {
				Log::warning(sprintf(
					'ClienteCorrelatedIds: empresa %d tem ≥%d clientes; correlacionar ativos pode ficar lenta.',
					$idempresa,
					$warnThreshold
				));
			}
			if (self::_rowMatchesRef($cli, $row)) {
				$ids[] = self::_rowId($row);
			}
			if (count($debugSample) < 20) {
				[$krz, $knm, $knf] = self::_rowNomeKeys($row);
				$debugSample[] = [
					'row_idcliente' => self::_rowId($row),
					'row_public_code' => self::_rowPublicCode($row),
					'row_cnpj_digits' => self::_rowCnpjDigits($row),
					'row_cpf_digits' => self::_rowCpfDigits($row),
					'row_nome_keys_sha1_8' => [substr(sha1($krz), 0, 8), substr(sha1($knm), 0, 8), substr(sha1($knf), 0, 8)],
					'matches' => self::_rowMatchesRef($cli, $row),
				];
			}
		}
		$ids = array_values(array_unique(array_filter($ids, static fn($v) => $v > 0)));
		// #region agent log
		self::_agentDebugLog18a583('post-fix', 'H10', 'ClienteCorrelatedIds::forEmpresaCliente:scanResult', 'scan completed', [
			'idempresa' => $idempresa,
			'ref_idcliente' => (int)$cli->get('id'),
			'scanned' => $scanned,
			'matched_ids' => $ids,
			'debug_sample' => $debugSample,
		]);
		// #endregion

		return !empty($ids) ? $ids : [(int)$cli->get('id')];
	}
}
