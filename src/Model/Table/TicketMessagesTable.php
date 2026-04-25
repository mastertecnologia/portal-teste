<?php
namespace App\Model\Table;

use Cake\ORM\Table;

class TicketMessagesTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_messages');
		$this->setPrimaryKey('id');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id']);
		// Sem try/catch, qualquer acesso a TicketsController rebenta se a migration
		// ticket_messages ainda não tiver corrido (getSchema() descreve a tabela).
		try {
			$schema = $this->getSchema();
			if (in_array('metadata', $schema->columns(), true)) {
				$schema->setColumnType('metadata', 'json');
			}
		} catch (\Throwable $e) {
			// #region agent log
			$line = json_encode([
				'sessionId' => 'd63dd9',
				'hypothesisId' => 'H3',
				'location' => 'TicketMessagesTable::initialize:schema',
				'message' => $e->getMessage(),
				'data' => ['class' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()],
				'timestamp' => (int) round(microtime(true) * 1000),
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
			@file_put_contents(ROOT . DS . 'debug-d63dd9.log', $line, FILE_APPEND);
			@file_put_contents(TMP . 'debug-d63dd9.log', $line, FILE_APPEND);
			// #endregion
		}
	}
}
