<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Utility\ErpGridUrl;
use Cake\Event\Event;
use CakeSoap\Network\CakeSoap;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . '/queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/queencitycodefactory/cakesoap/src/Network/CakeSoap.php';


class ProdutosController extends AppController {
	public function initialize() {
        parent::initialize();
		$this->loadModel('Empresas');
		$this->loadModel('Config');
    }
    
    public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		$this->set('title', 'Produtos');
		$this->Auth->allow(['addApi', 'listApi']);

		if ($this->Auth->user('role') == C_RoleCliente) {
            $this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
	}

	public function index() {
		$this->set('title', 'Produtos e Serviços');
        $produtos = $this->Produtos->findByTipo(1)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);

		try {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) { }
	
		$response = $soap->sendRequest('GetEstoqueProdutos', [
			'Data' => [
				'iFilial' => C_Filial,
				'sChave' => C_ChaveAcesso,
				'bApenasComSaldo' => false,
				'sCodProduto' => null,
				'sDescricao' => null,
			]
		]);
		if(!is_array($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) $response->GetEstoqueProdutosResult->tWsProdutosEstoque = [$response->GetEstoqueProdutosResult->tWsProdutosEstoque];
		foreach($response->GetEstoqueProdutosResult->tWsProdutosEstoque as $produto) $produtosWs[$produto->sCodProduto] = $produto;

		foreach($produtos as $produto) {
			if(isset($produtosWs[$produto->codigo])) {
				$produto->nPrecoVenda = $produtosWs[$produto->codigo]->nPrecoVenda;
				$produto->nPrecoCusto = $produtosWs[$produto->codigo]->nPrecoCusto;
				$produto->nQtdeAtual = $produtosWs[$produto->codigo]->nQtdeAtual;
			}
		}

        $servicos = $this->Produtos->findByTipo(2)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();
        $contratos = $this->Produtos->findByTipo(3)->where(['idempresa' => $this->Auth->user('idempresa')])->toArray();
        
		$this->set('contratos', $contratos);
		$this->set('produtos', $produtos);
		$this->set('servicos', $servicos);
	}

	public function add() {
        $produto = $this->Produtos->newEntity();
        
		if ($this->request->is('post')) {
            $data = $this->request->getData();
            $produto = $this->Produtos->patchEntity($produto, $data);
            $produto->idempresa = $this->Auth->user('idempresa');
            $produto->vlunitario = str_replace('.', '', $data['vlunitario']);
            $produto->vlunitario = str_replace(',', '.', $produto->vlunitario);
            if ($this->Produtos->save($produto)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);

                if($produto->tipo == 1)  $this->Flash->success(__('O produto foi cadastrado com sucesso!.'));
                else  $this->Flash->success(__('O serviço foi cadastrado com sucesso!.'));
                return $this->redirect(['action' => 'edit', $produto->id]);
            }
            $this->Flash->error(__('Não foi possível cadastrar o produto/serviço.'));
        }

		$this->set('produto', $produto);
		$this->set('title', 'Cadastro de Produtos e Serviços');
	}

	public function edit($id = null) {
        $produto = $this->Produtos->get($id);
        $produto->vlunitario = number_format($produto->vlunitario, 2, ",", ".");
        
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
            $produto = $this->Produtos->patchEntity($produto, $data);
            $produto->vlunitario = str_replace('.', '', $data['vlunitario']);
			$produto->vlunitario = str_replace(',', '.', $produto->vlunitario);
            if ($this->Produtos->save($produto)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);
                if($produto->tipo == 1)  $this->Flash->success(__('O produto foi salvo com sucesso!.'));
                else  $this->Flash->success(__('O serviço foi salvo com sucesso!.'));

                return $this->redirect(['action' => 'edit', $id]);
            }
            if($produto->tipo == 1)  $this->Flash->error(__('Não foi possível salvar o produto.'));
            else  $this->Flash->error(__('Não foi possível salvar o serviço.'));
        }
        
		$this->set('produto', $produto);
        $this->set('title', 'Editar Produto');
    }

	public function isAuthorized($user) {
		return true;
	}

	public function delete($id = null) {
		$produto = $this->Produtos->get($id);

		if ($this->Produtos->delete($produto)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $produto->id);
			if($produto->tipo == 1)  $this->Flash->success(__('O produto foi deletado com sucesso!.'));
			else $this->Flash->success('O serviço foi deletado com sucesso!');
			return $this->redirect(['action' => 'index']);
		}
    }
	
	public function produtostipo($tipo){
		$this->autoRender = false;
		error_reporting(0);
		$produtosOpt1 = $this->Produtos->find('all')->where(['tipo' => $tipo, 'idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[] = ['codigo' => trim($reg->codigo), 'descricao' => trim($reg->descricao).' ('.trim($reg->codigo).')'];
		//echo json_encode($produtosOpt, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		return $this->jsonResponse($produtosOpt, 200);
    }

    public function produto($codigo, $tipo = null){
		$this->autoRender = false;
		//echo json_encode($this->Produtos->findByCodigo($codigo)->first(), JSON_PRETTY_PRINT);
		return $this->jsonResponse($this->Produtos->findByCodigo($codigo)->where(['idempresa' => $this->Auth->user('idempresa')])->first(), 200);
    }

    public function listAPI() {
        $this->autoRender = false;
        if ($this->request->is('get')) {
			$empresa = $this->request->getHeaderLine('empresa');
            $token = $this->request->getHeaderLine('token');
            $codigo = $this->request->getHeaderLine('codigo');

			if(empty($token) || empty($empresa)) 
			return $this->jsonResponse(['mensagem' => 'Parâmetros da requisição inválidos'], 400);
			
			if(empty($this->Empresas->findById($empresa)->first())) return $this->jsonResponse(['mensagem' => 'Parâmetros da requisição inválidos'], 400);
			if($token == $this->Empresas->get($empresa)->token){
				if(!empty($codigo)){
					$produto = $this->Produtos->findByCodigo($codigo)->where(['idempresa' => $empresa])->toArray(); 
					if ($produto == null) return $this->jsonResponse(['mensagem' => 'Não foi encontrado um produto com o código '. $codigo], 404);
				}else $produto = $this->Produtos->find('all')->where(['idempresa' => $empresa])->toArray(); 
				foreach($produto as $reg) {
					unset($reg->id);
					unset($reg->idempresa);
				}
				return $this->jsonResponse($produto, 200);
			}else return $this->jsonResponse(['mensagem' => 'Autenticação Inválida'], 401);
        }
    }

    public function addAPI() {
        $this->autoRender = false;
        if ($this->request->is('post')) {
			$empresa = $this->request->getHeaderLine('empresa');
            $token = $this->request->getHeaderLine('token');
			$json = $this->request->input('json_decode');

			if(empty($token) || empty($empresa) || empty($json)) 
			return $this->jsonResponse(['mensagem' => 'Objeto ou parâmetros inválidos'], 400);

			if(empty($this->Empresas->findById($empresa)->first())) return $this->jsonResponse(['mensagem' => 'Objeto ou parâmetros inválidos'], 400);
			if($token == $this->Empresas->get($empresa)->token){
				$produto = $this->Produtos->findByCodigo(trim($json->codigo))->where(['idempresa' => $empresa])->first(); 
				if (empty($produto)) $produto = $this->Produtos->newEntity();
			
				$produto->idempresa = $empresa;
				$produto->codigo = trim($json->codigo);
				$produto->descricao = trim($json->descricao);
				$produto->unidade = $json->unidade;
				$produto->vlunitario = $json->vlunitario;
				$produto->tipo = $json->tipo;;
				$produto->ativo = $json->ativo;

                if($this->Produtos->save($produto)) return $this->jsonResponse(['mensagem' => 'Produto cadastrado com sucesso'], 201);
                else return $this->jsonResponse(['mensagem' => 'Objeto ou parâmetros inválidos'], 400);
			}else{
				return $this->jsonResponse(['mensagem' => 'Autenticação Inválida'], 401);
			}
        }
	}

	public function qtdestoque($produto) {
		error_reporting(0);
		$this->autoRender = false;

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);

		try {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			return $this->jsonResponse(-999, 200);
		}
		
		$response = $soap->sendRequest('GetProdutoEstoque', [
			'Data' => [
				'iFilial' => C_Filial,
				'sChave' => C_ChaveAcesso,
				'sProduto' => $produto,
			]
		]);

		//echo $response->GetProdutoEstoqueResult;
		return $this->jsonResponse($response->GetProdutoEstoqueResult, 200);
       
	}

	public function serialnumberproduto($produto) {
		error_reporting(0);
		$this->autoRender = false;

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);
		try {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			return $this->jsonResponse([], 200);
		}
        
        $response = $soap->sendRequest('GetSerialNumberProduto', [
            'Data' => [
                'iFilial' => C_Filial,
                'sChave' => C_ChaveAcesso,
                'sProduto' => $produto,
                'bApenasDisponiveis' => true,
            ]
        ]);
		
		// echo json_encode($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber, JSON_PRETTY_PRINT);
		if(!is_array($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber)) $response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber = array($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber);
		return $this->jsonResponse($response->GetSerialNumberProdutoResult->tWsProdutoSerialNumber, 200);
	}

	public function estoque($opt = null) {
		error_reporting(0);
		if($opt == 't') $bApenasComSaldo = true;
		else $bApenasComSaldo = false;
		$sCodProduto = null;
		$sDescricao = null;

		if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();
			$sCodProduto = $data['sCodProduto'];
			$sDescricao = $data['sDescricao'];
			$opt = 't';
        }

		$soapprodutos = ErpGridUrl::wsdl($this->Empresas->get($this->Auth->user('idempresa'))->urlerp);
		try {
			$soap = new CakeSoap(['wsdl' => $soapprodutos]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			$this->Flash->error(__('O WS não pode ser acessado no momento!.'));
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		}

		if($sCodProduto == 0) $sCodProduto = null;
	
		$response = $soap->sendRequest('GetEstoqueProdutos', [
			'Data' => [
				'iFilial' => C_Filial,
				'sChave' => C_ChaveAcesso,
				'bApenasComSaldo' => $bApenasComSaldo,
				'sCodProduto' => $sCodProduto,
				'sDescricao' => $sDescricao,
			]
		]);

		if(!is_array($response->GetEstoqueProdutosResult->tWsProdutosEstoque)) $response->GetEstoqueProdutosResult->tWsProdutosEstoque = [$response->GetEstoqueProdutosResult->tWsProdutosEstoque];

		$produtos = $response->GetEstoqueProdutosResult->tWsProdutosEstoque;
		usort( $produtos,
			function( $a, $b ) {
				if( $a->sDescProduto == $b->sDescProduto  ) return 0;
				return ( ( $a->sDescProduto  < $b->sDescProduto ) ? -1 : 1 );
			}
		);

		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';

		$this->set('sCodProduto', $sCodProduto);
		$this->set('produtosOpt', $produtosOpt);
		$this->set('bApenasComSaldo', $bApenasComSaldo);
		$this->set('produtos', $produtos);
        $this->set('title', 'Produtos em Estoque');
	}

	public function pesquisar() {
        $this->autoRender = false;
        $termo = $this->request->getQuery('termo');
        $idEmpresa = $this->Auth->user('idempresa');
        $query = $this->Produtos->find()
            ->select(['codigo', 'descricao', 'vlunitario', 'unidade', 'tipo'])
            ->where(['idempresa' => $idEmpresa, 'ativo' => 1]);
    
        if (!empty($termo)) {
            $termo = trim($termo);
            $termoMin = mb_strtolower($termo);
            $query->where([
                'OR' => [
                    'LOWER(descricao) LIKE' => '%' . $termoMin . '%',
                    'codigo LIKE' => '%' . $termo . '%'
                ]
            ]);
        }
        $produtos = $query
            ->limit(25)
            ->order(['descricao' => 'ASC']) 
            ->toArray();
            
        return $this->jsonResponse($produtos, 200);
    }
}



