<?php
namespace App\Controller;

/**
 * Modelos de contrato (contract_templates). Equipe interna (role 0).
 */
class ContractTemplatesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
		$this->loadModel('ContractTemplates');
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? 1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	public function index() {
		$this->set('title', 'Modelos de contrato');
		$idempresa = (int)$this->Auth->user('idempresa');
		try {
			$q = $this->ContractTemplates->find()
				->where(['ContractTemplates.idempresa' => $idempresa])
				->order(['ContractTemplates.modified' => 'DESC']);
			$this->paginate = ['limit' => 30];
			$this->set('templates', $this->paginate($q));
		} catch (\Throwable $e) {
			$this->Flash->error(__('Tabela contract_templates indisponível. Execute a migration do módulo de contratos.'));
			$this->set('templates', []);
		}
	}

	public function add() {
		$this->set('title', 'Novo modelo de contrato');
		$tpl = $this->ContractTemplates->newEntity();
		$idempresa = (int)$this->Auth->user('idempresa');

		if ($this->request->is('post')) {
			$data = $this->_normalizeTemplateData($this->request->getData());
			$data['idempresa'] = $idempresa;
			$data['ativo'] = $this->_boolFromRequest('ativo');
			if (!isset($data['tipo_contrato']) || $data['tipo_contrato'] === '') {
				$data['tipo_contrato'] = 'servico';
			}
			if (!isset($data['versao']) || $data['versao'] === '') {
				$data['versao'] = 1;
			}
			$tpl = $this->ContractTemplates->patchEntity($tpl, $data);
			if ($this->ContractTemplates->save($tpl)) {
				$this->Flash->success(__('Modelo gravado.'));
				return $this->redirect('/modulo-avancado/modelos-contrato');
			}
			$this->Flash->error(__('Não foi possível gravar. Verifique os campos.'));
		}

		$this->set('template', $tpl);
	}

	public function edit($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$tpl = $this->ContractTemplates->get($id);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$tpl->idempresa !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}

		$this->set('title', 'Editar modelo: ' . $tpl->nome);

		if ($this->request->is(['post', 'put'])) {
			$data = $this->_normalizeTemplateData($this->request->getData());
			$data['idempresa'] = $idempresa;
			$data['ativo'] = $this->_boolFromRequest('ativo');
			$tpl = $this->ContractTemplates->patchEntity($tpl, $data);
			if ($this->ContractTemplates->save($tpl)) {
				$this->Flash->success(__('Modelo atualizado.'));
				return $this->redirect('/modulo-avancado/modelos-contrato');
			}
			$this->Flash->error(__('Não foi possível salvar.'));
		}

		$this->set('template', $tpl);
	}

	public function delete($id = null) {
		$this->request->allowMethod(['post']);
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		try {
			$tpl = $this->ContractTemplates->get($id);
		} catch (\Throwable $e) {
			throw new \Cake\Http\Exception\NotFoundException();
		}
		if ((int)$tpl->idempresa !== $idempresa) {
			throw new \Cake\Http\Exception\ForbiddenException();
		}
		if ($this->ContractTemplates->delete($tpl)) {
			$this->Flash->success(__('Modelo excluído.'));
		} else {
			$this->Flash->error(__('Não foi possível excluir.'));
		}
		return $this->redirect('/modulo-avancado/modelos-contrato');
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function _normalizeTemplateData(array $data) {
		foreach (['clausulas_padrao', 'variaveis'] as $key) {
			if (!isset($data[$key])) {
				continue;
			}
			$raw = $data[$key];
			if (is_string($raw)) {
				$t = trim($raw);
				if ($t === '') {
					$data[$key] = [];
					continue;
				}
				$decoded = json_decode($t, true);
				$data[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
			}
		}
		return $data;
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	protected function _boolFromRequest($field) {
		$v = $this->request->getData($field);
		return $v === 1 || $v === '1' || $v === true || $v === 'true';
	}
}

