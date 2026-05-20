<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use App\Utility\ErpPrototypeAccess;

/**
 * RBAC alinhado ao menu ERP prototype (config/erp_prototype_rbac.php).
 */
trait ErpPrototypeRbacTrait {

	public function isAuthorized($user) {
		if (empty($user)) {
			return false;
		}
		if ((int)($user['role'] ?? -1) !== 0) {
			$this->_erpPrototypeDenyPortalUser();

			return false;
		}
		$controller = (string)$this->request->getParam('controller');
		$action = (string)$this->request->getParam('action');
		if (!ErpPrototypeAccess::allows((array)$user, $controller, $action)) {
			$this->Flash->error(__('Sem permissão para este módulo no protótipo ERP.'));

			return false;
		}

		return parent::isAuthorized($user);
	}

	protected function _erpPrototypeDenyPortalUser(): void {
	}
}
