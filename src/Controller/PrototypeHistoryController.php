<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use App\Service\PrototypeApiUsageService;
use App\Utility\PortalUrlPath;
use Cake\Event\Event;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;

/**
 * Histórico global de transições (prototype_status_history) + estatísticas de APIs.
 */
class PrototypeHistoryController extends AppController {

	public function beforeFilter(Event $event) {
		$redirect = $this->request->getRequestTarget();
		$staffLogin = [
			'controller' => 'Users',
			'action' => 'acessoEmpresa',
			'prefix' => false,
			'?' => ['redirect' => $redirect],
		];
		$this->Auth->setConfig('loginAction', $staffLogin);
		$this->Auth->setConfig('unauthorizedRedirect', $staffLogin);
		parent::beforeFilter($event);
		$this->_applyErpLocaleFromSession();
		$this->viewBuilder()->setLayout('erp_prototype');
		$action = (string)$this->request->getParam('action');
		if ($action === 'index' && (int)$this->Auth->user('admin') !== 1) {
			$this->Flash->error(__('Histórico de transições restrito a administradores.'));
		}
	}

	/**
	 * GET /prototype-history — tabela filtrável de transições.
	 */
	public function index() {
		$empresa = (int)$this->Auth->user('idempresa');
		$q = $this->request->getQueryParams();
		$type = trim((string)($q['type'] ?? ''));
		$userId = (int)($q['user'] ?? 0);
		$de = trim((string)($q['de'] ?? ''));
		$ate = trim((string)($q['ate'] ?? ''));
		$busca = trim((string)($q['q'] ?? ''));

		$allowedTypes = ['orcamento', 'os', 'ticket', 'rbac', 'fatura'];
		if ($type !== '' && !in_array($type, $allowedTypes, true)) {
			$type = '';
		}

		$rows = [];
		$users = [];
		$tableMissing = false;
		try {
			$tbl = TableRegistry::getTableLocator()->get('PrototypeStatusHistory');
			$cond = ['idempresa' => $empresa];
			if ($type !== '') {
				$cond['source_type'] = $type;
			}
			if ($userId > 0) {
				$cond['actor_user_id'] = $userId;
			}
			if ($de !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $de)) {
				$cond['created >='] = $de . ' 00:00:00';
			}
			if ($ate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ate)) {
				$cond['created <='] = $ate . ' 23:59:59';
			}
			if ($busca !== '') {
				if (ctype_digit($busca)) {
					$cond['source_id'] = (int)$busca;
				} else {
					$cond['OR'] = [
						'note ILIKE' => '%' . $busca . '%',
						'actor_name ILIKE' => '%' . $busca . '%',
						'status_to ILIKE' => '%' . $busca . '%',
					];
				}
			}
			$query = $tbl->find()->where($cond)->order(['created' => 'DESC'])->limit(500);
			foreach ($query->all() as $r) {
				$st = (string)$r->get('source_type');
				$sid = (int)$r->get('source_id');
				$rows[] = [
					'id' => (int)$r->get('id'),
					'type' => $st,
					'type_label' => $this->_typeLabel($st),
					'source_id' => $sid,
					'from' => (string)($r->get('status_from') ?? '—'),
					'to' => (string)$r->get('status_to'),
					'user_id' => (int)($r->get('actor_user_id') ?? 0),
					'user_name' => (string)($r->get('actor_name') ?? '—'),
					'note' => (string)($r->get('note') ?? ''),
					'created' => $r->get('created'),
					'url' => $this->_sourceUrl($st, $sid),
				];
				$uid = (int)($r->get('actor_user_id') ?? 0);
				$uname = trim((string)($r->get('actor_name') ?? ''));
				if ($uid > 0 && $uname !== '') {
					$users[$uid] = $uname;
				}
			}
			asort($users);
		} catch (\Throwable $e) {
			$tableMissing = true;
		}

		$isAdmin = (int)$this->Auth->user('admin') === 1;
		$apiStats = $isAdmin ? (new PrototypeApiUsageService())->top(20) : [];

		$this->set([
			'title' => __('Histórico de transições'),
			'erpNavActive' => 'prototype-history',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Histórico'), 'cur' => true],
			],
			'erpEmpresas' => $this->_loadEmpresasParaTopbar(),
			'histRows' => $rows,
			'histUsers' => $users,
			'histFiltros' => [
				'type' => $type,
				'user' => $userId > 0 ? (string)$userId : '',
				'de' => $de,
				'ate' => $ate,
				'q' => $busca,
			],
			'histTableMissing' => $tableMissing,
			'histApiStats' => $apiStats,
			'histTypeOptions' => [
				'' => __('Todos os tipos'),
				'orcamento' => __('Orçamento'),
				'os' => __('Ordem de serviço'),
				'ticket' => __('Ticket'),
				'rbac' => __('RBAC'),
				'fatura' => __('Fatura'),
			],
		]);
	}

	/**
	 * GET /prototype-history/set-locale/:locale — persiste idioma na sessão.
	 */
	public function setLocale($locale = 'pt_BR') {
		$this->request->allowMethod(['get', 'post']);
		$allowed = ['pt_BR' => 'pt_BR', 'en' => 'en_US', 'en_US' => 'en_US', 'es' => 'es', 'es_ES' => 'es'];
		$key = (string)$locale;
		$loc = $allowed[$key] ?? 'pt_BR';
		$this->request->getSession()->write('Erp.locale', $loc);
		I18n::setLocale($loc);
		$back = PortalUrlPath::sanitizeInternalRedirect($this->request->getQuery('redirect'));
		if ($back === null || $back === '') {
			$back = PortalUrlPath::sanitizeInternalRedirect($this->referer('/', true));
		}
		if ($back === null || $back === '') {
			$back = $this->Url->build(['controller' => 'Users', 'action' => 'dashboard']);
		}

		return $this->redirect($back);
	}

	protected function _applyErpLocaleFromSession(): void {
		$loc = (string)$this->request->getSession()->read('Erp.locale');
		if (in_array($loc, ['pt_BR', 'en_US', 'es'], true)) {
			I18n::setLocale($loc);
		}
	}

	protected function _typeLabel(string $type): string {
		$map = [
			'orcamento' => __('Orçamento'),
			'os' => __('OS'),
			'ticket' => __('Ticket'),
			'rbac' => __('RBAC'),
			'fatura' => __('Fatura'),
		];

		return (string)($map[$type] ?? ucfirst($type));
	}

	/**
	 * @return array<string,mixed>|null
	 */
	protected function _sourceUrl(string $type, int $id) {
		if ($id <= 0) {
			return null;
		}
		if ($type === 'orcamento') {
			return ['controller' => 'OrcamentosPrototype', 'action' => 'detalhe', $id];
		}
		if ($type === 'os') {
			return ['controller' => 'OrdensservicoPrototype', 'action' => 'detalhe', $id];
		}
		if ($type === 'ticket') {
			return ['controller' => 'ServicedeskPrototype', 'action' => 'ticket', $id];
		}

		return null;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function _loadEmpresasParaTopbar(): array {
		try {
			$tbl = $this->loadModel('Empresas');
		} catch (\Throwable $e) {
			return [];
		}
		$active = (int)$this->Auth->user('idempresa');
		$out = [];
		try {
			foreach ($tbl->find()->order(['id' => 'ASC'])->limit(20)->all() as $e) {
				$nome = (string)($e->get('razaosocial') ?? $e->get('nome') ?? '');
				if ($nome === '') {
					continue;
				}
				$out[] = [
					'id' => (int)$e->get('id'),
					'nome' => $nome,
					'cnpj' => (string)($e->get('cnpj') ?? ''),
					'current' => (int)$e->get('id') === $active,
				];
			}
		} catch (\Throwable $e) {
			return [];
		}

		return $out;
	}
}
