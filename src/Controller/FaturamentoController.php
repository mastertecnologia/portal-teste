<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';

/**
 * Módulo Faturamento
 * Fluxo: Pré-faturamento / OS → Faturamento → Financeiro
 * Status: rascunho → pendente → enviado → pago → cancelado
 */
class FaturamentoController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Faturamento');
		$this->loadModel('FaturamentoItens');
		$this->loadModel('Clientes');
		$this->loadModel('Ordensservico');
		$this->loadModel('Empresas');
		$this->loadModel('FinanceiroLancamentos');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Faturamento');
	}

	public function isAuthorized($user) {
		if ((int)($user['role'] ?? 1) === C_RoleCliente) {
			return false;
		}
		return parent::isAuthorized($user);
	}

	/* ── Listagem ──────────────────────────────────────────────────────── */
	public function index() {
		$idempresa = $this->Auth->user('idempresa');
		$status    = $this->request->getQuery('status') ?? '';
		$cliente   = $this->request->getQuery('cliente') ?? '';
		if ((string)$cliente === '0') $cliente = '';

		$q = $this->Faturamento->find('all')
			->where(['Faturamento.idempresa' => $idempresa])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				'Users'    => ['fields' => ['Users.id', 'Users.name']],
			])
			->order(['Faturamento.id' => 'DESC']);

		if ($status !== '') $q->where(['Faturamento.status' => $status]);
		if ($cliente !== '') $q->where(['Faturamento.idcliente' => $cliente]);

		$documentos = $q->toArray();

		// KPIs rápidos
		$kpi = [
			'rascunho' => 0, 'pendente' => 0,
			'enviado'  => 0, 'pago'     => 0, 'cancelado' => 0,
		];
		foreach ($documentos as $d) {
			if (isset($kpi[$d->status])) $kpi[$d->status]++;
		}

		$clientes = $this->Clientes->find('all')
			->where(['idempresa' => $idempresa, 'inativo' => 0])
			->order(['razaosocial'])->toArray();

		$this->set(compact('documentos', 'kpi', 'clientes', 'status', 'cliente'));
		$this->set('hideLayoutPageTitle', true);
	}

	/* ── Visualizar ────────────────────────────────────────────────────── */
	public function view($id = null) {
		$idempresa = $this->Auth->user('idempresa');
		$doc = $this->Faturamento->find('all')
			->where(['Faturamento.id' => $id, 'Faturamento.idempresa' => $idempresa])
			->contain([
				'Clientes',
				'Users'    => ['fields' => ['Users.id', 'Users.name']],
				'FaturamentoItens',
				'FinanceiroLancamentos',
			])
			->first();

		if (empty($doc)) {
			$this->Flash->error('Documento não encontrado.');
			return $this->redirect(['action' => 'index']);
		}
		$this->set(compact('doc'));
	}

	/* ── Novo documento ────────────────────────────────────────────────── */
	public function add() {
		$idempresa = $this->Auth->user('idempresa');
		$doc = $this->Faturamento->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$data['idempresa'] = $idempresa;
			$data['idautor']   = $this->Auth->user('id');
			$data['hash']      = $this->Faturamento->gerarHash();
			$data['numero']    = $this->Faturamento->proximoNumero($idempresa);

			$doc = $this->Faturamento->patchEntity($doc, $data);
			if ($this->Faturamento->save($doc)) {
				// Salva itens
				$itens = $data['itens'] ?? [];
				foreach ($itens as $item) {
					if (empty(trim($item['descricao'] ?? ''))) continue;
					$item['idfaturamento'] = $doc->id;
					$item['valor_total']   = (float)($item['quantidade'] ?? 1) * (float)($item['valor_unitario'] ?? 0);
					$novoItem = $this->FaturamentoItens->newEntity($item);
					$this->FaturamentoItens->save($novoItem);
				}
				$this->Flash->success('Documento de faturamento criado com sucesso.');
				return $this->redirect(['action' => 'view', $doc->id]);
			}
			$this->Flash->error('Erro ao salvar. Verifique os dados e tente novamente.');
		}

		$clientes = $this->Clientes->find('list', [
			'keyField'   => 'id',
			'valueField' => 'razaosocial',
		])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();

		$this->set(compact('doc', 'clientes'));
	}

	/* ── Editar ────────────────────────────────────────────────────────── */
	public function edit($id = null) {
		$idempresa = $this->Auth->user('idempresa');
		$doc = $this->Faturamento->find('all')
			->where(['Faturamento.id' => $id, 'Faturamento.idempresa' => $idempresa])
			->contain(['FaturamentoItens'])
			->first();

		if (empty($doc)) {
			$this->Flash->error('Documento não encontrado.');
			return $this->redirect(['action' => 'index']);
		}
		if (in_array($doc->status, ['pago', 'cancelado'])) {
			$this->Flash->error('Este documento não pode ser editado no status atual.');
			return $this->redirect(['action' => 'view', $id]);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$doc = $this->Faturamento->patchEntity($doc, $data);
			if ($this->Faturamento->save($doc)) {
				$this->Flash->success('Documento atualizado.');
				return $this->redirect(['action' => 'view', $id]);
			}
			$this->Flash->error('Erro ao atualizar o documento.');
		}

		$clientes = $this->Clientes->find('list', [
			'keyField'   => 'id',
			'valueField' => 'razaosocial',
		])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();

		$this->set(compact('doc', 'clientes'));
	}

	/* ── Alterar status ────────────────────────────────────────────────── */
	public function alterarStatus($id = null) {
		$this->request->allowMethod(['post']);
		$idempresa = $this->Auth->user('idempresa');
		$doc = $this->Faturamento->find('all')
			->where(['Faturamento.id' => $id, 'Faturamento.idempresa' => $idempresa])
			->first();

		if (empty($doc)) {
			return $this->jsonResponse(['ok' => false, 'msg' => 'Não encontrado.'], 404);
		}

		$novoStatus = $this->request->getData('status');
		$permitidos = ['rascunho', 'pendente', 'enviado', 'pago', 'cancelado'];
		if (!in_array($novoStatus, $permitidos, true)) {
			return $this->jsonResponse(['ok' => false, 'msg' => 'Status inválido.'], 422);
		}

		$doc->status = $novoStatus;
		$this->Faturamento->save($doc);

		// Ao marcar como pago: gera lançamento financeiro automaticamente
		if ($novoStatus === 'pago') {
			$this->_gerarLancamentoFinanceiro($doc);
		}

		return $this->jsonResponse(['ok' => true, 'status' => $novoStatus]);
	}

	/* ── Gerar de OS (ação chamada a partir da OS finalizada) ──────────── */
	public function gerarDeOS($idordem = null) {
		if ($idordem === null) {
			$pass = $this->request->getParam('pass') ?: [];
			$idordem = $pass[0] ?? null;
		}
		if (is_array($idordem)) {
			$idordem = reset($idordem);
		}
		$idordem = (int)$idordem;
		if ($idordem < 1) {
			$this->Flash->error('Ordem de serviço inválida.');
			return $this->redirect(['controller' => 'Ordensservico', 'action' => 'index']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$ordem = $this->Ordensservico->find('all')
			->where(['Ordensservico.id' => $idordem, 'Ordensservico.idempresa' => $idempresa])
			->contain(['Clientes', 'Ordemservicositens'])
			->first();

		if (empty($ordem)) {
			$this->Flash->error('Ordem de serviço não encontrada.');
			return $this->redirect(['controller' => 'Ordensservico', 'action' => 'index']);
		}

		// Verifica se já existe faturamento para esta OS
		$existente = $this->Faturamento->find('all')
			->where(['idordem' => $idordem])->first();
		if (!empty($existente)) {
			$this->Flash->warning('Já existe um documento de faturamento para esta OS.');
			return $this->redirect(['action' => 'view', $existente->id]);
		}

		$doc = $this->Faturamento->newEntity([
			'idempresa'      => $idempresa,
			'idcliente'      => $ordem->idcliente,
			'idordem'        => $idordem,
			'status'         => 'rascunho',
			'tipo'           => 'servico',
			'data_emissao'   => date('Y-m-d'),
			'valor_total'    => $ordem->valortotal ?? 0,
			'valor_subtotal' => $ordem->valortotal ?? 0,
			'valor_desconto' => 0,
			'numero'         => $this->Faturamento->proximoNumero($idempresa),
			'hash'           => $this->Faturamento->gerarHash(),
			'idautor'        => $this->Auth->user('id'),
			'descricao'      => 'Gerado a partir da OS #' . $idordem,
		]);

		if ($this->Faturamento->save($doc)) {
			$this->Flash->success('Documento de faturamento criado a partir da OS #' . $idordem . '.');
			return $this->redirect(['action' => 'view', $doc->id]);
		}

		$this->Flash->error('Erro ao gerar o documento de faturamento.');
		return $this->redirect(['controller' => 'Ordensservico', 'action' => 'view', $idordem]);
	}

	/* ── Excluir (apenas rascunho) ─────────────────────────────────────── */
	public function delete($id = null) {
		$this->request->allowMethod(['post', 'delete']);
		$idempresa = $this->Auth->user('idempresa');
		$doc = $this->Faturamento->find('all')
			->where(['Faturamento.id' => $id, 'Faturamento.idempresa' => $idempresa])
			->first();

		if (empty($doc) || $doc->status !== 'rascunho') {
			$this->Flash->error('Apenas documentos em rascunho podem ser excluídos.');
			return $this->redirect(['action' => 'index']);
		}

		$this->Faturamento->delete($doc);
		$this->Flash->success('Documento excluído.');
		return $this->redirect(['action' => 'index']);
	}

	/* ── Helpers privados ──────────────────────────────────────────────── */
	private function _gerarLancamentoFinanceiro($doc) {
		$existente = $this->FinanceiroLancamentos->find('all')
			->where(['idfaturamento' => $doc->id])->first();
		if (!empty($existente)) return;

		$lancamento = $this->FinanceiroLancamentos->newEntity([
			'idempresa'       => $doc->idempresa,
			'idcliente'       => $doc->idcliente,
			'idfaturamento'   => $doc->id,
			'tipo'            => 'receita',
			'descricao'       => 'Recebimento - ' . ($doc->numero ?? 'FT-' . $doc->id),
			'valor'           => $doc->valor_total,
			'data_lancamento' => date('Y-m-d'),
			'data_vencimento' => $doc->data_vencimento,
			'data_recebimento'=> date('Y-m-d'),
			'status'          => 'recebido',
			'idautor'         => $doc->idautor,
		]);
		$this->FinanceiroLancamentos->save($lancamento);
	}
}
