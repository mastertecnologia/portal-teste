<?php
namespace App\Shell;

use App\Service\Ticket\WorkflowSlaService;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;

/**
 * Corrige tickets já abertos cujo SLA diverge da policy do workflow do estado atual.
 * Usa exclusivamente {@see WorkflowSlaService::applyStateSla()} (mesma lógica da transição).
 *
 * Por segurança há apenas modo por ticket (sem --all em massa).
 *
 * Uso: bin/cake workflow_sla_reconcile ticket 1174
 */
class WorkflowSlaReconcileShell extends Shell {

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->setDescription(
			'Realinha SLA do ticket com workflow_sla_policies do estado atual (somente um id por vez).'
		);

		return $parser;
	}

	/**
	 * @return int Exit code
	 */
	public function ticket() {
		$id = 0;
		if (!empty($this->args[0]) && ctype_digit((string)$this->args[0])) {
			$id = (int)$this->args[0];
		}
		if ($id <= 0) {
			$this->err('Uso: bin/cake workflow_sla_reconcile ticket <id>');

			return 1;
		}

		$tickets = TableRegistry::get('Tickets');
		try {
			$t = $tickets->get($id);
		} catch (\Throwable $e) {
			$this->err('Ticket não encontrado: ' . $e->getMessage());

			return 1;
		}

		$this->hr(0);
		$this->out(sprintf('Ticket #%d — Antes:', $id));
		$this->out($this->slaSnapshotLine($t));

		$empresa = (int)($t->get('idempresa') ?? 0);
		$stateId = (int)($t->get('workflow_state_id') ?? 0);
		if ($empresa <= 0) {
			$this->err('Ticket sem idempresa.');

			return 1;
		}
		if ($stateId <= 0) {
			$this->err('Ticket sem workflow_state_id.');

			return 1;
		}

		$svc = new WorkflowSlaService($tickets);
		$changed = $svc->applyStateSla($t, $empresa, $stateId);
		if ($changed === []) {
			$this->out('Nada a alterar (já alinhado, política sem minutos na dimensão afetada, estado final/pausa ou feature desligada).');
			$this->hr(0);

			return 0;
		}

		$this->out('Campos alterados na entidade: ' . implode(', ', $changed));

		if (!$tickets->save($t, ['fields' => $changed, 'atomic' => false])) {
			$this->err('Falha ao gravar.');
			try {
				$this->err(json_encode($t->getErrors(), JSON_UNESCAPED_UNICODE));
			} catch (\Throwable $e) {
			}
			$this->hr(0);

			return 1;
		}

		try {
			$tAfter = $tickets->get($id);
		} catch (\Throwable $e) {
			$this->out(sprintf('OK gravado — não foi possível recarregar ticket: %s', $e->getMessage()));
			$this->hr(0);

			return 0;
		}

		$this->out(sprintf('Ticket #%d — Depois (recarregado da BD):', $id));
		$this->out($this->slaSnapshotLine($tAfter));
		$this->hr(0);

		return 0;
	}

	/**
	 * @param \Cake\Datasource\EntityInterface|object $t
	 */
	protected function slaSnapshotLine($t): string {
		$fmt = static function ($v): string {
			if ($v === null || $v === '') {
				return '(null)';
			}
			if ($v instanceof \DateTimeInterface) {
				return $v->format('Y-m-d H:i:s T');
			}

			return (string)$v;
		};

		return sprintf(
			'sla_resposta_minutos=%s | sla_resolucao_minutos=%s | data_limite_resposta=%s | data_limite_resolucao=%s',
			$fmt($t->get('sla_resposta_minutos')),
			$fmt($t->get('sla_resolucao_minutos')),
			$fmt($t->get('data_limite_resposta')),
			$fmt($t->get('data_limite_resolucao'))
		);
	}

	public function main() {
		$this->out('Uso: bin/cake workflow_sla_reconcile ticket <id>  (apenas um ticket; sem modo em massa)');
	}
}
