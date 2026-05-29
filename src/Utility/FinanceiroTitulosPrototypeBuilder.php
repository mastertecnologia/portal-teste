<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload pg-titulos — contas a receber (financeiro_lancamentos tipo receita).
 */
class FinanceiroTitulosPrototypeBuilder {

	/**
	 * @param array{tab?:string,cliente?:int,banco?:int,busca?:string,page?:int} $filters
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId, array $filters = []): array {
		$now = Time::now();
		$tab = (string)($filters['tab'] ?? 'todos');
		$clienteId = (int)($filters['cliente'] ?? 0);
		$bancoId = (int)($filters['banco'] ?? 0);
		$busca = trim((string)($filters['busca'] ?? ''));
		$page = max(1, (int)($filters['page'] ?? 1));
		$perPage = 50;

		$tbl = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
		$q = $tbl->find()
			->where([
				'FinanceiroLancamentos.idempresa' => $empresaId,
				'FinanceiroLancamentos.tipo' => 'receita',
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'nome', 'cnpj', 'cpf', 'tipo']],
				'FinanceiroBancos' => ['fields' => ['id', 'codigo_banco', 'numero_banco', 'nome', 'numero_agencia', 'digito_agencia']],
				'Faturamento' => ['fields' => ['id', 'numero', 'idordem', 'idorcamento']],
			])
			->order(['FinanceiroLancamentos.data_vencimento' => 'DESC', 'FinanceiroLancamentos.id' => 'DESC']);

		if ($clienteId > 0) {
			$q->where(['FinanceiroLancamentos.idcliente' => $clienteId]);
		}
		if ($bancoId > 0) {
			$q->where(['FinanceiroLancamentos.financeiro_banco_id' => $bancoId]);
		}

		$allRows = [];
		try {
			$allRows = $q->limit(500)->all()->toArray();
		} catch (\Throwable $e) {
			$allRows = [];
		}

		$parcelas = FinanceiroPrototypeSupport::parcelaMap($allRows);
		$kpi = [
			'total_receber' => 0.0,
			'vence_30d' => 0.0,
			'vencendo_7d' => 0.0,
			'em_atraso' => 0.0,
			'pago_mes' => 0.0,
			'pendentes' => 0,
		];
		$monthStart = $now->copy()->startOfMonth();
		$monthEnd = $now->copy()->endOfMonth();
		$in30 = $now->copy()->addDays(30);
		$in7 = $now->copy()->addDays(7);

		$items = [];
		foreach ($allRows as $l) {
			$cls = FinanceiroPrototypeSupport::classifyReceita($l, $now);
			$v = (float)$l->get('valor');
			$venc = $l->get('data_vencimento');
			$cliente = FinanceiroPrototypeSupport::clienteInfo($l->cliente ?? null);
			$fat = $l->faturamento ?? null;
			$origem = FinanceiroPrototypeSupport::origemReceita($l, $fat);
			$id = (int)$l->get('id');
			$codigo = FinanceiroPrototypeSupport::tituloCodigo($id, $l->get('data_lancamento'));

			if ($busca !== '') {
				$hay = strtolower($codigo . ' ' . $cliente['nome'] . ' ' . $origem['label'] . ' ' . (string)$l->get('descricao'));
				if (strpos($hay, strtolower($busca)) === false) {
					continue;
				}
			}

			if ($tab === 'vencendo' && $cls['state'] !== 'vencendo') {
				continue;
			}
			if ($tab === 'atraso' && $cls['state'] !== 'atraso') {
				continue;
			}
			if ($tab === 'pago' && $cls['state'] !== 'pago') {
				continue;
			}

			$pago = FinanceiroPrototypeSupport::isReceitaPaga($l);
			if (!$pago) {
				$kpi['total_receber'] += $v;
				$kpi['pendentes']++;
				if ($venc instanceof \DateTimeInterface) {
					if ($venc < $now) {
						$kpi['em_atraso'] += $v;
					} elseif ($venc <= $in7) {
						$kpi['vencendo_7d'] += $v;
					}
					if ($venc <= $in30) {
						$kpi['vence_30d'] += $v;
					}
				}
			} else {
				$rec = $l->get('data_recebimento') ?? $l->get('data_baixa');
				if ($rec instanceof \DateTimeInterface && $rec >= $monthStart && $rec <= $monthEnd) {
					$kpi['pago_mes'] += $v;
				}
			}

			$items[] = [
				'id' => $id,
				'codigo' => $codigo,
				'cliente_nome' => $cliente['nome'],
				'cliente_cnpj' => $cliente['cnpj'],
				'origem_label' => $origem['label'],
				'origem_badge' => $origem['badge'],
				'origem_url' => $origem['url'],
				'parcela' => $parcelas[$id] ?? '1/1',
				'vencimento' => $venc,
				'valor' => $v,
				'banco_label' => FinanceiroPrototypeSupport::bancoLabel($l->financeiro_banco ?? null),
				'status' => $cls,
				'fatura_url' => ['controller' => 'Financeiro', 'action' => 'fatura', $id],
			];
		}

		$totalItems = count($items);
		$totalPages = max(1, (int)ceil($totalItems / $perPage));
		if ($page > $totalPages) {
			$page = $totalPages;
		}
		$offset = ($page - 1) * $perPage;
		$pageItems = array_slice($items, $offset, $perPage);

		return [
			'crKpi' => $kpi,
			'crItems' => $pageItems,
			'crFiltros' => [
				'tab' => $tab,
				'cliente' => $clienteId,
				'banco' => $bancoId,
				'busca' => $busca,
				'page' => $page,
			],
			'crPaginacao' => [
				'total' => $totalItems,
				'page' => $page,
				'pages' => $totalPages,
				'per_page' => $perPage,
				'showing' => count($pageItems),
			],
			'crClientes' => $this->_loadClientes($empresaId),
			'crBancos' => $this->_loadBancos($empresaId),
			'crAtualizado' => $now,
		];
	}

	/**
	 * @return array<int,array{id:int,nome:string}>
	 */
	private function _loadClientes(int $empresaId): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('Clientes')
				->find('list', ['keyField' => 'id', 'valueField' => 'razaosocial'])
				->where(['idempresa' => $empresaId, 'inativo' => 0])
				->order(['razaosocial' => 'ASC'])
				->limit(200)
				->toArray();
			foreach ($rows as $id => $nome) {
				$out[] = ['id' => (int)$id, 'nome' => (string)$nome];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array{id:int,label:string}>
	 */
	private function _loadBancos(int $empresaId): array {
		$out = [];
		try {
			$rows = TableRegistry::getTableLocator()->get('FinanceiroBancos')
				->find()
				->where(['FinanceiroBancos.idempresa' => $empresaId, 'FinanceiroBancos.ativo' => true])
				->order(['FinanceiroBancos.nome' => 'ASC'])
				->limit(50)
				->all();
			foreach ($rows as $b) {
				$out[] = [
					'id' => (int)$b->get('id'),
					'label' => FinanceiroPrototypeSupport::bancoLabel($b),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}
}
