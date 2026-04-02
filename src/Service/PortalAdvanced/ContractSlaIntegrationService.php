<?php
namespace App\Service\PortalAdvanced;

use App\Service\Ticket\SlaRecalculationService;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

/**
 * Integra SLA definido em contracts.sla_hours (horas) com os campos enterprise do ticket.
 * Não substitui App\Service\Ticket\SlaService (políticas SlaPolicies); pode complementar quando
 * o ticket estiver ligado a um contrato avançado e os prazos ainda estiverem vazios.
 */
class ContractSlaIntegrationService {

	/**
	 * @param EntityInterface $ticket Entidade Ticket (já com created preenchido)
	 * @param EntityInterface $contract Entidade contracts
	 * @param bool $onlyIfEmpty Só preenche data_limite_* se estiverem vazios
	 * @return string[] Nomes de colunas alteradas no ticket (para array fields no save)
	 */
	public static function applyContractHoursToTicket(
		EntityInterface $ticket,
		EntityInterface $contract,
		bool $onlyIfEmpty = true
	): array {
		$Tickets = TableRegistry::get('Tickets');
		$cols = $Tickets->getSchema()->columns();
		$hours = (int)($contract->get('sla_hours') ?? 0);
		if ($hours <= 0) {
			return [];
		}

		$created = $ticket->get('created');
		if ($created === null || $created === '') {
			return [];
		}
		try {
			$base = $created instanceof Time ? clone $created : new Time($created);
		} catch (\Throwable $e) {
			return [];
		}

		$deadline = (clone $base)->addHours($hours);
		$changed = [];

		if (in_array('data_limite_resposta', $cols, true)) {
			if (!$onlyIfEmpty || $ticket->get('data_limite_resposta') === null || $ticket->get('data_limite_resposta') === '') {
				$ticket->set('data_limite_resposta', $deadline);
				$changed[] = 'data_limite_resposta';
			}
		}
		if (in_array('data_limite_resolucao', $cols, true)) {
			if (!$onlyIfEmpty || $ticket->get('data_limite_resolucao') === null || $ticket->get('data_limite_resolucao') === '') {
				$ticket->set('data_limite_resolucao', $deadline);
				$changed[] = 'data_limite_resolucao';
			}
		}
		if (in_array('sla_status', $cols, true) && empty($ticket->get('sla_status'))) {
			$ticket->set('sla_status', 'dentro_sla');
			$changed[] = 'sla_status';
		}

		return array_values(array_unique($changed));
	}

	/**
	 * Regista primeira resposta (data_primeira_resposta) se a coluna existir e estiver vazia.
	 *
	 * @return string[]
	 */
	public static function recordFirstResponseIfEmpty(EntityInterface $ticket): array {
		$Tickets = TableRegistry::get('Tickets');
		$cols = $Tickets->getSchema()->columns();
		if (!in_array('data_primeira_resposta', $cols, true)) {
			return [];
		}
		if ($ticket->get('data_primeira_resposta') !== null && $ticket->get('data_primeira_resposta') !== '') {
			return [];
		}
		$ticket->set('data_primeira_resposta', Time::now());

		return ['data_primeira_resposta'];
	}

	/**
	 * Após fechar/resolver ticket, recalcula sla_percentual_consumido e sla_status (violado / dentro_sla / em_risco).
	 */
	public static function refreshSlaAfterStatusChange(EntityInterface $ticket): bool {
		try {
			$Tickets = TableRegistry::get('Tickets');
			$svc = new SlaRecalculationService($Tickets);

			return $svc->recalculateOne($ticket);
		} catch (\Throwable $e) {
			\Cake\Log\Log::warning('ContractSlaIntegrationService::refreshSlaAfterStatusChange ' . $e->getMessage());

			return false;
		}
	}
}
