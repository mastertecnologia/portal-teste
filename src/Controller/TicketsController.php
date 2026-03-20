<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Mailer\Email;
use Cake\Routing\Router;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class TicketsController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Tickets');
		$this->loadModel('Users');
		$this->loadModel('Ticketsusers');
		$this->loadModel('Ticketsanexos');
		$this->loadModel('Ticketcomentarios');
		$this->loadModel('Ticketshoras');
		$this->loadModel('Ticketsmovs');
		$this->loadModel('Notificacoes');
		$this->loadModel('Ticketsservicos');
		$this->loadModel('Ticketsmodulos');
		$this->loadModel('Ticketslogemail');
		$this->loadModel('Clientes');
		$this->loadModel('Cliservicos');
		$this->loadModel('Climodulos');
		$this->loadModel('Homologacoes');
		$this->loadModel('Servicos');
		$this->loadModel('Modulos');
		$this->loadModel('Faturas');
		$this->loadModel('Faturaparcelas');
		$this->loadModel('Cancelamento');
		$this->loadModel('Empresas');
		$this->loadModel('Empresasusers');
		$this->loadModel('Ordensservico');
		$this->loadModel('Config');
	}

	public function isAuthorized($user) {
		$action = $this->request->getParam('action');

		if ($action === 'apiIndex') {
			return (int)$user['role'] === 0;
		}
		if ($action === 'apiIndexCliente') {
			return (int)$user['role'] === 1;
		}
		if (in_array($action, ['apiView', 'apiSaveTicket', 'apiComments', 'apiAnexoUpload', 'apiAnexoDelete'], true)) {
			return in_array((int)$user['role'], [0, 1], true);
		}

		if ($user['role'] == 0 and $action != 'indexcliente') return true;
		else if ($user['role'] == 1 and in_array($action, [
			'indexcliente', 'add', 'assuntoTicket', 'view', 'cancelar', 'downloadAnexo', 'imprimir',
		])) return true;

		return false;
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

	public function criaLogEmail($idticket, $acao) {
		$log = $this->Ticketslogemail->newEntity();
		$log->idticket = $idticket;
		$log->acao = $acao;
		$log->iduser = $this->Auth->user('id');
		$log->datetime = date('d/m/Y H:i:s', time());


		return $this->Ticketslogemail->save($log);
	}

	public function criaNot($situacao, $idticket, $idcliente = null) {
		$not = $this->Notificacoes->newEntity();
		$not->titulo = 'Ticket '.$idticket;
		$not->texto = 'Ticket Aberto';
		$not->situacao = $situacao;
		$not->tipo = C_NotificacaoTipoTikcet;
		$not->idacao = $idticket;
		$not->idcliente = $idcliente;
		$not->data = date('d/m/Y');

		return $this->Notificacoes->save($not);
	}

	public function dirAnexos($idempresa = null, $idticket = null) {
		if ($idempresa === null || $idempresa === '' || $idempresa == 0) {
			$idempresa = $this->Auth->user('idempresa');
		}

		return (WWW_ROOT . 'arquivos' . DS . 'tickets' . DS . $idempresa . DS . $idticket);
	}

	/**
	 * Normaliza campo upload file-3 (um ou vários arquivos) para lista de entradas compatíveis com moveFile().
	 *
	 * @param array|null $raw Valor de $this->request->getData('file-3')
	 * @return array<int, array<string, mixed>>
	 */
	protected function _normalizeUploadFilesList($raw): array {
		if (empty($raw) || !is_array($raw)) {
			return [];
		}
		// Um arquivo: name é string
		if (isset($raw['name']) && !is_array($raw['name'])) {
			$err = (int)($raw['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($err === UPLOAD_ERR_NO_FILE || empty($raw['tmp_name'])) {
				return [];
			}

			return [$raw];
		}
		// Vários: name é array (mesmo com um único elemento)
		if (isset($raw['name']) && is_array($raw['name'])) {
			$out = [];
			foreach ($raw['name'] as $i => $name) {
				if ($name === '' || $name === null) {
					continue;
				}
				$err = (int)($raw['error'][$i] ?? UPLOAD_ERR_NO_FILE);
				if ($err === UPLOAD_ERR_NO_FILE) {
					continue;
				}
				$out[] = [
					'name' => $name,
					'type' => $raw['type'][$i] ?? '',
					'tmp_name' => $raw['tmp_name'][$i] ?? '',
					'error' => $err,
					'size' => $raw['size'][$i] ?? 0,
				];
			}

			return $out;
		}

		return [];
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
		ob_start();
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($arquivo));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($arquivo));
		ob_clean();
		readfile($arquivo);
		exit;
	}

	/**
	 * Abre no navegador (imagens/PDF) em vez de forçar download.
	 */
	protected function _sendFileInline($fullPath, $downloadName) {
		$this->autoRender = false;
		if (!is_readable($fullPath)) {
			return;
		}
		$mime = 'application/octet-stream';
		if (function_exists('mime_content_type')) {
			$m = @mime_content_type($fullPath);
			if (is_string($m) && $m !== '') {
				$mime = $m;
			}
		}
		ob_start();
		header('Content-Type: ' . $mime);
		header('Content-Disposition: inline; filename="' . str_replace('"', '', basename($downloadName)) . '"');
		header('Content-Length: ' . filesize($fullPath));
		ob_clean();
		readfile($fullPath);
		exit;
	}

	public function downloadAnexo($idanexo) {
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		if ($this->request->is('get')) {
			$anexo = $this->Ticketsanexos->get($idanexo);
			$inline = in_array((string)$this->request->getQuery('inline'), ['1', 'true', 'yes'], true);

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
				if ($inline) {
					$this->_sendFileInline($arquivo, $anexo->arquivo);
				} else {
					$this->downloadFile($arquivo);
				}
			} else {
				$this->Flash->error('O arquivo solicitado para download não foi localizado!', ['params' => ['title' => 'Erro ao fazer download do anexo']]);
				return $this->redirect($this->referer());
			}
		}
	}

	public function deleteFile($arquivo) {
		if (file_exists($arquivo)) return unlink($arquivo);
		else return -1;
	}

	public function deleteAnexo($idticket) {
		if ($this->request->is('get')) {
			// $idticket aqui é o ID do anexo
			$anexo = $this->Ticketsanexos->get($idticket);
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
					$this->criarMov($idticket, 0, C_TicketAnexoDeletado, $anexo->arquivo);
					$this->Flash->success('O anexo foi deletado com sucesso!');
					return $this->redirect(['action' => 'edit', $idticket]);
				}
			} else {
				$this->Flash->error('O arquivo solicitado para deleção não foi localizado!', ['params' => ['title' => 'Erro ao excluir o anexo']]);
				return $this->redirect(['action' => 'edit', $idticket]);
			}
		}
	}

	public function index(){
		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Listagem de Tickets');
		$this->set('reactBoot', $this->_reactBoot('tech_index', null));
	}

	public function finalizados() {
		$this->set('title', 'Tickets finalizados');
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
		}

		$empresa = $this->Auth->user('idempresa');
		$ticketsFinalizados = $this->Tickets
			->findByIdempresa($empresa)
			->contain(['Users', 'Clientes'])
			->where(['situacao IN' => [C_TicketSituacaoResolvido, C_TicketSituacaoFechado]])
			->order(['Tickets.id DESC'])
			->limit(500)
			->toArray();

		$solicitantesMap = [];
		try {
			$solicitantesIds = [];
			foreach ($ticketsFinalizados as $t) {
				if (!empty($t->idsolicitante)) $solicitantesIds[] = (int)$t->idsolicitante;
			}
			$solicitantesIds = array_values(array_unique(array_filter($solicitantesIds)));
			if (!empty($solicitantesIds)) {
				$solicitantesMap = $this->Users
					->find('list', ['keyField' => 'id', 'valueField' => 'name'])
					->where(['id IN' => $solicitantesIds])
					->toArray();
			}
		} catch (\Throwable $e) {}

		$this->set('ticketsFinalizados', $ticketsFinalizados);
		$this->set('solicitantesMap', $solicitantesMap);
	}

	/**
	 * Visualização enxuta para modal (sem menu lateral/abas).
	 */
	public function viewModal($idticket = null) {
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$role = $this->Auth->user('role');
		$admin = (bool)$this->Auth->user('admin');
		$permissaoacesso = (bool)$this->Auth->user('permissaoacesso');
		$iduser = $this->Auth->user('id');

		$this->set('title', "Ticket $idticket");
		$this->viewBuilder()->setLayout('clear');

		// Impede vazamento entre empresas: modal só pode exibir ticket da empresa atual.
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket, 'tickets.idempresa' => $idempresa])
			->first();
		if (empty($ticket)) {
			$this->autoRender = false;
			return $this->response->withStringBody('Ticket não encontrado.')->withStatus(404);
		}

		// Permissões: manter regra do view()
		if ($role == C_RoleCliente) {
			// Valida permissões usando somente a empresa atual (não empresadominante).
			$clienteBase = $this->Clientes->findById($idcliente)->first();
			$clienteVerifica = null;

			if ($clienteBase && (int)$clienteBase->idempresa !== (int)$idempresa) {
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
			} else {
				$clienteVerifica = $clienteBase;
			}

			if (empty($clienteVerifica)) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}

			if ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}
			if ($ticket->idautor != $iduser && !$permissaoacesso) {
				$this->autoRender = false;
				return $this->response->withStringBody('Sem permissão.')->withStatus(403);
			}
		}

		// Cliente
		$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo'])->first();
		$clienteNome = $cliente && $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : ($cliente->razaosocial ?? ($ticket->cliente->razaosocial ?? ''));

		// Solicitante
		$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();

		// Comentários (somente leitura no modal)
		$ticketcomentarios = $this->Ticketcomentarios->find('all', [
			'contain' => ['users'],
			'fields' => ['Users.name', 'Users.role', 'Ticketcomentarios.comentario', 'Ticketcomentarios.created']
		])->where(['Ticketcomentarios.idticket' => $idticket])->order(['Ticketcomentarios.id'])->toArray();

		// Anexos (somente leitura no modal)
		$ticketanexos = $this->Ticketsanexos->find('all')
			->where(['idticket' => $idticket])
			->toArray();

		// Movimentações e horas (somente leitura no modal)
		$ticketshoras = $this->Ticketshoras->find('all', ['contain' => 'Users'])->where(['idticket' => $idticket])->toArray();
		$ticketsmovs = $this->Ticketsmovs->find('all', ['contain' => ['users']])->where(['idticket' => $ticket->id])->order('ticketsmovs.id')->toArray();

		foreach (array_reverse($ticketsmovs) as $reg) {
			if ($reg['sitnova'] == C_TicketSituacaoFechado && $reg['sitnova'] != $reg['sitantiga']) {
				$this->set('bMovCancelada', true);
				break;
			}
		}

		$this->set('role', $role);
		$this->set('admin', $admin);
		$this->set('permissaoacesso', $permissaoacesso);
		$this->set('iduser', $iduser);

		$this->set('ticketsmovs', $ticketsmovs);
		$this->set('ticketanexos', $ticketanexos);
		$this->set('ticketshoras', $ticketshoras);
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->set('ticket', $ticket);
		$this->set('clienteNome', $clienteNome);
		if (isset($solicitante->name)) $this->set('solicitante', $solicitante->name);
	}

	public function indexcliente(){
		$assunto = $this->request->getQuery('assunto');
		$situacao = $this->request->getQuery('situacao');

		// Debug server-side opcional (para identificar por que o clique não dispara).
		// Use: /tickets/indexcliente?debug=1
		$debug = (string)$this->request->getQuery('debug');
		if ($debug === '1') {
			try {
				@file_put_contents(
					ROOT . DS . 'debug-tickets-indexcliente.log',
					date('Y-m-d H:i:s') .
						' debug=1 userId=' . (int)($this->Auth->user('id') ?? 0) .
						' idcliente=' . (int)($this->Auth->user('idcliente') ?? 0) .
						' assunto=' . (string)($assunto ?? '') .
						' situacao=' . (string)($situacao ?? '') .
						PHP_EOL,
					FILE_APPEND
				);
			} catch (\Throwable $e) {}
		}

		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', 'Tickets');
		$this->set('reactBoot', $this->_reactBoot('client_index', null, [
			'queryAssunto' => $assunto,
			'querySituacao' => $situacao,
		]));
	}

	public function meustickets(){
		$meustickets = $this->Tickets->find('all',['contain' => ['Users', 'Ticketsusers', 'Clientes' => ['fields' => ['razaosocial', 'id', 'nomefantasia']]]])
			->where(['AND' => ['idautor' => $this->Auth->user('id')], ['Ticketsusers.iduser' => $this->Auth->user('id')], ['Tickets.idempresa' => $this->Auth->user('idempresa')]])
			->distinct(['Tickets.id'])
		->toArray();
		
		$this->set('title', 'Meus Tickets');
		$this->set(compact('meustickets'));
	}

	public function empresatickets(){
		$empresatickets = [];

		$empresatickets = $this->Tickets->find('all',['contain' => ['Users', 'Ticketsusers', 'Clientes' => ['fields' => ['razaosocial', 'id', 'nomefantasia']]]])
			->where(['AND' => ['idautor' => $this->Auth->user('id')], ['Ticketsusers.iduser' => $this->Auth->user('id')], ['Tickets.idempresa' => $this->Auth->user('idempresa')]])
			->distinct(['Tickets.id'])
		->toArray();
		
		$this->set('title', 'Tickets da Empresa');
		$this->set(compact('empresatickets'));
	}

	public function add($assunto = null) { 
		$this->set('title', 'Abertura de Ticket');
		$ticket = $this->Tickets->newEntity();

		// Cliente
		if($this->Auth->user('role') == C_RoleCliente){
			$this->set('email', $this->Auth->user(['email']));

			$empresaAtual = (int)$this->Auth->user('idempresa');
			$cliente = $this->Clientes->findById($this->Auth->user('idcliente'))->order(['idempresa ASC'])->first();
			if (empty($cliente)) {
				$this->Flash->error('Cliente não encontrado para a empresa atual.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			// Seleciona o cadastro do cliente dentro da empresa atual.
			if($cliente->tipo == C_ClientesTipoJuridica) {
				$clienteAtual = $this->Clientes
					->findByCnpj(removeCaracteres($cliente->cnpj))
					->where(['idempresa' => $empresaAtual])
					->first();
			} else {
				$clienteAtual = $this->Clientes
					->findByCpf(removeCaracteres($cliente->cpf))
					->where(['idempresa' => $empresaAtual])
					->first();
			}

			if (!empty($clienteAtual)) {
				$ticket->idempresa = $empresaAtual;
				$ticket->idcliente = $clienteAtual->id;
			} else {
				$this->Flash->error('Não existe cadastro do cliente na empresa atual para abrir o ticket.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		}

		if ($this->request->is('post')) {
			$post = $this->request->getData();
			$anexos = [];
			if (!empty($post['file-3'])) {
				$anexos = $this->_normalizeUploadFilesList($post['file-3']);
			}
			unset($post['file-3']);

			// Caso não tenha email preenchido
			if ($this->Auth->user('role') == 1 && ($post['email'] ?? '') === '' && isset($clientequetememail->email)) {
				$ticket->email = $clientequetememail->email;
			}

			$ticket = $this->Tickets->patchEntity($ticket, $post);
			$ticket->idautor = $this->Auth->user('id');
			$ticket->situacao = 0;
			$ticket->resolvido = 0;
			// Garante idempresa correto (empresa atual no dropdown).
			$ticket->idempresa = $this->Auth->user('idempresa');

			// Para cliente, também garante idcliente correto (CPF/CNPJ dentro da empresa atual).
			if($this->Auth->user('role') == C_RoleCliente) {
				$empresaAtual = (int)$this->Auth->user('idempresa');
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();

				$clienteAtual = null;
				if (!empty($clienteBase)) {
					if ($clienteBase->tipo == C_ClientesTipoJuridica) {
						$clienteAtual = $this->Clientes
							->findByCnpj(removeCaracteres($clienteBase->cnpj))
							->where(['idempresa' => $empresaAtual])
							->first();
					} else {
						$clienteAtual = $this->Clientes
							->findByCpf(removeCaracteres($clienteBase->cpf))
							->where(['idempresa' => $empresaAtual])
							->first();
					}
				}

				if (empty($clienteAtual)) {
					$this->Flash->error('Não existe cadastro do cliente na empresa atual para abrir o ticket.');
					return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
				}

				$ticket->idcliente = $clienteAtual->id;
			}
		
			if ($this->Tickets->save($ticket)) {
				// Anexos (vários arquivos: input multiple ou lista normalizada)
					foreach ($anexos as $file) {
						$idempresa = $this->Auth->user('idempresa');
						$ret = $this->moveFile($file, $idempresa, $ticket->id);
						if ($ret != 1) $this->Flash->error('Ocorreu um erro ao enviar o arquivo "' . $file['name'] . '"! Tente novamente mais tarde.');
						else {
							if (!empty($file['name'])) {
								$anexo = $this->Ticketsanexos->newEntity();
								$anexo->arquivo = $file['name'];
								$anexo->idticket = $ticket->id;
								$anexo->idempresa = $ticket->idempresa;
								if (!$this->Ticketsanexos->save($anexo)) {
									$this->Flash->error('Ocorreu um erro ao salvar o anexo "' . $file['name'] . '"! Tente novamente mais tarde.');
								}
							}
						}
					}
				// Mov
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ticket->id;
					$mov->sitantiga = 0;
					$mov->sitnova = 0;
					$mov->idusuario = $this->Auth->user('id');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$this->Ticketsmovs->save($mov);
				// E-mail (cliente): notificar suporte — chamar a Table diretamente.
				// NÃO usar $this->email() aqui: o request ainda é POST do formulário "add" e o action email()
				// interpretaria como envio manual (para/sugestoes vazios), gerava Flash de erro e descartava o redirect.
					$emailSuporteOk = true;
					if ($this->Auth->user('role') == C_RoleCliente) {
						$emailSuporteOk = false;
						try {
							$sent = $this->Tickets->email(
								$ticket->id,
								C_TicketCriado,
								null,
								$this->Auth->user('idempresa')
							);
							$emailSuporteOk = !empty($sent);
							if (!$emailSuporteOk) {
								$this->log('[Tickets::add] Falha ao enviar e-mail de ticket criado (cliente).', 'warning');
							}
						} catch (\Throwable $e) {
							$this->log('[Tickets::add] Exceção ao enviar e-mail de ticket criado: ' . $e->getMessage(), 'error');
							$emailSuporteOk = false;
						}
					}
				// Not
					$this->criaNot($ticket->situacao, $ticket->id, $ticket->idcliente);
				// 

				if ($this->Auth->user('role') == C_RoleCliente && !$emailSuporteOk) {
					$this->Flash->warning(__(
						'O Ticket nº ' . $ticket->id . ' foi aberto com sucesso. A notificação por e-mail ao suporte não foi enviada — confira o campo "e-mail de tickets" (formato válido), senha MAIL_MASTER_PASSWORD ou MAIL_PGM_PASSWORD no servidor e, na porta 587, TLS (MAIL_*_TLS). Detalhes em logs/error.log.'
					));
				} else {
					$this->Flash->success(__("O Ticket nº $ticket->id foi aberto com sucesso"));
				}
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ticket->id);
				if ($this->Auth->user('role') == C_RoleCliente) return $this->redirect(['action' => 'view', $ticket->id]);
				else return $this->redirect(['action' => 'edit', $ticket->id]);
			}
			$this->Flash->error(__('Não foi possível enviar o ticket.'));
		}

		$clientesFis = $this->Clientes->find('all')
			->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'inativo' => '0', 'tipo' => '1']])
			->order(['nome'])
		->toArray();
		$clientesJur = $this->Clientes->find('all')
			->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'inativo' => '0', 'tipo' => '2']])
			->order(['razaosocial'])
		->toArray();
		
		$clientesList = [];
		foreach($clientesJur as $reg){
		$clientesList[$reg->id] = $reg->razaosocial;}
		foreach($clientesFis as $reg){
		$clientesList[$reg->id] = $reg->nome;}

		
		$this->set('assunto', $assunto);
		$this->set('clientes', $clientesList);
		$this->set('authUserName', (string)($this->Auth->user('name') ?? ''));
		$this->set(compact('ticket'));
	}

	public function edit($idticket = null){
		// Meus tickets 
			$meustickets = $this->Ticketsusers->find('all', [ 'contain' => ['Tickets']])->where(['Ticketsusers.idempresa' => $this->Auth->user('idempresa')])->toArray();
			$ticketsusers = $this->Ticketsusers->find('all', ['contain' => ['users'], 'fields' => ['Users.name', 'Users.id', 'Ticketsusers.id']])->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->autoFields(true)->toArray();
		// Permissões 
			if ($this->Auth->user('role') == 1) {
				$this->Flash->error('Você não possui permissões para visualizar esta página.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Ticket 
			$ticket = $this->Tickets->findById($idticket)->contain(['users'])->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Cliente e Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
			$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo', 'idempresa'])->first();
			$clienteNome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		// Comentarios 
			$ticketcomentarios = $this->Ticketcomentarios->find('all', [
				'contain' => ['users', 'Tickets'],
				'fields' => ['Users.name', 'Users.role', 'Ticketcomentarios.comentario', 'Ticketcomentarios.created', 'Tickets.idempresa']
			])->where(['Ticketcomentarios.idticket' => $idticket, 'Tickets.idempresa' => $this->Auth->user('idempresa')])->order(['Ticketcomentarios.id'])->toArray();
		// Anexos 
			$ticketanexos = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->toArray();
		// Movs e horas 
			$ticketsmovs = $this->Ticketsmovs->find('all', ['contain' => ['users']])->where(['idticket' => $ticket->id])->order('ticketsmovs.id')->toArray();
			$ticketshoras = $this->Ticketshoras->find('all', ['contain' => 'Users'])->where(['idticket' => $idticket])->toArray();
		// Users 
			$users = $this->Users->find('list', ['keyField' => 'id', 'valueField' => 'name'])->order(['name'])->where(['role' => 0,'inativo' => 0,])->toArray();
			//verifica se o usuário já tá no ticket pra não add ele de novo
			foreach($users as $key => $user) foreach($ticketsusers as $jatanoticket) if($key == $jatanoticket->Users['id']) unset($users[$key]);
		// 

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$this->Tickets->patchEntity($ticket, $data);
			if ($this->Tickets->save($ticket)) {
				$this->Flash->success('A descrição foi salva com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o ticket.');
		}

		$ordem = $this->Ordensservico->findByIdticket($idticket)->first();
		$ordem = empty($ordem) ? false : $ordem->id;

		$timerAtivo = null;
		$timerPausado = false;
		try {
			$this->loadModel('AtendimentoTimer');
			$timerAtivo = $this->AtendimentoTimer->find()
				->where([
					'idticket' => $idticket,
					'iduser' => $this->Auth->user('id'),
					'hora_fim IS' => null,
				])
				->orderDesc('id')
				->first();
			if ($timerAtivo) {
				$horaPausa = $timerAtivo->get('hora_pausa');
				$timerPausado = !empty($horaPausa);
			}
		} catch (\Throwable $e) {
			// Tabela pode não existir; view mostra só Iniciar ou esconde o bloco
		}

		$timerPausadoElapsedTexto = null;
		if ($timerAtivo && $timerPausado) {
			$hi = $timerAtivo->get('hora_inicio');
			$hp = $timerAtivo->get('hora_pausa');
			if ($hi && $hp) {
				$tIni = is_object($hi) && method_exists($hi, 'getTimestamp') ? $hi->getTimestamp() : strtotime($hi);
				$tPausa = is_object($hp) && method_exists($hp, 'getTimestamp') ? $hp->getTimestamp() : strtotime($hp);
				$segundos = max(0, (int)($tPausa - $tIni));
				$h = (int)floor($segundos / 3600);
				$m = (int)floor(($segundos % 3600) / 60);
				$s = $segundos % 60;
				$timerPausadoElapsedTexto = sprintf('%02d:%02d:%02d', $h, $m, $s);
			}
		}

		$minutosTicket = 0;
		$minutosClienteMes = 0;
		$horasContratoTexto = null;
		$saldoContratoMinutos = null;
		try {
			$inicioMes = (new \DateTime('first day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$fimMes = (new \DateTime('last day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$minutosTicket = $this->Ticketshoras->minutosTicket($idticket, '2000-01-01', '2099-12-31');
			$minutosClienteMes = $this->Ticketshoras->minutosCliente($ticket->idcliente, $inicioMes, $fimMes);
		} catch (\Throwable $e) {}
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$contrato = $table->find()->where(['idcliente' => $ticket->idcliente, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$contrato) {
				$contrato = $table->find()->where(['idcliente' => $ticket->idcliente])->first();
			}
			if ($contrato) {
				// Formato do módulo em produção: horas_contratadas e saldo (em horas, decimal) ou horas_consumidas
				if ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('horas_consumidas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$hConsumidas = (float)str_replace(',', '.', $contrato->get('horas_consumidas'));
					$saldoH = max(0, $hContratadas - $hConsumidas);
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format($saldoH, 2, ',', '.') . ' h';
				} elseif ($contrato->get('minutos_contratados') !== null && $contrato->get('minutos_consumidos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('minutos_contratados') - (int)$contrato->get('minutos_consumidos');
					$horasContratoTexto = number_format((int)$contrato->get('minutos_contratados') / 60, 1, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoContratoMinutos) / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('saldo_minutos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('saldo_minutos');
					$horasContratoTexto = 'Saldo: ' . number_format($saldoContratoMinutos / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas';
				} elseif ($contrato->get('saldo') !== null) {
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = 'Saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				}
			}
		} catch (\Throwable $e) {}

		$this->set('title', "Ticket $idticket" );
		$this->set('users', $users);
		$this->set('ticketsmovs', $ticketsmovs);
		$this->set('ticketsusers', $ticketsusers);
		$this->set('ticketanexos', $ticketanexos);
		$this->set('ticketcomentarios', $ticketcomentarios);
		$this->set('ticket', $ticket);
		$this->set('ticketshoras', $ticketshoras);
		$this->set('ordem', $ordem);
		$this->set('timerAtivo', $timerAtivo);
		$this->set('timerPausado', $timerPausado);
		$this->set('timerPausadoElapsedTexto', $timerPausadoElapsedTexto);
		$this->set('minutosTicket', $minutosTicket);
		$this->set('minutosClienteMes', $minutosClienteMes);
		$this->set('horasContratoTexto', $horasContratoTexto);

		$this->set('cliente', $clienteNome);
		@$this->set('solicitante', $solicitante->name);

		if (!$this->request->is(['post', 'put']) && $this->request->getQuery('classic') !== '1') {
			$this->viewBuilder()->setLayout('default');
			$this->viewBuilder()->setTemplate('react_app');
			// Evita tarja duplicada: o React já mostra "Ticket #…" no conteúdo.
			$this->set('hideLayoutPageTitle', true);
			$this->set('reactBoot', $this->_reactBoot('tech_edit', (int)$idticket, [
				'classicEditUrl' => Router::url(['action' => 'edit', $idticket, '?' => ['classic' => '1']]),
			]));
		}
	}

	public function view($idticket = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		// Ticket 
			// Impede vazamento entre empresas.
			$ticket = $this->Tickets->find('all',['contain' => ['Clientes', 'Users']])
				->where(['tickets.id' => $idticket, 'tickets.idempresa' => $idempresa])
				->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		// Permissões 
			if(empty($idticket)) {
				$this->Flash->error('Selecione um ticket para editar.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			if ($this->Auth->user('role') == C_RoleCliente) {
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();
				$clienteVerifica = null;

				if (!empty($clienteBase)) {
					if($clienteBase->tipo == C_ClientesTipoJuridica) {
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
				}

				if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
					$this->Flash->error('Você não possui permissão para visualizar este ticket.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if ($this->Auth->user('role') == C_RoleCliente && !$this->Auth->user('permissaoacesso')
				&& (int)$ticket->idautor !== (int)$this->Auth->user('id')
				&& (int)$ticket->idcliente !== (int)$this->Auth->user('idcliente')) {
				$this->Flash->error('Você não possui permissão para visualizar este ticket.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		// Comentar 
			$bComentar = false;

			if($this->Auth->user('admin') != 1){
				// Verifica se é um ticket sem outros funcionários, pois nesses casos, o ticket não entra em na consulta retornada de 'Ticketsusers'
				$meuticket = $this->Tickets->findById($idticket)->toArray();
				if ($this->Auth->user('id') != $meuticket[0]->idautor && $this->Auth->user('idcliente') != $meuticket[0]->idcliente) {
					$this->Flash->error('Você não possui permissões para visualizar este Ticket. Contate um administrador do sistema.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				} else $bComentar = true;
			}

		$this->viewBuilder()->setLayout('default');
		$this->viewBuilder()->setTemplate('react_app');
		$this->set('title', "Ticket $idticket");
		$this->set('hideLayoutPageTitle', true);
		$this->set('reactBoot', $this->_reactBoot('client_view', (int)$idticket));
	}

	public function imprimir($idticket = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$this->viewBuilder()->setLayout('print');
		
		// Ticket 
			// Impede vazamento entre empresas.
			$ticket = $this->Tickets->find('all',['contain' => ['Clientes', 'Users']])
				->where(['tickets.id' => $idticket, 'tickets.idempresa' => $idempresa])
				->first();
			if(empty($ticket)) {
				$this->Flash->error('Não foi encontrado um ticket com o Id informado na Empresa selecionada.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}
		// Solicitante 
			$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		// Permissões 
			if(empty($idticket)) {
				$this->Flash->error('Selecione um ticket para editar.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			if ($this->Auth->user('role') == C_RoleCliente) {
				$clienteBase = $this->Clientes->findById($this->Auth->user('idcliente'))->first();
				$clienteVerifica = null;

				if (!empty($clienteBase)) {
					if($clienteBase->tipo == C_ClientesTipoJuridica) {
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
				}

				if (empty($clienteVerifica) || ($clienteVerifica->cpf != $clienteBase->cpf && $clienteBase->cnpj != $clienteVerifica->cnpj)) {
					$this->Flash->error('Você não possui permissão para visualizar este ticket.');
					return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
				}
			}

			if ($this->Auth->user('role') == C_RoleCliente && !$this->Auth->user('permissaoacesso')
				&& (int)$ticket->idautor !== (int)$this->Auth->user('id')
				&& (int)$ticket->idcliente !== (int)$this->Auth->user('idcliente')) {
				$this->Flash->error('Você não possui permissão para visualizar este ticket.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
				// Cliente 
			$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo'])->first();
		// 

		
		$clienteNome = $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : $cliente->razaosocial;
		
		if(isset($solicitante->name)) $this->set('solicitante', $solicitante->name);
		$this->set('cliente', $clienteNome);
		$this->set('ticket', $ticket);
		$this->set('title', "Ticket $idticket" );
	}

	public function cancelar($idticket = null){
		$ticket = $this->Tickets->get($idticket);

		if($ticket->idautor != $this->Auth->user('id') && !$this->Auth->user('admin') && ($this->Auth->user('role') == 1 && !$this->Auth->user('permissaoacesso'))) {
			$this->Flash->error('Você não possui permissões para cancelar este Ticket. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$observacao = "";

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if (isset($data['observacao'])) $observacao = $data['observacao'];

			$sitantiga = $ticket->situacao;
			$ticket->situacao = C_TicketSituacaoFechado;

			if ($this->Tickets->save($ticket)) {
				$this->criarMov($idticket, $sitantiga, C_TicketSituacaoFechado, $observacao);
				$this->Flash->success("Ticket cancelado.");
			} else $this->Flash->error("Erro ao cancelar Ticket.");

			return $this->redirect(['action' => $this->Auth->user('role') == 0 ? 'edit' : 'view', $idticket]);
		}
		$this->set('title', 'Ticket ' . $idticket);
		$this->set('ticket', $ticket);
	}

	public function reabrir($idticket = null){
		$ticket = $this->Tickets->get($idticket);

		$observacao = "";

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if (isset($data['observacao'])) $observacao = $data['observacao'];

			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoPendente;

			if ($this->Tickets->save($ticket)) {
				//Cria a movimentação.
				$this->criarMov($idticket, $sitantiga, C_TicketSituacaoPendente, $observacao);
				$this->Flash->success("Ticket Reaberto.");

				return $this->redirect(['action' => 'edit', $idticket]);
			} else {
				$this->Flash->error("Erro ao reabrir Ticket.");
				return $this->redirect(['action' => 'edit', $idticket]);
			}
		}
		$this->set('title', 'Ticket ' . $idticket);
		$this->set('ticket', $ticket);
	}

	public function cadhoras($idticket = null) {
		$this->set('title', 'Ticket ' . $idticket);

		$horas = $this->Ticketshoras->horasTicket($idticket);

		$this->set('horas', $horas);
		$this->set('idticket', $idticket);
	}

	public function delete($id = null) {
		//Verifica permissões
		if (!$this->Auth->user('admin')){
			$this->Flash->error('Você não possui permissões para deletar este Ticket. Contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$entity = $this->Tickets->get($id);

		if ($this->Tickets->delete($entity)) {
			$this->Flash->success(__('Ticket apagado com sucesso!'));
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
		}else $this->Flash->error(__('Não foi possível deletar o ticket.'));
	}

	public function alterarsituacao($idticket = null, $sit = null) {
		$ticket = $this->Tickets->find('all')->where(['AND' => ['id' => $idticket], ['idempresa' => $this->Auth->user('idempresa')]])->first();
		if (!$ticket) {
			$this->Flash->error('Ticket não encontrado.');
			return $this->redirect(['action' => 'index']);
		}
		$situacao = $sit;
		$sitantiga = $ticket->situacao;
		$ticket->situacao = $situacao;

		if ($situacao == C_TicketSituacaoResolvido || $situacao == C_TicketSituacaoFechado) $ticket->datafinalizado = date('d/m/Y');

		if ($this->Tickets->save($ticket)) {
			try {
				$this->criarMov($ticket->id, $sitantiga, $ticket->situacao);
			} catch (\Throwable $e) {
				$this->log('Tickets::alterarsituacao criarMov: ' . $e->getMessage(), 'error');
			}
			$this->Flash->success("Situação do ticket alterada.");
			try {
				if ($situacao == C_TicketSituacaoPendente && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoPendente, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoEmandamento && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoEmandamento, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoFechado && $situacao != $sitantiga) $this->email($idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
				else if ($situacao == C_TicketSituacaoResolvido && $situacao != $sitantiga) $this->email($idticket, null, null, $this->Auth->user('idempresa'));
			} catch (\Throwable $e) {
				$this->log('Tickets::alterarsituacao email: ' . $e->getMessage(), 'error');
			}
			if ($this->request->getHeaderLine('HX-Request')) {
				return $this->redirect(['controller' => 'Tickets', 'action' => 'panelLeftFragment', $idticket]);
			}
			if (in_array($ticket->situacao, [C_TicketSituacaoPendente, C_TicketSituacaoResolvido])) return $this->redirect(['controller' => 'Tickets', 'action' => 'index']);
			return $this->redirect(['controller' => 'Tickets', 'action' => 'edit', $idticket]);
		}
	}

	/**
	 * Retorna apenas o HTML do painel esquerdo do ticket (para HTMX swap).
	 * GET; requer autenticação.
	 */
	public function panelLeftFragment($idticket = null) {
		if (!$idticket) return $this->redirect(['action' => 'index']);
		$ticket = $this->Tickets->findById($idticket)->contain(['users'])->first();
		if (!$ticket || $ticket->idempresa != $this->Auth->user('idempresa')) {
			return $this->response->withStatus(404);
		}
		$this->_setEditPanelLeftVars($idticket);
		$this->viewBuilder()->setLayout('ajax');
		$this->viewBuilder()->setTemplate('edit_panel_left');
	}

	public function viewhomologacoes($idticket) {
		$homologacao = $this->Homologacoes->findByIdticket($idticket)->toArray();

		$this->set('homologacao', $homologacao[0]);
	}

	public function poderesolver($idchamado){
		$this->autoRender = false;
		$this->viewBuilder()->setLayout('ajax');

		$user = $this->Users->get($this->Auth->user('id'));

		if($idchamado == $user->ticketemand) echo 'poderesolver';
		else if( $user->ticketemand != '' && $user->ticketemand != null ) echo $user->ticketemand;
		else echo 'poderesolver';
	}

	public function faturas($idcliente = null) {
		// Lista de serviços
		$optServicos = $this->Servicos->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('optServicos', $optServicos);
		// Lista de módulos
		$optModulos = $this->Modulos->find('list', ['keyField' => 'id', 'valueField' => 'nome'])->order(['nome'])->toArray();
		$this->set('optModulos', $optModulos);

		if($idcliente == 61) $this->set('terceiros', 'terceiros');
		else $this->set('terceiros', 'naotemterceiros');
	}

	public function viewfaturas($idticket) {
		$faturas = $this->Faturas->findByIdticket($idticket)->toArray();

		$parcelas = $this->Faturaparcelas->findByIdfatura($faturas[0]->id) ->order([ 'Faturaparcelas.id' => 'ASC' ])->toArray();

		$ticketsservicos = $this->Ticketsservicos->findByIdticket($idticket)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->toArray();
		$ticketsmodulos = $this->Ticketsmodulos->findByIdticket($idticket)->contain(['Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->toArray();

		$this->set('fatura', $faturas[0]);
		$this->set('parcelas', $parcelas);
		$this->set('ticketsservicos', $ticketsservicos);
		$this->set('ticketsmodulos', $ticketsmodulos);
	}

	public function cancelamento($idcliente) {
		$cliservicos = $this->Cliservicos->findByIdcliente($idcliente)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->toArray();
		$climodulos = $this->Climodulos->findByIdcliente($idcliente)->contain(['Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->toArray();
		$mensalidade = $this->Clientes->get($idcliente)->valormensalidade;

		$this->set('mensalidade', $mensalidade);
		$this->set('cliservicos', $cliservicos);
		$this->set('climodulos', $climodulos);
	}

	public function cancelamentoview($idticket) {
		$ticket = $this->Tickets->get($idticket);
		$servicos = $this->Cancelamento->findByIdticket($idticket)->contain(['Servicos' => ['fields' => ['Servicos.id', 'Servicos.nome']]])->where(['idservico is not' => null,])->toArray();
		$modulos = $this->Cancelamento->findByIdticket($idticket)->contain([ 'Modulos' => ['fields' => ['Modulos.id', 'Modulos.nome']]])->where(['idmodulo is not' => null,])->toArray();
		$mensalidadefinal = $this->Cancelamento->findByIdticket($idticket)->where(['valormensalidade is not' => null,])->first();
		$mensalidade = $this->Clientes->get($ticket->idcliente)->valormensalidade;

		$this->set('mensalidade', $mensalidade);
		if(!empty($mensalidadefinal)) $this->set('mensalidadefinal', $mensalidadefinal->valorfinalmensalidade);
		$this->set('cliservicos', $servicos);
		$this->set('climodulos', $modulos);
	}

	public function checkboxesParcelas($idparcela, $marcou){
		$this->autoRender = false;

		$parcela = $this->Faturaparcelas->get($idparcela, 
			['contain' => [
				'Faturas' => ['fields' => ['Faturas.id', 'Faturas.idticket', 'Faturas.tipopagamento']],
				'Faturas.Tickets' => ['fields' => ['Faturas.id', 'Faturas.idticket', 'Tickets.id', 'Tickets.idcliente']],
		]]);		
		
		$parcela->faturado = $marcou;
		$this->Faturaparcelas->save($parcela);

		$parcelasdessafaturapraversemarcoutodas = $this->Faturaparcelas->findByIdfatura($parcela->idfatura);		
		$resolvido = 'sim';

		foreach($parcelasdessafaturapraversemarcoutodas as $reg){
			echo $reg->faturado;
			if($reg->faturado != 1) $resolvido = 'nao';
		}


		if($parcela->ticket->situacao == C_TicketSituacaoPendente && $marcou == 1 && $resolvido == 'nao'){
			$ticket = $this->Tickets->get($parcela->fatura->idticket);
			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoEmandamento;
			$ticket->datafinalizado = date('d/m/Y');

			if ($this->Tickets->save($ticket)) {
				//Cria a movimentação.
				$this->criarMov($ticket->id, $sitantiga, C_TicketSituacaoEmandamento, '');
			}
		}


		if($resolvido == 'sim'){
			$ticket = $this->Tickets->get($parcela->fatura->idticket);
			$sitantiga = $ticket->situacao;

			$ticket->situacao = C_TicketSituacaoResolvido;
			$ticket->datafinalizado = date('d/m/Y');

			if ($this->Tickets->save($ticket)) {
				// Desmarca nos user q tavam fazendo ele
				$users = $this->Users->findByTicketemand($ticket->id)->toArray();
				foreach($users as $reg){
					$user = $this->Users->get($reg->id);
					$user->ticketemand = null;
					$this->Users->save($user);
				}
				//Cria a movimentação.
				$this->criarMov($ticket->id, $sitantiga, C_TicketSituacaoResolvido, '');
			}
		}
	}

	public function mudasituacaofatura($idfatura, $situacao){
		$this->autoRender = false;

		$fatura = $this->Faturas->get($idfatura);		
		$fatura->situacao = $situacao;
		$this->Faturas->save($fatura);
	}

	public function email($idticket, $situacao = null, $redirect = null) {
		if($situacao == 'redirect') {
			$situacao = null;
			$redirect = 'redirect';
		}

		// GET: exibe tela para selecionar/digitar destinatário (sem envio automático)
		if (!$this->request->is(['post', 'put'])) {
			$this->set('title', "Enviar e-mail - Ticket $idticket");

			$ticket = $this->Tickets->find()
				->contain(['Users', 'Clientes'])
				->where(['Tickets.id' => $idticket, 'Tickets.idempresa' => $this->Auth->user('idempresa')])
				->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['controller' => 'Users', 'action' => 'dashboard']);
			}

			$cliente = null;
			try {
				// prioridade: usar associação que já vem no contain()
				$cliente = $ticket->cliente ?? null;
				if (!is_object($cliente)) {
					// fallback: carregar do banco pelo id
					$this->loadModel('Clientes');
					$cliente = $this->Clientes->findById($ticket->idcliente)->first();
				}
			} catch (\Throwable $e) {}

			// Extrai e-mails de forma consistente (mesma ideia do parseEmailList em TicketsTable).
			$parseEmailList = function ($value) {
				$value = (string)$value;
				if (trim($value) === '') return [];

				$value = str_replace(["\r", "\n", "\t"], ' ', $value);
				$parts = preg_split('/[;,\\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY);

				$out = [];
				foreach ($parts as $p) {
					$p = trim((string)$p);
					if ($p === '') continue;
					if (filter_var($p, FILTER_VALIDATE_EMAIL)) $out[] = $p;
				}

				// Fallback: caso o conteúdo venha com algum formato inesperado,
				// ainda assim tenta listar tokens que parecem conter e-mail.
				if (empty($out)) {
					foreach ($parts as $p) {
						$p = trim((string)$p);
						if ($p === '') continue;
						if (strpos($p, '@') !== false) $out[] = $p;
					}
				}

				return array_values(array_unique($out));
			};

			$sugestoes = [];

			// Sugestões devem ser SOMENTE dos e-mails cadastrados no cliente
			// monta a partir do objeto de Cliente disponível (contain ou findById)
			if (is_object($cliente)) {
				$sugestoes = array_merge(
					$sugestoes,
					$parseEmailList($cliente->email ?? ''),
					$parseEmailList($cliente->emailresponsavel ?? '')
				);
			}

			// Inclui também os e-mails cadastrados no cliente na guia "Usuários"
			// (Users.email vinculados ao idcliente do ticket).
			$idclienteDoTicket = (int)($ticket->idcliente ?? 0);
			if (!empty($idclienteDoTicket)) {
				try {
					$usuariosQueryAtivos = $this->Users
						->find()
						->select(['email' => 'Users.email'])
						->where(['Users.idcliente' => $idclienteDoTicket, 'Users.inativo' => 0]);

					$usuariosEmails = $usuariosQueryAtivos->toArray();

					// Se não houver ativos, lista também inativos (para não ficar vazio).
					if (empty($usuariosEmails)) {
						$usuariosEmails = $this->Users
							->find()
							->select(['email' => 'Users.email'])
							->where(['Users.idcliente' => $idclienteDoTicket])
							->toArray();
					}

					foreach ($usuariosEmails as $u) {
						$email = is_object($u) ? ($u->email ?? '') : ($u['email'] ?? '');
						if (trim((string)$email) === '') continue;
						$sugestoes = array_merge($sugestoes, $parseEmailList($email));
					}
				} catch (\Throwable $e) {}
			}

			// Fallback: caso o findById falhe ou venha sem emailresponsavel,
			// tenta extrair do cliente vindo via contain() do ticket.
			if (empty($sugestoes) && is_object($ticket->cliente ?? null)) {
				$sugestoes = array_merge(
					$sugestoes,
					$parseEmailList($ticket->cliente->email ?? ''),
					$parseEmailList($ticket->cliente->emailresponsavel ?? '')
				);
			}

			$sugestoes = array_values(array_unique($sugestoes));

			$this->set('ticket', $ticket);
			$this->set('sugestoes', $sugestoes);
			$this->set('defaultPara', (string)($ticket->user->email ?? $ticket->email ?? ''));
			$this->set('situacao', $situacao);
			$this->set('redirectAfter', $redirect);
			return;
		}

		// POST: envia para destinatário(s) informado(s)
		$para = (string)($this->request->getData('para') ?? '');
		$selecionados = (array)($this->request->getData('sugestoes') ?? []);
		$selecionados = array_values(array_filter(array_map('trim', $selecionados)));
		$emailInput = trim($para . ';' . implode(';', $selecionados));

		$emailDest = $this->Tickets->email($idticket, $situacao, $emailInput, $this->Auth->user('idempresa'));

		if(!empty($emailDest)){
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
			if($this->Auth->user('role') == 0) $this->Flash->success("E-mail enviado com sucesso para '$emailDest'!");
		} else {
			$this->Flash->error('Erro ao enviar e-mail.');
		}

		$redir = $this->request->getData('redirect') ?: $redirect;
		if(!empty($redir)) return $this->redirect(['action' => 'edit', $idticket]);
		return $this->redirect(['action' => 'finalizados']);
	}

	public function emailvarios() {
		$data = $this->request->getData();
		$idticket = $data['idticket'];
		foreach($data['email'] as $dest) $this->Tickets->email($idticket, null, $dest, $this->Auth->user('idempresa'));

		$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
		if($this->Auth->user('role') == 0) $this->Flash->success("E-mail enviado com sucesso!");
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	/**
	 * Timer (Horas Técnicas) – todas as ações com try/catch para evitar "An Internal Error Has Occurred".
	 */
	public function timerIniciar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$ativo = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, 'iduser' => $this->Auth->user('id'), 'hora_fim IS' => null])->first();
			if ($ativo) {
				$this->Flash->warning('Já existe um timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$novo = $this->AtendimentoTimer->newEntity([
				'idticket' => (int)$idticket,
				'iduser' => (int)$this->Auth->user('id'),
				'idempresa' => (int)$this->Auth->user('idempresa'),
				'hora_inicio' => $agora->format('Y-m-d H:i:s'),
			]);
			$this->AtendimentoTimer->save($novo);
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas iniciado.');
			$this->Flash->success('Timer iniciado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Na pasta do portal execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Execute verificar_atendimento_timer.bat ou veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao iniciar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerPausar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, 'iduser' => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$timer->set('hora_pausa', $agora->format('Y-m-d H:i:s'));
			$this->AtendimentoTimer->save($timer);
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerPausado, 'Timer de horas técnicas pausado.');
			$this->Flash->success('Timer pausado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao pausar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerRetomar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, 'iduser' => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$timer->set('hora_pausa', null);
			$this->AtendimentoTimer->save($timer);
			$this->criarMov($idticket, $ticket->situacao, C_TicketTimerIniciado, 'Timer de horas técnicas retomado.');
			$this->Flash->success('Timer retomado.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao retomar o timer. Verifique logs/error.log ou ative debug em app_local.php.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	public function timerFinalizar($idticket = null) {
		$this->request->allowMethod(['post']);
		if (!$idticket) {
			$this->Flash->error('Ticket não informado.');
			return $this->redirect(['action' => 'index']);
		}
		try {
			$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$ticket) {
				$this->Flash->error('Ticket não encontrado.');
				return $this->redirect(['action' => 'index']);
			}
			$this->loadModel('AtendimentoTimer');
			$timer = $this->AtendimentoTimer->find()->where(['idticket' => $idticket, 'iduser' => $this->Auth->user('id'), 'hora_fim IS' => null])->orderDesc('id')->first();
			if (!$timer) {
				$this->Flash->error('Nenhum timer em andamento para este ticket.');
				return $this->redirect(['action' => 'edit', $idticket]);
			}
			$agora = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
			$timer->set('hora_fim', $agora->format('Y-m-d H:i:s'));
			$horaInicio = $timer->get('hora_inicio') ?: $timer->get('horainicio');
			$horaFim = $timer->get('hora_fim') ?: $timer->get('horafim');
			// Normalizar para string Y-m-d H:i:s (o banco pode devolver objeto Time)
			if ($horaInicio && is_object($horaInicio) && method_exists($horaInicio, 'format')) {
				$horaInicio = $horaInicio->format('Y-m-d H:i:s');
			}
			if ($horaFim && is_object($horaFim) && method_exists($horaFim, 'format')) {
				$horaFim = $horaFim->format('Y-m-d H:i:s');
			}
			$inicio = null;
			$fim = null;
			if ($horaInicio && $horaFim) {
				$inicio = is_string($horaInicio) ? \DateTime::createFromFormat('Y-m-d H:i:s', $horaInicio) : null;
				$fim = is_string($horaFim) ? \DateTime::createFromFormat('Y-m-d H:i:s', $horaFim) : null;
			if ($inicio && $fim) {
				$duracaoSegundos = (int) ($fim->getTimestamp() - $inicio->getTimestamp());
				$duracaoMinutos = (int) round($duracaoSegundos / 60);
				$timer->set('duracao_calculada_minutos', $duracaoMinutos);
				}
			}
			$this->AtendimentoTimer->save($timer);

			// Registra as horas em Ticketshoras (Horas Cadastradas) para o ticket
			if ($inicio && $fim) {
				try {
					$regHora = $this->Ticketshoras->newEntity([
						'idticket' => (int)$idticket,
						'iduser' => (int)$this->Auth->user('id'),
						'idempresa' => (int)$this->Auth->user('idempresa'),
						'data' => $inicio->format('Y-m-d'),
						'horaini' => $inicio->format('Y-m-d H:i:s'),
						'horafin' => $fim->format('Y-m-d H:i:s'),
					]);
					$this->Ticketshoras->save($regHora);
				} catch (\Throwable $e) {
					$this->log('Timer: falha ao registrar em Ticketshoras: ' . $e->getMessage(), 'error');
				}
				$this->criarMov($idticket, $ticket->situacao, C_TicketTimerFinalizado, 'Duração: ' . $duracaoMinutos . ' min. Horas registradas em Horas Cadastradas.');
				$this->subtrairHorasContrato($ticket->idcliente, $this->Auth->user('idempresa'), $duracaoSegundos, $duracaoMinutos);
			}

			$this->Flash->success('Timer finalizado. Horas registradas. Você pode iniciar um novo timer para continuar o atendimento.');
		} catch (\Throwable $e) {
			$this->log($e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
			$msg = $e->getMessage();
			if (stripos($msg, 'does not exist') !== false || stripos($msg, 'relation') !== false || stripos($msg, 'undefined table') !== false) {
				$this->Flash->error('Tabela atendimento_timer não existe. Execute: verificar_atendimento_timer.bat ou php scripts/verificar_criar_atendimento_timer.php');
			} elseif (stripos($msg, 'column') !== false && stripos($msg, 'exist') !== false) {
				$this->Flash->error('Tabela atendimento_timer com colunas incorretas. Veja docs/CONFIRMAR_TABELA_ATENDIMENTO_TIMER.md');
			} else {
				$this->Flash->error('Erro ao finalizar o timer. Verifique logs/error.log ou ative debug em app_local.php para ver o detalhe.');
			}
			return $this->redirect(['action' => 'edit', $idticket]);
		}
		// HTMX: redirecionar para o fragmento do painel (evita render aqui e possível saída indesejada)
		if ($this->request->getHeaderLine('HX-Request')) {
			return $this->redirect(['action' => 'panelLeftFragment', $idticket]);
		}
		return $this->redirect(['action' => 'edit', $idticket]);
	}

	/**
	 * Subtrai tempo do contrato do cliente (segundos/minutos/horas conforme coluna na tabela).
	 * Ordem: segundos_consumidos → horas_consumidas → saldo → minutos_consumidos → saldo_minutos.
	 */
	protected function subtrairHorasContrato($idcliente, $idempresa, $duracaoSegundos, $duracaoMinutos = null) {
		if ($duracaoSegundos <= 0) return;
		if ($duracaoMinutos === null) $duracaoMinutos = (int) round($duracaoSegundos / 60);
		$horasUsadas = round($duracaoSegundos / 3600.0, 4);
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$contrato = $table->find()->where(['idcliente' => $idcliente, 'idempresa' => $idempresa])->first();
			if (!$contrato) $contrato = $table->find()->where(['idcliente' => $idcliente])->first();
			if (!$contrato) {
				$this->log("subtrairHorasContrato: contrato não encontrado idcliente=$idcliente idempresa=$idempresa", 'error');
				return;
			}
			$saved = false;
			if ($contrato->get('segundos_consumidos') !== null) {
				$atual = (int) $contrato->get('segundos_consumidos');
				$contrato->set('segundos_consumidos', $atual + (int) $duracaoSegundos);
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('horas_consumidas') !== null) {
				$atual = $contrato->get('horas_consumidas');
				$atual = is_string($atual) ? (float)str_replace(',', '.', $atual) : (float)$atual;
				$contrato->set('horas_consumidas', round($atual + $horasUsadas, 4));
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('saldo') !== null) {
				$saldoAtual = $contrato->get('saldo');
				$saldoAtual = is_string($saldoAtual) ? (float)str_replace(',', '.', $saldoAtual) : (float)$saldoAtual;
				$contrato->set('saldo', max(0, round($saldoAtual - $horasUsadas, 4)));
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('minutos_consumidos') !== null) {
				$contrato->set('minutos_consumidos', (int)$contrato->get('minutos_consumidos') + $duracaoMinutos);
				$table->save($contrato);
				$saved = true;
			}
			if (!$saved && $contrato->get('saldo_minutos') !== null) {
				$contrato->set('saldo_minutos', max(0, (int)$contrato->get('saldo_minutos') - $duracaoMinutos));
				$table->save($contrato);
				$saved = true;
			}
			if ($saved) {
				$this->log("subtrairHorasContrato: atualizado idcliente=$idcliente, -{$duracaoSegundos}s ({$horasUsadas}h)", 'debug');
			} else {
				$this->log("subtrairHorasContrato: nenhuma coluna editável. idcliente=$idcliente", 'error');
			}
		} catch (\Throwable $e) {
			$this->log('subtrairHorasContrato: ' . $e->getMessage() . ' (idcliente=' . $idcliente . ')', 'error');
		}
	}

	protected function _reactBoot(string $screen, $ticketId = null, array $extra = []): array {
		$w = $this->request->getAttribute('webroot');
		$base = [
			'screen' => $screen,
			'ticketId' => $ticketId !== null ? (int)$ticketId : null,
			'webroot' => $w,
			'role' => (int)$this->Auth->user('role'),
			'admin' => (int)$this->Auth->user('admin'),
			'userName' => (string)($this->Auth->user('name') ?? ''),
			'paths' => [
				'apiIndex' => $w . 'tickets/api-index',
				'apiIndexCliente' => $w . 'tickets/api-index-cliente',
				'apiView' => $w . 'tickets/api-view/',
				'apiComments' => $w . 'tickets/api-comments/',
				'apiSaveTicket' => $w . 'tickets/api-save/',
				'apiAnexoUpload' => $w . 'tickets/api-anexo-upload/',
				'apiAnexoDelete' => $w . 'tickets/api-anexo-delete/',
				'apiAddComentario' => $w . 'ticket-comentarios/api-add/',
				'indexTecnico' => Router::url(['action' => 'index']),
				'indexCliente' => Router::url(['action' => 'indexcliente']),
				'addTicket' => Router::url(['action' => 'add']),
				'dashboard' => Router::url(['controller' => 'Users', 'action' => 'dashboard']),
				'viewTicketBase' => $w . 'tickets/view/',
				'editTicketBase' => $w . 'tickets/edit/',
				'cancelarBase' => $w . 'tickets/cancelar/',
				'imprimirBase' => $w . 'tickets/imprimir/',
			],
		];

		return array_replace_recursive($base, $extra);
	}

	/**
	 * SituacaoTicket()/AssuntoTicket() no legado retornam HTML; a API JSON precisa de texto para o React.
	 */
	protected function _ticketSituacaoTexto($situacao) {
		$raw = SituacaoTicket($situacao);
		$t = trim(html_entity_decode(preg_replace('/\s+/u', ' ', strip_tags((string)$raw)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		return $t !== '' ? $t : (string)$situacao;
	}

	protected function _ticketAssuntoTexto($assunto) {
		$raw = AssuntoTicket($assunto);
		$t = trim(html_entity_decode(preg_replace('/\s+/u', ' ', strip_tags((string)$raw)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
		return $t !== '' ? $t : (string)$assunto;
	}

	/** Nome de exibição de quem abriu o ticket (idautor). */
	protected function _ticketAutorNome($reg): string {
		$name = '';
		if (!empty($reg->users)) {
			$u = $reg->users;
			if (is_object($u)) {
				$name = (string)($u->name ?? $u->username ?? '');
			} elseif (is_array($u)) {
				$name = (string)($u['name'] ?? $u['username'] ?? '');
			}
		}
		if ($name === '' && !empty($reg->user)) {
			$u = $reg->user;
			if (is_object($u)) {
				$name = (string)($u->name ?? $u->username ?? '');
			}
		}
		if ($name === '' && !empty($reg->idautor)) {
			$u = $this->Users->findById($reg->idautor)->select(['name', 'username'])->first();
			if ($u) {
				$name = trim((string)($u->name ?? ''));
				if ($name === '') {
					$name = trim((string)($u->username ?? ''));
				}
			}
		}

		return $name;
	}

	protected function _ticketRowApiTecnico($reg): array {
		$c = $reg->cliente ?? null;
		$nomeCliente = '';
		if ($c) {
			$nomeCliente = (int)$c->tipo === (int)C_ClientesTipoFisica ? (string)$c->nome : (string)$c->razaosocial;
		}
		$id = (int)$reg->id;
		$sit = (int)$reg->situacao;
		$acoes = [];
		if ($sit !== (int)C_TicketSituacaoResolvido && $sit !== (int)C_TicketSituacaoFechado) {
			if ($sit !== (int)C_TicketSituacaoPendente) {
				$acoes[] = ['key' => 'pendente', 'label' => 'Aguardando técnico', 'url' => Router::url(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoPendente])];
			}
			if ($sit !== (int)C_TicketSituacaoEmandamento) {
				$acoes[] = ['key' => 'emandamento', 'label' => 'Em execução', 'url' => Router::url(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoEmandamento])];
			}
			if ($sit !== (int)C_TicketSituacaoResolvido) {
				$acoes[] = ['key' => 'resolvido', 'label' => 'Resolvido', 'url' => Router::url(['action' => 'alterarsituacao', $id, (string)C_TicketSituacaoResolvido])];
			}
			if ($sit !== (int)C_TicketSituacaoFechado) {
				$acoes[] = ['key' => 'cancelar', 'label' => 'Cancelar', 'url' => Router::url(['action' => 'cancelar', $id])];
			}
		}
		$acoes[] = ['key' => 'imprimir', 'label' => 'Imprimir', 'url' => Router::url(['action' => 'imprimir', $id, '?' => ['autoprint' => 1]]), 'target' => '_blank'];

		return [
			'id' => $id,
			'autor' => $this->_ticketAutorNome($reg),
			'created' => $reg->created ? $reg->created->format('d/m/Y') : '',
			'assunto' => $this->_ticketAssuntoTexto($reg->assunto),
			'assuntoCode' => $reg->assunto,
			'situacao' => $sit,
			'situacaoLabel' => $this->_ticketSituacaoTexto($reg->situacao),
			'cliente' => $nomeCliente,
			'solicitacaoPreview' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 220, '…', 'UTF-8'),
			'urls' => [
				'edit' => Router::url(['action' => 'edit', $id]),
			],
			'acoes' => $acoes,
		];
	}

	protected function _ticketRowApiCliente($reg): array {
		$c = $reg->cliente ?? null;
		$nomeCliente = '';
		if ($c) {
			$nomeCliente = (int)$c->tipo === (int)C_ClientesTipoFisica ? (string)$c->nome : (string)($c->razaosocial ?? '');
		}
		$id = (int)$reg->id;
		$sit = (int)$reg->situacao;
		$acoes = [];
		if ($sit !== (int)C_TicketSituacaoResolvido && $sit !== (int)C_TicketSituacaoFechado) {
			$acoes[] = ['key' => 'cancelar', 'label' => 'Cancelar', 'url' => Router::url(['action' => 'cancelar', $id])];
		}
		$acoes[] = ['key' => 'imprimir', 'label' => 'Imprimir', 'url' => Router::url(['action' => 'imprimir', $id, '?' => ['autoprint' => 1]], true), 'target' => '_blank'];

		return [
			'id' => $id,
			'autor' => $this->_ticketAutorNome($reg),
			'created' => $reg->created ? $reg->created->format('d/m/Y') : '',
			'assunto' => $this->_ticketAssuntoTexto($reg->assunto),
			'assuntoCode' => $reg->assunto,
			'status' => $this->_ticketSituacaoTexto($reg->situacao),
			'situacao' => $sit,
			'situacaoLabel' => $this->_ticketSituacaoTexto($reg->situacao),
			'cliente' => $nomeCliente,
			'descricao' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 160, '…', 'UTF-8'),
			'solicitacaoPreview' => mb_strimwidth(strip_tags((string)($reg->solicitacao ?? '')), 0, 220, '…', 'UTF-8'),
			'urls' => [
				'view' => Router::url(['action' => 'view', $id]),
			],
			'acoes' => $acoes,
		];
	}

	protected function _apiTicketViewAllowed($ticket): bool {
		if (empty($ticket)) {
			return false;
		}
		$role = (int)$this->Auth->user('role');
		$idempresa = $this->Auth->user('idempresa');
		if ((int)$ticket->idempresa !== (int)$idempresa) {
			return false;
		}
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
			if ($this->Auth->user('permissaoacesso')) {
				return true;
			}
			if ((int)$ticket->idautor === (int)$this->Auth->user('id')) {
				return true;
			}
			if ((int)$ticket->idcliente === (int)$this->Auth->user('idcliente')) {
				return true;
			}
			return false;
		}
		// Técnico (role 0): mesmo critério do isAuthorized do edit — qualquer usuário técnico da empresa.
		if ($role === 0) {
			return true;
		}
		return false;
	}

	protected function _apiAnexoRow($a): array {
		$id = (int)$a->id;
		$base = Router::url(['action' => 'downloadAnexo', $id], true);

		return [
			'id' => $id,
			'nome' => (string)$a->arquivo,
			'url' => $base,
			'urlView' => $base . (strpos($base, '?') !== false ? '&' : '?') . 'inline=1',
		];
	}

	protected function _apiComentariosPayload($idticket): array {
		// Sem lista restrita de fields: no CakePHP, omitir FKs quebra o contain e a lista pode vir vazia.
		$rows = $this->Ticketcomentarios->find('all', [
			'contain' => ['Users', 'Tickets'],
		])->where([
			'Ticketcomentarios.idticket' => $idticket,
			'Tickets.idempresa' => $this->Auth->user('idempresa'),
		])->order(['Ticketcomentarios.id' => 'ASC'])->toArray();

		$comentarios = [];
		foreach ($rows as $c) {
			$u = $c->user ?? null;
			$autor = '';
			$roleU = 1;
			if ($u) {
				$autor = trim((string)($u->name ?? $u->username ?? ''));
				$roleU = (int)($u->role ?? 1);
			}
			if ($autor === '' && !empty($c->idautor)) {
				$uf = $this->Users->findById($c->idautor)->select(['name', 'username', 'role'])->first();
				if ($uf) {
					$autor = trim((string)($uf->name ?: $uf->username));
					$roleU = (int)$uf->role;
				}
			}
			$papel = $roleU === 0 ? 'tecnico' : 'cliente';
			if ($autor === '') {
				$autor = $papel === 'tecnico' ? 'Técnico' : 'Cliente';
			}
			$comentarios[] = [
				'id' => (int)$c->id,
				'autor' => $autor,
				'papel' => $papel,
				'texto' => (string)($c->comentario ?? ''),
				'quando' => $c->created ? $c->created->format('d/m/Y H:i') : '',
			];
		}

		return $comentarios;
	}

	public function apiComments($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket, 'tickets.idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$comentarios = $this->_apiComentariosPayload($idticket);
		$payload = [
			'ok' => true,
			'comentarios' => $comentarios,
			'status' => $this->_ticketSituacaoTexto($ticket->situacao),
			'situacao' => (int)$ticket->situacao,
		];

		return $this->response
			->withType('application/json')
			->withHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
			->withHeader('Pragma', 'no-cache')
			->withStringBody(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	protected function _apiTicketDetailPayload($ticket, $idticket): array {
		$role = (int)$this->Auth->user('role');
		$cliente = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo'])->first();
		$clienteNome = $cliente && $cliente->tipo == C_ClientesTipoFisica ? $cliente->nome : ($cliente->razaosocial ?? '');
		$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();

		$comentarios = $this->_apiComentariosPayload($idticket);

		$anexosRows = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->toArray();
		$anexos = [];
		foreach ($anexosRows as $a) {
			$anexos[] = $this->_apiAnexoRow($a);
		}

		$createdFmt = $ticket->created ? $ticket->created->format('d/m/Y H:i') : '';

		$descAtend = isset($ticket->descricao_atendimento) ? (string)$ticket->descricao_atendimento : '';

		return [
			'id' => (int)$ticket->id,
			'assunto' => $this->_ticketAssuntoTexto($ticket->assunto),
			'status' => $this->_ticketSituacaoTexto($ticket->situacao),
			'situacao' => (int)$ticket->situacao,
			'descricao' => (string)($ticket->solicitacao ?? ''),
			'descricaoAtendimento' => $descAtend,
			'prioridade' => '—',
			'responsavel' => $solicitante->name ?? '—',
			'atualizado' => $createdFmt,
			'cliente' => $clienteNome,
			'email' => $ticket->email ?? '',
			'comentarios' => $comentarios,
			'anexos' => $anexos,
			'urls' => [
				'indexCliente' => Router::url(['action' => 'indexcliente']),
				'edit' => Router::url(['action' => 'edit', $idticket]),
				'imprimir' => Router::url(['action' => 'imprimir', $idticket, '?' => ['autoprint' => 1]]),
			],
			'flags' => [
				'role' => $role,
				'canEditDescricao' => $role === 0 && (int)$this->Auth->user('admin') === 1,
				'canEditDescricaoAtendimento' => $role === 0,
			],
		];
	}

	public function apiIndex() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$empresa = $this->Auth->user('idempresa');
		$base = ['contain' => ['Users', 'Clientes'], 'order' => ['Tickets.id' => 'DESC']];
		$ticketsPendentes = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoPendente, 'Tickets.idempresa' => $empresa])->toArray();
		$ticketsEmandamento = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoEmandamento, 'Tickets.idempresa' => $empresa])->toArray();
		$ticketsResolvidos = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoResolvido, 'Tickets.idempresa' => $empresa])->toArray();
		$ticketsFechados = $this->Tickets->find('all', $base)->where(['situacao' => C_TicketSituacaoFechado, 'Tickets.idempresa' => $empresa])->toArray();
		$tickets = $this->Tickets->find('all', $base)->where(['Tickets.idempresa' => $empresa])->order(['Tickets.situacao' => 'ASC', 'Tickets.id' => 'DESC'])->toArray();

		$map = [$this, '_ticketRowApiTecnico'];
		$out = [
			'ok' => true,
			'groups' => [
				'todos' => array_map($map, $tickets),
				'pendentes' => array_map($map, $ticketsPendentes),
				'emandamento' => array_map($map, $ticketsEmandamento),
				'resolvidos' => array_map($map, $ticketsResolvidos),
				'fechados' => array_map($map, $ticketsFechados),
			],
		];
		return $this->jsonResponse($out);
	}

	public function apiIndexCliente() {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$cliente = $this->Clientes->findById($this->Auth->user('idcliente'))->order(['idempresa ASC'])->first();
		if (empty($cliente)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'cliente_not_found'], 404);
		}
		$assunto = $this->request->getQuery('assunto');
		$situacao = $this->request->getQuery('situacao');
		$fila = $this->request->getQuery('fila');

		$tickets = $this->Tickets->find('all', ['contain' => ['Users', 'Clientes']])->where([
			'Tickets.idempresa' => $this->Auth->user('idempresa'),
			'OR' => ['Clientes.cpf' => $cliente->cpf, 'Clientes.cnpj' => $cliente->cnpj],
		]);
		if ($assunto !== null && $assunto !== '') {
			$tickets = $tickets->where(['tickets.assunto' => $assunto]);
		}
		if ($fila !== null && $fila !== '') {
			switch ($fila) {
				case 'pendente':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoPendente]);
					break;
				case 'execucao':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoEmandamento]);
					break;
				case 'resolvido':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoResolvido]);
					break;
				case 'fechados':
					$tickets = $tickets->where(['tickets.situacao' => C_TicketSituacaoFechado]);
					break;
				case 'todos':
				default:
					break;
			}
		} elseif ($situacao !== null && $situacao !== '' && (int)$situacao != -1) {
			$tickets = $tickets->where(['tickets.situacao' => $situacao]);
		} elseif ($situacao != -1) {
			$tickets = $tickets->where(['tickets.situacao IN' => [C_TicketSituacaoPendente, C_TicketSituacaoEmandamento]]);
		}
		if (!$this->Auth->user('permissaoacesso')) {
			$tickets = $tickets->where(['idautor' => $this->Auth->user('id')]);
		}
		$tickets = $tickets->order(['Tickets.id' => 'DESC'])->toArray();

		$rows = array_map([$this, '_ticketRowApiCliente'], $tickets);
		return $this->jsonResponse([
			'ok' => true,
			'tickets' => $rows,
			'query' => ['assunto' => $assunto, 'situacao' => $situacao, 'fila' => $fila],
		]);
	}

	public function apiAnexoUpload($idticket = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket, 'tickets.idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$file = $this->request->getData('file');
		if (empty($file) || !is_array($file) || empty($file['tmp_name'])) {
			return $this->jsonResponse(['ok' => false, 'error' => 'no_file'], 400);
		}
		$idempresa = (int)$this->Auth->user('idempresa');
		$ret = $this->moveFile($file, $idempresa, $idticket);
		if ($ret != 1) {
			return $this->jsonResponse(['ok' => false, 'error' => 'upload_failed'], 500);
		}
		if (empty($file['name'])) {
			return $this->jsonResponse(['ok' => false, 'error' => 'no_file'], 400);
		}
		$anexo = $this->Ticketsanexos->newEntity();
		$anexo->arquivo = $file['name'];
		$anexo->idticket = $idticket;
		$anexo->idempresa = $idempresa;
		if (!$this->Ticketsanexos->save($anexo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
		}
		$this->criarMov((int)$idticket, 0, C_TicketAnexoAdicionado, $file['name']);
		$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiAnexoUpload', (int)$anexo->id);

		return $this->jsonResponse(['ok' => true, 'anexo' => $this->_apiAnexoRow($anexo)]);
	}

	public function apiAnexoDelete($idanexo = null) {
		$this->request->allowMethod(['post']);
		$this->autoRender = false;
		try {
			$anexo = $this->Ticketsanexos->get($idanexo);
		} catch (\Exception $e) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if ((int)$anexo->idempresa !== (int)$this->Auth->user('idempresa')) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$idticket = (int)$anexo->idticket;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket, 'tickets.idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket) || !$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$arquivo = $this->dirAnexos($anexo->idempresa, $anexo->idticket) . DS . $anexo->arquivo;
		if (file_exists($arquivo) && !@unlink($arquivo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'unlink_failed'], 500);
		}
		if (!$this->Ticketsanexos->delete($anexo)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'delete_failed'], 500);
		}
		$this->criarMov($idticket, 0, C_TicketAnexoDeletado, $anexo->arquivo);
		$this->Atividades->registrar($this->Auth->user('id'), 'Tickets', 'apiAnexoDelete', (int)$idanexo);
		$anexosRows = $this->Ticketsanexos->find('all')->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->toArray();
		$list = [];
		foreach ($anexosRows as $row) {
			$list[] = $this->_apiAnexoRow($row);
		}

		return $this->jsonResponse(['ok' => true, 'anexos' => $list]);
	}

	public function apiView($idticket = null) {
		$this->request->allowMethod(['get']);
		$this->autoRender = false;
		$ticket = $this->Tickets->find('all', ['contain' => ['Clientes', 'Users']])
			->where(['tickets.id' => $idticket, 'tickets.idempresa' => $this->Auth->user('idempresa')])
			->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$data = $this->_apiTicketDetailPayload($ticket, $idticket);
		$body = json_encode(['ok' => true, 'ticket' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return $this->response
			->withType('application/json')
			->withHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate')
			->withHeader('Pragma', 'no-cache')
			->withStringBody($body);
	}

	public function apiSaveTicket($idticket = null) {
		$this->request->allowMethod(['post', 'put']);
		$this->autoRender = false;
		if ((int)$this->Auth->user('role') !== 0) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$ticket = $this->Tickets->find()->where(['id' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->first();
		if (empty($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'not_found'], 404);
		}
		if (!$this->_apiTicketViewAllowed($ticket)) {
			return $this->jsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
		}
		$body = $this->request->input('json_decode', true);
		if (!is_array($body)) {
			$body = $this->request->getData();
		}

		$saveFields = [];

		if (array_key_exists('solicitacao', $body)) {
			if ((int)$this->Auth->user('admin') !== 1) {
				return $this->jsonResponse(['ok' => false, 'error' => 'only_admin_can_edit_descricao'], 403);
			}
			$ticket->solicitacao = (string)$body['solicitacao'];
			$saveFields[] = 'solicitacao';
		}

		$descAt = null;
		if (array_key_exists('descricao_atendimento', $body)) {
			$descAt = $body['descricao_atendimento'];
		} elseif (array_key_exists('descricaoAtendimento', $body)) {
			$descAt = $body['descricaoAtendimento'];
		}
		if ($descAt !== null) {
			$ticket->descricao_atendimento = (string)$descAt;
			$saveFields[] = 'descricao_atendimento';
		}

		if ($saveFields === []) {
			return $this->jsonResponse(['ok' => false, 'error' => 'missing_fields'], 400);
		}

		if ($this->Tickets->save($ticket, ['fields' => $saveFields])) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $idticket);
			return $this->jsonResponse(['ok' => true]);
		}
		return $this->jsonResponse(['ok' => false, 'error' => 'save_failed'], 500);
	}

	/**
	 * Preenche as variáveis de view necessárias para o fragmento do painel esquerdo (HTMX).
	 * Usado quando as ações do timer respondem com atualização parcial em vez de redirect.
	 */
	protected function _setEditPanelLeftVars($idticket) {
		$ticket = $this->Tickets->findById($idticket)->contain(['users'])->first();
		if (!$ticket) return;
		$ticketsusers = $this->Ticketsusers->find('all', ['contain' => ['users'], 'fields' => ['Users.name', 'Users.id', 'Ticketsusers.id']])
			->where(['idticket' => $idticket, 'idempresa' => $this->Auth->user('idempresa')])->autoFields(true)->toArray();
		$solicitante = $this->Users->findById($ticket->idsolicitante)->select(['name'])->first();
		$clienteRow = $this->Clientes->findById($ticket->idcliente)->select(['razaosocial', 'nomefantasia', 'nome', 'tipo', 'idempresa'])->first();
		$clienteNome = $clienteRow && $clienteRow->tipo == C_ClientesTipoFisica ? $clienteRow->nome : ($clienteRow ? $clienteRow->razaosocial : '');
		$ordem = $this->Ordensservico->findByIdticket($idticket)->first();
		$ordem = empty($ordem) ? false : $ordem->id;

		$timerAtivo = null;
		$timerPausado = false;
		$timerPausadoElapsedTexto = null;
		try {
			$this->loadModel('AtendimentoTimer');
			$timerAtivo = $this->AtendimentoTimer->find()
				->where(['idticket' => $idticket, 'iduser' => $this->Auth->user('id'), 'hora_fim IS' => null])
				->orderDesc('id')->first();
			if ($timerAtivo) {
				$horaPausa = $timerAtivo->get('hora_pausa');
				$timerPausado = !empty($horaPausa);
				if ($timerPausado) {
					$hi = $timerAtivo->get('hora_inicio');
					$hp = $timerAtivo->get('hora_pausa');
					if ($hi && $hp) {
						$tIni = is_object($hi) && method_exists($hi, 'getTimestamp') ? $hi->getTimestamp() : strtotime($hi);
						$tPausa = is_object($hp) && method_exists($hp, 'getTimestamp') ? $hp->getTimestamp() : strtotime($hp);
						$segundos = max(0, (int)($tPausa - $tIni));
						$timerPausadoElapsedTexto = sprintf('%02d:%02d:%02d', (int)floor($segundos / 3600), (int)floor(($segundos % 3600) / 60), $segundos % 60);
					}
				}
			}
		} catch (\Throwable $e) {}
		$minutosTicket = 0;
		$minutosClienteMes = 0;
		$horasContratoTexto = null;
		try {
			$inicioMes = (new \DateTime('first day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$fimMes = (new \DateTime('last day of this month', new \DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
			$minutosTicket = $this->Ticketshoras->minutosTicket($idticket, '2000-01-01', '2099-12-31');
			$minutosClienteMes = $this->Ticketshoras->minutosCliente($ticket->idcliente, $inicioMes, $fimMes);
		} catch (\Throwable $e) {}
		try {
			$table = \Cake\ORM\TableRegistry::getTableLocator()->get('ContratosHoras');
			$contrato = $table->find()->where(['idcliente' => $ticket->idcliente, 'idempresa' => $this->Auth->user('idempresa')])->first();
			if (!$contrato) $contrato = $table->find()->where(['idcliente' => $ticket->idcliente])->first();
			if ($contrato) {
				if ($contrato->get('horas_contratadas') !== null && $contrato->get('saldo') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$saldoH = (float)str_replace(',', '.', $contrato->get('saldo'));
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoH), 2, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null && $contrato->get('horas_consumidas') !== null) {
					$hContratadas = (float)str_replace(',', '.', $contrato->get('horas_contratadas'));
					$hConsumidas = (float)str_replace(',', '.', $contrato->get('horas_consumidas'));
					$saldoH = max(0, $hContratadas - $hConsumidas);
					$horasContratoTexto = number_format($hContratadas, 2, ',', '.') . ' h contratadas; saldo: ' . number_format($saldoH, 2, ',', '.') . ' h';
				} elseif ($contrato->get('minutos_contratados') !== null && $contrato->get('minutos_consumidos') !== null) {
					$saldoContratoMinutos = (int)$contrato->get('minutos_contratados') - (int)$contrato->get('minutos_consumidos');
					$horasContratoTexto = number_format((int)$contrato->get('minutos_contratados') / 60, 1, ',', '.') . ' h contratadas; saldo: ' . number_format(max(0, $saldoContratoMinutos) / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('saldo_minutos') !== null) {
					$horasContratoTexto = 'Saldo: ' . number_format((int)$contrato->get('saldo_minutos') / 60, 1, ',', '.') . ' h';
				} elseif ($contrato->get('horas_contratadas') !== null) {
					$horasContratoTexto = number_format((float)str_replace(',', '.', $contrato->get('horas_contratadas')), 2, ',', '.') . ' h contratadas';
				} elseif ($contrato->get('saldo') !== null) {
					$horasContratoTexto = 'Saldo: ' . number_format(max(0, (float)str_replace(',', '.', $contrato->get('saldo'))), 2, ',', '.') . ' h';
				}
			}
		} catch (\Throwable $e) {}

		$this->set(compact('ticket', 'ticketsusers', 'ordem', 'timerAtivo', 'timerPausado', 'timerPausadoElapsedTexto', 'minutosTicket', 'minutosClienteMes', 'horasContratoTexto'));
		$this->set('cliente', $clienteNome);
		$this->set('solicitante', $solicitante ? $solicitante->name : null);
	}
}
