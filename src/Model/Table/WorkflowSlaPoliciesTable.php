<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class WorkflowSlaPoliciesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('workflow_sla_policies');
		$this->setDisplayField('id');
		$this->belongsTo('Empresas', [
			'foreignKey' => 'empresa_id',
			'joinType' => 'LEFT',
		]);
		$this->belongsTo('WorkflowStates', [
			'foreignKey' => 'workflow_state_id',
			'joinType' => 'INNER',
		]);
		$this->belongsTo('EscalateToStates', [
			'className' => 'WorkflowStates',
			'foreignKey' => 'escalate_to_state_id',
			'joinType' => 'LEFT',
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator->integer('empresa_id')->allowEmpty('empresa_id');
		$validator->requirePresence('workflow_state_id', 'create')
			->notEmpty('workflow_state_id');
		$validator->integer('resposta_minutos')->allowEmpty('resposta_minutos');
		$validator->integer('resolucao_minutos')->allowEmpty('resolucao_minutos');
		$validator->boolean('pausa_sla');
		$validator->boolean('is_final');
		$validator->boolean('auto_escalar');
		$validator->integer('escalate_to_state_id')->allowEmpty('escalate_to_state_id');
		$validator->integer('escalate_after_minutos')->allowEmpty('escalate_after_minutos');

		return $validator;
	}

	public function buildRules(RulesChecker $rules) {
		$rules->add($rules->existsIn(['workflow_state_id'], 'WorkflowStates'));
		$rules->add($rules->existsIn(['escalate_to_state_id'], 'WorkflowStates'), 'escalateToExists', [
			'allowNullable' => true,
		]);

		return $rules;
	}

}
