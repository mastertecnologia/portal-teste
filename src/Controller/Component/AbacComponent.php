<?php
namespace App\Controller\Component;

use App\Utility\AbacQuery;
use Cake\Controller\Component;

/**
 * Aplica escopo ABAC (empresa/cliente/own) em queries conforme mapa e RBAC.
 */
class AbacComponent extends Component {

	/**
	 * @param \Cake\ORM\Query $query
	 * @param string $tableKey chave em config/abac.php → Abac.tables
	 * @param string|null $alias alias da tabela na query
	 * @return \Cake\ORM\Query
	 */
	public function applyToQuery($query, $tableKey, $alias = null) {
		$user = $this->getController()->Auth->user();
		if (!is_array($user)) {
			$user = [];
		}

		return AbacQuery::apply($query, $user, $this->getController(), $tableKey, $alias);
	}
}
