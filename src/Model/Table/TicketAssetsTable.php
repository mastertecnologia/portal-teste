<?php
namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TicketAssetsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_assets');
		$this->setPrimaryKey('id');
		$this->addBehavior('Timestamp');
		$this->setEntityClass('App\\Model\\Entity\\TicketAsset');

		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Assets', ['foreignKey' => 'asset_id']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator->integer('id')->allowEmptyString('id', null, 'create');
		$validator
			->integer('idempresa')
			->requirePresence('idempresa', 'create')
			->notEmptyString('idempresa');
		$validator
			->integer('ticket_id')
			->requirePresence('ticket_id', 'create')
			->notEmptyString('ticket_id');
		$validator
			->integer('asset_id')
			->requirePresence('asset_id', 'create')
			->notEmptyString('asset_id');
		$validator
			->scalar('papel')
			->maxLength('papel', 16)
			->add('papel', 'inList', [
				'rule' => ['inList', ['afetado', 'relacionado']],
				'message' => 'Papel inválido.',
			])
			->allowEmptyString('papel');
		$validator->integer('user_id')->allowEmptyString('user_id');
		$validator->scalar('observacao')->maxLength('observacao', 255)->allowEmptyString('observacao');

		return $validator;
	}

	public function buildRules(RulesChecker $rules) {
		$rules->add($rules->isUnique(['ticket_id', 'asset_id'], 'Ativo já vinculado a este chamado.'), 'ticket_asset_unique');

		return $rules;
	}
}
