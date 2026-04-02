<?php
namespace App\Controller;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;

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
				return $this->redirect('/contract-templates');
			}
			$this->Flash->error(__('Não foi possível gravar. Verifique os campos.'));
		}

		$this->set('contractTemplatePlaceholders', $this->_placeholdersForTemplate($tpl));
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
				return $this->redirect('/contract-templates');
			}
			$this->Flash->error(__('Não foi possível salvar.'));
		}

		$this->set('contractTemplatePlaceholders', $this->_placeholdersForTemplate($tpl));
		$this->set('template', $tpl);
	}

	public function clonar($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new NotFoundException();
		}
		try {
			$src = $this->ContractTemplates->get($id);
		} catch (\Throwable $e) {
			throw new NotFoundException();
		}
		if ((int)$src->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}
		$copy = $this->ContractTemplates->newEntity([
			'idempresa' => $idempresa,
			'nome' => trim((string)$src->nome) . ' (cópia)',
			'tipo_contrato' => $src->tipo_contrato,
			'descricao' => $src->descricao,
			'conteudo_html' => $src->conteudo_html,
			'clausulas_padrao' => $src->clausulas_padrao,
			'variaveis' => $src->variaveis,
			'versao' => 1,
			'ativo' => false,
		]);
		if ($this->ContractTemplates->save($copy)) {
			$this->Flash->success(__('Modelo duplicado. Ajuste o nome e ative quando estiver pronto.'));

			return $this->redirect('/contract-templates/edit/' . (int)$copy->id);
		}
		$this->Flash->error(__('Não foi possível duplicar o modelo.'));

		return $this->redirect('/contract-templates');
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
		return $this->redirect('/contract-templates');
	}

	public function preview($id = null) {
		$id = (int)$id;
		$idempresa = (int)$this->Auth->user('idempresa');
		if ($id <= 0) {
			throw new NotFoundException();
		}
		try {
			$tpl = $this->ContractTemplates->get($id);
		} catch (\Throwable $e) {
			throw new NotFoundException();
		}
		if ((int)$tpl->idempresa !== $idempresa) {
			throw new ForbiddenException();
		}
		$this->set('title', __('Pré-visualização: {0}', $tpl->nome));
		$this->set('template', $tpl);
	}

	/**
	 * @param array $data
	 * @return array
	 */
	/**
	 * @param \App\Model\Entity\ContractTemplate $tpl
	 * @return string[]
	 */
	protected function _placeholdersForTemplate($tpl) {
		$defaults = [
			'nome_cliente', 'razao_social', 'cnpj', 'email_cliente',
			'vigencia_inicio', 'vigencia_fim', 'valor_mensal', 'codigo_contrato',
		];
		$extra = [];
		if ($tpl !== null && !empty($tpl->variaveis) && is_array($tpl->variaveis)) {
			foreach ($tpl->variaveis as $k => $v) {
				if (is_string($v) && $v !== '') {
					$extra[] = $v;
				} elseif (is_string($k) && $k !== '' && !is_numeric($k)) {
					$extra[] = $k;
				} elseif (is_array($v) && !empty($v['nome'])) {
					$extra[] = (string)$v['nome'];
				}
			}
		}

		return array_values(array_unique(array_merge($extra, $defaults)));
	}

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

