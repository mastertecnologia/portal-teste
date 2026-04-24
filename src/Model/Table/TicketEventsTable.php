<?php
namespace App\Model\Table;

use App\Service\Ticket\BusinessHoursService;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class TicketEventsTable extends Table {

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('ticket_events');
		$this->setEntityClass('App\Model\Entity\TicketEvent');
		$schema = $this->getSchema();
		if (in_array('metadata', $schema->columns(), true)) {
			$schema->setColumnType('metadata', 'json');
		}
		$this->belongsTo('Tickets', ['foreignKey' => 'ticket_id']);
		$this->belongsTo('Users', ['foreignKey' => 'user_id', 'joinType' => 'LEFT']);
	}

	public function afterSave(Event $event, EntityInterface $entity, \ArrayObject $options) {
		if (!empty($options['skipBillingClassify'])) {
			return;
		}
		$type = (string)($entity->get('type') ?? '');
		if ($type !== 'worklog') {
			return;
		}
		if ($entity->get('billing_type') !== null && $entity->get('billing_type') !== '') {
			return;
		}
		$id = (int)($entity->get('id') ?? 0);
		if ($id <= 0) {
			return;
		}
		$at = $entity->get('created');
		if (!($at instanceof \DateTimeInterface)) {
			$at = Time::now();
		}
		$emp = (int)($entity->get('idempresa') ?? 0);
		$bh = new BusinessHoursService();
		$billing = $bh->classifyBilling($at, $emp > 0 ? $emp : null);
		$hourly = $this->resolveHourlyRate((int)($entity->get('ticket_id') ?? 0), $emp, $billing);
		$set = ['billing_type' => $billing];
		if ($hourly !== null) {
			$set['hourly_rate'] = $hourly;
		}
		$this->query()
			->update()
			->set($set)
			->where(['id' => $id])
			->execute();
	}

	protected function resolveHourlyRate(int $ticketId, int $idempresa, string $billing): ?float {
		if ($ticketId <= 0) {
			return null;
		}
		try {
			$t = TableRegistry::get('Tickets')->get($ticketId, ['fields' => ['id', 'idcliente']]);
			$idc = (int)($t->idcliente ?? 0);
			if ($idc <= 0) {
				return null;
			}
			$ch = TableRegistry::get('ContratosHoras');
			$c = $ch->find()
				->where(['idcliente' => $idc])
				->orderDesc('id')
				->first();
			if (!$c) {
				return null;
			}
			$cols = $ch->getSchema()->columns();
			$try = array_filter([
				'valor_hora', 'vlhora', 'preco_hora', 'valor', 'valorporhora',
			], function ($n) use ($cols) {
				return in_array($n, $cols, true);
			});
			$v = null;
			foreach ($try as $n) {
				if ($c->get($n) !== null && $c->get($n) !== '') {
					$v = $c->get($n);
					break;
				}
			}
			if ($v === null) {
				return null;
			}
			$f = is_string($v) ? (float)str_replace(',', '.', (string)$v) : (float)$v;

			return $f;
		} catch (\Throwable $e) {
			return null;
		}
	}
}
