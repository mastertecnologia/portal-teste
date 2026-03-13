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
		$atividade = $this->newEntity();
		$atividade->iduser = $iduser;
		$atividade->controller = $controller;
		$atividade->action = $action;
		$atividade->idtable = $idtable;
		$atividade->data = date('d/m/Y');
		$atividade->hora = date('H:i');

		if (!$this->save($atividade)) {
			$this->Flash->error(__('Não foi possível registrar a atividade.'));
		}

		return true;
	}
}
