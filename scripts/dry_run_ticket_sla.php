<?php
/**
 * Dry-run de SLA por ticket (somente leitura, sem UPDATE/INSERT/DELETE).
 *
 * Uso:
 *   php scripts/dry_run_ticket_sla.php 1174
 */

$root = dirname(__DIR__);
chdir($root);

require $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require $root . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Service\Ticket\BusinessHoursService;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;

function normalize_code($value): string {
	$s = (string)$value;
	$s = preg_replace('/[\s\-]+/', '_', $s);
	$s = strtolower(trim($s));
	return $s;
}

function bool_or_null($value) {
	if ($value === null) {
		return null;
	}
	return (bool)$value;
}

$ticketId = isset($argv[1]) ? (int)$argv[1] : 1174;
if ($ticketId <= 0) {
	fwrite(STDERR, "Uso: php scripts/dry_run_ticket_sla.php <ticket_id>\n");
	exit(2);
}

$output = [
	'ticket' => null,
	'policy' => [
		'policy_id' => null,
		'origem' => 'nao_encontrada',
		'empresa_id' => null,
		'workflow_state_id' => null,
		'resposta_minutos' => null,
		'resolucao_minutos' => null,
		'auto_escalar' => null,
		'is_final' => null,
		'escalate_to_state_id' => null,
		'escalate_to_nome' => null,
		'escalate_to_codigo' => null,
		'escalate_after_minutos' => null,
	],
	'validation' => [
		'policy_ok' => false,
		'errors' => [],
		'warnings' => [],
	],
	'simulation' => [
		'strategy' => 'now_plus_policy_business_minutes',
		'new_sla_resolucao_minutos' => null,
		'new_data_limite_resolucao' => null,
		'used_business_hours_service' => false,
	],
	'proposed_update' => [
		'will_update' => false,
		'ticket_id' => $ticketId,
		'fields' => [],
	],
];

try {
	$tickets = TableRegistry::getTableLocator()->get('Tickets');
	$states = TableRegistry::getTableLocator()->get('WorkflowStates');
	$policies = TableRegistry::getTableLocator()->get('WorkflowSlaPolicies');

	$ticket = $tickets->find()->where(['Tickets.id' => $ticketId])->first();
	if ($ticket === null) {
		$output['validation']['errors'][] = "ticket_nao_encontrado";
		echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
		exit(1);
	}

	$state = null;
	$stateId = (int)($ticket->get('workflow_state_id') ?? 0);
	if ($stateId > 0) {
		$state = $states->find()->where(['id' => $stateId])->first();
	}

	$ticketRow = [
		'id' => (int)$ticket->get('id'),
		'empresa_id' => (int)($ticket->get('idempresa') ?? 0),
		'workflow_state_id' => $stateId > 0 ? $stateId : null,
		'workflow_state_nome' => $state ? (string)$state->get('nome') : null,
		'workflow_state_codigo' => $state ? (string)$state->get('codigo') : null,
		'situacao' => $ticket->get('situacao'),
		'idtecnico_responsavel' => $ticket->get('idtecnico_responsavel'),
		'queue_id' => $ticket->get('queue_id'),
		'fila_suporte' => $ticket->get('fila_suporte'),
		'nivel_atendimento' => $ticket->get('nivel_atendimento'),
		'sla_resolucao_minutos_atual' => $ticket->get('sla_resolucao_minutos'),
		'sla_resposta_minutos_atual' => $ticket->get('sla_resposta_minutos'),
		'data_limite_resolucao_atual' => $ticket->get('data_limite_resolucao') ? (new Time($ticket->get('data_limite_resolucao')))->format('c') : null,
		'data_limite_resposta_atual' => $ticket->get('data_limite_resposta') ? (new Time($ticket->get('data_limite_resposta')))->format('c') : null,
		'sla_status' => $ticket->get('sla_status'),
		'sla_percentual_consumido' => $ticket->get('sla_percentual_consumido'),
		'sla_escalated_at' => $ticket->get('sla_escalated_at') ? (new Time($ticket->get('sla_escalated_at')))->format('c') : null,
	];
	$output['ticket'] = $ticketRow;

	$empresaId = (int)($ticket->get('idempresa') ?? 0);
	if ($empresaId <= 0) {
		$output['validation']['errors'][] = 'empresa_invalida_no_ticket';
	}
	if ($stateId <= 0) {
		$output['validation']['errors'][] = 'workflow_state_id_invalido_no_ticket';
	}

	$policy = null;
	$policyOrigin = 'nao_encontrada';
	if ($empresaId > 0 && $stateId > 0) {
		$policy = $policies->find()
			->where([
				'workflow_state_id' => $stateId,
				'empresa_id' => $empresaId,
			])
			->order(['id' => 'ASC'])
			->first();
		if ($policy !== null) {
			$policyOrigin = 'empresa';
		} else {
			$policy = $policies->find()
				->where([
					'workflow_state_id' => $stateId,
					'empresa_id IS' => null,
				])
				->order(['id' => 'ASC'])
				->first();
			if ($policy !== null) {
				$policyOrigin = 'global';
			}
		}
	}

	if ($policy === null) {
		$output['validation']['errors'][] = 'policy_nao_encontrada';
	} else {
		$escState = null;
		$escStateId = (int)($policy->get('escalate_to_state_id') ?? 0);
		if ($escStateId > 0) {
			$escState = $states->find()->where(['id' => $escStateId])->first();
		}
		$output['policy'] = [
			'policy_id' => (int)$policy->get('id'),
			'origem' => $policyOrigin,
			'empresa_id' => $policy->get('empresa_id') !== null ? (int)$policy->get('empresa_id') : null,
			'workflow_state_id' => (int)$policy->get('workflow_state_id'),
			'resposta_minutos' => $policy->get('resposta_minutos') !== null ? (int)$policy->get('resposta_minutos') : null,
			'resolucao_minutos' => $policy->get('resolucao_minutos') !== null ? (int)$policy->get('resolucao_minutos') : null,
			'auto_escalar' => bool_or_null($policy->get('auto_escalar')),
			'is_final' => bool_or_null($policy->get('is_final')),
			'escalate_to_state_id' => $escStateId > 0 ? $escStateId : null,
			'escalate_to_nome' => $escState ? (string)$escState->get('nome') : null,
			'escalate_to_codigo' => $escState ? (string)$escState->get('codigo') : null,
			'escalate_after_minutos' => $policy->get('escalate_after_minutos') !== null ? (int)$policy->get('escalate_after_minutos') : null,
		];

		$isFinal = (bool)$policy->get('is_final');
		$autoEscalar = (bool)$policy->get('auto_escalar');
		$resolucao = (int)($policy->get('resolucao_minutos') ?? 0);
		$escCode = normalize_code($escState ? $escState->get('codigo') : '');

		if ($isFinal && $autoEscalar) {
			$output['validation']['errors'][] = 'estado_final_auto_escalar_conflito';
		}
		if ($resolucao !== 1) {
			$output['validation']['warnings'][] = 'resolucao_minutos_diferente_de_1:' . $resolucao;
		}
		if ($escCode !== 'pendente') {
			$output['validation']['warnings'][] = 'escalate_to_state_nao_pendente:' . ($escCode !== '' ? $escCode : 'vazio');
		}

		if ($resolucao > 0 && $empresaId > 0) {
			try {
				$bh = new BusinessHoursService();
				$newDeadline = $bh->addBusinessMinutes(Time::now(), $resolucao, $empresaId);
				$output['simulation']['new_sla_resolucao_minutos'] = $resolucao;
				$output['simulation']['new_data_limite_resolucao'] = $newDeadline ? $newDeadline->format('c') : null;
				$output['simulation']['used_business_hours_service'] = true;
			} catch (\Throwable $e) {
				$output['simulation']['used_business_hours_service'] = false;
				$output['validation']['warnings'][] = 'falha_business_hours_service:' . $e->getMessage();
			}
		} else {
			$output['validation']['warnings'][] = 'resolucao_minutos_invalido_para_simulacao';
		}
	}

	$stateCode = normalize_code($ticketRow['workflow_state_codigo'] ?? '');
	$isExec = in_array($stateCode, ['emandamento', 'em_execucao', 'execucao', 'em_andamento'], true);
	if (!$isExec) {
		$output['validation']['warnings'][] = 'ticket_nao_esta_em_execucao';
	}

	$situacao = (int)($ticket->get('situacao') ?? 0);
	$closedSituacoes = [];
	if (defined('C_TicketSituacaoResolvido')) {
		$closedSituacoes[] = (int)constant('C_TicketSituacaoResolvido');
	}
	if (defined('C_TicketSituacaoFechado')) {
		$closedSituacoes[] = (int)constant('C_TicketSituacaoFechado');
	}
	if (defined('C_TicketSituacaoCancelado')) {
		$closedSituacoes[] = (int)constant('C_TicketSituacaoCancelado');
	}
	if ($closedSituacoes !== [] && in_array($situacao, $closedSituacoes, true)) {
		$output['validation']['errors'][] = 'ticket_resolvido_fechado_ou_cancelado';
	}

	$canPropose = $output['validation']['errors'] === []
		&& $output['simulation']['used_business_hours_service'] === true
		&& $output['simulation']['new_data_limite_resolucao'] !== null
		&& $isExec;

	$output['validation']['policy_ok'] = $output['validation']['errors'] === [];
	$output['proposed_update']['will_update'] = false;
	$output['proposed_update']['fields'] = [
		[
			'field' => 'sla_resolucao_minutos',
			'old' => $ticketRow['sla_resolucao_minutos_atual'],
			'new' => $canPropose ? $output['simulation']['new_sla_resolucao_minutos'] : null,
		],
		[
			'field' => 'data_limite_resolucao',
			'old' => $ticketRow['data_limite_resolucao_atual'],
			'new' => $canPropose ? $output['simulation']['new_data_limite_resolucao'] : null,
		],
	];
} catch (\Throwable $e) {
	$output['validation']['errors'][] = 'dry_run_exception:' . $e->getMessage();
}

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(0);

