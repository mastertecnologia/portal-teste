<?php
namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\Table;

/**
 * Aplica políticas de SLA ao ticket (criação e recálculo futuro).
 */
class SlaService {

	/** @var Table */
	protected $tickets;

	public function __construct(Table $ticketsTable) {
		$this->tickets = $ticketsTable;
	}

	/**
	 * Preenche prioridade (se vazia), impacto/urgência a partir da severidade, política e prazos.
	 *
	 * @param EntityInterface $ticket Ticket já persistido (possui id).
	 * @param int $idempresa Empresa do contexto.
	 * @param string|null $severidade Valor do campo severidade (legado).
	 * @return array Lista de nomes de colunas alteradas (para save fields).
	 */
	public function bootstrapNewTicket(EntityInterface $ticket, int $idempresa, ?string $severidade): array {
		$cols = $this->tickets->getSchema()->columns();
		$changed = [];

		if (!in_array('prioridade', $cols, true)) {
			return $changed;
		}

		$policies = $this->loadPoliciesTable();
		if ($policies === null) {
			return $changed;
		}

		if (empty($ticket->get('impacto')) || empty($ticket->get('urgencia'))) {
			list($imp, $urg) = TicketClassificationService::impactoUrgenciaFromSeveridade($severidade);
			if (in_array('impacto', $cols, true) && empty($ticket->get('impacto'))) {
				$ticket->set('impacto', $imp);
				$changed[] = 'impacto';
			}
			if (in_array('urgencia', $cols, true) && empty($ticket->get('urgencia'))) {
				$ticket->set('urgencia', $urg);
				$changed[] = 'urgencia';
			}
		}

		$impacto = $ticket->get('impacto');
		$urgencia = $ticket->get('urgencia');
		if (empty($ticket->get('prioridade'))) {
			$ticket->set('prioridade', TicketClassificationService::prioridadeFromImpactoUrgencia(
				is_string($impacto) ? $impacto : null,
				is_string($urgencia) ? $urgencia : null
			));
			$changed[] = 'prioridade';
		}

		if (in_array('tipo_ticket', $cols, true) && ($ticket->get('tipo_ticket') === null || $ticket->get('tipo_ticket') === '')) {
			$ticket->set('tipo_ticket', 'incidente');
			$changed[] = 'tipo_ticket';
		}

		$prioridade = (string)$ticket->get('prioridade');
		$tipoTicket = in_array('tipo_ticket', $cols, true) ? $ticket->get('tipo_ticket') : null;
		$policy = $this->findPolicy($policies, $idempresa, $prioridade, is_string($tipoTicket) ? $tipoTicket : null);
		if ($policy === null) {
			return array_values(array_unique($changed));
		}

		$this->_applySlaPolicyToTicket($ticket, $idempresa, $policy, $changed, true);

		return array_values(array_unique($changed));
	}

	/**
	 * Após alteração manual da prioridade: reaplica política e prazos (sla_policies por empresa),
	 * se existir linha para o valor atual (P1–P4 ou texto conforme cadastro).
	 * Não zera sla_percentual_consumido (diferente do bootstrap de ticket novo).
	 *
	 * @return array<string> nomes de colunas alteradas na entidade (para save fields)
	 */
	public function syncPolicyForTicket(EntityInterface $ticket, int $idempresa): array {
		$cols = $this->tickets->getSchema()->columns();
		$changed = [];
		if (!in_array('prioridade', $cols, true)) {
			return $changed;
		}
		$policies = $this->loadPoliciesTable();
		if ($policies === null) {
			return $changed;
		}
		$prioridade = trim((string)$ticket->get('prioridade'));
		if ($prioridade === '') {
			return $changed;
		}
		$tipoTicket = in_array('tipo_ticket', $cols, true) ? $ticket->get('tipo_ticket') : null;
		$policy = $this->findPolicy($policies, $idempresa, $prioridade, is_string($tipoTicket) ? $tipoTicket : null);
		if ($policy === null) {
			return $changed;
		}
		$this->_applySlaPolicyToTicket($ticket, $idempresa, $policy, $changed, false);

		return array_values(array_unique($changed));
	}

	/**
	 * @param object $policy registo SlaPolicies
	 * @param array<string> $changed
	 * @param bool $bootstrapNew quando true, zera percentual e sla_status como na abertura
	 */
	protected function _applySlaPolicyToTicket(EntityInterface $ticket, int $idempresa, $policy, array &$changed, bool $bootstrapNew): void {
		$cols = $this->tickets->getSchema()->columns();
		$now = Time::now();
		$bh = new BusinessHoursService();

		if (in_array('sla_policy_id', $cols, true)) {
			$ticket->set('sla_policy_id', (int)$policy->id);
			$changed[] = 'sla_policy_id';
		}
		if (in_array('sla_resposta_minutos', $cols, true)) {
			$ticket->set('sla_resposta_minutos', (int)$policy->resposta_minutos);
			$changed[] = 'sla_resposta_minutos';
		}
		if (in_array('sla_resolucao_minutos', $cols, true)) {
			$ticket->set('sla_resolucao_minutos', (int)$policy->resolucao_minutos);
			$changed[] = 'sla_resolucao_minutos';
		}
		if (in_array('data_limite_resposta', $cols, true)) {
			$ticket->set('data_limite_resposta', $bh->addBusinessMinutes($now, (int)$policy->resposta_minutos, $idempresa));
			$changed[] = 'data_limite_resposta';
		}
		if (in_array('data_limite_resolucao', $cols, true)) {
			$ticket->set('data_limite_resolucao', $bh->addBusinessMinutes($now, (int)$policy->resolucao_minutos, $idempresa));
			$changed[] = 'data_limite_resolucao';
		}
		if ($bootstrapNew) {
			if (in_array('sla_percentual_consumido', $cols, true)) {
				$ticket->set('sla_percentual_consumido', 0);
				$changed[] = 'sla_percentual_consumido';
			}
			if (in_array('sla_status', $cols, true)) {
				$ticket->set('sla_status', 'dentro_sla');
				$changed[] = 'sla_status';
			}
		}
	}

	/**
	 * @param \Cake\ORM\Table $policies SlaPolicies
	 */
	protected function findPolicy($policies, int $idempresa, string $prioridade, ?string $tipoTicket) {
		$q = $policies->find()
			->where([
				'idempresa' => $idempresa,
				'prioridade' => $prioridade,
				'ativo' => true,
				'tipo_ticket IS' => null,
			])
			->order(['id' => 'ASC'])
			->first();
		if ($q !== null) {
			return $q;
		}
		if ($tipoTicket !== null && $tipoTicket !== '') {
			return $policies->find()
				->where([
					'idempresa' => $idempresa,
					'prioridade' => $prioridade,
					'ativo' => true,
					'tipo_ticket' => $tipoTicket,
				])
				->order(['id' => 'ASC'])
				->first();
		}

		return null;
	}

	/**
	 * @return \Cake\ORM\Table|null
	 */
	protected function loadPoliciesTable() {
		try {
			return \Cake\ORM\TableRegistry::get('SlaPolicies');
		} catch (\Throwable $e) {
			return null;
		}
	}
}
