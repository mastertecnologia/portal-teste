<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Routing\Router;

$__pgmDir = ROOT . DS . 'vendor' . DS . 'PGMPackages' . DS;
foreach (['Utilities.php', 'UserConstants.php', 'TicketConstants.php'] as $__pgmFile) {
	$__pgmPath = $__pgmDir . $__pgmFile;
	if (is_file($__pgmPath)) {
		require_once $__pgmPath;
	}
}


class NotificacoesController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas');
		$this->loadModel('Users');
		$this->loadModel('Ticketsusers');
		$this->loadModel('Config');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
	}

	public function notificacoes() {
		$setor = $this->Auth->user('setor');
		$role = $this->Auth->user('role');
		$urlfora = $this->Config->get(1)->urlfora;

		$meustickets = $this->Ticketsusers->find('all', ['contain' => ['Tickets', 'Users'], 'fields' => ['Tickets.situacao', 'Ticketsusers.idticket', 'Ticketsusers.iduser']	])->toArray();
		$userid = $this->Auth->user('id');
		$img = $this->request->getAttribute('webroot') . 'assets/images/pgm.png';

		if ($role == 0) {
			$notificacoes = $this->Notificacoes->find('all')->toArray();
		} else {
			$notificacoes = $this->Notificacoes
				->find('all')
				->where(['idcliente' => $this->Auth->user('idcliente')])
				->toArray();
		}

		// Carrega todos os usuários das notificações em uma única query (evita N+1)
		$idsUser = array_unique(array_filter(array_map(function ($n) {
			return $n->iduser;
		}, $notificacoes)));
        $usersMap = [];
        if (!empty($idsUser)) {
            $users = $this->Users->find('all')
                ->where(['Users.id IN' => $idsUser])
                ->select(['id', 'role'])
                ->toArray();

            foreach ($users as $u) {
                $usersMap[$u->id] = $u;
            }
        }

		//notificacoes de mudança de status de um ticket
		foreach ($notificacoes as $not) {
			$bRecebe = false;
			$notUser = isset($usersMap[$not->iduser]) ? $usersMap[$not->iduser] : null;

			if ($this->Auth->user('role') == 1) {
				if ($not->tipo == C_NotificacaoTipoTikcetComentario)
					if ($this->Auth->user('idcliente') == $not->idcliente && $notUser && $notUser->role == 0) {
						$bRecebe = true;
					}
			} else {
				if ($not->tipo == C_NotificacaoTipoTikcetComentario && $notUser && $notUser->role == 1) {
					$bRecebe = true;
				}
				if ($not->tipo == C_NotificacaoTipoTikcet) {
					$bRecebe = true;
				}
			}

			if($bRecebe) {
				$tags = $not->recebidapor;
				$recebidasporArray = explode('|', $tags);
				
				if (!in_array($userid, $recebidasporArray)) {
					$__nl = \NotificacaoLink($not->tipo, $not->idacao);
					$scriptNot[] = ['texto' => $not->texto, 'img' => $img, 'titulo' => $not->titulo, 'url' => $urlfora . $__nl['controller'] . '/' . $__nl['action'] . '/' . $not->idacao];
				
					$recebidapor = $not->recebidapor.'|'.$userid;
					$query = $this->Notificacoes->query();
					$query->update()
						->set(['recebidapor' => $recebidapor])
						->where(['id' => $not->id])
						->execute();
				}
			}
		}

		if(isset($scriptNot)) $this->set('scriptNot', $scriptNot);
	}
}
