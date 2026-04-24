<?php
namespace App\Model\Table;

use App\Utility\SupportInboxMail;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Mailer\Email;

class TicketsTable extends Table {
	private function parseEmailList($value) {
		$value = (string)$value;
		if ($value === '') return [];
		$value = str_replace(["\r", "\n", "\t"], ' ', $value);
		$parts = preg_split('/[;,\\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
		$out = [];
		foreach ($parts as $p) {
			$p = trim($p);
			if ($p === '') continue;
			// Aceita "Nome" <mail@dominio.com> colado no campo de configuração
			if (preg_match('/<([^>]+@[^>]+)>/', $p, $m)) {
				$p = trim($m[1]);
			}
			if (filter_var($p, FILTER_VALIDATE_EMAIL)) {
				$out[] = $p;
			}
		}
		return array_values(array_unique($out));
	}

	public function initialize(array $config) {
		$this->hasMany('Ticketcomentarios')->setForeignKey('idticket')->setDependent(true);
		$this->hasOne('Ticketsusers')->setForeignKey('idticket')->setDependent(true);
		
		$this->hasMany('Ticketsanexos')->setForeignKey('idticket')->setDependent(true);
		$this->hasMany('Ticketsmovs')->setForeignKey('idticket')->setDependent(true);
		$this->hasMany('Ticketshoras')->setForeignKey('idticket')->setDependent(true);
		$this->hasMany('AtendimentoTimer', ['foreignKey' => 'idticket', 'dependent' => true]);

		// LEFT: tickets legados ou idcliente/idautor inconsistente não podem sumir da listagem JSON.
		$this->belongsTo('Clientes', ['foreignKey' => 'idcliente', 'joinType' => 'LEFT']);
		$this->belongsTo('Clicontabilidade')->setForeignKey('idsolicitantecontador');
		$this->belongsTo('users', ['foreignKey' => 'idautor', 'joinType' => 'LEFT'])->setDependent(false);
		$this->belongsTo('Queues', ['foreignKey' => 'queue_id', 'joinType' => 'LEFT']);
		$this->belongsTo('SupportLevels', ['foreignKey' => 'support_level_id', 'joinType' => 'LEFT']);
		$this->belongsTo('SlaPolicies', ['foreignKey' => 'sla_policy_id', 'joinType' => 'LEFT']);

		$this->hasMany('TicketHistories', ['foreignKey' => 'ticket_id', 'dependent' => true]);
		$this->hasMany('TicketEvents', ['foreignKey' => 'ticket_id', 'dependent' => true]);
		$this->hasMany('Ticketsclientes', ['dependent' => true,'cascadeCallbacks' => true,])->setForeignKey('idticket'); 
		
		$this->hasOne('Empresas');
		$this->hasOne('Config');
	}

	/**
	 * (1) owner_id: espelha idtecnico_responsavel (ver legado; não usar owner_id como fonte de verdade).
	 * (2) _auditPrev: snapshot para afterSave de ticket_events (type audit) — não duplicar outro beforeSave: em PHP
	 *     só existe um beforeSave; combinar sempre aqui.
	 */
	public function beforeSave(Event $event, EntityInterface $entity, ArrayObject $options) {
		$cols = $this->getSchema()->columns();
		$hasOwner = in_array('owner_id', $cols, true);
		$hasTec = in_array('idtecnico_responsavel', $cols, true);
		if ($hasOwner && $hasTec) {
			$tid = $entity->idtecnico_responsavel;
			$uid = ($tid !== null && $tid !== '' && (int)$tid > 0) ? (int)$tid : null;
			$entity->owner_id = $uid;
		}
		if (!empty($options['skipTicketEventAudit'])) {
			return true;
		}
		if ($entity->isNew() || (int)($entity->id ?? 0) <= 0) {
			return true;
		}
		if (!$this->_ticketEventsTableExists()) {
			return true;
		}
		try {
			$prev = $this->get($entity->id, [
				'fields' => ['id', 'situacao', 'prioridade', 'severidade', 'idtecnico_responsavel', 'owner_id'],
			]);
			$options['_auditPrev'] = $prev;
		} catch (\Throwable $e) {
		}

		return true;
	}

	public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options) {
		if (!empty($options['skipTicketEventAudit'])) {
			return;
		}
		$te = $this->_ticketEventsTableExists();
		if (!$te) {
			return;
		}
		if ((int)($entity->id ?? 0) <= 0) {
			return;
		}
		$prev = isset($options['_auditPrev']) ? $options['_auditPrev'] : null;
		if ($prev === null) {
			return;
		}
		$watch = [];
		$schemaCols = $this->getSchema()->columns();
		if (in_array('situacao', $schemaCols, true)) {
			$p = $prev->get('situacao');
			$n = $entity->get('situacao');
			if ((string)$p !== (string)$n) {
				$watch['situacao'] = 'Situação';
			}
		}
		if (in_array('prioridade', $schemaCols, true)) {
			$p = $prev->get('prioridade');
			$n = $entity->get('prioridade');
			if ((string)$p !== (string)$n) {
				$watch['prioridade'] = 'Prioridade';
			}
		} elseif (in_array('severidade', $schemaCols, true)) {
			$p = $prev->get('severidade');
			$n = $entity->get('severidade');
			if ((string)$p !== (string)$n) {
				$watch['severidade'] = 'Severidade';
			}
		}
		if (in_array('idtecnico_responsavel', $schemaCols, true)) {
			$p = $prev->get('idtecnico_responsavel');
			$n = $entity->get('idtecnico_responsavel');
			if ((string)$p !== (string)$n) {
				$watch['idtecnico_responsavel'] = 'Técnico responsável';
			}
		} elseif (in_array('owner_id', $schemaCols, true)) {
			$p = $prev->get('owner_id');
			$n = $entity->get('owner_id');
			if ((string)$p !== (string)$n) {
				$watch['owner_id'] = 'Responsável';
			}
		}
		if ($watch === []) {
			return;
		}
		$lines = [];
		foreach ($watch as $field => $label) {
			$ov = $prev->get($field);
			$nv = $entity->get($field);
			$lines[] = $label . ': ' . $this->fmtAuditVal($ov) . ' → ' . $this->fmtAuditVal($nv);
		}
		$uid = $this->currentRequestUserId();
		$ev = $this->TicketEvents->newEntity([
			'idempresa' => (int)($entity->idempresa ?? 0),
			'ticket_id' => (int)$entity->id,
			'user_id' => $uid,
			'type' => 'audit',
			'description' => implode("\n", $lines),
			'created' => Time::now(),
		], ['validate' => false, 'markClean' => true]);
		try {
			$this->TicketEvents->save($ev, ['checkRules' => false, 'validate' => false, 'skipBillingClassify' => true]);
		} catch (\Throwable $e) {
			$this->log('TicketsTable::afterSave audit event: ' . $e->getMessage(), 'warning');
		}
	}

	protected function fmtAuditVal($v) {
		if ($v === null) {
			return '—';
		}
		if (is_object($v) && method_exists($v, 'format')) {
			return (string)$v;
		}

		return (string)$v;
	}

	protected function currentRequestUserId(): ?int {
		$req = Router::getRequest();
		if ($req instanceof ServerRequest) {
			$uid = $req->getSession()->read('Auth.User.id');
			if ($uid !== null && $uid !== '') {
				return (int)$uid;
			}
		}
		// CLI / filas: sem sessão
		return null;
	}

	protected function _ticketEventsTableExists(): bool {
		static $y;
		if ($y !== null) {
			return $y;
		}
		try {
			$c = \Cake\Datasource\ConnectionManager::get('default')->getSchemaCollection();
			$y = in_array('ticket_events', $c->listTables(), true);
		} catch (\Throwable $e) {
			$y = false;
		}

		return $y;
	}

	/**
	 * Para save com lista explícita de fields: incluir owner_id quando idtecnico_responsavel for persistido.
	 */
	public function fieldsComEspelhoResponsavel(array $fields): array {
		$cols = $this->getSchema()->columns();
		if (in_array('idtecnico_responsavel', $cols, true) && in_array('owner_id', $cols, true)
			&& in_array('idtecnico_responsavel', $fields, true) && !in_array('owner_id', $fields, true)) {
			$fields[] = 'owner_id';
		}

		return $fields;
	}

	public function email($idticket, $acao = null, $emailDest = null, $idempresa = null) {
		$ticket = $this->get($idticket, [
			'contain' => [
				'Clientes' => ['fields' => ['Clientes.email', 'Clientes.razaosocial']],
				'users' => ['fields' => ['Users.email', 'Users.name']]
			]
		]);
		$cliente = $this->Clientes->findById($ticket->idcliente)->first();
		if (empty($cliente)) {
			$this->log('[TicketsTable::email] Cliente não encontrado idcliente=' . $ticket->idcliente, 'error');
			return false;
		}
		$emailResponsavel = $this->parseEmailList(isset($cliente->emailresponsavel) ? $cliente->emailresponsavel : '');
		
		$empresa = $this->Empresas->get($idempresa);
		if (isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
		else $nomeempresa = $empresa->razaosocial;
		$transporteEmail = ((int)$idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm';

		$configRow = TableRegistry::get('Config')->get(1);
		$urlFora = !empty($configRow->urlfora) ? $configRow->urlfora : '#';

		// Ticket criado: mesmo envio que UsersController::email (Cadastre-se) — ->to() com string de emailtickets.
		if ($acao == C_TicketCriado) {
			$assunto = AssuntoTicket($ticket->assunto);
			$nomeClienteEmail = ($cliente->tipo == C_ClientesTipoFisica) ? $cliente->nome : $cliente->razaosocial;
			$message =
				"<h3> Ticket $idticket Criado! </h3>
				<p> <b> Assunto: </b> $assunto</p>
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				<p> <b> Cliente: </b> $nomeClienteEmail</p>
			";
			$subject = "Ticket nº $idticket criado - $nomeempresa";
			if (!SupportInboxMail::sendHtml($message, $subject, (int)$idempresa)) {
				return false;
			}
			$from = 'helpdesk@pgm.inf.br';
			foreach ($emailResponsavel as $regEmailResp) {
				try {
					$emailResp = new Email();
					$emailResp->transport($transporteEmail);
					$emailResp->from([$from => $nomeempresa])->to($regEmailResp)->emailFormat('html')->subject($subject);
					$emailResp->send($message);
				} catch (\Throwable $e) {
					$this->log('[TicketsTable::email] Falha cópia responsável: ' . $e->getMessage(), 'error');
				}
			}
			$raw = !empty($configRow->emailtickets) ? trim((string)$configRow->emailtickets) : '';
			return $raw !== '' ? $raw : '1';
		}

		// E-mail do destinatário (prioridade: ticket, usuário vinculado ao ticket, cliente vinculado ao ticket)
		if (empty($emailDest)) {
			if (!empty($ticket->email)) {
				$emailDest = $ticket->email;
			} elseif (!empty($ticket->user) && !empty($ticket->user->email)) {
				$emailDest = $ticket->user->email;
			} elseif (!empty($ticket->cliente) && !empty($ticket->cliente->email)) {
				$emailDest = $ticket->cliente->email;
			}
		}
		$emailDestList = $this->parseEmailList($emailDest);

		if (empty($emailDestList)) {
			if ($acao == C_TicketCriado) {
				$this->log('[TicketsTable::email] Ticket criado: nenhum destinatário válido (campo emailtickets vazio ou formato inválido).', 'warning');
			} else {
				$this->log('[TicketsTable::email] Lista de destinatários vazia após validação.', 'warning');
			}
			return false;
		}

		// Cálculo de horas técnicas do ticket e do mês para o cliente
		$minutosTicket = $this->Ticketshoras->minutosTicket($idticket, '01/01/2000', '31/12/2099');
		$horasTicket = $minutosTicket > 0 ? number_format($minutosTicket / 60, 2, ',', '.') : null;

		$inicioMesAtual = '01/' . date('m/Y');
		$fimMesAtual = date('t') . '/' . date('m/Y');
		$minutosMesCliente = $this->Ticketshoras->minutosCliente($ticket->idcliente, $inicioMesAtual, $fimMesAtual);
		$horasMesCliente = $minutosMesCliente > 0 ? number_format($minutosMesCliente / 60, 2, ',', '.') : null;

		$abertoPor = null;
		if (!empty($ticket->user) && !empty($ticket->user->name)) {
			$abertoPor = $ticket->user->name;
		}

		$data = @date_format($ticket->datafinalizado, 'd/m/Y');
		$assunto = AssuntoTicket($ticket->assunto);

		if (empty($acao)) {
			$descSic = $ticket->situacao == C_TicketSituacaoRespondido ? 'respondido' : 'resolvido';
		
			$linhaAbertoPor = $abertoPor !== null ? "<p> <b> Aberto por: </b> $abertoPor</p>" : "";
			$linhaHorasTicket = $horasTicket !== null
				? "<p> <b> Horas trabalhadas neste atendimento: </b> {$horasTicket} h</p>"
				: "";
			$linhaHorasMes = $horasMesCliente !== null
				? "<p> <b> Horas técnicas consumidas neste mês para este cliente: </b> {$horasMesCliente} h</p>"
				: "";

			$message = 
				"<h3> Ticket $idticket ${descSic}! </h3>
				<p> <b> Data de término: </b> $data</p>
				<p> <b> Assunto: </b> $assunto</p>
				$linhaAbertoPor
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				$linhaHorasTicket
				$linhaHorasMes
				<br/><strong>Verifique os tickets da sua empresa <a href='".$urlFora."'>clicando aqui!</a></strong>
				<br/><br />Atenciosamente,<br />$nomeempresa.
			";

			$subject = "Seu ticket nº $idticket foi $descSic - $nomeempresa";
		} else if ($acao == C_TicketsAcaoPendente) {
			$message = 
				"<h3> Ticket $idticket aguardando técnico! </h3>
				<p> <b> Assunto: </b> $assunto</p>
				<p> Seu ticket nº $idticket foi movido para 'Aguardando técnico' </p>
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				<br/><strong>Verifique os tickets da sua empresa <a href='".$urlFora."'>clicando aqui!</a></strong>
				<br/><br />Atenciosamente,<br/>$nomeempresa.
			";

			$subject = "Ticket nº $idticket foi movido para 'Aguardando técnico' - $nomeempresa";
		} else if ($acao == C_TicketsAcaoEmandamento) {
			$message = 
				"<h3> Ticket $idticket em execução! </h3>
				<p> <b> Assunto: </b> $assunto</p>
				<p> Seu ticket nº $idticket foi movido para 'Em execução' </p>
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				<br/><strong>Verifique os tickets da sua empresa <a href='".$urlFora."'>clicando aqui!</a></strong>
				<br/><br />Atenciosamente,<br/>$nomeempresa.
			";

			$subject = "Ticket nº $idticket Em execução - $nomeempresa";
		} else if ($acao == C_TicketsAcaoFechado) {
			$message = 
				"<h3> Ticket $idticket cancelado! </h3>
				<p> <b> Assunto: </b> $assunto</p>
				<p> Seu ticket nº $idticket foi cancelado! </p>
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				<br/><strong>Verifique os tickets da sua empresa <a href='".$urlFora."'>clicando aqui!</a></strong>
				<br/><br />Atenciosamente,<br/>$nomeempresa.
			";

			$subject = "Ticket nº $idticket cancelado - $nomeempresa";
		}

		$email = new Email();
		
		$email->transport($transporteEmail);
		$from = 'helpdesk@pgm.inf.br';
		
		$email->from([$from => $nomeempresa])->to($emailDestList)->emailFormat('html')->subject($subject);
			
		try {
			if ($email->send($message)) {
				if (empty($acao)) {
					$ticket->emailenviado = 1;
					$this->save($ticket);
				}

				foreach ($emailResponsavel as $regEmailResp) {
					try {
						$emailResp = new Email();
						$emailResp->transport($transporteEmail);
						$emailResp->from([$from => $nomeempresa])->to($regEmailResp)->emailFormat('html')->subject($subject);
						$emailResp->send($message);
					} catch (\Throwable $e) {
						$this->log('[TicketsTable::email] Falha cópia responsável: ' . $e->getMessage(), 'error');
					}
				}

				return implode(';', $emailDestList);
			}
			$this->log('[TicketsTable::email] Email::send() retornou false (transporte ' . $transporteEmail . ', ticket ' . $idticket . '). Verifique SMTP/senha/TLS.', 'warning');
		} catch (\Throwable $e) {
			$this->log('[TicketsTable::email] Falha SMTP/envio: ' . $e->getMessage(), 'error');
		}
		return false;
	}
}
