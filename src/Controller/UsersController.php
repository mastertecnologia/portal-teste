<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Response;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

require_once (WWW_ROOT . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS . 'FixedBitNotation.php');
require_once (WWW_ROOT . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS . 'GoogleQrUrl.php');
require_once (WWW_ROOT . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS . 'GoogleAuthenticatorInterface.php');
require_once (WWW_ROOT . 'plugins' . DS . 'GoogleAuthenticator-2.x' . DS . 'src' . DS . 'GoogleAuthenticator.php');

use Cake\Mailer\Email;

class UsersController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Clicontratos');
		$this->loadModel('Users');
		$this->loadModel('Atividades');
		$this->loadModel('Books');
		$this->loadModel('Tickets');
		$this->loadModel('Visitas');
		$this->loadModel('Tarefas');
		$this->loadModel('Empresas');
		$this->loadModel('Empresasusers');
		$this->loadModel('Orcamentos');
		$this->loadModel('Orcamentosservicos');
		$this->loadModel('Produtos');
		$this->loadModel('Ordensservico');
		$this->loadModel('Config');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Usuários');
		$this->Auth->allow(['login', 'acessoEmpresa', 'desativaverificacaosemlogin', 'enviaEmailAutenticacaoSemLogin', 'loginempresa', 'logout', 'privacyPolicy', 'cadastrocliente', 'verificacnpjcliente', 'verificacpfcliente', 'verificadadoscliente', 'verificalogincadastro', 'resetPassword', 'resetPasswordNew', 'verificacpf', 'verificacodigo', 'verificaloginduasetapas']);
		
		if (in_array('Security', $this->components()->loaded())) {
            $this->Security->setConfig('unlockedActions', ['verificacnpjcliente', 'verificacpfcliente', 'cadastrocliente']);
        }

		if (in_array('Csrf', $this->components()->loaded())) {
            if (in_array($this->request->getParam('action'), ['verificacnpjcliente', 'verificacpfcliente', 'cadastrocliente'])) {
                $this->getEventManager()->off($this->Csrf);
            }
        }
	}

	public function index() {
		$this->set('title', 'Usuários da equipe (PGM/Master)');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		$admins = $this->Users
			->find('all', ['fields' => ['id', 'username', 'name', 'email', 'secret', 'created', 'inativo', 'idcliente']])
			->where(['role' => 0, 'idcliente IS' => null])
			->order(['username' => 'ASC'])
			->toArray();
		$this->set('admins', $admins);
	}

	public function indexClientes() {
		$this->set('title', 'Usuários clientes');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		$clients = $this->Users
			->find('all')
			->where(['role' => 1, 'idcliente IS NOT' => null])
			->contain(['Clientes' => ['fields' => ['razaosocial', 'nome']]])
			->order(['username' => 'ASC'])
			->toArray();
		$this->set('clients', $clients);
	}

	public function dashboard($erro = null) {
		$this->set('title', 'Dashboard');
		$idusuario = $this->Auth->user('id');
		$idcliente = $this->Auth->user('idcliente');
		$role = $this->Auth->user('role');
		$hoje = date('d/m/Y');
		$mes = date('01/m/Y');
		$empresa = $this->Auth->user('idempresa');

		if(empty($this->Users->get($idusuario)->secret)) $this->set('bAtivarDuasEtapas', true);

		$iniSemana = primeiroDiaSemana();
		$finSemana = ultimoDiaSemana();

		if($role == 0) { 
			$ordensTable = $this->Ordensservico->findByIdempresa($empresa)
				->contain(['Clientes'])
				->where([
					'Ordensservico.iduser' => $idusuario,
					'dataabertura >=' => $iniSemana,
					'dataabertura <=' => $finSemana,
				])
				->order(['Ordensservico.id DESC'])
				->limit(5)
			->toArray();
			$ticketsPendentesTable = $this->Tickets->findByIdempresa($empresa)
				->contain(['Clientes'])
				->where([ 'situacao IN' => [C_TicketSituacaoPendente]])
				->order(['Tickets.id DESC'])
			->toArray();
			$ticketsSendoResolvidosTable = $this->Tickets->findByIdempresa($empresa)
				->contain(['Clientes'])
				->where([ 'situacao IN' => [C_TicketSituacaoEmandamento], ])
				->order(['Tickets.id DESC'])
			->toArray();
			$usuariosBloqueadosTable = $this->Users->findByBloqueado(1)->contain(['Clientes' => ['fields' => ['nome', 'razaosocial', 'cnpj', 'tipo', 'cpf']]])->contain(['Empresasusers', 'Empresasusers.Empresas'])->toArray();
			
			$this->set('ticketsPendentesTable', $ticketsPendentesTable);
			$this->set('ticketsSendoResolvidosTable', $ticketsSendoResolvidosTable);
			$this->set('usuariosBloqueadosTable', $usuariosBloqueadosTable);
		} else {
			if(!$this->Auth->user('permissaoacesso')) return $this->redirect(['controller' => 'Tickets', 'action' => 'indexcliente']);

			$ordensCliente		= sizeof($this->Ordensservico->find('all')->where(['idempresa' => $empresa, 'idcliente' => $idcliente])->toArray());
			$orcamentosCliente	= sizeof($this->Orcamentos->find('all')->where(['idempresa' => $empresa, 'idcliente' => $idcliente])->toArray());
			$ticketsCliente 	= sizeof($this->Tickets->find('all')->where(['idempresa' => $empresa, 'idautor' => $idusuario])->toArray());
			$visitasCliente		= sizeof($this->Visitas->find('all')->where(['idempresa' => $empresa, 'idcliente' => $idcliente, 'situacao' => C_UserSituacaoFinalizada])->toArray());
			$ticketsPendentes = $this->Tickets->find('all')->where([
				'Tickets.idempresa' => $empresa, 
				'Tickets.idcliente' => $idcliente, 
				'situacao IN' => [C_TicketSituacaoPendente, C_TicketSituacaoEmandamento]
			]);
			if(!$this->Auth->user('permissaoacesso')) $ticketsPendentes = $ticketsPendentes->where(['idautor' => $idusuario]);

			$contratos = $this->Clicontratos->find('all')->where(['idempresa' => $empresa,'idcliente' => $idcliente])->order(['id DESC'])->toArray();
		
			$orcamentosRecentes = $this->Orcamentos->find('all')->contain(['Clientes'])->where(['Orcamentos.idempresa' => $empresa,'idcliente' => $idcliente,])->order(['Orcamentos.id DESC'])->limit(5)->toArray();
			$visitasRecentes = $this->Visitas->find('all')->contain(['Listamembros', 'Clientes'])->where(['Visitas.idempresa' => $empresa,'idcliente' => $idcliente,])->limit(5)->order(['Visitas.data'])->toArray();

			$autores =  $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->where(['role' => 0])->toArray();
			$this->set('autores', $autores);

			$this->set('ordensCliente',  $ordensCliente);
			$this->set('orcamentosCliente', $orcamentosCliente);
			$this->set('ticketsCliente', $ticketsCliente);
			$this->set('visitasCliente', $visitasCliente);
			$this->set('ticketsPendentes', $ticketsPendentes->toArray());

			$this->set('contratos', $contratos);
			$this->set('orcamentosRecentes', $orcamentosRecentes);
			$this->set('visitasRecentes', $visitasRecentes);
		}

		if(isset($_SESSION['PMG_veiologin'])) {
			$this->set('veiologin', true);
			unset($_SESSION['PMG_veiologin']);
		}

		// Desempenho pessoal
		$this->set('historico', $this->Ordensservico->historicoOrdens($this->Auth->user('id'), $empresa));
		// Label para o gráfico
		$this->set('labelHist', ['Ordens de Serviço']);
	}

	public function requisicoesAcesso() {
		$this->set('title', 'Requisições de acesso');
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['action' => 'dashboard']);
		}

		$usuariosBloqueadosTable = $this->Users
			->findByBloqueado(1)
			->contain(['Clientes' => ['fields' => ['nome', 'razaosocial', 'cnpj', 'tipo', 'cpf']]])
			->contain(['Empresasusers', 'Empresasusers.Empresas'])
			->order(['Users.created' => 'DESC'])
			->toArray();

		$this->set('usuariosBloqueadosTable', $usuariosBloqueadosTable);
	}

	public function add() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$this->set('title', 'Adicionar Usuário');

		$user = $this->Users->newEntity(); 

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$this->usuarioExistente($data['username']);

			if (strcmp($data['password'], $data['confirm_password']) != 0) $this->Flash->warning(__('Senhas não conferem!'));
			else {
				// $data['admin'] = $data['role'] == C_RoleFuncionario ? 1 : 0;
				$user = $this->Users->patchEntity($user, $data);
				$user->role = 0;

				if ($this->Users->save($user)) {
					$this->Flash->success(__('O usuário foi salvo.'));
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);
					return $this->redirect(['action' => 'index']);
				}

				$this->Flash->error(__('Não é possível adicionar o usuário.'));
			}
		}

		$this->set('user', $user);
	}

	public function addcliente() {
		$idempresa = 0;

		$this->set('title', 'Adicionar Usuário de Cliente');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);

		$user = $this->Users->newEntity();

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
			
			$idempresa = $reg->empresadominante;
		}
		asort($clientesOpt);

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$cliente = $this->Clientes->findById($data['idcliente']);
			$cliente = $cliente->toList();

			if (isset($cliente[0])) $idempresa = $cliente[0]['empresadominante'];
			
			$this->usuarioExistente($data['email']);

			if (strcmp($data['password'], $data['confirm_password']) != 0) $this->Flash->warning(__('Senhas não conferem!'));
			else {
				$user = $this->Users->patchEntity($user, $data);
				$user->role = 1;

				if ($this->Users->save($user)) {
					$empresauser = $this->Empresasusers->newEntity();
					$empresauser->idempresa = $idempresa; //$this->Auth->user('idempresa');
					$empresauser->iduser = $user->id;
					$this->Empresasusers->save($empresauser);

					$this->Flash->success(__('O usuário foi salvo.'));
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);
					return $this->redirect(['action' => 'index']);
				}
				$this->Flash->error(__('Não é possível adicionar o usuário.'));
			}
		}

		$this->set('clientes', $clientesOpt);
		$this->set('user', $user);
	}

	public function edit($id = null) {
		// Verifica as permissões
		if (!$this->Auth->user('admin')) {
			$this->Flash->error('Você não possui permissões para editar sua conta. Contate um administrador do sistema.');
			return $this->redirect(['action' => 'dashboard']);
		}
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		
		if ($id == null) $id = $this->Auth->user('id');
		$situacoes = "";
		
		$user = $this->Users->get($id);
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			// Se o usuário permanecer ativo (inativo vazio ou 0), mantém a validação rígida de e-mail duplicado.
			// Se ele estiver sendo inativado, permitimos salvar mesmo com e-mail duplicado, para justamente
			// conseguir desativar o usuário conflitante.
			$isBecomingInactive = isset($data['inativo']) && (int)$data['inativo'] === 1;

			if (!$isBecomingInactive) {
				$this->usuarioExistente($data['email'], $id);

				$userExistente = $this->Users
					->find()
					->where([
						'inativo' => 0,
						'id !=' => $id,
						'OR' => [
							'username' => $data['email'],
							'email' => $data['email'],
						],
					])
					->first();

				if (!empty($userExistente)) {
					$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário correspondente.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if (isset($data['role'])) unset($data['role']);
			$this->Users->patchEntity($user, $data);

			if ($this->Users->save($user)) {
				$this->Flash->success('As informações do usuário foram alteradas com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['action' => 'index']);
			}

			$this->Flash->error('Ocorreu um erro ao editar as informações do usuário! Tente novamente mais tarde.');
		}

		$this->set('user', $user);
		$this->set('title', 'Editar Usuário');
	}

	public function editcliente($id = null) {
		$this->set('title', 'Usuários');
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);
		if ($id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);
		$role = $user->role;

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$this->usuarioExistente($data['email'], $id);

			if (isset($data['role'])) unset($data['role']);
			if (isset($data['desativasecret']) && !empty($data['desativasecret'])) $user->secret = null;

			$this->Users->patchEntity($user, $data);

			if ($this->Users->save($user)) {
				$this->Flash->success('As informações do usuário foram alteradas com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error('Ocorreu um erro ao editar as informações do usuário! Tente novamente mais tarde.');
		}
		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		foreach($clientes as $reg) $clientesOpt[$reg->id] = $reg->tipo == C_ClientesTipoJuridica ? $reg->razaosocial :  $reg->nome;
		asort($clientesOpt);

		$this->set('clientes', $clientesOpt);
		$this->set('user', $user);
	}

	public function desbloquear($id = null) {
		// Verifica as permissões
		if ($this->Auth->user('admin') !== 1) {
			$this->Flash->error('Você não possui permissões para desbloquear este cliente.');
			return $this->redirect(['action' => 'dashboard']);
		}
		
		$user = $this->Users->findById($id)->first();
		$user->bloqueado = 0;
		
		if ($this->Users->save($user)) {
			$this->Flash->success('O usuário foi liberado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
		} else $this->Flash->success('Ocorreu um erro ao liberar o usuário, tente novamente.');
		return $this->redirect(['action' => 'dashboard']);
	}

	public function login() {
		$this->viewBuilder()->setLayout("login");
	
		if ($this->Auth->user()) $this->redirect($this->Auth->redirectUrl());
	
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$user = $this->Users->findByEmail($data['username'])->where(['inativo' => 0])->first();
			if(empty($user)) $user = $this->Users->findByUsername($data['username'])->where(['inativo' => 0])->first();
	
			if(!empty($user)) $data['username'] = $user->username;
			$user = $this->Auth->identify($data);
			
			if ($user) {
				// Só clientes (role = C_RoleCliente) podem logar aqui. Qualquer outro role é rejeitado.
				if (!isset($user['role']) || $user['role'] !== C_RoleCliente) {
					$this->Flash->error(__('Este acesso é para clientes. Use o link "Acesso PGM / Master" para entrar com usuário da equipe.'));
					return $this->redirect(['action' => 'acessoEmpresa']);
				}
				if(!$user['inativo'] && !$user['bloqueado']){
					$_SESSION['PMG_veiologin'] = true;
					$user['idempresa'] = $this->getEmpresaPreferencial($user['id']);
					$this->Auth->setUser($user);
					return $this->redirect($this->Auth->redirectUrl());
				} else if($user['inativo']){
					$this->Flash->error(__('Seu usuário está inativo!'));
					return $this->redirect($this->Auth->redirectUrl());
				} else if($user['bloqueado']){
					$this->Flash->error(__('Seu usuário está bloqueado! Aguarde a liberação de um administrador.'));
					return $this->redirect($this->Auth->redirectUrl());
				} 
			}
	
			$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
		}
	}

	/**
	 * Tela de login exclusiva para usuários das empresas (PGM / Master).
	 * Apenas role = C_RoleFuncionario pode logar aqui. Clientes devem usar login().
	 */
	public function acessoEmpresa() {
		$this->viewBuilder()->setLayout("login");
		$this->viewBuilder()->setTemplate("login_empresa");
	
		if ($this->Auth->user()) $this->redirect($this->Auth->redirectUrl());
	
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$user = $this->Users->findByEmail($data['username'])->where(['inativo' => 0])->first();
			if(empty($user)) $user = $this->Users->findByUsername($data['username'])->where(['inativo' => 0])->first();
	
			if(!empty($user)) $data['username'] = $user->username;
			$user = $this->Auth->identify($data);
			
			if ($user) {
				// Só equipe PGM/Master (role = C_RoleFuncionario) pode logar aqui. Qualquer outro role é rejeitado.
				if (!isset($user['role']) || $user['role'] !== C_RoleFuncionario) {
					$this->Flash->error(__('Este acesso é para a equipe PGM / Master. Use o acesso para clientes.'));
					return $this->redirect(['action' => 'login']);
				}
				if(!$user['inativo'] && !$user['bloqueado']){
					$_SESSION['PMG_veiologin'] = true;
					$user['idempresa'] = $this->getEmpresaPreferencial($user['id']);
					$this->Auth->setUser($user);
					return $this->redirect($this->Auth->redirectUrl());
				} else if($user['inativo']){
					$this->Flash->error(__('Seu usuário está inativo!'));
					return $this->redirect($this->Auth->redirectUrl());
				} else if($user['bloqueado']){
					$this->Flash->error(__('Seu usuário está bloqueado! Aguarde a liberação de um administrador.'));
					return $this->redirect($this->Auth->redirectUrl());
				} 
			}
	
			$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
		}
	}

	private function getEmpresaPreferencial($userId) {
		$relacoes = $this->Empresasusers->find('all')
			->where(['iduser' => $userId])
			->contain(['Empresas' => ['fields' => ['Empresas.id', 'Empresas.nomefantasia']]])
			->order(['Empresas.id' => 'ASC'])
			->toArray();
		
		// Se for admin prioriza PGM (ID 2)
		$user = $this->Users->get($userId);
		if ($user->role == C_RoleFuncionario) { 
			foreach ($relacoes as $rel) {
				if ($rel->empresa->id == C_EmpresaPGM) {
					return $rel->empresa->id;
				}
			}
		}
		
		// Se não encontrou PGM ou não é admin, retorna a primeira da lista
		return !empty($relacoes) ? $relacoes[0]->idempresa : null;
	}

	public function loginempresa($string) {
		// Agora essa função é o ajax que retorna a lista, ela pega pela $string que é o login que vai ser enviado pelo ajax
		$this->viewBuilder()->setLayout("login");
		$this->autoRender = false;
		$user = $this->Users->find('all')->where(['username' => $string])->first();

		// Usa o contain pra fazer o join da empresasusers com a empresas, aí tem tudo no mesmo array
		$relacoes = $this->Empresasusers->find('all')->where(['iduser' => $user['id']])->contain(['Empresas' => ['fields' => ['Empresas.id', 'Empresas.nomefantasia']]])->toArray();

		// Aqui arruma a lista certo
		foreach($relacoes as $rel){
			$lista[$rel->empresa->id] = $rel->empresa->nomefantasia;
		}
		
		if(isset($lista) and sizeof($lista) > 0){
			// Isso escreve o json com a lista pra formar depois no html com o jquery o select com as options
			//echo json_encode($lista, JSON_PRETTY_PRINT);
			return $this->jsonResponse($lista, 200);
		}
	}

	public function logout() {
		$this->Ordensservico->limpacarrinho();
		$this->Orcamentos->limpacarrinho();
		return $this->redirect($this->Auth->logout());
	}

	public function isAuthorized($user) {
		// Reutiliza as regras padrão do AppController (inclui verificação de prefixo admin)
		return parent::isAuthorized($user);
	}

	public function delete($id = null) {
		if ($this->Auth->user('role') == 1) return $this->redirect(['action' => 'dashboard']);

		$user = $this->Users->get($id);

		if ($this->Users->delete($user)) {
			$this->Flash->success('O usuário foi deletado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
			if ($this->Auth->user('id') == $id) return $this->redirect(['action' => 'logout']);
			else return $this->redirect(['action' => 'index']);
		}
	}

	public function changeProfile($id = null) {
		if ($this->Auth->user('role') == 1 or $id == null) $id = $this->Auth->user('id');
		$user = $this->Users->get($id);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$this->usuarioExistente($data['email'], $id);

			if (isset($data['role'])) unset($data['role']);
			$this->Users->patchEntity($user, $data);
			// $user->username = $data['email'];

			if ($this->Users->save($user)) {
				$this->Flash->success('O perfil foi alterado com sucesso!!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['action' => 'dashboard']);
			}

			$this->Flash->error('Ocorreu um erro ao salvar os dados!');
		}

		$this->set('user', $user);
		$this->set('title', 'Alterar Perfil');
	}

	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		if ($this->Auth->user('role') == 1 or $id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);

		if (!empty($this->request->data)) {
			$user = $this->Users->patchEntity($user, [
				'old_password'  => $this->request->data['old_password'],
				'password'      => $this->request->data['password1'],
				'password1'     => $this->request->data['password1'],
				'password2'     => $this->request->data['password2']
			],
			['validate' => 'password']);

			if ($this->Users->save($user)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				if ($this->Auth->user('id') == $id) return $this->redirect(['action' => 'logout']);
				else return $this->redirect(['action' => 'dashboard']);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('user', $user);
	}

	public function changePasswordAdmin($id = null) {
		$this->set('title', 'Alterar Senha');
		
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Seu usuário não possui permissão para acessar este recurso!');
			return $this->redirect(['controller' => 'tickets', 'action' => 'indexcliente']);
		} else if ($id == $this->Auth->user('id')) {
			$this->Flash->error('Você não pode resetar sua própria senha por esta página! Por favor, acesse a troca de senha individual do usuário.');
			return $this->redirect(['action' => 'index']);
		}

		$user = $this->Users->get($id);

		if ($user->role == 0) {
			$this->Flash->error('Você não pode resetar a senha de administradores do sistema por esta página.');
			return $this->redirect(['action' => 'index']);
		}

		if (!empty($this->request->data)) {
			$user = $this->Users->patchEntity($user, [
				'password'      => $this->request->data['password1'],
				'password1'     => $this->request->data['password1'],
				'password2'     => $this->request->data['password2']
			],
			['validate' => 'passwordTecnico']);

			if ($this->Users->save($user)) {
				$this->Flash->success("A senha do usuário $user->username foi alterada com sucesso!");

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);

				return $this->redirect(['action' => 'index']);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('user', $user);
	}

	public function atendimentosSemana() {
		$inicio = primeiroDiaSemana();
		$fim = ultimoDiaSemana();

		$this->set('dataini', $inicio);
		$this->set('datafim', $fim);

		$data = $inicio;

		while ($data <= $fim) {
			$nroAtendimentosDia = $this->Atendimentos->nroAtendimentosDia($data);
			$atendimentos[] = $nroAtendimentosDia;

			$data = date('d-m-Y', strtotime("+1 days", strtotime($data)));
		}

		return $atendimentos;
	}

	public function privacyPolicy() {
		$this->viewBuilder()->setLayout("privacy_policy");
		$this->set('title', 'Políticas de Privacidade');
	}

	public function selectTemplate(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
	 		$user = $this->Users->get($this->Auth->user('id'));
		 	$user->skin = $this->request->data['skin'];
		 	$this->Users->save($user);
		}
	}

	public function selectSidebar(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->sidebar = $this->request->data['sidebar'];
			$this->Users->save($user);
		}
	}
	
	public function pagelength(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->pagelength = $this->request->data['len'];
			$this->Users->save($user);
		}
	}

	public function cadastrocliente() {
		$this->viewBuilder()->setLayout("cadastrocliente");
		
		$user = $this->Users->newEntity();
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			
			// Normalização do e-mail
			$data['email1'] = strtolower(trim($data['email1'])); 

			if (!filter_var($data['email1'], FILTER_VALIDATE_EMAIL)) {
				$this->Flash->error(__('O e-mail fornecido não é válido!'));
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}

			$cliente = $this->Clientes->findByCnpj(removeCaracteres(trim($data['cnpj'])))->first();
			if(empty($cliente)) $cliente = $this->Clientes->findByCpf(removeCaracteres(trim($data['cpfcliente'])))->first();

			if(empty($cliente)) {
				$this->Flash->error(__('Não foi localizado o cadastro do cliente através do CPF/CNPJ informado para vinculação ao novo login.'));
				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}

			$bExists = $this->usuarioExistente(trim($data['email1']));
			if (isset($bExists)) {
				$this->Flash->error(__('Já existe um usuário com o e-mail informado!'));
				$this->redirect(['controller' => 'users', 'action' => 'login']);
			}
			
			if (strcmp($data['password'], $data['confirm_password']) != 0) $this->Flash->error(__('Senhas não conferem!'));
			else {
				$user = $this->Users->patchEntity($user, $data);
				
				$user->name = trim($data['name']);
				$user->username = strtolower(trim($data['email1']));
				$user->email = strtolower(trim($data['email1']));
				$user->role = 1;
				$user->bloqueado = 1;
				$user->created = date('Y-m-d');
				$user->idcliente = trim($data['cliente']);
				$user->tipo = intval($data['tipo']);
				$user->ip = $this->request->clientIp();

				$cliente = $this->Clientes->findByCnpj(removeCaracteres(trim($data['cnpj'])))->first();
				if(empty($cliente)) $cliente = $this->Clientes->findByCpf(removeCaracteres(trim($data['cpfcliente'])))->first();

				if ($this->Users->save($user)) {
					$empresauser = $this->Empresasusers->newEntity();
					$empresauser->idempresa = $cliente->empresadominante;
					$empresauser->iduser = $user->id;
					$this->Empresasusers->save($empresauser);
					$this->email($user->id, $empresauser->idempresa);
					$this->Flash->success(__('Usuário cadastrado com sucesso! Aguarde a liberação de um administrador para efetuar o login.'));
				} else $this->Flash->error(__('Não foi possível adicionar o usuário.'));

				return $this->redirect(['controller' => 'users', 'action' => 'login']);
			}
		}
		
		$this->set('title', 'Cadastro de Cliente');
		$this->set('user', $user);
	}

	public function verificacnpjcliente(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$cnpj = $data['cnpj'];
		
			$clientes = $this->Clientes->findByCnpj(removeCaracteres($cnpj))->toArray();
			foreach($clientes as $reg) {
				if($reg->empresadominante == $reg->idempresa) {
					$cliente = $reg;
					break;
				}
			}

			if(empty($cliente)) return $this->jsonResponse(['Mensagem' => 'naopode'], 400);
			if($cliente->inativo) return $this->jsonResponse(['Mensagem' => 'inativo'], 400);
			
			return $this->jsonResponse(['IdCliente' => $cliente->id, 'RazaoSocial' => $cliente->razaosocial], 200);

		}
	}	

	public function verificacpfcliente(){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		if ($this->request->is(['post'])) {
			$data = $this->request->getData();
			$cpf = $data['cpf'];
			$clientes = $this->Clientes->findByCpf(removeCaracteres($cpf))->toArray();
			foreach($clientes as $reg) {
				if($reg->empresadominante == $reg->idempresa) {
					$cliente = $reg;
					break;
				}
			}
			
			if(empty($cliente)) return $this->jsonResponse(['Mensagem' => 'naopode'], 400);
			if($cliente->inativo) return $this->jsonResponse(['Mensagem' => 'inativo'], 400);
			
			return $this->jsonResponse(['IdCliente' => $cliente->id, 'NomeCliente' => $cliente->nome], 200);
		}
	}	

	public function verificalogincadastro($login){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->findByUsername($login)->where(['inativo' => 0])->first();
		if(empty($user)) $user = $this->Users->findByEmail($login)->where(['inativo' => 0])->first();

		if(empty($user))	$tudocerto = 'podecadastrar';
		else				$tudocerto = 'naopode';

		echo $tudocerto;
	}

	public function verificaloginedit($login){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->findByUsername($login)->where(['inativo' => 0])->first();
		if(empty($user)) $user = $this->Users->findByEmail($login)->where(['inativo' => 0])->first();

		if(empty($user))	$tudocerto = 'podecadastrar';
		else				$tudocerto = $user->name;

		echo $tudocerto;
	}

	public function verificacpf($cpf){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$cpf = removeCaracteres($cpf);
		$user = $this->Users->findByCpf($cpf)->first();

		if(empty($user)) $tudocerto = 'Não foi encontrado um usuário com este CPF!';
		else $tudocerto = $user->id;

		echo $tudocerto;
	}

	public function verificadadoscliente($idcliente, $nomeresponsavel, $cpf, $rg){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$cliente = $this->Clientes->get($idcliente);
		$tudocerto = 'tudocerto';

		if(strtoupper($nomeresponsavel) != strtoupper($cliente->nomeresponsavel)) $tudocerto = 'nao';
		if(removeCaracteres($cpf) != removeCaracteres($cliente->cpf)) $tudocerto = 'nao';
		if(removeCaracteres($rg) != removeCaracteres($cliente->rg)) $tudocerto = 'nao';

		echo $tudocerto;
	}

	public function permissaoacesso() {
		error_reporting(0);
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			foreach ($this->Users->findByIdcliente($data['idcliente']) as $user) {
				$user->permissaoacesso = 0;
				$this->Users->save($user);
			}
			foreach($data['users']['_ids'] as $id){
				$user = $this->Users->get($id);
				$user->permissaoacesso = 1;
				$this->Users->save($user);
			}
			$this->Flash->success(__('Os usuários foram salvos.'));
			return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $data['idcliente']]);
		}
	}

	public function recuperasenha($id, $destinatario) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout("ajax");

		$user = $this->Users->get($id);

		$vogais = ['a','e','i','o','u'];
		$consoantes = ['b','c','d','f','g','h','nh','lh','ch','j','k','l','m','n','p','qu','r','rr','s','ss','t','v','w','x','y','z','shim','ba','la','ie'];
		
		$novasenha = '';
		$tamanho_palavra = rand(2,7);
		$contar_silabas = 0;
		while($contar_silabas < $tamanho_palavra){
			$vogal = $vogais[rand(0,count($vogais)-1)];
			$consoante = $consoantes[rand(0,count($consoantes)-1)];
			$silaba = $consoante.$vogal;
			$novasenha .=$silaba;
			$contar_silabas++;
			unset($vogal,$consoante,$silaba);
		}

		$user->password = $novasenha;

		$message = "Sua nova senha é $novasenha";

		if ($this->Users->save($user)) {
			$email = new Email();
	
			$email->transport('master');
			$from = 'helpdesk@pgm.inf.br';
	
			$email->from([$from => 'PGM']);
			$email->to($destinatario)
				->emailFormat('html')
				->subject('Recuperação de senha - PGM');
	
			if($email->send($message)) echo 'Verifique seu e-mail!';

		} else echo 'Erro ao recuperar a senha!';
	
		
	}

	public function email($iduser, $idempresa) {
		$user = $this->Users->get($iduser);
		$empresa = $this->Empresas->get($idempresa);
		$cliente = $this->Clientes->findById($user->idcliente)->first();

		if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
		else $nomeempresa = $empresa->razaosocial;

		$message = 
		"<h3> Novo usuário cadastrado! </h3>
		<h3> O usuário $user->name do cliente $cliente->razaosocial foi cadastrado e está aguardando liberação. </h3>";
		$emailDest = $this->Config->get(1)->emailtickets;
		$subject = "Novo usuário cadastrado";

		$email = new Email();

		$email->transport('pgm');
		$from = 'helpdesk@pgm.inf.br';
		
		$email->from([$from => $nomeempresa])
			->to($emailDest)
			// ->to('joaomario3224@gmail.com')
			->emailFormat('html')
			->subject($subject);

		$email->send($message);
	}

	public function verificasenha($senhaadm) {
        $this->autoRender = false;
		$empresa = $this->Empresas->get(C_EmpresaPGM);
        if($empresa->senhaadministrativa == criptografasenha($senhaadm)) return $this->jsonResponse(['Mensagem' => 'Senha correta'], 200);
		else return $this->jsonResponse(['Mensagem' => 'Senha administrativa incorreta'], 400);
	}

	public function loginduasetapas() {
		$user = $this->Users->get($this->Auth->user('id'));
		if(empty($user->secret)) $this->set('bAutenticacao', false);
		else {
			$urlQRCode = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($user->username, $user->secret, 'PGM');
			$this->set('urlQRCode', $urlQRCode);
			$this->set('bAutenticacao', true);
		}
		if ($this->request->is('post')) {
			if($this->request->getData('duasetapas') == 'ativa') {
				$g = new \Google\Authenticator\GoogleAuthenticator();
				$secret = $g->generateSecret();
				$user->secret = $secret;
				$this->Users->save($user);
				$urlQRCode = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($user->username, $secret, 'PGM');
				$this->set('urlQRCode', $urlQRCode);
				$this->Flash->success('A verificação em duas etapas foi ativada com sucesso! Leia o código com o Google Authenticator App');
			} else if($this->request->getData('duasetapas') == 'tira') {
				$user->secret = null;
				if($this->Users->save($user)){
					$this->Flash->success('A verificação em duas etapas foi desativada com sucesso!');
					return $this->redirect(['action' => 'dashboard']);
				}
			}				
		}
	}

	public function verificacodigo($username, $code) {
		$this->autoRender = false;
		$user = $this->Users->findByUsername($username)->first();
		if(empty($user)) $user = $this->Users->findByEmail(trim($username))->first();
		$g = new \Google\Authenticator\GoogleAuthenticator();
		$secret = $user->secret;

		$code = str_replace(' ', '', $code);

		if ($g->checkCode($secret, $code)) echo 'sucesso';
		else echo 'erro';
	}

	public function verificaloginduasetapas($username) {
		$this->autoRender = false;
		$user = $this->Users->findByUsername(trim($username))->first();
		if(empty($user)) $user = $this->Users->findByEmail(trim($username))->first();
		if (empty($user->secret)) echo 'naotemcodigo';
		else echo 'temcodigo';
	}

	public function desativaverificacao() {
		$user = $this->Users->get($this->Auth->user('id'));
	
		if ($this->request->is(['post', 'put'])) {
			$user = $this->Users->patchEntity($user, [
				'old_password'  => $this->request->data['senha'],
				'password'      => $this->request->data['senha'],
			],
			['validate' => 'password']);

			if(!empty($user->getErrors()['old_password'])) {
				$this->Flash->error('A senha informada está incorreta!');
				return $this->redirect(['action' => 'desativaverificacao']);
			}

			$g = new \Google\Authenticator\GoogleAuthenticator();
			$secret = $user->secret;

			if (!$g->checkCode($secret,  $this->request->data['codigo'])) {
				$this->Flash->error('O código informado está incorreto!');
				return $this->redirect(['action' => 'desativaverificacao']);
			} 
			
			$user->secret = null;
			if($this->Users->save($user)){
				$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
				return $this->redirect(['action' => 'dashboard']);
			}
		}
		$this->set('title', 'Desativar verificação');
		$this->set('user', $user);
	}

	public function usuarioExistente($email, $iduser = null) {
		$userExistente = $this->Users->findByUsername($email)->where(['inativo' => 0])->first();
		if(empty($userExistente)) $userExistente = $this->Users->findByEmail($email)->where(['inativo' => 0])->first();
		if(!empty($userExistente) && $userExistente->id != $iduser) {
			//$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário "' . $userExistente->username . '"');
			$this->Flash->error('Já existe um usuário com este e-mail no sistema, verifique e inative o usuário correspondente.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	public function resetPassword($iduser) {
		if(!is_numeric($iduser)) $user = $this->Users->findByEmail($iduser)->first();
		else $user = $this->Users->findById($iduser)->first();

		if(empty($user)) {
			$this->Flash->error('Não foi encontrado um usuário com o email informado!');
			return $this->redirect(['action' => 'dashboard']);
		}

		if(empty($this->Auth->user('idempresa'))) $idempresa = 2;
		else $idempresa = $this->Auth->user('idempresa');
		$urlfora = $this->Config->get(1)->urlfora;
		$empresa = $this->Empresas->get($idempresa);

		if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
		else $nomeempresa = $empresa->razaosocial;

		$user->hashreset = criptografaSenha($user->id.$user->name.date('d/m/Y|H:i:s'));
		$user->hashreset = removeCaracteres($user->hashreset);
		$this->Users->save($user);

		$link = $urlfora . "Users/resetPasswordNew?hash=$user->hashreset";
		
		$message = 
		"<h3> Redefinição de senha! </h3>
		<h3> Redefina sua senha <a href='$link'> clicando aqui </a>!";
		
		$email = new Email();

		$email->transport('pgm');
		$from = 'helpdesk@pgm.inf.br';
		$email->from([$from => $nomeempresa])->to($user->email)->emailFormat('html')->subject('Redefinição de senha');
		$email->send($message);

		$this->Flash->success('Email para redefiniçao de senha enviado!');
		return $this->redirect(['action' => 'dashboard']);
	}

	public function resetPasswordNew() {
		$this->viewBuilder()->setLayout("login");
		extract($this->request->getQuery());

		$user = $this->Users->findByHashreset($hash)->first();

		if(empty($user)) {
			$this->Flash->success('Não foi encontrado um usuário válido!');
			return $this->redirect(['action' => 'login']);
		}

		if (!empty($this->request->data)) {

			$user = $this->Users->patchEntity($user, [
				'password'      => $this->request->data['password1'],
				'password1'     => $this->request->data['password1'],
				'password2'     => $this->request->data['password2']
			],
			['validate' => 'password']);
			// Invalida o hash de redefinição após o uso para evitar reutilização do link
			$user->hashreset = null;

			if ($this->Users->save($user)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $user->id);

				if ($this->Auth->user('id') == $user->id) return $this->redirect(['action' => 'logout']);
				else return $this->redirect(['action' => 'dashboard']);
			} else $this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('user', $user);
		$this->set('title', 'Redefinição de senha');
	}

	public function enviaEmailAutenticacaoSemLogin($destinatario) {

		if(($destinatario)) $user = $this->Users->findByEmail($destinatario)->first();
		else $user = NULL;

		if(empty($user)) {
			$this->Flash->error('Não foi encontrado um usuário com o email informado!');
			return $this->redirect(['action' => 'logout']);
		}
			$email = new Email();
	
			$email->transport('master');
			$from = 'helpdesk@pgm.inf.br';
			
			$urlfora = $this->Config->get(1)->urlfora;

			$link = $urlfora . "Users/desativaverificacaosemlogin?hash=$user->hashreset";
			
			$message = 
			"<h3> Desativação de autenticação 2F! </h3>
			<h3> Desative sua autenticação 2F <a href='$link'> clicando aqui </a>!";

			$email->from([$from => 'PGM']);
			$email->to($destinatario)
				->emailFormat('html')
				->subject('Desativação de autenticação 2F - PGM');
	
			if($email->send($message)) {
				$this->Flash->success('Email para desativação de 2FA enviado!');
				return $this->redirect(['action' => 'dashboard']);
			}
			else echo 'Não foi encontrado nenhum usuário válido com este email!';
	}

	//possibilita qualquer usuario desabilitar sua 2FA sem fazer login
	public function desativaverificacaosemlogin() {
		$this->viewBuilder()->setLayout("login");
	
		if ($this->request->is(['post', 'put'])) {

			if ($this->Auth->user()) $this->redirect($this->Auth->redirectUrl());

			$data = $this->request->getData();
	
			$user = $this->Users->findByEmail($data['username'])->where(['inativo' => 0])->first();
			if(empty($user)) $user = $this->Users->findByUsername($data['username'])->where(['inativo' => 0])->first();
		
			if(!empty($user)) $data['username'] = $user->username;
	
			$logou = $this->Auth->identify($data);
	
			if (!$logou) {
				$this->Flash->error(__('Usuário e/ou senha incorretos. Tente novamente.'));
			} 
			
			$user['secret'] = null;

			if($this->Users->save($user)){
				$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
				return $this->redirect(['action' => 'dashboard']);
			}
		}
		$this->set('title', 'Desativar verificação');
	}

	//possibilita o adm desabilitar a 2FA de qualquer usuário
	public function desativaverificacaoqualqueruser($id) {
		if (!$this->Auth->user('admin')) {
			$this->Flash->error('Você não possui permissão para desativar a autenticação de dois fatores. Contate um administrador do sistema.');
			return $this->redirect(['action' => 'dashboard']);
		}

		if ($id == null) $id = $this->Auth->user('id');

		$user = $this->Users->get($id);
		$user['secret'] = null;

		if($this->Users->save($user)){
			$this->Flash->success('A verificação em duas etapas foi desativada com sucesso! Remova a conta no Google Authenticator App');
			return $this->redirect(['action' => 'index']);
		}
	}
}
