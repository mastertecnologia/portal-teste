<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Resolve a política {@see \App\Model\Table\WorkflowSlaPoliciesTable} mais específica para um ticket,
 * com ordem de precedência contratual/cliente/empresa/global e fallback à regra legada (empresa → global por estado).
 */
class SlaPolicyResolverService {

	/** @var Table */
	protected $policies;

	/** @var Table|null */
	protected $tickets;

	public function __construct(?Table $workflowSlaPoliciesTable = null, ?Table $ticketsTable = null) {
		$this->policies = $workflowSlaPoliciesTable
			?? TableRegistry::get('WorkflowSlaPolicies');
		$this->tickets = $ticketsTable;
	}

	/**
	 * @param EntityInterface $ticket Entidade com idempresa, workflow_state_id e dimensões opcionais.
	 * @return EntityInterface|null Entidade WorkflowSlaPolicy ou null se não houver estado/tabela.
	 */
	public function resolveForTicket(EntityInterface $ticket): ?EntityInterface {
		$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
		$empresaId = (int)($ticket->get('idempresa') ?? 0);
		if ($stateId <= 0) {
			return null;
		}

		$table = $this->policies;
		try {
			if (!in_array('workflow_state_id', $table->getSchema()->columns(), true)) {
				return $this->legacyResolve($empresaId, $stateId);
			}
		} catch (\Throwable $e) {
			return $this->legacyResolve($empresaId, $stateId);
		}

		$ctx = $this->buildContext($ticket);
		$candidates = $this->loadCandidates($stateId, $empresaId);
		if ($candidates === []) {
			return $this->legacyResolve($empresaId, $stateId);
		}

		for ($tier = 1; $tier <= 7; $tier++) {
			$matched = [];
			foreach ($candidates as $p) {
				if (!$this->policyMatchesTicket($p, $ctx)) {
					continue;
				}
				if (!$this->policyMatchesTierShape($p, $tier)) {
					continue;
				}
				$matched[] = $p;
			}
			if ($matched !== []) {
				return $this->pickBest($matched);
			}
		}

		return $this->legacyResolve($empresaId, $stateId);
	}

	/**
	 * Mesma lógica que {@see WorkflowSlaService::findPolicy} (empresa específica, depois global por estado).
	 */
	public function legacyResolve(int $empresaId, int $stateId): ?EntityInterface {
		$empresaId = (int)$empresaId;
		$stateId = (int)$stateId;
		if ($stateId <= 0) {
			return null;
		}
		try {
			$table = $this->policies;
		} catch (\Throwable $e) {
			return null;
		}
		if ($empresaId > 0) {
			$q1 = $table->find()
				->where([
					'workflow_state_id' => $stateId,
					'empresa_id' => $empresaId,
				]);
			$specific = $this->onlyActivePoliciesQuery($q1)
				->order([$table->aliasField('id') => 'ASC'])
				->first();
			if ($specific !== null) {
				return $specific;
			}
		}

		$q2 = $table->find()
			->where([
				'workflow_state_id' => $stateId,
				'empresa_id IS' => null,
			]);

		return $this->onlyActivePoliciesQuery($q2)
			->order([$table->aliasField('id') => 'ASC'])
			->first();
	}

	/**
	 * @param \Cake\ORM\Query $query
	 * @return \Cake\ORM\Query
	 */
	protected function onlyActivePoliciesQuery($query) {
		try {
			if (in_array('ativo', $this->policies->getSchema()->columns(), true)) {
				$query->andWhere([$this->policies->aliasField('ativo') => true]);
			}
		} catch (\Throwable $e) {
		}

		return $query;
	}

	/**
	 * @return array<string, int|null>
	 */
	protected function buildContext(EntityInterface $ticket): array {
		$cols = [];
		if ($this->tickets !== null) {
			try {
				$cols = $this->tickets->getSchema()->columns();
			} catch (\Throwable $e) {
				$cols = [];
			}
		}

		$getInt = function (array $fieldNames) use ($ticket, $cols) {
			foreach ($fieldNames as $n) {
				if ($cols === [] || in_array($n, $cols, true)) {
					$v = $ticket->get($n);
					if ($v !== null && $v !== '') {
						return (int)$v;
					}
				}
			}

			return null;
		};

		return [
			'empresa_id' => (int)($ticket->get('idempresa') ?? 0),
			'workflow_state_id' => (int)($ticket->get('workflow_state_id') ?? 0),
			'idcliente' => $getInt(['idcliente']),
			'contract_id' => $getInt(['contract_id']),
			'contract_service_id' => $getInt(['contract_service_id']),
			'problema_id' => $getInt(['problema_id', 'idproblema']),
			'queue_id' => $getInt(['queue_id']),
			'support_level_id' => $getInt(['support_level_id']),
		];
	}

	/**
	 * @return array<int, EntityInterface>
	 */
	protected function loadCandidates(int $stateId, int $empresaId): array {
		$table = $this->policies;
		$q = $table->find()
			->where(['workflow_state_id' => $stateId]);
		if ($empresaId > 0) {
			$q->where([
				'OR' => [
					[$table->aliasField('empresa_id') => $empresaId],
					[$table->aliasField('empresa_id') . ' IS' => null],
				],
			]);
		} else {
			$q->where([$table->aliasField('empresa_id') . ' IS' => null]);
		}
		$q = $this->onlyActivePoliciesQuery($q);

		$rows = $q->order([$table->aliasField('id') => 'ASC'])->toArray();

		return array_values($rows);
	}

	/**
	 * Cada campo não nulo na política deve coincidir com o contexto (wildcard = NULL na política).
	 *
	 * @param EntityInterface $policy WorkflowSlaPolicy
	 * @param array<string, int|null> $ctx
	 */
	protected function policyMatchesTicket(EntityInterface $policy, array $ctx): bool {
		$pairs = [
			'idcliente',
			'contract_id',
			'contract_service_id',
			'problema_id',
			'queue_id',
			'support_level_id',
		];
		foreach ($pairs as $f) {
			$pv = $policy->get($f);
			if ($pv === null || $pv === '') {
				continue;
			}
			$cv = $ctx[$f] ?? null;
			if ($cv === null || (int)$pv !== (int)$cv) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Forma esperada para o número do tier (1 = mais específico conforme especificação).
	 *
	 * @param EntityInterface $policy
	 */
	protected function policyMatchesTierShape(EntityInterface $policy, int $tier): bool {
		$nn = function (string $f) use ($policy): bool {
			$v = $policy->get($f);

			return $v !== null && $v !== '';
		};

		switch ($tier) {
			case 1:
				return $nn('contract_id') && $nn('contract_service_id') && $nn('problema_id')
					&& ($nn('queue_id') || $nn('support_level_id'));
			case 2:
				return $nn('contract_id') && $nn('contract_service_id') && $nn('problema_id')
					&& !$nn('queue_id') && !$nn('support_level_id');
			case 3:
				return $nn('contract_id') && $nn('contract_service_id')
					&& !$nn('problema_id') && !$nn('queue_id') && !$nn('support_level_id');
			case 4:
				return $nn('contract_id')
					&& !$nn('contract_service_id') && !$nn('problema_id') && !$nn('queue_id') && !$nn('support_level_id');
			case 5:
				return $nn('idcliente')
					&& !$nn('contract_id') && !$nn('contract_service_id') && !$nn('problema_id')
					&& !$nn('queue_id') && !$nn('support_level_id');
			case 6:
				return $nn('empresa_id')
					&& !$nn('idcliente') && !$nn('contract_id') && !$nn('contract_service_id')
					&& !$nn('problema_id') && !$nn('queue_id') && !$nn('support_level_id');
			case 7:
				return !$nn('empresa_id')
					&& !$nn('idcliente') && !$nn('contract_id') && !$nn('contract_service_id')
					&& !$nn('problema_id') && !$nn('queue_id') && !$nn('support_level_id');
			default:
				return false;
		}
	}

	/**
	 * Desempate no mesmo tier: linha com `empresa_id` definido (não global) antes da global;
	 * depois menor `scope_priority` (ausente = 1000); empate por menor `id`.
	 *
	 * @param array<int, EntityInterface> $matched
	 */
	protected function pickBest(array $matched): EntityInterface {
		usort($matched, function (EntityInterface $a, EntityInterface $b): int {
			$ea = $a->get('empresa_id');
			$eb = $b->get('empresa_id');
			$aEmp = ($ea !== null && $ea !== '');
			$bEmp = ($eb !== null && $eb !== '');
			if ($aEmp !== $bEmp) {
				return $aEmp ? -1 : 1;
			}
			$pa = $a->get('scope_priority');
			$pb = $b->get('scope_priority');
			$sa = ($pa === null || $pa === '') ? 1000 : (int)$pa;
			$sb = ($pb === null || $pb === '') ? 1000 : (int)$pb;
			if ($sa !== $sb) {
				return $sa <=> $sb;
			}

			return ((int)($a->get('id') ?? 0)) <=> ((int)($b->get('id') ?? 0));
		});

		return $matched[0];
	}
}
