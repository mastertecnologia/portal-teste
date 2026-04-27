<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS . 'TicketConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';


class TicketsanexosController extends BaseController {
    public function initialize(): void {
        parent::initialize();
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->set('title', 'Licenças');
    }

    public function index() {
        $this->set('title', 'Lista de Licenças');

        $clianexos = $this->Ticketsanexos->find('all')->toArray();

        $this->set('clianexos', $clianexos);
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

		return $this->Ticketsmovs->save($mov);
	}

    public function dirAnexos($idempresa = null, $idticket = null) {
		if ($idempresa === null) {
			$idempresa = $this->Auth->user('idempresa');
		}

		return (WWW_ROOT . 'arquivos' . DS . 'tickets' . DS . $idempresa . DS . $idticket);
	}

	public function moveFile($file, $idempresa, $idticket) {
		//Ignora, se não tiver nada selecionado.
		if (!isset($file['tmp_name']) || !isset($file['name'])) {
		 return 1;
		}

		if (empty($file['name'])) {
		 return 1;
		}

		// Evita path traversal ou nomes maliciosos contendo separadores de diretório
		$nomeArquivo = (string)$file['name'];
		if (strpos($nomeArquivo, '..') !== false || strpos($nomeArquivo, '/') !== false || strpos($nomeArquivo, '\\') !== false) {
			return 0;
		}

		$diretorio = $this->dirAnexos($idempresa, $idticket);

		//  Cria a pasta caso ela não exista
		if (!file_exists($diretorio)) {
		 mkdir($diretorio, 0755, true);
		}

		$arquivo = $diretorio . DS . $nomeArquivo;

		//Move o arquivo para a pasta.
		if (move_uploaded_file($file['tmp_name'], $arquivo)) return 1;
		else return 0;
	}

	public function downloadFile($arquivo) {
		$this->autoRender = false;

		return $this->response->withFile($arquivo, [
			'download' => true,
			'name' => basename($arquivo),
		]);
	}

	public function downloadAnexo($idanexo) {
		$this->autoRender = false;

		if ($this->request->is('get')) {
			$anexo = $this->Ticketsanexos->get($idanexo);

			// Garantir que o anexo pertence à mesma empresa do usuário logado
			if ($anexo->idempresa != $this->Auth->user('idempresa')) {
				$this->Flash->error('Você não possui permissão para acessar este anexo.');
				return $this->redirect($this->referer());
			}

			// Regras adicionais para clientes
			if ($this->Auth->user('role') == C_RoleCliente) {
				$ticket = $this->Tickets->get($anexo->idticket);

				if ($ticket->idautor != $this->Auth->user('id')
					&& $ticket->idcliente != $this->Auth->user('idcliente')
					&& !$this->Auth->user('permissaoacesso')) {
					$this->Flash->error('Você não possui permissão para acessar este anexo.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			// Arquivo para download
			$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;
			if (file_exists($arquivo)) {
				return $this->downloadFile($arquivo);
			}
			$this->Flash->error('O arquivo solicitado para download não foi localizado!', ['params' => ['title' => 'Erro ao fazer download do anexo']]);

			return $this->redirect($this->referer());
		}
	}

	public function deleteFile($arquivo) {
		if (file_exists($arquivo)) return unlink($arquivo);
		else return -1;
	}

	public function deleteAnexo($idanexo) {
		if ($this->request->is('get')) {
			$anexo = $this->Ticketsanexos->get($idanexo);
			$idticket = $anexo->idticket;

			// Garantir que o anexo pertence à mesma empresa do usuário logado
			if ($anexo->idempresa != $this->Auth->user('idempresa')) {
				$this->Flash->error('Você não possui permissão para excluir este anexo.');
				return $this->redirect($this->referer());
			}

			// Regras adicionais para clientes
			if ($this->Auth->user('role') == C_RoleCliente) {
				$ticket = $this->Tickets->get($anexo->idticket);

				if ($ticket->idautor != $this->Auth->user('id')
					&& $ticket->idcliente != $this->Auth->user('idcliente')
					&& !$this->Auth->user('permissaoacesso')) {
					$this->Flash->error('Você não possui permissão para excluir este anexo.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			// Arquivo para download
			$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;

			$ret = $this->deleteFile($arquivo);

			if ($ret != 0) {
				if ($this->Ticketsanexos->delete($anexo)) {
					$this->Flash->success('O anexo foi deletado com sucesso!');

					$this->criarMov($idticket, 0, C_TicketAnexoDeletado, $arquivo);
					$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idanexo);

					return $this->redirect(['action' => 'edit', $idticket]);
				}
			} else {
				$this->Flash->error('O arquivo solicitado para deleção não foi localizado!', ['params' => ['title' => 'Erro ao excluir o anexo']]);
				return $this->redirect(['action' => 'edit', $idticket]);
			}
		}
	}

    public function add($idticket = null) {
        $entity = $this->Ticketsanexos->newEntity();
		$anexos = array();
		$idempresa = $this->Auth->user('idempresa');

        if ($this->request->is('post')) {

			$data = $this->request->getData();

            if (isset($data['file-3'])) {
				$anexos = $data['file-3'];
				unset($data['file-3']);
            }

			$entity->idticket = $idticket;
			$entity->idempresa = $idempresa;

            $entity = $this->Ticketsanexos->patchEntity($entity, $data);
			$excluir = $entity->excluir;

            foreach ($anexos as $file) {
				$ret = $this->moveFile($file, $idempresa, $idticket);
				if ($ret != 1) $this->Flash->error('Ocorreu um erro ao enviar o arquivo "' . $file['name'] . '"! Tente novamente mais tarde.');
				else {
					if (!empty($file['name'])) {
						$anexo = $this->Ticketsanexos->newEntity();
						$anexo->arquivo = $file['name'];
						$anexo->idticket = $idticket;
						$anexo->idempresa = $this->Auth->user('idempresa');
						$anexo->excluir = $excluir;

						$this->criarMov($idticket, 0, C_TicketAnexoAdicionado, $file['name']);

						if (!$this->Ticketsanexos->save($anexo)) $this->Flash->error('Ocorreu um erro ao salvar o anexo "' . $file['name'] . '"! Tente novamente mais tarde.');
					}
				}
            }
			
            $this->Flash->success(__('O arquivo foi salvo.'));
            $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $entity->id);
            if ($this->request->getHeaderLine('HX-Request')) {
                $ticketanexos = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->toArray();
                $this->set('ticketanexos', $ticketanexos);
                $this->set('admin', $this->Auth->user('role') == 0 ? 1 : 0);
                $this->viewBuilder()->setLayout('ajax');
                $this->viewBuilder()->setTemplate('partial_list');
                return;
            }
            return $this->redirect(['controller' => 'tickets', 'action' => 'edit', $idticket]);
		}
    }

    public function delete($id = null) {
  		// Verifica as permissões
  		if ($this->Auth->user('admin') !== 1) {
  			$this->Flash->error('Você não possui permissões para deletar arquivos do ticket. Contate um administrador do sistema.');
  			return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
  		}

        $entity = $this->Ticketsanexos->get($id);
		$idticket = $entity->idticket;
		$arquivo = $entity->arquivo;

        if ($this->Ticketsanexos->delete($entity)) {
            $this->Flash->success('O anexo foi deletado com sucesso!');
            $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $entity->id);
            return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);
        }
    }

}
