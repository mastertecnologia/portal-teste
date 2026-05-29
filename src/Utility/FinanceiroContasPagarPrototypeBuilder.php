<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Payload pg-contas-pagar — financeiro_lancamentos tipo despesa.
 */
class FinanceiroContasPagarPrototypeBuilder {

	/**
	 * @param array{status?:string,fornecedor?:int,periodo?:string,centro?:int,busca?:string,page?:int} $filters
	 * @return array<string,mixed>
	 */
	public function build(int $empresaId, array $filters = []): array {
		$now = Time::now();
		$statusFiltro = trim((string)($filters['status'] ?? ''));
		$fornecedorId = (int)($filters['fornecedor'] ?? 0);
		$centroId = (int)($filters['centro'] ?? 0);
		$busca = trim((string)($filters['busca'] ?? ''));
		$page = max(1, (int)($filters['page'] ?? 1));
		$perPage = 50;

		$tbl = TableRegistry::getTableLocator()->get('FinanceiroLancamentos');
		$q = $tbl->find()
			->where([
				'FinanceiroLancamentos.idempresa' => $empresaId,
				'FinanceiroLancamentos.tipo' => 'despesa',
			])
			->contain([
				'Clientes' => ['fields' => ['id', 'razaosocial', 'nome', 'cnpj', 'cpf']],
				'FinanceiroPlanoContas' => ['fields' => ['id', 'codigo', 'descricao']],
				'FinanceiroCentrosCusto' => ['fields' => ['id', 'codigo', 'descricao']],
			])
			->order(['FinanceiroLancamentos.data_vencimento' => 'ASC', 'FinanceiroLancamentos.id' => 'DESC']);

		if ($fornecedorId > 0) {
			$q->where(['FinanceiroLancamentos.idcliente' => $fornecedorId]);
		}
		if ($centroId > 0) {
			$q->where(['FinanceiroLancamentos.centro_custo_id' => $centroId]);
		}
		if ($statusFiltro !== '') {
			$q->where(['FinanceiroLancamentos.status' => $statusFiltro]);
		}

		$rows = [];
		try {
			$rows = $q->limit(500)->all()->toArray();
		} catch (\Throwable $e) {
		}

		$kpi = [
			'total_pagar' => 0.0,
			'titulos_abertos' => 0,
			'vencidos_valor' => 0.0,
			'vencidos_qtd' => 0,
			'vence_7d_valor' => 0.0,
			'vence_7d_qtd' => 0,
			'vence_mes_valor' => 0.0,
			'vence_mes_qtd' => 0,
			'aguarda_aprov_qtd' => 0,
			'aguarda_aprov_valor' => 0.0,
			'pago_mes' => 0.0,
		];
		$monthStart = $now->copy()->startOfMonth();
		$monthEnd = $now->copy()->endOfMonth();
		$monthEndDate = $monthEnd->format('Y-m-d');
		$in7 = $now->copy()->addDays(7);

		$items = [];
		foreach ($rows as $l) {
			$cls = FinanceiroPrototypeSupport::classifyDespesa($l, $now);
			$v = (float)$l->get('valor');
			$venc = $l->get('data_vencimento');
			$cliente = FinanceiroPrototypeSupport::clienteInfo($l->cliente ?? null);
			$id = (int)$l->get('id');
			$codigo = FinanceiroPrototypeSupport::despesaCodigo($id, $l->get('data_lancamento'));
			$plano = $l->financeiro_plano_conta ?? null;
			$categoria = $plano !== null
				? trim((string)($plano->get('descricao') ?? ''))
				: '';
			if ($categoria === '' && $plano !== null) {
				$categoria = (string)($plano->get('codigo') ?? '');
			}

			if ($busca !== '') {
				$hay = strtolower($codigo . ' ' . $cliente['nome'] . ' ' . $categoria . ' ' . (string)$l->get('descricao'));
				if (strpos($hay, strtolower($busca)) === false) {
					continue;
				}
			}

			$pago = FinanceiroPrototypeSupport::isDespesaPaga($l);
			if (!$pago) {
				$kpi['total_pagar'] += $v;
				$kpi['titulos_abertos']++;
				if ($cls['state'] === 'vencido') {
					$kpi['vencidos_valor'] += $v;
					$kpi['vencidos_qtd']++;
				} elseif ($cls['state'] === 'proximo') {
					$kpi['vence_7d_valor'] += $v;
					$kpi['vence_7d_qtd']++;
				}
				if ($venc instanceof \DateTimeInterface && $venc->format('Y-m-d') <= $monthEndDate) {
					$kpi['vence_mes_valor'] += $v;
					$kpi['vence_mes_qtd']++;
				}
				if ($cls['state'] === 'aberto') {
					$kpi['aguarda_aprov_qtd']++;
					$kpi['aguarda_aprov_valor'] += $v;
				}
			} else {
				$baixa = $l->get('data_baixa') ?? $l->get('data_recebimento');
				if ($baixa instanceof \DateTimeInterface && $baixa >= $monthStart && $baixa <= $monthEnd) {
					$kpi['pago_mes'] += $v;
				}
			}

			$vencLabel = '—';
			if ($venc instanceof \DateTimeInterface) {
				$vencLabel = $venc->format('d/m');
				if ($cls['dias'] !== null && !$pago) {
					if ($cls['dias'] < 0) {
						$vencLabel .= ' · -' . abs((int)$cls['dias']) . ' dias';
					} elseif ($cls['state'] === 'proximo') {
						$vencLabel .= ' · ' . (int)$cls['dias'] . ' dias';
					}
				}
			}

			$items[] = [
				'id' => $id,
				'codigo' => $codigo,
				'fornecedor' => $cliente['nome'],
				'documento' => \Cake\Utility\Text::truncate((string)$l->get('descricao'), 40, ['ellipsis' => '…']),
				'categoria' => $categoria !== '' ? $categoria : '—',
				'valor' => $v,
				'emissao' => $l->get('data_lancamento'),
				'vencimento' => $venc,
				'vencimento_label' => $vencLabel,
				'status' => $cls,
				'edit_url' => ['controller' => 'Financeiro', 'action' => 'editDespesa', $id],
				'pagar_url' => ['controller' => 'Financeiro', 'action' => 'fatura', $id],
			];
		}

		$totalVisivel = 0.0;
		foreach ($items as $it) {
			if ($it['status']['state'] !== 'pago') {
				$totalVisivel += (float)$it['valor'];
			}
		}

		$totalItems = count($items);
		$totalPages = max(1, (int)ceil($totalItems / $perPage));
		if ($page > $totalPages) {
			$page = $totalPages;
		}
		$pageItems = array_slice($items, ($page - 1) * $perPage, $perPage);

		return [
			'cpKpi' => $kpi,
			'cpItems' => $pageItems,
			'cpTotalVisivel' => $totalVisivel,
			'cpFiltros' => [
				'status' => $statusFiltro,
				'fornecedor' => $fornecedorId,
				'centro' => $centroId,
				'busca' => $busca,
				'page' => $page,
			],
			'cpPaginacao' => [
				'total' => $totalItems,
				'page' => $page,
				'pages' => $totalPages,
				'showing' => count($pageItems),
			],
			'cpFornecedores' => $this->_loadFornecedores($empresaId),
			'cpCentros' => $this->_loadCentros($empresaId),
		];
	}

	/**
	 * @return array<int,array{id:int,nome:string}>
	 */
	private function _loadFornecedores(int $empresaId): array {
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
	private function _loadCentros(int $empresaId): array {
		$out = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('FinanceiroCentrosCusto');
			if (method_exists($tbl, 'listByEmpresa')) {
				foreach ($tbl->listByEmpresa($empresaId) as $id => $label) {
					$out[] = ['id' => (int)$id, 'label' => (string)$label];
				}
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}
}
