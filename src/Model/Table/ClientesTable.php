<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Security;

class ClientesTable extends Table {
    public function initialize(array $config) {
        $this->hasMany('Cliacessos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Cliservicos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Clicontint')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('Clicontratos')->setForeignKey('idcliente')->setDependent(true);
        $this->hasMany('ContratosHoras', ['foreignKey' => 'idcliente', 'dependent' => true]);
        $this->belongsTo('Cidades')->setForeignKey('idcidade');
    }

    public function validationDefault(Validator $validator) {
        return $validator
        ->notEmpty('fone', 'Telefone obrigatório!');
    }

    public function generateToken($string) {
      return Security::hash($string, 'sha1', true);
    }

    public function validToken($cnpj, $token) {
		$cliente = $this->find('all', ['fields' => ['id', 'cnpj', 'token']])->where(['cnpj' => $cnpj])->toArray();
		if (count($cliente) > 0 and $cliente[0]['token'] == $token) return true;

		return false;
    }
    	
    public function clientesArr($cliente){
		if($cliente->tipo == C_ClientesTipoFisica) $cliente->cnpj = $cliente->cpf;
		else $cliente->nome = $cliente->razaosocial;
		$cliente->inscest = removeCaracteres($cliente->inscricaoestadual);
		$cliente->codibge = $this->Cidades->get($cliente->idcidade)->codibge;
		$cliente->telefone = $cliente->fone;
		$cliente->celular = $cliente->fone2;
		$cliente->fantasia = $cliente->nomefantasia;
		unset($cliente->inscricaoestadual);
		unset($cliente->inscricaomunicipal);
		unset($cliente->cpf);
		unset($cliente->tipo);
		unset($cliente->razaosocial);
		unset($cliente->idcidade);
		unset($cliente->fone);
		unset($cliente->estado);
		unset($cliente->id);
		unset($cliente->inativo);
		unset($cliente->token);
		unset($cliente->membrodesde);
		unset($cliente->nomefantasia);
		unset($cliente->nomeresponsavel);
		unset($cliente->rg);
		unset($cliente->idempresa);
		unset($cliente->fone2);
		return $cliente;
	}
	
	public function clicontratosArr($cliente){
		$clicontratos = $this->Clicontratos->find('all')->where(['idcliente' => $cliente->id, 'idempresa' => $cliente->idempresa])->toArray();
		foreach($clicontratos as $reg){
			unset($reg->id);
			unset($reg->idempresa);
			unset($reg->idcliente);
		}
		$cliente->Servicos = $clicontratos;

		return $cliente;
	}
}

