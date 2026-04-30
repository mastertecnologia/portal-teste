<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AtividadesTable extends Table
{

    public function initialize(array $config) {
        $this->belongsTo('Users')->setForeignKey('iduser');
    }

    public function atividadesUsuario($iduser = null) {
        $atividades = $this->find('all')
        ->where(['iduser' => $iduser])
        ->order(['data DESC'])
        ->toArray();

        return $atividades;
    }

    public function atividadesUsuarioAction($iduser = null, $controller = null, $action = null) {
        $atividades = $this->find('all')
        ->where(['iduser' => $iduser], ['controller' => $controller], ['action' => $action])
        ->order(['data DESC'])
        ->toArray();

        return $atividades;
    }

    public function atividadesUsuarioData($iduser = null, $dataini, $datafin) {
        $atividades = $this->find('all')
        ->where(['iduser' => $iduser], ['data >=' => $dataini], ['data <=' => $datafin])
        ->order(['data DESC'])
        ->toArray();

        return $atividades;
    }

	public function registrar($iduser = null, $controller = null, $action = null, $idtable = null) {
		$cols = $this->getSchema()->columns();
		$controllerMax = in_array('controller', $cols, true) ? (int)$this->getSchema()->getColumn('controller')['length'] : 0;
		$actionMax = in_array('action', $cols, true) ? (int)$this->getSchema()->getColumn('action')['length'] : 0;
		$controller = (string)$controller;
		$action = (string)$action;
		if ($controllerMax > 0 && strlen($controller) > $controllerMax) {
			$controller = substr($controller, 0, $controllerMax);
		}
		if ($actionMax > 0 && strlen($action) > $actionMax) {
			$action = substr($action, 0, $actionMax);
		}

		$atividade = $this->newEntity();
		$atividade->iduser = $iduser;
		$atividade->controller = $controller;
		$atividade->action = $action;
		$atividade->idtable = $idtable;
		$atividade->data = date('Y-m-d');
		$atividade->hora = date('H:i:s');

		// Table não possui Flash/redirect; em caso de falha, apenas sinaliza.
		if (!$this->save($atividade)) {
			return false;
		}

		return true;
	}
}
