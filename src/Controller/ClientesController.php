<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
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
	}

	/**
	 * Cliente do ID informado, restrito à empresa do utilizador (mitiga IDOR).
	 *
	 * @param int|string|null $id
	 * @return \App\Model\Entity\Cliente|null
	 */
	protected function _findClienteForCurrentUser($id) {
		if ($id === null || $id === '') {
			return null;
		}
		$q = $this->Clientes->find()->where(['id' => (int) $id]);
		$this->Abac->applyToQuery($q, 'Clientes');

		return $q->first();
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Clientes');
		$this->Auth->allow(['addApi', 'listApi', 'addAPI', 'listAPI']);
	}

	public function index() {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$this->set('title', 'Lista de Clientes');
		$this->set('hideLayoutPageTitle', true);

		$qAtivos = $this->Clientes->find('all')->where(['inativo' => 0]);
		$this->Abac->applyToQuery($qAtivos, 'Clientes');
		$clientesAtivos = $qAtivos->toArray();

		$qInativos = $this->Clientes->find('all')->where(['inativo' => 1]);
		$this->Abac->applyToQuery($qInativos, 'Clientes');
		$clientesInativos = $qInativos->toArray();

		$this->set('clientesAtivos', $clientesAtivos);
		$this->set('clientesInativos', $clientesInativos);

		$this->set('clientesAtivosPJ', array_values(array_filter($clientesAtivos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoJuridica; })));
		$this->set('clientesAtivosPF', array_values(array_filter($clientesAtivos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoFisica; })));
		$this->set('clientesInativosPJ', array_values(array_filter($clientesInativos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoJuridica; })));
		$this->set('clientesInativosPF', array_values(array_filter($clientesInativos, function($c){ return (int)$c->tipo === (int)C_ClientesTipoFisica; })));
	}

	public function cadastrar() {
		return $this->redirect(['action' => 'add']);
	}

	public function view($id) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado.'));
			return $this->redirect(['action' => 'index']);
		}
		$cidade = null;
		if (!empty($cliente->idcidade)) {
			$cidade = $this->Cidades->get($cliente->idcidade);
		}
		$this->set(compact('cliente', 'cidade'));
	}

	/**
	 * Pesquisa server-side (tela search.ctp): mesma regra de formatos da lista — e-mail, CNPJ/CPF (apenas dígitos e máscara) ou nome (palavras em nome/razão/fantasia/e-mail).
	 *
	 * @param string $keywords
	 * @return \App\Model\Entity\Cliente[]
	 */
	protected function _findClientesPorKeywords($keywords) {
		$kw = trim((string)$keywords);
		if ($kw === '') {
			return [];
		}
		if (mb_strpos($kw, '@') !== false) {
			$email = mb_strtolower($kw, 'UTF-8');
			$q = $this->Clientes->find('all')->where([
				'LOWER(Clientes.email) LIKE' => '%' . $email . '%',
			]);
			$this->Abac->applyToQuery($q, 'Clientes');

			return $q->toArray();
		}

		$digits = preg_replace('/\D/', '', $kw);
		if (preg_match('/^[\d\s.\-\/\(\)]+$/u', $kw) && strlen($digits) >= 3) {
			$q = $this->Clientes->find('all')->where([
				'OR' => [
					['Clientes.cnpj LIKE' => '%' . $digits . '%'],
					['Clientes.cpf LIKE' => '%' . $digits . '%'],
				],
			]);
			$this->Abac->applyToQuery($q, 'Clientes');

			return $q->toArray();
		}

		$words = preg_split('/\s+/', mb_strtolower($kw, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
		if (empty($words)) {
			return [];
		}

		$q = $this->Clientes->find('all');
		$this->Abac->applyToQuery($q, 'Clientes');
		foreach ($words as $w) {
			$q->andWhere([
				'OR' => [
					['LOWER(Clientes.razaosocial) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.nomefantasia) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.nome) LIKE' => '%' . $w . '%'],
					['LOWER(Clientes.email) LIKE' => '%' . $w . '%'],
				],
			]);
		}

		return $q->toArray();
	}

	public function search() {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$this->set('title', 'Pesquisa de Clientes');
		$clientes = [];
		if ($this->request->is('get')) {
			$keywords = $this->request->getQuery('keywords');

			if ($keywords) {
				$clientes = $this->_findClientesPorKeywords($keywords);

				foreach ($clientes as $key => $reg) {
					$reg->controller = 'Clientes';
					if ($reg->inativo == 0) {
						$clientes[$key]->search = '<span class="label label-info">Ativo</span>';
					} elseif ($reg->inativo == 1) {
						$clientes[$key]->search = '<span class="label label-danger">Inativo</span>';
					}
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
		$this->set('hideLayoutPageTitle', true);
		$cliente = $this->Clientes->newEntity();

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			if ($data['tipo'] == C_ClientesTipoFisica) {
				$qDup = $this->Clientes->findByCpf($data['cpf'])->where(['tipo' => C_ClientesTipoFisica]);
				$this->Abac->applyToQuery($qDup, 'Clientes');
				$clientequejaexiste = $qDup->first();
			} else {
				$qDup = $this->Clientes->findByCnpj($data['cnpj']);
				$this->Abac->applyToQuery($qDup, 'Clientes');
				$clientequejaexiste = $qDup->first();
			}
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

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado ou sem permissão.'));
			return $this->redirect(['action' => 'index']);
		}
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

			$cliente = $this->Clientes->patchEntity($cliente, $data);
			if(!empty($data['cpf'])) $cliente->cpf = removeCaracteres($data['cpf']);
			if(!empty($data['senha'])) $cliente->senha = criptografasenha($data['senha']);

			if ($this->Clientes->save($cliente)) {
				$this->sincronizacliente($id);
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);

				$this->Flash->success(__('O cliente foi salvo.'));
				return $this->redirect(['action' => 'edit', $cliente->id]);
			}

			$this->Flash->error(__('Não foi possível salvar o cliente.'));
		}

		$hoje = date('d/m/Y');
		$mes = date('01/m/Y');

		$cidades = $this->Cidades->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$acessos = $this->Cliacessos->find('all')->order(['nome'])->where(['idcliente' => $id])->toArray();
		$contratos = $this->Clicontratos->find('all')->where(['idcliente' => $id])->toArray();

		$this->set('acessos', $acessos);
		$this->set('contratos', $contratos);
		// UF do contribuinte (para consulta IE na edição): a partir da cidade do cliente
		$ufContribuinte = null;
		if (!empty($cliente->idcidade)) {
			$cidade = $this->Cidades->find()->where(['id' => $cliente->idcidade])->first();
			if ($cidade && $cidade->idestado) {
				$estado = $this->Estados->find()->where(['id' => $cidade->idestado])->first();
				if ($estado) {
					$ufContribuinte = $estado->sigla;
				}
			}
		}
		$this->set('ufContribuinte', $ufContribuinte);
		$this->set('cidades', $cidades);
		$this->set('cliente', $cliente);	
		$this->set('usuarios', $usuarios);	
		$this->set('usuariosValue', $usuariosValue);	
	}

	public function cidadesestado($idcidade){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		$cidade = $this->Cidades->find('all')->where(['id' => $idcidade])->first();
		if (empty($cidade) || empty($cidade->idestado)) {
			return;
		}
		$estado = $this->Estados->find()->where(['id' => $cidade->idestado])->first();
		if (!empty($estado)) {
			echo h($estado->sigla);
		}
	}

	public function delete($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Você não possui permissão para excluir este cliente ou registro não encontrado.');
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

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Cliente não encontrado ou sem permissão.');
			return $this->redirect(['action' => 'index']);
		}
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

	public function inativar($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			$this->Flash->error('Cliente não encontrado ou sem permissão.');
			return $this->redirect(['action' => 'index']);
		}
		if ((int)$cliente->inativo === 1) {
			$this->Flash->warning('Este cliente já está inativo.');
			return $this->redirect(['action' => 'index', '#' => 'inativos']);
		}
		$cliente->inativo = 1;

		if ($this->Clientes->save($cliente)) {
			$this->sincronizacliente($id);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success('O cliente foi inativado com sucesso!');
		} else {
			$this->Flash->error('Não foi possível inativar o cliente.');
		}

		return $this->redirect(['action' => 'index', '#' => 'inativos']);
	}

	public function solicitantes($idcliente) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if (!$this->Auth->user()) {
			return $this->jsonResponse([], 401);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse([], 403);
		}
		$cid = (int)$idcliente;
		if ($cid <= 0) {
			return $this->jsonResponse([], 400);
		}
		$qCli = $this->Clientes->find()->where(['id' => $cid]);
		$this->Abac->applyToQuery($qCli, 'Clientes');
		$cli = $qCli->first();
		if (empty($cli)) {
			return $this->jsonResponse([], 404);
		}

		$solicitantes = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])
			->order(['name'])
			->where(['idcliente' => $cid, 'inativo' => 0])
			->toArray();

		return $this->jsonResponse($solicitantes, 200);
	}

	public function solicitante($idsolicitante) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('ajax')) {
			$user = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->where(['id' => $idsolicitante, 'inativo' => '0'])->toArray();
			return $this->jsonResponse($user, 200);
		}
	}

	public function cliemail($idcliente) {
		$this->autoRender = false;

		if (!$this->Auth->user()) {
			return $this->jsonResponse(['email' => ''], 401);
		}
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['email' => ''], 403);
		}
		$cid = (int)$idcliente;
		if ($cid <= 0) {
			return $this->jsonResponse(['email' => ''], 400);
		}
		$qMail = $this->Clientes->find('all')->where([
			'id' => $cid,
			'inativo' => '0',
		]);
		$this->Abac->applyToQuery($qMail, 'Clientes');
		$cliente = $qMail->first();
		if (empty($cliente)) {
			return $this->jsonResponse(['email' => ''], 404);
		}

		return $this->jsonResponse([
			'email' => (string)($cliente->get('email') ?? ''),
		], 200);
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
		$clientes = [];
		$idempresa = $this->Auth->user('idempresa');

		if ($this->request->is('ajax') && $idempresa !== null && $idempresa !== '') {
			foreach ($idsclientes as $id) {
				$id = trim((string) $id);
				if ($id === '') {
					continue;
				}
				$qRow = $this->Clientes->find()
					->select(['id', 'razaosocial'])
					->where(['id' => (int) $id]);
				$this->Abac->applyToQuery($qRow, 'Clientes');
				$rows = $qRow->toArray();
				foreach ($rows as $row) {
					$clientes[] = $row;
				}
			}
			return $this->jsonResponse($clientes, 200);
		}
		return $this->jsonResponse([], 400);
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
	 * Consulta Inscrição Estadual (IE) na SEFAZ/SINTEGRA via API SintegraPI.
	 * Requer chave em SINTEGRA_API_KEY (env) ou Configure Sintegra.apiKey.
	 * Parâmetros: cnpj (obrigatório), uf (opcional; ex: RS, SP).
	 */
	public function consultaIe($cnpj = null, $uf = null) {
		$this->autoRender = false;

		if (!$this->request->is('ajax')) {
			return $this->jsonResponse(['success' => false, 'message' => 'Requisição inválida'], 400);
		}

		$cnpj = preg_replace('/\D+/', '', (string)($cnpj ?? ''));
		if (strlen($cnpj) !== 14) {
			return $this->jsonResponse(['success' => false, 'message' => 'CNPJ inválido'], 400);
		}

		$apiKey = env('SINTEGRA_API_KEY', \Cake\Core\Configure::read('Sintegra.apiKey'));
		if (empty($apiKey)) {
			return $this->jsonResponse([
				'success' => false,
				'message' => 'Consulta de IE não configurada. Defina SINTEGRA_API_KEY no ambiente ou Sintegra.apiKey na configuração.',
				'ie' => null,
			], 200);
		}

		$uf = $uf ? strtoupper(trim($uf)) : null;
		$url = 'https://api.sintegrapi.com.br/consultas/v2/sintegra/' . $cnpj;
		if ($uf) {
			$url .= '?uf=' . rawurlencode($uf);
		}

		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => 15,
				'header' => [
					'Accept: application/json',
					'x-api-key: ' . $apiKey,
					'cache: 25',
				],
			],
		]);

		$result = @file_get_contents($url, false, $context);
		if ($result === false) {
			return $this->jsonResponse(['success' => false, 'message' => 'Falha ao acessar serviço de IE (SEFAZ/SINTEGRA).', 'ie' => null], 502);
		}

		$data = json_decode($result, true);
		if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
			return $this->jsonResponse(['success' => false, 'message' => 'Retorno inválido do serviço de IE.', 'ie' => null], 502);
		}

		if (!empty($data['error']) || empty($data['success'])) {
			return $this->jsonResponse([
				'success' => false,
				'message' => isset($data['message']) ? $data['message'] : 'IE não encontrada ou indisponível.',
				'ie' => null,
			], 200);
		}

		$ie = null;
		$situacao = null;
		$inscricoes = $data['inscricoes_estaduais'] ?? [];
		if ($uf) {
			foreach ($inscricoes as $item) {
				if (isset($item['uf']) && strtoupper($item['uf']) === $uf && !empty($item['inscricao_estadual'])) {
					$ie = preg_replace('/\D+/', '', $item['inscricao_estadual']);
					$situacao = $item['situacao_pj'] ?? ($item['ativa'] ? 'Ativa' : 'Inativa');
					break;
				}
			}
		}
		if ($ie === null && !empty($inscricoes)) {
			$first = $inscricoes[0];
			$ie = preg_replace('/\D+/', '', $first['inscricao_estadual'] ?? '');
			$situacao = $first['situacao_pj'] ?? null;
		}

		return $this->jsonResponse([
			'success' => true,
			'ie' => $ie,
			'situacao' => $situacao,
			'uf' => $uf,
		], 200);
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

		$cliente = $this->_findClienteForCurrentUser($idcliente);
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado.'));
			return $this->redirect(['action' => 'index']);
		}
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

		$cliente = $this->_findClienteForCurrentUser($id);
		if (empty($cliente)) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		// Clientes só podem ver o próprio contrato (quando vinculados)
		if ($this->Auth->user('role') == C_RoleCliente && $this->Auth->user('idcliente') != $id) {
			echo 'Você não possui permissão para visualizar este contrato.';
			return;
		}

		$this->response = $this->response->withType('text/html; charset=UTF-8');
		echo h((string) $cliente->contrato);
	}

	public function addAPI() {
		$this->autoRender = false;

		$apiRet = function ($msg, $status = 200) {
			return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
		};

		if (!$this->request->is('post')) {
			return $apiRet('Método não permitido. Use POST com JSON em /clientes/addAPI.', 405);
		}

		try {
			$empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
			$token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
			$json = $this->request->getData();
			if (empty($json) || !is_array($json)) {
				$raw = $this->request->input('json_decode');
				$json = is_string($raw) ? json_decode($raw) : $raw;
			} else {
				$json = (object)$json;
			}

			if (empty($token)) {
				return $apiRet('O token não foi informado', 400);
			}
			if (empty($empresa)) {
				return $apiRet('O ID da empresa não foi informado', 400);
			}
			if ($json === null || !is_object($json)) {
				return $apiRet('O JSON não foi informado ou é inválido.', 400);
			}
			if (!isset($json->cnpj) || trim((string) $json->cnpj) === '') {
				return $apiRet('JSON inválido: o campo cnpj é obrigatório.', 400);
			}
			if (empty($this->Empresas->findById($empresa)->first())) {
				return $apiRet("Não foi encontrada uma empresa com o ID ($empresa) informado", 400);
			}

			if ($token != $this->Empresas->get($empresa)->token) {
				return $apiRet('Autenticação Inválida', 401);
			}

			$retorno['CNPJ'] = removeCaracteres($json->cnpj);
			$retorno['Empresa'] = $empresa;
			$tipo = strlen($retorno['CNPJ']) > 11 ? 'j' : 'f';

			if ($tipo == 'j') {
				$cliente = $this->Clientes->findByCnpj($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa']])->first();
			} else {
				$cliente = $this->Clientes->findByCpf($retorno['CNPJ'])->where(['idempresa' => $retorno['Empresa'], 'tipo' => C_ClientesTipoFisica])->first();
			}

			if ($cliente == null) {
				$cliente = $this->Clientes->newEntity();
				$string = $empresa . $retorno['CNPJ'] . date('d/m/y') . date('H:i');
				$cliente->token = $this->Clientes->generateToken($string);
			}

			$nomeIn = strtoupper(trim((string)($json->nome ?? '')));
			if ($tipo == 'j') {
				$cliente->razaosocial = $nomeIn;
				$cliente->nome = ' ';
				$cliente->cnpj = $retorno['CNPJ'];
				$cliente->tipo = C_ClientesTipoJuridica;
			} else {
				$cliente->nome = $nomeIn !== '' ? $nomeIn : ' ';
				$cliente->cpf = $retorno['CNPJ'];
				$cliente->tipo = C_ClientesTipoFisica;
			}
			$cliente->inscricaoestadual = $json->inscest ?? null;
			$cliente->membrodesde = date('d/m/y');
			$cliente->idempresa = $empresa;
			$cliente->endereco = $json->endereco ?? null;
			$cliente->nroendereco = $json->nroendereco ?? null;
			$cliente->complemento = $json->complemento ?? null;
			$cliente->bairro = $json->bairro ?? null;
			$cliente->cep = isset($json->cep) ? removeCaracteres((string)$json->cep) : null;
			if (isset($json->telefone)) {
				$cliente->fone = removeCaracteres((string)$json->telefone);
			}
			if (isset($json->celular)) {
				$cliente->fone2 = removeCaracteres((string)$json->celular);
			}
			$cliente->email = $json->email ?? null;
			$cliente->contrato = $json->contrato ?? null;
			$cliente->nomefantasia = $json->fantasia ?? null;
			$cliente->inativo = 0;

			$codibge = isset($json->codibge) ? trim((string)$json->codibge) : '';
			if ($codibge === '') {
				return $apiRet('JSON inválido: informe codibge (código IBGE do município).', 400);
			}
			$cidade = $this->Cidades->findByCodibge($codibge)->first();
			if ($cidade === null) {
				return $apiRet("Município não encontrado no portal para codibge={$codibge}. Cadastre a cidade ou corrija o IBGE.", 400);
			}
			$cliente->idcidade = $cidade->id;
			$cliente->empresadominante = (int)$empresa;

			if (!$this->Clientes->save($cliente)) {
				$err = json_encode($cliente->getErrors(), JSON_UNESCAPED_UNICODE);

				return $apiRet('Erro ao salvar cliente no portal: ' . $err, 400);
			}

			$deuerro = 'não';
			$contratos = $this->Clicontratos->find('all')->where(['idempresa' => $empresa, 'idcliente' => $cliente->id])->toArray();
			foreach ($contratos as $reg) {
				$this->Clicontratos->delete($reg);
			}

			$servicosList = [];
			if (isset($json->Servicos)) {
				if (is_array($json->Servicos)) {
					$servicosList = $json->Servicos;
				} elseif (is_object($json->Servicos)) {
					$servicosList = [$json->Servicos];
				}
			}
			foreach ($servicosList as $servico) {
				$servico = is_array($servico) ? (object)$servico : $servico;
				$contrato = $this->Clicontratos->newEntity();
				$contrato->iderp = $servico->idERP ?? null;
				$contrato->codproduto = $servico->codproduto ?? null;
				$contrato->descricao = $servico->descricao ?? null;
				$contrato->infadicional = $servico->infadicional ?? null;
				$contrato->vlunit = $servico->vlunit ?? null;
				$contrato->qtde = $servico->qtde ?? null;
				$contrato->vltotal = $servico->vltotal ?? null;
				if (!empty($servico->dtcontratacao)) {
					$contrato->dtcontratacao = $servico->dtcontratacao;
				}
				if (!empty($servico->dtvalidade)) {
					$contrato->dtvalidade = $servico->dtvalidade;
				}
				if (!empty($servico->dtcancelamento)) {
					$contrato->dtcancelamento = $servico->dtcancelamento;
				}
				$contrato->idcliente = $cliente->id;
				$contrato->idempresa = $empresa;
				if ($this->Clicontratos->save($contrato)) {
					$contratos[] = $contrato;
				} else {
					$deuerro = 'sim';
				}
			}

			if ($deuerro == 'não') {
				return $apiRet('Cliente cadastrado/atualizado com sucesso', 201);
			}

			return $apiRet('Houve um erro ao salvar os contratos do cliente.', 400);
		} catch (\Throwable $e) {
			$this->log('Clientes::addAPI: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), 'error');

			return $this->jsonResponse([
				'mensagem' => 'Erro interno ao processar addAPI: ' . $e->getMessage(),
				'retorno' => 'Erro interno ao processar addAPI: ' . $e->getMessage(),
			], 500);
		}
	}
	
	public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			$empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
            $token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
            $cnpj = $this->request->getHeaderLine('cnpj') ?: $this->request->getQuery('cnpj');

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
					if (empty($cliente)) {
						return $apiRetList('Não foi encontrado um cliente com o CNPJ/CPF '. $cnpj, 404);
					}
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
		$clienteEnt = $this->_findClienteForCurrentUser($idcliente);
		if (empty($clienteEnt)) {
			$this->Flash->error(__('Cliente não encontrado para sincronização.'));
			return;
		}
		$cliente = ['Cliente' => $this->Clientes->clientesArr($clienteEnt)];

		$json = json_encode($cliente, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$soap = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WSPGMPessoas.wso?wsdl';
		try {
			@$soap = new CakeSoap(['wsdl' => $soap]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			$this->Flash->error(__('O WS não pode ser acessado no momento!'));
			return;
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
