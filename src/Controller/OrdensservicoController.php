<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');
require_once (ROOT . DS . 'vendor' . DS . 'queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

use CakeSoap\Network\CakeSoap;

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/TicketConstants.php';

class OrdensservicoController extends AppController {
	public function initialize() {
        parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Areas');
		$this->loadModel('Problemas');
		$this->loadModel('Produtos');
		$this->loadModel('Itensordem');
		$this->loadModel('Ordemservicositens'); 
		$this->loadModel('Ordemmovs');
		$this->loadModel('Ordemhoras');
		$this->loadModel('Ordemparcelas');
		$this->loadModel('Empresas'); 
		$this->loadModel('Cidades'); 
		$this->loadModel('Estados'); 
		$this->loadModel('Tickets'); 
		$this->loadModel('Ticketcomentarios');
		$this->loadModel('Ticketsmovs');
	}

	public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        
        if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
            $ordem = $this->Ordensservico->get($event->_subject->request->params['pass'][0])->idcliente;
            $cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;
            
            if ($ordem != $cliente) {
                $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
                return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
            }
        }
        $this->Auth->allow(['refreshAPI', 'listAPI']);  
        if (in_array('Security', $this->components()->loaded())) {
            $this->Security->setConfig('unlockedActions', ['refreshAPI', 'listAPI']);
        }
        if (in_array('Csrf', $this->components()->loaded())) {
            if (in_array($this->request->getParam('action'), ['refreshAPI', 'listAPI'])) {
                $this->getEventManager()->off($this->Csrf);
            }
        }
    }

	public function index() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');

		$cliente = $this->request->getQuery('cliente');
		$situacao = $this->request->getQuery('situacao');
		$problema = $this->request->getQuery('problema');
		$locacao = $this->request->getQuery('locacao');

		if ($this->request->is('get')) {
			if ($this->Auth->user('role') == C_RoleCliente) {
				$ordens = $this->Ordensservico->find('all')
					->where(['Ordensservico.idempresa' => $idempresa, 'Ordensservico.idcliente' => $idcliente])
					->contain([
						'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
						'Users' => ['fields' => ['Users.id', 'Users.name']],
				]);
			} else {
				$ordens = $this->Ordensservico->find('all')
					->where(['Ordensservico.idempresa' => $idempresa])
					->contain([
						'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
						'Users' => ['fields' => ['Users.id', 'Users.name']],
				]);
			}

			if (!empty($cliente)) $ordens = $ordens->where(['Ordensservico.idcliente' => $cliente]);
			if (!empty($situacao)) $ordens = $ordens->where(['situacao' => $situacao]);
			if (!empty($problema)) $ordens = $ordens->where(['idproblema' => $problema]);
			if ($locacao != -1 && !empty($locacao)) $ordens = $ordens->where(['locacao' => $locacao]);
		}
		$ordens = $ordens->toArray();
		$problemas1 = [0 => 'Todos'];
		$clientesOpt1 = [0 => 'Todos'];
		
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		foreach($problemas as $key=>$reg) $problemas1[$key] = $reg;

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		if(sizeof($clientes) > 0){
			foreach($clientes as $reg){
				if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
				else $clientesOpt[$reg->id] = $reg->nome;
			}
			asort($clientesOpt);
			foreach($clientesOpt as $key=>$reg) $clientesOpt1[$key] = $reg;
		}

		$this->set('problema', $problema);
		$this->set('cliente', $cliente);
		$this->set('situacao', $situacao);
		$this->set('problemas', $problemas1);
		$this->set('clientes', $clientesOpt1);
		$this->set('locacao', $locacao);
		$this->set('ordens',  $ordens);
		$this->set('title', 'Ordens de Serviços');
		$this->set('hideLayoutPageTitle', true);
	}

	public function add() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$ordem = $this->Ordensservico->newEntity();

		if(empty($_SESSION['PGM_Ordem_Idcarrinhoadd'])){
			$ultimo = $this->Empresas->prxOrdem($this->Auth->user('idempresa'));
			if($ultimo == null || $ultimo == 0) {
				$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $this->Auth->user('id');
				$idcarrinho = $idempresa . 1 . $this->Auth->user('id');
			} else {
				$idcarrinho =  $idempresa . $ultimo . $this->Auth->user('id');
				$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo . $this->Auth->user('id');
			}
		}

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
				$this->Flash->error('Ocorreu um erro ao salvar a ordem de serviço. Verifique sua empresa atual e tente novamente');
				return $this->redirect(['action' => 'add']);
			}			
            $ordem = $this->Ordensservico->patchEntity($ordem, $data);
			$ordem->idempresa = $idempresa;
			$ordem->iduser = $this->Auth->user('id');
			// $ordem->situacao = C_OrdensSituacaoEmExecucao;
			$ordem->situacao = C_OrdensSituacaoAberta;
			$ordem->valortotal = $data['valortotalordem'];
			$ordem->id = $this->Empresas->incrementOrdem($this->Auth->user('idempresa'));

            if ($this->Ordensservico->save($ordem)) {
				$carrinho = $this->Ordemservicositens->newEntity();
				$idempresaCarrinho = $_SESSION['PGM_Ordem_Idcarrinhoadd'][0];
				if($idempresa == $idempresaCarrinho){
					$carrinho->iditens = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
				}  else {
						$carrinho->iditens = $idempresa . substr($_SESSION['PGM_Ordem_Idcarrinhoadd'], 1);
						$itemOrdens = $this->Itensordem->find('all')->where(['idordempk' => $_SESSION['PGM_Ordem_Idcarrinhoadd']])->toArray();

						foreach($itemOrdens as $item) {
							$item['idordempk'] = $carrinho->iditens;
							$this->Itensordem->save($item);
						}
				}

				$carrinho->idordem = $ordem->id;
				$carrinho->idempresa = $idempresa;
				$this->Ordemservicositens->save($carrinho);
				unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);

				// Movimentação
				$this->Ordensservico->criarMov($ordem->id, 1, 1, $this->Auth->user('idempresa'), $this->Auth->user('id'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi cadastrada com sucesso!'));
                return $this->redirect(['action' => 'edit', $ordem->id]);
			}
			
			// Decrementa o último id em caso de erro
			$this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
            $this->Flash->error(__('Não foi possível cadastrar a ordem de serviço.'));
        }

/* 		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}
		asort($clientesOpt); */

		$clientes = $this->Clientes->find('all')
			->where(['Clientes.idempresa' => $idempresa, 'Clientes.inativo' => 0])
			->contain(['Cidades']) 
			->order(['Clientes.razaosocial'])
			->toArray();

		$clientesOpt = [];
		foreach($clientes as $reg){
			$nomeCliente = ($reg->tipo == C_ClientesTipoJuridica) ? $reg->razaosocial : $reg->nome;
			$nomeCidade = (!empty($reg->cidade) && !empty($reg->cidade->nome)) ? $reg->cidade->nome : 'Sem Cidade';
			$clientesOpt[$reg->id] = $nomeCliente . ' (' . $nomeCidade . ')';
		}
		asort($clientesOpt);


		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();

		$produtosOpt = [];
		foreach($this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['descricao'])->toArray() as $reg)
			$produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		
		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', C_ProdutosTipo);
		$this->set('tiposOpt', json_encode(C_ProdutosTipo, JSON_PRETTY_PRINT));
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('ordem', $ordem);
		$this->set('title', 'Cadastro de ordem de serviços');
	}

	public function edit($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');

		$data = $this->request->getData();

		
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $idempresa])->first();
		
		if($this->request->is(['post', 'put']) && $data['idEmpresaAtual'] != $idempresa || empty($ordem) ) {
			$this->Flash->error('Ocorreu um erro ao editar a ordem de serviço. Verifique sua empresa atual e tente novamente');
			return $this->redirect(['action' => 'index']);
		}	
		

		$ordem->dataabertura = date_format($ordem->dataabertura, 'd/m/Y');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'd/m/Y');

		$movimentacoes = $this->Ordemmovs->find('all')->where(['idordem' => $id, 'Ordemmovs.idempresa' => $idempresa])->contain(['Users' => ['fields' => ['Users.name']]])->order(['data'])->toArray();
		$parcelas = $this->Ordemparcelas->find('all')->where(['idordem' => $id, 'Ordemparcelas.idempresa' => $idempresa])->contain(['Users' => ['fields' => ['Users.name']]])->order(['data'])->toArray();
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
				$this->Flash->error('Ocorreu um erro ao editar a ordem de serviço. Verifique sua empresa atual e tente novamente');
				return $this->redirect(['action' => 'add']);
			}	

			$atendAnt = $ordem['atendimento'];
			$ordem = $this->Ordensservico->patchEntity($ordem, $data);
			$atendNov = $ordem['atendimento'];
			$ordem->valortotal = $data['valortotalordem'];

            if ($this->Ordensservico->save($ordem)) {
				if($atendAnt != $atendNov){
					if($atendAnt == 0) $atendAnt = 7; else $atendAnt = 8;
					if($atendNov == 0) $atendNov = 7; else $atendNov = 8;
					$this->Ordensservico->criarMov($ordem->id, $atendAnt, $atendNov, $this->Auth->user('idempresa'), $this->Auth->user('id'));
				}
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi salva com sucesso!.'));
                return $this->redirect(['action' => 'edit', $ordem->id]);
            }
            $this->Flash->error(__('Não foi possível salvar a ordem de serviço.'));
        }

		$clientes = $this->Clientes->find('all')
			->where(['Clientes.idempresa' => $idempresa, 'Clientes.inativo' => 0])
			->contain(['Cidades']) 
			->order(['Clientes.razaosocial'])
			->toArray();

		$clientesOpt = [];
		foreach($clientes as $reg){
			$nomeCliente = ($reg->tipo == C_ClientesTipoJuridica) ? $reg->razaosocial : $reg->nome;
			$nomeCidade = (!empty($reg->cidade) && !empty($reg->cidade->nome)) ? $reg->cidade->nome : 'Sem Cidade';
			$clientesOpt[$reg->id] = $nomeCliente . ' (' . $nomeCidade . ')';
		}
		asort($clientesOpt);


		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();
		
		$produtosOpt = [];
		foreach($this->Produtos->find('all')->where(['idempresa' => $idempresa])->order(['descricao'])->toArray() as $reg)
			$produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];

		$ordemhoras = $this->Ordemhoras->find('all')->contain(['Users', 'Ordensservico'])->where(['idordem' => $id, 'Ordensservico.idempresa' => $this->Auth->user('idempresa')])->toArray();
		$ordemparcelas = $this->Ordemparcelas->find('all')->contain(['Users', 'Ordensservico'])->where(['idordem' => $id, 'Ordensservico.idempresa' => $this->Auth->user('idempresa')])->first();

		$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $idempresa, 'idordem' => $id])->first();
		if(empty($this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray())) $this->set('finaliza', 'finaliza');

		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', C_ProdutosTipo);
		$this->set('tiposOpt', json_encode(C_ProdutosTipo, JSON_PRETTY_PRINT));
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('movimentacoes', $movimentacoes);
		$this->set('ordem', $ordem);
		$this->set('ordemhoras', $ordemhoras);
		$this->set('ordemparcelas', $ordemparcelas);
		$this->set('title', 'Editar ordem de serviços');
	}
	
	public function view($id = null) {
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if ($this->Auth->user('role') == C_RoleCliente && $ordem->idcliente != $idcliente) {
			$this->Flash->error('Você não possui permissão para visualizar outras ordens.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$ordem->dataabertura = date_format($ordem->dataabertura, 'd/m/Y');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'd/m/Y');

		$movimentacoes = $this->Ordemmovs->find('all')->where(['idordem' => $id, 'Ordemmovs.idempresa' => $idempresa])
			->contain(['Users' => ['fields' => ['Users.name']]])
		->toArray();

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg){
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}
		asort($clientesOpt);

		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where(['idempresa' => $idempresa])->order(['descricao'])->toArray();
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg){
			$produtosOpt[$reg->codigo] = $reg->descricao.' ('.$reg->codigo.')';
		}

		$tiposOpt = C_ProdutosTipo;

		$ordemhoras = $this->Ordemhoras->find('all', ['contain' => 'Users'])->where(['idordem' => $id])->toArray();
		$ordemparcelas = $this->Ordemparcelas->find('all', ['contain' => 'Users'])->where(['idordem' => $id])->first();

		$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $idempresa, 'idordem' => $id])->first();
		$carrinho = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();

		$this->set('carrinho', $carrinho);
		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', $tiposOpt);
		$this->set('tiposOpt', json_encode($tiposOpt, JSON_PRETTY_PRINT));
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('movimentacoes', $movimentacoes);
		$this->set('ordem', $ordem);
		$this->set('ordemhoras', $ordemhoras);
		$this->set('ordemparcelas', $ordemparcelas);
		$this->set('title', 'Visualizar ordem de serviços');
	}
	
	public function cadhoras($idordem = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$this->set('title', 'Cadastrar horas');

		$horas = $this->Ordemhoras->horasOrdem($idordem);

		$this->set('horas', $horas);
		$this->set('idordem', $idordem);
	}

	public function isAuthorized($user) {
		// Usa a mesma regra padrão do AppController (inclui verificação de prefixo admin)
		return parent::isAuthorized($user);
	}

	public function delete($id = null) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$idempresa = $this->Auth->user('idempresa');
		$ordem = $this->Ordensservico->find('all')
			->where(['id' => $id, 'idempresa' => $idempresa])
			->first();

		if (empty($ordem)) {
			$this->Flash->error('Ordem de serviço não encontrada para a empresa atual.');
			return $this->redirect(['action' => 'index']);
		}

		// Decrementa contador apenas se a exclusão ocorrer
		if ($this->Ordensservico->delete($ordem)) {
			$this->Empresas->decrementOrdem($idempresa);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
			$this->Flash->success(__('A ordem foi deletada com sucesso!.'));
			return $this->redirect(['action' => 'index']);
		}

		$this->Flash->error(__('Não foi possível deletar a ordem de serviço.'));
		return $this->redirect(['action' => 'index']);
	}

	public function carrinho($idordem = null){
		error_reporting(0);
		$this->autoRender = false;

		if($idordem === null){
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
			$result = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();
		} else {
			$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $idordem])->first();
			$result = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();
		}

		$output = [];
        foreach($result as $row)
        {
            $output[] = array(
            'id'            => $row->id,
            'tipo'          => $row->tipo,
            'codprodutosoocod' => $row->codproduto,   
            'codproduto'    => $row->codproduto,   
            'descricao'     => $row->descricao,
            'observacao'    => $row->observacao,
            'unidade'       => $row->unidade,
            'quantidade'    => $row->quantidade,
            'serialnumber'  => $row->serialnumber,
            'modelo'        => $row->modelo, 
			'productkey'	=> $row->productkey,
			'obsinterna'	=> $row->obsinterna,
            'valorunitario' => number_format($row->valorunitario, 2, ",", "."),
            'valordesconto' => number_format( $row->valordesconto, 2, ",", "."),
            'valortotal'    => number_format( $row->valortotal   , 2, ",", "."),
            );
        }

		header("Content-Type: application/json");
		return $this->jsonResponse($output, 200);
	}

	public function carrinhoadd($idordem = null, $codproduto = null){
		$this->autoRender = false;

		if($idordem == 'null'){
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
		} else {
			$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $idordem])->first();
			$idordem = $idcarrinho->iditens;
		}
		
		$carrinho = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();

		$data = $this->request->getData();
		if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
			$this->Flash->error('Ocorreu um erro ao salvar os itens de ordem de serviço. Verifique sua empresa atual e tente novamente');
			return $this->jsonResponse(['msg' => 'Ocorreu um erro ao salvar os itens de ordem de serviço. Verifique sua empresa atual e tente novamente'], 400);
		}

		// Código do produto pode vir na URL ou no POST (ex.: formulário mobile)
		if (empty($codproduto) && !empty($data['codproduto'])) {
			$codproduto = is_array($data['codproduto']) ? ($data['codproduto'][0] ?? null) : $data['codproduto'];
		}
		$codproduto = trim($codproduto ?? '');

		$idempresa = $this->Auth->user('idempresa');
		$valorunitario = (float) str_replace(',', '.', str_replace('.', '', $data['valorunitario'] ?? '0'));
		$descricao = $data['descricao'] ?? '';
		$unidade = $data['unidade'] ?? '';
		$tipo = $data['tipo'] ?? 0;

		$valordesconto = formatNumber($data['valordesconto']);
		$quantidade = (float) ($data['quantidade'] ?? 1);

		// Sempre buscar preço vigente: primeiro no ERP (Preço de Venda do estoque), senão no cadastro
		if ($codproduto !== '') {
			$produto = $this->Produtos->findByCodigo($codproduto)->where(['idempresa' => $idempresa])->first();
			if ($produto) {
				$descricao = $produto->descricao;
				$unidade = $produto->unidade;
				$tipo = $produto->tipo;
				$precoDoErp = null;
				try {
					$soapprodutos = $this->Empresas->get($idempresa)->urlerp . 'WsProdutos.wso?wsdl';
					$soap = new CakeSoap(['wsdl' => $soapprodutos]);
					$response = $soap->sendRequest('GetEstoqueProdutos', [
						'Data' => [
							'iFilial' => C_Filial,
							'sChave' => C_ChaveAcesso,
							'bApenasComSaldo' => false,
							'sCodProduto' => null,
							'sDescricao' => null,
						]
					]);
					$lista = $response->GetEstoqueProdutosResult->tWsProdutosEstoque ?? null;
					if ($lista !== null) {
						if (!is_array($lista)) $lista = [$lista];
						foreach ($lista as $item) {
							if (trim((string)($item->sCodProduto ?? '')) === $codproduto && isset($item->nPrecoVenda)) {
								$precoDoErp = (float) $item->nPrecoVenda;
								break;
							}
						}
					}
				} catch (\Exception $e) {
					// ERP indisponível: usa cadastro
				}
				if ($precoDoErp !== null) {
					$valorunitario = $precoDoErp;
					// Atualiza o cadastro do produto para refletir o Preço de Venda do estoque
					if ((float)$produto->vlunitario != $precoDoErp) {
						$produto->vlunitario = $precoDoErp;
						$this->Produtos->save($produto);
					}
				} else {
					$valorunitario = (float) $produto->vlunitario;
				}
			}
		}

		$valortotal = ($quantidade * $valorunitario) - $valordesconto;

		$ordem = $this->Itensordem->newEntity();
        $ordem->idordempk = $idordem;
        $ordem->idempresa = $idempresa;
        $ordem->tipo = $tipo;
        $ordem->codproduto = $codproduto;
        $ordem->descricao = $descricao;
        $ordem->observacao = $data['observacao'];
        $ordem->unidade = $unidade;
        $ordem->quantidade = $data['quantidade'];
        $ordem->serialnumber = $data['serialnumber'];
        $ordem->modelo = $data['modelo']; 
		$ordem->productkey = isset($data['productkey']) ? $data['productkey'] : '';
        $ordem->obsinterna = isset($data['obsinterna']) ? $data['obsinterna'] : '';
        $ordem->valorunitario = $valorunitario;
        $ordem->valordesconto = $valordesconto;
        $ordem->valortotal = $valortotal;

		if( $this->Itensordem->save($ordem) ) echo('boa');

	}

	public function carrinhoedititem(){
        $this->autoRender = false;
        $data = $this->request->getData();

        if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
            $this->Flash->error('Ocorreu um erro ao atualizar os itens. Verifique sua empresa.');
            return $this->jsonResponse(['msg' => 'Erro empresa'], 400);
        }   

        $ordem = $this->Itensordem->findById($data['id'])->first();
        
        if ($ordem) {
            $ordem->tipo =  $data['tipo'];
            $ordem->codproduto = $data['codproduto'];
            $ordem->descricao =  $data['descricao'];
            $ordem->observacao = isset($data['observacao']) ? $data['observacao'] : '';
            $ordem->modelo = isset($data['modelo']) ? $data['modelo'] : '';
            $ordem->serialnumber = isset($data['serialnumber']) ? $data['serialnumber'] : '';
			$ordem->productkey = isset($data['productkey']) ? $data['productkey'] : '';
			$ordem->obsinterna = isset($data['obsinterna']) ? $data['obsinterna'] : '';
            $ordem->unidade =  $data['unidade'];
            $ordem->quantidade = $data['quantidade'];
            $ordem->valorunitario = str_replace('.', '', $data['valorunitario']);
            $ordem->valorunitario = str_replace(',', '.', $ordem->valorunitario);          
            $ordem->valordesconto = str_replace('.', '', $data['valordesconto']);
            $ordem->valordesconto = str_replace(',', '.', $ordem->valordesconto); 
            $ordem->valortotal = str_replace('.', '', $data['valortotal']);
            $ordem->valortotal = str_replace(',', '.', $ordem->valortotal);

            if( $this->Itensordem->save($ordem) ) {
                echo('boa');
            } 
        }
    }
	
	public function carrinhodelitem(){
		$this->autoRender = false;
		$data = $this->request->getData();

		if($data['idEmpresaAtual'] != $this->Auth->user('idempresa')) {
			$this->Flash->error('Ocorreu um erro ao deletar o item da ordem de serviço. Verifique sua empresa atual e tente novamente');
			return $this->jsonResponse(['msg' => 'Ocorreu um erro ao salvar os itens de ordem de serviço. Verifique sua empresa atual e tente novamente'], 400);
		}	

		$ordem = $this->Itensordem->findById($data['id'])->first();
		
		if( $this->Itensordem->delete($ordem) ) echo('boa');
	}

	public function valortotal($idordem = null){
		$this->autoRender = false;
		if($idordem == null){
			$idordem = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
			$carrinho = $this->Itensordem->findByIdordempk($idordem)->order(['id'])->toArray();
		} else {
			//$idcarrinho = $this->Ordemservicositens->findByIdordem($idordem)->toArray();
			//$carrinho = $this->Itensordem->findByIdordempk($idcarrinho[0]->iditens)->order(['id'])->toArray();

			$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $idordem])->first();
			$carrinho = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();
		}
		
		$valortotal = 0;
		foreach($carrinho as $reg) {
			 $valortotal += $reg->valortotal;
		}

		return $this->jsonResponse(['valortotal' => $valortotal], 200);

	}

	public function pausar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;
		
		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoAberta;

		if ($this->Ordensservico->save($ordem)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoAberta, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi pausada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao pausar a ordem de serviço.');
	}

	public function cancelar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;
		
		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoCancelada;

		if ($this->Ordensservico->save($ordem)) {
			$ticket = $this->Tickets->findById($ordem->idticket)->first();
			if(!empty($ticket)) {
				$sitantiga = $ticket->situacao;
				$ticket->situacao = C_TicketSituacaoFechado;
				if($this->Tickets->save($ticket)) {
					$emailDest = $this->Tickets->email($ordem->idticket, C_TicketsAcaoFechado, null, $this->Auth->user('idempresa'));
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ordem->idticket;
					$mov->sitantiga = $sitantiga;
					$mov->sitnova = C_TicketSituacaoFechado;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $this->Auth->user('idempresa');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$mov->observacao = "Ticket cancelado com o cancelamento da ordem de serviço nº $id";
					$this->Ticketsmovs->save($mov);
					$this->Flash->success("O ticket $ordem->idticket foi cancelado e um email foi enviado para $emailDest informando o cancelamento!");
				}
			}
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoCancelada, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi cancelada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao cancelar a ordem de serviço.');
	}

	public function liberar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;
		
		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoLiberadaParaFaturamento;

		if ($this->Ordensservico->save($ordem)) {
			$ticket = $this->Tickets->findById($ordem->idticket)->first();
			if(!empty($ticket)) {
				$sitantiga = $ticket->situacao;
				$ticket->situacao = C_TicketSituacaoResolvido;
				if($this->Tickets->save($ticket)) {
					$emailDest = $this->Tickets->email($ordem->idticket, null, null, $this->Auth->user('idempresa'));
					$mov = $this->Ticketsmovs->newEntity();
					$mov->idticket = $ordem->idticket;
					$mov->sitantiga = $sitantiga;
					$mov->sitnova = C_TicketSituacaoResolvido;
					$mov->idusuario = $this->Auth->user('id');
					$mov->idempresa = $this->Auth->user('idempresa');
					$mov->datetime = date('d/m/Y H:i:s', time());
					$mov->observacao = "Ticket resolvido com a liberação da ordem de serviço nº $id";
					$this->Ticketsmovs->save($mov);
					$this->Flash->success("O ticket $ordem->idticket foi resolvido e um email foi enviado para $emailDest informando a resolução!");
				}
			}
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoLiberadaParaFaturamento, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi liberada para faturamento com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao liberar a ordem de serviço.');
	}

	public function finalizar($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;
		
		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoFinalizada;

		if ($this->Ordensservico->save($ordem)) {
			
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoFinalizada, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi finalizada com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao finalizar a ordem de serviço.');
	}

	public function emexec($id = null){
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$data = $this->request->getData();
		$sitantiga = $ordem->situacao;
		
		$this->Ordensservico->patchEntity($ordem, $data);
		$ordem->situacao = C_OrdensSituacaoEmExecucao;

		if ($this->Ordensservico->save($ordem)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
			$this->Ordensservico->criarMov($id, $sitantiga, C_OrdensSituacaoEmExecucao, $this->Auth->user('idempresa'), $this->Auth->user('id'));
			$this->Flash->success('A ordem de serviço foi movida para "em execução" com sucesso!');
			return $this->redirect(['action' => 'edit', $id]);
		}
		$this->Flash->error('Ocorreu um erro ao mover a ordem de serviço.');
	}

	public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			// Suporta credenciais via headers (ERP) e também via query string (fallback)
			$empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
			$token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
			$situacao = $this->request->getHeaderLine('situacao') ?: $this->request->getQuery('situacao');
			$id = $this->request->getHeaderLine('id') ?: $this->request->getQuery('id');

			$apiRet = function ($msg, $status = 200) {
				return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
			};

			$empresa = is_string($empresa) ? trim($empresa) : $empresa;
			$token = is_string($token) ? trim($token) : $token;
			$situacaoInt = is_numeric($situacao) ? (int)$situacao : null;

			if (empty($token) || empty($empresa) || $situacaoInt === null) {
				return $apiRet('Parâmetros da requisição inválidos. Envie empresa, token e situacao (ex.: situacao=4).', 400);
			}
			
			if(empty($this->Empresas->findById($empresa)->first())) return $apiRet('Parâmetros da requisição inválidos', 400);
			if($token == $this->Empresas->get($empresa)->token) {
				if(!empty($id)){
					$ordem = $this->Ordensservico->find('all')->where(['Ordensservico.idempresa' => $empresa, 'Ordensservico.id' => $id, 'situacao' => $situacaoInt])
						->contain([
							'Clientes' => ['fields' => ['Clientes.cnpj', 'Clientes.cpf', 'Clientes.razaosocial', 'Clientes.nome', 
							'Clientes.inscricaoestadual', 'Clientes.endereco', 'Clientes.nroendereco', 'Clientes.complemento', 'Clientes.bairro', 'Clientes.idcidade',
							'Clientes.cep', 'Clientes.fone', 'Clientes.email', 'Clientes.contrato', 'Clientes.tipo']]
						])
					->toArray();
					if ($ordem == []) return $this->jsonResponse($ordem, 200);
				}else{
					$ordem = $this->Ordensservico->find('all')->where(['Ordensservico.idempresa' => $empresa, 'situacao' => $situacaoInt])
						->contain([
							'Clientes' => ['fields' => ['Clientes.cnpj', 'Clientes.cpf', 'Clientes.razaosocial', 'Clientes.nome', 
							'Clientes.inscricaoestadual', 'Clientes.endereco', 'Clientes.nroendereco', 'Clientes.complemento', 'Clientes.bairro', 'Clientes.idcidade',
							'Clientes.cep', 'Clientes.fone', 'Clientes.email', 'Clientes.contrato', 'Clientes.tipo']]
						])
					->toArray();
				}
				foreach($ordem as $reg){
					// Itens
					//$idcarrinho = $this->Ordemservicositens->findByIdordem($reg->id)->first();
					$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $empresa, 'idordem' => $reg->id])->first();
					$itens = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();
					$reg->itens = $this->itensArr($itens);
					// Parcelas
					$parcelas = $this->Ordemparcelas->findByIdordem($reg->id)->where(['idempresa' => $empresa])->toArray();
					$reg->pagamento = $this->parcelasArr($parcelas);
					// Clientes
					$reg->cliente = $this->Clientes->clientesArr($reg->cliente);
					$reg = $this->ordensArr($reg);
				}
				return $this->jsonResponse($ordem, 200);
			} else {
				return $apiRet('Autenticação Inválida', 401);
			}
        }
	}

	public function ordensArr($ordem){
		$ordem->numero = $ordem->id;
		$ordem->dataabertura = date_format($ordem->dataabertura, 'Y-d-m');
		$ordem->dataprevisao = date_format($ordem->dataprevisao, 'Y-d-m');
		try {
			$ordem->cpftecnico = $ordem->iduser ? $this->Users->get($ordem->iduser)->cpf : '';
		} catch (\Throwable $e) {
			$ordem->cpftecnico = '';
		}
		// Manter situacao na resposta para o ERP exibir "Liberada para faturamento" (4) etc.
		if (isset($ordem->situacao) && function_exists('DescricaoSituacaoOrdem')) {
			$ordem->situacao_descricao = DescricaoSituacaoOrdem($ordem->situacao);
		}
		unset($ordem->id);
		unset($ordem->idcliente);
		unset($ordem->iduser);
		unset($ordem->valortotal);
		unset($ordem->relato);
		unset($ordem->idproblema);
		unset($ordem->idarea);
		unset($ordem->idempresa);
		unset($ordem->nrodestino);
		return $ordem;
	}

	public function parcelasArr($pag){
		$pagamentoarr = [];
		foreach($pag as $pagamento){
			$pagamentoarr[] = array(
				'id'    		=> $pagamento->id,
				'pagamento'   	=> $pagamento->pagamento,
				'nmrparcelas'	=> $pagamento->nmrparcelas,
				'entrada'    	=> $pagamento->entrada,
				'parcelas'		=> array(),
			);

			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval1, 'd/m/Y'), 'valor' => $pagamento->valor1);
			
			if($pagamento->dataval2 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval2, 'd/m/Y'), 'valor' => $pagamento->valor2);
			if($pagamento->dataval3 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval3, 'd/m/Y'), 'valor' => $pagamento->valor3);
			if($pagamento->dataval4 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval4, 'd/m/Y'), 'valor' => $pagamento->valor4);
			if($pagamento->dataval5 != null)
			$pagamentoarr[0]['parcelas'][] = array('vencimento' => date_format($pagamento->dataval5, 'd/m/Y'), 'valor' => $pagamento->valor5);
		}
		return $pagamentoarr;
	}
	
	public function itensArr($itens){
		$itensarr = [];
		foreach($itens as $row){
			$itensarr[] = array(
			'id'    		=> $row->id,
			'tipo'   		=> $row->tipo,
			'codproduto'    => $row->codproduto,   
			'descricao'  	=> $row->descricao,
			'observacao'  	=> $row->observacao,
			'unidade'   	=> $row->unidade,
			'quantidade'    => $row->quantidade,
			'valorunitario' => $row->valorunitario,
			'valordesconto' => $row->valordesconto,
			'valortotal'   	=> $row->valortotal,
			'serialnumber'	=> $row->serialnumber,
			'ativo'   		=> $row->ativo,
			);
		}
		return $itensarr;
	}

    public function refreshAPI() {
        $this->autoRender = false;
        $reqMethod = $this->request->getMethod();
        $reqPath = $this->request->getRequestTarget();
        $hEmpresa = $this->request->getHeaderLine('empresa');
        $hToken = $this->request->getHeaderLine('token');
        $this->log('[API-ORDENS refreshAPI] request method=' . $reqMethod . ' path=' . $reqPath . ' headers(empresa=' . ($hEmpresa ?: 'vazio') . ' token=' . (strlen($hToken ?? '') ? '***' : 'vazio') . ')', 'info');

		$apiRet = function ($msg, $status = 200) {
			return $this->jsonResponse(['mensagem' => $msg, 'retorno' => $msg], $status);
		};
		if (!$this->request->is('put')) {
            $this->log('[API-ORDENS refreshAPI] resposta 405 metodo nao permitido', 'info');
			return $apiRet('Método não permitido. Use PUT.', 405);
		}
		$empresa = $this->request->getHeaderLine('empresa') ?: $this->request->getQuery('empresa');
		$token = $this->request->getHeaderLine('token') ?: $this->request->getQuery('token');
		// Aceitar JSON do body: getData() ou input('json_decode')
		$json = $this->request->getData();
		if (empty($json) || !is_array($json)) {
			$raw = $this->request->input('json_decode');
			$json = is_string($raw) ? json_decode($raw) : (is_array($raw) ? (object)$raw : $raw);
		} else {
			$json = (object) $json;
		}

			if ($json === null || !is_object($json)) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (empresa/token/body)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if (empty($token) || empty($empresa)) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (empresa/token/body)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if (!isset($json->nroordem) || $json->nroordem === '' || $json->nroordem === null) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 parametros invalidos (sem nroordem)', 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if(empty($this->Empresas->findById($empresa)->first())) {
				$this->log('[API-ORDENS refreshAPI] resposta 400 empresa nao encontrada id=' . $empresa, 'info');
				return $apiRet('Parâmetros da requisição inválidos.', 400);
			}
			if($token == $this->Empresas->get($empresa)->token){
				$ordem = $this->Ordensservico->findById($json->nroordem)->where(['idempresa' => $empresa])->first(); 

				if ($ordem == null) {
					$this->log('[API-ORDENS refreshAPI] resposta 400 ordem nao encontrada nroordem=' . ($json->nroordem ?? '?') . ' empresa=' . $empresa, 'info');
					return $apiRet('Parâmetros da requisição inválidos.', 400);
				}

				$sitantiga = $ordem->situacao;
				$ordem->nrodestino = $json->nrodestino;
				$ordem->situacao = $json->situacao;

                if($this->Ordensservico->save($ordem)){
					// criarMov(idordem, sitantiga, sitnova, idempresa, iduser, obs) — API não tem usuário logado, usar 0
					$iduser = $this->Auth->user('id');
					if ($iduser === null || $iduser === '') $iduser = 0;
					try {
						$this->Ordensservico->criarMov($ordem->id, $sitantiga, $ordem->situacao, $empresa, $iduser, 'Sincronização ERP');
					} catch (\Throwable $e) {
						$this->log('Ordensservico::refreshAPI criarMov: ' . $e->getMessage(), 'error');
					}
					$this->log('[API-ORDENS refreshAPI] resposta 201 ok ordem=' . $ordem->id . ' empresa=' . $empresa . ' situacao=' . $ordem->situacao, 'info');
					return $apiRet('Situação da Ordem de Serviço alterada com sucesso', 201);
				}
                else {
					$this->log('[API-ORDENS refreshAPI] resposta 400 erro ao salvar ordem', 'info');
					return $apiRet('Ocorreu um erro ao atualizar a ordem!', 400);
				}
			}
			$this->log('[API-ORDENS refreshAPI] resposta 401 autenticacao invalida empresa=' . $empresa, 'info');
			return $apiRet('Autenticação Inválida', 401);
	}

	public function imprimir($id = null){
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordem = $this->Ordensservico->find('all', ['contain' => ['Users', 'Clientes']])->where(['Ordensservico.id' => $id, 'Ordensservico.idempresa' => $idempresa])->first();
		$cidade = $this->Cidades->get($ordem->cliente->idcidade);
		$estado = $this->Estados->get($cidade->idestado);

		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		//$idcarrinho = $this->Ordemservicositens->findByIdordem($id)->first();
		$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $idempresa, 'idordem' => $id])->first();
		$carrinho = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();

		$this->set('cidade', $cidade->nome);
		$this->set('estado', $estado->nome);
		$this->set('carrinho', $carrinho);
		$this->set('idcliente', $idcliente);
		$this->set('idempresa', $idempresa);
		$this->set('ordem', $ordem);
		$this->set('title', 'Imprimir ordem de serviços');
	}

	public function imprimirordens(){
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$data = $this->request->getData();
		$idempresa = $this->Auth->user('idempresa');
		$idcliente = $this->Auth->user('idcliente');
		$ordens = $this->Ordensservico->find('all', ['contain' => ['Users', 'Clientes']])->where(['Ordensservico.id IN' => explode(',', $data['idsimprimir']), 'Ordensservico.idempresa' => $idempresa])->toArray();
		
		foreach($ordens as $ordem) {
			$cidades[$ordem->id] = $this->Cidades->get($ordem->cliente->idcidade);
			$estados[$ordem->id] = $this->Estados->get($cidades[$ordem->id]->idestado);
			//$idcarrinho = $this->Ordemservicositens->findByIdordem($ordem->id)->first();
			$idcarrinho = $this->Ordemservicositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $ordem->id])->first();
			$carrinhos[$ordem->id] = $this->Itensordem->findByIdordempk($idcarrinho->iditens)->order(['id'])->toArray();
		}
	
		$this->set('cidades', $cidades);
		$this->set('estados', $estados);
		$this->set('carrinhos', $carrinhos);
		$this->set('idcliente', $idcliente);
		$this->set('idempresa', $idempresa);
		$this->set('ordens', $ordens);
		$this->set('title', 'Imprimir ordens de serviços');
	}

	public function ticketordem($idticket) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$ticket = $this->Tickets->findById($idticket)->contain(['Clientes' => ['fields' => ['contrato']], 'Ticketcomentarios', 'Ticketcomentarios.Users'])->first();
		
		$ordem = $this->Ordensservico->newEntity();
		$ordem->idcliente = $ticket->idcliente;
		$ordem->idsolicitante = $ticket->idsolicitante;
		$ordem->relato = $ticket->solicitacao;
		$ordem->dataabertura = date('d/m/Y');
		$ordem->contrato = $ticket->cliente->contrato;
		$ordem->idarea = $this->Areas->find('all')->where(["LOWER(descricao) ilike '%aguardando técnico%'"])->first()->id;
		$ordem->idproblema = $this->Problemas->find('all')->where([	'LOWER(descricao)' => 'corretiva'])->first()->id;
		$idsolicitante = $ordem->idsolicitante;
		foreach($ticket->ticketcomentarios as $reg) $ordem->observacao .= $reg->user->name . ': ' . $reg->comentario . '; ';

		$ultimo = $this->Empresas->prxOrdem($this->Auth->user('idempresa'));
		if($ultimo == null || $ultimo == 0) {
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $this->Auth->user('id');
			$idcarrinho = $idempresa . 1 . $this->Auth->user('id');
		} else {
			$idcarrinho =  $idempresa . $ultimo . $this->Auth->user('id');
			$_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo . $this->Auth->user('id');
		}

		if ($this->request->is('post')) {
			$data = $this->request->getData();
            $ordem = $this->Ordensservico->patchEntity($ordem, $data);
			$ordem->idempresa = $idempresa;
			$ordem->iduser = $this->Auth->user('id');
			$ordem->situacao = C_OrdensSituacaoEmExecucao;
			$ordem->valortotal = $data['valortotalordem'];
			$ordem->id = $this->Empresas->incrementOrdem($this->Auth->user('idempresa'));
			$ordem->idticket = $idticket;

            if ($this->Ordensservico->save($ordem)) {
				// Itens
				$carrinho = $this->Ordemservicositens->newEntity();
				$carrinho->iditens = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
				$carrinho->idordem = $ordem->id;
				$carrinho->idempresa = $idempresa;
				$this->Ordemservicositens->save($carrinho);
				unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);
				// Movimentação
				$this->Ordensservico->criarMov($ordem->id, 1, 1, $this->Auth->user('idempresa'), $this->Auth->user('id'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi cadastrada com sucesso!'));
                return $this->redirect(['action' => 'edit', $ordem->id]);
			}
			// Decrementa o último id em caso de erro
			$this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
            $this->Flash->error(__('Não foi possível cadastrar a ordem de serviço.'));
        }

		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
		$clientesOpt = [];
		foreach($clientes as $reg) {
			if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
			else $clientesOpt[$reg->id] = $reg->nome;
		}
		asort($clientesOpt);

		$areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();
		$problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->order(['descricao'])->toArray();
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		
		$this->set('produtosMobile', $produtosOpt);
		$this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
		$this->set('tiposMobile', C_ProdutosTipo);
		$this->set('tiposOpt', json_encode(C_ProdutosTipo, JSON_PRETTY_PRINT));
		$this->set('problemas', $problemas);
		$this->set('areas', $areas);
		$this->set('clientes', $clientesOpt);
		$this->set('idsolicitante', $idsolicitante);
		$this->set('ordem', $ordem);
		$this->set('idticket', $idticket);
		$this->set('title', 'Cadastro de ordem de serviços');
	}

	public function acaoindex() {
		$data = $this->request->getData();
		foreach(explode(',', $data['ids']) as $id) {
			$ordem = $this->Ordensservico->findById($id)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			if(!empty($ordem)) {
				$ordem->situacao = $data['situacao'];
				$this->Ordensservico->save($ordem);
				if(in_array($data['situacao'], [C_OrdensSituacaoLiberadaParaFaturamento, C_OrdensSituacaoCancelada])) {
					$ticket = $this->Tickets->findById($ordem->idticket)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
					if(!empty($ticket)) {
						$sitantiga = $ticket->situacao;
						if($data['situacao'] == C_OrdensSituacaoLiberadaParaFaturamento) {
							$sitnova = C_TicketSituacaoResolvido;
							$observacao = "Ticket resolvido com a liberação da ordem de serviço nº $id";
							$acao1 = 'resolvido';
							$acao2 = 'a resolução';
							$acaoEmail = null;
						} else {
							$sitnova = C_TicketSituacaoFechado;
							$observacao = "Ticket cancelado com o cancelamento da ordem de serviço nº $id";
							$acao1 = 'cancelado';
							$acao2 = 'o cancelamento';
							$acaoEmail = C_TicketsAcaoFechado;
						}

						$ticket->situacao = $sitnova;
						if($this->Tickets->save($ticket)) {
							$emailDest = $this->Tickets->email($ordem->idticket, $acaoEmail, null, $this->Auth->user('idempresa'));
							$mov = $this->Ticketsmovs->newEntity();
							$mov->idticket = $ordem->idticket;
							$mov->sitantiga = $sitantiga;
							$mov->sitnova = $sitnova;
							$mov->idusuario = $this->Auth->user('id');
							$mov->idempresa = $this->Auth->user('idempresa');
							$mov->datetime = date('d/m/Y H:i:s', time());
							$mov->observacao = $observacao;
							$this->Ticketsmovs->save($mov);
							$this->Flash->success("O ticket $ordem->idticket foi $acao1 e um email foi enviado para $emailDest informando $acao2!");
						}
					}
				}

				if($data['situacao'] == C_OrdensSituacaoLiberadaParaFaturamento) {
					$pagamentosAnt = $this->Ordemparcelas->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idordem' => $id])->toArray();
					if(empty($pagamentosAnt)) {
						$pagamento = $this->Ordemparcelas->newEntity();
						$pagamento = $this->Ordemparcelas->patchEntity($pagamento, $data);
						$pagamento->idempresa = $this->Auth->user('idempresa');
						$pagamento->iduser = $this->Auth->user('id');
						$pagamento->idordem = intval($id);
						$pagamento->data = date('d/m/Y', time());

						switch ($pagamento->nmrparcelas) {
							case '1': $pagamento->dataval2 = null;
									$pagamento->dataval3 = null;
									$pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '2': $pagamento->dataval3 = null;
									$pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '3': $pagamento->dataval4 = null;
									$pagamento->dataval5 = null; break;
							case '4': $pagamento->dataval5 = null; break;
							default: break;
						}

						$this->Ordemparcelas->save($pagamento);
					}
				}
			}
		}
		$this->Flash->success('As ordem selecionadas foram movidas para "'. DescricaoSituacaoOrdem($data['situacao']) .'" com sucesso!');
		return $this->redirect(['action' => 'index']);
	}	

	public function locacao($id, $locacao) {
		$ordem = $this->Ordensservico->find('all')->where(['id' => $id, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$ordem->locacao = $locacao;

		if ($this->Ordensservico->save($ordem)) $this->Flash->success('Informações da ordem de serviço alteradas com sucesso!');
		else $this->Flash->error('Ocorreu um erro ao salvar as informações da ordem de serviço.');
		return $this->redirect(['action' => 'edit', $id]);
	}
}