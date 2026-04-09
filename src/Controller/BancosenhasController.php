<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\VaultCrypto;
use Cake\Event\Event;

/**
 * Cofre de senhas: persistência via VaultCrypto (legado PGM ou AES-256-CBC com VAULT_ENCRYPTION_KEY).
 * Transporte: vaultReveal em POST + JSON — não enviar senha administrativa na query string.
 */
$__pgmUtilities = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'Utilities.php';
if (is_file($__pgmUtilities)) {
	require_once $__pgmUtilities;
}
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

class BancosenhasController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas');
	}	
	
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	public function index() {
		$this->set('title', 'Banco de Senhas');
		$this->set('hideLayoutPageTitle', true);
		$idempresa = $this->Auth->user('idempresa');
		$senhas = $this->Bancosenhas
			->find('all')
			->where(['idempresa' => $idempresa])
			->order(['nomeservico' => 'ASC', 'id' => 'ASC'])
			->all();
		$this->set('senhas', $senhas);

		$meta = [];
		foreach ($senhas as $r) {
			$meta[] = [
				'id' => (int)$r->id,
				'nomeservico' => (string)$r->nomeservico,
				'provedor' => (string)$r->provedor,
				'ip' => (string)$r->ip,
				'porta' => (string)$r->porta,
				'usuario' => (string)$r->usuario,
				'url' => (string)$r->url,
				'protocolo' => (string)$r->protocolo,
			];
		}
		$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
		$this->set('vaultMetaJson', json_encode($meta, $flags));
	}

	public function add() {
		$senha = $this->Bancosenhas->newEntity();
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();

			if ($data['confirmasenha'] !== $data['senha']) {
				$this->Flash->error(__('As senhas não conferem.'));
				return $this->redirect(['action' => 'index']);
			}
			$plain = $data['senha'];
			unset($data['senha'], $data['confirmasenha']);
			$senha = $this->Bancosenhas->patchEntity($senha, $data);
			$senha->idempresa = $this->Auth->user('idempresa');
			try {
				$senha->senha = VaultCrypto::encrypt($plain, $senha->idempresa);
			} catch (\Exception $e) {
				$this->Flash->error($e->getMessage());

				return $this->redirect(['action' => 'add']);
			}

			if ($this->Bancosenhas->save($senha)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
				$this->Flash->success(__('A senha foi cadastrada com sucesso!.'));
				return $this->redirect(['action' => 'index']);
			}
			
			$this->Flash->error(__('Não foi possível cadastrar a senha.'));
		}

		$this->set('senha', $senha);
		$this->set('title', 'Cadastro de Senha');
		$this->set('hideLayoutPageTitle', true);
		$this->set('vaultDedicatedKey', VaultCrypto::isDedicatedKeyEnabled());
	}

	public function edit($id = null) {
		$senha = $this->Bancosenhas->get($id);
		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para editar este registro.');
			return $this->redirect(['action' => 'index']);
		}
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			unset($data['senha'], $data['confirmasenha']);

			$senha = $this->Bancosenhas->patchEntity($senha, $data);

			if ($this->Bancosenhas->save($senha)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
				$this->Flash->success(__('O registro foi alterado com sucesso!.'));
				return $this->redirect(['action' => 'index']);
			}
			
			$this->Flash->error(__('Não foi possível alterar o registro.'));
		}

		$this->set('senha', $senha);
		$this->set('title', 'Edição');
		$this->set('hideLayoutPageTitle', true);
		$this->set('vaultDedicatedKey', VaultCrypto::isDedicatedKeyEnabled());
	}

	public function isAuthorized($user) {
		// Apenas usuários administradores podem acessar o banco de senhas
		return !empty($user) && !empty($user['admin']);
	}

	public function delete($id = null) {
		$senha = $this->Bancosenhas->get($id);
		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para excluir este registro.');
			return $this->redirect(['action' => 'index']);
		}
		
		if ($this->Bancosenhas->delete($senha)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $senha->id);
			$this->Flash->success(__('O registro foi deletado com sucesso!'));
			return $this->redirect(['action' => 'index']);
		}
		
	}
	
	/**
	 * Legado: use POST com id + senha_administrativa (corpo). GET na URL foi descontinuado por segurança.
	 */
	public function verificasenha($id = null, $senhaadm = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;

		$id = $this->request->getData('id') ?: $id;
		$senhaadm = $this->request->getData('senha_administrativa');
		if ($senhaadm === null) {
			$senhaadm = $this->request->getData('senhaadministrativa');
		}

		$payload = $this->_vaultDecryptPayload((int)$id, (string)$senhaadm);
		$this->response = $this->response->withType('text/plain; charset=UTF-8');

		return $this->response->withStringBody(
			$payload['ok'] ? $payload['secret'] : $payload['error']
		);
	}

	/**
	 * Revela senha do cofre (POST JSON ou form): valida sessão, empresa e senha administrativa.
	 * Resposta: { ok, password? , error? }
	 */
	public function vaultReveal() {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;

		$id = (int)$this->request->getData('id');
		$adminPass = $this->request->getData('senha_administrativa');
		if ($adminPass === null) {
			$adminPass = $this->request->getData('senhaadministrativa');
		}
		$adminPass = $adminPass !== null ? (string)$adminPass : '';

		$payload = $this->_vaultDecryptPayload($id, $adminPass);
		$this->response = $this->response->withType('application/json; charset=UTF-8');
		if ($payload['ok']) {
			$body = json_encode(['ok' => true, 'password' => $payload['secret']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		} else {
			$body = json_encode(['ok' => false, 'error' => $payload['error']], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		}

		return $this->response->withStringBody($body);
	}

	/**
	 * @param int    $id
	 * @param string $adminPassPlain
	 * @return array{ok:bool, secret?:string, error?:string}
	 */
	protected function _vaultDecryptPayload($id, $adminPassPlain) {
		if ($id <= 0 || $adminPassPlain === '') {
			return ['ok' => false, 'error' => 'Dados inválidos.'];
		}

		try {
			$senha = $this->Bancosenhas->get($id);
		} catch (\Exception $e) {
			return ['ok' => false, 'error' => 'Registro não encontrado.'];
		}

		if ((int)$senha->idempresa !== (int)$this->Auth->user('idempresa')) {
			return ['ok' => false, 'error' => 'Operação não permitida.'];
		}

		$empresa = $this->Empresas->get($senha->idempresa);
		if ($empresa->senhaadministrativa != criptografasenha($adminPassPlain)) {
			return ['ok' => false, 'error' => 'Senha administrativa da empresa incorreta.'];
		}

		try {
			$plain = VaultCrypto::decrypt($senha->senha, $senha->idempresa);
		} catch (\Exception $e) {
			return ['ok' => false, 'error' => 'Não foi possível descriptografar o registro.'];
		}

		return ['ok' => true, 'secret' => $plain];
	}

	public function changePassword($id = null) {
		$this->set('title', 'Alterar Senha');

		$senha = $this->Bancosenhas->get($id);

		if ($senha->idempresa != $this->Auth->user('idempresa')) {
			$this->Flash->error('Você não possui permissão para alterar esta senha.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$p1 = isset($data['password1']) ? $data['password1'] : '';
			$p2 = isset($data['password2']) ? $data['password2'] : '';
			$old = isset($data['old_password']) ? $data['old_password'] : '';

			if ($p1 !== $p2) {
				$this->Flash->error('As senhas não conferem!');

				return $this->redirect(['action' => 'changePassword', $id]);
			}

			try {
				$senhaatual = VaultCrypto::decrypt($senha->senha, $senha->idempresa);
			} catch (\Exception $e) {
				$this->Flash->error('Não foi possível ler a senha atual do cofre.');

				return $this->redirect(['action' => 'changePassword', $id]);
			}

			if ($old !== $senhaatual) {
				$this->Flash->error('A senha antiga não confere!');

				return $this->redirect(['action' => 'changePassword', $id]);
			}

			try {
				$encryptionNova = VaultCrypto::encrypt($p1, $senha->idempresa);
			} catch (\Exception $e) {
				$this->Flash->error($e->getMessage());

				return $this->redirect(['action' => 'changePassword', $id]);
			}

			$senha->senha = $encryptionNova;
			unset($data['senha'], $data['confirmasenha'], $data['password1'], $data['password2'], $data['old_password']);
			$senha = $this->Bancosenhas->patchEntity($senha, $data);

			if ($this->Bancosenhas->save($senha)) {
				$this->Flash->success('A senha foi alterada com sucesso!');

				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->action, $id);
				return $this->redirect(['action' => 'index']);
			}
			$this->Flash->error('Ocorreu um erro ao salvar a senha!');
		}

		$this->set('senha', $senha);
		$this->set('hideLayoutPageTitle', true);
		$this->set('vaultDedicatedKey', VaultCrypto::isDedicatedKeyEnabled());
	}
}

