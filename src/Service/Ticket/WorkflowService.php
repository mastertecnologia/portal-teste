<?php
namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class WorkflowService {

	/** @var Table */
	protected $tickets;

	/** @var Table|null */
	protected $statesTable;

	/** @var Table|null */
	protected $transitionsTable;

	/** @var SlaService */
	protected $slaService;

	/** @var WorkflowSlaService */
	protected $workflowSlaService;

	public function __construct(Table $ticketsTable, ?SlaService $slaService = null, ?WorkflowSlaService $workflowSlaService = null) {
		$this->tickets = $ticketsTable;
		$this->slaService = $slaService ?: new SlaService($ticketsTable);
		$this->workflowSlaService = $workflowSlaService ?: new WorkflowSlaService($ticketsTable, $this->slaService, $this);
		$this->statesTable = $this->loadTable('WorkflowStates');
		$this->transitionsTable = $this->loadTable('WorkflowTransitions');
	}

	public function hasConfiguredTransitionsForEmpresa(int $empresaId): bool {
		if ($this->transitionsTable === null) {
			return false;
		}
		$q = $this->transitionsTable->find()->where([
			'OR' => [
				['empresa_id' => $empresaId],
				['empresa_id IS' => null],
			],
		]);
		return (bool)$q->first();
	}

	public function canTransition(int $empresaId, int $fromStateId, int $toStateId): bool {
		if ($this->transitionsTable === null || $fromStateId <= 0 || $toStateId <= 0) {
			return false;
		}
		$qEmpresa = $this->transitionsTable->find()->where([
			'from_state_id' => $fromStateId,
			'to_state_id' => $toStateId,
			'empresa_id' => $empresaId,
		])->first();
		if ($qEmpresa) {
			return true;
		}
		$qGlobal = $this->transitionsTable->find()->where([
			'from_state_id' => $fromStateId,
			'to_state_id' => $toStateId,
			'empresa_id IS' => null,
		])->first();

		return (bool)$qGlobal;
	}

	/**
	 * @return array<int,array{id:int,label:string,codigo:string}>
	 */
	public function getAllowedTransitions(int $empresaId, int $fromStateId): array {
		if ($this->transitionsTable === null || $this->statesTable === null || $fromStateId <= 0) {
			return [];
		}
		$baseQuery = $this->transitionsTable->find()
			->select([
				'to_state_id',
				'to_nome' => 'WorkflowStates.nome',
				'to_codigo' => 'WorkflowStates.codigo',
				'empresa_id',
			])
			->join([
				'WorkflowStates' => [
					'table' => 'workflow_states',
					'type' => 'INNER',
					'conditions' => 'WorkflowStates.id = WorkflowTransitions.to_state_id',
				],
			])
			->where(['WorkflowTransitions.from_state_id' => $fromStateId]);

		$rowsEmpresa = (clone $baseQuery)
			->where(['WorkflowTransitions.empresa_id' => $empresaId])
			->order(['WorkflowTransitions.id' => 'ASC'])
			->toArray();
		$rowsGlobal = (clone $baseQuery)
			->where(['WorkflowTransitions.empresa_id IS' => null])
			->order(['WorkflowTransitions.id' => 'ASC'])
			->toArray();
		$rows = array_merge($rowsEmpresa, $rowsGlobal);

		$out = [];
		foreach ($rows as $r) {
			$id = (int)($r->to_state_id ?? 0);
			if ($id <= 0 || isset($out[$id])) {
				continue;
			}
			$out[$id] = [
				'id' => $id,
				'label' => (string)($r->to_nome ?? ''),
				'codigo' => (string)($r->to_codigo ?? ''),
			];
		}

		return array_values($out);
	}

	/**
	 * @return EntityInterface
	 */
	public function applyTransition(EntityInterface $ticket, int $toStateId, int $empresaId): EntityInterface {
		if ($this->statesTable === null) {
			return $ticket;
		}
		$toState = $this->statesTable->find()
			->where(['id' => $toStateId])
			->first();
		if (empty($toState)) {
			return $ticket;
		}
		$newSituacao = $this->legacySituacaoForWorkflowStateId((int)$toState->id);
		if ($newSituacao === null) {
			return $ticket;
		}
		$codigo = $this->normalizeStateCodigo((string)$toState->codigo);
		$oldSituacao = (int)($ticket->get('situacao') ?? 0);
		$ticket->set('workflow_state_id', (int)$toState->id);
		$ticket->set('situacao', $newSituacao);
		// SLA antes do timer agregado em tickets.paused_at: retomada de SLA precisa da âncora
		// (pendência) antes que applyOnSituacaoChange altere/remova paused_at.
		$this->applySla($ticket, $empresaId, $codigo);
		$this->applyTimer($ticket, $oldSituacao, $newSituacao, $codigo);

		return $ticket;
	}

	public function applyTimer(EntityInterface $ticket, int $oldSituacao, int $newSituacao, string $toStateCodigo): void {
		if ($toStateCodigo === '') {
			return;
		}
		TicketAttendimentoTimerService::applyOnSituacaoChange($this->tickets, $ticket, $oldSituacao, $newSituacao);
	}

	public function applySla(EntityInterface $ticket, int $empresaId, string $toStateCodigo): void {
		$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
		if ($stateId > 0) {
			$this->workflowSlaService->applyStateSla($ticket, $empresaId, $stateId);
			return;
		}
		if (in_array($toStateCodigo, ['aberto', 'pendente', 'emandamento'], true)) {
			$this->slaService->syncPolicyForTicket($ticket, $empresaId);
		}
	}

	/**
	 * Garante workflow_state_id inicial quando estiver nulo.
	 * Retorna o estado aplicado ou null quando não conseguiu inicializar.
	 *
	 * @return array{id:int,nome:string,codigo:string,is_final:bool}|null
	 */
	public function bootstrapStateForTicket(EntityInterface $ticket): ?array {
		$currentStateId = (int)($ticket->get('workflow_state_id') ?? 0);
		if ($currentStateId > 0) {
			return $this->getStateById($currentStateId);
		}
		$fromSituacao = $this->getStateBySituacao((int)($ticket->get('situacao') ?? 0));
		if ($fromSituacao !== null) {
			$ticket->set('workflow_state_id', (int)$fromSituacao['id']);
			return $fromSituacao;
		}
		$initial = $this->getInitialState();
		if ($initial !== null) {
			$ticket->set('workflow_state_id', (int)$initial['id']);
			return $initial;
		}

		return null;
	}

	/**
	 * @return array{id:int,nome:string,codigo:string,is_final:bool}|null
	 */
	/**
	 * Mapeamento workflow_states → tickets.situacao (mesma regra do PATCH status / stateCodigoToSituacao).
	 */
	public function legacySituacaoForWorkflowStateId(int $stateId): ?int {
		$st = $this->getStateById($stateId);
		if ($st === null) {
			return null;
		}

		return $this->stateCodigoToSituacao((string)($st['codigo'] ?? ''));
	}

	public function getStateById(int $stateId): ?array {
		if ($this->statesTable === null || $stateId <= 0) {
			return null;
		}
		$st = $this->statesTable->find()->where(['id' => $stateId])->first();
		if (!$st) {
			return null;
		}

		return [
			'id' => (int)$st->id,
			'nome' => (string)$st->nome,
			'codigo' => $this->normalizeStateCodigo((string)$st->codigo),
			'is_final' => (bool)$st->is_final,
		];
	}

	/**
	 * @return array{id:int,nome:string,codigo:string,is_final:bool}|null
	 */
	public function getStateBySituacao(int $situacao): ?array {
		$codigo = $this->situacaoToStateCodigo($situacao);
		if ($codigo === null || $this->statesTable === null) {
			return null;
		}
		$st = $this->statesTable->find()->where(['codigo' => $codigo])->first();
		if (!$st) {
			return null;
		}

		return [
			'id' => (int)$st->id,
			'nome' => (string)$st->nome,
			'codigo' => $this->normalizeStateCodigo((string)$st->codigo),
			'is_final' => (bool)$st->is_final,
		];
	}

	/**
	 * @return array{id:int,nome:string,codigo:string,is_final:bool}|null
	 */
	public function getStateByStatusLabel(string $statusLabel): ?array {
		$sit = $this->statusLabelToSituacao($statusLabel);
		if ($sit === null) {
			return null;
		}

		return $this->getStateBySituacao($sit);
	}

	/**
	 * @return array{id:int,nome:string,codigo:string,is_final:bool}|null
	 */
	public function getInitialState(): ?array {
		if ($this->statesTable === null) {
			return null;
		}
		$st = $this->statesTable->find()
			->where(['is_inicial' => true])
			->order(['id' => 'ASC'])
			->first();
		if (!$st) {
			return null;
		}

		return [
			'id' => (int)$st->id,
			'nome' => (string)$st->nome,
			'codigo' => $this->normalizeStateCodigo((string)$st->codigo),
			'is_final' => (bool)$st->is_final,
		];
	}

	protected function statusLabelToSituacao(string $raw): ?int {
		$t = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		if ($t === '') {
			return null;
		}
		$tLow = mb_strtolower($t, 'UTF-8');
		$fold = strtr($tLow, [
			'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
			'é' => 'e', 'ê' => 'e',
			'í' => 'i',
			'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
			'ú' => 'u', 'ü' => 'u',
			'ç' => 'c',
		]);
		$fold = preg_replace('/\s+/u', ' ', trim((string)$fold));
		$map = [
			'pendente' => (int)C_TicketSituacaoPendente,
			'em execucao' => (int)C_TicketSituacaoEmandamento,
			'emandamento' => (int)C_TicketSituacaoEmandamento,
			'em_andamento' => (int)C_TicketSituacaoEmandamento,
			'resolvido' => (int)C_TicketSituacaoResolvido,
			'fechado' => (int)C_TicketSituacaoFechado,
		];

		return $map[$fold] ?? null;
	}

	protected function situacaoToStateCodigo(int $situacao): ?string {
		if ($situacao === (int)C_TicketSituacaoEmandamento) {
			return 'emandamento';
		}
		if ($situacao === (int)C_TicketSituacaoPendente) {
			return 'pendente';
		}
		if ($situacao === (int)C_TicketSituacaoResolvido) {
			return 'resolvido';
		}
		if ($situacao === (int)C_TicketSituacaoFechado) {
			return 'fechado';
		}

		return null;
	}

	protected function stateCodigoToSituacao(string $codigo): ?int {
		if ($codigo === 'emandamento') {
			return (int)C_TicketSituacaoEmandamento;
		}
		if ($codigo === 'pendente' || $codigo === 'aberto') {
			return (int)C_TicketSituacaoPendente;
		}
		if ($codigo === 'resolvido') {
			return (int)C_TicketSituacaoResolvido;
		}
		if ($codigo === 'fechado') {
			return (int)C_TicketSituacaoFechado;
		}

		return null;
	}

	protected function normalizeStateCodigo(string $codigo): string {
		$k = strtolower(trim($codigo));
		if ($k === 'em_execucao' || $k === 'em-execucao' || $k === 'em execucao') {
			return 'emandamento';
		}
		return $k;
	}

	/**
	 * @return Table|null
	 */
	protected function loadTable(string $name) {
		try {
			return TableRegistry::get($name);
		} catch (\Throwable $e) {
			return null;
		}
	}
}
