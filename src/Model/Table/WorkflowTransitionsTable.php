<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class WorkflowTransitionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('workflow_transitions');
		$this->belongsTo('FromStates', [
			'className' => 'WorkflowStates',
			'foreignKey' => 'from_state_id',
		]);
		$this->belongsTo('ToStates', [
			'className' => 'WorkflowStates',
			'foreignKey' => 'to_state_id',
		]);
		$this->belongsTo('Empresas', [
			'foreignKey' => 'empresa_id',
			'joinType' => 'LEFT',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator->requirePresence('from_state_id', 'create')->notEmpty('from_state_id');
		$validator->requirePresence('to_state_id', 'create')->notEmpty('to_state_id');
		$validator->integer('empresa_id')->allowEmpty('empresa_id');

		return $validator;
	}

	public function buildRules(RulesChecker $rules) {
		$rules->add($rules->existsIn(['from_state_id'], 'FromStates'));
		$rules->add($rules->existsIn(['to_state_id'], 'ToStates'));

		return $rules;
	}

}
