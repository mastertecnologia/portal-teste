<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class WorkflowStatesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('workflow_states');
		$this->setDisplayField('nome');
		$this->setPrimaryKey('id');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->scalar('nome')
			->maxLength('nome', 120)
			->requirePresence('nome', 'create')
			->notEmptyString('nome', 'Informe o nome do estado.');

		$validator
			->scalar('codigo')
			->maxLength('codigo', 80)
			->requirePresence('codigo', 'create')
			->notEmptyString('codigo', 'Informe o código (slug).')
			->add('codigo', 'format', [
				'rule' => ['custom', '/^[a-z][a-z0-9_]*$/'],
				'message' => 'Código: apenas letras minúsculas, números e underscore (ex.: aguardando_cliente).',
			]);

		$validator->boolean('is_inicial');
		$validator->boolean('is_final');

		return $validator;
	}

	public function buildRules(RulesChecker $rules) {
		$rules->add($rules->isUnique(['codigo'], 'Já existe um estado com este código.'));

		return $rules;
	}

}
