<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'TicketConstants.php');

// Fallback: define constants that may be missing from UserConstants.php
if (!defined('C_LocacaoStatusPendente'))   define('C_LocacaoStatusPendente',   1);
if (!defined('C_LocacaoStatusAprovado'))   define('C_LocacaoStatusAprovado',    2);
if (!defined('C_LocacaoStatusRejeitado'))  define('C_LocacaoStatusRejeitado',   3);
if (!defined('C_LocacaoStatusFinalizado')) define('C_LocacaoStatusFinalizado',  4);
if (!defined('C_LocacaoTipoDiaria'))       define('C_LocacaoTipoDiaria',        1);
if (!defined('C_LocacaoTipoSemanal'))      define('C_LocacaoTipoSemanal',       2);
if (!defined('C_LocacaoTipoQuinzenal'))    define('C_LocacaoTipoQuinzenal',     3);
if (!defined('C_LocacaoTipoMensal'))       define('C_LocacaoTipoMensal',        4);
if (!defined('C_LocacaoTipoArray'))        define('C_LocacaoTipoArray',         [1 => 'Diária', 2 => 'Semanal', 3 => 'Quinzenal', 4 => 'Mensal']);
if (!defined('C_RoleCliente'))             define('C_RoleCliente',              1);
if (!defined('C_ClientesTipoJuridica'))    define('C_ClientesTipoJuridica',     2);

class FaturasController extends AppController {
	public function initialize() {
		parent::initialize();
		$this->loadModel('Empresas'); 
		$this->loadModel('Clientes');
		$this->loadModel('Cidades'); 
		$this->loadModel('Estados'); 
		$this->loadModel('Faturasitens'); 
		$this->loadModel('Faturasrecibos'); 
		$this->loadModel('Produtos'); 
		$this->loadModel('Config');
		$this->loadModel('Ordensservico');
		$this->loadModel('FaturasOrdensServico');
	}

	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);
		if($event->_subject->request->params['action'] == 'imprimir' && $this->Auth->user('role') == 1){
			$fatura = $this->Faturas->get($event->_subject->request->params['pass'][0])->idcliente;
			$cliente = $this->Clientes->get($this->Auth->user('idcliente'))->id;
			
			if ($fatura != $cliente) {
				$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
				return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
			}
		}

		$this->Auth->allow(['view', 'aprovarhash', 'rejeitarhash']);	
	}

	public function index() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');

		if ($this->request->is('get')) {
			if ($this->Auth->user('role') == C_RoleCliente) {
				$idcliente = $this->Auth->user('idcliente');
				$faturas = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa, 'Faturas.idcliente' => $idcliente])
					->contain([
						'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				]);
			} else {
				$cliente = $this->request->getQuery('cliente');
				$faturas = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa])
					->contain([
						'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
				]);

				$faturasPendentes = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa, 'status' => C_LocacaoStatusPendente])
					->contain(['Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]]);
				$faturasAprovadas = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa, 'status' => C_LocacaoStatusAprovado])
					->contain(['Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]]);
				$faturasRejeitadas = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa, 'status' => C_LocacaoStatusRejeitado])
					->contain(['Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]]);
				$faturasFinalizadas = $this->Faturas->find('all')
					->where(['Faturas.idempresa' => $idempresa, 'status' => C_LocacaoStatusFinalizado])
					->contain(['Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']]]);
			}

			if (!empty($cliente)) {
				$faturas = $faturas->where(['Faturas.idcliente' => $cliente]);
				$faturasPendentes = $faturasPendentes->where(['Faturas.idcliente' => $cliente]);
				$faturasAprovadas = $faturasAprovadas->where(['Faturas.idcliente' => $cliente]);
				$faturasRejeitadas = $faturasRejeitadas->where(['Faturas.idcliente' => $cliente]);
				$faturasFinalizadas = $faturasFinalizadas->where(['Faturas.idcliente' => $cliente]);
			}
		}
		$faturas = $faturas->toArray();
		$faturasPendentes = $faturasPendentes->toArray();
		$faturasAprovadas = $faturasAprovadas->toArray();
		$faturasRejeitadas = $faturasRejeitadas->toArray();
		$faturasFinalizadas = $faturasFinalizadas->toArray();

		$clientesOpt1 = [0 => 'Todos'];
		
		$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $this->Auth->user('idempresa'), 'inativo' => 0])->order(['razaosocial'])->toArray();
		if(sizeof($clientes) > 0){
			foreach($clientes as $reg){
				if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
				else $clientesOpt[$reg->id] = $reg->nome;
			}
			asort($clientesOpt);
			foreach($clientesOpt as $key=>$reg) $clientesOpt1[$key] = $reg;
		}

		$this->set('cliente', $cliente);
		$this->set('clientes', $clientesOpt1);
		$this->set('faturas', $faturas);
		$this->set('faturasPendentes', $faturasPendentes);
		$this->set('faturasAprovadas', $faturasAprovadas);
		$this->set('faturasRejeitadas', $faturasRejeitadas);
		$this->set('faturasFinalizadas', $faturasFinalizadas);
		$this->set('title', 'Locações');
		
	}

	public function add() {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$idempresa = $this->Auth->user('idempresa');
		$fatura = $this->Faturas->newEntity();

		$idclientePrefill = $this->request->getQuery('idcliente');
		if ($this->request->is('get') && $idclientePrefill !== null && $idclientePrefill !== '') {
			$c = $this->Clientes->find()->where([
				'id' => $idclientePrefill,
				'idempresa' => $idempresa,
				'inativo' => 0,
			])->first();
			if ($c) {
				$fatura->idcliente = (int)$idclientePrefill;
			}
		}

		if ($this->request->is('get')) {
			$idosQ = $this->request->getQuery('idos');
			if ($idosQ === null || $idosQ === '') {
				unset($_SESSION['PGM_Prefat_Idos']);
			} else {
				$list = array_values(array_unique(array_filter(array_map('intval', preg_split('/[,\s;]+/', (string)$idosQ)))));
				$idcliExpect = (int)$this->request->getQuery('idcliente');
				$valid = [];
				foreach ($list as $oid) {
					if ($oid < 1) {
						continue;
					}
					$os = $this->Ordensservico->find()->where([
						'id' => $oid,
						'idempresa' => $idempresa,
						'situacao' => C_OrdensSituacaoLiberadaParaFaturamento,
					])->first();
					if (!$os) {
						continue;
					}
					if ($idcliExpect > 0 && (int)$os->idcliente !== $idcliExpect) {
						continue;
					}
					$valid[] = $oid;
				}
				$_SESSION['PGM_Prefat_Idos'] = $valid;
			}
		}

		if(empty($_SESSION['PGM_Ordem_Idcarrinhofaturaadd'])) {
			$prxId = $this->Empresas->prxFatura($this->Auth->user('idempresa'));
			$idcarrinho = $idempresa . $prxId . $this->Auth->user('id');
			$_SESSION['PGM_Ordem_Idcarrinhofaturaadd'] = $idcarrinho;
			$_SESSION['PGM_Ordem_Id_FaturaAdd'] = $prxId;
		} else {
			$idcarrinho = $_SESSION['PGM_Ordem_Idcarrinhofaturaadd'];
		}

		$fatura->nro = 'R'.$_SESSION['PGM_Ordem_Id_FaturaAdd'];

		if ($this->request->is('post')) {
			$data = $this->request->getData();

			$faturaExistente = $this->Faturas->find('all')->where(['nro' => $fatura->nro, 'idempresa' => $idempresa])->first();
			if(!empty($faturaExistente)) {
				$this->Flash->error(__('Já existe uma locação cadastrado com este número.'));
				return $this->redirect(['action' => 'add']);
			}

			$fatura = $this->Faturas->patchEntity($fatura, $data);
			$fatura->idempresa = $idempresa;
			$fatura->iduser = $this->Auth->user('id');
			$fatura->id = $this->Empresas->prxFatura($this->Auth->user('idempresa'));
			$fatura->valor = 0;
			$fatura->created = date('d/m/Y');
			$fatura->idcarrinho = $idcarrinho;
			$fatura->hash = $this->Faturas->hash();
			$fatura->status = C_LocacaoStatusPendente;
			$fatura->desconto = formatNumber($fatura->desconto);
			$fatura->outrosgastos = formatNumber($fatura->outrosgastos);

			$carrinho = $this->Faturasitens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idcarrinho' => $idcarrinho]])->order(['id'])->toArray();
			foreach($carrinho as $reg) $fatura->valor += $reg->valortotal;

			if ($this->Faturas->save($fatura)) {
				foreach($carrinho as $reg) {
					$reg->idfatura = $fatura->id;
					$this->Faturasitens->save($reg);
				}
				$this->_persistPrefatOsLinks((int)$idempresa, (int)$fatura->id, (int)$fatura->idcliente);
				unset($_SESSION['PGM_Prefat_Idos']);
				unset($_SESSION['PGM_Ordem_Idcarrinhofaturaadd']);
				$this->Empresas->incrementFatura($this->Auth->user('idempresa'));
				$this->Flash->success(__('A locação foi cadastrada com sucesso!'));
				return $this->redirect(['action' => 'edit', $fatura->id]);
			} else {
				// Decrementa o último id em caso de erro
				$this->Empresas->decrementFatura($this->Auth->user('idempresa'));
				$this->Flash->error(__('Não foi possível cadastrar a locação.'));
			}
		}

		// Combos 
			// Clientes 
				$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
				$clientesOpt = [];
				foreach($clientes as $reg){
					if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
					else $clientesOpt[$reg->id] = $reg->nome;
				}
				asort($clientesOpt);
			// Cidades 
				$cidades = $this->Cidades->find('list', ['keyField' => 'nome', 'valueField' => 'nome'])->order(['nome'])->toArray();
			// Produtos 
				$produtosOpt = [0 => 'Código'];
				$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['tipo', 'codigo'])->toArray();
				foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->codigo . ' - ' . $reg->descricao;
			// 
		// 

		$this->set('produtosOpt', $produtosOpt);
		$this->set('cidades', $cidades);
		$this->set('idcarrinho', $idcarrinho);
		$this->set('clientes', $clientesOpt);
		$this->set('fatura', $fatura);
		$this->set('title', 'Cadastro de locação');
	}

	public function edit($id = null, $aba = 1) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$idempresa = $this->Auth->user('idempresa');
		$fatura = $this->Faturas->find('all')->where(['id' => $id, 'idempresa' => $idempresa])->first();

		if ($idempresa != $fatura->idempresa) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'locacao', 'action' => 'index']);
		}

		$prefatOsIds = [];
		try {
			$__rows = $this->FaturasOrdensServico->find()
				->select(['idordem'])
				->where(['idfatura' => $id, 'idempresa' => $idempresa])
				->order(['idordem' => 'ASC'])
				->toArray();
			foreach ($__rows as $__r) {
				$prefatOsIds[] = (int)$__r->idordem;
			}
		} catch (\Throwable $e) {
			$prefatOsIds = [];
		}
		$this->set('prefatOsIds', $prefatOsIds);
		
		$fatura->created = date_format($fatura->created, 'd/m/Y');
		$fatura->vencimento = date_format($fatura->vencimento, 'd/m/Y');
		$fatura->dtemissao = date_format($fatura->dtemissao, 'd/m/Y');
		$fatura->dtretorno = date_format($fatura->dtretorno, 'd/m/Y');
		$fatura->dtdevolucao = date_format($fatura->dtdevolucao, 'd/m/Y');
		$fatura->desconto = number_format($fatura->desconto, 2, ',', '.');
		$fatura->outrosgastos = number_format($fatura->outrosgastos, 2, ',', '.');

		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();

			$fatura = $this->Faturas->patchEntity($fatura, $data);

			$fatura->desconto = formatNumber($fatura->desconto);
			$fatura->outrosgastos = formatNumber($fatura->outrosgastos);

			if ($this->Faturas->save($fatura)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $fatura->id);
				$this->Flash->success(__('O contrato foi salvo com sucesso!.'));
				return $this->redirect(['action' => 'edit', $fatura->id]);
			}
			$this->Flash->error(__('Não foi possível salvar o contrato.'));
		}

		// Combos 
			// Clientes 
				$clientes = $this->Clientes->find('all', ['keyField' => 'id', 'valueField' => 'razaosocial'])->where(['idempresa' => $idempresa, 'inativo' => 0])->order(['razaosocial'])->toArray();
				$clientesOpt = [];
				foreach($clientes as $reg){
					if($reg->tipo == C_ClientesTipoJuridica) $clientesOpt[$reg->id] = $reg->razaosocial;
					else $clientesOpt[$reg->id] = $reg->nome;
				}
				asort($clientesOpt);
			// Cidades 
				$cidades = $this->Cidades->find('list', ['keyField' => 'nome', 'valueField' => 'nome'])->order(['nome'])->toArray();
			// Produtos 
				$produtosOpt = [0 => 'Código'];
				$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['tipo', 'codigo'])->toArray();
				foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->codigo . ' - ' . $reg->descricao;
			// 
		// Recibos 
			$recibos = $this->Faturasrecibos->findByIdfatura($id)->where(['idempresa' => $idempresa])->order(['nro ASC'])->toArray();
		// 

		$config = $this->Config->get(1);

		$linkCompartilhar = $config->urlfora . '/locacao/view/' . $fatura->hash;

		$this->set('linkCompartilhar', $linkCompartilhar);
		$this->set('cidades', $cidades);
		$this->set('clientes', $clientesOpt);
		$this->set('fatura', $fatura);
		$this->set('produtosOpt', $produtosOpt);
		$this->set('aba', $aba);
		$this->set('recibos', $recibos);
		$this->set('title', 'Editar locação');
	}
	
	public function isAuthorized($user) {
		// Usa a mesma regra padrão do AppController (inclui verificação de prefixo admin)
		return parent::isAuthorized($user);
	}

	public function carrinho($idcarrinho = null){
		if($idcarrinho == null) $idcarrinho = $_SESSION['PGM_Ordem_Idcarrinhofaturaadd'];
		$carrinho = $this->Faturasitens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idcarrinho' => $idcarrinho]])->order(['id'])->toArray();
		$valorTotal = 0;
		foreach($carrinho as $reg) $valorTotal += $reg->valortotal;

		$this->set('carrinho', $carrinho);
		$this->set('idcarrinho', $idcarrinho);
		$this->set('valorTotal', $valorTotal);
	}

	public function carrinhoedit($idfatura = null){
		$carrinho = $this->Faturasitens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idfatura' => $idfatura])->order(['id'])->toArray();
		$fatura = $this->Faturas->find('all')->where(['id' => $idfatura, 'idempresa' => $this->Auth->user('idempresa')])->first();
		$valorTotal = 0;
		foreach($carrinho as $reg) $valorTotal += $reg->valortotal;
		$this->set('fatura', $fatura);
		$this->set('carrinho', $carrinho);
		$this->set('valorTotal', $valorTotal);
	}

	public function additem($idfatura = null){
		$this->autoRender = false;
		$data = $this->request->getData();
		
		if(empty($idfatura)) {
			$idcarrinho = $_SESSION['PGM_Ordem_Idcarrinhofaturaadd'];
		} else {
			$fatura = $this->Faturas->findById($idfatura)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
			$idcarrinho = $fatura->idcarrinho;
		}

		$carrinho = $this->Faturasitens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idcarrinho' => $idcarrinho]])->order(['id'])->toArray();

		foreach($carrinho as $reg) {
			if($reg->codigo == $data['codigo']) {
				return $this->jsonResponse(['msg' => 'Já existe um item com este código no carrinho.'], 400);
			}
		}

		$item = $this->Faturasitens->newEntity();
		$item->codigo = $data['codigo'];
		$item->descricao = $data['descricao'];
		$item->quantidade = $data['quantidade'];
		$item->valoritem = formatNumber($data['valoritem']);
		$item->valortotal = formatNumber($data['valortotal']);
		$item->idcarrinho = $idcarrinho;
		$item->idfatura = $idfatura;
		$item->idempresa = $this->Auth->user('idempresa');

		if(!empty($idfatura)) {
			$fatura->valor += $item->valortotal;
			$this->Faturas->save($fatura);
		}

		if($this->Faturasitens->save($item)) return $this->jsonResponse(['msg' => 'Item cadastrado com sucesso.'], 200);
		else return $this->jsonResponse(['msg' => 'Ocorreu um erro ao cadastrar o item, tente novamente.'], 400);
	}

	public function edititem(){
		$this->autoRender = false;
		$data = $this->request->getData();

		$item = $this->Faturasitens->findById($data['id'])->where(['idempresa' => $this->Auth->user('idempresa')])->first();

		$item->codigo = $data['codigo'];
		$item->descricao = $data['descricao'];
		$item->quantidade = $data['quantidade'];
		$item->valoritem = formatNumber($data['valoritem']);
		$item->valortotal = formatNumber($data['valortotal']);

		if($this->Faturasitens->save($item)) {
			// Valor total da locação
				$carrinho = $this->Faturasitens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idcarrinho' => $item->idcarrinho]])->order(['id'])->toArray();
				$fatura = $this->Faturas->findById($item->idfatura)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
				$fatura->valor = 0;
				foreach($carrinho as $reg) $fatura->valor += $reg->valortotal;
				$this->Faturas->save($fatura);
			// 
			return $this->jsonResponse(['msg' => 'Item salvo com sucesso.'], 200);
		}
		else return $this->jsonResponse(['msg' => 'Não foi possível salvar o item, tente novamente.'], 400);

	}

	public function viewitem($iditem){
		$item = $this->Faturasitens->findById($iditem)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
		$item->valoritem = number_format($item->valoritem, 2, ',', '.');
		$item->valortotal = number_format($item->valortotal, 2, ',', '.');
		$item->adicionalcobrado = number_format($item->adicionalcobrado, 2, ',', '.');

		$this->set('item', $item);
	}
	
	public function deleteitem($id){
		$item = $this->Faturasitens->findById($id)->first();
		if($this->Faturasitens->delete($item)) return $this->jsonResponse(['msg' => 'Item excluído com sucesso.'], 200);
		else return $this->jsonResponse(['msg' => 'Ocorreu um erro ao excluir o item, tente novamente.'], 400);
	}

	public function limpacarrinho($idcarrinho = null){
		if($idcarrinho == null) $idcarrinho = $_SESSION['PGM_Ordem_Idcarrinhofaturaadd'];
		$carrinho = $this->Faturasitens->find('all')->where(['AND' => ['idempresa' => $this->Auth->user('idempresa'), 'idcarrinho' => $idcarrinho]])->order(['id'])->toArray();

		foreach($carrinho as $item) {
			$this->Faturasitens->delete($item);
		}

		return $this->jsonResponse(['msg' => 'Carrinho limpo com sucesso.'], 200);
	}

	public function imprimir($id = null) {
		$this->viewBuilder()->setLayout("print");
		$empresa = $this->Empresas->findById($this->Auth->user('idempresa'))->contain(['Cidades', 'Cidades.Estados'])->first();
		$idcliente = $this->Auth->user('idcliente');
		$fatura = $this->Faturas->find('all', ['contain' => ['Clientes', 'Clientes.Cidades', 'Clientes.Cidades.Estados']])->where(['Faturas.id' => $id, 'Faturas.idempresa' => $empresa->id])->first();

		if(empty($fatura->outrosgastos)) $fatura->outrosgastos = 0;
		if(empty($fatura->outrosgastos)) $fatura->desconto = 0;

		$fatura->valor = $fatura->valor + $fatura->outrosgastos - $fatura->desconto;

		if ($empresa->id != $fatura->idempresa) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'locacao', 'action' => 'index']);
		}

		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$carrinho = $this->Faturasitens->findByIdfatura($id)->where(['idempresa' => $empresa->id])->order(['codigo'])->toArray();

		$this->set('carrinho', $carrinho);
		$this->set('idcliente', $idcliente);
		$this->set('empresaObj', $empresa);
		$this->set('fatura', $fatura);
		$this->set('title', 'Imprimir contrato');
	}

	public function view($hash = null) {
		$this->viewBuilder()->setLayout("print");
		$fatura = $this->Faturas->find('all', ['contain' => ['Clientes', 'Clientes.Cidades', 'Clientes.Cidades.Estados']])->where(['Faturas.hash' => $hash])->first();
		if(empty($fatura)) {
			$this->Flash->error('Não foi encontrada uma fatura com este código, entre em contato com o administrador.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		$empresa = $this->Empresas->findById($fatura->idempresa)->contain(['Cidades', 'Cidades.Estados'])->first();

		if(empty($fatura->outrosgastos)) $fatura->outrosgastos = 0;
		if(empty($fatura->outrosgastos)) $fatura->desconto = 0;
		$fatura->valor = $fatura->valor + $fatura->outrosgastos - $fatura->desconto;

		if ($empresa->id != $fatura->idempresa) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'locacao', 'action' => 'index']);
		}

		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$carrinho = $this->Faturasitens->findByIdfatura($fatura->id)->where(['idempresa' => $empresa->id])->order(['codigo'])->toArray();

		$this->set('carrinho', $carrinho);
		$this->set('idcliente', $fatura->idcliente);
		$this->set('empresaObj', $empresa);
		$this->set('fatura', $fatura);
		$this->set('title', 'Imprimir contrato');
	}

	public function aprovarhash($hash = null) {
		$fatura = $this->Faturas->find('all')->where(['Faturas.hash' => $hash])->first();
		$fatura->status = C_LocacaoStatusAprovado;
		$this->Faturas->save($fatura);
		return $this->redirect(['controller' => 'faturas', 'action' => 'view', $hash]);
	}

	public function rejeitarhash($hash = null) {
		$fatura = $this->Faturas->find('all')->where(['Faturas.hash' => $hash])->first();
		$fatura->status = C_LocacaoStatusRejeitado;
		$this->Faturas->save($fatura);
		return $this->redirect(['controller' => 'faturas', 'action' => 'view', $hash]);
	}

	public function aprovar($id = null) {
		$fatura = $this->Faturas->find('all')->where(['Faturas.id' => $id])->first();
		$fatura->status = C_LocacaoStatusAprovado;
		$this->Faturas->save($fatura);
		return $this->redirect(['controller' => 'faturas', 'action' => 'edit', $id]);
	}

	public function rejeitar($id = null) {
		$fatura = $this->Faturas->find('all')->where(['Faturas.id' => $id])->first();
		$fatura->status = C_LocacaoStatusRejeitado;
		$this->Faturas->save($fatura);
		return $this->redirect(['controller' => 'faturas', 'action' => 'edit', $id]);
	}

	public function receber($idfatura) {
		if ($this->Auth->user('role') == C_RoleCliente) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}
		
		$idempresa = $this->Auth->user('idempresa');
		$fatura = $this->Faturas->find('all')->where(['id' => $idfatura, 'idempresa' => $idempresa])->first();

		if ($idempresa != $fatura->idempresa) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'locacao', 'action' => 'index']);
		}

		$recibo = $this->Faturasrecibos->newEntity();
		$recibo->valorpago = number_format($fatura->valor + $fatura->outrosgastos - $fatura->desconto, 2, ",", ".");
		$fatura->valor = number_format($fatura->valor + $fatura->outrosgastos - $fatura->desconto, 2, ",", ".");

		if ($this->request->is('post')) {
			$data = $this->request->getData();

			$ultimoRecibo = $this->Faturasrecibos->findByIdfatura($idfatura)->where(['idempresa' => $idempresa])->order('id DESC')->select(['nro'])->first();

			$recibo = $this->Faturasrecibos->patchEntity($recibo, $data);
			$recibo->idempresa = $idempresa;
			$recibo->idfatura = $idfatura;
			$recibo->nro = empty($ultimoRecibo) ? 1 : $ultimoRecibo->nro + 1;
			$recibo->desconto = formatNumber($recibo->desconto);
			$recibo->juros = formatNumber($recibo->juros);
			$recibo->valorpago = formatNumber($recibo->valorpago);

			if ($this->Faturasrecibos->save($recibo)) {
				$this->Flash->success(__('Recibo gerado!'));
			} else {
				$this->Flash->error(__('Não foi possível gerar o recibo.'));
			}
			return $this->redirect(['action' => 'edit', $fatura->id, 2]);
		}

		$this->set('recibo', $recibo);
		$this->set('fatura', $fatura);
		$this->set('title', 'Receber');
	}

	public function recibo($id = null) {
		$this->viewBuilder()->setLayout("print");
		$empresa = $this->Empresas->findById($this->Auth->user('idempresa'))->contain(['Cidades', 'Cidades.Estados'])->first();
		$idcliente = $this->Auth->user('idcliente');
		$recibo = $this->Faturasrecibos->find('all')->where(['id' => $id])->first();
		$fatura = $this->Faturas->find('all', ['contain' => ['Clientes', 'Clientes.Cidades', 'Clientes.Cidades.Estados']])->where(['Faturas.id' => $recibo->idfatura, 'Faturas.idempresa' => $empresa->id])->first();

		if(empty($fatura->outrosgastos)) $fatura->outrosgastos = 0;
		if(empty($fatura->outrosgastos)) $fatura->desconto = 0;
		$fatura->valor = $fatura->valor + $fatura->outrosgastos - $fatura->desconto;

		if ($empresa->id != $fatura->idempresa) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'locacao', 'action' => 'index']);
		}

		if ($this->Auth->user('role') == 1) {
			$this->Flash->error('Você não possui permissão para realizar esta ação, contate um administrador do sistema.');
			return $this->redirect(['controller' => 'users', 'action' => 'dashboard']);
		}

		$this->set('idcliente', $idcliente);
		$this->set('empresaObj', $empresa);
		$this->set('fatura', $fatura);
		$this->set('recibo', $recibo);
		$this->set('title', 'Imprimir recibo');
	}

	public function devolveritem($id) {
		$item = $this->Faturasitens->findById($id)->where(['idempresa' => $this->Auth->user('idempresa')])->first();

		$fatura = $this->Faturas->findById($item->idfatura)->where(['idempresa' => $this->Auth->user('idempresa')])->first();
		if(!empty($item->dataretorno)) $item->dataretorno = date_format($item->dataretorno, 'd/m/Y');
		
		if ($this->request->is(['post', 'put'])) {
			$data = $this->request->getData();
			$item = $this->Faturasitens->patchEntity($item, $data);
			if($item->qtddevolvida > $item->quantidade) $item->qtddevolvida = $item->quantidade;

			if($this->Faturasitens->save($item)) {
				$this->Flash->success(__('Item devolvido!'));

				$carrinho = $this->Faturasitens->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'idfatura' => $item->idfatura])->order(['id'])->toArray();
				$bFinalizado = true;
				foreach($carrinho as $reg) {
					if($reg->qtddevolvida < $reg->quantidade) $bFinalizado = false;
				}
				if($bFinalizado == true) {
					$fatura->status = C_LocacaoStatusFinalizado;
					$this->Faturas->save($fatura);
					$this->Flash->success(__('Fatura finalizada!'));
				}
			} else {
				$this->Flash->error(__('Não foi possível devolver o item.'));
			}
			return $this->redirect(['action' => 'edit', $fatura->id]);
		}
		$this->set('title', 'Devolução');
		$this->set('item', $item);
		$this->set('fatura', $fatura);
	}

	/**
	 * Grava vínculos pré-faturamento → fatura (tabela `faturas_ordens_servico` após migração).
	 */
	protected function _persistPrefatOsLinks($idempresa, $idfatura, $idcliente) {
		$ids = isset($_SESSION['PGM_Prefat_Idos']) && is_array($_SESSION['PGM_Prefat_Idos']) ? $_SESSION['PGM_Prefat_Idos'] : [];
		if ($idfatura < 1 || $idcliente < 1 || empty($ids)) {
			return;
		}
		foreach ($ids as $idordem) {
			$idordem = (int)$idordem;
			if ($idordem < 1) {
				continue;
			}
			$os = $this->Ordensservico->find()->where([
				'id' => $idordem,
				'idempresa' => $idempresa,
				'idcliente' => $idcliente,
				'situacao' => C_OrdensSituacaoLiberadaParaFaturamento,
			])->first();
			if (!$os) {
				continue;
			}
			$dup = $this->FaturasOrdensServico->find()->where([
				'idempresa' => $idempresa,
				'idfatura' => $idfatura,
				'idordem' => $idordem,
			])->first();
			if ($dup) {
				continue;
			}
			try {
				$row = $this->FaturasOrdensServico->newEntity([
					'idfatura' => $idfatura,
					'idordem' => $idordem,
					'idempresa' => $idempresa,
				]);
				$this->FaturasOrdensServico->save($row);
			} catch (\Throwable $e) {
				$this->log('[Prefat] vínculo fatura/os: ' . $e->getMessage(), 'warning');
			}
		}
	}
}