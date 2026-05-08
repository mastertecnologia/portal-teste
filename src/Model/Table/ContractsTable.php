<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\FrozenDate;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Contratos do módulo avançado (tabela contracts).
 * Legado comercial de itens do cliente continua em clicontratos / contratos_horas.
 */
class ContractsTable extends Table {

	/**
	 * Status permitidos (PT + active; alinhado a MODULO_CONTRATOS_COMPLETO e uso no código).
	 *
	 * @return string[]
	 */
	public static function allowedStatusValues() {
		return [
			'rascunho',
			'revisao',
			'aguardando_assinatura',
			'awaiting_signature',
			'ativo',
			'active',
			'a_vencer',
			'em_renovacao',
			'suspenso',
			'encerrado',
			'cancelado',
			'recusado',
			'assinatura_expirada',
		];
	}

	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('contracts');
		$this->setDisplayField('name');
		$this->setEntityClass('App\Model\Entity\Contract');
		$this->addBehavior('Timestamp');

		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Empresas', ['foreignKey' => 'idempresa', 'joinType' => 'LEFT']);
		$this->belongsTo('ContractTemplates', ['foreignKey' => 'template_id', 'joinType' => 'LEFT']);
		$this->belongsTo('ParentContracts', [
			'className' => 'Contracts',
			'foreignKey' => 'contrato_pai_id',
			'joinType' => 'LEFT',
		]);

		$this->hasMany('ContractServices', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractDocuments', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractConsumptions', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('Invoices', ['foreignKey' => 'contract_id', 'dependent' => false]);
		$this->hasMany('AttendanceHistories', ['foreignKey' => 'contract_id', 'dependent' => false]);
		$this->hasMany('ChildContracts', [
			'className' => 'Contracts',
			'foreignKey' => 'contrato_pai_id',
			'dependent' => false,
		]);
		$this->hasMany('ContractSignatories', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractAutentiqueLogs', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractRenewals', ['foreignKey' => 'contract_id', 'dependent' => true]);
		$this->hasMany('ContractNotifications', ['foreignKey' => 'contract_id', 'dependent' => true]);
	}

	/**
	 * Converte valores monetários do formulário (ex.: 1.234,56 ou vazio após máscara) para número.
	 * Evita NULL em colunas NOT NULL (monthly_value, valor_total).
	 *
	 * @param \Cake\Event\Event $event
	 * @param \ArrayObject $data
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		foreach (['monthly_value', 'valor_total', 'overage_hour_value'] as $field) {
			if (!isset($data[$field])) {
				continue;
			}
			$raw = $data[$field];
			if ($raw === null || $raw === '') {
				if (in_array($field, ['monthly_value', 'valor_total', 'overage_hour_value'], true)) {
					$data[$field] = 0;
				}

				continue;
			}
			if (is_numeric($raw) && !is_string($raw)) {
				continue;
			}
			$parsed = static::_parseDecimalFromForm($raw);
			if ($parsed === null) {
				$data[$field] = in_array($field, ['monthly_value', 'valor_total', 'overage_hour_value'], true) ? 0 : null;
			} else {
				$data[$field] = $parsed;
			}
		}
	}

	/**
	 * Garante consistência financeira: mensalidade vem da soma dos serviços
	 * e valor_total é mensalidade x meses de vigência.
	 *
	 * @param \Cake\Event\Event $event
	 * @param \Cake\Datasource\EntityInterface $entity
	 * @param \ArrayObject $options
	 * @return void
	 */
	public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options) {
		$monthly = $this->sumMonthlyValueFromServices((int)$entity->get('id'));
		$entity->set('monthly_value', $monthly);

		$startDate = $entity->get('start_date');
		$endDate = $entity->get('end_date');
		$months = $this->calculateVigencyMonths($startDate, $endDate);
		$entity->set('valor_total', $months > 0 ? round($monthly * $months, 2) : 0);
	}

	/**
	 * @param mixed $raw
	 * @return float|null
	 */
	protected static function _parseDecimalFromForm($raw) {
		if ($raw === null || $raw === '') {
			return null;
		}
		if (is_int($raw) || is_float($raw)) {
			return (float)$raw;
		}
		if (!is_string($raw)) {
			return null;
		}
		$s = trim(preg_replace('/[^\d,.-]/', '', $raw));
		if ($s === '' || $s === '-') {
			return null;
		}
		if (strpos($s, ',') !== false) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
		}

		return is_numeric($s) ? (float)$s : null;
	}

	/**
	 * Contratos em vigor operacional (ativo EN/PT).
	 *
	 * @param \Cake\ORM\Query $query
	 * @param array $options
	 * @return \Cake\ORM\Query
	 */
	public function findActive($query, array $options = []) {
		return $query->where(['Contracts.status IN' => ['active', 'ativo']]);
	}

	/**
	 * Contratos com ciclo de vida ainda relevante para alertas e UI.
	 *
	 * @param \Cake\ORM\Query $query
	 * @param array $options
	 * @return \Cake\ORM\Query
	 */
	public function findOpenLifecycle($query, array $options = []) {
		return $query->where([
			'Contracts.status IN' => [
				'active', 'ativo', 'a_vencer', 'aguardando_assinatura', 'awaiting_signature', 'rascunho', 'revisao', 'em_renovacao',
			],
		]);
	}

	/**
	 * Recalcula mensalidade e valor total baseado em serviços e vigência.
	 *
	 * @param int $contractId
	 * @return bool
	 */
	public function recalculateFinancialsFromServices($contractId) {
		$contractId = (int)$contractId;
		if ($contractId <= 0) {
			return false;
		}

		$contract = $this->find()
			->select(['id', 'start_date', 'end_date', 'monthly_value', 'valor_total'])
			->where(['id' => $contractId])
			->first();
		if (!$contract) {
			return false;
		}

		$monthly = $this->sumMonthlyValueFromServices($contractId);
		$months = $this->calculateVigencyMonths($contract->get('start_date'), $contract->get('end_date'));
		$total = $months > 0 ? round($monthly * $months, 2) : 0;

		$this->patchEntity($contract, [
			'monthly_value' => $monthly,
			'valor_total' => $total,
		], [
			'validate' => false,
			'fields' => ['monthly_value', 'valor_total'],
		]);

		return (bool)$this->save($contract, ['checkRules' => false, 'validate' => false]);
	}

	/**
	 * @param int $contractId
	 * @return float
	 */
	public function sumMonthlyValueFromServices($contractId) {
		$contractId = (int)$contractId;
		if ($contractId <= 0) {
			return 0.0;
		}

		$services = $this->getAssociation('ContractServices')->getTarget();
		$q = $services->find();
		$row = $q
			->select(['sum_total' => $q->func()->sum('valor_total')])
			->where(['contract_id' => $contractId])
			->first();

		return round((float)($row->sum_total ?? 0), 2);
	}

	/**
	 * Meses para cobrança: conta meses completos e arredonda para cima quando há dias remanescentes.
	 * Ex.: 07/05/2026 a 07/05/2027 => 12; 07/05/2026 a 22/06/2027 => 14.
	 * Regra comercial: vigência em meses cheios; qualquer dia excedente arredonda para mais.
	 *
	 * @param \DateTimeInterface|string|null $startDate
	 * @param \DateTimeInterface|string|null $endDate
	 * @return int
	 */
	public function calculateVigencyMonths($startDate, $endDate) {
		$start = $this->normalizeDate($startDate);
		$end = $this->normalizeDate($endDate);
		if ($start === null || $end === null || $end < $start) {
			return 0;
		}

		$diff = $start->diff($end);
		$months = ($diff->y * 12) + $diff->m;
		if ($diff->d > 0) {
			$months++;
		}

		return max(1, $months);
	}

	/**
	 * @param \DateTimeInterface|string|null $date
	 * @return \Cake\I18n\FrozenDate|null
	 */
	protected function normalizeDate($date) {
		if ($date instanceof \DateTimeInterface) {
			return FrozenDate::parseDate($date->format('Y-m-d'), 'yyyy-MM-dd');
		}
		if (!is_string($date) || trim($date) === '') {
			return null;
		}

		return FrozenDate::parseDate(trim($date), 'yyyy-MM-dd');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->integer('idcliente')
			->requirePresence('idcliente', 'create')
			->notEmpty('idcliente', __('Selecione um cliente.'));

		$validator
			->scalar('code')
			->maxLength('code', 50)
			->requirePresence('code', 'create')
			->notEmpty('code');

		$validator
			->scalar('name')
			->maxLength('name', 255)
			->requirePresence('name', 'create')
			->notEmpty('name');

		$validator
			->scalar('type')
			->maxLength('type', 100)
			->requirePresence('type', 'create')
			->notEmpty('type');

		$validator
			->scalar('status')
			->maxLength('status', 50)
			->requirePresence('status', 'create')
			->notEmpty('status')
			->inList('status', static::allowedStatusValues(), __('Status de contrato inválido.'));

		$validator
			->date('start_date')
			->requirePresence('start_date', 'create')
			->notEmpty('start_date');

		$validator
			->date('end_date')
			->requirePresence('end_date', 'create')
			->notEmpty('end_date');
		$validator->add('end_date', 'afterStartDate', [
			'rule' => function ($value, $context) {
				$start = $context['data']['start_date'] ?? null;
				if (empty($start) || empty($value)) {
					return true;
				}
				$startDate = $this->normalizeDate($start);
				$endDate = $this->normalizeDate($value);
				if ($startDate === null || $endDate === null) {
					return true;
				}

				return $endDate >= $startDate;
			},
			'message' => __('A data final da vigência deve ser posterior à data inicial.'),
		]);

		$validator->scalar('nivel_sla')->maxLength('nivel_sla', 30)->allowEmpty('nivel_sla');
		$validator->scalar('autentique_doc_id')->maxLength('autentique_doc_id', 255)->allowEmpty('autentique_doc_id');
		$validator->scalar('autentique_status')->maxLength('autentique_status', 30)->allowEmpty('autentique_status');
		$validator->scalar('pdf_path')->maxLength('pdf_path', 500)->allowEmpty('pdf_path');
		$validator->scalar('signed_pdf_path')->maxLength('signed_pdf_path', 500)->allowEmpty('signed_pdf_path');
		$validator->scalar('signature_provider')->maxLength('signature_provider', 50)->allowEmpty('signature_provider');
		$validator->scalar('signed_file_url')->allowEmpty('signed_file_url');
		$validator->dateTime('sent_for_signature_at')->allowEmpty('sent_for_signature_at');
		$validator->dateTime('fully_signed_at')->allowEmpty('fully_signed_at');

		return $validator;
	}
}
