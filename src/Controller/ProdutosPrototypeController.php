<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Produtos — protótipo (mockup pg-produtos, pg-produto-novo, pg-produto-detalhe,
 * pg-estoque, pg-precos, pg-precificacao, pg-historico-precos, pg-import-produtos).
 *
 * Lado-a-lado com ProdutosController (legado). Rotas em /produtos-prototype/*.
 */
class ProdutosPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Produtos');
	}

	public function beforeFilter(Event $event) {
		$redirect = $this->request->getRequestTarget();
		$staffLogin = [
			'controller' => 'Users',
			'action' => 'acessoEmpresa',
			'prefix' => false,
			'?' => ['redirect' => $redirect],
		];
		$this->Auth->setConfig('loginAction', $staffLogin);
		$this->Auth->setConfig('unauthorizedRedirect', $staffLogin);
		parent::beforeFilter($event);
		$this->viewBuilder()->setLayout('erp_prototype');
	}

	public function isAuthorized($user) {
		if (empty($user)) {
			return false;
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * pg-produtos — lista de produtos com KPIs por tipo + estoque.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa])
				->order(['Produtos.id' => 'DESC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['total' => 0, 'ativos' => 0, 'inativos' => 0, 'sem_estoque' => 0];
		$valorTotal = 0.0;
		$items = [];
		foreach ($rows as $r) {
			$counts['total']++;
			$ativo = (int)$r->get('ativo') === 1;
			if ($ativo) {
				$counts['ativos']++;
			} else {
				$counts['inativos']++;
			}
			$estoque = (float)($r->get('estoque_atual') ?? 0);
			if ($estoque <= 0) {
				$counts['sem_estoque']++;
			}
			$valor = (float)($r->get('vlunitario') ?? 0);
			$valorTotal += $valor;
			$items[] = [
				'id' => (int)$r->get('id'),
				'codigo' => (string)($r->get('codigo') ?? ''),
				'descricao' => (string)($r->get('descricao') ?? ''),
				'tipo' => (string)($r->get('tipo') ?? ''),
				'unidade' => (string)($r->get('unidade') ?? ''),
				'ncm' => (string)($r->get('ncm') ?? ''),
				'preco' => $valor,
				'estoque' => $estoque,
				'ativo' => $ativo,
			];
		}

		$this->set([
			'title' => __('Produtos'),
			'erpNavActive' => 'produtos',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Produtos'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'prodCounts' => $counts,
			'prodValorTotal' => $valorTotal,
			'prodItems' => $items,
		]);
	}

	/**
	 * pg-estoque — listagem de estoque (mesmos produtos, ordenado por saldo).
	 */
	public function estoque() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa, 'Produtos.tipo' => 'prod'])
				->order(['Produtos.estoque_atual' => 'ASC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$kpi = ['baixo' => 0, 'zero' => 0, 'ok' => 0];
		$items = [];
		foreach ($rows as $r) {
			$est = (float)($r->get('estoque_atual') ?? 0);
			if ($est <= 0) {
				$kpi['zero']++;
				$status = 'no';
			} elseif ($est < 5) {
				$kpi['baixo']++;
				$status = 'low';
			} else {
				$kpi['ok']++;
				$status = 'ok';
			}
			$items[] = [
				'id' => (int)$r->get('id'),
				'codigo' => (string)($r->get('codigo') ?? ''),
				'descricao' => (string)($r->get('descricao') ?? ''),
				'unidade' => (string)($r->get('unidade') ?? ''),
				'estoque' => $est,
				'status' => $status,
				'preco' => (float)($r->get('vlunitario') ?? 0),
			];
		}

		$this->set([
			'title' => __('Estoque'),
			'erpNavActive' => 'estoque',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Produtos'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'lista']],
				['label' => __('Estoque'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'estKpi' => $kpi,
			'estItems' => $items,
		]);

		return $this->render('estoque');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'estoque') {
			return $this->estoque();
		}
		$allowed = ['novo', 'detalhe', 'precos', 'precificacao', 'estoque-log', 'historico-precos', 'import', 'pc-lista', 'pc-novo', 'inventario', 'inv-historico'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$set = [
			'title' => __('Produtos · {0}', ucfirst((string)$page)),
			'erpNavActive' => $page === 'precos' ? 'precos' : ($page === 'historico-precos' ? 'historico-precos' : 'produtos'),
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Produtos'), 'url' => ['controller' => 'ProdutosPrototype', 'action' => 'lista']],
				['label' => ucfirst((string)$page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		];

		if ($page === 'precos') {
			$set += $this->buildPrecosPayload();
			$this->set($set);

			return $this->render('precos');
		}

		if ($page === 'pc-lista' || $page === 'pc-novo') {
			$this->set($set);

			return $this->render('pc_placeholder');
		}

		if ($page === 'inventario' || $page === 'inv-historico') {
			$this->set($set);

			return $this->render('inv_placeholder');
		}

		if ($page === 'import') {
			$this->set($set);

			return $this->render('import');
		}

		$this->set($set);

		return $this->render('placeholder');
	}

	/**
	 * Tabela de preços agrupada por tipo, com margem (custo via vllocdiario apenas como referência).
	 *
	 * @return array<string,mixed>
	 */
	protected function buildPrecosPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$rows = [];
		try {
			$q = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa, 'Produtos.ativo' => 1])
				->order(['Produtos.tipo' => 'ASC', 'Produtos.descricao' => 'ASC'])
				->limit(200);
			if ($busca !== '') {
				$q->where(['OR' => [
					'Produtos.descricao ILIKE' => '%' . $busca . '%',
					'Produtos.codigo ILIKE' => '%' . $busca . '%',
				]]);
			}
			foreach ($q->all() as $p) {
				$rows[] = [
					'id' => (int)$p->get('id'),
					'codigo' => (string)$p->get('codigo'),
					'descricao' => (string)$p->get('descricao'),
					'tipo' => (string)$p->get('tipo'),
					'unidade' => (string)$p->get('unidade'),
					'venda' => (float)$p->get('vlunitario'),
					'loc_diaria' => (float)$p->get('vllocdiario'),
					'loc_semanal' => (float)$p->get('vllocsemanal'),
					'loc_mensal' => (float)$p->get('vllocmensal'),
				];
			}
		} catch (\Throwable $e) {
		}

		$kpi = ['total' => count($rows), 'media' => 0.0, 'min' => 0.0, 'max' => 0.0];
		if ($rows !== []) {
			$valores = array_filter(array_column($rows, 'venda'), static function ($v) { return $v > 0; });
			if ($valores !== []) {
				$kpi['media'] = array_sum($valores) / count($valores);
				$kpi['min'] = min($valores);
				$kpi['max'] = max($valores);
			}
		}

		return [
			'precosItems' => $rows,
			'precosKpi' => $kpi,
			'precosFiltro' => $busca,
		];
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($tbl->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
				$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
				if ($nome === '') {
					continue;
				}
				$out[] = [
					'id' => (int)$e->get('id'),
					'nome' => $nome,
					'cnpj' => (string)($e->get('cnpj') ?? ''),
					'current' => (int)$e->get('id') === $active,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}
}
