<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class OrdensservicoTable extends Table {
	public function initialize(array $config) {
		$this->belongsTo('Clientes')->setForeignKey('idcliente')->setDependent(false);
		$this->belongsTo('Users')->setForeignKey('iduser')->setDependent(false);
		$this->hasMany('Ordemservicositens')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Ordemparcelas')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Ordemhoras')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Ordemmovs')->setForeignKey('id')->setDependent(false);
		$this->hasMany('Itensordem');
		$this->hasMany('Ordemmovs');
	}

	public function limpacarrinho(){
		$this->autoRender = false;
		if(isset($_SESSION['PGM_Idcarrinhoadd'])){
			$carrinho = $this->Itensordem->find('all')->where(['idordempk' => $_SESSION['PGM_Idcarrinhoadd']])->toArray();
			foreach($carrinho as $item) $this->Itensordem->delete($item);
			unset($_SESSION['PGM_Idcarrinhoadd']);
		}
	}
	
	// Retorna um histórico de 4 meses
	public function historicoOrdens($idUser = null, $idempresa = null) {

		$dIni = decreaseMonths(primeiroDiaMes(dataAtual()), 3);
		$dFin = decreaseMonths(ultimoDiaMes(dataAtual()), 3);

		$historico = "";

		for ($i = 1; $i <= 4; $i++) {
			$xKey1 = sizeof($this->find()->where([
				'iduser'   => $idUser,
				'idempresa'   => $idempresa,
				'dataabertura::date >=' => $dIni,
				'dataabertura::date <=' => $dFin
			])->toArray());

			$historico .= "{ mes: '" . descricaoMes($dIni) . "', xKey1: " . $xKey1. " },";
			
			// Desconta 1 mês
			$dIni = increaseMonths($dIni);
			$dFin = ultimoDiaMes($dIni);
		}
		
		return (substr($historico, 0, -1));
	}

	public function criarMov($idordem = null, $sitantiga = null, $sitnova = null, $idempresa = null, $iduser = null, $obs = null) {
		$mov = $this->Ordemmovs->newEntity();
		$mov->idordem = $idordem;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->iduser =  $iduser;
		$mov->idempresa = $idempresa;
		$mov->data = date('d/m/Y H:i:s', time());
		$mov->obs = $obs;

		return $this->Ordemmovs->save($mov);
	}
}
