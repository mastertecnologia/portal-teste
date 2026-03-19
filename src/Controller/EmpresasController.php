<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
Use Cake\Datasource\ConnectionManager;
use Cake\Utility\Crypto\Mcrypt;
use Cake\Utility\Security;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';


class EmpresasController extends AppController{
    public function initialize() { 
        parent::initialize();
        $this->loadModel('Empresas');
        $this->loadModel('Empresasusers');
        $this->loadModel('Users');
		$this->loadModel('Cidades');
		$this->loadModel('Estados');
		$this->loadModel('Areas');
		$this->loadModel('Bancosenhas');
		$this->loadModel('Cliacessos');
		$this->loadModel('Clicontratos');
		$this->loadModel('Clientes');
		$this->loadModel('Ordemhoras');
		$this->loadModel('Ordemmovs');
		$this->loadModel('Ordemparcelas');
		$this->loadModel('Ordensservico');
		$this->loadModel('Ordemservicositens');
		$this->loadModel('Problemas');
		$this->loadModel('Produtos');
		$this->loadModel('Ticketcomentarios');
		$this->loadModel('Ticketsanexos');
		$this->loadModel('Tickets');
		$this->loadModel('Ticketsmovs');
		$this->loadModel('Ticketshoras');
		$this->loadModel('Ticketsusers');
		$this->loadModel('Users');
		$this->loadModel('Visitas');
		$this->loadModel('Orcamentos');
		$this->loadModel('Orcamentositens');
		$this->loadModel('Orcamentosmovs');
		$this->loadModel('Orcamentosnovosdes');
		$this->loadModel('Orcamentosservicos');
		$this->loadModel('Itensordem');
	}
	
    public function beforeFilter(Event $event) { 
		parent::beforeFilter($event);
		$this->set('title', 'Empresas');		
		
		// Permite a troca de empresa via dropdown também para clientes.
		// Outras ações do controller continuam bloqueadas para role de cliente.
		$action = (string)$this->request->getParam('action');
		if ($this->Auth->user('role') == C_RoleCliente && $action !== 'alteraempresa') {
            $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

    public function index(){ 
        $this->set('title', 'Lista de Empresas');
        if ($this->Auth->user('role') == 1) return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);

        $ativas = $this->Empresas->selectAllByInativa(0);
        $inativas = $this->Empresas->selectAllByInativa(1);

        foreach($ativas as $ativa){
        $ativa['nrousuarios'] = sizeof($this->Empresasusers->findByIdempresa($ativa['id']));}
        foreach($inativas as $inativa){
        $inativa['nrousuarios'] = sizeof($this->Empresasusers->findByIdempresa($inativa['id']));}

        $this->set('ativas', $ativas);
        $this->set('inativas', $inativas);
	} 

	public function dirLogotipos($idempresa = null) { 
		return (WWW_ROOT . 'arquivos' . DS . 'empresas' . DS . "logotipos" . DS . $idempresa);
	}
	
	public function moveFile($file, $idempresa) { 
		//Ignora, se não tiver nada selecionado.
		if (!isset($file['tmp_name']) || !isset($file['name'])) {
			return 1;
		}

		if (empty($file['name'])) {
			return 1;
		}

		$diretorio = $this->dirLogotipos($idempresa);

		//  Cria a pasta caso ela não exista
		if (!file_exists($diretorio)) {
			mkdir($diretorio, 0755, true);
		}

		$arquivo = $diretorio . DS . 'logo.png';

		//Move o arquivo para a pasta.
		if (move_uploaded_file($file['tmp_name'], $arquivo)) return 1;
		else return 0;
	}

	public function deleteFolder($dirname) { 
		if (is_dir($dirname))
			$dir_handle = opendir($dirname);
		if (!$dir_handle)
			return -1;
		while($file = readdir($dir_handle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dirname."/".$file))
					unlink($dirname."/".$file);
				else
					delete_directory($dirname.'/'.$file);
			}
		}
		closedir($dir_handle);
		rmdir($dirname);
		return 1;
	}

	public function deleteLogotipo($idempresa) { 
		if ($this->request->is('get')) {
			// Pasta do logotipo
			$pasta = $this->dirLogotipos($idempresa);

			$ret = $this->deleteFolder($pasta);

			if ($ret != 0) {
				$this->Flash->success('O logotipo foi deletado com sucesso!');
				return $this->redirect(['action' => 'edit', $idempresa, "1"]);
			} else {
				$this->Flash->error('O logotipo solicitado para deleção não foi localizado!', ['params' => ['title' => 'Erro ao excluir o logotipo']]);
				return $this->redirect(['action' => 'edit', $idempresa, "1"]);
			}
		}
	}

    public function add(){ 
        $this->set('title', 'Adicionar Empresa');
        $empresa = $this->Empresas->newEntity();

		if (!empty($this->request->data) ) {
			$data = $this->request->getData();

			if($data['senhaadministrativa'] !== $data['confirmasenhaadministrativa']) {
				$this->Flash->error(__('As senhas não conferem.'));
				return $this->redirect(['action' => 'index']);
			}

			if (isset($data['logotipo'])) {
				$logotipo = $data['logotipo'];
				unset($data['logotipo']);
			}

            if (!isset($data['inativa'])) $data['inativa'] = '0';
            if (!isset($data['nrousuarios'])) $data['nrousuarios'] = '0';

            $data['token'] = $this->Empresas->generateToken($data['cnpj']);

			$empresa = $this->Empresas->patchEntity($empresa, $data);
			$empresa->created = date('d/m/y');
			$empresa->senhaadministrativa = criptografaSenha($data['senhaadministrativa']); 


			if ($this->Empresas->save($empresa)) {
				$ret = $this->moveFile($logotipo, $empresa->id);
				if ($ret != 1) $this->Flash->error('Ocorreu um erro ao enviar o arquivo "' . $logotipo['name'] . '". Tente novamente mais tarde.');
				else {
					if (!empty($logotipo['name'])) {
						$empresa->logotipo = $logotipo['name'];

						if (!$this->Empresas->save($empresa)) {
							$this->Flash->error('Ocorreu um erro ao salvar o arquivo "' . $logotipo['name'] . '". Tente novamente mais tarde.');
						}
					}
				}
				
                $this->Flash->success(__('A empresa foi salva.'));
				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error(__('Não foi possível adicionar o empresa.'));
		}

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
        $this->set('cidades', $cidades);
		$this->set('empresa', $empresa);
    }

    public function edit($id = null){ 
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'Empresas', 'action' => 'index']);
		}

        $empresa = $this->Empresas->get($id);
		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();

		if ($this->request->is(['patch', 'post', 'put'])) {
			$data = $this->request->getData();

			if (isset($data['logotipo'])) {
				$logotipo = $data['logotipo'];
				unset($data['logotipo']);
			}

			$empresa = $this->Empresas->patchEntity($empresa, $data);
			if ($this->Empresas->save($empresa)) {
				if(isset($logotipo)){
					$ret = $this->moveFile($logotipo, $empresa->id);
					if ($ret != 1) $this->Flash->error('Ocorreu um erro ao enviar o arquivo "' . $logotipo['name'] . '". Tente novamente mais tarde.');
					else {
						if (!empty($logotipo['name'])) {
							$empresa->logotipo = $logotipo['name'];
	
							if (!$this->Empresas->save($empresa)) {
								$this->Flash->error('Ocorreu um erro ao salvar o arquivo "' . $logotipo['name'] . '". Tente novamente mais tarde.');
							}
						}
					}
				}
				
                $this->Flash->success(__('A empresa foi salva.'));
				return $this->redirect(['action' => 'edit', $empresa->id]);
			}
			$this->Flash->error(__('Não foi possível salvar a empresa.'));
		}
		
		$this->set('cidades', $cidades);
		$this->set('title', 'Empresa: ' . $empresa->razaosocial);
		$this->set('entity', $empresa);
    }

    public function updateToken() { 
		$this->autoRender = false;

		if ($this->Auth->user('role') == 0) {
			$empresas = $this->Empresas->find('all', ['fields' => ['id', 'cnpj', 'token']])->toArray();

			foreach ($empresas as $key => $value) {
				if (!isset($value['token']) or empty($value['token'])) {
					// Gera o token
					$data['token'] = $this->Empresas->generateToken($value['cnpj']);

					// Atualiza o token
					$empresa = $this->Empresas->get($value['id']);
					$empresa = $this->Empresas->patchEntity($empresa, $data);
					$this->Empresas->save($empresa);
				}
			}
		}
	}

	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		$empresa = $this->Empresas->get($id);

		if (!empty($this->request->data)) {
			$data = $this->request->data();
			if($data['password1'] !== $data['password2']){
				$this->Flash->success('As senhas não conferem!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}

			$senhaatual = descriptografaSenha($empresa->senhaadministrativa); 

			if($data['old_password'] !== $senhaatual){
				$this->Flash->error('A senha antiga não confere!');
				return $this->redirect(['action' => 'changePassword', $id]);
			}
			$encryptionNova = criptografaSenha($data['password1']); 

			$empresa->senhaadministrativa = $encryptionNova;
			$empresa = $this->Users->patchEntity($empresa, $data);

			if ($this->Empresas->save($empresa)) {
				$this->Flash->success('A senha foi alterada com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['action' => 'edit', $id]);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('empresa', $empresa);
	} 

	public function migrar() {
		if($this->request->is(['post'])) {
			extract($this->request->getData());

			if($empresapara == C_EmpresaMaster) $empresaDe = C_EmpresaPGM;
			if($empresapara == C_EmpresaPGM) $empresaDe = C_EmpresaMaster;

			$this->Bancosenhas->query()->update()->set(['idempresa' => $empresapara])->execute();
			
			$this->Flash->success('A migração foi realizada com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action);
			return $this->redirect($this->add);
		}

		$this->set('title', 'Migração de dados');
	}

	public function migrarcliente() {
		$empresapara = $this->Auth->user('idempresa') == 1 ? $this->Empresas->get(2) : $this->Empresas->get(1);

		if($this->request->is(['post'])) {
			extract($this->request->getData());

			foreach($clientes['_ids'] as $cliente) {
				if($empresapara->id == C_EmpresaMaster) $empresaDe = C_EmpresaPGM;
				if($empresapara->id == C_EmpresaPGM) $empresaDe = C_EmpresaMaster;
				
				// Cliente 
					$this->Clientes->query()->update()->set(['idempresa' => $empresapara->id])->where(['id' => $cliente])->execute();
					$cliente1 = $this->Clientes->get($cliente);
					$cliente2 = $this->Clientes->get($cliente)->tipo == C_ClientesTipoJuridica ? $this->Clientes->findByCnpj($cliente1->cnpj)->first() : $this->Clientes->findByCpf($cliente1->cpf)->first();
					if(!empty($cliente2)) $this->Clientes->query()->update()->set(['empresadominante' => $empresapara->id])->where(['id' => $cliente2->id])->execute();
					$this->Cliacessos->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
					$this->Clicontratos->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
					$this->Visitas->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
				// Users 
					$clientesIds = [];
					foreach($this->Users->findByIdcliente($cliente)->where(['role' => C_RoleCliente])->toArray() as $reg) 
						$query = $this->Empresasusers->query()->update()->set(['idempresa' => $empresapara->id])->where(['iduser' => $reg->id])->execute();
				// Ordens 
					$this->Ordensservico->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
					foreach($this->Ordensservico->findByIdcliente($cliente)->toArray() as $reg) {
						$this->Ordemhoras->query()->update()->set(['idempresa' => $empresapara->id])->where(['idordem' => $reg->id])->execute();
						$this->Ordemmovs->query()->update()->set(['idempresa' => $empresapara->id])->where(['idordem' => $reg->id])->execute();
						$this->Ordemparcelas->query()->update()->set(['idempresa' => $empresapara->id])->where(['idordem' => $reg->id])->execute();
						$this->Ordemservicositens->query()->update()->set(['idempresa' => $empresapara->id])->where(['idordem' => $reg->id])->execute();
					}
				// Tickets 
					$this->Tickets->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
					foreach($this->Tickets->findByIdcliente($cliente)->toArray() as $reg) {
						$this->Ticketsmovs->query()->update()->set(['idempresa' => $empresapara->id])->where(['idticket' => $reg->id])->execute();
						$this->Ticketshoras->query()->update()->set(['idempresa' => $empresapara->id])->where(['idticket' => $reg->id])->execute();
						$this->Ticketsusers->query()->update()->set(['idempresa' => $empresapara->id])->where(['idticket' => $reg->id])->execute();
					}
				// Orçamentos 
					$this->Orcamentos->query()->update()->set(['idempresa' => $empresapara->id])->where(['idcliente' => $cliente])->execute();
					foreach($this->Orcamentos->findByIdcliente($cliente)->toArray() as $reg) {
						$idcarrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $empresaDe, 'idorcamento' => $reg->id])->first();
						$this->Orcamentositens->query()->update()->set(['idempresa' => $empresapara->id])->where(['idorcamento' => $reg->id])->execute();
						if(!empty($idcarrinho)) $this->Orcamentosservicos->query()->update()->set(['idempresa' => $empresapara->id])->where(['idorcamento' => $idcarrinho->iditem])->execute();
						$this->Orcamentosmovs->query()->update()->set(['idempresa' => $empresapara->id])->where(['idorcamento' => $reg->id])->execute();
					}
				// 
			}
			
			$this->Flash->success('A migração foi realizada com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action);
			return $this->redirect(['action' => 'index']);
		}

		$clientesOpt = [];
		foreach($this->Clientes->find('all')->where(['idempresa' => $this->Auth->user('idempresa')])->order(['razaosocial'])->toArray() as $reg) 
			$clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial : $reg->nome;
		asort($clientesOpt);

		$this->set('empresaParaNome', $empresapara->nomefantasia);
		$this->set('clientes', $clientesOpt);
		$this->set('title', 'Migração de dados');
	}

	public function alteraempresa() {
		if(isset($_SESSION['PGM_Ordem_Idcarrinhoadd'])){
			$carrinho = $this->Itensordem->find('all')->where(['idordempk' => $_SESSION['PGM_Ordem_Idcarrinhoadd']])->toArray();
			foreach($carrinho as $item) $this->Itensordem->delete($item);
			unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);
		}

		$this->autoRender = false;
		$empresaSelecionada = (int)$this->request->getData('empresa');
		if ($empresaSelecionada <= 0) return;

		// Garante que o usuário tem acesso a essa empresa (evita troca indevida).
		$temAcesso = $this->Empresasusers->find()
			->where(['iduser' => $this->Auth->user('id'), 'idempresa' => $empresaSelecionada])
			->count();
		if ($temAcesso <= 0) return;

		$user = $this->Users->get($this->Auth->user('id'));
		$user->idempresa = $empresaSelecionada;
		$this->Users->save($user); // persiste para que recarregamentos/flows que re-carregam do banco reflitam
		$this->Auth->setUser($user);
	}
}
