<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\NotFoundException;

/**
 * Pesquisa CSAT pública (cliente acessa via token único enviado por e-mail
 * após fechamento do ticket).
 *
 * Rotas (sem autenticação obrigatória):
 *   GET  /csat/{token}        — exibe formulário
 *   POST /csat/{token}        — grava resposta
 *   GET  /csat/{token}/ok     — agradecimento
 */
class TicketCsatController extends AppController {

	public function initialize() {
		parent::initialize();
		$this->loadModel('TicketCsatResponses');
		$this->loadModel('Tickets');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		// CSAT é público (cliente sem login)
		$this->Auth->allow(['responder', 'sucesso']);
	}

	public function isAuthorized($user) {
		return true;
	}

	/**
	 * @param string|null $token
	 */
	public function responder($token = null) {
		$token = (string)$token;
		if ($token === '' || strlen($token) < 16) {
			throw new NotFoundException(__('Token inválido.'));
		}

		$existente = $this->TicketCsatResponses->find()
			->where(['TicketCsatResponses.token' => $token])
			->first();
		if ($existente !== null) {
			return $this->redirect(['action' => 'sucesso', $token]);
		}

		$ticket = $this->resolveTicketByToken($token);
		if ($ticket === null) {
			throw new NotFoundException(__('Ticket não encontrado para este token.'));
		}

		if ($this->request->is('post')) {
			$score = (int)$this->request->getData('csat_score');
			$nps = $this->request->getData('nps_score');
			$nps = ($nps === null || $nps === '') ? null : (int)$nps;
			$comentario = trim((string)$this->request->getData('comentario'));
			if ($score < 1 || $score > 5) {
				$this->Flash->error(__('Selecione uma nota CSAT de 1 a 5 estrelas.'));
			} else {
				$entity = $this->TicketCsatResponses->newEntity([
					'idempresa' => (int)$ticket->get('idempresa'),
					'ticket_id' => (int)$ticket->get('id'),
					'idcliente' => (int)$ticket->get('idcliente'),
					'csat_score' => $score,
					'nps_score' => $nps !== null && $nps >= 0 && $nps <= 10 ? $nps : null,
					'comentario' => $comentario !== '' ? $comentario : null,
					'token' => $token,
					'responded_at' => date('Y-m-d H:i:s'),
					'responded_ip' => substr((string)$this->request->clientIp(), 0, 45),
				]);
				if ($this->TicketCsatResponses->save($entity)) {
					return $this->redirect(['action' => 'sucesso', $token]);
				}
				$this->Flash->error(__('Falha ao salvar resposta.'));
			}
		}

		$this->set([
			'title' => __('Pesquisa CSAT · Ticket #{0}', (int)$ticket->get('id')),
			'erpNavActive' => '',
			'erpBreadcrumb' => [['label' => __('Pesquisa CSAT'), 'cur' => true]],
			'erpEmpresas' => [],
			'ticket' => $ticket,
			'csatToken' => $token,
		]);
	}

	/**
	 * @param string|null $token
	 */
	public function sucesso($token = null) {
		$this->set([
			'title' => __('Obrigado!'),
			'erpNavActive' => '',
			'erpBreadcrumb' => [['label' => __('Pesquisa CSAT'), 'cur' => true]],
			'erpEmpresas' => [],
			'csatToken' => (string)$token,
		]);
	}

	/**
	 * Token = `csat-{ticket_id}-{hash}` onde hash é sha1(idempresa . ticket_id . App.salt).
	 *
	 * @param string $token
	 * @return \Cake\Datasource\EntityInterface|null
	 */
	protected function resolveTicketByToken(string $token) {
		if (!preg_match('/^csat-(\d+)-([a-f0-9]{16})$/', $token, $m)) {
			return null;
		}
		$tid = (int)$m[1];
		$hash = $m[2];
		$ticket = null;
		try {
			$ticket = $this->Tickets->find()->where(['Tickets.id' => $tid])->first();
		} catch (\Throwable $e) {
		}
		if ($ticket === null) {
			return null;
		}
		$expected = substr(sha1(
			(string)$ticket->get('idempresa') . '|' . $tid . '|' . (string)\Cake\Core\Configure::read('Security.salt')
		), 0, 16);
		if (!hash_equals($expected, $hash)) {
			return null;
		}

		return $ticket;
	}

	/**
	 * Helper estático: gera token determinístico de um ticket.
	 */
	public static function tokenForTicket(int $ticketId, int $idempresa): string {
		$hash = substr(sha1((string)$idempresa . '|' . $ticketId . '|' . (string)\Cake\Core\Configure::read('Security.salt')), 0, 16);

		return 'csat-' . $ticketId . '-' . $hash;
	}
}
