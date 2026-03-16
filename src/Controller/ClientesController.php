<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
Use Cake\Datasource\ConnectionManager;
use CakeSoap\Network\CakeSoap;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . '/queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/queencitycodefactory/cakesoap/src/Network/CakeSoap.php';

class ClientesController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Cidades');
		$this->loadModel('Estados');
		$this->loadModel('Servicos');
		$this->loadModel('Clientes');
		$this->loadModel('Cliacessos');
		$this->loadModel('Clicontratos');
		$this->loadModel('Visitas');
		$this->loadModel('Empresas');
		$this->loadModel('Users');
		$this->loadModel('Config');
		$this->loadModel('Empresas');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Clientes');
		$this->Auth->allow(['addApi', 'listApi']);
	}

	public function index() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$this->set('title', 'Lista de Clientes');

		$clientesAtivos = $this->Clientes->find('all')
			->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])
			->toArray();

		$clientesInativos = $this->Clientes->find('all')
			->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 1])
			->toArray();

		$this->set('clientesAtivos', $clientesAtivos);
		$this->set('clientesInativos', $clientesInativos);

		$this->set('clientesAtivosPJ', array_values(array_filter($clientesAtivos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoJuridica; })));
		$this->set('clientesAtivosPF', array_values(array_filter($clientesAtivos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoFisica; })));
		$this->set('clientesInativosPJ', array_values(array_filter($clientesInativos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoJuridica; })));
		$this->set('clientesInativosPF', array_values(array_filter($clientesInativos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoFisica; })));
	}

	public function cadastrar() {
		$clientesAtivos = sizeof($this->Clientes->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->toArray());
		$clientesInativos = sizeof($this->Clientes->findByInativo(1)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray());
		
		$this->set('title', 'Cadastro de Clientes');
		$this->set('clientesAtivos', $clientesAtivos);
		$this->set('clientesInativos', $clientesInativos);
	}

	public function view($id) {
		$cliente = $this->Clientes->get($id);
		$cidade = $this->Cidades->findById($cliente->idcidade);
		$cidade = $cidade->toList();
		$this->set(compact('cliente'));
		$this->set(compact('cidade'));
	}

	public function search() {
		$this->set('title', 'Pesquisa de Clientes');
		if ($this->request->is('get')) {
			$keywords = $this->request->getQuery('keywords');
			$clientes = array();

			if ($keywords) {
				$array = explode(" ", $keywords);

				foreach ($array as $data) {
					$data = strtolower($data);
					$query = $this->Clientes->find('all', [
						'conditions' => ['AND' => [
							['idempresa' => $this->Auth->user('idempresa')],
							['OR' =>[['LOWER(Clientes.razaosocial) LIKE' => '%' . $data . '%'], ['LOWER(Clientes.nomefantasia) LIKE' => '%' . $data . '%'], 
							['LOWER(Clientes.nome) LIKE' => '%' . $data . '%']]]]]
					])->toArray();

					$clientes = array_unique(array_merge($clientes, $query), SORT_REGULAR);
				}
				
				foreach($clientes as $key=>$reg){
					$reg->controller = 'Clientes';
					if($reg->inativo == 0) 	$clientes[$key]->search =  '<span class="label label-info">Ativo</span>';
					else if($reg->inativo == 1) $clientes[$key]->search = '<span class="label label-danger">Inativo</span>';
				}
			}
		}

		$this->set('clientes', $clientes);
	}
		
	public function add() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$this->set('title', 'Adicionar Cliente');
		$cliente = $this->Clientes->newEntity();

		if (!empty($this->request->data) ) {
			$data = $this->request->getData();
			if($data['tipo'] == C_ClientesTipoFisica) $clientequejaexiste = $this->Clientes->findByCpf($data['cpf'])->where(['tipo' => C_ClientesTipoFisica, 'idempresa' => $this->Auth->user('idempresa')])->first();
			else $clientequejaexiste = $this->Clientes->findByCnpj($data['cnpj'])->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			if(empty($clientequejaexiste)){
				if (!isset($data['inativo'])) $data['inativo'] = '0';

				// Geração do token
				$cpfoucnpj = isset($data['cnpj']) ? $data['cnpj'] : $data['cpf'];
				$string = $this->Auth->user('id') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
				$data['token'] = $this->Clientes->generateToken($string);
			
				$cliente = $this->Clientes->patchEntity($cliente, $data);
				$cliente->membrodesde = date('d/m/y');
				$cliente->idempresa = $this->Auth->user('idempresa');
				if(!empty($data['cnpj'])) $cliente->cnpj = removeCaracteres($data['cnpj']);
				if(!empty($data['cpf'])) $cliente->cpf = removeCaracteres($data['cpf']);
				if(!empty($data['inscricaoestadual'])) $cliente->inscricaoestadual = removeCaracteres($data['inscricaoestadual']);
				if(!empty($data['inscricaomunicipal'])) $cliente->inscricaomunicipal = removeCaracteres($data['inscricaomunicipal']);
				if(!empty($data['cep'])) $cliente->cep = removeCaracteres($data['cep']);
	
				if ($this->Clientes->save($cliente)) {
					$this->sincronizacliente($cliente->id);
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);
					$this->Flash->success(__('O cliente foi salvo.'));
					return $this->redirect(['action' => 'index']);
				}
				$this->Flash->error(__('Não foi possível adicionar o cliente.'));
			} else $this->Flash->error(__('Já existe um cliente cadastrado com este CPF/CNPJ.'));
		}

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('cidades', $cidades);
		$this->set('cliente', $cliente);
	}

	public function edit($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1){
			if(!$this->Auth->user('permissaoacesso')) return $this->redirect(['controller' => 'Tickets', 'action' => 'indexcliente']);	
			if($this->Auth->user('idcliente') != $id) return $this->redirect(['controller' => 'Clientes', 'action' => 'edit', $this->Auth->user('idcliente')]);
		} 

		$cliente = $this->Clientes->get($id);
		$titlenome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		$this->set('title', 'Cliente: ' . $titlenome);
		
		// Todos os usuários relacionados a este cliente:
		// - vinculados diretamente por idcliente
		// - ou associados a um cliente com mesmo CNPJ/CPF (casos de cadastros antigos/dominantes)
		$usuariosQuery = $this->Users
			->find('all')
			->contain(['Clientes' => ['fields' => ['id', 'razaosocial', 'nome', 'cnpj', 'cpf']]])
			->order(['Users.username' => 'ASC']);

		$conditions = ['Users.idcliente' => $id];

		if (!empty($cliente->cnpj)) {
			$conditions = [
				'OR' => [
					['Users.idcliente' => $id],
					['Clientes.cnpj IS NOT' => null, 'Clientes.cnpj' => $cliente->cnpj],
				],
			];
		} elseif (!empty($cliente->cpf)) {
			$conditions = [
				'OR' => [
					['Users.idcliente' => $id],
					['Clientes.cpf IS NOT' => null, 'Clientes.cpf' => $cliente->cpf],
				],
			];
		}

		$usuarios = $usuariosQuery->where($conditions)->toArray();
		$cliente->users = $this->Users->find('all')->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$usuariosValue = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$cliente->senha = descriptografasenha($cliente->senha);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			// #region agent log
			@file_put_contents(
				ROOT . DS . 'debug-cb94ed.log',
				json_encode([
					'sessionId' => 'cb94ed',
					'runId' => 'clientes_edit_preSave',
					'hypothesisId' => 'H_emails_validation',
					'location' => 'ClientesController.php:edit:preSave',
					'message' => 'Edit cliente POST received',
					'data' => [
						'id' => $id,
						'has_email' => array_key_exists('email', $data),
						'has_emailresponsavel' => array_key_exists('emailresponsavel', $data),
					],
					'timestamp' => round(microtime(true) * 1000),
				]) . PHP_EOL,
				FILE_APPEND
			);
			// #endregion agent log

			$cliente = $this->Clientes->patchEntity($cliente, $data);
			if(!empty($data['cpf'])) $cliente->cpf = removeCaracteres($data['cpf']);
			if(!empty($data['senha'])) $cliente->senha = criptografasenha($data['senha']);

			if ($this->Clientes->save($cliente)) {
				$this->sincronizacliente($id);
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);

				// #region agent log
				@file_put_contents(
					ROOT . DS . 'debug-cb94ed.log',
					json_encode([
						'sessionId' => 'cb94ed',
						'runId' => 'clientes_edit_postSave',
						'hypothesisId' => 'H_emails_validation',
						'location' => 'ClientesController.php:edit:postSave',
						'message' => 'Cliente saved successfully',
						'data' => [
							'id' => $cliente->id,
						],
						'timestamp' => round(microtime(true) * 1000),
					]) . PHP_EOL,
					FILE_APPEND
				);
				// #endregion agent log

				$this->Flash->success(__('O cliente foi salvo.'));
				return $this->redirect(['action' => 'edit', $cliente->id]);
			}

			// #region agent log
			@file_put_contents(
				ROOT . DS . 'debug-cb94ed.log',
				json_encode([
					'sessionId' => 'cb94ed',
					'runId' => 'clientes_edit_saveError',
					'hypothesisId' => 'H_emails_validation',
					'location' => 'ClientesController.php:edit:saveError',
					'message' => 'Failed to save cliente',
					'data' => [
						'id' => $id,
						'errors' => $cliente->getErrors(),
					],
					'timestamp' => round(microtime(true) * 1000),
				]) . PHP_EOL,
				FILE_APPEND
			);
			// #endregion agent log

			$this->Flash->error(__('Não foi possível salvar o cliente.'));
		}

		$hoje = date('d/m/Y');
		$mes = date('01/m/Y');

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$acessos = $this->Cliacessos->find('all')->order(['nome'])->where(['idcliente' => $id])->toArray();
		$contratos = $this->Clicontratos->find('all')->where(['idcliente' => $id])->toArray();

		$this->set('acessos', $acessos);
		$this->set('contratos', $contratos);
		$this->set('cidades', $cidades);
		$this->set('cliente', $cliente);	
		$this->set('usuarios', $usuarios);	
		$this->set('usuariosValue', $usuariosValue);	
	}

	public function cidadesestado($idcidade){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		$cidade = $this->Cidades->find('all')->where(['id' => $idcidade])->toArray();
		$estado = $this->Estados->findById($cidade[0]->idestado)->toArray();;

		echo $estado[0]->sigla;
	}

	public function delete($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->Clientes->get($id);

		// Garante que o cliente pertence à mesma empresa do usuário logado
		if ($cliente->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para excluir este cliente.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->Clientes->delete($cliente)) {
			$this->Flash->success('O cliente foi deletado com sucesso!');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['action' => 'index']);
		}
	}

	public function reativar($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->Clientes->get($id);
		$cliente->inativo = 0;

		if ($this->Clientes->save($cliente)) {
			$this->sincronizacliente($id);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success('O cliente foi reativado com sucesso!');
		} else {
			$this->Flash->error('Não foi possível reativar o cliente.');
		}

		return $this->redirect(['action' => 'index', '#' => 'inativos']);
	}

	public function solicitantes($idcliente) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');
	
		if ($this->request->is('ajax')) {
			$solicitantes = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['name'])->where(['idcliente' => $idcliente, 'inativo' => 0])->toArray();
			/* $solicitantes = [0 => 'Outros'] + $solicitantes; */
			
			return $this->jsonResponse($solicitantes, 200);
		}
	}

	public function solicitante($idsolicitante) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->where(['id' => $idsolicitante, 'inativo' => '0'])->toArray();
			return $this->jsonResponse($solicitante, 200);
		}
	}

	public function cliemail($idcliente) {
		$this->autoRender = false;

		if ($this->request->is('ajax')) {
			$cliente = $this->Clientes->find('all')->where(['id' => $idcliente, 'inativo' => '0', 'idempresa' => $this->Auth->user('idempresa')])->first();
			return $this->jsonResponse($cliente, 200);
		}
	}

	public function solemail($idsolicitante) {
		$this->autoRender = false;

		if ($this->request->is('ajax')) {
			$contato = $this->Users->find('all')->where(['id' => $idsolicitante, 'inativo' => 0])->first();
			return $this->jsonResponse($contato, 200);
		}
	}

	public function clientebyid($idsclientes){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');
		
		$idsclientes = explode(',', $idsclientes);

		if ($this->request->is('ajax')) {
			foreach($idsclientes as $id){
				$clientes[] = $this->Clientes->findById($id)
					->select([ 'id', 'razaosocial'])
					->toArray();
			}
			//echo json_encode($clientes, JSON_PRETTY_PRINT);
			return $this->jsonResponse($clientes, 200);
		}
	}

	public function consultacnpj($cnpj = null) {
		$this->autoRender = false;

		if (!$this->request->is('ajax')) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Requisição inválida'], 400);
		}

		$cnpj = preg_replace('/\D+/', '', (string)($cnpj ?? ''));
		if (strlen($cnpj) !== 14) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'CNPJ inválido'], 400);
		}

		$url = 'https://www.receitaws.com.br/v1/cnpj/' . $cnpj;

		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => 15,
				'header' => [
					'Accept: application/json',
				],
			],
		]);

		$result = @file_get_contents($url, false, $context);
		if ($result === false) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Falha ao acessar serviço de CNPJ'], 502);
		}

		$data = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $this->jsonResponse(['status' => 'ERROR', 'message' => 'Retorno inválido do serviço de CNPJ'], 502);
		}

		// Tenta localizar a cidade (idcidade) a partir do município + UF (comparação sem acentos)
		if (!empty($data['municipio'])) {
			$municipioNorm = $this->normalizaTextoParaBusca($data['municipio']);
			$uf = !empty($data['uf']) ? strtoupper(trim($data['uf'])) : null;

			$query = $this->Cidades->find('all');
			if ($uf) {
				$estado = $this->Estados->find()->where(['sigla' => $uf])->first();
				if ($estado) {
					$query->where(['idestado' => $estado->id]);
				}
			}
			$cidadesList = $query->toArray();

			foreach ($cidadesList as $c) {
				if ($this->normalizaTextoParaBusca($c->nome) === $municipioNorm) {
					$data['idcidade'] = $c->id;
					break;
				}
			}
		}

		return $this->jsonResponse($data, 200);
	}

	/**
	 * Normaliza texto para busca (maiúsculas, sem acentos) para comparar nomes de cidade.
	 */
	private function normalizaTextoParaBusca($texto) {
		$t = mb_strtoupper(trim((string)$texto), 'UTF-8');
		$map = ['Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','Ä'=>'A','Ç'=>'C','É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E','Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I','Ó'=>'O','Ò'=>'O','Õ'=>'O','Ô'=>'O','Ö'=>'O','Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U'];
		return strtr($t, $map);
	}

	public function updateToken($idcliente) {
		$this->autoRender = false;

		$cliente = $this->Clientes->findById($idcliente, ['fields' => ['id', 'cnpj', 'token', 'cpf']])->first();
		// Gera o token
		$cpfoucnpj = isset($cliente->cnpj) ? $cliente->cnpj : $cliente->cpf;
		$string = $this->Auth->user('idempresa') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
		// Atualiza o token
		$cliente->token = $this->Clientes->generateToken($string);
		if($this->Clientes->save($cliente)){
			$this->Flash->success(__('O token foi atualizado com sucesso.'));
			return $this->redirect(['action' => 'edit', $idcliente]);
		}
	}

	public function contrato($id){
		$this->autoRender = false;

		$cliente = $this->Clientes->get($id);

		// Garante que o cliente pertence à mesma empresa
		if ($cliente->idempresa != $this->Auth->user('idempresa')) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		// Clientes só podem ver o próprio contrato (quando vinculados)
		if ($this->Auth->user('role') == C_RoleCliente && $this->Auth->user('idcliente') != $id) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		echo $cliente->contrato;
	}

	public function addAPI() {
		$this->autoRender = false;

        if ($this->request->is('post')) {
			$empresa = $this->request->getHeaderLine('empresa');
            $token = $this->request->getHeaderLine('token');
			$json = $this->request->input('json_decode');

			$apiRet = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};
			if(empty($token)) return $apiRet("O token não foi informado", 400);
			if(empty($empresa)) return $apiRet("O ID da empresa não foi informado", 400);
			if(empty($json)) return $apiRet("O JSON não foi informado", 400);
			if(empty($this->Empresas->findById($empresa)->first())) return $apiRet("Não foi encontrada uma empresa com o ID ($empresa) informado", 400);

			if($token == $this->Empresas->get($empresa)->token) {
				$retorno['CNPJ'] = removeCaracteres($json->cnpj);
				$retorno['Empresa'] = $empresa;

				$tipo = strlen($retorno['CNPJ']) > 11 ? 'j' : 'f' ;

				if($tipo == 'j') $cliente = $this->Clientes->findByCnpj($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa']])->first(); 
				else $cliente = $this->Clientes->findByCpf($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa'], 'tipo' => C_ClientesTipoFisica])->first();

				if ($cliente == null){
					$cliente = $this->Clientes->newEntity();
					$string = $empresa . $retorno['CNPJ'] .  date('d/m/y') .  date('H:i');
					$cliente->token = $this->Clientes->generateToken($string);
				} 

				if($tipo == 'j'){
					$cliente->razaosocial = strtoupper($json->nome);
					$cliente->nome = ' ';
					$cliente->cnpj = $retorno['CNPJ'];
					$cliente->tipo = C_ClientesTipoJuridica;
				} else{
					$cliente->nome = strtoupper($json->nome);
					$cliente->cpf = $retorno['CNPJ'];
					$cliente->tipo = C_ClientesTipoFisica;
				}
				$cliente->inscricaoestadual = $json->inscest;
				$cliente->membrodesde = date('d/m/y');
				$cliente->idempresa = $empresa;
				$cliente->endereco = $json->endereco;
				$cliente->nroendereco = $json->nroendereco;
				$cliente->complemento = $json->complemento;
				$cliente->bairro = $json->bairro;
				$cliente->cep = $json->cep;
				if(isset($json->telefone)) $cliente->fone = $json->telefone;
				if(isset($json->celular)) $cliente->fone2 = $json->celular;
				$cliente->email = $json->email;
				$cliente->contrato = $json->contrato;
				$cliente->nomefantasia = $json->fantasia;
				$cliente->inativo = 0;
				$cliente->idcidade = $this->Cidades->findByCodibge($json->codibge)->first()->id;
				$cliente->empresadominante = 2; // fixo pgm

				if($this->Clientes->save($cliente)) {
					$deuerro = 'não';
					$contratos = $this->Clicontratos->find('all')->where(['idempresa' => $empresa, 'idcliente' => $cliente->id])->toArray();
					foreach($contratos as $reg) $this->Clicontratos->delete($reg);
					foreach($json->Servicos as $servico){
						// $contrato = $this->Clicontratos->findByCodproduto($servico->codproduto)->where(['idempresa' => $empresa, 'idcliente' => $cliente->id])->first();
						// if(empty($contrato)) $contrato = $this->Clicontratos->newEntity();
						$contrato = $this->Clicontratos->newEntity();
							$contrato->iderp = $servico->idERP;
							$contrato->codproduto = $servico->codproduto;
							$contrato->descricao = $servico->descricao;
							$contrato->infadicional = $servico->infadicional;
							$contrato->vlunit = $servico->vlunit;
							$contrato->qtde = $servico->qtde;
							$contrato->vltotal = $servico->vltotal;
							if(!empty($servico->dtcontratacao))$contrato->dtcontratacao = $servico->dtcontratacao;
							if(!empty($servico->dtvalidade))$contrato->dtvalidade = $servico->dtvalidade;
							if(!empty($servico->dtcancelamento))$contrato->dtcancelamento = $servico->dtcancelamento;
							$contrato->idcliente = $cliente->id;
							$contrato->idempresa = $empresa;
						if($this->Clicontratos->save($contrato)) $contratos[] = $contrato;
						else $deuerro = 'sim';
					}
					if(  $deuerro == 'não' ) return $apiRet("Cliente cadastrado/atualizado com sucesso", 201);
					else return $apiRet("Houve um erro ao salvar os contratos do cliente. Json recebido: $json", 400);
				} else return $apiRet("Houve um erro ao cadastrar/atualizar o cliente. Json recebido: $json", 400);
			} else return $apiRet("Autenticação Inválida", 401);
        }
	}
	
	public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			$empresa = $this->request->getHeaderLine('empresa');
            $token = $this->request->getHeaderLine('token');
            $cnpj = $this->request->getHeaderLine('cnpj');

			$apiRetList = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};
			if(empty($token) || empty($empresa)) 
			return $apiRetList('Parâmetros da requisição inválidos', 400);

			if(empty($this->Empresas->findById($empresa)->first())) return $apiRetList('Parâmetros da requisição inválidos', 400);
			if($token == $this->Empresas->get($empresa)->token){
				$retorno['CNPJ'] = removeCaracteres($cnpj);
				$retorno['Empresa'] = $empresa;

				if(!empty($cnpj)){
					$tipo = strlen($retorno['CNPJ']) > 11 ? 'j' : 'f' ;
					if($tipo == 'j') $cliente = $this->Clientes->findByCnpj($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa']])->toArray();
					else $cliente = $this->Clientes->findByCpf($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa'], 'tipo' => C_ClientesTipoFisica])->toArray();
					if ($cliente == null) return $apiRetList('Não foi encontrado um cliente com o CNPJ/CPF '. $cnpj, 404);
				} else {
					$cliente = $this->Clientes->find('all')->where(['idempresa' => $retorno['Empresa']])->toArray(); 
				}
				foreach($cliente as $reg){
					$reg = $this->Clientes->clicontratosArr($reg);
					$reg = $this->Clientes->clientesArr($reg);
				} 
				return $this->jsonResponse($cliente, 200);
			} else {
				return $apiRetList('Autenticação Inválida', 401);
			}
		}
	}

	public function sincronizacliente($idcliente) {
		$cliente = $this->Clientes->get($idcliente);
		$cliente = $arr = ['Cliente' => $this->Clientes->clientesArr($cliente)];

		$json = json_encode($cliente, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$soap = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WSPGMPessoas.wso?wsdl';
		try {
			@$soap = new CakeSoap(['wsdl' => $soap]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			$this->Flash->error(__('O WS não pode ser acessado no momento!'));
			return $this->redirect(['controller' => 'Clientes', 'action' => 'index']);
		}
	
		$response = $soap->sendRequest('GerenciaCliente', [
			'Data' => [
				'iEmpresa' => $this->Auth->user('idempresa'),
				'sToken' => $this->Empresas->get($this->Auth->user('idempresa'))->token,
				'sJSON' => $json,
			]
		]);

		if(!in_array($response->GerenciaClienteResult->cStatus, [201, 200])) $this->Flash->error($response->GerenciaClienteResult->sMsg);
		// else  $this->Flash->success('Não foi possível sincronizar o cliente com o ERP!');
	}
}
