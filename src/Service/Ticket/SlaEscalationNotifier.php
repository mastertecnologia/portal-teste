<?php
declare(strict_types=1);

namespace App\Service\Ticket;

use Cake\Datasource\EntityInterface;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

/**
 * E-mails de auto-escalonamento SLA (manager / cliente / técnico). Falhas isoladas.
 */
class SlaEscalationNotifier {

	/**
	 * @param array{manager?:bool,customer?:bool,technician?:bool} $flags
	 * @return array<string, array{ok: bool, error?: string, skipped?: string}>
	 */
	public function notify(EntityInterface $ticket, int $empresaId, array $flags, ?string $contextNote = null): array {
		$out = [];
		$ticketId = (int)($ticket->get('id') ?? 0);
		if ($ticketId <= 0 || $empresaId <= 0) {
			return $out;
		}
		$subject = sprintf('[SLA] Ticket #%d — escalonamento automático', $ticketId);
		$body = $this->buildBody($ticket, $empresaId, $contextNote);

		if (!empty($flags['manager'])) {
			$out['manager'] = $this->sendManager($empresaId, $subject, $body);
		}
		if (!empty($flags['customer'])) {
			$out['customer'] = $this->sendCustomer($ticket, $empresaId, $subject, $body);
		}
		if (!empty($flags['technician'])) {
			$out['technician'] = $this->sendTechnician($ticket, $empresaId, $subject, $body);
		}

		return $out;
	}

	protected function buildBody(EntityInterface $ticket, int $empresaId, ?string $note): string {
		$tid = (int)($ticket->get('id') ?? 0);
		$lines = [
			'<p>O prazo de SLA de <strong>resolução</strong> deste ticket foi ultrapassado e o escalonamento automático foi acionado.</p>',
			sprintf('<p><strong>Ticket:</strong> #%d | <strong>Empresa ID:</strong> %d</p>', $tid, $empresaId),
		];
		$dl = $ticket->get('data_limite_resolucao');
		if ($dl !== null && $dl !== '') {
			$lines[] = sprintf('<p><strong>Prazo resolução:</strong> %s</p>', htmlspecialchars((string)$dl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		}
		if ($note !== null && $note !== '') {
			$lines[] = '<p>' . htmlspecialchars($note, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
		}
		$lines[] = '<p><em>Mensagem automática — Service Desk PGM</em></p>';

		return implode("\n", $lines);
	}

	/**
	 * @return array{ok: bool, error?: string, skipped?: string}
	 */
	protected function sendManager(int $empresaId, string $subject, string $html): array {
		$to = $this->managerRecipients($empresaId);
		if ($to === []) {
			return ['ok' => false, 'skipped' => 'no_manager_address'];
		}
		$transport = ((int)$empresaId === (defined('C_EmpresaMaster') ? (int)C_EmpresaMaster : -1)) ? 'master' : 'pgm';
		$from = 'helpdesk@pgm.inf.br';
		$nome = $this->empresaNomeFantasia($empresaId);
		foreach ($to as $addr) {
			if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			try {
				$email = new Email();
				$email->transport($transport);
				$email->from([$from => $nome])->to($addr)->emailFormat('html')->subject($subject)->send($html);
			} catch (\Throwable $e) {
				return ['ok' => false, 'error' => $e->getMessage()];
			}
		}

		return ['ok' => true];
	}

	/**
	 * @return array{ok: bool, error?: string, skipped?: string}
	 */
	protected function sendCustomer(EntityInterface $ticket, int $empresaId, string $subject, string $html): array {
		$idCliente = (int)($ticket->get('idcliente') ?? 0);
		if ($idCliente <= 0) {
			return ['ok' => false, 'skipped' => 'no_cliente'];
		}
		try {
			$cli = TableRegistry::get('Clientes')->get($idCliente, ['fields' => ['id', 'email', 'razaosocial', 'nome', 'tipo']]);
		} catch (\Throwable $e) {
			return ['ok' => false, 'skipped' => 'cliente_not_found'];
		}
		$addr = trim((string)($cli->get('email') ?? ''));
		if ($addr === '' || !filter_var($addr, FILTER_VALIDATE_EMAIL)) {
			return ['ok' => false, 'skipped' => 'no_client_email'];
		}
		$transport = ((int)$empresaId === (defined('C_EmpresaMaster') ? (int)C_EmpresaMaster : -1)) ? 'master' : 'pgm';
		$from = 'helpdesk@pgm.inf.br';
		$nome = $this->empresaNomeFantasia($empresaId);
		try {
			$email = new Email();
			$email->transport($transport);
			$email->from([$from => $nome])->to($addr)->emailFormat('html')->subject($subject)->send($html);
		} catch (\Throwable $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}

		return ['ok' => true];
	}

	/**
	 * @return array{ok: bool, error?: string, skipped?: string}
	 */
	protected function sendTechnician(EntityInterface $ticket, int $empresaId, string $subject, string $html): array {
		$cols = [];
		try {
			$cols = TableRegistry::get('Tickets')->getSchema()->columns();
		} catch (\Throwable $e) {
		}
		$tecId = 0;
		if (in_array('idtecnico_responsavel', $cols, true)) {
			$tecId = (int)($ticket->get('idtecnico_responsavel') ?? 0);
		} elseif (in_array('owner_id', $cols, true)) {
			$tecId = (int)($ticket->get('owner_id') ?? 0);
		}
		if ($tecId <= 0) {
			return ['ok' => false, 'skipped' => 'no_technician'];
		}
		try {
			$u = TableRegistry::get('Users')->get($tecId, ['fields' => ['id', 'email', 'name']]);
		} catch (\Throwable $e) {
			return ['ok' => false, 'skipped' => 'user_not_found'];
		}
		$addr = trim((string)($u->get('email') ?? ''));
		if ($addr === '' || !filter_var($addr, FILTER_VALIDATE_EMAIL)) {
			return ['ok' => false, 'skipped' => 'no_tech_email'];
		}
		$transport = ((int)$empresaId === (defined('C_EmpresaMaster') ? (int)C_EmpresaMaster : -1)) ? 'master' : 'pgm';
		$from = 'helpdesk@pgm.inf.br';
		$nome = $this->empresaNomeFantasia($empresaId);
		try {
			$email = new Email();
			$email->transport($transport);
			$email->from([$from => $nome])->to($addr)->emailFormat('html')->subject($subject)->send($html);
		} catch (\Throwable $e) {
			return ['ok' => false, 'error' => $e->getMessage()];
		}

		return ['ok' => true];
	}

	/**
	 * @return string[]
	 */
	protected function managerRecipients(int $empresaId): array {
		$raw = env('WORKFLOW_SLA_MANAGER_EMAIL', '');
		$out = $this->parseEmails($raw);
		if ($out !== []) {
			return array_values(array_unique($out));
		}
		try {
			$config = TableRegistry::get('Config')->get(1);
			$out = $this->parseEmails($config->emailtickets ?? '');
		} catch (\Throwable $e) {
			$out = [];
		}
		if ($out !== []) {
			return array_values(array_unique($out));
		}
		try {
			$em = TableRegistry::get('Empresas')->get($empresaId, ['fields' => ['id', 'email']]);
			$one = trim((string)($em->get('email') ?? ''));
			if ($one !== '' && filter_var($one, FILTER_VALIDATE_EMAIL)) {
				return [$one];
			}
		} catch (\Throwable $e) {
		}

		return [];
	}

	/**
	 * @return string[]
	 */
	protected function parseEmails($raw): array {
		$s = trim((string)$raw);
		if ($s === '') {
			return [];
		}
		$parts = preg_split('/[;,\s]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
		$out = [];
		foreach ($parts as $p) {
			$p = trim((string)$p);
			if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) {
				$out[] = $p;
			}
		}

		return $out;
	}

	protected function empresaNomeFantasia(int $empresaId): string {
		try {
			$em = TableRegistry::get('Empresas')->get($empresaId, ['fields' => ['id', 'nomefantasia', 'razaosocial']]);

			return trim((string)($em->get('nomefantasia') ?: $em->get('razaosocial') ?: 'PGM'));
		} catch (\Throwable $e) {
			return 'PGM';
		}
	}
}
