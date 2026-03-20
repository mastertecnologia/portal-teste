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

use Cake\Controller\Controller;
use Cake\Event\Event;

class AppController extends Controller {
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
		$this->loadComponent('Security', [
			'unlockedActions' => [
				'login', 'logout', 'loginempresa', 'acessoEmpresa', 'loginduasetapas',
				'add', 'edit',
				// Troca de empresa via dropdown (AJAX) não envia _Token.
				'alteraempresa',
				'carrinho', 'carrinhoadd', 'carrinhoedititem', 'carrinhodelitem', 'valortotal', 'acaoindex',
				'addservico', 'limpacarrinho', 'excluiitemcarrinho', 'getitemcarrinho', 'edititemcarrinho', 'carrinhoedit',
				'timerIniciar', 'timerPausar', 'timerRetomar', 'timerFinalizar',
				'produto', 'qtdestoque', // Orçamentos: busca produto e estoque via AJAX
				// APIs de integração ERP (sem sessão web; token em header)
				'addApi', 'listApi', 'refreshApi', 'addAPI', 'listAPI', 'refreshAPI',
				// Clientes: consulta CNPJ (Receita) e IE (SEFAZ/SINTEGRA) via AJAX
				'consultacnpj', 'consultaIe',
				// API cadastro consolidado (CadastroController)
				'empresa', 'consultar',
				// Tickets UI React (JSON; autenticação via sessão Auth)
				'apiIndex', 'apiIndexCliente', 'apiView', 'apiComments', 'apiSaveTicket',
				'apiAnexoUpload', 'apiAnexoDelete',
				'apiAdd',
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
					  'fields' => ['username' => 'username', 'password' => 'password', 'idempresa' => 'idempresa']
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
		// if (!array_key_exists('_serialize', $this->viewVars)
		// 'authorize' => ['Controller'], && in_array($this->response->type(), ['application/json', 'application/xml'])
		// ) {
			// $this->set('_serialize', true);
		// }
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
			'financeiroActive' => '',
			'areasActive' => '',
			'problemasActive' => '',
			'ordensActive' => '',
			'ticketsActive' => '',
			'senhasActive' => '',
			'faturasActive' => '',
			'config' => '',
		];

		if ($action === "dashboard") {
			$menuStates['dashboard'] = "active";
		}

		if (in_array($controllerLower, ["config", "empresasusers", "empresas", "users", "clientes", "areas", "problemas", "visitas", "feriados"], true)) {
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
			'areas' => 'areasActive',
			'problemas' => 'problemasActive',
			'ordensservico' => 'ordensActive',
			'bancosenhas' => 'senhasActive',
			'faturas' => 'faturasActive',
			'tickets' => 'ticketsActive',
		];

		if (isset($controllerToMenuMap[$controllerLower])) {
			$menuStates[$controllerToMenuMap[$controllerLower]] = "active";
		}

		$this->set('role', $role);
		$this->set('admin', $admin);
		$this->set('setor', $setor);
		$this->set('empresa', $empresa);
		if (!empty($empresa)) $this->set('nomeempresa', $this->Empresas->get($empresa)->razaosocial);
		else $this->set('nomeempresa', 'Grid Sistemas');
		$this->set('idempresa', $this->Auth->user('idempresa'));
		$this->set('name', $this->Auth->user('name'));
		$this->set('permissaoacesso', $this->Auth->user('permissaoacesso'));
		$this->set($menuStates);
		$this->set('idcliente', $idcliente);
		$this->set('iduser', $iduser);

		$url = $this->request->getAttribute('src') . 'template/notificacoes/notificacoes/';
		$this->set('url', $url);

		$empresasOptSidebar = [];
		foreach (
			$this->Empresasusers
				->find('all')
				->where(['iduser' => $iduser])
				->contain(['Empresas' => ['fields' => ['nomefantasia']]])
				->order(['Empresas.nomefantasia' => 'ASC'])
				->toArray() as $reg
		) {
			$empresasOptSidebar[$reg->idempresa] = $reg->empresa->nomefantasia;
		}
		$this->set('empresasOptSidebar', $empresasOptSidebar);
		
		// Obtém os dados atualizados do usuário logado
		if ($this->Auth->user('id') > 0) {
			$user = $this->Users->get($this->Auth->user('id'));
			$this->set('skin', $user->skin);
			$this->set('sidebar', $user->sidebar);
			$this->set('pagelength', $user->pagelength);
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