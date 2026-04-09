<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\RbacChecker;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}

/**
 * Fila de pré-faturamento: ordens com situação "Liberada para faturamento".
 * Conferências e vínculo com fatura exigem migração `PrefaturamentoFaturasOsLink`.
 */
class PrefaturamentoController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Ordensservico');
		$this->loadModel('Clientes');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function index() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$cliente = $this->request->getQuery('cliente');
		if ((string)$cliente === '0') {
			$cliente = '';
		}

		$q = $this->Ordensservico->find('all')
			->where([
				'Ordensservico.idempresa' => $idempresa,
				'Ordensservico.situacao' => C_OrdensSituacaoLiberadaParaFaturamento,
			])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				'Users' => ['fields' => ['Users.id', 'Users.name']],
			])
			->order(['Ordensservico.id' => 'DESC']);

		if ($cliente !== '' && $cliente !== null && (string)$cliente !== '0') {
			$q->where(['Ordensservico.idcliente' => $cliente]);
		}

		$ordens = $q->toArray();

		$clientesOpt1 = [0 => 'Todos'];
		$clientes = $this->Clientes->find('all')->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach ($clientes as $reg) {
			if ($reg->tipo == C_ClientesTipoJuridica) {
				$clientesOpt[$reg->id] = $reg->razaosocial;
			} else {
				$clientesOpt[$reg->id] = $reg->nome;
			}
		}
		asort($clientesOpt);
		foreach ($clientesOpt as $key => $reg) {
			$clientesOpt1[$key] = $reg;
		}

		$this->set('ordens', $ordens);
		$this->set('cliente', $cliente);
		$this->set('clientesOpt1', $clientesOpt1);
		$this->set('title', 'Pré-faturamento');
		$this->set('bodyPageClass', 'pgm-prefaturamento-page');
		$this->set('showPrefaturamentoConferencia', $this->_canEditPrefaturamentoConferencia());
	}

	/**
	 * Salva flags mínimas de conferência (execução / comercial / fiscal) na OS.
	 */
	public function conferencia() {
		$this->request->allowMethod(['post']);
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}
		if (!$this->_canEditPrefaturamentoConferencia()) {
			$this->Flash->error(__('Sem permissão para alterar conferências de pré-faturamento.'));
			return $this->redirect(['action' => 'index']);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$idordem = (int)$this->request->getData('idordem');
		if ($idordem < 1) {
			throw new NotFoundException(__('OS inválida.'));
		}
		$ordem = $this->Ordensservico->find()->where(['id' => $idordem, 'idempresa' => $idempresa])->first();
		if (!$ordem) {
			throw new NotFoundException(__('OS não encontrada.'));
		}
		if ((int)$ordem->situacao !== (int)C_OrdensSituacaoLiberadaParaFaturamento) {
			$this->Flash->error(__('Só é possível conferir OS na situação "Liberada para faturamento".'));
			return $this->redirect(['action' => 'index']);
		}
		$bExec = $this->_boolFromRequest('prefat_conf_exec');
		$bCom = $this->_boolFromRequest('prefat_conf_comercial');
		$bFis = $this->_boolFromRequest('prefat_conf_fiscal');
		try {
			$this->Ordensservico->updateAll(
				[
					'prefat_conf_exec' => $bExec,
					'prefat_conf_comercial' => $bCom,
					'prefat_conf_fiscal' => $bFis,
					'prefat_conf_em' => date('Y-m-d H:i:s'),
					'prefat_conf_iduser' => $this->Auth->user('id'),
				],
				['id' => $idordem, 'idempresa' => $idempresa]
			);
		} catch (\Throwable $e) {
			$this->log('[Prefat] conferência: ' . $e->getMessage(), 'warning');
			$this->Flash->error(__('Não foi possível salvar as conferências. Execute a migração do banco se ainda não rodou.'));
			return $this->redirect(['action' => 'index']);
		}
		$this->Flash->success(__('Conferências salvas.'));
		return $this->redirect(['action' => 'index']);
	}

	protected function _boolFromRequest($key) {
		$v = $this->request->getData($key);
		return $v === '1' || $v === 1 || $v === true || $v === 'true' || $v === 'on';
	}

	/**
	 * Alinha a UI e o POST conferencia a menu_sidebar_gates.prefaturamento_conferencia (Fase 6e).
	 *
	 * @return string|string[]
	 */
	protected function _prefaturamentoConferenciaGateCodes() {
		$rb = Configure::read('Rbac');
		if (!is_array($rb) || empty($rb['menu_sidebar_gates']['prefaturamento_conferencia'])) {
			return ['prefaturamento.conferencia', 'prefaturamento.manage'];
		}

		return $rb['menu_sidebar_gates']['prefaturamento_conferencia'];
	}

	protected function _canEditPrefaturamentoConferencia(): bool {
		$u = $this->Auth->user();
		$uid = (int)($u['id'] ?? 0);

		return RbacChecker::shouldShowSidebarGate(
			$u['admin'] ?? null,
			(int)($u['role'] ?? 1),
			$uid,
			$this->_prefaturamentoConferenciaGateCodes()
		);
	}
}
