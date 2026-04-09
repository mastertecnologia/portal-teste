<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacRolesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_roles');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacRole');
	}

	/**
	 * Papéis de sistema usados no rollout (paridade com PermissoesController::_ensureDefaultRoles).
	 */
	public function ensureDefaultSystemRoles(): void {
		$defaults = [
			['slug' => 'super_admin', 'name' => 'Super administrador', 'description' => 'Acesso total ao catálogo quando vinculado.', 'sort_order' => 10, 'hierarchy_level' => 10000],
			['slug' => 'admin_equipe', 'name' => 'Administrador da equipe', 'description' => 'Usuários internos, filas e parâmetros.', 'sort_order' => 20, 'hierarchy_level' => 8000],
			['slug' => 'operacao', 'name' => 'Operação', 'description' => 'OS, tickets, orçamentos, agenda.', 'sort_order' => 30, 'hierarchy_level' => 5000],
			['slug' => 'financeiro', 'name' => 'Financeiro', 'description' => 'Locação e faturas.', 'sort_order' => 40, 'hierarchy_level' => 5000],
			['slug' => 'leitura', 'name' => 'Somente leitura', 'description' => 'Consulta sem alteração.', 'sort_order' => 50, 'hierarchy_level' => 500],
			['slug' => 'cliente_portal', 'name' => 'Cliente portal', 'description' => 'Usuário externo (ABAC por cliente).', 'sort_order' => 60, 'hierarchy_level' => 100],
		];
		foreach ($defaults as $d) {
			$exists = $this->find()->where(['slug' => $d['slug']])->first();
			if ($exists) {
				continue;
			}
			$e = $this->newEntity($d + ['is_system' => true, 'active' => true]);
			$this->save($e);
		}
	}
}
