<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Mailer\Email;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class TicketcomentariosController extends AppController {

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');
		if ($action === 'apiAdd') {
			return in_array((int)($user['role'] ?? -1), [0, 1], true);
		}
		return parent::isAuthorized($user);
	}

	public function initialize() {
		parent::initialize();
		$this->loadModel('Tickets');
		$this->loadModel('Users');
		$this->loadModel('Ticketsusers');
		$this->loadModel('Ticketcomentarios');
		$this->loadModel('Ticketsmovs');
		$this->loadModel('Ticketslogemail');
		$this->loadModel('Clientes');
		$this->loadModel('Empresas');
		$this->loadModel('Config');
		$this->loadModel('Notificacoes');
	}

	public function criarMov($idticket = null, $sitantiga = null, $sitnova = null, $observacao = null) {
		$mov = $this->Ticketsmovs->newEntity();
		$mov->idticket = $idticket;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->idusuario = $this->Auth->user('id');
		$mov->idempresa = $this->Auth->user('idempresa');
		$mov->datetime = date('d/m/Y H:i:s', time());

		if (!empty($observacao)) $mov->observacao = $observacao;

		try {
			$this->_enviarEmailNovoComentario($idticket, C_TicketsAcaoAddComentario);
		} catch (\Throwable $e) {
			$this->log('Ticketcomentarios::criarMov _enviarEmailNovoComentario: ' . $e->getMessage(), 'error');
		}

		return $this->Ticketsmovs->save($mov);
	}

	/**
	 * Envia o e-mail de “novo comentário” sem Flash/redirect (uso em criarMov e API).
	 *
	 * @return bool true se enviou ao destinatário principal; false se não havia destino ou falhou.
	 */
	protected function _enviarEmailNovoComentario($idticket = null, $acao = null) {
		$ticket = $this->Tickets->get($idticket, ['contain' => ['Clientes' => ['fields' => ['Clientes.email', 'Clientes.emailresponsavel']], 'Users' => ['fields' => ['Users.email']]]]);
		$cliente = $this->Clientes->findById($ticket->idcliente)->first();
		$emailResponsavel = explode(';', $cliente->emailresponsavel ?? '');

		$idempresaAtual = (int)$this->Auth->user('idempresa');
		$empresa = $this->Empresas->get($idempresaAtual);
		$nomeempresa = isset($empresa->nomefantasia) ? $empresa->nomefantasia : $empresa->razaosocial;

		$emailDest = '';
		$autorEnt = $ticket->users ?? $ticket->user ?? null;
		if ($this->Auth->user('role') == 1) {
			$emailDest = $this->Config->get(1)->emailtickets;
		} elseif (!empty($ticket->email)) {
			$emailDest = $ticket->email;
		} elseif ($autorEnt && !empty($autorEnt->email)) {
			$emailDest = $autorEnt->email;
		} elseif (!empty($ticket->cliente->email)) {
			$emailDest = $ticket->cliente->email;
		}

		if (empty($emailDest)) {
			return false;
		}

		$assunto = AssuntoTicket($ticket->assunto);
		$message =
			"<h3> Ticket $idticket </h3>
			<p> <b> Assunto: </b> $assunto</p>
			<p> Um novo comentário foi adicionado no seu ticket nº $idticket - $nomeempresa </p>
			<br/><strong>Verifique os tickets da sua empresa <a href='" . $this->Config->get(1)->urlfora . "'>clicando aqui!</a></strong>
			<br/><br />Atenciosamente,<br />$nomeempresa.
		";
		$subject = "Novo comentário no ticket nº $idticket - $nomeempresa";

		$email = new Email();
		$email->transport($idempresaAtual === (int)C_EmpresaMaster ? 'master' : 'pgm');
		$from = 'helpdesk@pgm.inf.br';
		$email->from([$from => $nomeempresa])->to($emailDest)->emailFormat('html')->subject($subject);

		if (!$email->send($message)) {
			return false;
		}

		$log = $this->Ticketslogemail->newEntity();
		$log->idticket = $idticket;
		$log->acao = $acao;
		$log->iduser = $this->Auth->user('id');
		$log->datetime = date('d/m/Y H:i:s', time());
		$this->Ticketslogemail->save($log);

		foreach ($emailResponsavel as $regEmailResp) {
			if (!empty($regEmailResp)) {
				$email2 = new Email();
				$email2->transport($idempresaAtual === (int)C_EmpresaMaster ? 'master' : 'pgm');
				$email2->from([$from => $nomeempresa])->to($regEmailResp)->emailFormat('html')->subject($subject);
				$email2->send($message);
			}
		}

		return true;
	}

	protected function _ticketVisibleForComentarioApi($ticket) {
		if (empty($ticket) || (int)$ticket->idempresa !== (int)$this->Auth->user('idempresa')) {
			return false;
		}
		$role = (int)$this->Auth->user('role');
		$idempresa = $this->Auth->user('idempresa');
		if ($role === (int)C_RoleCliente) {
			$idcliente = $this->Auth->user('idcliente');
			$clienteBase = $this->Clientes->findById($idcliente)->first();
			if (empty($clienteBase)) {
				return false;
			}
			$clienteVerifica = null;
			if ($clienteBase->tipo == C_ClientesTipoJuridica) {
				$clienteVerifica = $this->Clientes
					->findByCnpj(removeCaracteres($clienteBase->cnpj))
					->where(['idempresa' => $idempresa])
					->first();
			} else {
				$clienteVerifica = $this->Clientes
					->findByCpf(removeCaracteres($clienteBase->cpf))
					->where(['idempresa' => $idempresa])
					->first();
			}
			if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
				return false;
			}
			if ($ticket->idautor != $this->Auth->user('id') && !$this->Auth->user('permissaoacesso')) {
				return false;
			}
			return true;
		}
		if ($role === 0) {
			if ((int)$this->Auth->user('admin') !== 1) {
				$meuticket = $this->Tickets->findById($ticket->id)->first();
				if (!$meuticket) {
					return false;
				}
				if ($this->Auth->user('id') != $meuticket->idautor && $this->Auth->user('idcliente') != $meuticket->idcliente) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	public function criaNot($situacao, $idticket, $idcliente = null) {
		$not = $this->Notificacoes->newEntity();
		$not->titulo = 'Ticket '.$idticket;
		$not->texto = 'Novo comentário no ticket!';
		$not->situacao = 0;
		$not->tipo = C_NotificacaoTipoTikcetComentario;
		$not->idacao = $idticket;
		$not->idcliente = $idcliente;
		$not->iduser = $this->Auth->user('id');
		$not->data = date('d/m/Y');

		return $this->Notificacoes->save($not);
	}
	
	/**
	 * Retorna apenas o HTML da lista de comentários (para HTMX swap após add).
	 * GET; requer autenticação.
	 */
	public function partialList($idticket = null) {
		if (!$idticket) return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
		$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
		if (!$ticket) return $this->response->withStatus(404);
		$ticketcomentarios = $this->Ticketcomentarios->find('all', [
			'contain' => ['Users', 'Tickets'],
			'fields' => ['Users.name', 'Users.role', 'Ticketcomentarios.comentario', 'Ticketcomentarios.created', 'Tickets.idempresa']
		])->where(['Ticketcomentarios.idticket' => $idticket, 'Tickets.idempresa' => $this->Auth->user('idempresa')])->order(['Ticketcomentarios.id'])->toArray();
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->viewBuilder()->setLayout('ajax');
		$this->viewBuilder()->setTemplate('partial_list');
	}

	public function add($idticket = null, $action = null) {
		$data = $this->request->getData();
		$comentario = $this->Ticketcomentarios->newEntity();

		if ($this->request->is('post')) {
			if ($action == null) $action = 'edit';

			$comentario = $this->Ticketcomentarios->patchEntity($comentario, $this->request->getData());
			$comentario->idautor = $this->Auth->user('id');
			$comentario->idticket = $idticket;
			$comentario->idempresa = $this->Auth->user('idempresa');
			$comentario->created = date("Y-m-d H:i:s");;

			if ($this->Ticketcomentarios->save($comentario)) {
				$ticket = $this->Tickets->find('all')->where(['AND' => ['id' => $idticket], ['idempresa' => $this->Auth->user('idempresa')]])->first();
				if ($ticket) {
					try {
						$sitantiga = $ticket->situacao;
						if (isset($sitantiga)) {
							$this->criarMov($idticket, $sitantiga, C_TicketSituacaoRespondido);
						}
					} catch (\Throwable $e) {
						$this->log('Ticketcomentarios::add criarMov: ' . $e->getMessage(), 'error');
					}
					try {
						$this->criaNot($ticket->situacao, $ticket->id, $ticket->idcliente);
					} catch (\Throwable $e) {
						$this->log('Ticketcomentarios::add criaNot: ' . $e->getMessage(), 'error');
					}
				}
				$this->Flash->success(__("Comentário enviado com sucesso!"));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $comentario->id);
				if ($this->request->getHeaderLine('HX-Request')) {
					return $this->redirect(['action' => 'partialList', $idticket]);
				}
				if ($this->Auth->user('role') == 0) {
					return $this->redirect(['controller' => 'Tickets', 'action' => $action, $idticket]);
				}
				return $this->redirect(['controller' => 'Tickets', 'action' => 'view', $idticket]);
			}
			$this->Flash->error(__('Não foi possível enviar o comentário.'));
		}
	}

	public function apiAdd($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_ticketVisibleForComentarioApi($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$data = $this->request->getData();
		$json = $this->request->input('json_decode', true);
		if (is_array($json)) {
			$data = array_merge($data, $json);
		}
		$comentario = $this->Ticketcomentarios->newEntity();
		$comentario = $this->Ticketcomentarios->patchEntity($comentario, $data);
		$comentario->idautor = $this->Auth->user('id');
		$comentario->idticket = $idticket;
		$comentario->idempresa = $this->Auth->user('idempresa');
		$comentario->created = date('Y-m-d H:i:s');

		if (!$this->Ticketcomentarios->save($comentario)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'validation', 'errors' => $comentario->getErrors()], 400);
		}
		$ticketReload = $this->Tickets->find('all')->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
		if ($ticketReload) {
			try {
				$sitantiga = $ticketReload->situacao;
				if (isset($sitantiga)) {
					$this->criarMov($idticket, $sitantiga, C_TicketSituacaoRespondido);
				}
			} catch (\Throwable $e) {
				$this->log('Ticketcomentarios::apiAdd criarMov: ' . $e->getMessage(), 'error');
			}
			try {
				$this->criaNot($ticketReload->situacao, $ticketReload->id, $ticketReload->idcliente);
			} catch (\Throwable $e) {
				$this->log('Ticketcomentarios::apiAdd criaNot: ' . $e->getMessage(), 'error');
			}
		}
		$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $comentario->id);
		$u = $this->Users->findById($this->Auth->user('id'))->select(['name', 'role'])->first();
		$roleU = $u ? (int)$u->role : (int)$this->Auth->user('role');
		$out = [
			'id' => (int)$comentario->id,
			'autor' => $u ? $u->name : '',
			'papel' => $roleU === 0 ? 'tecnico' : 'cliente',
			'texto' => $comentario->comentario,
			'quando' => $comentario->created ? date('d/m/Y H:i', strtotime($comentario->created)) : '',
		];
		return $this->jsonResponse(['ok' => true, 'data' => $out]);
	}

	public function delete($id = null) {
		$entity = $this->Tickets->get($id);

		if ($this->Tickets->delete($entity)) {
			$this->Flash->success(__('Parâmetro apagado com sucesso!.'));
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
		}else $this->Flash->error(__('Não foi possível deletar o ticket.'));
	}

	public function email($idticket = null, $acao = null) {
		try {
			$sent = $this->_enviarEmailNovoComentario($idticket, $acao);
		} catch (\Throwable $e) {
			$this->log('Ticketcomentarios::email: ' . $e->getMessage(), 'error');
			$sent = false;
		}
		if (!$sent) {
			$this->Flash->error('O ticket/solicitante/cliente não possui um endereço de e-mail válido para envio do e-mail ou não há um e-mail do suporte configurado, ou o envio falhou.');
		} elseif ((int)$this->Auth->user('role') === 0) {
			$this->Flash->success('E-mail de notificação de comentário enviado com sucesso.');
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
		}
		return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);
	}

}
