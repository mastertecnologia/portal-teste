<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Mailer\Email;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class TicketcomentariosController extends AppController {
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

		$this->email($idticket, C_TicketsAcaoAddComentario);

		return $this->Ticketsmovs->save($mov);
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

	public function delete($id = null) {
		$entity = $this->Tickets->get($id);

		if ($this->Tickets->delete($entity)) {
			$this->Flash->success(__('Parâmetro apagado com sucesso!.'));
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
		}else $this->Flash->error(__('Não foi possível deletar o ticket.'));
	}

	public function email($idticket = null, $acao = null) {
		$ticket = $this->Tickets->get($idticket, ['contain' => ['Clientes' => ['fields' => ['Clientes.email']],'Users' => ['fields' => ['Users.email']]]]);
		$cliente = $this->Clientes->findById($ticket->idcliente)->first();
		$emailResponsavel = explode(';', $cliente->emailresponsavel);
		
		$idempresaAtual = (int)$this->Auth->user('idempresa');
		$empresa = $this->Empresas->get($idempresaAtual);
		if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
		else $nomeempresa = $empresa->razaosocial;

		// E-mail do destinatário (prioridade: ticket, contato vinculado ao ticket, cliente vinculado ao ticket)
		$emailDest = '';
		if($this->Auth->user('role') == 1) $emailDest = $this->Config->get(1)->emailtickets;
		else if (!empty($ticket->email)) $emailDest = $ticket->email;
		else if (!empty($ticket->user->email)) $emailDest = $ticket->user->email;
		else if (!empty($ticket->cliente->email)) $emailDest = $ticket->cliente->email;

		if (empty($emailDest)) {
			$this->Flash->error('O ticket/solicitante/cliente não possui um endereço de e-mail válido para envio do e-mail ou não há um e-mail do suporte configurado!');
			return $this->redirect(['action' => 'edit', $idticket]);
		}

		$data = @date_format($ticket->datafinalizado, 'd/m/Y');
		$assunto = AssuntoTicket($ticket->assunto);

		$message = 
			"<h3> Ticket $idticket </h3>
			<p> <b> Assunto: </b> $assunto</p>
			<p> Um novo comentário foi adicionado no seu ticket nº $idticket - $nomeempresa </p>
			<br/><strong>Verifique os tickets da sua empresa <a href='".$this->Config->get(1)->urlfora."'>clicando aqui!</a></strong>
			<br/><br />Atenciosamente,<br />$nomeempresa.
		";

		$subject = "Novo comentário no ticket nº $idticket - $nomeempresa";

		$email = new Email();
		$email->transport($idempresaAtual === (int)C_EmpresaMaster ? 'master' : 'pgm');
		$from = 'helpdesk@pgm.inf.br';
		$email->from([$from => $nomeempresa])->to($emailDest)->emailFormat('html')->subject($subject);
		
		if($email->send($message)) {
			$log = $this->Ticketslogemail->newEntity();
				$log->idticket = $idticket;
				$log->acao = $acao;
				$log->iduser = $this->Auth->user('id');
				$log->datetime = date('d/m/Y H:i:s', time());
	 		$this->Ticketslogemail->save($log);
			 
			 foreach($emailResponsavel as $regEmailResp) {
				if(!empty($regEmailResp)) {
					$email = new Email();
					$email->transport($idempresaAtual === (int)C_EmpresaMaster ? 'master' : 'pgm');
					$from = 'helpdesk@pgm.inf.br';
					$email->from([$from => $nomeempresa])->to($regEmailResp)->emailFormat('html')->subject($subject);
					$email->send($message);
				}
			}


			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
			if($this->Auth->user('role') == 0) $this->Flash->success("E-mail enviado com sucesso para '$emailDest'!");
		} else $this->Flash->success('Erro ao enviar e-mail.');
		return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);
	}

}
