<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\ORM\Table;

/**
 * Flags eh_cliente / eh_fornecedor no cadastro mestre (clientes).
 */
class ClientesPapelCadastro {

	public const STATUS_HOMOLOGADO = 'homologado';
	public const STATUS_ANALISE = 'analise';
	public const STATUS_CADASTRADO = 'cadastrado';

	/**
	 * Confere colunas no banco (evita 500 quando cache do schema Cake diverge da migration).
	 *
	 * @param Table|null $clientesTable
	 */
	public static function columnsAvailable($clientesTable): bool {
		if ($clientesTable === null) {
			return false;
		}
		try {
			if (!$clientesTable->hasField('eh_cliente') || !$clientesTable->hasField('eh_fornecedor')) {
				return false;
			}
			$schema = $clientesTable->getConnection()->getSchemaCollection()->describe('clientes');

			return $schema->hasColumn('eh_cliente') && $schema->hasColumn('eh_fornecedor');
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @param array<string,mixed> $data POST/PUT
	 * @return array{ok:bool,errors?:array<string,string>,data?:array<string,mixed>}
	 */
	public static function normalizeFromRequest(array $data, bool $columnsAvailable): array {
		if (!$columnsAvailable) {
			unset($data['eh_cliente'], $data['eh_fornecedor'], $data['fornecedor_categoria'], $data['fornecedor_status_homologacao'], $data['fornecedor_lead_time_dias']);

			return ['ok' => true, 'data' => $data];
		}
		$ehCliente = !empty($data['eh_cliente']);
		$ehFornecedor = !empty($data['eh_fornecedor']);
		if (!$ehCliente && !$ehFornecedor) {
			return [
				'ok' => false,
				'errors' => ['papel' => __('Marque ao menos um papel: Cliente e/ou Fornecedor.')],
			];
		}
		$data['eh_cliente'] = $ehCliente;
		$data['eh_fornecedor'] = $ehFornecedor;
		if (!$ehFornecedor) {
			$data['fornecedor_categoria'] = null;
			$data['fornecedor_status_homologacao'] = null;
			$data['fornecedor_lead_time_dias'] = null;
		} else {
			$cat = trim((string)($data['fornecedor_categoria'] ?? ''));
			$data['fornecedor_categoria'] = $cat !== '' ? $cat : null;
			$st = trim((string)($data['fornecedor_status_homologacao'] ?? self::STATUS_CADASTRADO));
			if (!in_array($st, [self::STATUS_CADASTRADO, self::STATUS_ANALISE, self::STATUS_HOMOLOGADO], true)) {
				$st = self::STATUS_CADASTRADO;
			}
			$data['fornecedor_status_homologacao'] = $st;
			$lt = (int)($data['fornecedor_lead_time_dias'] ?? 0);
			$data['fornecedor_lead_time_dias'] = $lt > 0 ? min(999, $lt) : null;
		}

		return ['ok' => true, 'data' => $data];
	}

	/**
	 * Defaults ao abrir formulário novo.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaultsForNewEntity(bool $prefFornecedor = false): array {
		return [
			'eh_cliente' => $prefFornecedor ? false : true,
			'eh_fornecedor' => $prefFornecedor,
			'fornecedor_status_homologacao' => self::STATUS_CADASTRADO,
		];
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 */
	public static function isCliente($entity, bool $columnsAvailable): bool {
		if (!$columnsAvailable) {
			return true;
		}

		return (bool)$entity->get('eh_cliente');
	}

	/**
	 * @param \Cake\Datasource\EntityInterface $entity
	 */
	public static function isFornecedor($entity, bool $columnsAvailable): bool {
		if (!$columnsAvailable) {
			return false;
		}

		return (bool)$entity->get('eh_fornecedor');
	}

	/**
	 * WHERE para listagens de clientes (CRM).
	 *
	 * @return array<string,mixed>
	 */
	public static function whereCliente(int $idempresa, bool $columnsAvailable): array {
		$where = [
			'Clientes.idempresa' => $idempresa,
		];
		if ($columnsAvailable) {
			$where['Clientes.eh_cliente'] = true;
		}

		return $where;
	}

	/**
	 * WHERE para listagens de fornecedores.
	 *
	 * @return array<string,mixed>
	 */
	public static function whereFornecedor(int $idempresa, bool $columnsAvailable): array {
		$where = [
			'Clientes.idempresa' => $idempresa,
			'Clientes.inativo' => 0,
		];
		if ($columnsAvailable) {
			$where['Clientes.eh_fornecedor'] = true;
		} else {
			$where['Clientes.id'] = 0;
		}

		return $where;
	}

	public static function statusHomologacaoLabel(string $codigo): string {
		switch ($codigo) {
			case self::STATUS_HOMOLOGADO:
				return __('★ Homologado');
			case self::STATUS_ANALISE:
				return __('⚠ Em análise');
			default:
				return __('Cadastrado');
		}
	}

	public static function statusHomologacaoBadge(string $codigo): string {
		switch ($codigo) {
			case self::STATUS_HOMOLOGADO:
				return 'paga';
			case self::STATUS_ANALISE:
				return 'env';
			default:
				return 'aprov';
		}
	}

	public static function codigoFornecedorDisplay(int $id, ?string $publicCode = null): string {
		$pc = trim((string)$publicCode);
		if ($pc !== '' && stripos($pc, 'FOR-') === 0) {
			return strtoupper($pc);
		}

		return 'FOR-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
	}

	/**
	 * Código exibido conforme contexto (portal P… vs fornecedor FOR-…).
	 *
	 * @param \Cake\Datasource\EntityInterface $entity
	 */
	public static function codigoExibicao($entity, bool $columnsAvailable, bool $contextoFornecedor): string {
		if ($contextoFornecedor && $columnsAvailable && self::isFornecedor($entity, true)) {
			return self::codigoFornecedorDisplay((int)$entity->get('id'), $entity->get('public_code'));
		}
		$pc = trim((string)$entity->get('public_code'));

		return $pc !== '' ? $pc : '—';
	}
}
