<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Fornecedores — protótipo (mockup pg-fornecedores, pg-fornecedor-novo, pg-fornecedor-360).
 *
 * Fornecedores no portal = clientes PJ (`clientes.tipo` jurídica), usados em CP e Fiscal.
 * Bridge para Clientes/add, visao360 e edit.
 */
class FornecedoresPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
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
	}

	/**
	 * pg-fornecedores — lista de clientes PJ (fornecedores no escopo financeiro).
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$filtroStatus = (string)$this->request->getQuery('status', '');
		$pjTipo = defined('C_ClientesTipoJuridica') ? (int)C_ClientesTipoJuridica : 2;
		$where = [
			'Clientes.idempresa' => $empresa,
			'Clientes.tipo' => $pjTipo,
		];
		if ($busca !== '') {
			$where['OR'] = [
				'Clientes.razaosocial ILIKE' => '%' . $busca . '%',
				'Clientes.nomefantasia ILIKE' => '%' . $busca . '%',
				'Clientes.cnpj ILIKE' => '%' . $busca . '%',
				'Clientes.email ILIKE' => '%' . $busca . '%',
				'Clientes.fone ILIKE' => '%' . $busca . '%',
			];
		}
		if ($filtroStatus === 'ativo') {
			$where['Clientes.inativo'] = 0;
		} elseif ($filtroStatus === 'inativo') {
			$where['Clientes.inativo'] = 1;
		}

		$rows = [];
		try {
			$rows = $this->Clientes->find()
				->where($where)
				->order(['Clientes.razaosocial' => 'ASC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$counts = ['total' => 0, 'ativos' => 0, 'inativos' => 0];
		$items = [];
		foreach ($rows as $r) {
			$counts['total']++;
			$inativo = (int)$r->get('inativo') === 1;
			if ($inativo) {
				$counts['inativos']++;
			} else {
				$counts['ativos']++;
			}
			$items[] = [
				'id' => (int)$r->get('id'),
				'nome' => (string)($r->get('razaosocial') ?? $r->get('nome') ?? ''),
				'fantasia' => (string)($r->get('nomefantasia') ?? ''),
				'cnpj' => (string)($r->get('cnpj') ?? ''),
				'email' => (string)($r->get('email') ?? ''),
				'fone' => (string)($r->get('fone') ?? $r->get('fone2') ?? ''),
				'inativo' => $inativo,
			];
		}

		$this->set([
			'title' => __('Fornecedores'),
			'erpNavActive' => 'fornecedores',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Fornecedores'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'fornCounts' => $counts,
			'fornItems' => $items,
			'fornFiltros' => ['q' => $busca, 'status' => $filtroStatus],
		]);

		return $this->render('lista');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'novo') {
			return $this->redirect(['controller' => 'Clientes', 'action' => 'add']);
		}
		$allowed = ['novo', '360'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}
		$fornId = (int)$this->request->getQuery('id', 0);
		if ($page === '360' && $fornId > 0) {
			return $this->redirect(['controller' => 'Clientes', 'action' => 'visao360', $fornId]);
		}

		$this->set([
			'title' => __('Fornecedores · {0}', ucfirst((string)$page)),
			'erpNavActive' => 'fornecedores',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Fornecedores'), 'url' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista']],
				['label' => ucfirst((string)$page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		]);

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
