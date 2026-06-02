<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Controller\Traits\FornecedoresListaTrait;
use App\Utility\ClientesPapelCadastro;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Fornecedores — protótipo (mockup pg-fornecedores, pg-fornecedor-novo, pg-fornecedor-360).
 */
class FornecedoresPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;
	use FornecedoresListaTrait;

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
	 * pg-fornecedores — lista alinhada ao mockup com dados reais.
	 */
	public function lista() {
		$empresa = (int)$this->Auth->user('idempresa');
		$busca = trim((string)$this->request->getQuery('q', ''));
		$filtroStatus = (string)$this->request->getQuery('status', '');
		$filtroCategoria = (string)$this->request->getQuery('categoria', '');
		$filtroPj = $this->request->getQuery('pj') !== '0';
		$filtroPf = $this->request->getQuery('pf') === '1';

		$data = $this->buildFornecedoresListaData($empresa, $busca, $filtroStatus, $filtroCategoria, $filtroPj, $filtroPf);

		$this->set([
			'title' => __('Fornecedores'),
			'erpNavActive' => 'fornecedores',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Fornecedores'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'fornData' => $data,
			'fornFiltros' => [
				'q' => $busca,
				'status' => $filtroStatus,
				'categoria' => $filtroCategoria,
				'pj' => $filtroPj,
				'pf' => $filtroPf,
			],
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
			return $this->redirect([
				'controller' => 'Clientes',
				'action' => 'add',
				'?' => ['fornecedor' => '1'],
			]);
		}
		$allowed = ['360'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}
		$fornId = (int)$this->request->getQuery('id', 0);
		if ($page === '360' && $fornId > 0) {
			return $this->redirect([
				'controller' => 'Clientes',
				'action' => 'edit',
				$fornId,
				'?' => ['fornecedor' => '1'],
			]);
		}

		throw new NotFoundException(__('Tela do protótipo não encontrada.'));
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
