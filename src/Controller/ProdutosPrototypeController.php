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

		if ($page === 'precificacao') {
			$set += $this->buildPrecificacaoPayload();
			$this->set($set);

			return $this->render('precificacao');
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
	 * Atualiza preço unitário de um produto (edição inline a partir da tabela de preços).
	 * Aceita POST com produto_id + vlunitario; faz update direto via ORM.
	 */
	public function precoSave() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$id = (int)$this->request->getData('produto_id');
		$novo = (float)str_replace(',', '.', (string)$this->request->getData('vlunitario'));
		if ($id <= 0 || $novo < 0) {
			$this->Flash->error(__('Dados inválidos.'));

			return $this->redirect(['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos']);
		}
		try {
			$prod = $this->Produtos->find()
				->where(['Produtos.id' => $id, 'Produtos.idempresa' => $empresa])
				->first();
			if ($prod === null) {
				$this->Flash->error(__('Produto fora do seu escopo.'));

				return $this->redirect(['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos']);
			}
			$prod->set('vlunitario', $novo);
			if ($this->Produtos->save($prod)) {
				$this->Flash->success(__('Preço do produto {0} atualizado para {1}.', (string)$prod->get('codigo'), 'R$ ' . number_format($novo, 2, ',', '.')));
			} else {
				$this->Flash->error(__('Falha ao salvar.'));
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Erro: {0}', $e->getMessage()));
		}

		$q = trim((string)$this->request->getData('q'));

		return $this->redirect(['controller' => 'ProdutosPrototype', 'action' => 'view', 'precos', '?' => $q !== '' ? ['q' => $q] : []]);
	}

	/**
	 * Centro de Cálculo — simula impacto de margem/desconto a partir de um produto base.
	 *
	 * @return array<string,mixed>
	 */
	protected function buildPrecificacaoPayload(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$query = $this->request->getQueryParams();
		$prodId = (int)($query['produto_id'] ?? 0);
		$margem = (float)str_replace(',', '.', (string)($query['margem'] ?? '30'));
		$descontoMax = (float)str_replace(',', '.', (string)($query['desconto'] ?? '10'));
		$icms = (float)str_replace(',', '.', (string)($query['icms'] ?? '18'));
		$pisCofins = (float)str_replace(',', '.', (string)($query['pis_cofins'] ?? '9.25'));
		if ($margem < 0 || $margem > 500) {
			$margem = 30;
		}
		if ($descontoMax < 0 || $descontoMax > 90) {
			$descontoMax = 10;
		}
		if ($icms < 0 || $icms > 35) {
			$icms = 18;
		}
		if ($pisCofins < 0 || $pisCofins > 20) {
			$pisCofins = 9.25;
		}

		$produto = null;
		$opcoes = [];
		try {
			foreach ($this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa, 'Produtos.ativo' => 1, 'Produtos.tipo' => 'prod'])
				->order(['Produtos.descricao' => 'ASC'])
				->limit(200)
				->all() as $p) {
				$opcoes[(int)$p->get('id')] = trim(sprintf('%s · %s', (string)$p->get('codigo'), (string)$p->get('descricao')));
				if ($prodId > 0 && (int)$p->get('id') === $prodId) {
					$produto = [
						'id' => (int)$p->get('id'),
						'codigo' => (string)$p->get('codigo'),
						'descricao' => (string)$p->get('descricao'),
						'venda' => (float)$p->get('vlunitario'),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		$resultado = null;
		if ($produto !== null) {
			$custoEstimado = round((float)$produto['venda'] / (1 + ($margem / 100)), 2);
			$precoSugerido = $custoEstimado * (1 + ($margem / 100));
			$precoMinDesc = $precoSugerido * (1 - ($descontoMax / 100));
			$margemLiquida = $custoEstimado > 0
				? (($precoMinDesc - $custoEstimado) / $precoMinDesc) * 100
				: 0;
			$valorImpostos = $precoSugerido * (($icms + $pisCofins) / 100);
			$resultado = [
				'custo_estimado' => $custoEstimado,
				'preco_sugerido' => round($precoSugerido, 2),
				'preco_minimo_com_desconto' => round($precoMinDesc, 2),
				'margem_liquida_pct' => round($margemLiquida, 2),
				'valor_impostos' => round($valorImpostos, 2),
				'preco_total_com_impostos' => round($precoSugerido + $valorImpostos, 2),
			];
		}

		return [
			'precificOpcoes' => $opcoes,
			'precificProduto' => $produto,
			'precificFiltro' => [
				'produto_id' => $prodId,
				'margem' => $margem,
				'desconto' => $descontoMax,
				'icms' => $icms,
				'pis_cofins' => $pisCofins,
			],
			'precificResultado' => $resultado,
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
