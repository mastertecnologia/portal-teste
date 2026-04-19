<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');
require_once ROOT . DS . 'config' . DS . 'ticket_workflow_constants.php';

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class TicketsusersController extends BaseController
{
    public function initialize(): void {
        parent::initialize();
    }

	public function criarMov($idticket = null, $sitantiga = null, $sitnova = null, $observacao = null) {
		$mov = $this->Ticketsmovs->newEntity();
		$mov->idticket = $idticket;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->idusuario = $this->Auth->user('id');
        $mov->datetime = date('d/m/Y H:i:s', time());
        if($sitantiga == null) $mov->sitantiga = 0;

        if (!empty($observacao)) $mov->observacao = $observacao;
		return $this->Ticketsmovs->save($mov);
	}

	/**
	 * Grava o técnico responsável ao assumir o chamado (colunas de workflow na tabela tickets).
	 */
	protected function gravarTecnicoResponsavelAssuncao($idticket, $iduser) {
		try {
			$Tickets = TableRegistry::get('Tickets');
			$cols = $Tickets->getSchema()->columns();
			if (!in_array('idtecnico_responsavel', $cols, true)) {
				return;
			}
			$ticket = $Tickets->get($idticket);
			$ticket->idtecnico_responsavel = (int)$iduser;
			$f = $Tickets->fieldsComEspelhoResponsavel(['idtecnico_responsavel']);
			$Tickets->save($ticket, ['fields' => $f]);
		} catch (\Throwable $e) {
			$this->log('Ticketsusers::gravarTecnicoResponsavelAssuncao ' . $e->getMessage(), 'warning');
		}
	}

    public function resolver($idticket = null) {
        $ticket = $this->Tickets->get($idticket);

		if ($this->request->is('get')) {
            $tikcketsusersquejaexistem = $this->Ticketsusers->find('all')->toArray();
            $ticket = $this->Tickets->get($idticket);
			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoEmandamento;
			$this->Tickets->save($ticket);

			if(empty( $this->Ticketsusers->findByIdticket($idticket)->where(['iduser' => $this->Auth->user('id')])->first())) {
				$ticketuser = $this->Ticketsusers->newEntity();
				$ticketuser->idticket = $idticket;
				$ticketuser->iduser = $this->Auth->user('id');
				$ticketuser->idempresa = $this->Auth->user('idempresa');

				if ($this->Ticketsusers->save($ticketuser)) {
					$this->gravarTecnicoResponsavelAssuncao($idticket, $this->Auth->user('id'));
					$this->Flash->success(__('Ingressado ao ticket com sucesso!'));
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ticketuser->id);
				} else {
					$this->Flash->error(__('Não foi possível responder o ticket.'));
					return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
				}
			} else {
				$this->gravarTecnicoResponsavelAssuncao($idticket, $this->Auth->user('id'));
				$this->Flash->success(__('Ticket movido para "Em andamento" com sucesso!'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ticket->id);
			}

			try {
				$this->criarMov($ticket->id, $sitantiga, C_TicketSituacaoEmandamento, '');
			} catch (\Throwable $e) {
				$this->log('Ticketsusers::resolver criarMov: ' . $e->getMessage(), 'error');
			}

			try {
				$emailDest = $this->Tickets->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
				if (!empty($emailDest) && $this->Auth->user('role') == 0) {
					$this->Flash->success("E-mail enviado com sucesso para '$emailDest'!");
				} elseif (empty($emailDest)) {
					$this->Flash->success('Erro ao enviar e-mail.');
				}
			} catch (\Throwable $e) {
				$this->log('Ticketsusers::resolver email: ' . $e->getMessage(), 'error');
			}

			if ($this->request->getHeaderLine('HX-Request')) {
				return $this->redirect(['controller' => 'Tickets', 'action' => 'panelLeftFragment', $idticket]);
			}
			return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);
		}
    }

    public function delete($id = null) {
        $entity = $this->Ticketsusers->get($id);
        // Desmarca no user q tava fazendo ele
        $user = $this->Users->get($entity->iduser);
        $user->ticketemand = null;
        $this->Users->save($user);

		if ($this->Ticketsusers->delete($entity)) {
			$this->Flash->success(__('Usuário removido com sucesso!.'));
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $entity->idticket]);
		} else $this->Flash->error(__('Não foi possível deletar o ticket.'));
	}

}
