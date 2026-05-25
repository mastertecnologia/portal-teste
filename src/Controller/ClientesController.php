<?php
namespace App\Controller;

use App\Controller\Traits\ClientesCrmListaTrait;
use App\Controller\Traits\ClientesVisao360SupportTrait;
use App\Controller\Traits\ClientesVisao360Trait;
use App\Utility\ErpIntegrationRequest;
use App\Controller\AppController;
use App\Service\Common\CryptoService;
use App\Service\ClienteDomain\ClienteDomainBridge;
use App\Service\ClienteDomain\InfrastructureGuard;
use App\Service\ClienteIntegration\ClienteErpSyncService;
use App\Model\Table\ClientesTable;
use App\Utility\ClienteDomainEventType;
use App\Utility\PortalUi;
use App\Utility\RbacChecker;
use Cake\Event\Event;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use CakeSoap\Network\CakeSoap;

$__pgmUserConstants = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php';
if (is_file($__pgmUserConstants)) {
	require_once $__pgmUserConstants;
}
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
$__cakeSoap = ROOT . DS . 'vendor' . DS . 'queencitycodefactory' . DS . 'cakesoap' . DS . 'src' . DS . 'Network' . DS . 'CakeSoap.php';
if (is_file($__cakeSoap)) {
	require_once $__cakeSoap;
}
if (!defined('C_RoleCliente')) {
	define('C_RoleCliente', 1);
}
if (!defined('C_RoleFuncionario')) {
	define('C_RoleFuncionario', 0);
}
if (!defined('C_ClientesTipoJuridica')) {
	define('C_ClientesTipoJuridica', 2);
}
if (!defined('C_ClientesTipoFisica')) {
	define('C_ClientesTipoFisica', 1);
}

class ClientesController extends AppController {

	use ClientesCrmListaTrait;
	use ClientesVisao360SupportTrait;
	use ClientesVisao360Trait;

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

	/**
	 * Data de validade do item de contrato em Y-m-d, ou null se cancelado ou sem validade útil.
	 * Usado no resumo do rodapé e na situação da linha na aba Contratos (mesma regra).
	 *
	 * @param object $c Registro Clicontratos
	 */
	protected function _clicontratoValidadeYmd($c): ?string {
		if (!empty($c->dtcancelamento)) {
			return null;
		}
		$raw = $c->dtvalidade ?? null;
		if ($raw instanceof \DateTimeInterface) {
			return $raw->format('Y-m-d');
		}
		if (is_string($raw) && $raw !== '') {
			$t = strtotime($raw);

			return $t ? date('Y-m-d', $t) : null;
		}

		return null;
	}

	/**
	 * Rótulo, classe de linha e badge Bootstrap para a listagem de contratos na ficha do cliente.
	 *
	 * @param object $c Registro Clicontratos
	 */
	protected function _clicontratoRowUi($c, string $todayStr, string $lim30): array {
		if (!empty($c->dtcancelamento)) {
			return [
				'label' => 'Cancelado',
				'row_class' => 'cli-ctr-row--cancelado',
				'badge_class' => 'badge-secondary',
			];
		}
		$dv = $this->_clicontratoValidadeYmd($c);
		if ($dv === null) {
			return [
				'label' => 'Sem validade',
				'row_class' => 'cli-ctr-row--semvalidade',
				'badge_class' => 'badge-secondary',
			];
		}
		if ($dv < $todayStr) {
			return [
				'label' => 'Vencido',
				'row_class' => 'cli-ctr-row--vencido',
				'badge_class' => 'badge-danger',
			];
		}
		if ($dv <= $lim30) {
			return [
				'label' => 'Vence em 30 dias',
				'row_class' => 'cli-ctr-row--vencendo',
				'badge_class' => 'badge-warning text-dark',
			];
		}

		return [
			'label' => 'Ativo',
			'row_class' => 'cli-ctr-row--ok',
			'badge_class' => 'badge-success',
		];
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
		$prototypeLista = PortalUi::redirectToPrototypeIfEnabled('clientes', 'ClientesPrototype', 'lista');
		if ($prototypeLista !== null) {
			return $this->redirect($prototypeLista);
		}
		$this->set('title', 'Clientes');
		$this->set('hideLayoutPageTitle', true);
		$empresaLbl = trim((string)($this->viewVars['nomeempresa'] ?? ''));
		if ($empresaLbl === '') {
			$empresaLbl = __('PGM Soluções');
		}
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Clientes'));
		$this->set('pgmTopbarClientesLista', true);
		$this->set('pgmTopbarEmpresas', $this->_clientesTopbarEmpresas());

		$qAll = $this->Clientes->find('all')->contain(['Cidades.Estados'])->order(['Clientes.id' => 'DESC']);
		$this->Abac->applyToQuery($qAll, 'Clientes');
		$todos = $qAll->toArray();

		$clientesAtivos = array_values(array_filter($todos, function ($c) {
			return (int)$c->inativo === 0;
		}));
		$clientesInativos = array_values(array_filter($todos, function ($c) {
			return (int)$c->inativo === 1;
		}));

		$this->set('clientesAtivos', $clientesAtivos);
		$this->set('clientesInativos', $clientesInativos);
		$this->set('clientesLista', $todos);

		$this->set('clientesAtivosPJ', array_values(array_filter($clientesAtivos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoJuridica;
		})));
		$this->set('clientesAtivosPF', array_values(array_filter($clientesAtivos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoFisica;
		})));
		$this->set('clientesInativosPJ', array_values(array_filter($clientesInativos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoJuridica;
		})));
		$this->set('clientesInativosPF', array_values(array_filter($clientesInativos, function ($c) {
			return (int)$c->tipo === (int)C_ClientesTipoFisica;
		})));

		$crm = $this->_clientesIndexCrmMetrics($todos, count($clientesAtivos));
		$this->set('cliCrm', $crm);
		$this->set('cliRows', $this->_clientesIndexRows($todos, $crm));
		$this->set('cliVendedores', $this->_clientesIndexVendedoresLista());
	}

	/**
	 * Empresas do utilizador para o seletor da topbar (lista CRM).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function _clientesTopbarEmpresas() {
		$lista = [];
		$idAtiva = (int)$this->Auth->user('idempresa');
		$opt = (array)($this->viewVars['empresasOptSidebar'] ?? []);
		if ($opt === [] && $this->Auth->user('id') > 0) {
			try {
				$rows = $this->Empresasusers->find('all')
					->where(['Empresasusers.iduser' => (int)$this->Auth->user('id')])
					->contain(['Empresas'])
					->order(['Empresas.nomefantasia' => 'ASC'])
					->all();
				foreach ($rows as $reg) {
					if (!empty($reg->empresa)) {
						$opt[(int)$reg->idempresa] = (string)$reg->empresa->nomefantasia;
					}
				}
			} catch (\Throwable $e) {
			}
		}
		foreach ($opt as $eid => $nome) {
			$eid = (int)$eid;
			$cnpj = '';
			try {
				$emp = $this->Empresas->get($eid, ['fields' => ['cnpj', 'razaosocial', 'nomefantasia']]);
				$cnpj = formatCnpjCpf((string)($emp->cnpj ?? ''));
				if (trim((string)$nome) === '') {
					$nome = (string)($emp->nomefantasia ?? $emp->razaosocial ?? '');
				}
			} catch (\Throwable $e) {
			}
			$parts = preg_split('/\s+/u', trim((string)$nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
			$ini = '';
			if (!empty($parts[0])) {
				$ini .= mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8'));
			}
			if (!empty($parts[1])) {
				$ini .= mb_strtoupper(mb_substr($parts[1], 0, 1, 'UTF-8'));
			}
			$lista[] = [
				'id' => $eid,
				'nome' => (string)$nome,
				'cnpj' => $cnpj,
				'initials' => $ini !== '' ? $ini : 'PG',
				'current' => $eid === $idAtiva,
			];
		}

		return $lista;
	}

	/**
	 * Site vindo do JSON da integração ERP (vários aliases).
	 *
	 * @param object $json
	 * @return string|null
	 */
	protected function _clientesSiteFromIntegrationJson($json) {
		foreach (['site', 'website', 'site_web', 'url_site'] as $key) {
			if (!isset($json->$key)) {
				continue;
			}
			$s = trim((string)$json->$key);
			if ($s !== '') {
				return $s;
			}
		}

		return null;
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
		$qCode = $this->Clientes->find('all')->where(['Clientes.public_code' => $kw]);
		$this->Abac->applyToQuery($qCode, 'Clientes');
		$byPublicCode = $qCode->toArray();
		if (!empty($byPublicCode)) {
			return $byPublicCode;
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
					['LOWER(Clientes.public_code) LIKE' => '%' . $w . '%'],
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
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Cadastrar clientes'));
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
				unset($data['public_code']);
				if ($this->_clientesCrmFinanceReady()) {
					if (array_key_exists('limite_credito', $data)) {
						$data['limite_credito'] = $this->_clientesParseDecimalBr($data['limite_credito']);
					}
					if (array_key_exists('score_interno', $data)) {
						$data['score_interno'] = $this->_clientesParseDecimalBr($data['score_interno']);
					}
				} else {
					unset($data['limite_credito'], $data['score_interno'], $data['observacoes_financeiras']);
				}

				// Geração do token
				$cpfoucnpj = isset($data['cnpj']) ? $data['cnpj'] : $data['cpf'];
				$string = $this->Auth->user('id') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
				$data['token'] = $this->Clientes->generateToken($string);
			
				$cliente = $this->Clientes->patchEntity($cliente, $data);
				$cliente->membrodesde = date('Y-m-d');
				$cliente->idempresa = $this->Auth->user('idempresa');
				if(!empty($data['cnpj'])) $cliente->cnpj = \removeCaracteres($data['cnpj']);
				if(!empty($data['cpf'])) $cliente->cpf = \removeCaracteres($data['cpf']);
				if(!empty($data['inscricaoestadual'])) $cliente->inscricaoestadual = \removeCaracteres($data['inscricaoestadual']);
				if(!empty($data['inscricaomunicipal'])) $cliente->inscricaomunicipal = \removeCaracteres($data['inscricaomunicipal']);
				if(!empty($data['cep'])) $cliente->cep = \removeCaracteres($data['cep']);
	
				if ($this->Clientes->save($cliente)) {
					$this->sincronizacliente($cliente->id);
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);
					$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
					ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_CRIADO, [
						'idcliente' => (int)$cliente->id,
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'actor_user_id' => (int)$this->Auth->user('id'),
						'title' => __('Cliente cadastrado'),
						'message' => __('Novo cliente: {0}', $nomeCli),
						'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $cliente->id]),
						'entity_type' => 'Cliente',
						'entity_id' => $cliente->id,
					]);
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
		$this->set('hideLayoutPageTitle', true);
		$this->set('topbarParentLabel', __('Cadastros'));
		$this->set('topbarCurrentLabel', __('Editar cliente'));

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
		$usuariosOptions = [];
		foreach ($usuarios as $u) {
			$label = (string)($u->username ?? '');
			if (!empty($u->email)) {
				$label .= ($label !== '' ? ' · ' : '') . $u->email;
			}
			if (!empty($u->name)) {
				$label .= ($label !== '' ? ' — ' : '') . $u->name;
			}
			$usuariosOptions[$u->id] = $label !== '' ? $label : '#' . $u->id;
		}
		$cliente->users = $this->Users->find('all')->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$usuariosValue = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where(['idcliente' => $id, 'permissaoacesso' => 1])->toArray();
		$cliente->senha = CryptoService::decrypt($cliente->senha, $cliente->idempresa ?? 0);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			unset($data['public_code']);
			if (!$this->_clientesCrmFinanceReady()) {
				unset($data['limite_credito'], $data['score_interno'], $data['observacoes_financeiras']);
			} else {
				if (array_key_exists('limite_credito', $data)) {
					$data['limite_credito'] = $this->_clientesParseDecimalBr($data['limite_credito']);
				}
				if (array_key_exists('score_interno', $data)) {
					$data['score_interno'] = $this->_clientesParseDecimalBr($data['score_interno']);
				}
			}
			if ((int)$this->Auth->user('role') === C_RoleFuncionario) {
				$inativoGate = RbacChecker::resourceFieldAccess((int)$this->Auth->user('id'), 'Clientes.field.inativo');
				if ($inativoGate !== null && (empty($inativoGate['visible']) || empty($inativoGate['editable']))) {
					unset($data['inativo']);
				}
			}

			$cliente = $this->Clientes->patchEntity($cliente, $data);
			if(!empty($data['cpf'])) $cliente->cpf = \removeCaracteres($data['cpf']);
			if(!empty($data['senha'])) $cliente->senha = CryptoService::encrypt($data['senha'], $cliente->idempresa ?? 0);

			if ($this->Clientes->save($cliente)) {
				$this->sincronizacliente($id);
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $cliente->id);
				$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
				ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_ATUALIZADO, [
					'idcliente' => (int)$cliente->id,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'actor_user_id' => (int)$this->Auth->user('id'),
					'title' => __('Cliente atualizado'),
					'message' => __('Cadastro alterado: {0}', $nomeCli),
					'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $cliente->id]),
					'entity_type' => 'Cliente',
					'entity_id' => $cliente->id,
				]);

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

		$cliFooter = [
			'status_label' => $cliente->inativo ? 'Inativo' : 'Ativo',
			'status_class' => $cliente->inativo ? 'danger' : 'success',
			'contratos_total' => count($contratos),
			'contratos_vencidos' => 0,
			'contratos_vencendo30' => 0,
			'token_note' => 'Renove o token de integração periodicamente; não há data de validade cadastrada no sistema.',
		];
		$todayStr = (new \DateTimeImmutable('today'))->format('Y-m-d');
		$lim30 = (new \DateTimeImmutable('today'))->add(new \DateInterval('P30D'))->format('Y-m-d');
		foreach ($contratos as $c) {
			$dv = $this->_clicontratoValidadeYmd($c);
			if ($dv === null) {
				continue;
			}
			if ($dv < $todayStr) {
				$cliFooter['contratos_vencidos']++;
			} elseif ($dv <= $lim30) {
				$cliFooter['contratos_vencendo30']++;
			}
		}

		$contratosRowUi = [];
		foreach ($contratos as $c) {
			$cid = (int)$c->id;
			if ($cid <= 0) {
				continue;
			}
			$contratosRowUi[$cid] = $this->_clicontratoRowUi($c, $todayStr, $lim30);
		}

		$this->set('acessos', $acessos);
		$this->set('contratos', $contratos);
		$this->set('contratosRowUi', $contratosRowUi);
		$this->set('cliFooter', $cliFooter);
		// Ativos de TI (CMDB) — listagem compacta na aba "Ativos" da ficha do cliente.
		$ativosCliente = [];
		try {
			$assetsTbl = $this->loadModel('Assets');
			$ativosCliente = $assetsTbl->find()
				->where(['Assets.idcliente' => (int)$cliente->id])
				->order(['Assets.id' => 'DESC'])
				->limit(200)
				->toArray();
		} catch (\Throwable $e) {
			$ativosCliente = [];
		}
		$this->set('ativosCliente', $ativosCliente);
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
		$this->set('usuariosOptions', $usuariosOptions);
		$this->set('usuariosValue', $usuariosValue);
		$this->set('cliCrmFinanceReady', $this->_clientesCrmFinanceReady());
		$this->set('cliContatosReady', $this->_clientesContatosReady());
		$this->set('cliContatos', $this->_clientesContatosList((int)$id));
	}

	/**
	 * Visão 360º do cliente (indicadores + histórico com dados reais do ERP).
	 *
	 * @param int|string|null $id
	 */
	public function visao360($id = null) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$cid = (int)$id;
		if ($cid > 0 && PortalUi::isPremiumModule('clientes')) {
			$tab = trim((string)$this->request->getQuery('tab', ''));
			$protoRoute = PortalUi::visao360Route($cid, $tab !== '' ? ['tab' => $tab] : []);
			if ($protoRoute !== null) {
				return $this->redirect($protoRoute);
			}
		}
		$q = $this->Clientes->find()
			->contain(['Cidades.Estados'])
			->where(['Clientes.id' => (int)$id]);
		$this->Abac->applyToQuery($q, 'Clientes');
		$cliente = $q->first();
		if (empty($cliente)) {
			$this->Flash->error(__('Cliente não encontrado ou sem permissão.'));
			return $this->redirect(['action' => 'index']);
		}
		$nome = $this->_clientesIndexNomeExibicao($cliente);
		$this->set('title', __('Visão 360° — {0}', $nome));
		$this->set('hideLayoutPageTitle', true);
		$this->set('topbarParentLabel', __('Clientes'));
		$this->set('topbarCurrentLabel', __('Visão 360°'));
		$tab = trim((string)$this->request->getQuery('tab', 'geral'));
		$allowedTabs = ['geral', 'orcamentos', 'os', 'financeiro', 'contratos', 'historico', 'arquivos'];
		if (!in_array($tab, $allowedTabs, true)) {
			$tab = 'geral';
		}
		$this->set('cli360Tab', $tab);
		$this->set('cliente', $cliente);
		$this->set('cliContatosReady', $this->_clientesContatosReady());
		$this->set('cli360', $this->_clientesVisao360Payload($cliente));
	}

	/**
	 * Histórico legado — redireciona para a aba Histórico da Visão 360º.
	 *
	 * @param int|string|null $id
	 */
	public function eventos($id = null) {
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		if ($id === null || $id === '') {
			return $this->redirect(['action' => 'index']);
		}

		if (PortalUi::isPremiumModule('clientes')) {
			$protoRoute = PortalUi::visao360Route((int)$id, ['tab' => 'historico']);
			if ($protoRoute !== null) {
				return $this->redirect($protoRoute);
			}
		}

		return $this->redirect(['action' => 'visao360', (int)$id, '?' => ['tab' => 'historico']]);
	}

	/**
	 * Total de anexos vinculados ao cliente (tickets + financeiro).
	 */
	protected function _clientesContarArquivosCliente(int $idcliente, int $idempresa): int {
		return count($this->_clientesListarArquivosCliente($idcliente, $idempresa));
	}

	/**
	 * @param mixed $raw
	 * @return string|null
	 */
	protected function _clientesParseDecimalBr($raw) {
		if ($raw === null || $raw === '') {
			return null;
		}
		$s = trim((string)$raw);
		if ($s === '') {
			return null;
		}
		$s = str_replace(['R$', ' '], '', $s);
		if (strpos($s, ',') !== false) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
		}

		return is_numeric($s) ? $s : null;
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
			$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
			ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_ATIVADO, [
				'idcliente' => (int)$id,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Cliente reativado'),
				'message' => __('Cliente ativo novamente: {0}', $nomeCli),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $id]),
				'entity_type' => 'Cliente',
				'entity_id' => $id,
			]);
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
			$nomeCli = $cliente->tipo == C_ClientesTipoFisica ? ($cliente->nome ?? '') : ($cliente->razaosocial ?? '');
			ClienteDomainBridge::emit(ClienteDomainEventType::CLIENTE_INATIVADO, [
				'idcliente' => (int)$id,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Cliente inativado'),
				'message' => __('Cliente inativado: {0}', $nomeCli),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $id]),
				'entity_type' => 'Cliente',
				'entity_id' => $id,
			]);
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
		$uidTok = (int)$this->Auth->user('id');
		$apiTokGate = RbacChecker::resourceFieldAccess($uidTok, 'Clientes.field.api_token');
		if ($apiTokGate !== null && (empty($apiTokGate['visible']) || empty($apiTokGate['editable']))) {
			$this->Flash->error(__('Sem permissão para renovar o token.'));
			return $this->redirect(['action' => 'edit', $idcliente]);
		}
		// Gera o token
		$cpfoucnpj = isset($cliente->cnpj) ? $cliente->cnpj : $cliente->cpf;
		$string = $this->Auth->user('idempresa') . $cpfoucnpj .  date('d/m/y') .  date('H:i');
		// Atualiza o token
		$cliente->token = $this->Clientes->generateToken($string);
		if($this->Clientes->save($cliente)){
			ClienteDomainBridge::emit(ClienteDomainEventType::TOKEN_GERADO, [
				'idcliente' => (int)$idcliente,
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'actor_user_id' => (int)$this->Auth->user('id'),
				'title' => __('Token do cliente renovado'),
				'message' => __('Foi gerado um novo token de integração para este cliente.'),
				'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $idcliente]),
				'entity_type' => 'Cliente',
				'entity_id' => $idcliente,
			]);
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
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
			if ($erpCredErr !== null) {
				return $apiRet($erpCredErr, 400);
			}
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

			$retorno['CNPJ'] = \removeCaracteres($json->cnpj);
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
			$cliente->membrodesde = date('Y-m-d');
			$cliente->idempresa = $empresa;
			$cliente->endereco = $json->endereco ?? null;
			$cliente->nroendereco = $json->nroendereco ?? null;
			$cliente->complemento = $json->complemento ?? null;
			$cliente->bairro = $json->bairro ?? null;
			$cliente->cep = isset($json->cep) ? \removeCaracteres((string)$json->cep) : null;
			if (isset($json->telefone)) {
				$cliente->fone = \removeCaracteres((string)$json->telefone);
			}
			if (isset($json->celular)) {
				$cliente->fone2 = \removeCaracteres((string)$json->celular);
			}
			$cliente->email = $json->email ?? null;
			$siteIn = $this->_clientesSiteFromIntegrationJson($json);
			if ($siteIn !== null) {
				$cliente->site = $siteIn;
			}
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

			$extPublic = null;
			if (isset($json->public_code)) {
				$extPublic = ClientesTable::normalizeIntegrationPublicCode($json->public_code);
			} elseif (isset($json->codigo_publico)) {
				$extPublic = ClientesTable::normalizeIntegrationPublicCode($json->codigo_publico);
			}
			if ($extPublic === false) {
				return $apiRet('JSON inválido: public_code/codigo_publico com formato inválido (use até 32 caracteres: letras, números, ponto, hífen e sublinhado).', 400);
			}
			if ($extPublic !== null) {
				$cliente->accessible('public_code', true);
				$cliente->set('public_code', $extPublic);
			}

			try {
				$saved = $this->Clientes->save($cliente);
			} catch (\Throwable $e) {
				$pdo = $e instanceof \PDOException ? $e : $e->getPrevious();
				$msg = $pdo instanceof \PDOException ? $pdo->getMessage() : $e->getMessage();
				if ($pdo instanceof \PDOException
					&& (strpos($msg, '23505') !== false
						|| stripos($msg, 'unique') !== false
						|| stripos($msg, 'uq_clientes_idempresa_public_code') !== false)) {
					return $this->jsonResponse([
						'mensagem' => 'Código de cliente já cadastrado para esta empresa.',
						'retorno' => 'Código de cliente já cadastrado para esta empresa.',
					], 409);
				}
				throw $e;
			}
			if (!$saved) {
				$errors = $cliente->getErrors();
				if (!empty($errors['public_code'])) {
					return $this->jsonResponse([
						'mensagem' => 'Código de cliente já cadastrado para esta empresa.',
						'retorno' => 'Código de cliente já cadastrado para esta empresa.',
					], 409);
				}
				$err = json_encode($errors, JSON_UNESCAPED_UNICODE);

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
			list($empresa, $token, $erpCredErr) = ErpIntegrationRequest::readEmpresaAndToken(
				$this->request,
			);
            $cnpj = $this->request->getHeaderLine('cnpj') ?: $this->request->getQuery('cnpj');

			$apiRetList = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};
			if ($erpCredErr !== null) {
				return $apiRetList($erpCredErr, 400);
			}
			if(empty($token) || empty($empresa)) 
			return $apiRetList('Parâmetros da requisição inválidos', 400);

			if(empty($this->Empresas->findById($empresa)->first())) return $apiRetList('Parâmetros da requisição inválidos', 400);
			if($token == $this->Empresas->get($empresa)->token){
				$retorno['CNPJ'] = \removeCaracteres($cnpj);
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
				foreach ($cliente as $reg) {
					$publicCode = $reg->get('public_code');
					$reg = $this->Clientes->clicontratosArr($reg);
					$reg = $this->Clientes->clientesArr($reg);
					if ($publicCode !== null && $publicCode !== '') {
						$reg->accessible('public_code', true);
						$reg->set('public_code', $publicCode);
					}
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
		$err = ClienteErpSyncService::sincronizarCliente(
			$clienteEnt,
			(int)$idcliente,
			(int)$this->Auth->user('idempresa'),
			(int)$this->Auth->user('id')
		);
		if ($err !== null) {
			$this->Flash->error($err);
		}
	}
}
