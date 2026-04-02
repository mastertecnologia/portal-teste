<?php
namespace App\Controller;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

use App\Service\ContractRenewalService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

/**
 * Contratos no portal do cliente — URLs /cliente/contratos/* (spec MODULO_CONTRATOS_COMPLETO §5.3).
 * Não expor em views: valores internos, notas, IDs Autentique (ABAC).
 */
class PortalContratosController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('Contracts');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== C_RoleCliente) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * @return int[]
	 */
	protected function _clienteContext() {
		return [
			(int)$this->Auth->user('idcliente'),
			(int)$this->Auth->user('idempresa'),
		];
	}

	/**
	 * @param int $id
	 * @param array $contain
	 * @return \App\Model\Entity\Contract
	 */
	protected function _getContractForClienteOrFail($id, array $contain = []) {
		$id = (int)$id;
		list($idcliente, $idempresa) = $this->_clienteContext();
		if ($id <= 0 || $idcliente <= 0) {
			throw new NotFoundException();
		}
		try {
			$c = $this->Contracts->get($id, ['contain' => $contain]);
		} catch (\Throwable $e) {
			throw new NotFoundException();
		}
		if ((int)$c->idcliente !== $idcliente || (int)($c->idempresa ?? 0) !== $idempresa) {
			throw new ForbiddenException();
		}

		return $c;
	}

	public function index() {
		$this->set('title', __('Meus contratos'));
		list($idcliente, $idempresa) = $this->_clienteContext();
		$this->set('idcliente', $idcliente);
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set('contracts', []);

			return;
		}
		try {
			$q = $this->Contracts->find()
				->contain(['Clientes'])
				->where([
					'Contracts.idcliente' => $idcliente,
					'Contracts.idempresa' => $idempresa,
				])
				->order(['Contracts.modified' => 'DESC']);
			$this->paginate = ['limit' => 30];
			$this->set('contracts', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Módulo indisponível.'));
			$this->set('contracts', []);
		}

		$clicontratosLegado = [];
		try {
			$this->loadModel('Clicontratos');
			$clicontratosLegado = $this->Clicontratos->find()
				->contain(['Clientes'])
				->where([
					'Clicontratos.idempresa' => $idempresa,
					'Clicontratos.idcliente' => $idcliente,
				])
				->order(['Clicontratos.modified' => 'DESC'])
				->limit(150)
				->all();
		} catch (\Throwable $e) {
			$clicontratosLegado = [];
		}
		$this->set('clicontratosLegado', $clicontratosLegado);
	}

	public function view($id = null) {
		$c = $this->_getContractForClienteOrFail($id, [
			'ContractServices',
			'ContractDocuments' => function (\Cake\ORM\Query $q) {
				return $q->where(['ContractDocuments.is_public' => true]);
			},
			'ContractTemplates',
			'ContractSignatories',
		]);
		$this->set('title', h($c->name));
		$this->set('contract', $c);
	}

	public function downloadPdf($id = null) {
		$c = $this->_getContractForClienteOrFail($id, ['Clientes']);
		$path = (string)($c->pdf_path ?? '');
		if ($path === '' || !is_readable($path)) {
			$this->Flash->error(__('PDF ainda não disponível para este contrato.'));

			return $this->redirect(['action' => 'view', (int)$c->id]);
		}
		$this->autoRender = false;
		$body = file_get_contents($path);

		return $this->response
			->withType('application/pdf')
			->withDownload('contrato-' . (int)$c->id . '.pdf')
			->withStringBody($body !== false ? $body : '');
	}

	public function downloadSigned($id = null) {
		$c = $this->_getContractForClienteOrFail($id, ['Clientes']);
		$path = (string)($c->signed_pdf_path ?? '');
		if ($path === '' || !is_readable($path)) {
			$this->Flash->error(__('PDF assinado ainda não disponível.'));

			return $this->redirect(['action' => 'view', (int)$c->id]);
		}
		$this->autoRender = false;
		$body = file_get_contents($path);

		return $this->response
			->withType('application/pdf')
			->withDownload('contrato-assinado-' . (int)$c->id . '.pdf')
			->withStringBody($body !== false ? $body : '');
	}

	public function faturas() {
		return $this->redirect('/cliente/faturas-avancadas');
	}

	public function franquia() {
		$this->set('title', __('Franquia de horas'));
		list($idcliente, $idempresa) = $this->_clienteContext();
		$mes = trim((string)$this->request->getQuery('mes', date('Y-m')));
		if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
			$mes = date('Y-m');
		}
		$rows = [];
		if ($idcliente <= 0) {
			$this->Flash->error(__('Cliente não vinculado ao usuário.'));
			$this->set(compact('rows', 'mes'));

			return;
		}
		try {
			$this->loadModel('ContractConsumptions');
			$list = $this->Contracts->find()
				->where([
					'Contracts.idcliente' => $idcliente,
					'Contracts.idempresa' => $idempresa,
				])
				->order(['Contracts.name' => 'ASC'])
				->all();
			foreach ($list as $contract) {
				$qSum = $this->ContractConsumptions->find();
				$sum = $qSum->select(['h' => $qSum->func()->sum('consumed_hours')])
					->where([
						'contract_id' => $contract->id,
						'reference_month' => $mes,
					])
					->first();
				$consumed = (float)($sum && isset($sum->h) ? $sum->h : 0);
				$rows[] = [
					'contract' => $contract,
					'consumed_hours' => $consumed,
					'included_hours' => (float)($contract->included_hours ?? 0),
				];
			}
		} catch (\Throwable $e) {
			$this->Flash->error(__('Não foi possível carregar os consumos.'));
		}
		$this->set(compact('rows', 'mes'));
	}

	public function solicitarRenovacao($id = null) {
		$this->request->allowMethod(['post']);
		$c = $this->_getContractForClienteOrFail($id);
		$uid = (int)$this->Auth->user('id');
		$svc = new ContractRenewalService();
		$r = $svc->solicitarRenovacao($c, $uid > 0 ? $uid : null);
		if ($r) {
			$this->Flash->success(__('Pedido de renovação enviado à equipe.'));
		} else {
			$this->Flash->warning(__('Já existe pedido de renovação em análise.'));
		}

		return $this->redirect(['action' => 'view', (int)$c->id]);
	}
}
