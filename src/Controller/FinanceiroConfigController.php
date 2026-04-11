<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

/**
 * Configurações do módulo financeiro: Plano de Contas e Centros de Custo.
 */
class FinanceiroConfigController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadModel('FinanceiroPlanoContas');
        $this->loadModel('FinanceiroCentrosCusto');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Configuração Financeira');
    }

    public function isAuthorized($user) {
        if ((int)($user['role'] ?? 1) !== 0) {
            return false;
        }
        return parent::isAuthorized($user);
    }

    // ── Plano de Contas ──────────────────────────────────────────────

    public function planoContas() {
        $idempresa = (int)$this->Auth->user('idempresa');
        $tipo = $this->request->getQuery('tipo');
        $conditions = ['FinanceiroPlanoContas.idempresa' => $idempresa];
        if ($tipo && in_array($tipo, ['receita', 'despesa'], true)) {
            $conditions['FinanceiroPlanoContas.tipo'] = $tipo;
        }

        $contas = $this->FinanceiroPlanoContas->find()
            ->where($conditions)
            ->order(['codigo' => 'ASC'])
            ->toArray();

        $this->set(compact('contas', 'tipo'));
    }

    public function planoContasAdd() {
        $idempresa = (int)$this->Auth->user('idempresa');
        $conta = $this->FinanceiroPlanoContas->newEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $idempresa;
            $conta = $this->FinanceiroPlanoContas->patchEntity($conta, $data);
            if ($this->FinanceiroPlanoContas->save($conta)) {
                $this->Flash->success('Conta cadastrada.');
                return $this->redirect(['action' => 'planoContas']);
            }
            $this->Flash->error('Não foi possível salvar.');
        }

        $pais = $this->FinanceiroPlanoContas->listByEmpresa($idempresa);
        $this->set(compact('conta', 'pais'));
    }

    public function planoContasEdit($id = null) {
        $idempresa = (int)$this->Auth->user('idempresa');
        $conta = $this->FinanceiroPlanoContas->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->firstOrFail();

        if ($this->request->is(['put', 'post', 'patch'])) {
            $conta = $this->FinanceiroPlanoContas->patchEntity($conta, $this->request->getData());
            if ($this->FinanceiroPlanoContas->save($conta)) {
                $this->Flash->success('Conta atualizada.');
                return $this->redirect(['action' => 'planoContas']);
            }
            $this->Flash->error('Não foi possível salvar.');
        }

        $pais = $this->FinanceiroPlanoContas->listByEmpresa($idempresa);
        $this->set(compact('conta', 'pais'));
    }

    public function planoContasDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = (int)$this->Auth->user('idempresa');
        $conta = $this->FinanceiroPlanoContas->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->firstOrFail();

        // Verificar se tem filhos
        $filhos = $this->FinanceiroPlanoContas->find()
            ->where(['parent_id' => $id])->count();
        if ($filhos > 0) {
            $this->Flash->error('Não é possível excluir: esta conta possui subcontas.');
            return $this->redirect(['action' => 'planoContas']);
        }

        if ($this->FinanceiroPlanoContas->delete($conta)) {
            $this->Flash->success('Conta excluída.');
        } else {
            $this->Flash->error('Não foi possível excluir.');
        }
        return $this->redirect(['action' => 'planoContas']);
    }

    // ── Centros de Custo ─────────────────────────────────────────────

    public function centrosCusto() {
        $idempresa = (int)$this->Auth->user('idempresa');
        $centros = $this->FinanceiroCentrosCusto->find()
            ->where(['idempresa' => $idempresa])
            ->order(['codigo' => 'ASC'])
            ->toArray();

        $this->set(compact('centros'));
    }

    public function centroCustoAdd() {
        $idempresa = (int)$this->Auth->user('idempresa');
        $centro = $this->FinanceiroCentrosCusto->newEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $idempresa;
            $centro = $this->FinanceiroCentrosCusto->patchEntity($centro, $data);
            if ($this->FinanceiroCentrosCusto->save($centro)) {
                $this->Flash->success('Centro de custo cadastrado.');
                return $this->redirect(['action' => 'centrosCusto']);
            }
            $this->Flash->error('Não foi possível salvar.');
        }

        $this->set(compact('centro'));
    }

    public function centroCustoEdit($id = null) {
        $idempresa = (int)$this->Auth->user('idempresa');
        $centro = $this->FinanceiroCentrosCusto->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->firstOrFail();

        if ($this->request->is(['put', 'post', 'patch'])) {
            $centro = $this->FinanceiroCentrosCusto->patchEntity($centro, $this->request->getData());
            if ($this->FinanceiroCentrosCusto->save($centro)) {
                $this->Flash->success('Centro de custo atualizado.');
                return $this->redirect(['action' => 'centrosCusto']);
            }
            $this->Flash->error('Não foi possível salvar.');
        }

        $this->set(compact('centro'));
    }

    public function centroCustoDelete($id = null) {
        $this->request->allowMethod(['post', 'delete']);
        $idempresa = (int)$this->Auth->user('idempresa');
        $centro = $this->FinanceiroCentrosCusto->find()
            ->where(['id' => $id, 'idempresa' => $idempresa])
            ->firstOrFail();

        if ($this->FinanceiroCentrosCusto->delete($centro)) {
            $this->Flash->success('Centro de custo excluído.');
        } else {
            $this->Flash->error('Não foi possível excluir.');
        }
        return $this->redirect(['action' => 'centrosCusto']);
    }
}
