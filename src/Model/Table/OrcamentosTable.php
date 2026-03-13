<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrcamentosTable extends Table {
	public function initialize(array $config) {
		$this->setTable('orcamentosnovosdes');
		$this->table('orcamentosnovosdes');
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('idautor')->setDependent(false);
		$this->hasMany('Orcamentosservicos')->setForeignKey('idorcamento');
		$this->hasMany('Orcamentosmovs')->setForeignKey('idorcamento');
		$this->hasMany('Orcamentositens')->setForeignKey('idorcamento');
	}

	public function limpacarrinho(){
		$this->autoRender = false;
		if(isset($_SESSION['idcarrinhondadd'])){
			$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $_SESSION['idcarrinhondadd']])->order(['id'])->toArray();
			foreach($carrinho as $item) $this->Orcamentositens->delete($item);
			unset($_SESSION['idcarrinhondadd']);
		} else return true;
	}
}