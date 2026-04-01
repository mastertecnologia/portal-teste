<?php
namespace App\Controller;

use App\Controller\AppController;
use App\Service\ClienteDomain\ClienteDomainBridge;
use App\Utility\ClienteDomainEventType;
use Cake\Event\Event;
use Cake\Routing\Router;

require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'Utilities.php');
require_once (ROOT . DS . 'vendor' . DS  . 'PGMPackages' . DS . 'UserConstants.php');
require_once (ROOT . DS . 'vendor' . DS  . '/queencitycodefactory/cakesoap/src/Network/CakeSoap.php');

//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/Utilities.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/PGMPackages/UserConstants.php';
//require_once $_SERVER['DOCUMENT_ROOT'].'/portal/vendor/queencitycodefactory/cakesoap/src/Network/CakeSoap.php';

use CakeSoap\Network\CakeSoap;

class ClicontratosController extends AppController {
	public function initialize() {
        parent::initialize();
		$this->loadModel('Empresas');
		$this->loadModel('Config');
		$this->loadModel('Produtos');
		$this->loadModel('Clientes');
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

	public function add($idcliente = null) {
        $contrato = $this->Clicontratos->newEntity();
        
		if ($this->request->is('post')) {
            $data = $this->request->getData();

			$data['vlunit'] = str_replace('.', '', $data['vlunit']);
			$data['vlunit'] = str_replace(',', '.', $data['vlunit']);

			$data['vltotal'] = str_replace('.', '', $data['vltotal']);
			$data['vltotal'] = str_replace(',', '.', $data['vltotal']);

            $contrato = $this->Clicontratos->patchEntity($contrato, $data);
            $contrato->idempresa = $this->Auth->user('idempresa');

            if ($this->Clicontratos->save($contrato)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $contrato->id);
				$this->sincronizacontrato($contrato->idcliente);
				ClienteDomainBridge::emit(ClienteDomainEventType::CONTRATO_CRIADO, [
					'idcliente' => (int)$contrato->idcliente,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'actor_user_id' => (int)$this->Auth->user('id'),
					'title' => __('Item de contrato cadastrado'),
					'message' => __('Novo item de contrato vinculado ao cliente (id {0}).', $contrato->id),
					'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $contrato->idcliente]),
					'entity_type' => 'Clicontrato',
					'entity_id' => $contrato->id,
				]);
                $this->Flash->success(__('O item do contrato foi cadastrado com sucesso!'));
                return $this->redirect(["controller" => "Clientes", 'action' => 'edit', $contrato->idcliente]);
            }
            $this->Flash->error(__('Não foi possível cadastrar o item do contrato.'));
        }

		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';

        $this->set('idcliente', $idcliente);
		$this->set('produtos', $produtosOpt);
		$this->set('contrato', $contrato);
		$this->set('title', 'Cadastro de item do contrato');
	}

	public function edit($id = null) {
		$contrato = $this->Clicontratos->get($id);

		@$contrato->vlunit = number_format($contrato->vlunit, 2, ',', '.');
		@$contrato->vltotal = number_format($contrato->vltotal, 2, ',', '.');
		@$contrato->dtcontratacao = date_format($contrato->dtcontratacao, 'd/m/Y');
		@$contrato->dtvalidade = date_format($contrato->dtvalidade, 'd/m/Y');
		@$contrato->dtcancelamento = date_format($contrato->dtcancelamento, 'd/m/Y');
        
		if ($this->request->is(['post', 'put'])) {
            $data = $this->request->getData();

			$data['vlunit'] = str_replace('.', '', $data['vlunit']);
			$data['vlunit'] = str_replace(',', '.', $data['vlunit']);

			$data['vltotal'] = str_replace('.', '', $data['vltotal']);
			$data['vltotal'] = str_replace(',', '.', $data['vltotal']);

            $contrato = $this->Clicontratos->patchEntity($contrato, $data);

            if ($this->Clicontratos->save($contrato)) {
				$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $contrato->id);
				$this->sincronizacontrato($contrato->idcliente);
				ClienteDomainBridge::emit(ClienteDomainEventType::CONTRATO_ATUALIZADO, [
					'idcliente' => (int)$contrato->idcliente,
					'idempresa' => (int)$this->Auth->user('idempresa'),
					'actor_user_id' => (int)$this->Auth->user('id'),
					'title' => __('Item de contrato atualizado'),
					'message' => __('Contrato alterado (item id {0}).', $contrato->id),
					'action_url' => Router::url(['controller' => 'Clientes', 'action' => 'edit', $contrato->idcliente]),
					'entity_type' => 'Clicontrato',
					'entity_id' => $contrato->id,
				]);
                $this->Flash->success(__('O item do contrato foi alterado com sucesso!'));
                return $this->redirect(["controller" => "Clientes", 'action' => 'edit', $contrato->idcliente]);
            }
            $this->Flash->error(__('Não foi possível cadastrar o item do contrato.'));
        }
		
		$produtosOpt = [0 => 'Código'];
		$produtosOpt1 = $this->Produtos->find('all')->where(['idempresa' => $this->Auth->user('idempresa'), 'ativo' => 1])->order(['descricao'])->toArray();
		foreach($produtosOpt1 as $reg) $produtosOpt[$reg->codigo] =  $reg->descricao . ' (' . $reg->codigo . ')';

        $this->set('idcliente', $contrato->idcliente);
        $this->set('produtos', $produtosOpt);
		$this->set('contrato', $contrato);
		$this->set('title', 'Editar item do contrato');
	}

	public function view($id = null) {
		$contrato = $this->Clicontratos->get($id, ['contain' => ['Clientes']]);
		if ((int)$contrato->idempresa !== (int)$this->Auth->user('idempresa')) {
			throw new \Cake\Http\Exception\NotFoundException(__('Contrato não encontrado.'));
		}

		$this->loadModel('Faturas');
		$this->loadModel('Atividades');

		$faturasRelacionadas = $this->Faturas->find('all')
			->where([
				'Faturas.idempresa' => $this->Auth->user('idempresa'),
				'Faturas.idcliente' => $contrato->idcliente,
			])
			->contain([
				'Clientes' => ['fields' => ['Clientes.id', 'Clientes.razaosocial', 'Clientes.tipo', 'Clientes.nome']],
			])
			->order(['Faturas.id' => 'DESC'])
			->limit(50)
			->toArray();

		$auditoriaContrato = $this->Atividades->find('all')
			->contain(['Users' => ['fields' => ['Users.id', 'Users.name', 'Users.username']]])
			->where([
				'Atividades.controller' => 'Clicontratos',
				'Atividades.idtable' => (int)$id,
			])
			->order(['Atividades.id' => 'DESC'])
			->limit(80)
			->toArray();

		$this->set('contrato', $contrato);
		$this->set('idcliente', (int)$contrato->idcliente);
		$this->set('title', 'Detalhe do contrato');
		$this->set('faturasRelacionadas', $faturasRelacionadas);
		$this->set('auditoriaContrato', $auditoriaContrato);
		$this->set('contratoDocumentos', []);
	}

	/**
	 * Fluxo de renovação: novo item no mesmo cliente (preenchimento no cadastro).
	 */
	public function renovar($id = null) {
		$contrato = $this->Clicontratos->get($id);
		if ((int)$contrato->idempresa !== (int)$this->Auth->user('idempresa')) {
			throw new \Cake\Http\Exception\NotFoundException(__('Contrato não encontrado.'));
		}
		$this->Flash->info(__('Cadastre um novo item de contrato para o cliente. Copie valores do item anterior se necessário.'));
		return $this->redirect(['action' => 'add', $contrato->idcliente]);
	}

	public function delete($id = null) {
		$contrato = $this->Clicontratos->get($id);

		if ($this->Clicontratos->delete($contrato)) {
			$this->Atividades->registrar($this->Auth->user('id'), $this->request->getParam('controller'), $this->request->getParam('action'), $contrato->id);
			if($produto->tipo == 1)  $this->Flash->success(__('O contrato foi deletado com sucesso!.'));
			else $this->Flash->success('O contrato foi deletado com sucesso!');
			return $this->redirect(['action' => 'index']);
		}
    }

	public function sincronizacontrato($idcliente) {
		// Objeto de  comunicação
		$soap = $this->Empresas->get($this->Auth->user('idempresa'))->urlerp . 'WSPGMContratos.wso?wsdl';
		try {
			$soap = new CakeSoap(['wsdl' => $soap]);
			if ($soap === null) throw new \Exception('Erro');
		} catch (\Exception $e) {
			$this->Flash->error(__('O WS não pode ser acessado no momento!.'));
			return $this->redirect(['controller' => 'Produtos', 'action' => 'index']);
		}

		// CNPJ do cliente
		$cnpj = $this->Clientes->get($idcliente)->cnpj;

		// Todos os contratos do cliente
		$contratos = $this->Clicontratos->find('all')->where(['idcliente' => $idcliente])->order(['id' => 'ASC'])->toArray();
		
		// Array para armazenar os contratos formatados
		$contratosArr = ['contratos' => []];

		foreach ($contratos as $contrato) {
			$idcontrato = $contrato->id;

			unset($contrato->id);
			unset($contrato->idempresa);
			unset($contrato->idcliente);
			unset($contrato->erp);

			// Adicionar o contrato formatado ao array de contratos
			$contratosArr['contratos'][] = $contrato;
		}

		// Converter o array de contratos em JSON
		$json = json_encode($contratosArr, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$response = $soap->sendRequest('GerenciaContrato', [
		'Data' => [
				'iEmpresa' => $this->Auth->user('idempresa'),
				'sToken' => $this->Empresas->get($this->Auth->user('idempresa'))->token,
				'sJSON' => $json,
				'sCNPJ' => $cnpj,
			]
		]);

		if(!in_array($response->GerenciaContratoResult->cStatus, [201, 200])) $this->Flash->error($response->GerenciaContratoResult->sMsg);
	}
}