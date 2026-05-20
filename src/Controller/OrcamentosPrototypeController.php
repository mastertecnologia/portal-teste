<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Orçamentos — protótipo (telas do mockup pgm_erp_completo.html, prefixo `pg-`).
 *
 * Lado-a-lado com OrcamentosController (legado). Rotas em /orcamentos-prototype/*.
 * Dados reais via ORM, somente leitura nesta fase 2; ações de escrita
 * permanecem nas rotas legadas até validação.
 */
class OrcamentosPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Orcamentos');
		$this->loadModel('Clientes');
		$this->loadModel('Users');
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
	 * pg-lista — listagem de orçamentos com KPIs + tabela.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');

		$base = $this->Orcamentos->find()
			->contain(['Clientes', 'Users'])
			->where(['Orcamentos.idempresa' => $empresa])
			->order(['Orcamentos.modified' => 'DESC'])
			->limit(100);

		$rows = [];
		try {
			$rows = $base->all()->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$stPend = defined('C_OrcamentoStatusPendente') ? (int)C_OrcamentoStatusPendente : 0;
		$stEnv = defined('C_OrcamentoStatusEnviado') ? (int)C_OrcamentoStatusEnviado : 1;
		$stApr = defined('C_OrcamentoStatusAprovado') ? (int)C_OrcamentoStatusAprovado : 2;
		$stRec = defined('C_OrcamentoStatusRecusado') ? (int)C_OrcamentoStatusRecusado : 3;

		$counts = ['pendente' => 0, 'enviado' => 0, 'aprovado' => 0, 'recusado' => 0, 'total' => 0];
		$totalValor = 0.0;
		$items = [];
		foreach ($rows as $o) {
			$st = (int)$o->get('status');
			$counts['total']++;
			if ($st === $stPend) {
				$counts['pendente']++;
			} elseif ($st === $stEnv) {
				$counts['enviado']++;
			} elseif ($st === $stApr) {
				$counts['aprovado']++;
			} elseif ($st === $stRec) {
				$counts['recusado']++;
			}
			$valor = (float)($o->get('valortotal') ?? $o->get('valor') ?? 0);
			$totalValor += $valor;
			$cl = $o->cliente ?? null;
			$autor = $o->user ?? null;
			$items[] = [
				'id' => (int)$o->get('id'),
				'cliente' => $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—',
				'autor' => $autor ? trim((string)($autor->get('name') ?? $autor->get('username'))) : '—',
				'valor' => $valor,
				'status' => $st,
				'modified' => $o->get('modified') ?? $o->get('created'),
				'observacao' => (string)($o->get('observacao') ?? ''),
			];
		}

		$this->set([
			'title' => __('Orçamentos'),
			'erpNavActive' => 'orc-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Comercial')],
				['label' => __('Orçamentos'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'orcCounts' => $counts,
			'orcTotalValor' => $totalValor,
			'orcItems' => $items,
			'orcStatusLabels' => [
				$stPend => __('Pendente'),
				$stEnv => __('Enviado'),
				$stApr => __('Aprovado'),
				$stRec => __('Recusado'),
			],
		]);
	}

	/**
	 * Detalhe de um orçamento (mockup pg-revisao com dados reais).
	 *
	 * @param string|int $id
	 */
	public function detalhe($id) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new NotFoundException(__('Orçamento inválido.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$orc = null;
		$itens = [];
		try {
			$orc = $this->Orcamentos->find()
				->contain(['Clientes', 'Users'])
				->where(['Orcamentos.id' => $id, 'Orcamentos.idempresa' => $empresa])
				->first();
		} catch (\Throwable $e) {
		}
		if ($orc === null) {
			throw new NotFoundException(__('Orçamento não encontrado ou fora do seu escopo.'));
		}
		try {
			$tblItens = $this->loadModel('Orcamentositens');
			$itens = $tblItens->find()
				->where(['Orcamentositens.idorcamento' => $id])
				->order(['Orcamentositens.id' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$totalSub = 0.0;
		$totalDesc = 0.0;
		$linhas = [];
		foreach ($itens as $it) {
			$qtd = (float)($it->get('quantidade') ?? 1);
			$vu = (float)($it->get('valorunitario') ?? 0);
			$desc = (float)($it->get('valordesconto') ?? 0);
			$subtotal = $qtd * $vu - $desc;
			$totalSub += $qtd * $vu;
			$totalDesc += $desc;
			$linhas[] = [
				'codigo' => (string)($it->get('codproduto') ?? ''),
				'descricao' => (string)($it->get('descricao') ?? ''),
				'unidade' => (string)($it->get('unidade') ?? ''),
				'qtd' => $qtd,
				'vlr' => $vu,
				'desconto' => $desc,
				'subtotal' => $subtotal,
			];
		}

		$cliente = $orc->cliente ?? null;
		$autor = $orc->user ?? null;
		$this->set([
			'title' => __('Orçamento #{0}', $id),
			'erpNavActive' => 'orc-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Comercial')],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => '#' . $id, 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'orc' => [
				'id' => (int)$orc->get('id'),
				'cliente' => $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—',
				'cliente_cnpj' => $cliente ? (string)($cliente->get('cnpj') ?? $cliente->get('cpf') ?? '') : '',
				'autor' => $autor ? trim((string)($autor->get('name') ?? $autor->get('username'))) : '—',
				'status' => (int)$orc->get('status'),
				'created' => $orc->get('created'),
				'modified' => $orc->get('modified'),
				'observacao' => (string)($orc->get('observacao') ?? ''),
				'valortotal' => (float)($orc->get('valortotal') ?? $orc->get('valor') ?? 0),
			],
			'orcLinhas' => $linhas,
			'orcTotalSub' => $totalSub,
			'orcTotalDesc' => $totalDesc,
		]);

		return $this->render('detalhe');
	}

	/**
	 * Telas wizard (pg-novo|revisao|print|esign|sucesso) e faturamento/cobranca.
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$wizard = ['novo' => 1, 'revisao' => 2, 'print' => 3, 'esign' => 4, 'sucesso' => 5];
		$allowed = array_merge(array_keys($wizard), ['faturamento', 'cobranca']);
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$steps = [
			['label' => __('Novo'), 'state' => 'pending'],
			['label' => __('Revisão'), 'state' => 'pending'],
			['label' => __('Impressão'), 'state' => 'pending'],
			['label' => __('Assinatura'), 'state' => 'pending'],
			['label' => __('Sucesso'), 'state' => 'pending'],
		];
		if (isset($wizard[$page])) {
			$current = (int)$wizard[$page] - 1;
			for ($i = 0; $i < $current; $i++) {
				$steps[$i]['state'] = 'done';
			}
			$steps[$current]['state'] = 'active';
		}

		$this->set([
			'title' => __('Orçamentos · {0}', ucfirst($page)),
			'erpNavActive' => 'orc-' . $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Comercial')],
				['label' => __('Orçamentos'), 'url' => ['controller' => 'OrcamentosPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
			'wizardSteps' => $steps,
			'wizardCurrent' => $page,
		]);

		$dedicated = ['novo', 'revisao', 'print', 'esign', 'sucesso'];
		if (in_array($page, $dedicated, true)) {
			if ($page === 'novo' || $page === 'revisao') {
				$this->set('orcCatalogo', $this->buildCatalogoProdutos());
				$this->set('orcClientesOptions', $this->buildClientesOptions());
			}

			return $this->render('wizard_' . $page);
		}

		return $this->render('placeholder');
	}

	/**
	 * Catálogo (até 40 produtos ativos) para o wizard de orçamento.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildCatalogoProdutos(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$out = [];
		try {
			$q = $this->Produtos->find()
				->where(['Produtos.idempresa' => $empresa, 'Produtos.ativo' => 1])
				->order(['Produtos.descricao' => 'ASC'])
				->limit(40);
			if ($busca !== '') {
				$q->where(['OR' => [
					'Produtos.descricao ILIKE' => '%' . $busca . '%',
					'Produtos.codigo ILIKE' => '%' . $busca . '%',
				]]);
			}
			foreach ($q->all() as $p) {
				$out[] = [
					'id' => (int)$p->get('id'),
					'codigo' => (string)$p->get('codigo'),
					'descricao' => (string)$p->get('descricao'),
					'tipo' => (string)$p->get('tipo'),
					'unidade' => (string)$p->get('unidade'),
					'preco' => (float)$p->get('vlunitario'),
					'estoque' => (float)$p->get('estoque_atual'),
				];
			}
		} catch (\Throwable $e) {
		}

		return $out;
	}

	/**
	 * @return array<int,string> id => nome
	 */
	protected function buildClientesOptions(): array {
		$empresa = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$rows = $this->Clientes->find()
				->where(['Clientes.idempresa' => $empresa, 'Clientes.inativo' => 0])
				->order(['Clientes.nome' => 'ASC'])
				->limit(200)
				->all();
			foreach ($rows as $c) {
				$nome = (int)$c->get('tipo') === 2
					? (string)($c->get('razaosocial') ?? $c->get('nome'))
					: (string)$c->get('nome');
				$out[(int)$c->get('id')] = $nome;
			}
		} catch (\Throwable $e) {
		}

		return $out;
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
		$userId = (int)$this->Auth->user('id');
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			$cols = $tbl->getSchema()->columns();
			$q = $tbl->find()->order(['id' => 'ASC'])->limit(20);
			foreach ($q->all() as $e) {
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
