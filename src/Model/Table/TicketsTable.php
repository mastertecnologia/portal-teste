<?php
namespace App\Model\Table;

use Cake\ORM\Table;
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

		$this->belongsTo('Clientes')->setForeignKey('idcliente');
		$this->belongsTo('Clicontabilidade')->setForeignKey('idsolicitantecontador');
		$this->belongsTo('users')->setForeignKey('idautor')->setDependent(false);

		$this->hasMany('Ticketsclientes', ['dependent' => true,'cascadeCallbacks' => true,])->setForeignKey('idticket'); 
		
		$this->hasOne('Empresas');
		$this->hasOne('Config');
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

		// Ticket criado: notificar o suporte (config.emailtickets) — deve ser resolvido ANTES do parse/lista vazia.
		// Antes, $emailDest era setado só no bloco C_TicketCriado (abaixo), depois da verificação, gerando falha falsa.
		if ($acao == C_TicketCriado) {
			$emailDest = !empty($configRow->emailtickets) ? trim((string)$configRow->emailtickets) : null;
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
		} else if ($acao == C_TicketCriado) {
			$nomeClienteEmail = ($cliente->tipo == C_ClientesTipoFisica) ? $cliente->nome : $cliente->razaosocial;
			$message =
				"<h3> Ticket $idticket Criado! </h3>
				<p> <b> Assunto: </b> $assunto</p>
				<p> <b> Descrição: </b> $ticket->solicitacao</p>
				<p> <b> Cliente: </b> $nomeClienteEmail</p>
			";
			$subject = "Ticket nº $idticket criado - $nomeempresa";
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
