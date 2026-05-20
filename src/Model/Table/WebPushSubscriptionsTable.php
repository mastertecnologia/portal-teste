<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class WebPushSubscriptionsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('web_push_subscriptions');
		$this->setDisplayField('id');
		$this->setPrimaryKey('id');
		$this->setEntityClass('App\Model\Entity\WebPushSubscription');
		$this->addBehavior('Timestamp');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('user_id')->requirePresence('user_id', 'create')->notEmpty('user_id')
			->scalar('endpoint')->notEmpty('endpoint')
			->scalar('endpoint_hash')->maxLength('endpoint_hash', 64)->notEmpty('endpoint_hash')
			->scalar('p256dh')->maxLength('p256dh', 200)
			->scalar('auth')->maxLength('auth', 100);

		return $validator;
	}
}
