<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;

/**
 * Service Desk — módulo de testes (UI espelhada do mockup pgm_erp_completo.html).
 * Não altera ServicedeskController nem Tickets; RBAC reutiliza permissões Servicedesk via RbacChecker::matchAction.
 */
class ServicedeskPrototypeController extends AppController {

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function isAuthorized($user) {
		if (empty($user) || (int)($user['role'] ?? -1) !== 0) {
			return false;
		}

		return parent::isAuthorized($user);
	}

	/**
	 * GET /servicedesk-prototype — dashboard executivo (dados mock).
	 */
	public function index() {
		$this->set('title', 'Service Desk — Dashboard (protótipo)');
	}

	/**
	 * GET /servicedesk-prototype/fila — placeholder para navegação do protótipo.
	 */
	public function fila() {
		$this->set('title', 'Service Desk — Fila técnica (protótipo)');
	}

}
