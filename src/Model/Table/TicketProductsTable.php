<?php
namespace App\Model\Table;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TicketProductsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_products');
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Produtos', ['foreignKey' => 'produto_id', 'className' => 'Produtos']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
	}

	public function beforeSave(Event $event, $entity, \ArrayObject $options) {
		$options['__new_ticket_product'] = (bool)$entity->isNew();
	}

	public function afterSave(Event $event, $entity, \ArrayObject $options) {
		if (empty($options['__new_ticket_product'])) {
			return;
		}
		unset($options['__new_ticket_product']);
		if (!empty($options['skipStockTimeline'])) {
			return;
		}
		if (!$this->tableExists('ticket_events')) {
			return;
		}
		$tid = (int)($entity->ticket_id ?? 0);
		$pid = (int)($entity->produto_id ?? 0);
		$q = (float)($entity->quantidade ?? 0);
		if ($tid <= 0 || $pid <= 0 || $q <= 0) {
			return;
		}
		$eid = (int)($entity->idempresa ?? 0);
		$uid = (int)($entity->user_id ?? 0);
		$meta = [
			'produto_id' => $pid,
			'quantidade' => $q,
			'custo_unitario' => $entity->custo_unitario,
			'preco_unitario' => $entity->preco_unitario,
		];
		$P = TableRegistry::get('Produtos');
		$cols = $P->getSchema()->columns();
		$hasEstoque = in_array('estoque_atual', $cols, true);
		$stockDeductedInRequest = !empty($options['_stockDeducted']);
		$conn = ConnectionManager::get('default');
		try {
			if ($hasEstoque && !$stockDeductedInRequest) {
				$st = $conn->execute(
					'UPDATE produtos SET estoque_atual = COALESCE(estoque_atual, 0) - :q WHERE id = :id AND idempresa = :eid AND COALESCE(estoque_atual, 0) >= :q2',
					['q' => $q, 'q2' => $q, 'id' => $pid, 'eid' => $eid]
				);
				$n = method_exists($st, 'rowCount') ? (int)$st->rowCount() : 0;
				if ($n < 1) {
					$this->delete($entity, ['checkRules' => false, 'atomic' => false]);
					throw new \RuntimeException('Estoque insuficiente ou produto inexistente');
				}
			}
		} catch (\RuntimeException $e) {
			throw $e;
		} catch (\Throwable $e) {
		}
		$te = TableRegistry::get('TicketEvents');
		try {
			$te->save($te->newEntity([
				'idempresa' => $eid,
				'ticket_id' => $tid,
				'user_id' => $uid > 0 ? $uid : null,
				'type' => 'product_usage',
				'description' => 'Baixa de estoque (produto ' . $pid . ', qtd ' . $q . ')',
				'metadata' => $meta,
				'created' => Time::now(),
			], ['validate' => false]), ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
		} catch (\Throwable $e) {
		}
	}

	protected function tableExists($name) {
		try {
			return in_array($name, ConnectionManager::get('default')->getSchemaCollection()->listTables(), true);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
