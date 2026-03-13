<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class AtividadesController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('User');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}
}
