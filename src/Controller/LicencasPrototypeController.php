<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Service\Lic\LicPrototypeDataService;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Licenciamento — protótipo (pg-lic-*).
 */
class LicencasPrototypeController extends AppController {

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
		]);

		return $this->render('dashboard');
	}

	/**
	 * pg-lic-licencas — listagem.
	 */
	public function licencas() {
		return $this->renderLicencasList();
	}

	/**
	 * GET /licencas-prototype/licenca-detalhe/:id
	 *
	 * @param int|null $id
	 */
	public function licencaDetalhe($id = null) {
		$licId = (int)($id ?? $this->request->getQuery('id', 0));
		if ($licId <= 0) {
			return $this->redirect(['action' => 'licencas']);
		}
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new LicPrototypeDataService($empresa);
		$row = $svc->getLicenca($licId);
		if ($row === null) {
			$this->Flash->error(__('Licença não encontrada.'));

			return $this->redirect(['action' => 'licencas']);
		}
		$this->set([
			'title' => __('Licença') . ' · ' . $row['codigo'],
			'erpNavActive' => 'lic-licencas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Licenciamento'), 'url' => ['action' => 'dashboard']],
				['label' => __('Licenças'), 'url' => ['action' => 'licencas']],
				['label' => $row['codigo'], 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'lic' => $row,
			'licMigrationHint' => !$svc->tablesAvailable(),
		]);

		return $this->render('licenca_detalhe');
	}

	public function salvarWizard() {
		$this->request->allowMethod(['post']);
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new LicPrototypeDataService($empresa);
		if (!$svc->tablesAvailable()) {
			$this->Flash->error(__('Execute a migration do módulo Licenciamento.'));

			return $this->redirect(['action' => 'dashboard']);
		}
		$step = (int)$this->request->getData('wizard_step', 1);
		$licId = (int)$this->request->getData('id', 0);
		$data = $this->request->getData();
		$data['iduser'] = (int)$this->Auth->user('id');
		$result = $svc->saveWizardStep($step, $data, $licId > 0 ? $licId : null);
		if (empty($result['ok'])) {
			$this->Flash->error(__('Não foi possível salvar. Verifique os campos.'));

			return $this->redirect($this->wizardRedirectTarget($step, $licId));
		}
		$newId = (int)($result['id'] ?? $licId);
		if ($step >= 4) {
			$licRow = $svc->getLicenca($newId);
			$cod = is_array($licRow) ? (string)($licRow['codigo'] ?? '') : '';
			$this->Flash->success(__('Licença {0} registrada.', $cod !== '' ? $cod : '#' . $newId));

			return $this->redirect(['action' => 'licencaDetalhe', $newId]);
		}
		$this->Flash->success(__('Rascunho salvo.'));

		return $this->redirect($this->wizardRedirectTarget($step + 1, $newId));
	}

	/**
	 * @param string $page
	 */
	public function view($page = 'dashboard') {
		if ($page === 'dashboard') {
			return $this->dashboard();
		}
		if ($page === 'licencas') {
			return $this->renderLicencasList();
		}
		if ($page === 'licenca-detalhe') {
			return $this->licencaDetalhe((int)$this->request->getQuery('id', 0));
		}
		if ($page === 'fornecedores') {
			return $this->redirect(['controller' => 'FornecedoresPrototype', 'action' => 'lista']);
		}
		if ($page === 'fornecedor-novo') {
			return $this->redirect(['controller' => 'FornecedoresPrototype', 'action' => 'view', 'novo']);
		}
		if ($page === 'empresas') {
			return $this->redirect(['controller' => 'ClientesPrototype', 'action' => 'lista']);
		}
		if ($page === 'empresa-nova') {
			return $this->redirect(['controller' => 'ClientesPrototype', 'action' => 'view', 'novo']);
		}

		$wizard = ['nova' => 1, 'nova-2' => 2, 'nova-3' => 3, 'nova-4' => 4];
		if (isset($wizard[$page])) {
			return $this->renderWizard($page, (int)$wizard[$page]);
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
			'licMigrationHint' => !(new LicPrototypeDataService((int)$this->Auth->user('idempresa')))->tablesAvailable(),
		]);

		return $this->render('placeholder');
	}

	protected function renderLicencasList() {
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new LicPrototypeDataService($empresa);
		$filters = [
			'status' => $this->request->getQuery('status'),
			'cliente' => $this->request->getQuery('cliente'),
		];
		$this->set([
			'title' => __('Licenças'),
			'erpNavActive' => 'lic-licencas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Licenciamento'), 'url' => ['action' => 'dashboard']],
				['label' => __('Licenças'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'licItems' => $svc->listLicencas($filters),
			'licClientes' => $svc->listClientesOptions(),
			'licFilters' => $filters,
			'licMigrationHint' => !$svc->tablesAvailable(),
		]);

		return $this->render('licencas');
	}

	protected function renderWizard(string $page, int $stepNum) {
		$empresa = (int)$this->Auth->user('idempresa');
		$svc = new LicPrototypeDataService($empresa);
		if (!$svc->tablesAvailable()) {
			$this->Flash->error(__('Migration lic_* pendente.'));

			return $this->redirect(['action' => 'dashboard']);
		}
		$licId = (int)$this->request->getQuery('id', 0);
		if ($stepNum > 1 && $licId <= 0) {
			return $this->redirect(['action' => 'view', 'nova']);
		}
		$lic = $licId > 0 ? $svc->getLicenca($licId) : null;
		if ($stepNum > 1 && $lic === null) {
			$this->Flash->error(__('Licença inválida.'));

			return $this->redirect(['action' => 'view', 'nova']);
		}
		$steps = $this->wizardSteps($stepNum);
		$this->set([
			'title' => __('Nova licença') . ' · ' . $stepNum . '/4',
			'erpNavActive' => 'lic-licencas',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Licenciamento'), 'url' => ['action' => 'dashboard']],
				['label' => __('Licenças'), 'url' => ['action' => 'licencas']],
				['label' => __('Nova licença'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'wizardSteps' => $steps,
			'wizardPage' => $page,
			'wizardStepNum' => $stepNum,
			'lic' => $lic,
			'licId' => $licId,
			'licCatalogo' => $svc->listCatalogo(),
			'licClientes' => $svc->listClientesOptions(),
		]);
		$tpl = 'wizard_' . str_replace('-', '_', $page);

		return $this->render($tpl);
	}

	/**
	 * @return array<int,array{label:string,state:string}>
	 */
	protected function wizardSteps(int $current): array {
		$labels = [
			__('Cliente & produto'),
			__('Quantidade & vigência'),
			__('Assentos'),
			__('Finalizar'),
		];
		$out = [];
		foreach ($labels as $i => $label) {
			$state = 'pending';
			if ($i + 1 < $current) {
				$state = 'done';
			} elseif ($i + 1 === $current) {
				$state = 'active';
			}
			$out[] = ['label' => $label, 'state' => $state];
		}

		return $out;
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function wizardRedirectTarget(int $step, int $licId): array {
		$map = [1 => 'nova', 2 => 'nova-2', 3 => 'nova-3', 4 => 'nova-4'];
		$page = $map[$step] ?? 'nova';

		return ['action' => 'view', $page, '?' => ['id' => $licId]];
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	protected function buildPages(): array {
		return [
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
