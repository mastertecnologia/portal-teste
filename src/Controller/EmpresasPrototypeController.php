<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Empresas (multi-empresa) — protótipo (mockup pg-empresas, pg-empresa-nova, pg-empresa).
 *
 * Lado-a-lado com EmpresasController + EmpresasusersController. Somente leitura
 * para listagem; troca de empresa ativa continua via EmpresasusersController::switchempresa.
 */
class EmpresasPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas');
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
	 * pg-empresas — listagem multi-empresa.
	 */
	public function lista() {
		$active = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Empresas->find()
				->order(['Empresas.id' => 'ASC'])
				->limit(100)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$items = [];
		$ativas = 0;
		$inativas = 0;
		foreach ($rows as $e) {
			$inativa = (int)$e->get('inativa') === 1;
			if ($inativa) {
				$inativas++;
			} else {
				$ativas++;
			}
			$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
			$items[] = [
				'id' => (int)$e->get('id'),
				'nome' => $nome,
				'fantasia' => (string)($e->get('nomefantasia') ?? ''),
				'cnpj' => (string)($e->get('cnpj') ?? ''),
				'email' => (string)($e->get('email') ?? ''),
				'fone' => (string)($e->get('fone') ?? $e->get('fone2') ?? ''),
				'usuarios' => (int)($e->get('nrousuarios') ?? 0),
				'inativa' => $inativa,
				'current' => (int)$e->get('id') === $active,
				'erp' => (string)($e->get('urlerp') ?? ''),
			];
		}

		$this->set([
			'title' => __('Empresas'),
			'erpNavActive' => 'empresas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Empresas'), 'cur' => true],
			],
			'erpEmpresas' => $this->buildErpEmpresasFromItems($items),
			'empItems' => $items,
			'empKpi' => ['total' => count($items), 'ativas' => $ativas, 'inativas' => $inativas],
		]);
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		if ($page === 'nova') {
			return $this->redirect(['controller' => 'Empresas', 'action' => 'add']);
		}
		$empId = (int)$this->request->getQuery('id', 0);
		if ($empId > 0 && ($page === 'editar' || $page === 'detalhe')) {
			return $this->redirect(['controller' => 'Empresas', 'action' => 'edit', $empId]);
		}
		if ($page === 'editar' || $page === 'detalhe') {
			return $this->redirect(['action' => 'lista']);
		}
		$allowed = ['nova', 'editar', 'detalhe'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$this->set([
			'title' => __('Empresas · {0}', ucfirst($page)),
			'erpNavActive' => 'empresas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Empresas'), 'url' => ['controller' => 'EmpresasPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		]);

		if ($page === 'nova') {
			return $this->render('nova');
		}

		return $this->render('placeholder');
	}

	/**
	 * @param array<int,array<string,mixed>> $items
	 * @return array<int,array<string,mixed>>
	 */
	protected function buildErpEmpresasFromItems(array $items): array {
		$out = [];
		foreach ($items as $i) {
			if (!empty($i['inativa'])) {
				continue;
			}
			$out[] = [
				'id' => (int)$i['id'],
				'nome' => (string)$i['nome'],
				'cnpj' => (string)$i['cnpj'],
				'current' => !empty($i['current']),
			];
			if (count($out) >= 20) {
				break;
			}
		}

		return $out;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEmpresasParaTopbar(): array {
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($this->Empresas->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
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
