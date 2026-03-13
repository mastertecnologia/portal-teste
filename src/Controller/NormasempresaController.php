<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class NormasempresaController  extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function index() {
		$this->set('title', 'Normas da Empresa');
	}
	public function contato() {
		$this->set('title', 'Contato');
	}

	public function dirNormas($tipo) {
		return (WWW_ROOT . 'arquivos' . DS . 'normas' . DS . TipoNorma($tipo));
	}

	public function downloadFile($arquivo) {
		ob_start();
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($arquivo));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($arquivo));
		ob_clean();
		readfile($arquivo);
		exit;
	}

	public function download($tipo = null) {
		$this->set('title', 'Normas da Empresa');
        $this->autoRender = false;

		if ($this->request->is('get')) {
			$this->downloadFile($this->dirNormas($tipo));
		}
	}

	public function acessoremoto() {
		$this->set('title', 'Acesso Remoto');
	}

}
