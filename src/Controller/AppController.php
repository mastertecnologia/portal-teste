<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation,
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Utility\RbacChecker;
use Cake\Controller\Controller;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\Event;

class AppController extends Controller {

	/** @var string|null escopo ABAC da permissão RBAC que autorizou a ação (empresa|cliente|own) */
	public $rbacAbacScope = null;

	/** @var string|null código da permissão RBAC que autorizou (ex.: clientes.manage) */
	public $rbacAbacPermissionCode = null;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Atividades');
		$this->loadModel('Users');
		$this->loadModel('Empresas');
		$this->loadModel('Empresasusers');

		$this->loadComponent('RequestHandler', [
		  'enableBeforeRedirect' => false
		]);
		$this->loadComponent('Flash');
		$this->loadComponent('Rbac');
		$this->loadComponent('Abac');
		$this->loadComponent('Security', [
			'unlockedActions' => [
				'login', 'logout', 'loginempresa', 'acessoEmpresa', 'loginduasetapas',
				'verificaloginduasetapas', 'verificacodigo',
				'add', 'edit',
				// Troca de empresa via dropdown (AJAX) não envia _Token.
				'alteraempresa',
				'carrinho', 'carrinhoadd', 'carrinhoedititem', 'carrinhodelitem', 'valortotal', 'acaoindex',
				'addservico', 'limpacarrinho', 'additem', 'edititem', 'deleteitem', 'excluiitemcarrinho', 'getitemcarrinho', 'edititemcarrinho', 'editaitemcarrinho', 'carrinhoedit',
				// Solicitar orçamento: inputs HTML + itens dinâmicos (itens[n][*]) fora do FormHelper
				'solicitar',
				'catalogoSugestoes',
				'timerIniciar', 'timerPausar', 'timerRetomar', 'timerFinalizar',
				'produto', 'qtdestoque', 'estoquesLote', // Orçamentos: produto, estoque e catálogo (lote)
				// APIs de integração ERP (sem sessão web; token em header)
				'addApi', 'listApi', 'refreshApi', 'addAPI', 'listAPI', 'refreshAPI',
				// Clientes: consulta CNPJ (Receita) e IE (SEFAZ/SINTEGRA) via AJAX
				'consultacnpj', 'consultaIe',
				// API cadastro consolidado (CadastroController)
				'empresa', 'consultar',
				// Tickets UI React (JSON; autenticação via sessão Auth)
				'apiIndex', 'apiDashboardOperacional', 'apiIndexCliente', 'apiView', 'apiComments', 'apiSaveTicket',
				'apiTimer',
				'apiAnexoUpload', 'apiAnexoDelete',
				'apiTecnicosLista', 'apiTransferirTicket', 'apiStartTicket', 'startTicket',
				'apiForTicket', 'getAvailableQueues', 'apiEnsureDefaults', 'apiSupportLevels', 'apiSave',
				'adminIndex', 'adminEdit', 'adminTechnicians', 'adminDelete', 'adminEnsureDefaults',
				'apiAdd',
				'adminSyncRegistry', 'adminMatrix', 'adminMatrixSave', 'adminGrantSuperAll',
				'adminUsers', 'adminUserRoles', 'adminUserEffective',
				'adminPermissionPolicies', 'adminPermissionPolicyEdit', 'adminPermissionPolicyDelete',
				// Cofre de senhas: revelar segredo via POST JSON (senha admin nunca na URL)
				'vaultReveal',
				'verificasenha',
				'verificadadoscliente',
				// selectTheme: desbloqueado do _Token do Security (POST AJAX só com theme);
				// CSRF validado manualmente em UsersController::selectTheme (hash_equals _csrfToken).
				'selectTheme',
				// Faturamento: modal alterar status (POST sem _Token no corpo)
				'alterarStatus',
				// Webhook Autentique (corpo JSON; sem _Token)
				'webhookAutentique',
			],
		]);
		$this->loadComponent('Auth', [
			'loginAction' => [
				  'controller' => 'Users',
				  'action' => 'login'
			 ],
			 'authenticate' => [
				  'Form' => [
					  'userModel' => 'Users',
					  // Credencial no POST continua com chave "username"; o Form autentica contra a coluna users.email.
					  // idempresa é escolhido após o login (getEmpresaPreferencial).
					  'fields' => ['username' => 'email', 'password' => 'password'],
				  ]
			 ],
			'loginRedirect' => [
				'controller' => 'Users',
				'action' => 'dashboard',
				'prefix' => false
			],
			'logoutRedirect' => [
				'controller' => 'Users',
				'action' => 'login',
			],
			'authError' => false
	  ]);

	  $this->Auth->setConfig('authorize', ['Controller']);
	}

	public function beforeRender(Event $event) {
		$c = (string)$this->request->getParam('controller');
		if (preg_match('/^(Advanced|PortalAdvanced|ContractManagement|PortalContratos)/', $c)) {
			$this->set('pgmAdvancedModuleStylesheet', true);
		}
	}

	public function afterFilter(Event $event) {
		$controllerLower = strtolower($this->request->getParam('controller'));
		$actionLower = strtolower($this->request->getParam('action'));
		$apiControllers = ['ordensservico' => ['listapi', 'refreshapi'], 'clientes' => ['addapi', 'listapi'], 'produtos' => ['addapi', 'listapi'], 'clicontratos' => ['addapi', 'listapi']];
		if (isset($apiControllers[$controllerLower]) && in_array($actionLower, $apiControllers[$controllerLower], true)) {
			$this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS')
				->withHeader('Access-Control-Allow-Headers', 'Content-Type, empresa, token, situacao, id, cnpj, codigo');
		}
	}

	public function beforeFilter(Event $event) {
		ini_set('memory_limit', '518M');
		set_time_limit(0);

		$controller = $this->request->getParam('controller');
		$action = $this->request->getParam('action');
		$controllerLower = strtolower($controller);
		$actionLower = strtolower($action);

		// APIs de integração ERP: CORS para o ERP conseguir chamar de outro domínio
		$apiControllers = ['ordensservico' => ['listapi', 'refreshapi'], 'clientes' => ['addapi', 'listapi'], 'produtos' => ['addapi', 'listapi'], 'clicontratos' => ['addapi', 'listapi']];
		if (isset($apiControllers[$controllerLower]) && in_array($actionLower, $apiControllers[$controllerLower], true)) {
			$this->response = $this->response->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, OPTIONS')
				->withHeader('Access-Control-Allow-Headers', 'Content-Type, empresa, token, situacao, id, cnpj, codigo')
				->withHeader('Access-Control-Max-Age', '86400');
			if ($this->request->is('options')) {
				return $this->response->withStatus(204);
			}
		}

		$role = $this->Auth->user('role');
		$iduser = $this->Auth->user('id');
		$admin = $this->Auth->user('admin');
		$setor = $this->Auth->user('setor');
		$idcliente = $this->Auth->user('idcliente');
		$empresa = $this->Auth->user('idempresa');

		$showConfigAdminHub = RbacChecker::shouldShowConfigAdminHub($admin, $role, $iduser);
		$showPermissoesRbacShortcut = RbacChecker::shouldShowPermissoesRbacShortcut($admin, $role, $iduser);

		$menuStates = [
			'dashboard' => '',
			'usuarios' => '',
			'clientesActive' => '',
			'empresasActive' => '',
			'empresasusersActive' => '',
			'configuracoes' => '',
			'relActive' => '',
			'produtosActive' => '',
			'visitasActive' => '',
			'orcamentosActive' => '',
			'faturamentoActive' => '',
			'financeiroActive' => '',
			'areasActive' => '',
			'problemasActive' => '',
			'ordensActive' => '',
			'ticketsActive' => '',
			'senhasActive' => '',
			'faturasActive' => '',
			'prefaturamentoActive' => '',
			'config' => '',
			'queuesAtendimentoActive' => '',
			'advancedModuleActive' => '',
		];

		if ($action === "dashboard") {
			$menuStates['dashboard'] = "active";
		}

		if (in_array($controllerLower, ['config', 'empresasusers', 'empresas', 'users', 'clientes', 'areas', 'problemas', 'visitas', 'feriados', 'queues', 'permissoes'], true)) {
			$menuStates['config'] = "active";
		}

		$controllerToMenuMap = [
			'users' => 'usuarios',
			'clientes' => 'clientesActive',
			'empresas' => 'empresasActive',
			'empresasusers' => 'empresasusersActive',
			'produtos' => 'produtosActive',
			'config' => 'configuracoes',
			'relatorios' => 'relActive',
			'visitas' => 'visitasActive',
			'orcamentos' => 'orcamentosActive',
			'financeiro' => 'financeiroActive',
			'faturamento' => 'faturamentoActive',
			'areas' => 'areasActive',
			'problemas' => 'problemasActive',
			'ordensservico' => 'ordensActive',
			'bancosenhas' => 'senhasActive',
			'faturas' => 'faturasActive',
			'prefaturamento' => 'prefaturamentoActive',
			'tickets' => 'ticketsActive',
			'servicedesk' => 'ticketsActive',
			'portaladvancedattendance' => 'ticketsActive',
			'queues' => 'queuesAtendimentoActive',
				'advancedcontracts' => 'advancedModuleActive',
			'contractmanagement' => 'advancedModuleActive',
			'contracttemplates' => 'advancedModuleActive',
			'advancedinvoices' => 'advancedModuleActive',
			'advancedreports' => 'relActive',
		];

		if (isset($controllerToMenuMap[$controllerLower])) {
			$menuStates[$controllerToMenuMap[$controllerLower]] = "active";
		}

		$this->set('role', $role);
		$this->set('admin', $admin);
		$this->set('setor', $setor);
		$this->set('empresa', $empresa);
		if (!empty($empresa)) {
			try {
				$this->set('nomeempresa', $this->Empresas->get($empresa)->razaosocial);
			} catch (RecordNotFoundException $e) {
				$this->set('nomeempresa', 'Grid Sistemas');
			}
		} else {
			$this->set('nomeempresa', 'Grid Sistemas');
		}
		$this->set('idempresa', $this->Auth->user('idempresa'));
		$this->set('name', $this->Auth->user('name'));
		$this->set('permissaoacesso', $this->Auth->user('permissaoacesso'));
		$this->set('showConfigAdminHub', $showConfigAdminHub);
		$this->set('showPermissoesRbacShortcut', $showPermissoesRbacShortcut);
		$this->set('sidebarMenuGates', RbacChecker::buildSidebarMenuGates($admin, $role, $iduser));
		$canClienteSolicitarOrcamento = false;
		if ((int)$role === 1) {
			$canClienteSolicitarOrcamento = RbacChecker::clientePodeSolicitarOrcamento(
				(int)$iduser,
				!empty($this->Auth->user('permissaoacesso'))
			);
		}
		$this->set('canClienteSolicitarOrcamento', $canClienteSolicitarOrcamento);
		$this->set($menuStates);
		$this->set('idcliente', $idcliente);
		$this->set('iduser', $iduser);

		$srcBase = $this->request->getAttribute('src');
		$url = (string)($srcBase !== null ? $srcBase : '') . 'template/notificacoes/notificacoes/';
		$this->set('url', $url);

		$empresasOptSidebar = [];
		// Sem iduser, não buscar (evita WHERE iduser IS NULL e erro ao acessar empresa nula no PHP 8+).
		if (!empty($iduser)) {
			foreach (
				$this->Empresasusers
					->find('all')
					->where(['iduser' => $iduser])
					->contain(['Empresas' => ['fields' => ['nomefantasia']]])
					->order(['Empresas.nomefantasia' => 'ASC'])
					->toArray() as $reg
			) {
				if (!empty($reg->empresa)) {
					$empresasOptSidebar[$reg->idempresa] = $reg->empresa->nomefantasia;
				}
			}
		}
		$this->set('empresasOptSidebar', $empresasOptSidebar);
		
		// Obtém os dados atualizados do usuário logado
		if ($this->Auth->user('id') > 0) {
			try {
				$user = $this->Users->get($this->Auth->user('id'));
				$this->set('skin', $user->skin);
				$this->set('sidebar', $user->sidebar);
				$this->set('pagelength', $user->pagelength);
			} catch (RecordNotFoundException $e) {
				$this->set('skin', '');
				$this->set('sidebar', 1);
				$this->set('pagelength', 25);
			}
		}

		if ($this->components()->has('Rbac') && $this->Auth->user('id') > 0) {
			$rbacResp = $this->Rbac->checkRequest($controller, $action);
			if ($rbacResp !== null) {
				return $rbacResp;
			}
		}
	}

	public function isAuthorized($user) {
		// APIs de integração ERP: permitir mesmo sem usuário logado (auth por token no header)
		$controller = strtolower($this->request->getParam('controller'));
		$action = strtolower($this->request->getParam('action'));
		$apiActions = [
			'ordensservico' => ['listapi', 'refreshapi'],
			'clientes' => ['addapi', 'listapi'],
			'produtos' => ['addapi', 'listapi'],
			'clicontratos' => ['addapi', 'listapi'],
		];
		if (isset($apiActions[$controller]) && in_array($action, $apiActions[$controller], true)) {
			return true;
		}

		if ($controller === 'pgmassets' && in_array($action, ['css', 'legacycss'], true)) {
			return true;
		}

		// Se não há usuário logado, nega acesso por padrão
		if (empty($user)) {
			return false;
		}

		$prefix = $this->request->getParam('prefix');

		// Regra simples: qualquer rota com prefixo "admin" exige usuário admin
		if ($prefix === 'admin') {
			return !empty($user['admin']);
		}

		// Fora do prefixo admin, por padrão permite
		return true;
	}

	public function jsonResponse($responseData = [], $responseStatusCode = 200) {
		return $this->response
			->withType('application/json')
			->withStatus($responseStatusCode)
			->withStringBody(json_encode(
				$responseData,
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			));
	}
}