<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class ContratosHorasController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('ContratosHoras');
        $this->loadModel('Clientes');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set('title', 'Contratos de Horas Técnicas');
        if ($this->Auth->user('role') == C_RoleCliente) {
            $this->Flash->error('Você não possui permissão para esta ação.');
            return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
    }

    /**
     * Lista contratos de horas do cliente.
     */
    public function index($idcliente = null)
    {
        $this->set('idcliente', $idcliente);
        $contratos = $this->ContratosHoras->find('all')
            ->where([
                'idcliente' => $idcliente,
                'idempresa' => $this->Auth->user('idempresa'),
            ])
            ->orderDesc('data_inicio')
            ->toArray();
        $this->set('contratos', $contratos);
    }

    public function add($idcliente = null)
    {
        $contrato = $this->ContratosHoras->newEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['idempresa'] = $this->Auth->user('idempresa');
            $data['saldo_horas'] = isset($data['horas_contratadas']) ? $data['horas_contratadas'] : 0;
            $data['horas_utilizadas'] = 0;
            $contrato = $this->ContratosHoras->patchEntity($contrato, $data);
            if ($this->ContratosHoras->save($contrato)) {
                $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $contrato->id);
                $this->Flash->success(__('Contrato de horas cadastrado com sucesso.'));
                return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
            }
            $this->Flash->error(__('Não foi possível salvar o contrato.'));
        }
        $this->set(compact('contrato', 'idcliente'));
    }

    public function edit($id = null)
    {
        $contrato = $this->ContratosHoras->get($id);
        if ($this->Auth->user('idempresa') != $contrato->idempresa) {
            $this->Flash->error('Acesso negado.');
            return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
        if ($this->request->is(['post', 'put'])) {
            $contrato = $this->ContratosHoras->patchEntity($contrato, $this->request->getData());
            if ($this->ContratosHoras->save($contrato)) {
                $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
                $this->Flash->success(__('Contrato atualizado.'));
                return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $contrato->idcliente]);
            }
            $this->Flash->error(__('Não foi possível salvar.'));
        }
        $this->set(compact('contrato'));
    }

    public function delete($id = null)
    {
        $contrato = $this->ContratosHoras->get($id);
        if ($this->Auth->user('idempresa') != $contrato->idempresa) {
            $this->Flash->error('Acesso negado.');
            return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
        $idcliente = $contrato->idcliente;
        if ($this->ContratosHoras->delete($contrato)) {
            $this->Flash->success(__('Contrato excluído.'));
        } else {
            $this->Flash->error(__('Não foi possível excluir.'));
        }
        return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $idcliente]);
    }
}
