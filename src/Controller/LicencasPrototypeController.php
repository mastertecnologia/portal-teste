<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Service\Lic\LicPrototypeDataService;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Licenciamento — protótipo (telas pg-lic-* do mock pgm_erp_completo.html).
 *
 * Módulo novo; tabelas lic_* via migration LicModuleFoundation.
 * Fornecedores de software reutilizam clientes PJ (sem API Grid dedicada).
 */
class LicencasPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

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

	public function lista() {
		return $this->redirect(['action' => 'dashboard']);
	}

	public function dashboard() {
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new LicPrototypeDataService($empresa);
		$this->set([
			'title' => __('Licenciamento · Painel'),
			'erpNavActive' => 'lic-dashboard',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Licenciamento'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'licKpi' => $svc->dashboardKpis(),
			'licMigrationHint' => !$svc->tablesAvailable(),
			'licPages' => $this->buildPages(),
		]);

		return $this->render('dashboard');
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'dashboard') {
		if ($page === 'dashboard') {
			return $this->dashboard();
		}
		if ($page === 'fornecedores' || $page === 'fornecedor-novo') {
			if ($page === 'fornecedores') {
				return $this->redirect(['controller' => 'FornecedoresPrototype', 'action' => 'lista']);
			}

			return $this->redirect(['controller' => 'FornecedoresPrototype', 'action' => 'view', 'novo']);
		}
		if ($page === 'empresas' || $page === 'empresa-nova') {
			if ($page === 'empresas') {
				return $this->redirect(['controller' => 'ClientesPrototype', 'action' => 'lista']);
			}

			return $this->redirect(['controller' => 'ClientesPrototype', 'action' => 'view', 'novo']);
		}

		$pages = $this->buildPages();
		if (!isset($pages[$page])) {
			throw new NotFoundException(__('Tela de licenciamento não encontrada.'));
		}
		$meta = $pages[$page];
		$this->set([
			'title' => __('Licenciamento') . ' · ' . $meta['title'],
			'erpNavActive' => $meta['nav'] ?? 'lic-dashboard',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Licenciamento'), 'url' => ['action' => 'dashboard']],
				['label' => $meta['title'], 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
			'pageMeta' => $meta,
			'licMigrationHint' => !(new LicPrototypeDataService((int)$this->Auth->user('idempresa')))->tablesAvailable(),
		]);

		return $this->render('placeholder');
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function buildPages(): array {
		$tiles = [
			'dashboard' => ['title' => __('Painel'), 'nav' => 'lic-dashboard'],
			'empresas' => ['title' => __('Empresas-cliente'), 'nav' => 'lic-empresas'],
			'licencas' => ['title' => __('Licenças'), 'nav' => 'lic-licencas'],
			'catalogo' => ['title' => __('Catálogo'), 'nav' => 'lic-catalogo'],
			'renovacoes' => ['title' => __('Renovações'), 'nav' => 'lic-renovacoes'],
			'dispositivos' => ['title' => __('Dispositivos'), 'nav' => 'lic-dispositivos'],
			'cofre' => ['title' => __('Cofre'), 'nav' => 'lic-cofre'],
			'solicitacoes' => ['title' => __('Solicitações'), 'nav' => 'lic-solicitacoes'],
			'calendario' => ['title' => __('Calendário'), 'nav' => 'lic-calendario'],
			'auditoria' => ['title' => __('Auditoria'), 'nav' => 'lic-auditoria'],
			'relatorios' => ['title' => __('Relatórios'), 'nav' => 'lic-relatorios'],
			'config' => ['title' => __('Configurações'), 'nav' => 'lic-config'],
		];
		$wizards = ['nova', 'nova-2', 'nova-3', 'nova-4'];
		foreach ($wizards as $w) {
			$tiles[$w] = ['title' => __('Nova licença'), 'nav' => 'lic-licencas'];
		}

		return $tiles;
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
