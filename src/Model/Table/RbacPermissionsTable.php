<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class RbacPermissionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('rbac_permissions');
		$this->setDisplayField('name');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\RbacPermission');
	}

	/**
	 * Insere em rbac_permissions as entradas de config/permissions_registry.php que ainda não existem.
	 * Paridade com PermissoesController::adminSyncRegistry (sem HTTP).
	 *
	 * @return array{inserted:int, errors:string[]}
	 */
	public function syncMissingFromRegistry(): array {
		$file = CONFIG . 'permissions_registry.php';
		if (!is_file($file)) {
			return ['inserted' => 0, 'errors' => ['Arquivo config/permissions_registry.php não encontrado.']];
		}
		$registry = require $file;
		if (!is_array($registry)) {
			return ['inserted' => 0, 'errors' => ['Registry inválido.']];
		}
		$inserted = 0;
		$errors = [];
		foreach ($registry as $row) {
			if (empty($row['code'])) {
				continue;
			}
			$exists = $this->find()->where(['code' => $row['code']])->first();
			if ($exists) {
				continue;
			}
			$entity = $this->newEntity([
				'code' => $row['code'],
				'name' => isset($row['name']) ? $row['name'] : $row['code'],
				'module' => isset($row['module']) ? $row['module'] : 'Outros',
				'controller' => isset($row['controller']) ? $row['controller'] : '',
				'action' => isset($row['action']) ? $row['action'] : '*',
				'perm_type' => isset($row['perm_type']) ? $row['perm_type'] : 'rbac',
				'abac_scope' => isset($row['abac_scope']) ? $row['abac_scope'] : null,
				'description' => isset($row['description']) ? $row['description'] : null,
				'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
			]);
			if ($this->save($entity)) {
				$inserted++;
			} else {
				$errors[] = (string)$row['code'] . ': ' . json_encode($entity->getErrors(), JSON_UNESCAPED_UNICODE);
			}
		}

		return ['inserted' => $inserted, 'errors' => $errors];
	}
}
