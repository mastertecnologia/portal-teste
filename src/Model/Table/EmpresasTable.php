<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Utility\Security;

class EmpresasTable extends Table{
	public function initialize(array $config){
		parent::initialize($config);
		$this->setTable('empresas');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');

		$this->hasMany('Empresasusers')->setForeignKey('idempresa');
		$this->hasMany('Ordensservico')->setForeignKey('idempresa');
		$this->hasMany('Orcamentos')->setForeignKey('idempresa');
		$this->hasMany('Tickets')->setForeignKey('idempresa');
		$this->hasMany('Faturas')->setForeignKey('idempresa');
		$this->hasMany('ContractTemplates')->setForeignKey('idempresa');
		$this->belongsTo('Cidades')->setForeignKey('idcidade');
	}

	public function selectAllByInativa($inativa) {
		return $this->find('all', ['fields' => ['id', 'nomefantasia', 'email', 'token', 'nrousuarios', 'created']])->where(['inativa' => $inativa])->toArray();
	}

	public function selectAllNrousuarios() {
		return $this->find('list', ['keyField' => 'id', 'valueField' => 'nrousuarios'])->order(['id'])->toArray();
	}

	public function selectAllEmpresas() {
		return $this->find('list', ['keyField' => 'id', 'valueField' => 'nomefantasia'])->order(['id'])->toArray();
	}

	public function validationDefault(Validator $validator) {
		return $validator
		->notEmpty('razaosocial', 'Razão social obrigatória!')
		->notEmpty('cnpj', 'CNPJ obrigatório!')
		->notEmpty('fone', 'Telefone obrigatório!')
		->add('cnpj', [
				'unique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
						'message' => 'Já existe uma empresa com este cnpj!'
				]
		]);
	}
	
	public function generateToken($cnpj) {
		return Security::hash($cnpj, 'sha1', true);
	}

	public function validToken($id, $token) {
		$empresa = $this->find('all', [ 'fields' => ['id', 'cnpj', 'token']])
		->where(['id' => $id])
		->toArray();

		if (count($empresa) > 0 and $empresa[0]['token'] == $token) return true;

		return false;
	}

	// Status
	public function incrementArea($idempresa = null) {
		$empresa = $this->get($idempresa);
		$empresa->prxarea = $empresa->prxarea+1;
		$this->save($empresa);

		return $empresa->prxarea;
	}

	public function decrementArea($idempresa = null, $idarea = 0) {
		$empresa = $this->get($idempresa);

		if ($idarea == $empresa->prxarea || $idarea == 0) {
			$empresa->prxarea = $empresa->prxarea-1;
			$this->save($empresa);
		}
	}

	// Problemas
	public function incrementProblema($idempresa = null) {
		$empresa = $this->get($idempresa);
		$empresa->prxproblema = $empresa->prxproblema+1;
		$this->save($empresa);

		return $empresa->prxproblema;
	}

	public function decrementProblema($idempresa = null, $idproblema = 0) {
		$empresa = $this->get($idempresa);

		if ($idproblema == $empresa->prxproblema || $idproblema == 0) {
				$empresa->prxproblema = $empresa->prxproblema-1;
				$this->save($empresa);
		}
	}

	// Próximo número para a ordem
	public function prxOrdem($idempresa = null) {
		$empresa = $this->get($idempresa);
		$bExiste = true;
		$prxordem = $empresa->prxordem;

		while($bExiste == true) {
			$prxordem = $prxordem+1;
			if(empty($this->Ordensservico->findById($prxordem)->where(['idempresa' => $idempresa])->first())) {
				return $prxordem;
			}
		}

		return 1;
	}

	// Ordens de Serviço
	public function incrementOrdem($idempresa = null) {
		$empresa = $this->get($idempresa);
		$bExiste = true;
		while($bExiste == true) {
			$empresa->prxordem = $empresa->prxordem+1;
			if(empty($this->Ordensservico->findById($empresa->prxordem)->where(['idempresa' => $idempresa])->first())) $bExiste = false;
		}

		$this->save($empresa);
		return $empresa->prxordem;
	}

	public function decrementOrdem($idempresa = null, $idordem = 0) {
		$empresa = $this->get($idempresa);

		if ($idordem == $empresa->prxordem || $idordem == 0) {
			$empresa->prxordem = $empresa->prxordem-1;
			$this->save($empresa);
		}
	}

	// Orçamentos
	public function incrementOrcamento($idempresa = null) {
		$empresa = $this->get($idempresa);

		$bExiste = true;
		while($bExiste == true) {
			$empresa->prxorcamento = $empresa->prxorcamento+1;
			if(empty($this->Orcamentos->findById($empresa->prxorcamento)->where(['idempresa' => $idempresa])->first())) $bExiste = false;
		}
	
		return $empresa->prxorcamento;
	}

	public function decrementOrcamento($idempresa = null, $idorcamento = 0) {
		$empresa = $this->get($idempresa);

		if ($idorcamento == $empresa->prxorcamento || $idorcamento == 0) {
			$empresa->prxorcamento = $empresa->prxorcamento-1;
			$this->save($empresa);
		}
	}
	
	// Tickets
	public function incrementTickets($idempresa = null) {
		$empresa = $this->get($idempresa);

		$bExiste = true;
		while($bExiste == true) {
			$empresa->prxticket = $empresa->prxticket+1;
			if(empty($this->Tickets->findById($empresa->prxticket)->where(['idempresa' => $idempresa])->first())) $bExiste = false;
		}

		return $empresa->prxticket;
	}

	public function decrementTickets($idempresa = null, $idticket = 0) {
		$empresa = $this->get($idempresa);

		if ($idorcamento == $empresa->prxticket || $idticket == 0) {
			$empresa->prxticket = $empresa->prxticket-1;
			$this->save($empresa);
		}
	}

	// Próximo número para a fatura
	public function prxFatura($idempresa = null) {
		$empresa = $this->get($idempresa);
		$bExiste = true;
		$prxfatura = $empresa->prxfatura;

		while($bExiste == true) {
			if(empty($this->Faturas->findById($prxfatura)->where(['idempresa' => $idempresa])->first())) {
				return $prxfatura;
			}
			$prxfatura = $prxfatura+1;
		}

		return 1;
	}

	// Faturas
	public function incrementFatura($idempresa = null) {
		$empresa = $this->get($idempresa);
		$bExiste = true;

		while($bExiste == true) {
			$empresa->prxfatura = $empresa->prxfatura+1;
			if(empty($this->Faturas->findById($empresa->prxfatura)->where(['idempresa' => $idempresa])->first())) {
				$bExiste = false;
				$this->save($empresa);
			}
		}
	}

	public function decrementFatura($idempresa = null, $idfatura = 0) {
		$empresa = $this->get($idempresa);

		if ($idfatura == $empresa->prxfatura || $idfatura == 0) {
			$empresa->prxfatura = $empresa->prxfatura-1;
			$this->save($empresa);
		}
	}
}
