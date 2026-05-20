<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Ordens de Serviço — protótipo (telas do mockup pgm_erp_completo.html, prefixo `pg-os-*`).
 *
 * Lado-a-lado com OrdensservicoController (legado). Rotas em /ordens-prototype/*.
 * Somente leitura nesta fase 2.
 */
class OrdensservicoPrototypeController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordensservico');
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
	 * pg-os-lista — listagem de OS com KPIs + tabela.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');

		$rows = [];
		try {
			$q = $this->Ordensservico->find()
				->contain(['Clientes'])
				->where(['Ordensservico.idempresa' => $empresa])
				->order(['Ordensservico.id' => 'DESC'])
				->limit(100);
			$rows = $q->all()->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['abertas' => 0, 'em_execucao' => 0, 'aguardando' => 0, 'concluidas' => 0, 'total' => 0];
		$totalValor = 0.0;
		$items = [];
		foreach ($rows as $os) {
			$counts['total']++;
			$st = strtolower(trim((string)($os->get('situacao') ?? '')));
			if (strpos($st, 'concl') !== false || strpos($st, 'fech') !== false) {
				$counts['concluidas']++;
			} elseif (strpos($st, 'execu') !== false || strpos($st, 'andam') !== false) {
				$counts['em_execucao']++;
			} elseif (strpos($st, 'aguard') !== false || strpos($st, 'aprov') !== false) {
				$counts['aguardando']++;
			} else {
				$counts['abertas']++;
			}
			$valor = (float)($os->get('valortotal') ?? $os->get('valor_total') ?? 0);
			$totalValor += $valor;
			$cl = $os->cliente ?? null;
			$items[] = [
				'id' => (int)$os->get('id'),
				'cliente' => $cl ? (string)($cl->get('razaosocial') ?? $cl->get('nome') ?? '') : '—',
				'descricao' => (string)($os->get('descricao') ?? $os->get('observacao') ?? ''),
				'valor' => $valor,
				'situacao' => (string)($os->get('situacao') ?? '—'),
				'data' => $os->get('dataabertura') ?? $os->get('created') ?? $os->get('data'),
			];
		}

		$this->set([
			'title' => __('Ordens de Serviço'),
			'erpNavActive' => 'os-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'osCounts' => $counts,
			'osTotalValor' => $totalValor,
			'osItems' => $items,
		]);
	}

	/**
	 * Detalhe de uma OS (carrega cliente + itens + horas).
	 *
	 * @param string|int $id
	 */
	public function detalhe($id) {
		$id = (int)$id;
		if ($id <= 0) {
			throw new NotFoundException(__('OS inválida.'));
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$os = null;
		try {
			$os = $this->Ordensservico->find()
				->contain(['Clientes'])
				->where(['Ordensservico.id' => $id, 'Ordensservico.idempresa' => $empresa])
				->first();
		} catch (\Throwable $e) {
		}
		if ($os === null) {
			throw new NotFoundException(__('OS não encontrada ou fora do seu escopo.'));
		}

		$itens = [];
		try {
			$tblIt = $this->loadModel('Itensordem');
			$itens = $tblIt->find()
				->where(['Itensordem.idordempk' => $id])
				->order(['Itensordem.id' => 'ASC'])
				->all()
				->toArray();
		} catch (\Throwable $e) {
		}

		$movs = [];
		try {
			$tblMov = $this->loadModel('Ordemmovs');
			$rows = $tblMov->find()
				->where(['Ordemmovs.idordem' => $id])
				->order(['Ordemmovs.data' => 'DESC'])
				->limit(20)
				->all();
			foreach ($rows as $m) {
				$movs[] = [
					'data' => $m->get('data'),
					'sitantiga' => (string)($m->get('sitantiga') ?? ''),
					'sitnova' => (string)($m->get('sitnova') ?? ''),
					'obs' => (string)($m->get('obs') ?? ''),
				];
			}
		} catch (\Throwable $e) {
		}

		$linhas = [];
		$totalItens = 0.0;
		foreach ($itens as $it) {
			$qtd = (float)($it->get('quantidade') ?? 1);
			$vu = (float)($it->get('valorunitario') ?? 0);
			$desc = (float)($it->get('valordesconto') ?? 0);
			$subtotal = (float)($it->get('valortotal') ?? ($qtd * $vu - $desc));
			$totalItens += $subtotal;
			$linhas[] = [
				'descricao' => (string)($it->get('descricao') ?? ''),
				'unidade' => (string)($it->get('unidade') ?? ''),
				'qtd' => $qtd,
				'vlr' => $vu,
				'desconto' => $desc,
				'subtotal' => $subtotal,
			];
		}

		$cliente = $os->cliente ?? null;
		$this->set([
			'title' => __('OS #{0}', $id),
			'erpNavActive' => 'os-lista',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista']],
				['label' => '#' . $id, 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'os' => [
				'id' => (int)$os->get('id'),
				'cliente' => $cliente ? (string)($cliente->get('razaosocial') ?? $cliente->get('nome') ?? '') : '—',
				'cliente_cnpj' => $cliente ? (string)($cliente->get('cnpj') ?? $cliente->get('cpf') ?? '') : '',
				'descricao' => (string)($os->get('descricao') ?? $os->get('observacao') ?? ''),
				'situacao' => (string)($os->get('situacao') ?? ''),
				'valortotal' => (float)($os->get('valortotal') ?? $os->get('valor_total') ?? $totalItens),
				'abertura' => $os->get('dataabertura') ?? $os->get('created') ?? $os->get('data'),
			],
			'osLinhas' => $linhas,
			'osTotalItens' => $totalItens,
			'osMovs' => $movs,
		]);

		return $this->render('detalhe');
	}

	/**
	 * Telas do wizard OS (abertura|execucao|aprovacao|conclusao|faturamento|cobranca|sucesso|kanban).
	 *
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$wizard = ['abertura' => 1, 'execucao' => 2, 'aprovacao' => 3, 'conclusao' => 4, 'sucesso' => 5];
		$allowed = array_merge(array_keys($wizard), ['faturamento', 'cobranca', 'kanban']);
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$steps = [
			['label' => __('Abertura'), 'state' => 'pending'],
			['label' => __('Execução'), 'state' => 'pending'],
			['label' => __('Aprovação cliente'), 'state' => 'pending'],
			['label' => __('Conclusão'), 'state' => 'pending'],
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
			'title' => __('OS · {0}', ucfirst($page)),
			'erpNavActive' => 'os-' . $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Operações')],
				['label' => __('Ordens de Serviço'), 'url' => ['controller' => 'OrdensservicoPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
			'wizardSteps' => $steps,
			'wizardCurrent' => $page,
		]);

		$dedicated = ['abertura', 'execucao', 'aprovacao', 'conclusao', 'sucesso'];
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
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
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
