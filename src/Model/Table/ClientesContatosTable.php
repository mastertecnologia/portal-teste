<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ClientesContatosTable extends Table {

	public function initialize(array $config) {
		$this->setTable('clientes_contatos');
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente']);
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmpty('idcliente')
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmpty('idempresa')
			->scalar('nome')
			->maxLength('nome', 120)
			->requirePresence('nome', 'create')
			->notEmpty('nome', __('Informe o nome do contato.'))
			->scalar('cargo')
			->maxLength('cargo', 80)
			->allowEmptyString('cargo')
			->email('email', false, __('E-mail inválido.'))
			->allowEmptyString('email')
			->scalar('fone')
			->maxLength('fone', 30)
			->allowEmptyString('fone')
			->boolean('principal');

		return $validator;
	}
}
