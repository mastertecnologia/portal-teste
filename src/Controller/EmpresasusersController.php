<?php
    namespace App\Controller;

    use App\Controller\AppController;
    use Cake\Event\Event;
    Use Cake\Datasource\ConnectionManager;
    
    require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
    //require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
    

class EmpresasusersController extends AppController{

    public function initialize() {
        parent::initialize();
        $this->loadModel('Empresas');
        $this->loadModel('Empresasusers');
        $this->loadModel('Users');
		$this->loadModel('Cidades');
		$this->loadModel('Estados');
    }

    public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
        $this->set('title', 'Relações');		
        
        if ($this->Auth->user('role') == C_RoleCliente) {
            $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

    public function index(){
        $this->set('title', 'Lista de Relações');
        if ($this->Auth->user('role') !== C_RoleFuncionario) return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        
        $funcrelacoes = $this->Empresasusers->find('all', ['contain' => ['Empresas', 'Users']])->where(['users.role' => 0])->toArray();
        $clirelacoes = $this->Empresasusers->find('all', ['contain' => ['Empresas', 'Users']])->where(['users.role' => 1])->toArray();

        if(isset($funcrelacoes)) $this->set('funcrelacoes', $funcrelacoes);
        if(isset($clirelacoes)) $this->set('clirelacoes', $clirelacoes);

    }

    public function add(){
        $this->set('title', 'Adicionar Relação');
        $empresasuser = $this->Empresasusers->newEntity();
        if ($this->request->is('post')) {
            $empresasuser = $this->Empresasusers->patchEntity($empresasuser, $this->request->getData());
            $relacoes = $this->Empresasusers->find('list', ['keyField' => 'id', 'valueField' => 'idempresa'])->where(['iduser' => $empresasuser['iduser']])->toArray();
            $rel = 0;
            foreach($relacoes as $relacao){
                if($empresasuser['idempresa'] == $relacao) {
                    $rel = 1;
                    break;
                }
            }
            if ($rel == 1) $this->Flash->error(__('Essa relação já existe.'));
            else if ($this->Empresasusers->save($empresasuser)) {
                $this->Flash->success(__('A relação foi salva.'));
                return $this->redirect(['action' => 'index']);
            }else $this->Flash->error(__('Não foi possível adicionar a relação.'));
        }
        $empresas = $this->Empresas->find('list', ['keyField' => 'id', 'valueField' => 'nomefantasia'])->order(['id'])->toArray();
        $users = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name', 'limit' => 200])->order(['name'])->toArray();
        $this->set(compact('empresasuser', 'empresas', 'users'));
    }

    public function delete($id = null){
        if ($this->Auth->user('role') !== C_RoleFuncionario) {
			$this->Flash->error('Você não possui permissões para realizar esta ação!');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
        }
        
        $empresasuser = $this->Empresasusers->get($id);

        if ($this->Empresasusers->delete($empresasuser)) {
            $this->Flash->success('A relação foi deletada com sucesso!');
            return $this->redirect(['action' => 'index']);
        }
    }
}
