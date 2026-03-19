<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Mailer\Email;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';

class OrcamentosController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Clientes');
		$this->loadModel('Orcamentos');
		$this->loadModel('Orcamentosmovs');
		$this->loadModel('Orcamentosservicos');
		$this->loadModel('Orcamentositens');
		$this->loadModel('Empresas');
		$this->loadModel('Produtos');
		$this->loadModel('Ordensservico');
		$this->loadModel('Areas');
		$this->loadModel('Problemas');
		$this->loadModel('Itensordem');
		$this->loadModel('Ordemservicositens');
		$this->loadModel('Config');
		$this->loadModel('Tickets');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		if($this->Auth->user('role') == 1 && !$this->Auth->user('permissaoacesso')) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
			$orcamento = $this->Orcamentos->get($event->_subject->request->params['pass'][0])->idcliente;
			$cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;
			
			if ($orcamento != $cliente) {
				$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		}

		$this->set('title', 'Orçamentos');
		$this->Auth->allow(['viewhash', 'carrinhoedit', 'aprovarhash']);
	}

	public function criarMov($idorcamento = null, $sitantiga = null, $sitnova = null, $observacao = null, $idempresa = null) {
		$mov = $this->Orcamentosmovs->newEntity();
		$mov->idorcamento = $idorcamento;
		$mov->sitantiga = $sitantiga;
		$mov->sitnova = $sitnova;
		$mov->idusuario = empty($this->Auth->user('id')) ? 0 : $this->Auth->user('id');
		$mov->idempresa = !empty($idempresa) ? $idempresa : $this->Auth->user('idempresa');
		$mov->datetime = date('d/m/Y H:i:s', time());

		if (!empty($observacao)) $mov->observacao = $observacao;

		return $this->Orcamentosmovs->save($mov);
	}

	public function index() {
		$idcliente = $this->Auth->user('idcliente');
		$orcamentosCliente = $this->Orcamentos->find('all', ['contain' => 'Users'
			])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa'), 'Orcamentos.idcliente' => $idcliente])
		->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosPendentes = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 0]
		])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa')])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosEnviados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 1]
		])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa')])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosAprovados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 2]
		])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa')])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosRecusados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 3]
		])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa')])->order(['Orcamentos.id DESC'])->toArray();

		$orcamentosArquivados = $this->Orcamentos->find('all',[
			'contain' => 'Clientes',
			'conditions' => ['Orcamentos.status' => 4]
		])->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa')])->order(['Orcamentos.id DESC'])->toArray();
		
		$this->set('orcamentosCliente', $orcamentosCliente);
		$this->set('orcamentosPendentes', $orcamentosPendentes);
		$this->set('orcamentosEnviados', $orcamentosEnviados);
		$this->set('orcamentosAprovados', $orcamentosAprovados);
		$this->set('orcamentosRecusados', $orcamentosRecusados);
		$this->set('orcamentosArquivados', $orcamentosArquivados);
		$this->set('title', 'Orçamentos');
	}

	public function add($idticket = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) return $this->redirect(['controller' => 'Tickets', 'action' => 'add', 4]);

		$orcamento = $this->Orcamentos->newEntity();
        $ticket = $this->Tickets->findById($idticket)->first();
		if(!empty($ticket)) $orcamento->solicitacao = $ticket->solicitacao;

		if(!isset($_SESSION['idcarrinhoadd'])) { 
			$ultimo = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa')])->order(['id ASC'])->last();
			if(empty($ultimo)) $_SESSION['idcarrinhoadd'] = 1 . $this->Auth->user('id');
			else{
				$idcarrinhoorcamento = $ultimo->id + 1 . $this->Auth->user('id');
				$_SESSION['idcarrinhoadd'] = $ultimo->id + 1 . $this->Auth->user('id');
			}
		}

		if ($this->request->is('post')) { 
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $this->request->getData());
			$orcamento->created = date("Y-m-d H:i:s");
			$orcamento->idautor = $this->Auth->user('id');
			$orcamento->id = $this->Empresas->incrementOrcamento($this->Auth->user('idempresa'));
			$orcamento->idempresa = $this->Auth->user('idempresa');
			$orcamento->hash = $orcamento->idautor . $orcamento->id . $orcamento->idempresa . sequenciaAleatoria();
			// cria status por padrao como pendente
			$orcamento->status = C_OrcamentoStatusPendente;

			if ($this->Orcamentos->save($orcamento)) {
				if(isset($idcarrinhoorcamento)) $_SESSION['idcarrinhoadd'] = $idcarrinhoorcamento;
				$carrinho = $this->Orcamentositens->newEntity();
				$carrinho->iditem = $_SESSION['idcarrinhoadd'];
				$carrinho->idorcamento = $orcamento->id;
				$carrinho->idempresa = $orcamento->idempresa;
				$this->Orcamentositens->save($carrinho);
				$this->limpasession();
				$this->Flash->success(__('Orçamento gerado com sucesso!'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
				return $this->redirect(['action' => 'edit', $orcamento->id]);
			}
			$orcamento->id = $this->Empresas->decrementOrcamento($this->Auth->user('idempresa'));
			$this->Flash->error(__('Não foi possível gerar o orçamento.'));
		} else $this->limpacarrinho();

		// Combos 
		$clientes = $this->Clientes->find('all')
			->where(['Clientes.idempresa' => $this->Auth->user('idempresa'), 'Clientes.inativo' => 0])
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

			$produtosOpt = [0 => 'Código'];
			$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
			foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';
		// 

		$this->set('idcarrinho', $_SESSION['idcarrinhoadd']);
		$this->set('clientes', $clientesOpt);
		$this->set('produtos', $produtosOpt);
		$this->set('orcamento', $orcamento);
		$this->set('title', 'Gerar Orçamento');
	}
	
	public function edit($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$orcamento = $this->Orcamentos->find('all')
		->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa'), 'Orcamentos.id' => $id])
		->contain([
			'Clientes' => [
				'fields' => ['Clientes.razaosocial', 'Clientes.email', 'Clientes.nome', 'Clientes.idcidade', 'Clientes.tipo'] 
			],
			'Clientes.Cidades' 
		])
		->first();
		$carrinho = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $id]])->first();


		if(empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();
			$_SESSION['idcarrinho'] =  $this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id');
		} else { 
			$idcarrinho = $carrinho->iditem;
			$_SESSION['idcarrinho'] = $idcarrinho;
		}


		if ($this->request->is(['post', 'put'])) {
			$orcamento = $this->Orcamentos->patchEntity($orcamento, $this->request->getData());

			if ($this->Orcamentos->save($orcamento)) {
				$this->Flash->success(__('Orçamento alterado com sucesso!'));
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			} else $this->Flash->error(__('Não foi possível alterar o orçamento.'));
			return $this->redirect(['action' => 'edit', $orcamento->id]);
		}

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';

		$ordem = $this->Ordensservico->findByIdorcamento($id)->first();
		$temordem = empty($ordem) ? 'nao' : $ordem->id;

		$this->set('title', 'Edição de Orçamento');
		$this->set('produtos', $produtosOpt);
		$this->set('temordem', $temordem);
		$this->set('orcamento', $orcamento);
		$this->set('idcarrinho', $_SESSION['idcarrinho']);
	}

	public function view($id = null, $idempresa = null) {
		if(!empty($idempresa)) {
			$user = $this->Users->get($this->Auth->user('id'));
			$user->idempresa = $idempresa;
			$this->Auth->setUser($user);
		}

		$ordem = $this->Ordensservico->findByIdorcamento($id)->first();
		$temordem = empty($ordem) ? 'nao' : $ordem->id;
		$this->set('temordem', $temordem);

		$orcamento = $this->Orcamentos->find('all')
			->where(['Orcamentos.idempresa' => $this->Auth->user('idempresa'), 'Orcamentos.id' => $id])
			->contain([ 'Users' => ['fields' => ['Users.name']]])
		->first();

		$idcliente = $this->Auth->user('idcliente');
		if ($this->Auth->user('role') == C_RoleCliente && $orcamento->idcliente != $idcliente) {
			$this->Flash->error('Você não possui permissão para visualizar outros orçamentos.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$carrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $id])->toArray();
		$empresaObj = $this->Empresas->get($this->Auth->user('idempresa'));

		if(empty($carrinho)) {
			$ultimo = $this->Orcamentos->find('all')->order(['id' => 'ASC'])->last();
			$_SESSION['idcarrinho'] = $this->Auth->user('idempresa') . $ultimo->id + 1 . $this->Auth->user('id');
		} else {
			$idcarrinho = $carrinho[0]->iditem;
			$_SESSION['idcarrinho'] = $idcarrinho;
		}
	
		$this->set('title', 'Visualização de Orçamento');
		$this->set('empresaObj', $empresaObj);
		$this->set('orcamento', $orcamento);
		$this->set('idcarrinho', $_SESSION['idcarrinho']);
	}

	public function viewhash($hash = null) {
		$this->viewBuilder()->setLayout('orcamentos');
		$orcamento = $this->Orcamentos->findByHash($hash)->contain(['Users' => ['fields' => ['Users.name']], 'Clientes' => ['fields' => ['Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]])->first();
		if(empty($orcamento)) {
			$this->Flash->error(__('Não foi encontrado um orçamento!'));
			return $this->redirect(['controller' => 'Users', 'action' => 'login']);
		}
		$carrinho = $this->Orcamentositens->find('all')->where(['idorcamento' => $orcamento->id])->first();
		$idcarrinho = $carrinho->iditem;
		$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $carrinho->iditem])->order(['id'])->toArray();
	
		$this->set('title', 'Visualização de Orçamento');
		$this->set('orcamento', $orcamento);
		$this->set('carrinho', $carrinho);
	}

	public function addservico($edit = null){
		$this->autoRender = false;
		try {
			$data = $this->request->getData();
			$idempresa = $this->Auth->user('idempresa');
			if (empty($idempresa)) {
				return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão inválida. Faça login novamente.']))->withStatus(401);
			}

			if ($edit == 'edit') {
				if (!array_key_exists('idcarrinho', $_SESSION)) {
					return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão do orçamento expirada. Recarregue a página e tente novamente.']))->withStatus(400);
				}
				$idorcamento = $_SESSION['idcarrinho'];
			} else {
				if (!array_key_exists('idcarrinhoadd', $_SESSION)) {
					return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => 'Sessão do orçamento expirada. Recarregue a página e tente novamente.']))->withStatus(400);
				}
				$idorcamento = $_SESSION['idcarrinhoadd'];
			}

			$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $idempresa, 'idorcamento' => $idorcamento]])->order(['id'])->toArray();
			$servico = isset($data['servico']) ? trim($data['servico']) : '';
			foreach ($carrinho as $reg) {
				if ($reg->servico == $servico) {
					$this->response = $this->response->withType('text/html')->withStringBody('nao pode');
					return $this->response;
				}
			}

			$valoruni = function_exists('formatNumber') ? formatNumber($data['valoruni'] ?? 0) : (float) str_replace([',', '.'], ['', '.'], $data['valoruni'] ?? '0');
			$valordoservico = function_exists('formatNumber') ? formatNumber($data['valordoservico'] ?? 0) : (float) str_replace([',', '.'], ['', '.'], $data['valordoservico'] ?? '0');

			// Sempre buscar preço atual do produto no BD (form envia código no select, não id)
			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$idprodutoVal = trim((string) $data['idproduto']);
				$produto = $this->Produtos->findByCodigo($idprodutoVal)->where(['idempresa' => $idempresa])->first();
				if (!$produto && is_numeric($idprodutoVal) && (int)$idprodutoVal > 0) {
					$produto = $this->Produtos->findById((int)$idprodutoVal)->where(['idempresa' => $idempresa])->first();
				}
				if ($produto) {
					$valoruni = (float) ($produto->vlunitario ?? 0);
					$valordoservico = $valoruni * (float) ($data['quantidade'] ?? 1);
				}
			}

			$orcamentond = $this->Orcamentosservicos->newEntity();
			if (!empty($data['idproduto']) && $data['idproduto'] != '0') {
				$orcamentond->idproduto = is_numeric($data['idproduto']) ? (int) $data['idproduto'] : 0;
			}
			$orcamentond->servico = $servico;
			$orcamentond->quantidade = $data['quantidade'] ?? 0;
			$orcamentond->observacao = isset($data['observacao']) ? $data['observacao'] : '';
			$orcamentond->valoruni = $valoruni;
			$orcamentond->tipo = isset($data['tipo']) ? $data['tipo'] : 0;
			$valormensal = isset($data['valormensal']) ? (function_exists('formatNumber') ? formatNumber($data['valormensal']) : (float) str_replace([',', '.'], ['', '.'], $data['valormensal'])) : 0;
			if ($valormensal != 0) {
				$qtd = function_exists('formatNumber') ? formatNumber($data['quantidade'] ?? 1) : (float) ($data['quantidade'] ?? 1);
				$orcamentond->valormensal = $qtd * $valormensal;
			}
			$orcamentond->valordoservico = $valordoservico;
			$orcamentond->idempresa = $idempresa;
			$orcamentond->idorcamento = $idorcamento;

			if ($this->Orcamentosservicos->save($orcamentond)) {
				$this->response = $this->response->withType('text/html')->withStringBody('boa');
				return $this->response;
			}

			$errors = $orcamentond->getErrors();
			$msg = 'Não foi possível salvar o item.';
			if (!empty($errors)) {
				$first = reset($errors);
				$msg = is_array($first) ? implode(' ', reset($first)) : (string) $first;
			}
			return $this->response->withType('application/json')->withStringBody(json_encode(['mensagem' => $msg]))->withStatus(422);
		} catch (\Throwable $e) {
			$this->log('Orcamentos::addservico ' . $e->getMessage(), 'error');
			return $this->response->withType('application/json')->withStringBody(json_encode([
				'mensagem' => 'Erro ao adicionar item. Tente novamente.',
				'detalhe' => $e->getMessage()
			]))->withStatus(500);
		}
	}

	public function carrinho($idorcamento = null){
		if($idorcamento == null) $idorcamento = $_SESSION['idcarrinhoadd'];
		$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento]])->order(['id'])->toArray();

		$this->set('carrinho', $carrinho);
		$this->set('idorcamento', $idorcamento);
	}
	
	public function carrinhoedit($idorcamento = null){
		$orcamento = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'id' => $idorcamento])->first();
		$idcarrinho = $this->Orcamentositens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento])->first();
		$idorcamentoServicos = $idcarrinho ? $idcarrinho->iditem : (isset($_SESSION['idcarrinho']) ? $_SESSION['idcarrinho'] : null);
		$carrinho = $idorcamentoServicos
			? $this->Orcamentosservicos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamentoServicos])->order(['id'])->toArray()
			: [];

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] = "$reg->descricao ($reg->codigo)";

		$this->set('produtos', $produtosOpt);
		$this->set('carrinho', $carrinho);
		$this->set('orcamento', $orcamento);
	}

	public function limpacarrinho(){
		if ($this->request->is(['ajax'])) $this->autoRender = false;
		if(isset($_SESSION['idcarrinhoadd']) && !empty($_SESSION['idcarrinhoadd'])) {
			$carrinho = $this->Orcamentosservicos->find('all')->where(['idorcamento' => $_SESSION['idcarrinhoadd']])->toArray();
			foreach($carrinho as $item) $this->Orcamentosservicos->delete($item);
		}
	}
	
	public function excluiitemcarrinho($id) {
		$this->autoRender = false;
		$item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		$this->Orcamentosservicos->delete($item);
	}

	public function getitemcarrinho($id) {
		$this->autoRender = false;
		$item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		return $this->jsonResponse($item, 200);
	}

	public function edititemcarrinho() {
        $this->autoRender = false;
        $data = $this->request->getData();
        $item = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $data['iditem']]])->first();
        $item = $this->Orcamentosservicos->patchEntity($item, $data);
        $vlMensal = isset($data['valormensal']) ? formatNumber($data['valormensal']) : 0;

        if ($vlMensal > 0) {
            $item->valormensal = $vlMensal;            
            $item->valoruni = 0;
            $item->valordoservico = 0;
        } else {
            if(isset($data['valoruni'])) $item->valoruni = formatNumber($data['valoruni']);
            if(isset($data['valordoservico'])) $item->valordoservico = formatNumber($data['valordoservico']);
            
            $item->valormensal = 0; 
        }
        if( $this->Orcamentosservicos->save($item) ){
            $this->Flash->success(__('Item alterado com sucesso!'));
            $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $item->id);
            return $this->redirect(['action' => 'edit', $data['idorcamentofind']]);
        }else $this->Flash->error(__('Não foi possível alterar o item!'));
    }
	
	public function limpasession() {
		$this->autoRender = false;
		unset($_SESSION['idcarrinho']);
		unset($_SESSION['idcarrinhoadd']);
	}
	
	public function imprimir($id = null) {
		// Permissão para o cliente
		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissões para acessar esta página.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$orcamento = $this->Orcamentos->find('all', [
			'contain' => ['Users', 'Clientes.Cidades']
		])->where(['AND' => ['orcamentos.idempresa' => $this->Auth->user('idempresa'), 'orcamentos.id' => $id]])->first();
		
		$orcamento->pdfgerado = 1;
		$this->Orcamentos->save($orcamento);
		
		$idprapesquisa = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $orcamento->id]])->toArray();

		if(empty($id)) $idorcamento = $_SESSION['idcarrinho'];
		$carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idprapesquisa[0]->iditem]])->order(['id'])->toArray();

		foreach($carrinho as $key => $reg) 
			if($reg->valormensal != null) 
				$carrinhoMensal[] = $reg;

		if(isset($carrinho))$this->set('carrinho', $carrinho);
		if(isset($carrinhoMensal))$this->set('carrinhoMensal', $carrinhoMensal);
		$this->set('idorcamento', $id);
		$this->set('title', 'Imprimir Orçamento');
		$this->set('orcamento', $orcamento);
	}
	
	public function aprovar($id = null) {
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		if($orcamento->status != C_OrcamentoStatusPendente && $orcamento->status != C_OrcamentoStatusEnviado){
			$this->Flash->error('Você não possui permissão para aprovar este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];
			
			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusAprovado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusAprovado, $observacao);
				$this->Flash->success('O orçamento foi aprovado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Aprovar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function aprovarhash($hash){
		$orcamento = $this->Orcamentos->findByHash($hash)->first();
		$sitantiga = $orcamento->status;
		$observacao = "O orçamento foi aprovado pelo cliente.";
		$orcamento->ipaprovacao = get_client_ip();
		$orcamento->navegadoraprovacao = VerificaNavegadorSO();
		$orcamento->status = C_OrcamentoStatusAprovado;

		if ($this->Orcamentos->save($orcamento)) {
			$this->Flash->success('O orçamento foi aprovado com sucesso!');
			$this->criarMov($orcamento->id, $sitantiga, C_OrcamentoStatusAprovado, $observacao, $orcamento->idempresa);
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $orcamento->id);
			// Email
				$empresa = $this->Empresas->get($orcamento->idempresa);
				// Mensagem 
					$url = $this->Config->get(1)->urlfora.'orcamentos/edit/'.$orcamento->id;
					
					$assunto = "Orçamento $orcamento->id aprovado!";
					$mensagem = "
						<h3> O orçamento $orcamento->id foi aprovado pelo cliente! </h3>
						<p> Para acessá-lo, <a href='$url'>clique aqui!</a> </p>
						<p> IP: $orcamento->ipaprovacao </p>
						<p> Navegador: $orcamento->navegadoraprovacao </p>
					";
				// Assinautra 
					if(empty($this->Auth->user('assinaturapgm'))) $message = $mensagem . '<hr> PGM' ;
					else {
						$img = '<img src="'.$this->Auth->user('assinaturapgm') .'" alt=""/>';
						$message = $mensagem . '<hr>' . $img;
					}
				// Empresa 
					if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
					else $nomeempresa = $empresa->razaosocial;
				//
				$email = new Email();
				$email->transport(((int)$orcamento->idempresa === (int)C_EmpresaMaster) ? 'master' : 'pgm');
				$from = 'helpdesk@pgm.inf.br';
				$email->from([$from => $nomeempresa]);
				$email->to($this->Config->get(1)->emailtickets)
				// $email->to('joaomario3224@gmail.com')
					->emailFormat('html')
					->subject($assunto);
	
				if($email->send($message)) $this->enviar($orcamento->id);
			//

		}
		else $this->Flash->error('Ocorreu um erro ao aprovar o orçamento.');

		if(!empty($this->Auth->user('id'))) return $this->redirect(['action' => 'view', $orcamento->id]);
		else return $this->redirect(['action' => 'viewhash', $orcamento->hash]);

		$this->set('title', 'Aprovar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function recusar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();

		if($orcamento->status != C_OrcamentoStatusPendente && $orcamento->status != C_OrcamentoStatusEnviado){
			$this->Flash->error('Você não possui permissão para imprimir este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusRecusado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusRecusado, $observacao);
				$this->Flash->success('O orçamento foi recusado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Recusar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function arquivar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();

		if($orcamento->status != C_OrcamentoStatusEnviado){
            $this->Flash->error('Você não possui permissão para arquivar este pedido, contate um administrador do sistema.');
			return $this->redirect(['action' => 'index']);
		}

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusArquivado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusArquivado, $observacao);
				$this->Flash->success('O orçamento foi arquivado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Arquivar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function enviar($id = null){
		$orcamento = $this->Orcamentos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'id' => $id]])->first();
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$sitantiga = $orcamento->status;
			if (isset($data['motivo'])) $observacao = $data['motivo'];
			else $observacao = null;

			$this->Orcamentos->patchEntity($orcamento, $data);
			$orcamento->status = C_OrcamentoStatusEnviado;

			if ($this->Orcamentos->save($orcamento)) {
				$this->criarMov($id, $sitantiga, C_OrcamentoStatusRecusado, $observacao);
				$this->Flash->success('O orçamento foi enviado com sucesso!');
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $id);
				return $this->redirect(['action' => 'edit', $id]);
			}
			$this->Flash->error('Ocorreu um erro ao editar o marcador.');
		}

		$this->set('title', 'Enviar Orçamento');
		$this->set('orcamento', $orcamento);
	}

	public function alterarsituacao($id = null) {
		$orcamento = $this->Orcamentos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'id' => $id])->first();

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			if (isset($data['motivo'])) {
				$motivo = $data['motivo'];
				$status = $data['status'];
				$sitantiga = $orcamento->status;

				if (!empty($motivo)) {
					$orcamento->status = $status;
					$orcamento->motivo = $motivo;

					if ($this->Orcamentos->save($orcamento)) {
						$this->criarMov($orcamento->id, $sitantiga, $status, $motivo);
						$this->Flash->success("Situação do orçamento alterada.");
						return $this->redirect(['action' => 'edit', $id]);
					}
				}
			}
		}
		
		$this->set('title', 'Alterar Situação');
		$this->set('orcamento', $orcamento);
	}

	public function email() {
		$this->autoRender = false;

		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$empresa = $this->Empresas->get($this->Auth->user('idempresa'));
			$orcamento = $this->Orcamentos->findById($data['idorcamento'])->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			// Link de acesso 
				$idorcamento = $data['idorcamento'];
				$url = $this->Config->get(1)->urlfora."orcamentos/view/$idorcamento/$orcamento->idempresa";
				$linkacesso = "<a href='$url'>Portal Web - Orçamentos</a>";
				$urlHash = $this->Config->get(1)->urlfora.'orcamentos/viewhash/'.$orcamento->hash;
				$linkacesso .= " ou se não possuir login, acesse <a href='$urlHash'>este link</a>";
			// Subistitui as tags 
				$mensagem = $empresa->orcamentomensagem;
				$mensagem = str_replace("#LINKACESSO#", $linkacesso, $empresa->orcamentomensagem);
				$assunto = str_replace("#NROORCAMENTO#", $idorcamento, $empresa->orcamentoassunto);
			// Assinautra 
				if(empty($this->Auth->user('assinaturapgm'))) $message = $mensagem . '<hr> PGM' ;
				else {
					$img = '<img src="'.$this->Auth->user('assinaturapgm') .'" alt=""/>';
					$message = $mensagem . '<hr>' . $img;
				}
			// Anexos 
				$arrayEmail = [];
				foreach($data['file'] as $reg) 
					if(!empty($reg['tmp_name']))
						$arrayEmail[$reg['name']] = ['file' => $reg['tmp_name']];
			// Empresa 
				$empresa = $this->Empresas->get($this->Auth->user('idempresa'));
				if(isset($empresa->nomefantasia)) $nomeempresa = $empresa->nomefantasia;
				else $nomeempresa = $empresa->razaosocial;
			//

			$destinatario = $data['emailemail'];
			$email = new Email();
			
			$email->transport(((int)$this->Auth->user('idempresa') === (int)C_EmpresaMaster) ? 'master' : 'pgm');
			$from = 'helpdesk@pgm.inf.br';

			$email->from([$from => $nomeempresa]);
			$email->attachments($arrayEmail)
				->to($destinatario)
				->emailFormat('html')
				->subject($assunto);

			if($email->send($message) ){
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $data['idorcamento']);
				$this->enviar($idorcamento);
			} else $this->Flash->success('Erro ao enviar e-mail.');
			return $this->redirect(['action' => 'edit', $data['idorcamento']]);
		}
	}

	public function novaordem($idorcamento) {
        $idempresa = $this->Auth->user('idempresa');

        $ultimo = $this->Ordensservico->find('all')->where(['idempresa' => $idempresa])->order(['id' => 'ASC'])->last();
        if(empty($ultimo)) {
            $_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . 1 . $this->Auth->user('id') . $idorcamento;
            $idcarrinhoorcamento = $idempresa . 1 . $this->Auth->user('id') . $idorcamento;
        } else{
            $idcarrinhoorcamento = $idempresa . $ultimo->id + 1 . $this->Auth->user('id') . $idorcamento;
            $_SESSION['PGM_Ordem_Idcarrinhoadd'] = $idempresa . $ultimo->id + 1 . $this->Auth->user('id') . $idorcamento;
        }

        $orcamento = $this->Orcamentos->findById($idorcamento)->where(['idempresa' => $idempresa])->first();
        
        // Se não achar o orçamento, aborta para não dar erro depois
        if (!$orcamento) {
            $this->Flash->error('Orçamento não encontrado.');
            return $this->redirect(['action' => 'index']);
        }

        $novaordem = $this->Ordensservico->newEntity();
        $novaordem->idcliente = $orcamento->idcliente;
        $novaordem->idempresa = $orcamento->idempresa;
        $novaordem->dataabertura = date('d/m-Y');
        $novaordem->contrato = C_OrdensContratoNao;
        $novaordem->prioridade = C_OrdensPrioridadeNormal;
        $novaordem->relato = $orcamento->relato;

        $areaObj = $this->Areas->find('all')->where([ 'LOWER(descricao)' => 'pendente'])->first();
        if ($areaObj) {
            $novaordem->idarea = $areaObj->id;
        } else {
            $areaQualquer = $this->Areas->find()->first();
            $novaordem->idarea = $areaQualquer ? $areaQualquer->id : null;
        }

        $problemaObj = $this->Problemas->find('all')->where([ 'LOWER(descricao)' => 'venda'])->first();
        if ($problemaObj) {
            $novaordem->idproblema = $problemaObj->id;
        } else {
            $problemaQualquer = $this->Problemas->find()->first();
            $novaordem->idproblema = $problemaQualquer ? $problemaQualquer->id : null;
        }
        // Combos
            $clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
            $clientesOpt = [];
            foreach($clientes as $reg){
                if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
                else $clientesOpt[$reg->id] = $reg->nome;
            }
            asort($clientesOpt);
            $areas = $this->Areas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where()->order(['descricao'])->toArray();
            $problemas = $this->Problemas->find('list', ['keyField' => 'id', 'valueField' => 'descricao'])->where()->order(['descricao'])->toArray();
            
            $produtosOpt = []; // Inicializa array vazio para evitar erro se não tiver produtos
            $produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $idempresa, 'ativo' => 1])->order(['codigo'])->toArray();
            foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];

            $this->set('produtosMobile', $produtosOpt);
            $this->set('produtosOpt', json_encode($produtosOpt, JSON_PRETTY_PRINT));
            $this->set('problemas', $problemas);
            $this->set('areas', $areas);
            $this->set('clientes', $clientesOpt);
            
        // Carrinho
            $idcarrinho = $this->Orcamentositens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idorcamento]])->first();
            
            if ($idcarrinho) {
                $carrinho = $this->Orcamentosservicos->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idorcamento' => $idcarrinho->iditem]])->order(['id'])->toArray();

                foreach($carrinho as $reg){
                    if($reg->virouitemordem === 0){
                        $pode = 'sim';
                        $produto = $this->Produtos->findByCodigo($reg->idproduto)->where(['idempresa' => $idempresa])->first();
                        if(empty($produto)) $this->set('temalgumsemproduto', true);
                        if($pode == 'sim'){
                            $itemordem = $this->Itensordem->newEntity();
                                $itemordem->idordempk = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
                                $itemordem->iditemorcamento = $reg->id;
                                $itemordem->idempresa = $reg->idempresa;
                                if(!empty($produto)){
                                    // debug($produto);
                                    $itemordem->unidade = $produto->unidade;
                                    $itemordem->tipo = $produto->tipo;
                                    $itemordem->codproduto =  $produto->codigo;
                                }else{
                                    $itemordem->tipo = C_ProdutosTipoProduto;
                                }
                                if($reg->tipo == 1){
                                    $itemordem->quantidade = 1;
                                    if($reg->valormensal > 0) $itemordem->valortotal = $reg->valormensal;
                                    else $itemordem->valortotal = $reg->valordoservico;
                                }else{
                                    $itemordem->quantidade = $reg->quantidade;
                                    if ($reg->valormensal > 0) $itemordem->valortotal = $reg->valormensal;
                                    else $itemordem->valortotal = $reg->valordoservico;
                                }
                                $itemordem->descricao =  $reg->servico;
                                $itemordem->observacao = $reg->observacao;
                                if ($reg->valormensal > 0) $itemordem->valorunitario = (float)$reg->valormensal / (float)$reg->quantidade;
                                else $itemordem->valorunitario = $reg->valoruni;
                                $itemordem->valordesconto = 0;

                            if($this->Itensordem->save($itemordem)){
                                $reg->virouitemordem = 1;
                                $this->Orcamentosservicos->save($reg);
                            }
                        }
                    }
                }
            }
        // 

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $ordem = $this->Ordensservico->newEntity();
            $ordem = $this->Ordensservico->patchEntity($ordem, $data);
            $ordem->idempresa = $idempresa;
            $ordem->iduser = $this->Auth->user('id');
            $ordem->situacao = C_OrdensSituacaoAberta;
            $ordem->valortotal = $data['valortotalordem'];
            $ordem->id = $this->Empresas->incrementOrdem($this->Auth->user('idempresa'));
            $ordem->idorcamento = $idorcamento;

            if ($this->Ordensservico->save($ordem)) {
                // Itens
                $carrinho = $this->Ordemservicositens->newEntity();
                    $carrinho->iditens = $_SESSION['PGM_Ordem_Idcarrinhoadd'];
                    $carrinho->idordem = $ordem->id;
                    $carrinho->idempresa = $idempresa;
                $this->Ordemservicositens->save($carrinho);
                unset($_SESSION['PGM_Ordem_Idcarrinhoadd']);
                // Movimentação
                // $this->Ordensservico->criarMov($ordem->id, C_OrdensSituacaoLiberadaParaFaturamento, C_OrdensSituacaoLiberadaParaFaturamento, $this->Auth->user('idempresa'), $this->Auth->user('id'), $idorcamento);
                
                // ao criar ela vai ficar em aberto e não em liberada para faturamento
                $this->Ordensservico->criarMov($ordem->id, C_OrdensSituacaoAberta, C_OrdensSituacaoAberta, $this->Auth->user('idempresa'), $this->Auth->user('id'), $idorcamento);

                $this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $ordem->id);
                $this->Flash->success(__('A ordem de serviço foi cadastrada com sucesso!'));
                return $this->redirect(['controller' => 'Ordensservico', 'action' => 'edit', $ordem->id]);
            }
            // Decrementa o último id em caso de erro
            $this->Empresas->decrementOrdem($this->Auth->user('idempresa'));
            $this->Flash->error(__('Não foi possível cadastrar a ordem de serviço.'));
        }

        $this->set('tiposMobile', C_ProdutosTipo);
        $this->set('tiposOpt', json_encode(C_ProdutosTipo, JSON_PRETTY_PRINT));
        $this->set('novaordem', $novaordem);
        $this->set('idorcamento', $idorcamento);
        $this->set('title', 'Nova Ordem de Serviço');
    }


	public function editaitemcarrinho() {
		$this->autoRender = false;
		
		if ($this->request->is('post')) {
			$data = $this->request->getData();
			$item = $this->Orcamentosservicos->find('all')
				->where([
					'AND' => [
						'idempresa' => $this->Auth->user('idempresa'), 
						'id' => $data['id']
					]
				])
				->first();
				
			if ($item) {
				function formatNumber($value) {
					if (empty($value)) return 0;
					$value = str_replace('.', '', $value);
					$value = str_replace(',', '.', $value);
					return floatval($value);
				}
				
				// Atualiza os dados do item
				$item->servico = $data['servico'];
				$item->quantidade = $data['quantidade'];
				$item->observacao = $data['observacao'];
				$item->valoruni = formatNumber($data['valoruni']);
				$item->valormensal = formatNumber($data['valormensal']);
				$item->valordoservico = formatNumber($data['valordoservico']);
				$item->idproduto = $data['idproduto'];
				$item->tipo = $data['tipo'];
				
				if ($this->Orcamentosservicos->save($item)) {
					echo 'success';
					return;
				}
			}
			
			echo 'error';
		}
	}
}