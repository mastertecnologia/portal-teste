<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Fornecedores — protótipo (mockup pg-fornecedores, pg-fornecedor-novo, pg-fornecedor-360).
 *
 * Observação: o portal não tem tabela `fornecedores` dedicada hoje — fornecedores
 * são tratados via `clientes` (com `tipo` específico) ou via cadastros fiscais.
 * Este controller serve como entrada premium e mostra placeholder informativo até
 * o módulo dedicado existir.
 */
class FornecedoresPrototypeController extends AppController {

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

	public function lista() {
		$this->set([
			'title' => __('Fornecedores'),
			'erpNavActive' => 'fornecedores',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Fornecedores'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => 'lista',
		]);

		return $this->render('placeholder');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'lista') {
		if ($page === 'lista') {
			return $this->lista();
		}
		$allowed = ['novo', '360'];
		if (!in_array($page, $allowed, true)) {
			throw new NotFoundException(__('Tela do protótipo não encontrada.'));
		}

		$this->set([
			'title' => __('Fornecedores · {0}', ucfirst($page)),
			'erpNavActive' => 'fornecedores',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Cadastros')],
				['label' => __('Fornecedores'), 'url' => ['controller' => 'FornecedoresPrototype', 'action' => 'lista']],
				['label' => ucfirst($page), 'cur' => true],
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
