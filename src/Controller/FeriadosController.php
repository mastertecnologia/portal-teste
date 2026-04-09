<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}

class FeriadosController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Feriados');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->set('title', 'Feriados');
        if ($this->Auth->user('role') == C_RoleCliente) {
            $this->Flash->error('Você não possui permissão para esta ação.');
            return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
    }

    public function index()
    {
        $ano = $this->request->getQuery('ano', date('Y'));
        $feriados = $this->Feriados->find('all')
            ->where([
                'OR' => [
                    ['idempresa IS' => null],
                    ['idempresa' => $this->Auth->user('idempresa')],
                ],
                'data >=' => $ano . '-01-01',
                'data <=' => $ano . '-12-31',
            ])
            ->orderAsc('data')
            ->toArray();
        $this->set(compact('feriados', 'ano'));
    }

    public function add()
    {
        $feriado = $this->Feriados->newEntity();
        if ($this->request->is('post')) {
            $feriado = $this->Feriados->patchEntity($feriado, $this->request->getData());
            $feriado->idempresa = $this->Auth->user('idempresa');
            if ($this->Feriados->save($feriado)) {
                $this->Flash->success(__('Feriado cadastrado.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar.'));
        }
        $this->set(compact('feriado'));
    }

    public function edit($id = null)
    {
        $feriado = $this->Feriados->get($id);
        if ($this->request->is(['post', 'put'])) {
            $feriado = $this->Feriados->patchEntity($feriado, $this->request->getData());
            if ($this->Feriados->save($feriado)) {
                $this->Flash->success(__('Feriado atualizado.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Não foi possível salvar.'));
        }
        $this->set(compact('feriado'));
    }

    public function delete($id = null)
    {
        $feriado = $this->Feriados->get($id);
        if ($this->Feriados->delete($feriado)) {
            $this->Flash->success(__('Feriado excluído.'));
        } else {
            $this->Flash->error(__('Não foi possível excluir.'));
        }
        return $this->redirect(['action' => 'index']);
    }
}
