<?php
declare(strict_types=1);

namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

use App\Service\Lic\LicPrototypeDataService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

/**
 * Portal do cliente — licenciamento (/cliente/licencas/*).
 */
class PortalLicencasController extends AppController {

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * @return array{0:int,1:int}
	 */
	protected function clienteContext(): array {
		return [
			(int)$this->Auth->user('idcliente'),
			(int)$this->Auth->user('idempresa'),
		];
	}

	protected function licService(): LicPrototypeDataService {
		list($idcliente, $idempresa) = $this->clienteContext();
		if ($idcliente <= 0 || $idempresa <= 0) {
			throw new ForbiddenException();
		}

		return new LicPrototypeDataService($idempresa, $idcliente);
	}

	public function index() {
		$this->set('title', __('Minhas licenças'));
		list($idcliente,) = $this->clienteContext();
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set('licKpi', []);

			return;
		}
		$svc = $this->licService();
		$this->set([
			'licKpi' => $svc->portalDashboardKpis(),
			'licMigrationHint' => !$svc->tablesAvailable(),
		]);
	}

	public function licencas() {
		$this->set('title', __('Licenças ativas'));
		$svc = $this->licService();
		$this->set([
			'licItems' => $svc->listLicencas(['status' => 'ativa']),
			'licMigrationHint' => !$svc->tablesAvailable(),
		]);
	}

	public function cofre() {
		$this->set('title', __('Cofre (somente leitura)'));
		$svc = $this->licService();
		$this->set([
			'licCofreItens' => $svc->listCofreItens(),
			'licMigrationHint' => !$svc->tablesAvailable(),
		]);
	}

	public function solicitar() {
		$this->set('title', __('Solicitar licença'));
		$svc = $this->licService();
		$this->set('licMigrationHint', !$svc->tablesAvailable());
	}

	public function salvarSolicitacao() {
		$this->request->allowMethod(['post']);
		list($idcliente, $idempresa) = $this->clienteContext();
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado.'));
			return $this->redirect(['action' => 'solicitar']);
		}
		$svc = new LicPrototypeDataService($idempresa, $idcliente);
		if (!$svc->tablesAvailable()) {
			$this->Flash->error(__('Módulo indisponível.'));
			return $this->redirect(['action' => 'index']);
		}
		$data = $this->request->getData();
		$data['idcliente'] = $idcliente;
		$data['iduser'] = (int)$this->Auth->user('id');
		$data['tipo'] = 'nova_licenca';
		$result = $svc->createSolicitacao($data);
		if (empty($result['ok'])) {
			$this->Flash->error(__('Não foi possível enviar a solicitação.'));
			return $this->redirect(['action' => 'solicitar']);
		}
		$this->Flash->success(__('Solicitação enviada. Acompanhe o status.'));
		return $this->redirect(['action' => 'acompanhar', $result['id']]);
	}

	/**
	 * @param int|null $id
	 */
	public function acompanhar($id = null) {
		$sid = (int)($id ?? 0);
		if ($sid <= 0) {
			return $this->redirect(['action' => 'index']);
		}
		$svc = $this->licService();
		$row = $svc->getSolicitacao($sid);
		if ($row === null) {
			throw new NotFoundException();
		}
		$this->set('title', __('Solicitação #{0}', $sid));
		$this->set('licSolicitacao', $row);
	}

}
