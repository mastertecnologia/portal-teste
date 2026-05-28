<?php
declare(strict_types=1);

namespace App\Service\Pcp;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Dados reais do módulo PCP para telas premium (ORM).
 */
class PcpPrototypeDataService {

	/** @var int */
	private $idempresa;

	public function __construct(int $idempresa) {
		$this->idempresa = $idempresa;
	}

	public function tablesAvailable(): bool {
		try {
			$locator = TableRegistry::getTableLocator();

			return $locator->exists('PcpOrdensProducao');
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<string,int>
	 */
	public function dashboardKpis(): array {
		$out = [
			'ops_abertas' => 0,
			'ops_execucao' => 0,
			'ops_aguardando' => 0,
			'ops_concluidas' => 0,
			'centros' => 0,
			'fichas' => 0,
		];
		if (!$this->tablesAvailable()) {
			return $out;
		}
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			foreach ($ops->find()->where(['idempresa' => $this->idempresa])->all() as $o) {
				$st = strtolower((string)$o->get('status'));
				if (in_array($st, ['concluida', 'fechada', 'encerrada'], true)) {
					$out['ops_concluidas']++;
				} elseif (in_array($st, ['execucao', 'em_execucao', 'producao'], true)) {
					$out['ops_execucao']++;
				} elseif (in_array($st, ['aguardando_material', 'aguardando'], true)) {
					$out['ops_aguardando']++;
				} else {
					$out['ops_abertas']++;
				}
			}
			$ct = TableRegistry::getTableLocator()->get('PcpCentrosTrabalho');
			$out['centros'] = $ct->find()->where(['idempresa' => $this->idempresa, 'ativo' => true])->count();
			$fi = TableRegistry::getTableLocator()->get('PcpEngenhariaFichas');
			$out['fichas'] = $fi->find()->where(['idempresa' => $this->idempresa])->count();
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listOrdens(int $limit = 100): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			$rows = $ops->find()
				->contain(['Produtos'])
				->where(['PcpOrdensProducao.idempresa' => $this->idempresa])
				->order(['PcpOrdensProducao.created' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $o) {
				$p = $o->produto ?? null;
				$items[] = [
					'id' => (int)$o->get('id'),
					'numero' => (string)$o->get('numero'),
					'produto' => $p ? (string)($p->get('descricao') ?? '') : (string)($o->get('descricao') ?? '—'),
					'quantidade' => (float)$o->get('quantidade'),
					'produzida' => (float)$o->get('quantidade_produzida'),
					'status' => (string)$o->get('status'),
					'inicio' => $o->get('data_inicio_prev'),
					'fim' => $o->get('data_fim_prev'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public function getOrdem(int $id): ?array {
		if (!$this->tablesAvailable() || $id <= 0) {
			return null;
		}
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			$o = $ops->find()
				->contain(['Produtos', 'PcpApontamentos' => ['PcpCentrosTrabalho', 'Users']])
				->where(['PcpOrdensProducao.id' => $id, 'PcpOrdensProducao.idempresa' => $this->idempresa])
				->first();
			if ($o === null) {
				return null;
			}
			$p = $o->produto ?? null;
			$apont = [];
			foreach ($o->pcp_apontamentos ?? [] as $a) {
				$u = $a->user ?? null;
				$centro = $a->pcp_centros_trabalho ?? null;
				$apont[] = [
					'operacao' => (string)($a->get('operacao') ?? ''),
					'centro' => $centro ? (string)$centro->get('nome') : '—',
					'operador' => $u ? trim((string)($u->get('name') ?? $u->get('username'))) : '—',
					'inicio' => $a->get('inicio'),
					'fim' => $a->get('fim'),
					'boa' => (float)$a->get('quantidade_boa'),
					'refugo' => (float)$a->get('quantidade_refugo'),
				];
			}

			return [
				'id' => (int)$o->get('id'),
				'numero' => (string)$o->get('numero'),
				'produto' => $p ? (string)($p->get('descricao') ?? '') : (string)($o->get('descricao') ?? ''),
				'codigo' => $p ? (string)($p->get('codigo') ?? '') : '',
				'quantidade' => (float)$o->get('quantidade'),
				'produzida' => (float)$o->get('quantidade_produzida'),
				'status' => (string)$o->get('status'),
				'inicio' => $o->get('data_inicio_prev'),
				'fim' => $o->get('data_fim_prev'),
				'apontamentos' => $apont,
			];
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCentros(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpCentrosTrabalho');
			foreach ($tbl->find()->where(['idempresa' => $this->idempresa])->order(['codigo' => 'ASC'])->limit($limit)->all() as $c) {
				$items[] = [
					'id' => (int)$c->get('id'),
					'codigo' => (string)$c->get('codigo'),
					'nome' => (string)$c->get('nome'),
					'capacidade' => (float)($c->get('capacidade_h_dia') ?? 0),
					'custo_h' => (float)($c->get('custo_h') ?? 0),
					'ativo' => (bool)$c->get('ativo'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listFichas(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpEngenhariaFichas');
			foreach ($tbl->find()->contain(['Produtos'])->where(['idempresa' => $this->idempresa])->order(['codigo' => 'ASC'])->limit($limit)->all() as $f) {
				$p = $f->produto ?? null;
				$items[] = [
					'id' => (int)$f->get('id'),
					'codigo' => (string)$f->get('codigo'),
					'revisao' => (string)$f->get('revisao'),
					'produto' => $p ? (string)($p->get('descricao') ?? '') : '—',
					'status' => (string)$f->get('status'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listBom(int $limit = 120): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpBomItens');
			$rows = $tbl->find()
				->contain(['ParentProdutos', 'ChildProdutos'])
				->where(['PcpBomItens.idempresa' => $this->idempresa])
				->order(['PcpBomItens.id' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $b) {
				$parent = $b->parent_produto ?? null;
				$child = $b->child_produto ?? null;
				$items[] = [
					'id' => (int)$b->get('id'),
					'parent' => $parent ? (string)$parent->get('descricao') : '—',
					'child' => $child ? (string)$child->get('descricao') : '—',
					'qtd' => (float)$b->get('quantidade'),
					'scrap' => (float)($b->get('scrap_pct') ?? 0),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listApontamentosRecentes(int $limit = 50): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpApontamentos');
			$desde = Time::now()->subDays(30);
			$rows = $tbl->find()
				->contain(['PcpOrdensProducao', 'PcpCentrosTrabalho', 'Users'])
				->where(['PcpApontamentos.idempresa' => $this->idempresa, 'PcpApontamentos.inicio >=' => $desde])
				->order(['PcpApontamentos.inicio' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $a) {
				$op = $a->pcp_ordens_producao ?? null;
				$items[] = [
					'ordem' => $op ? (string)$op->get('numero') : '—',
					'operacao' => (string)($a->get('operacao') ?? ''),
					'inicio' => $a->get('inicio'),
					'fim' => $a->get('fim'),
					'boa' => (float)$a->get('quantidade_boa'),
					'refugo' => (float)$a->get('quantidade_refugo'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	public function tableExists(string $alias): bool {
		try {
			return TableRegistry::getTableLocator()->exists($alias);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listRoteiros(int $limit = 120): array {
		if (!$this->tablesAvailable() || !$this->tableExists('PcpRoteiroOperacoes')) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpRoteiroOperacoes');
			$rows = $tbl->find()
				->contain(['Produtos', 'PcpCentrosTrabalho'])
				->where(['PcpRoteiroOperacoes.idempresa' => $this->idempresa])
				->order(['PcpRoteiroOperacoes.idproduto' => 'ASC', 'PcpRoteiroOperacoes.sequencia' => 'ASC'])
				->limit($limit)
				->all();
			foreach ($rows as $r) {
				$p = $r->produto ?? null;
				$ct = $r->pcp_centros_trabalho ?? null;
				$items[] = [
					'produto' => $p ? (string)($p->get('descricao') ?? '') : '—',
					'sequencia' => (int)$r->get('sequencia'),
					'operacao' => (string)$r->get('operacao'),
					'centro' => $ct ? (string)$ct->get('nome') : '—',
					'setup' => (float)($r->get('tempo_setup_min') ?? 0),
					'run' => (float)($r->get('tempo_run_min') ?? 0),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listMrpNecessidades(int $limit = 150): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$needs = [];
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			$bom = TableRegistry::getTableLocator()->get('PcpBomItens');
			$open = $ops->find()
				->where(['idempresa' => $this->idempresa])
				->andWhere(function ($exp) {
					return $exp->notIn('status', ['concluida', 'fechada', 'encerrada', 'cancelada']);
				})
				->all();
			foreach ($open as $o) {
				$parentId = (int)($o->get('idproduto') ?? 0);
				if ($parentId <= 0) {
					continue;
				}
				$opQty = (float)$o->get('quantidade');
				$opNum = (string)$o->get('numero');
				foreach ($bom->find()->contain(['ChildProdutos'])->where([
					'idempresa' => $this->idempresa,
					'parent_produto_id' => $parentId,
				])->all() as $b) {
					$child = $b->child_produto ?? null;
					$childId = (int)$b->get('child_produto_id');
					$scrap = 1 + ((float)($b->get('scrap_pct') ?? 0) / 100);
					$qtd = $opQty * (float)$b->get('quantidade') * $scrap;
					$key = $childId . '|' . $opNum;
					if (!isset($needs[$key])) {
						$needs[$key] = [
							'componente' => $child ? (string)($child->get('descricao') ?? '') : '—',
							'op' => $opNum,
							'necessidade' => 0.0,
						];
					}
					$needs[$key]['necessidade'] += $qtd;
				}
			}
		} catch (\Throwable $e) {
		}
		$items = array_values($needs);
		usort($items, static function ($a, $b) {
			return strcmp((string)$a['componente'], (string)$b['componente']);
		});

		return array_slice($items, 0, $limit);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCronograma(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			$rows = $ops->find()
				->contain(['Produtos'])
				->where(['idempresa' => $this->idempresa])
				->order(['data_inicio_prev' => 'ASC', 'numero' => 'ASC'])
				->limit($limit)
				->all();
			foreach ($rows as $o) {
				$p = $o->produto ?? null;
				$items[] = [
					'numero' => (string)$o->get('numero'),
					'produto' => $p ? (string)($p->get('descricao') ?? '') : (string)($o->get('descricao') ?? '—'),
					'inicio' => $o->get('data_inicio_prev'),
					'fim' => $o->get('data_fim_prev'),
					'status' => (string)$o->get('status'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listQualidadeRefugo(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpApontamentos');
			$rows = $tbl->find()
				->contain(['PcpOrdensProducao'])
				->where([
					'PcpApontamentos.idempresa' => $this->idempresa,
					'PcpApontamentos.quantidade_refugo >' => 0,
				])
				->order(['PcpApontamentos.inicio' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $a) {
				$op = $a->pcp_ordens_producao ?? null;
				$items[] = [
					'ordem' => $op ? (string)$op->get('numero') : '—',
					'operacao' => (string)($a->get('operacao') ?? ''),
					'boa' => (float)$a->get('quantidade_boa'),
					'refugo' => (float)$a->get('quantidade_refugo'),
					'inicio' => $a->get('inicio'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listExpedicaoPendente(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$ops = TableRegistry::getTableLocator()->get('PcpOrdensProducao');
			$rows = $ops->find()
				->contain(['Produtos'])
				->where(['idempresa' => $this->idempresa])
				->andWhere(function ($exp) {
					return $exp->in('status', [
						'concluida',
						'expedicao',
						'pronta_expedicao',
						'pronta',
						'fechada',
					]);
				})
				->order(['modified' => 'DESC', 'created' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $o) {
				$p = $o->produto ?? null;
				$items[] = [
					'numero' => (string)$o->get('numero'),
					'produto' => $p ? (string)($p->get('descricao') ?? '') : (string)($o->get('descricao') ?? '—'),
					'quantidade' => (float)$o->get('quantidade_produzida') ?: (float)$o->get('quantidade'),
					'status' => (string)$o->get('status'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @param string|null $tipo
	 * @return array<int,array<string,mixed>>
	 */
	public function listRequisicoesCompra(?string $tipo = null, int $limit = 100): array {
		if (!$this->tablesAvailable() || !$this->tableExists('PcpRequisicoesCompra')) {
			return [];
		}
		$items = [];
		try {
			$q = TableRegistry::getTableLocator()->get('PcpRequisicoesCompra')->find()
				->contain(['Produtos'])
				->where(['idempresa' => $this->idempresa]);
			if ($tipo !== null && $tipo !== '') {
				$q->andWhere(['tipo' => $tipo]);
			}
			foreach ($q->order(['created' => 'DESC'])->limit($limit)->all() as $r) {
				$p = $r->produto ?? null;
				$items[] = [
					'numero' => (string)$r->get('numero'),
					'tipo' => (string)$r->get('tipo'),
					'descricao' => (string)($r->get('descricao') ?? ($p ? $p->get('descricao') : '')),
					'quantidade' => (float)$r->get('quantidade'),
					'status' => (string)$r->get('status'),
					'created' => $r->get('created'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listCustosProducao(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$items = [];
		try {
			$tbl = TableRegistry::getTableLocator()->get('PcpApontamentos');
			$rows = $tbl->find()
				->contain(['PcpOrdensProducao', 'PcpCentrosTrabalho'])
				->where(['PcpApontamentos.idempresa' => $this->idempresa])
				->order(['PcpApontamentos.inicio' => 'DESC'])
				->limit($limit)
				->all();
			foreach ($rows as $a) {
				$op = $a->pcp_ordens_producao ?? null;
				$ct = $a->pcp_centros_trabalho ?? null;
				$horas = 0.0;
				$ini = $a->get('inicio');
				$fim = $a->get('fim');
				if ($ini instanceof \DateTimeInterface && $fim instanceof \DateTimeInterface) {
					$horas = max(0, ($fim->getTimestamp() - $ini->getTimestamp()) / 3600);
				}
				$custoH = $ct ? (float)($ct->get('custo_h') ?? 0) : 0.0;
				$items[] = [
					'ordem' => $op ? (string)$op->get('numero') : '—',
					'centro' => $ct ? (string)$ct->get('nome') : '—',
					'horas' => round($horas, 2),
					'custo' => round($horas * $custoH, 2),
				];
			}
		} catch (\Throwable $e) {
		}

		return $items;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function listProdutosConfigurador(int $limit = 80): array {
		if (!$this->tablesAvailable()) {
			return [];
		}
		$byId = [];
		try {
			$fichas = TableRegistry::getTableLocator()->get('PcpEngenhariaFichas');
			foreach ($fichas->find()->contain(['Produtos'])->where(['idempresa' => $this->idempresa])->limit($limit)->all() as $f) {
				$pid = (int)($f->get('idproduto') ?? 0);
				if ($pid <= 0) {
					continue;
				}
				$p = $f->produto ?? null;
				$byId[$pid] = [
					'produto' => $p ? (string)($p->get('descricao') ?? '') : '—',
					'codigo' => $p ? (string)($p->get('codigo') ?? '') : '',
					'fichas' => 1,
					'bom_itens' => 0,
				];
			}
			$bom = TableRegistry::getTableLocator()->get('PcpBomItens');
			foreach ($bom->find()->contain(['ParentProdutos'])->where(['idempresa' => $this->idempresa])->limit($limit * 2)->all() as $b) {
				$pid = (int)$b->get('parent_produto_id');
				if ($pid <= 0) {
					continue;
				}
				$p = $b->parent_produto ?? null;
				if (!isset($byId[$pid])) {
					$byId[$pid] = [
						'produto' => $p ? (string)($p->get('descricao') ?? '') : '—',
						'codigo' => $p ? (string)($p->get('codigo') ?? '') : '',
						'fichas' => 0,
						'bom_itens' => 0,
					];
				}
				$byId[$pid]['bom_itens']++;
			}
		} catch (\Throwable $e) {
		}

		return array_slice(array_values($byId), 0, $limit);
	}

}
