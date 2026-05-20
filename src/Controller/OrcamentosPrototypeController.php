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
			return $this->render('wizard_' . $page);
		}

		return $this->render('placeholder');
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
