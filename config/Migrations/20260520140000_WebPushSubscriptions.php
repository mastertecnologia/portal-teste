<?php
use Migrations\AbstractMigration;

/**
 * Inscrições Web Push (cliente + endpoint VAPID por usuário).
 */
class WebPushSubscriptions extends AbstractMigration {

	public function change() {
		if ($this->hasTable('web_push_subscriptions')) {
			return;
		}
		$table = $this->table('web_push_subscriptions');
		$table
			->addColumn('user_id', 'integer', ['null' => false])
			->addColumn('idempresa', 'integer', ['null' => true, 'default' => null])
			->addColumn('endpoint', 'text', ['null' => false])
			->addColumn('endpoint_hash', 'string', ['limit' => 64, 'null' => false, 'comment' => 'sha256 do endpoint'])
			->addColumn('p256dh', 'string', ['limit' => 200, 'null' => false])
			->addColumn('auth', 'string', ['limit' => 100, 'null' => false])
			->addColumn('user_agent', 'string', ['limit' => 255, 'null' => true, 'default' => null])
			->addColumn('last_seen_at', 'datetime', ['null' => true, 'default' => null])
			->addColumn('inativo', 'integer', ['null' => false, 'default' => 0])
			->addTimestamps('created', 'modified')
			->addIndex(['user_id'])
			->addIndex(['endpoint_hash'], ['unique' => true])
			->create();
	}
}
