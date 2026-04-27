<?php
namespace App\Service\Clientes;

use App\Model\Table\ClientesTable;
use Cake\Database\Driver\Postgres;
use Cake\Log\Log;

/**
 * Correlaciona vários `clientes.id` na mesma empresa que representam o mesmo cliente operacional
 * (CNPJ/CPF, public_code, nome/razão normalizado). PostgreSQL: consulta com regexp_replace; demais drivers: só o id de referência.
 */
class ClienteCorrelatedIds {

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
	 * @return int[]
	 */
	public static function forEmpresaCliente(ClientesTable $Clientes, int $idempresa, int $idclienteRef): array {
		$idempresa = (int)$idempresa;
		$idclienteRef = (int)$idclienteRef;
		if ($idempresa <= 0 || $idclienteRef <= 0) {
			return $idclienteRef > 0 ? [$idclienteRef] : [];
		}
		$cli = $Clientes->find()
			->select(['id', 'cnpj', 'cpf', 'public_code', 'razaosocial', 'nome', 'tipo', 'idempresa'])
			->where(['Clientes.id' => $idclienteRef, 'Clientes.idempresa' => $idempresa])
			->first();
		if ($cli === null) {
			return [$idclienteRef];
		}
		$driver = $Clientes->getConnection()->getDriver();
		if (!($driver instanceof Postgres)) {
			return [(int)$cli->id];
		}
		$cnpjD = preg_replace('/\D/', '', (string)($cli->cnpj ?? ''));
		$cpfD = preg_replace('/\D/', '', (string)($cli->cpf ?? ''));
		$pub = trim((string)($cli->public_code ?? ''));
		$tipoPj = defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2;
		$isPj = (int)($cli->tipo ?? 0) === $tipoPj;
		$nomeRaw = $isPj ? (string)($cli->razaosocial ?? '') : (string)($cli->nome ?? '');
		$nomeKey = self::normalizeNomeKey($nomeRaw);

		$conds = ['id = :cid'];
		$params = ['eid' => $idempresa, 'cid' => (int)$cli->id];
		if ($pub !== '') {
			$conds[] = 'public_code = :pub';
			$params['pub'] = $pub;
		}
		if (strlen($cnpjD) >= 11) {
			$conds[] = "regexp_replace(coalesce(cnpj, ''), '[^0-9]', '', 'g') = :cnpjd";
			$params['cnpjd'] = $cnpjD;
		}
		if (strlen($cpfD) >= 9) {
			$conds[] = "regexp_replace(coalesce(cpf, ''), '[^0-9]', '', 'g') = :cpfd";
			$params['cpfd'] = $cpfD;
		}
		$nomeLen = function_exists('mb_strlen') ? mb_strlen($nomeKey, 'UTF-8') : strlen($nomeKey);
		if ($nomeKey !== '' && $nomeLen >= 4) {
			$conds[] = "(lower(trim(regexp_replace(coalesce(razaosocial, ''), E'\\s+', ' ', 'g'))) = :nkey OR lower(trim(regexp_replace(coalesce(nome, ''), E'\\s+', ' ', 'g'))) = :nkey)";
			$params['nkey'] = $nomeKey;
		}
		$sql = 'SELECT id FROM clientes WHERE idempresa = :eid AND (' . implode(' OR ', $conds) . ')';
		try {
			$stmt = $Clientes->getConnection()->execute($sql, $params);
			$ids = [];
			while (($row = $stmt->fetch('assoc')) !== false) {
				$ids[] = (int)($row['id'] ?? 0);
			}
			$ids = array_values(array_unique(array_filter($ids, static fn($v) => $v > 0)));

			return !empty($ids) ? $ids : [(int)$cli->id];
		} catch (\Throwable $e) {
			Log::warning('ClienteCorrelatedIds: ' . $e->getMessage());

			return [(int)$cli->id];
		}
	}
}
