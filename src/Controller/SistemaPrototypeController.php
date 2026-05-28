<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\ErpPrototypeRbacTrait;
use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

/**
 * Sistema — protótipo (mockup pg-config, pg-empresa, pg-usuarios, pg-auditoria,
 * pg-acesso-central, pg-acesso-papeis, pg-acesso-usuario).
 *
 * Lado-a-lado com ConfigController + UsersController + AuditController +
 * controllers RBAC. Somente leitura.
 */
class SistemaPrototypeController extends AppController {

	use ErpPrototypeRbacTrait;

	public function initialize() {
		parent::initialize();
		$this->loadModel('Users');
	}

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
	}

	/**
	 * pg-config — atalhos.
	 */
	public function config() {
		return $this->redirect(['controller' => 'Config', 'action' => 'index']);
	}

	/**
	 * pg-usuarios — usuários do ERP da empresa ativa.
	 */
	public function usuarios() {
		$empresa = (int)$this->Auth->user('idempresa');
		$rows = [];
		try {
			$rows = $this->Users->find()
				->order(['Users.id' => 'DESC'])
				->limit(200)
				->all()
				->toArray();
		} catch (\Throwable $e) {
			$rows = [];
		}

		$items = [];
		$kpi = ['total' => 0, 'equipe' => 0, 'portal' => 0, 'admins' => 0];
		foreach ($rows as $u) {
			$role = (int)$u->get('role');
			$adminU = (int)$u->get('admin') === 1;
			$idEmp = (int)$u->get('idempresa');
			if ($empresa > 0 && $idEmp !== 0 && $idEmp !== $empresa) {
				continue;
			}
			$kpi['total']++;
			if ($role === 0) {
				$kpi['equipe']++;
			} else {
				$kpi['portal']++;
			}
			if ($adminU) {
				$kpi['admins']++;
			}
			$items[] = [
				'id' => (int)$u->get('id'),
				'nome' => trim((string)($u->get('name') ?? $u->get('username'))),
				'email' => (string)($u->get('email') ?? $u->get('username') ?? ''),
				'role' => $role === 0 ? 'equipe' : 'portal',
				'admin' => $adminU,
				'inativo' => (int)$u->get('inativo') === 1,
				'created' => $u->get('created'),
			];
		}

		$this->set([
			'title' => __('Usuários ERP'),
			'erpNavActive' => 'usuarios',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Usuários'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'usrItems' => $items,
			'usrKpi' => $kpi,
		]);

		return $this->render('usuarios');
	}

	/**
	 * pg-acesso-central — RBAC dashboard.
	 */
	public function acessoCentral() {
		$rolesTotal = 0;
		$usersWithRoles = 0;
		$pendentes = 0;
		try {
			if ($this->tableExists('rbac_roles')) {
				$rolesTotal = TableRegistry::getTableLocator()->get('RbacRoles')->find()->where(['active' => true])->count();
			}
		} catch (\Throwable $e) {
		}
		try {
			if ($this->tableExists('rbac_users_roles')) {
				$usersWithRoles = TableRegistry::getTableLocator()->get('RbacUsersRoles')
					->find()
					->select(['user_id'])
					->group(['user_id'])
					->count();
			}
		} catch (\Throwable $e) {
		}
		try {
			if ($this->tableExists('rbac_access_requests')) {
				$pendentes = TableRegistry::getTableLocator()->get('RbacAccessRequests')
					->find()
					->where(['status IN' => ['pending_manager', 'pending_admin', 'manager_approved']])
					->count();
			}
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Controle de Acesso'),
			'erpNavActive' => 'acesso-central',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Controle de Acesso'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'rbacKpi' => [
				'roles' => $rolesTotal,
				'users_with_roles' => $usersWithRoles,
				'access_pending' => $pendentes,
			],
		]);

		return $this->render('acesso_central');
	}

	/**
	 * pg-acesso-papeis — RBAC papéis.
	 */
	public function acessoPapeis() {
		$items = [];
		try {
			if ($this->tableExists('rbac_roles')) {
				foreach (TableRegistry::getTableLocator()->get('RbacRoles')
					->find()
					->where(['active' => true])
					->order(['sort_order' => 'ASC', 'name' => 'ASC'])
					->all() as $r) {
					$items[] = [
						'id' => (int)$r->get('id'),
						'slug' => (string)$r->get('slug'),
						'name' => (string)$r->get('name'),
						'description' => (string)($r->get('description') ?? ''),
						'is_system' => (int)$r->get('is_system') === 1,
						'hierarchy' => (int)($r->get('hierarchy_level') ?? 0),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Papéis RBAC'),
			'erpNavActive' => 'acesso-papeis',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Controle de Acesso'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral']],
				['label' => __('Papéis'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'roleItems' => $items,
		]);

		return $this->render('acesso_papeis');
	}

	/**
	 * Simulador View-As-User — mostra papéis efetivos e permissões expandidas.
	 */
	public function viewAs() {
		$uid = (int)$this->request->getQuery('user_id', 0);
		$selecionado = null;
		$papeis = [];
		$permissoes = [];

		try {
			$users = $this->Users->find()
				->select(['Users.id', 'Users.name', 'Users.username', 'Users.email', 'Users.role', 'Users.admin'])
				->order(['Users.name' => 'ASC'])
				->limit(200)
				->all();
			$candidatos = [];
			foreach ($users as $u) {
				$nome = trim((string)($u->get('name') ?? $u->get('username')));
				$candidatos[] = [
					'id' => (int)$u->get('id'),
					'nome' => $nome,
					'email' => (string)($u->get('email') ?? $u->get('username') ?? ''),
					'admin' => (int)$u->get('admin') === 1,
				];
				if ($uid > 0 && (int)$u->get('id') === $uid) {
					$selecionado = [
						'id' => $uid,
						'nome' => $nome,
						'email' => (string)($u->get('email') ?? $u->get('username') ?? ''),
						'role' => (int)$u->get('role'),
						'admin' => (int)$u->get('admin') === 1,
					];
				}
			}
		} catch (\Throwable $e) {
			$candidatos = [];
		}

		if ($selecionado !== null) {
			try {
				if ($this->tableExists('rbac_users_roles') && $this->tableExists('rbac_roles')) {
					$ur = TableRegistry::getTableLocator()->get('RbacUsersRoles');
					$roles = TableRegistry::getTableLocator()->get('RbacRoles');
					$roleIds = $ur->find()->select(['role_id'])->where(['user_id' => $uid])->extract('role_id')->toList();
					if ($roleIds !== []) {
						foreach ($roles->find()->where(['id IN' => $roleIds])->all() as $r) {
							$papeis[(int)$r->get('id')] = [
								'id' => (int)$r->get('id'),
								'name' => (string)$r->get('name'),
								'slug' => (string)$r->get('slug'),
							];
						}
					}
					if ($papeis !== [] && $this->tableExists('rbac_roles_permissions') && $this->tableExists('rbac_permissions')) {
						$rp = TableRegistry::getTableLocator()->get('RbacRolesPermissions');
						$perm = TableRegistry::getTableLocator()->get('RbacPermissions');
						$permIds = $rp->find()
							->select(['permission_id'])
							->where(['role_id IN' => array_keys($papeis)])
							->group(['permission_id'])
							->extract('permission_id')
							->toList();
						if ($permIds !== []) {
							foreach ($perm->find()->where(['id IN' => $permIds])->order(['code' => 'ASC'])->all() as $p) {
								$permissoes[] = [
									'code' => (string)$p->get('code'),
									'description' => (string)($p->get('description') ?? ''),
								];
							}
						}
					}
				}
			} catch (\Throwable $e) {
			}
		}

		$this->set([
			'title' => __('Simular acesso'),
			'erpNavActive' => 'acesso-central',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Controle de Acesso'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral']],
				['label' => __('Simular acesso'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'viewAsCandidatos' => $candidatos,
			'viewAsSelecionado' => $selecionado,
			'viewAsPapeis' => array_values($papeis),
			'viewAsPermissoes' => $permissoes,
		]);

		return $this->render('view_as');
	}

	/**
	 * pg-acesso-usuario — ficha de acesso de um usuário (papéis RBAC + dados).
	 */
	public function acessoUsuario() {
		$uid = (int)$this->request->getQuery('user_id', $this->Auth->user('id'));
		$user = null;
		try {
			$user = $this->Users->find()->where(['Users.id' => $uid])->first();
		} catch (\Throwable $e) {
		}

		$papeis = [];
		try {
			if ($this->tableExists('rbac_users_roles') && $this->tableExists('rbac_roles')) {
				$ur = TableRegistry::getTableLocator()->get('RbacUsersRoles');
				$roles = TableRegistry::getTableLocator()->get('RbacRoles');
				$ids = $ur->find()
					->select(['role_id'])
					->where(['user_id' => $uid])
					->extract('role_id')
					->toList();
				if ($ids !== []) {
					foreach ($roles->find()->where(['id IN' => $ids])->all() as $r) {
						$papeis[] = [
							'id' => (int)$r->get('id'),
							'slug' => (string)$r->get('slug'),
							'name' => (string)$r->get('name'),
							'is_system' => (int)$r->get('is_system') === 1,
							'hierarchy' => (int)($r->get('hierarchy_level') ?? 0),
						];
					}
				}
			}
		} catch (\Throwable $e) {
		}

		$pedidos = ['pending' => 0, 'approved_30d' => 0, 'rejected_30d' => 0];
		try {
			if ($this->tableExists('rbac_access_requests')) {
				$ar = TableRegistry::getTableLocator()->get('RbacAccessRequests');
				$pedidos['pending'] = (int)$ar->find()
					->where(['user_id' => $uid, 'status IN' => ['pending_manager', 'pending_admin', 'manager_approved']])
					->count();
				$since = \Cake\I18n\Time::now()->subDays(30);
				$pedidos['approved_30d'] = (int)$ar->find()
					->where(['user_id' => $uid, 'status' => 'admin_approved', 'admin_reviewed_at >=' => $since])
					->count();
				$pedidos['rejected_30d'] = (int)$ar->find()
					->where(['user_id' => $uid, 'status IN' => ['admin_rejected', 'manager_rejected'], 'modified >=' => $since])
					->count();
			}
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Ficha de acesso'),
			'erpNavActive' => 'acesso-usuario',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Controle de Acesso'), 'url' => ['controller' => 'SistemaPrototype', 'action' => 'acessoCentral']],
				['label' => __('Ficha de acesso'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'acessoUserId' => $uid,
			'acessoUser' => $user,
			'acessoPapeis' => $papeis,
			'acessoPedidos' => $pedidos,
		]);

		return $this->render('acesso_usuario');
	}

	/**
	 * pg-auditoria — auditoria (audit_logs).
	 */
	public function auditoria() {
		$items = [];
		try {
			if ($this->tableExists('audit_logs')) {
				foreach (TableRegistry::getTableLocator()->get('AuditLogs')
					->find()
					->order(['created' => 'DESC'])
					->limit(80)
					->all() as $a) {
					$items[] = [
						'id' => (int)$a->get('id'),
						'user_id' => (int)$a->get('user_id'),
						'entity_type' => (string)$a->get('entity_type'),
						'entity_id' => (string)$a->get('entity_id'),
						'action' => (string)$a->get('action'),
						'ip' => (string)$a->get('ip_address'),
						'created' => $a->get('created'),
					];
				}
			}
		} catch (\Throwable $e) {
		}

		$this->set([
			'title' => __('Auditoria · LGPD'),
			'erpNavActive' => 'auditoria',
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => __('Auditoria · LGPD'), 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'audItems' => $items,
		]);

		return $this->render('auditoria');
	}

	/**
	 * @param string $page
	 */
	public function view($page) {
		switch ($page) {
			case 'config':
				return $this->config();
			case 'usuarios':
				return $this->usuarios();
			case 'acesso-central':
				return $this->acessoCentral();
			case 'acesso-papeis':
				return $this->acessoPapeis();
			case 'auditoria':
				return $this->auditoria();
			case 'acesso-usuario':
				return $this->acessoUsuario();
			case 'view-as':
				return $this->viewAs();
			case 'empresa':
				$empresaId = (int)$this->Auth->user('idempresa');
				if ($empresaId > 0) {
					return $this->redirect(['controller' => 'Empresas', 'action' => 'edit', $empresaId]);
				}

				break;
			case 'acesso-auditoria':
				return $this->redirect(['controller' => 'RbacAccessRequests', 'action' => 'auditLogs']);
			case 'acesso-filiais':
				return $this->redirect(['controller' => 'EmpresasPrototype', 'action' => 'lista']);
		}

		if (in_array($page, ['empresa', 'acesso-auditoria', 'acesso-filiais'], true)) {
			$labels = [
				'empresa' => __('Dados da empresa'),
				'acesso-auditoria' => __('Auditoria de acessos'),
				'acesso-filiais' => __('Empresas & Filiais'),
			];
			$this->set([
				'title' => (string)($labels[$page] ?? ucfirst((string)$page)),
				'erpNavActive' => $page,
				'erpBreadcrumb' => [
					['label' => 'PGM ERP'],
					['label' => __('Sistema')],
					['label' => $labels[$page] ?? ucfirst((string)$page), 'cur' => true],
				],
				'erpEmpresas' => $this->loadEmpresasParaTopbar(),
				'page' => $page,
			]);

			return $this->render('placeholder');
		}

		throw new NotFoundException(__('Tela do protótipo não encontrada.'));
	}

	protected function renderSimple(string $page, string $title) {
		$this->set([
			'title' => $title,
			'erpNavActive' => $page,
			'erpBreadcrumb' => [
				['label' => 'PGM ERP'],
				['label' => __('Sistema')],
				['label' => $title, 'cur' => true],
			],
			'erpEmpresas' => $this->loadEmpresasParaTopbar(),
			'page' => $page,
		]);

		return $this->render('placeholder');
	}

	protected function tableExists(string $tbl): bool {
		try {
			$schema = TableRegistry::getTableLocator()->get('Users')->getConnection()->getSchemaCollection();

			return in_array($tbl, $schema->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	protected function loadEmpresasParaTopbar(): array {
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
